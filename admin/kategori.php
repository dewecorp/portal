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
    $grid_style = $_POST['grid_style'] ?? 'grid';
    
    // Validate grid_count
    if ($grid_count < 4) $grid_count = 4;
    if ($grid_count > 50) $grid_count = 50;
    
    // Validate grid_style
    if (!in_array($grid_style, ['grid', 'list', 'masonry'])) {
        $grid_style = 'grid';
    }

    if ($nama === '') {
        $error = 'Nama kategori wajib diisi.';
    } else {
        if ($slug === '') {
            $slug = kategori_slug($nama);
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO kategori (nama, slug, grid_count, grid_style) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssis', $nama, $slug, $grid_count, $grid_style);
        } else {
            $stmt = $conn->prepare("UPDATE kategori SET nama = ?, slug = ?, grid_count = ?, grid_style = ? WHERE id = ?");
            $stmt->bind_param('ssisi', $nama, $slug, $grid_count, $grid_style, $id);
        }

        if ($stmt->execute()) {
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
        <h2 class="h6 mb-3">Daftar Kategori</h2>
        <div class="table-responsive">
            <table class="table align-middle table-sm">
                <thead>
                <tr>
                    <th width="60">#</th>
                    <th>Nama Kategori</th>
                    <th width="120">Jml Berita</th>
                    <th width="130" class="text-end">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($kategoris)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted py-3">Belum ada kategori.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($kategoris as $index => $kat): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo htmlspecialchars($kat['nama']); ?></td>
                            <td>
                                <span class="badge bg-info-subtle text-info"><?php echo (int)$kat['jml_berita']; ?> berita</span>
                            </td>
                            <td class="text-end">
                                <a href="kategori?action=edit&id=<?php echo (int)$kat['id']; ?>" class="btn btn-sm btn-outline-primary"
                                   data-modal-open="modalEditKategori"
                                   data-id="<?php echo (int)$kat['id']; ?>"
                                   data-nama="<?php echo htmlspecialchars($kat['nama']); ?>"
                                   data-slug="<?php echo htmlspecialchars($kat['slug']); ?>"
                                   data-grid-count="<?php echo (int)($kat['grid_count'] ?? 12); ?>"
                                   data-grid-style="<?php echo htmlspecialchars($kat['grid_style'] ?? 'grid'); ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-kategori" data-id="<?php echo (int)$kat['id']; ?>" data-nama="<?php echo htmlspecialchars($kat['nama']); ?>">
                                    <i class="bi bi-trash"></i>
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
                </div>
                
                <!-- Display Settings -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-grid me-2"></i>Pengaturan Tampilan</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Jumlah Grid</label>
                            <input type="number" name="grid_count" class="form-control" min="4" max="50" value="<?php echo (int)($_POST['grid_count'] ?? 12); ?>">
                            <div class="form-text">Jumlah berita yang ditampilkan per halaman (4-50)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Style Grid</label>
                            <div class="mb-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="grid_style" id="grid_style_grid" value="grid" <?php echo ($action === 'add' && ($_POST['grid_style'] ?? 'grid') === 'grid') || ($action !== 'add' && ($_POST['grid_style'] ?? 'grid') === 'grid') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="grid_style_grid">
                                        <i class="bi bi-grid-3x3-gap me-1"></i>Grid
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="grid_style" id="grid_style_list" value="list" <?php echo isset($_POST['grid_style']) && $_POST['grid_style'] === 'list' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="grid_style_list">
                                        <i class="bi bi-list-ul me-1"></i>List
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="grid_style" id="grid_style_masonry" value="masonry" <?php echo isset($_POST['grid_style']) && $_POST['grid_style'] === 'masonry' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="grid_style_masonry">
                                        <i class="bi bi-columns-gap me-1"></i>Masonry
                                    </label>
                                </div>
                            </div>
                            <div class="form-text mb-3">Pilih tata letak tampilan berita</div>
                            
                            <!-- Grid Style Previews -->
                            <div class="grid grid-cols-3 gap-3 mt-3">
                                <!-- Grid Preview -->
                                <div class="grid-style-preview cursor-pointer border-2 border-gray-200 rounded-lg p-3 transition-all hover:shadow-md" data-style="grid">
                                    <div class="text-xs font-bold text-center mb-2 text-purple-700">Grid</div>
                                    <div class="grid grid-cols-2 gap-1" style="min-height: 80px;">
                                        <div class="bg-gradient-to-br from-purple-100 to-purple-200 rounded aspect-square flex items-center justify-center">
                                            <div class="w-3/4 space-y-1">
                                                <div class="h-1.5 bg-purple-300 rounded"></div>
                                                <div class="h-1 bg-purple-200 rounded w-2/3"></div>
                                            </div>
                                        </div>
                                        <div class="bg-gradient-to-br from-blue-100 to-blue-200 rounded aspect-square flex items-center justify-center">
                                            <div class="w-3/4 space-y-1">
                                                <div class="h-1.5 bg-blue-300 rounded"></div>
                                                <div class="h-1 bg-blue-200 rounded w-2/3"></div>
                                            </div>
                                        </div>
                                        <div class="bg-gradient-to-br from-purple-100 to-purple-200 rounded aspect-square flex items-center justify-center">
                                            <div class="w-3/4 space-y-1">
                                                <div class="h-1.5 bg-purple-300 rounded"></div>
                                                <div class="h-1 bg-purple-200 rounded w-2/3"></div>
                                            </div>
                                        </div>
                                        <div class="bg-gradient-to-br from-blue-100 to-blue-200 rounded aspect-square flex items-center justify-center">
                                            <div class="w-3/4 space-y-1">
                                                <div class="h-1.5 bg-blue-300 rounded"></div>
                                                <div class="h-1 bg-blue-200 rounded w-2/3"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- List Preview -->
                                <div class="grid-style-preview cursor-pointer border-2 border-gray-200 rounded-lg p-3 transition-all hover:shadow-md" data-style="list">
                                    <div class="text-xs font-bold text-center mb-2 text-purple-700">List</div>
                                    <div class="space-y-1.5" style="min-height: 80px;">
                                        <div class="flex gap-1.5 bg-gradient-to-r from-purple-50 to-purple-100 p-1.5 rounded">
                                            <div class="w-8 h-8 bg-purple-300 rounded flex-shrink-0"></div>
                                            <div class="flex-1 space-y-1">
                                                <div class="h-1.5 bg-purple-300 rounded w-3/4"></div>
                                                <div class="h-1 bg-purple-200 rounded w-full"></div>
                                                <div class="h-1 bg-purple-200 rounded w-1/2"></div>
                                            </div>
                                        </div>
                                        <div class="flex gap-1.5 bg-gradient-to-r from-blue-50 to-blue-100 p-1.5 rounded">
                                            <div class="w-8 h-8 bg-blue-300 rounded flex-shrink-0"></div>
                                            <div class="flex-1 space-y-1">
                                                <div class="h-1.5 bg-blue-300 rounded w-3/4"></div>
                                                <div class="h-1 bg-blue-200 rounded w-full"></div>
                                                <div class="h-1 bg-blue-200 rounded w-1/2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Masonry Preview -->
                                <div class="grid-style-preview cursor-pointer border-2 border-gray-200 rounded-lg p-3 transition-all hover:shadow-md" data-style="masonry">
                                    <div class="text-xs font-bold text-center mb-2 text-purple-700">Masonry</div>
                                    <div class="columns-2 gap-1.5 space-y-1.5" style="min-height: 80px;">
                                        <div class="break-inside-avoid bg-gradient-to-br from-purple-100 to-purple-200 rounded p-1.5">
                                            <div class="aspect-square bg-purple-200 rounded mb-1"></div>
                                            <div class="h-1.5 bg-purple-300 rounded w-3/4"></div>
                                        </div>
                                        <div class="break-inside-avoid bg-gradient-to-br from-blue-100 to-blue-200 rounded p-1.5">
                                            <div class="aspect-[4/3] bg-blue-200 rounded mb-1"></div>
                                            <div class="h-1.5 bg-blue-300 rounded w-3/4"></div>
                                        </div>
                                        <div class="break-inside-avoid bg-gradient-to-br from-purple-100 to-purple-200 rounded p-1.5">
                                            <div class="aspect-[3/4] bg-purple-200 rounded mb-1"></div>
                                            <div class="h-1.5 bg-purple-300 rounded w-3/4"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
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
                </div>
                
                <!-- Display Settings -->
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-grid me-2"></i>Pengaturan Tampilan</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Jumlah Grid</label>
                            <input type="number" name="grid_count" id="edit_grid_count" class="form-control" min="4" max="50" value="<?php echo (int)($editKategori['grid_count'] ?? 12); ?>">
                            <div class="form-text">Jumlah berita yang ditampilkan per halaman (4-50)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Style Grid</label>
                            <div class="mb-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="grid_style" id="edit_grid_style_grid" value="grid" <?php echo !isset($editKategori['grid_style']) || $editKategori['grid_style'] === 'grid' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="edit_grid_style_grid">
                                        <i class="bi bi-grid-3x3-gap me-1"></i>Grid
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="grid_style" id="edit_grid_style_list" value="list" <?php echo isset($editKategori['grid_style']) && $editKategori['grid_style'] === 'list' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="edit_grid_style_list">
                                        <i class="bi bi-list-ul me-1"></i>List
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="grid_style" id="edit_grid_style_masonry" value="masonry" <?php echo isset($editKategori['grid_style']) && $editKategori['grid_style'] === 'masonry' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="edit_grid_style_masonry">
                                        <i class="bi bi-columns-gap me-1"></i>Masonry
                                    </label>
                                </div>
                            </div>
                            <div class="form-text mb-3">Pilih tata letak tampilan berita</div>
                            
                            <!-- Grid Style Previews -->
                            <div class="grid grid-cols-3 gap-3 mt-3">
                                <!-- Grid Preview -->
                                <div class="grid-style-preview cursor-pointer border-2 border-gray-200 rounded-lg p-3 transition-all hover:shadow-md" data-style="grid">
                                    <div class="text-xs font-bold text-center mb-2 text-purple-700">Grid</div>
                                    <div class="grid grid-cols-2 gap-1" style="min-height: 80px;">
                                        <div class="bg-gradient-to-br from-purple-100 to-purple-200 rounded aspect-square flex items-center justify-center">
                                            <div class="w-3/4 space-y-1">
                                                <div class="h-1.5 bg-purple-300 rounded"></div>
                                                <div class="h-1 bg-purple-200 rounded w-2/3"></div>
                                            </div>
                                        </div>
                                        <div class="bg-gradient-to-br from-blue-100 to-blue-200 rounded aspect-square flex items-center justify-center">
                                            <div class="w-3/4 space-y-1">
                                                <div class="h-1.5 bg-blue-300 rounded"></div>
                                                <div class="h-1 bg-blue-200 rounded w-2/3"></div>
                                            </div>
                                        </div>
                                        <div class="bg-gradient-to-br from-purple-100 to-purple-200 rounded aspect-square flex items-center justify-center">
                                            <div class="w-3/4 space-y-1">
                                                <div class="h-1.5 bg-purple-300 rounded"></div>
                                                <div class="h-1 bg-purple-200 rounded w-2/3"></div>
                                            </div>
                                        </div>
                                        <div class="bg-gradient-to-br from-blue-100 to-blue-200 rounded aspect-square flex items-center justify-center">
                                            <div class="w-3/4 space-y-1">
                                                <div class="h-1.5 bg-blue-300 rounded"></div>
                                                <div class="h-1 bg-blue-200 rounded w-2/3"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- List Preview -->
                                <div class="grid-style-preview cursor-pointer border-2 border-gray-200 rounded-lg p-3 transition-all hover:shadow-md" data-style="list">
                                    <div class="text-xs font-bold text-center mb-2 text-purple-700">List</div>
                                    <div class="space-y-1.5" style="min-height: 80px;">
                                        <div class="flex gap-1.5 bg-gradient-to-r from-purple-50 to-purple-100 p-1.5 rounded">
                                            <div class="w-8 h-8 bg-purple-300 rounded flex-shrink-0"></div>
                                            <div class="flex-1 space-y-1">
                                                <div class="h-1.5 bg-purple-300 rounded w-3/4"></div>
                                                <div class="h-1 bg-purple-200 rounded w-full"></div>
                                                <div class="h-1 bg-purple-200 rounded w-1/2"></div>
                                            </div>
                                        </div>
                                        <div class="flex gap-1.5 bg-gradient-to-r from-blue-50 to-blue-100 p-1.5 rounded">
                                            <div class="w-8 h-8 bg-blue-300 rounded flex-shrink-0"></div>
                                            <div class="flex-1 space-y-1">
                                                <div class="h-1.5 bg-blue-300 rounded w-3/4"></div>
                                                <div class="h-1 bg-blue-200 rounded w-full"></div>
                                                <div class="h-1 bg-blue-200 rounded w-1/2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Masonry Preview -->
                                <div class="grid-style-preview cursor-pointer border-2 border-gray-200 rounded-lg p-3 transition-all hover:shadow-md" data-style="masonry">
                                    <div class="text-xs font-bold text-center mb-2 text-purple-700">Masonry</div>
                                    <div class="columns-2 gap-1.5 space-y-1.5" style="min-height: 80px;">
                                        <div class="break-inside-avoid bg-gradient-to-br from-purple-100 to-purple-200 rounded p-1.5">
                                            <div class="aspect-square bg-purple-200 rounded mb-1"></div>
                                            <div class="h-1.5 bg-purple-300 rounded w-3/4"></div>
                                        </div>
                                        <div class="break-inside-avoid bg-gradient-to-br from-blue-100 to-blue-200 rounded p-1.5">
                                            <div class="aspect-[4/3] bg-blue-200 rounded mb-1"></div>
                                            <div class="h-1.5 bg-blue-300 rounded w-3/4"></div>
                                        </div>
                                        <div class="break-inside-avoid bg-gradient-to-br from-purple-100 to-purple-200 rounded p-1.5">
                                            <div class="aspect-[3/4] bg-purple-200 rounded mb-1"></div>
                                            <div class="h-1.5 bg-purple-300 rounded w-3/4"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
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
            var gridCountInput = document.getElementById('edit_grid_count');
            var form = document.getElementById('formEditKategori');
            if (namaInput) namaInput.value = btn.getAttribute('data-nama') || '';
            if (slugInput) slugInput.value = btn.getAttribute('data-slug') || '';
            if (gridCountInput) gridCountInput.value = btn.getAttribute('data-grid-count') || '12';
            
            // Set grid style radio buttons
            var gridStyle = btn.getAttribute('data-grid-style') || 'grid';
            var gridStyleRadios = document.querySelectorAll('input[name="grid_style"]');
            gridStyleRadios.forEach(function(radio) {
                radio.checked = (radio.value === gridStyle);
            });
            
            if (form) form.action = 'kategori?action=edit&id=' + (btn.getAttribute('data-id') || '');
        });
    });
    
    // Grid style preview click handlers
    var stylePreviews = document.querySelectorAll('.grid-style-preview');
    stylePreviews.forEach(function(preview) {
        preview.addEventListener('click', function() {
            var style = this.getAttribute('data-style');
            
            // Find the parent form to scope the radio buttons
            var form = this.closest('form');
            if (!form) return;
            
            // Find radio button with this style in the same form
            var radio = form.querySelector('input[name="grid_style"][value="' + style + '"]');
            if (radio) {
                radio.checked = true;
                
                // Trigger change event
                radio.dispatchEvent(new Event('change'));
            }
        });
    });
    
    // Highlight selected grid style preview
    function updatePreviewHighlight(form) {
        if (!form) return;
        
        var selectedStyle = form.querySelector('input[name="grid_style"]:checked');
        if (!selectedStyle) return;
        
        var style = selectedStyle.value;
        var previews = form.querySelectorAll('.grid-style-preview');
        
        previews.forEach(function(preview) {
            var previewStyle = preview.getAttribute('data-style');
            if (previewStyle === style) {
                preview.classList.add('border-purple-500', 'bg-purple-50', 'shadow-md');
                preview.classList.remove('border-gray-200');
            } else {
                preview.classList.remove('border-purple-500', 'bg-purple-50', 'shadow-md');
                preview.classList.add('border-gray-200');
            }
        });
    }
    
    // Add change listeners to all grid style radio buttons
    var gridStyleRadios = document.querySelectorAll('input[name="grid_style"]');
    gridStyleRadios.forEach(function(radio) {
        radio.addEventListener('change', function() {
            var form = this.closest('form');
            updatePreviewHighlight(form);
        });
    });
    
    // Initialize preview highlights on page load
    document.addEventListener('DOMContentLoaded', function() {
        var forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            if (form.querySelector('input[name="grid_style"]')) {
                updatePreviewHighlight(form);
            }
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

<?php
include __DIR__ . '/footer.php';
