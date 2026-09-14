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

<main>
    <div class="mb-6 d-flex justify-content-between align-items-center gap-3" style="flex-wrap: wrap;">
        <div>
            <h1 class="h4 mb-1">Manajemen Pengguna</h1>
            <p class="text-muted small mb-0">Kelola pengguna dan role mereka</p>
        </div>
        <?php if ($action !== 'add' && $action !== 'edit'): ?>
        <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-3" id="btnTambahPengguna" style="white-space: nowrap;">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 1.1rem; height: 1.1rem; flex-shrink: 0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m7-7H5"></path>
            </svg>
            <span>Tambah Pengguna</span>
        </button>
        <?php endif; ?>
    </div>

    <?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="border rounded p-5 bg-white mb-6">
        <h2 class="h6 mb-4"><?php echo $action === 'add' ? 'Tambah Pengguna Baru' : 'Edit Pengguna'; ?></h2>
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
                <label class="form-check">
                    <input type="checkbox" name="is_active" class="form-check-input" <?php echo ($editUser['is_active'] ?? 1) ? 'checked' : ''; ?>>
                    <span>Aktif</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo $action === 'add' ? 'Tambah' : 'Simpan'; ?>
                </button>
                <a href="pengguna" class="btn btn-outline-secondary">Batal</a>
            </div>
        </form>
    </div>
    <?php else: ?>

    <div class="border rounded overflow-hidden bg-white">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 25%;">Nama</th>
                        <th style="width: 25%;">Username</th>
                        <th style="width: 20%;">Role</th>
                        <th style="width: 15%;">Status</th>
                        <th style="width: 10%; text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($u['nama']); ?></td>
                        <td><code style="background: #f1f5f9; padding: 0.2rem 0.4rem; border-radius: 0.3rem; font-size: 0.85rem;"><?php echo htmlspecialchars($u['username']); ?></code></td>
                        <td>
                            <span class="badge" style="background: <?php 
                                echo $u['role'] === 'administrator' ? '#fef3c7; color: #92400e;' : 
                                     ($u['role'] === 'author' ? '#d1fae5; color: #065f46;' : '#e0e7ff; color: #3730a3;');
                            ?>">
                                <?php echo $roleLabels[$u['role']] ?? 'Unknown'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                            <span class="badge" style="background: #d1fae5; color: #065f46;">Aktif</span>
                            <?php else: ?>
                            <span class="badge" style="background: #fee2e2; color: #991b1b;">Nonaktif</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <div class="d-flex gap-3" style="justify-content: center;">
                                <a href="pengguna?action=edit&id=<?php echo $u['id']; ?>" class="btn-icon btn-edit" title="Edit">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <?php if ($u['id'] !== 1): ?>
                                <button type="button" class="btn-icon btn-del" onclick="hapusPengguna(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>')" title="Hapus">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <style>
        .form-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
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
</main>

<?php include __DIR__ . '/footer.php'; ?>
