<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

function kategori_slug(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('~[^-\w]+~', '', $text);
    if ($text === '') {
        return uniqid('kategori-');
    }
    return $text;
}

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';
$openAddModal = $action === 'add';
$openEditModal = false;

// Delete
if ($action === 'delete' && $id > 0) {
    // Check if kategori has berita
    $check = $conn->query("SELECT COUNT(*) AS jml FROM berita WHERE kategori_id = $id");
    $canDelete = true;
    if ($check) {
        $row = $check->fetch_assoc();
        if ((int)$row['jml'] > 0) {
            $canDelete = false;
        }
    }
    if ($canDelete) {
        $stmt = $conn->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            admin_log($conn, 'delete', "Menghapus kategori ID $id");
            $stmt->close();
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit;
            }
            header('Location: kategori?success=delete');
            exit;
        }
        $stmt->close();
    }
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $canDelete ? 'Gagal menghapus kategori' : 'Kategori masih memiliki berita']);
        exit;
    }
header('Location: kategori' . (!$canDelete ? '?err=has_berita' : ''));
    exit;
}

// Bulk delete
if ($action === 'bulk_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = (string)($_POST['ids'] ?? '');
    $ids = array_values(array_filter(array_map('intval', explode(',', $raw))));
    $deleted = 0;
    $skipped = 0;
    foreach ($ids as $bid) {
        if ($bid <= 0) continue;
        $cnt = 0;
        $q = $conn->query("SELECT COUNT(*) AS jml FROM berita WHERE kategori_id = $bid");
        if ($q) $cnt = (int)$q->fetch_assoc()['jml'];
        if ($cnt > 0) { $skipped++; continue; }
        $stmt = $conn->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->bind_param('i', $bid);
        if ($stmt->execute() && $stmt->affected_rows > 0) $deleted++;
        $stmt->close();
    }
    header('Content-Type: application/json; charset=utf-8');
    $msg = "Berhasil menghapus $deleted kategori.";
    if ($skipped > 0) $msg .= " $skipped kategori dilewati karena masih memiliki berita.";
    echo json_encode(['success' => true, 'deleted' => $deleted, 'skipped' => $skipped, 'message' => $msg]);
    exit;
}

// Fetch edit data
$editKategori = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM kategori WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editKategori = $result->fetch_assoc();
    $stmt->close();
    if ($editKategori) {
        $openEditModal = true;
    } else {
        $action = 'list';
    }
}

// Process add/edit POST
if (in_array($action, ['add', 'edit'], true) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $grid_count = (int)($_POST['grid_count'] ?? 12);
    $grid_style = strtolower(trim((string)($_POST['grid_style'] ?? 'grid')));
    $animasi = strtolower(trim((string)($_POST['animasi'] ?? 'fade-up')));

    // Validate grid_count
    if ($grid_count < 4) $grid_count = 4;
    if ($grid_count > 50) $grid_count = 50;

    // Validate grid_style
    if (!in_array($grid_style, ['grid', 'list', 'masonry', 'overlay', 'magazine'], true)) {
        $grid_style = 'grid';
    }
    if (!in_array($animasi, ['fade-up', 'fade-down', 'fade-left', 'fade-right', 'zoom-in', 'flip'], true)) {
        $animasi = 'fade-up';
    }
    $chkAnim = $conn->query("SHOW COLUMNS FROM kategori LIKE 'animasi'");
    if ($chkAnim && $chkAnim->num_rows === 0) {
        $conn->query("ALTER TABLE kategori ADD COLUMN animasi VARCHAR(30) NOT NULL DEFAULT 'fade-up' AFTER grid_style");
    }

    if ($nama === '') {
        $error = 'Nama kategori wajib diisi.';
    } else {
        if ($slug === '') {
            $slug = kategori_slug($nama);
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO kategori (nama, slug, grid_count, grid_style, animasi) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('ssiss', $nama, $slug, $grid_count, $grid_style, $animasi);
        } else {
            $stmt = $conn->prepare("UPDATE kategori SET nama = ?, slug = ?, grid_count = ?, grid_style = ?, animasi = ? WHERE id = ?");
            $stmt->bind_param('ssissi', $nama, $slug, $grid_count, $grid_style, $animasi, $id);
        }

        if ($stmt->execute()) {
            admin_log($conn, $action, ($action === 'add' ? 'Menambah kategori: ' : 'Memperbarui kategori: ') . $nama);
            $stmt->close();
            $successAction = $action === 'add' ? 'add' : 'edit';
            header('Location: kategori?success=' . $successAction);
            exit;
        }
        $error = 'Terjadi kesalahan saat menyimpan kategori.';
        $stmt->close();
    }
}

