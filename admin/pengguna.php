<?php
require_once __DIR__ . '/auth.php';
admin_require_login();
admin_require_role('administrator');

$action = $_GET['action'] ?? '';
$userId = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'author');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($nama === '' || $username === '') {
        $error = 'Nama dan username harus diisi.';
    } elseif ($action === 'add' && $password === '') {
        $error = 'Password wajib diisi untuk pengguna baru.';
    } else {
        if ($action === 'add') {
            // Cek username sudah ada
            $cek = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
            $cek->bind_param('s', $username);
            $cek->execute();
            if ($cek->get_result()->num_rows > 0) {
                $error = 'Username sudah digunakan.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO admin_users (nama, username, password, role, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssi', $nama, $username, $hashedPassword, $role, $isActive);
                if ($stmt->execute()) {
                    admin_log($conn, 'create_user', "User baru: $username ($role)");
                    $success = 'Pengguna berhasil ditambahkan.';
                    $action = '';
                } else {
                    $error = 'Gagal menambahkan pengguna.';
                }
                $stmt->close();
            }
            $cek->close();
        } elseif ($action === 'edit' && $userId > 0) {
            $updates = [];
            $params = [];
            $types = '';

            $updates[] = 'nama = ?';
            $params[] = $nama;
            $types .= 's';

            if ($username !== '') {
                // Cek username tidak dipakai user lain
                $cek = $conn->prepare("SELECT id FROM admin_users WHERE username = ? AND id != ?");
                $cek->bind_param('si', $username, $userId);
                $cek->execute();
                if ($cek->get_result()->num_rows > 0) {
                    $error = 'Username sudah digunakan user lain.';
                    $cek->close();
                } else {
                    $cek->close();
                    $updates[] = 'username = ?';
                    $params[] = $username;
                    $types .= 's';
                }
            }

            if ($password !== '' && $error === '') {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updates[] = 'password = ?';
                $params[] = $hashedPassword;
                $types .= 's';
            }

            if ($error === '') {
                $updates[] = 'role = ?';
                $params[] = $role;
                $types .= 's';

                $updates[] = 'is_active = ?';
                $params[] = $isActive;
                $types .= 'i';

                $params[] = $userId;
                $types .= 'i';

                $sql = "UPDATE admin_users SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    admin_log($conn, 'update_user', "Update user: $username ($role)");
                    $success = 'Pengguna berhasil diperbarui.';
                    $action = '';
                } else {
                    $error = 'Gagal memperbarui pengguna.';
                }
                $stmt->close();
            }
        }
    }
}

// Handle delete
if (isset($_POST['_method']) && $_POST['_method'] === 'DELETE' && $userId > 0) {
    if ($userId === 1) {
        $error = 'Tidak bisa menghapus admin utama.';
    } else {
        $stmt = $conn->prepare("SELECT username FROM admin_users WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user) {
            $del = $conn->prepare("DELETE FROM admin_users WHERE id = ?");
            $del->bind_param('i', $userId);
            if ($del->execute()) {
                admin_log($conn, 'delete_user', "Hapus user: {$user['username']}");
                $success = 'Pengguna berhasil dihapus.';
            } else {
                $error = 'Gagal menghapus pengguna.';
            }
            $del->close();
        }
    }
}

// Get users
$users = [];
$result = $conn->query("SELECT id, nama, username, role, is_active, created_at FROM admin_users ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Get user for edit
$editUser = null;
if ($action === 'edit' && $userId > 0) {
    $stmt = $conn->prepare("SELECT id, nama, username, role, is_active FROM admin_users WHERE id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $editUser = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$roleLabels = [
    'administrator' => 'Administrator',
    'author' => 'Author',
    'editor' => 'Editor'
];

include __DIR__ . '/header.php';

if ($success) {
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '" . addslashes($success) . "',
            timer: 2500,
            timerProgressBar: true,
            showConfirmButton: false
        });
    });
    </script>";
}

if ($error) {
    echo "<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: '" . addslashes($error) . "',
            confirmButtonText: 'OK'
        });
    });
    </script>";
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 mb-0">Manajemen Pengguna</h1>
        <p class="text-muted small mb-0">Kelola pengguna dan role mereka</p>
    </div>
    <?php if ($action !== 'add' && $action !== 'edit'): ?>
    <a href="pengguna?action=add" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
        <?php echo ui_icon('plus', 'w-4 h-4'); ?> Tambah Pengguna
    </a>
    <?php endif; ?>