$errGet = $_GET['err'] ?? '';

// Check for success redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'add') {
        $success = 'Kategori berhasil ditambahkan';
    } elseif ($_GET['success'] === 'edit') {
        $success = 'Kategori berhasil diperbarui';
    } elseif ($_GET['success'] === 'delete') {
        $success = 'Kategori berhasil dihapus';
    }
}

// Fetch all kategori
$kategoris = [];
$result = $conn->query("SELECT k.*, (SELECT COUNT(*) FROM berita WHERE kategori_id = k.id) AS jml_berita FROM kategori k ORDER BY k.nama ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $kategoris[] = $row;
    }
}

include __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Kategori</h1>
    <a href="kategori?action=add" class="btn btn-primary btn-sm" data-modal-open="modalTambahKategori">
        <i class="bi bi-plus-lg me-1"></i> Tambah Kategori
    </a>
</div>

<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '<?php echo addslashes($success); ?>',
                timer: 2500,
                timerProgressBar: true,
                showConfirmButton: false
            });
        });
    </script>
<?php endif; ?>

<?php if ($errGet === 'has_berita'): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'warning',
                title: 'Tidak Dapat Dihapus',
                text: 'Tidak dapat menghapus kategori yang masih memiliki berita.',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        });
    </script>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h2 class="h6 mb-0">Daftar Kategori</h2>
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
                    <?php echo ui_icon('trash', 'w-4 h-4'); ?> Hapus Terpilih (<span id="bulkCount">0</span>)
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle table-sm">
                <thead>
                <tr>
                    <th width="40"><input type="checkbox" class="form-check-input" id="bulkAll" title="Pilih semua"></th>
                    <th width="60">#</th>
                    <th>Nama Kategori</th>
                    <th width="120">Jml Berita</th>
                    <th width="130" class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($kategoris)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">Belum ada kategori.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($kategoris as $index => $kat): ?>
                        <tr>
                            <td><input type="checkbox" class="form-check-input bulk-cb" value="<?php echo (int)$kat['id']; ?>"></td>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($kat['nama']); ?></td>
                            <td>
                                <span class="badge bg-info-subtle text-info"><?php echo (int)$kat['jml_berita']; ?> berita</span>
                            </td>
                            <td class="text-end">
                                <a href="kategori?action=edit&id=<?php echo (int)$kat['id']; ?>" class="btn-icon btn-edit" title="Ubah" aria-label="Ubah"
                                   data-modal-open="modalEditKategori"
                                   data-id="<?php echo (int)$kat['id']; ?>"
                                   data-nama="<?php echo htmlspecialchars($kat['nama']); ?>"
                                   data-slug="<?php echo htmlspecialchars($kat['slug']); ?>">
                                    <?php echo ui_icon('edit', 'w-5 h-5'); ?>
                                </a>
                                <button type="button" class="btn-icon btn-del btn-delete-kategori" title="Hapus" aria-label="Hapus" data-id="<?php echo (int)$kat['id']; ?>" data-nama="<?php echo htmlspecialchars($kat['nama']); ?>">
                                    <?php echo ui_icon('trash', 'w-5 h-5'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="admin-modal<?php echo $openAddModal ? ' is-open' : ''; ?>" id="modalTambahKategori" aria-hidden="true">
    <div class="admin-modal-panel" style="width: min(100%, 520px);">
        <div class="admin-modal-header">
            <div>
                <h2 class="h4 mb-1">Tambah Kategori</h2>
                <p class="text-muted small mb-0">Buat kategori baru untuk mengelompokkan berita.</p>
            </div>
            <button type="button" class="admin-modal-close" data-modal-close aria-label="Tutup">&times;</button>
        </div>
        <div class="admin-modal-body">
            <?php if ($error && $action === 'add'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: '<?php echo addslashes($error); ?>',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                    });
                </script>
            <?php endif; ?>
            <form method="post" action="kategori?action=add">
                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="nama" class="form-control" required
                           value="<?php echo htmlspecialchars($action === 'add' ? ($_POST['nama'] ?? '') : ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug (opsional)</label>
                    <input type="text" name="slug" class="form-control"
                           value="<?php echo htmlspecialchars($action === 'add' ? ($_POST['slug'] ?? '') : ''); ?>">
                    <div class="form-text">Jika dikosongkan akan dibuat otomatis dari nama.</div>
                </div><div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-modal-close>Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="admin-modal<?php echo $openEditModal ? ' is-open' : ''; ?>" id="modalEditKategori" aria-hidden="true">
    <div class="admin-modal-panel" style="width: min(100%, 520px);">
        <div class="admin-modal-header">
            <div>
                <h2 class="h4 mb-1">Ubah Kategori</h2>
                <p class="text-muted small mb-0">Edit nama atau slug kategori.</p>
            </div>
            <button type="button" class="admin-modal-close" data-modal-close aria-label="Tutup">&times;</button>
        </div>
        <div class="admin-modal-body">
            <?php if ($error && $action === 'edit'): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: '<?php echo addslashes($error); ?>',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                    });
                </script>
            <?php endif; ?>
            <form method="post" action="kategori?action=edit&id=<?php echo (int)$id; ?>" id="formEditKategori">
                <div class="mb-3">
                    <label class="form-label">Nama Kategori</label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required
                           value="<?php echo htmlspecialchars($editKategori['nama'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug (opsional)</label>
                    <input type="text" name="slug" id="edit_slug" class="form-control"
                           value="<?php echo htmlspecialchars($editKategori['slug'] ?? ''); ?>">
                    <div class="form-text">Jika dikosongkan akan dibuat otomatis dari nama.</div>
                </div><div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-modal-close>Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    // Populate edit modal fields from data attributes when opened via edit button
    var editBtns = document.querySelectorAll('[data-modal-open="modalEditKategori"][data-id]');
    editBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var namaInput = document.getElementById('edit_nama');
            var slugInput = document.getElementById('edit_slug');
            var form = document.getElementById('formEditKategori');
            if (namaInput) namaInput.value = btn.getAttribute('data-nama') || '';
            if (slugInput) slugInput.value = btn.getAttribute('data-slug') || '';
            if (form) form.action = 'kategori?action=edit&id=' + (btn.getAttribute('data-id') || '');
        });
    });

    // Delete confirmation with SweetAlert2
    var deleteBtns = document.querySelectorAll('.btn-delete-kategori');
    deleteBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var katId = this.getAttribute('data-id');
            var katNama = this.getAttribute('data-nama');
            
            Swal.fire({
                title: 'Hapus Kategori?',
                text: 'Apakah Anda yakin ingin menghapus kategori "' + katNama + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(function (result) {
                if (result.isConfirmed) {
                    fetch('kategori?action=delete&id=' + katId, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'Kategori berhasil dihapus',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(function () {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: data.error || 'Gagal menghapus kategori',
                                timer: 3000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            });
                        }
                    })
                    .catch(function () {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menghapus',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                    });
                }
            });
        });
    });
})();
</script>