</div>

<?php if ($action === 'add' || $action === 'edit'): ?>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
        <h2 class="h6 mb-3"><?php echo $action === 'add' ? 'Tambah Pengguna Baru' : 'Edit Pengguna'; ?></h2>
        <form method="post" class="form-grid">
            <div class="form-group">
                <label class="form-label">Nama Lengkap *</label>
                <input type="text" name="nama" class="form-control" value="<?php echo htmlspecialchars($editUser['nama'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Username *</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($editUser['username'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Password <?php echo $action === 'edit' ? '(Kosongkan jika tidak diubah)' : '*'; ?></label>
                <input type="password" name="password" class="form-control" <?php echo $action === 'add' ? 'required' : ''; ?>>
            </div>

            <div class="form-group">
                <label class="form-label">Role *</label>
                <select name="role" class="form-select" required>
                    <option value="">-- Pilih Role --</option>
                    <?php foreach ($roleLabels as $val => $label): ?>
                    <option value="<?php echo $val; ?>" <?php echo ($editUser['role'] ?? '') === $val ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-check form-switch mt-2">
                    <input type="checkbox" name="is_active" class="form-check-input" <?php echo ($editUser['is_active'] ?? 1) ? 'checked' : ''; ?>>
                    <span class="form-check-label">Aktif</span>
                </label>
            </div>

            <div class="form-actions mt-3">
                <button type="submit" class="btn btn-primary">
                    <?php echo $action === 'add' ? 'Tambah' : 'Simpan'; ?>
                </button>
                <a href="pengguna" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
</div>
<?php else: ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h6 mb-0">Daftar Pengguna</h2>
        </div>
        <div class="table-responsive">
            <table class="table align-middle table-sm">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Nama</th>
                        <th>Username</th>
                        <th width="150">Role</th>
                        <th width="120">Status</th>
                        <th width="120" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-3">Belum ada pengguna.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td class="fw-semibold"><?php echo htmlspecialchars($u['nama']); ?></td>
                        <td><code><?php echo htmlspecialchars($u['username']); ?></code></td>
                        <td>
                            <?php if ($u['role'] === 'administrator'): ?>
                                <span class="badge bg-warning-subtle text-warning">Administrator</span>
                            <?php elseif ($u['role'] === 'author'): ?>
                                <span class="badge bg-success-subtle text-success">Author</span>
                            <?php else: ?>
                                <span class="badge bg-info-subtle text-info"><?php echo htmlspecialchars($roleLabels[$u['role']] ?? $u['role']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success-subtle text-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-2 justify-content-end">
                                <a href="pengguna?action=edit&id=<?php echo $u['id']; ?>" class="btn-icon btn-edit" title="Edit" aria-label="Edit">
                                    <?php echo ui_icon('edit', 'w-5 h-5'); ?>
                                </a>
                                <?php if ($u['id'] !== 1): ?>
                                <button type="button" class="btn-icon btn-del" onclick="hapusPengguna(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>')" title="Hapus" aria-label="Hapus">
                                    <?php echo ui_icon('trash', 'w-5 h-5'); ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

     <style>
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        @media (max-width: 767px) {
            .table {
                font-size: 0.8rem;
            }
            .table th,
            .table td {
                padding: 0.6rem 0.4rem;
            }
            .btn-icon {
                width: 1.8rem !important;
                height: 1.8rem !important;
            }
            .btn-icon svg {
                width: 0.85rem !important;
                height: 0.85rem !important;
            }
            .badge {
                font-size: 0.65rem;
                padding: 0.25rem 0.5rem;
            }
            .btn-primary {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }
            .h4 {
                font-size: 1.3rem;
            }
            .mb-6 {
                flex-direction: column;
                align-items: flex-start !important;
            }
            #btnTambahPengguna {
                width: 100%;
            }
            .d-flex.gap-3 {
                gap: 0.5rem !important;
            }
            code {
                font-size: 0.75rem !important;
            }
        }
    </style>

    <script>
    document.getElementById('btnTambahPengguna')?.addEventListener('click', () => {
        window.location.href = 'pengguna?action=add';
    });

    function hapusPengguna(id, username) {
        Swal.fire({
            icon: 'warning',
            title: 'Hapus Pengguna?',
            text: `Apakah Anda yakin ingin menghapus pengguna "${username}"?`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc2626'
        }).then(result => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="_method" value="DELETE">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    </script>

    <?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