<script>
(function () {
    var cbs = Array.prototype.slice.call(document.querySelectorAll('.bulk-cb'));
    var all = document.getElementById('bulkAll');
    var btn = document.getElementById('bulkDeleteBtn');
    var countEl = document.getElementById('bulkCount');

    function refresh() {
        var sel = cbs.filter(function (c) { return c.checked; }).length;
        if (all) all.checked = cbs.length > 0 && sel === cbs.length;
        if (countEl) countEl.textContent = sel;
        if (btn) btn.disabled = sel === 0;
    }

    cbs.forEach(function (c) { c.addEventListener('change', refresh); });
    if (all) all.addEventListener('change', function () {
        cbs.forEach(function (c) { c.checked = all.checked; });
        refresh();
    });

    if (btn) btn.addEventListener('click', function () {
        var sel = cbs.filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
        if (!sel.length) return;
        Swal.fire({
            title: 'Hapus ' + sel.length + ' Kategori?',
            text: 'Seluruh kategori yang masih memiliki berita akan dilewati.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            fetch('kategori?action=bulk_delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'ids=' + encodeURIComponent(sel.join(','))
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: d.message || 'Kategori berhasil dihapus',
                        timer: 2200,
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(function () { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: d.error || 'Gagal menghapus kategori' });
                }
            })
            .catch(function () {
                Swal.fire({ icon: 'error', title: 'Error!', text: 'Terjadi kesalahan saat menghapus' });
            });
        });
    });

    refresh();
})();
</script>

<?php
include __DIR__ . '/footer.php';
