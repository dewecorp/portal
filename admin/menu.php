<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

function make_slug(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('~[^-\w]+~', '', $text);
    if ($text === '') {
        return uniqid('menu-');
    }
    return $text;
}

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';
$openAddModal = $action === 'add';
$openEditModal = $action === 'edit';

$allMenusForParent = [];
$resultParents = $conn->query("SELECT id, nama FROM menus ORDER BY urutan ASC, nama ASC");
if ($resultParents) {
    while ($row = $resultParents->fetch_assoc()) {
        $allMenusForParent[] = $row;
    }
}

$allCategories = [];
$resultCategories = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC");
if ($resultCategories) {
    while ($row = $resultCategories->fetch_assoc()) {
        $allCategories[] = $row;
    }
}

if ($action === 'save_order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $payload = json_decode(file_get_contents('php://input'), true);
    $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

    if (empty($items)) {
        echo json_encode(['success' => false, 'error' => 'Data urutan menu kosong.']);
        exit;
    }

    $parentIdsWithChildren = [];
    $stmt = $conn->prepare("UPDATE menus SET parent_id = ?, urutan = ? WHERE id = ?");
    $ok = true;
    foreach ($items as $item) {
        $menuId = (int)($item['id'] ?? 0);
        $parentId = (int)($item['parent_id'] ?? 0);
        $order = (int)($item['urutan'] ?? 0);
        if ($menuId <= 0 || $menuId === $parentId) {
            continue;
        }
        if ($parentId > 0) {
            $parentIdsWithChildren[$parentId] = true;
        }
        $stmt->bind_param('iii', $parentId, $order, $menuId);
        if (!$stmt->execute()) {
            $ok = false;
            break;
        }
    }
    $stmt->close();

    if ($ok && !empty($parentIdsWithChildren)) {
        $typeStmt = $conn->prepare("UPDATE menus SET menu_type = 'dropdown' WHERE id = ? AND menu_type = 'link'");
        foreach (array_keys($parentIdsWithChildren) as $parentMenuId) {
            $parentMenuId = (int)$parentMenuId;
            $typeStmt->bind_param('i', $parentMenuId);
            if (!$typeStmt->execute()) {
                $ok = false;
                break;
            }
        }
        $typeStmt->close();
    }

    echo json_encode($ok ? ['success' => true] : ['success' => false, 'error' => 'Gagal menyimpan urutan menu.']);
    exit;
}

if ($action === 'delete' && $id > 0) {
    $stmt = $conn->prepare("DELETE FROM menus WHERE id = ?");
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        $stmt->close();
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
        header('Location: menu');
        exit;
    }
    $stmt->close();
    
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Gagal menghapus menu']);
        exit;
    }
}

if (in_array($action, ['add', 'edit'], true) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $urutan = (int)($_POST['urutan'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $parent_id = (int)($_POST['parent_id'] ?? 0);
    $menu_type = $_POST['menu_type'] ?? 'link';
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);

    if ($nama === '') {
        $error = 'Nama menu wajib diisi.';
    } else {
        if ($slug === '') {
            $slug = make_slug($nama);
        }

        if ($menu_type !== 'dropdown' && $menu_type !== 'mega') {
            $menu_type = 'link';
        }

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO menus (nama, slug, urutan, is_active, parent_id, menu_type, kategori_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssiissi', $nama, $slug, $urutan, $is_active, $parent_id, $menu_type, $kategori_id);
        } else {
            $stmt = $conn->prepare("UPDATE menus SET nama = ?, slug = ?, urutan = ?, is_active = ?, parent_id = ?, menu_type = ?, kategori_id = ? WHERE id = ?");
            $stmt->bind_param('ssiiisii', $nama, $slug, $urutan, $is_active, $parent_id, $menu_type, $kategori_id, $id);
        }

        if ($stmt->execute()) {
            $stmt->close();
            $successMsg = $action === 'add' ? 'Menu berhasil ditambahkan' : 'Menu berhasil diperbarui';
            header('Location: menu?success=1');
            exit;
        }
        $error = 'Terjadi kesalahan saat menyimpan data.';
        $stmt->close();
    }
}

$editMenu = null;

if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM menus WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editMenu = $result->fetch_assoc();
    $stmt->close();
    if (!$editMenu) {
        $action = 'list';
    }
}

$menus = [];
$result = $conn->query("SELECT * FROM menus ORDER BY urutan ASC, nama ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $menus[] = $row;
    }
}

$menusByParent = [];
foreach ($menus as $menuItem) {
    $parentKey = (int)($menuItem['parent_id'] ?? 0);
    $menusByParent[$parentKey][] = $menuItem;
}

function menu_slug_path(array $menu): string
{
    $slug = trim((string)($menu['slug'] ?? ''));
    if ($slug === '') return '/';
    return '/' . ltrim($slug, '/');
}

function menu_type_label(string $type): string
{
    if ($type === 'dropdown') return 'Dropdown';
    if ($type === 'mega') return 'Mega';
    return 'Link';
}

function render_menu_builder_items(array $menusByParent, int $parentId = 0, int $level = 0): void
{
    foreach ($menusByParent[$parentId] ?? [] as $menu) {
        $menuId = (int)$menu['id'];
        $type = (string)($menu['menu_type'] ?? 'link');
        $typeLabel = menu_type_label($type);
        $isActive = (int)($menu['is_active'] ?? 1) === 1;
        ?>
        <div class="menu-builder-item" draggable="true" data-id="<?php echo $menuId; ?>">
            <div class="menu-builder-row">
                <button type="button" class="menu-drag-handle" aria-label="Geser menu" tabindex="-1"><?php echo ui_icon('grip', 'w-4 h-4'); ?></button>
                <div class="menu-builder-content">
                    <div class="menu-builder-title"><?php echo htmlspecialchars($menu['nama']); ?></div>
                    <div class="menu-builder-meta">
                        <span class="menu-slug"><?php echo htmlspecialchars(menu_slug_path($menu)); ?></span>
                        <span class="menu-badge"><?php echo (int)($menu['parent_id'] ?? 0) > 0 ? 'anak menu' : 'tab sama'; ?></span>
                    </div>
                </div>
                <div class="menu-builder-actions">
                    <button type="button" class="menu-nav" data-nav="up" title="Naik" aria-label="Naik"><?php echo ui_icon('chev-up', 'w-4 h-4'); ?></button>
                    <button type="button" class="menu-nav" data-nav="down" title="Turun" aria-label="Turun"><?php echo ui_icon('chev-down', 'w-4 h-4'); ?></button>
                    <a href="menu?action=edit&id=<?php echo $menuId; ?>" class="menu-act" title="Ubah"
                       data-modal-open="modalEditMenu"
                       data-id="<?php echo $menuId; ?>"
                       data-nama="<?php echo htmlspecialchars($menu['nama']); ?>"
                       data-slug="<?php echo htmlspecialchars($menu['slug']); ?>"
                       data-urutan="<?php echo (int)$menu['urutan']; ?>"
                       data-parent="<?php echo (int)($menu['parent_id'] ?? 0); ?>"
                       data-type="<?php echo htmlspecialchars($type); ?>"
                       data-active="<?php echo (int)$menu['is_active']; ?>"
                       data-kategori="<?php echo (int)($menu['kategori_id'] ?? 0); ?>">
                        <?php echo ui_icon('edit', 'w-4 h-4'); ?>
                    </a>
                    <button type="button" class="menu-act danger btn-delete-menu" data-id="<?php echo $menuId; ?>" data-nama="<?php echo htmlspecialchars($menu['nama']); ?>" title="Hapus">
                        <?php echo ui_icon('trash', 'w-4 h-4'); ?>
                    </button>
                </div>
            </div>
            <div class="menu-dropzone menu-child-dropzone" data-parent="<?php echo $menuId; ?>">
                <span class="menu-drop-hint">Seret ke sini untuk jadikan anak menu</span>
                <?php render_menu_builder_items($menusByParent, $menuId, $level + 1); ?>
            </div>
        </div>
        <?php
    }
}

// Check for success redirect
if (isset($_GET['success']) && $_GET['success'] === '1') {
    $success = 'Menu berhasil disimpan';
}

include __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Menu Navigasi</h1>
    <a href="menu?action=add" class="btn btn-primary btn-sm" data-modal-open="modalTambahMenu">
        <span style="display:inline-flex;vertical-align:-2px;margin-right:6px;"><?php echo ui_icon('plus', 'w-4 h-4'); ?></span>Tambah Menu
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

<!-- Modal Tambah Menu -->
<div class="admin-modal<?php echo $openAddModal ? ' is-open' : ''; ?>" id="modalTambahMenu" aria-hidden="true">
    <div class="admin-modal-panel" style="width: min(100%, 720px);">
        <div class="admin-modal-header">
            <div>
                <h2 class="h4 mb-1">Tambah Menu Baru</h2>
                <p class="text-muted small mb-0">Buat kategori atau struktur navigasi untuk portal.</p>
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
            <form method="post" action="menu?action=add">
                <div class="mb-3">
                    <label class="form-label">Nama Menu</label>
                    <input type="text" name="nama" class="form-control" required value="<?php echo htmlspecialchars($action === 'add' ? ($_POST['nama'] ?? '') : ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug (opsional)</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo htmlspecialchars($action === 'add' ? ($_POST['slug'] ?? '') : ''); ?>">
                    <div class="form-text">Jika dikosongkan akan dibuat otomatis dari nama.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Urutan</label>
                    <input type="number" name="urutan" class="form-control" value="<?php echo htmlspecialchars($action === 'add' ? ($_POST['urutan'] ?? '0') : '0'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipe Menu</label>
                    <?php $addType = $action === 'add' ? ($_POST['menu_type'] ?? 'link') : 'link'; ?>
                    <select name="menu_type" id="add_menu_type" class="form-select">
                        <option value="link" <?php echo $addType === 'link' ? 'selected' : ''; ?>>Link Biasa</option>
                        <option value="dropdown" <?php echo $addType === 'dropdown' ? 'selected' : ''; ?>>Dropdown</option>
                        <option value="mega" <?php echo $addType === 'mega' ? 'selected' : ''; ?>>Mega Menu</option>
                    </select>
                    <div class="form-text">Pilih tipe tampilan menu di navigasi publik.</div>
                </div>
                <div class="mb-3" id="add_parent_id_container" style="<?php echo $addType === 'dropdown' ? '' : 'display:none;'; ?>">
                    <label class="form-label">Menu Induk</label>
                    <?php $addParent = $action === 'add' ? (int)($_POST['parent_id'] ?? 0) : 0; ?>
                    <select name="parent_id" class="form-select">
                        <option value="0">-- Pilih Menu Induk --</option>
                        <?php foreach ($allMenusForParent as $parentOption): ?>
                            <option value="<?php echo (int)$parentOption['id']; ?>" <?php echo $addParent === (int)$parentOption['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parentOption['nama']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Pilih menu induk untuk membuat submenu (khusus tipe dropdown).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <?php $addKategori = $action === 'add' ? (int)($_POST['kategori_id'] ?? 0) : 0; ?>
                    <select name="kategori_id" class="form-select">
                        <option value="0">-- Pilih Kategori --</option>
                        <?php foreach ($allCategories as $catOption): ?>
                            <option value="<?php echo (int)$catOption['id']; ?>" <?php echo $addKategori === (int)$catOption['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($catOption['nama']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Pilih kategori yang akan ditampilkan saat menu ini diklik.</div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" <?php echo $action === 'add' ? (isset($_POST['is_active']) ? 'checked' : '') : 'checked'; ?>>
                    <label class="form-check-label" for="add_is_active">Aktifkan menu</label>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-modal-close>
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Simpan Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Menu -->
<div class="admin-modal<?php echo $openEditModal ? ' is-open' : ''; ?>" id="modalEditMenu" aria-hidden="true">
    <div class="admin-modal-panel" style="width: min(100%, 720px);">
        <div class="admin-modal-header">
            <div>
                <h2 class="h4 mb-1">Ubah Menu</h2>
                <p class="text-muted small mb-0">Edit nama atau pengaturan menu.</p>
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
            <form method="post" action="menu?action=edit&id=<?php echo (int)$id; ?>">
                <div class="mb-3">
                    <label class="form-label">Nama Menu</label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required value="<?php echo htmlspecialchars($editMenu['nama'] ?? ''); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Slug (opsional)</label>
                    <input type="text" name="slug" id="edit_slug" class="form-control" value="<?php echo htmlspecialchars($editMenu['slug'] ?? ''); ?>">
                    <div class="form-text">Jika dikosongkan akan dibuat otomatis dari nama.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Urutan</label>
                    <input type="number" name="urutan" id="edit_urutan" class="form-control" value="<?php echo htmlspecialchars($editMenu['urutan'] ?? '0'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipe Menu</label>
                    <?php $currentType = $editMenu['menu_type'] ?? 'link'; ?>
                    <select name="menu_type" id="edit_menu_type" class="form-select">
                        <option value="link" <?php echo $currentType === 'link' ? 'selected' : ''; ?>>Link Biasa</option>
                        <option value="dropdown" <?php echo $currentType === 'dropdown' ? 'selected' : ''; ?>>Dropdown</option>
                        <option value="mega" <?php echo $currentType === 'mega' ? 'selected' : ''; ?>>Mega Menu</option>
                    </select>
                    <div class="form-text">Pilih tipe tampilan menu di navigasi publik.</div>
                </div>
                <div class="mb-3" id="edit_parent_id_container" style="<?php echo $currentType === 'dropdown' ? '' : 'display:none;'; ?>">
                    <label class="form-label">Menu Induk</label>
                    <select name="parent_id" id="edit_parent_id" class="form-select">
                        <option value="0">-- Pilih Menu Induk --</option>
                        <?php
                        $currentParent = isset($editMenu['parent_id']) ? (int)$editMenu['parent_id'] : 0;
                        foreach ($allMenusForParent as $parentOption):
                            ?>
                            <option value="<?php echo (int)$parentOption['id']; ?>" <?php echo $currentParent === (int)$parentOption['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($parentOption['nama']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Pilih menu induk untuk membuat submenu (khusus tipe dropdown).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <?php $currentKategori = isset($editMenu['kategori_id']) ? (int)$editMenu['kategori_id'] : 0; ?>
                    <select name="kategori_id" id="edit_kategori_id" class="form-select">
                        <option value="0">-- Pilih Kategori --</option>
                        <?php foreach ($allCategories as $catOption): ?>
                            <option value="<?php echo (int)$catOption['id']; ?>" <?php echo $currentKategori === (int)$catOption['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($catOption['nama']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Pilih kategori yang akan ditampilkan saat menu ini diklik.</div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" <?php echo isset($editMenu['is_active']) ? ((int)$editMenu['is_active'] === 1 ? 'checked' : '') : 'checked'; ?>>
                    <label class="form-check-label" for="edit_is_active">Aktifkan menu</label>
                </div>
                <div class="d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" data-modal-close>
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Simpan Menu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .menu-builder-board {
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 18px 45px rgba(15, 23, 42, .06);
        padding: 18px;
    }
    .menu-dropzone { border-radius: 14px; }
    .menu-root-dropzone {
        display: grid;
        gap: 12px;
        min-height: 60px;
    }
    .menu-child-dropzone {
        margin: 8px 0 2px 28px;
        padding: 0 0 0 14px;
        border-left: 2px dashed #cbd5e1;
        display: grid;
        gap: 10px;
        min-height: 44px;
    }
    .menu-dropzone.is-over { background: #f1f5f9; }
    .menu-dropzone.is-over .menu-drop-hint { border-color: #0f9f94; color: #0b8077; background: #ecfdf5; }
    .menu-drop-hint {
        display: block;
        border: 1.5px dashed #cbd5e1;
        border-radius: 12px;
        color: #94a3b8;
        font-size: 12px;
        font-weight: 600;
        padding: 10px 12px;
        text-align: center;
        background: #fbfdff;
    }
    .menu-drop-placeholder {
        height: 52px;
        border: 2px dashed #0f9f94;
        border-radius: 14px;
        background: #ecfdf5;
    }
    .menu-builder-item { border-radius: 14px; }
    .menu-builder-item.is-dragging { opacity: .45; }
    .menu-builder-row {
        display: flex;
        align-items: center;
        gap: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #fff;
        padding: 12px 14px;
    }
    .menu-drag-handle {
        border: 0;
        background: transparent;
        color: #cbd5e1;
        font-size: 15px;
        letter-spacing: 1px;
        cursor: grab;
        padding: 4px;
    }
    .menu-builder-content { min-width: 0; flex: 1; }
    .menu-builder-title { font-weight: 800; color: #0f172a; font-size: 15px; }
    .menu-builder-meta { display: flex; align-items: center; gap: 8px; margin-top: 2px; }
    .menu-slug { color: #94a3b8; font-size: 12px; font-family: ui-monospace, monospace; }
    .menu-badge {
        border-radius: 6px;
        background: #f1f5f9;
        color: #64748b;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 8px;
    }
    .menu-builder-actions { display: flex; align-items: center; gap: 6px; }
    .menu-nav, .menu-act {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
        color: #475569;
        font-size: 14px;
        cursor: pointer;
        transition: .15s;
    }
    .menu-nav:hover, .menu-act:hover { border-color: #0f9f94; color: #0b8077; }
    .menu-act.danger { color: #dc2626; }
    .menu-act.danger:hover { border-color: #fca5a5; background: #fef2f2; }
    @media (max-width: 640px) {
        .menu-builder-row { align-items: flex-start; flex-wrap: wrap; }
        .menu-child-dropzone { margin-left: 14px; }
    }
</style>

<div class="menu-builder-board mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h2 class="h6 mb-1">Struktur Menu</h2>
            <p class="text-muted small mb-0">Geser ke area Menu Utama untuk menjadi induk. Geser ke kotak submenu di bawah item untuk menjadi anak menu.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="btnSaveMenuOrder">
            <span style="display:inline-flex;vertical-align:-2px;margin-right:6px;"><?php echo ui_icon('save', 'w-4 h-4'); ?></span>Simpan Urutan
        </button>
    </div>
    <?php if (empty($menus)): ?>
        <div class="text-center text-muted py-4">Belum ada menu.</div>
    <?php else: ?>
        <div class="mb-2 small fw-bold text-uppercase text-muted">Menu Utama</div>
        <div class="menu-dropzone menu-root-dropzone" id="menuRootDropzone" data-parent="0">
            <?php render_menu_builder_items($menusByParent, 0); ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Delete menu with SweetAlert
    document.querySelectorAll('.btn-delete-menu').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var menuId = this.getAttribute('data-id');
            var menuNama = this.getAttribute('data-nama');
            
            Swal.fire({
                title: 'Hapus Menu?',
                text: 'Apakah Anda yakin ingin menghapus "' + menuNama + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (result.isConfirmed) {
                    fetch('menu?action=delete&id=' + menuId, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: 'Menu berhasil dihapus',
                                timer: 2000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: data.error || 'Gagal menghapus menu',
                                timer: 3000,
                                timerProgressBar: true,
                                showConfirmButton: false
                            });
                        }
                    })
                    .catch(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menghapus menu',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                    });
                }
            });
        });
    });
    
    // Edit modal data population
    document.querySelectorAll('[data-modal-open="modalEditMenu"]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var nama = this.getAttribute('data-nama');
            var slug = this.getAttribute('data-slug');
            var urutan = this.getAttribute('data-urutan');
            var parent = this.getAttribute('data-parent');
            var type = this.getAttribute('data-type');
            var active = this.getAttribute('data-active');
            var kategori = this.getAttribute('data-kategori');
            
            var form = document.querySelector('#modalEditMenu form');
            if (form) {
                form.action = 'menu?action=edit&id=' + id;
                var namaInput = document.getElementById('edit_nama');
                var slugInput = document.getElementById('edit_slug');
                var urutanInput = document.getElementById('edit_urutan');
                var parentSelect = document.getElementById('edit_parent_id');
                var typeSelect = document.getElementById('edit_menu_type');
                var activeCheckbox = document.getElementById('edit_is_active');
                var kategoriSelect = document.getElementById('edit_kategori_id');
                var parentContainer = document.getElementById('edit_parent_id_container');
                
                if (namaInput) namaInput.value = nama;
                if (slugInput) slugInput.value = slug;
                if (urutanInput) urutanInput.value = urutan;
                if (parentSelect) parentSelect.value = parent;
                if (typeSelect) typeSelect.value = type;
                if (activeCheckbox) activeCheckbox.checked = active === '1';
                if (kategoriSelect) kategoriSelect.value = kategori;
                
                // Show/hide parent_id field based on menu_type
                if (parentContainer) {
                    if (type === 'dropdown') {
                        parentContainer.style.display = 'block';
                    } else {
                        parentContainer.style.display = 'none';
                    }
                }
            }
        });
    });
    
    // Toggle parent_id field visibility based on menu_type (Add form)
    var addTypeSelect = document.getElementById('add_menu_type');
    var addParentContainer = document.getElementById('add_parent_id_container');
    if (addTypeSelect && addParentContainer) {
        addTypeSelect.addEventListener('change', function() {
            if (this.value === 'dropdown') {
                addParentContainer.style.display = 'block';
            } else {
                addParentContainer.style.display = 'none';
            }
        });
    }
    
    // Toggle parent_id field visibility based on menu_type (Edit form)
    var editTypeSelect = document.getElementById('edit_menu_type');
    var editParentContainer = document.getElementById('edit_parent_id_container');
    if (editTypeSelect && editParentContainer) {
        editTypeSelect.addEventListener('change', function() {
            if (this.value === 'dropdown') {
                editParentContainer.style.display = 'block';
            } else {
                editParentContainer.style.display = 'none';
            }
        });
    }

    var draggedItem = null;
    var draggedId = 0;
    var placeholder = document.createElement('div');
    placeholder.className = 'menu-drop-placeholder';

    function getDragAfterElement(container, y) {
        var draggableElements = Array.prototype.slice.call(container.querySelectorAll(':scope > .menu-builder-item:not(.is-dragging)'));
        return draggableElements.reduce(function(closest, child) {
            var box = child.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            }
            return closest;
        }, { offset: Number.NEGATIVE_INFINITY, element: null }).element;
    }

    function clearDropState() {
        document.querySelectorAll('.menu-dropzone.is-over').forEach(function(zone) {
            zone.classList.remove('is-over');
        });
        if (placeholder.parentNode) {
            placeholder.parentNode.removeChild(placeholder);
        }
    }

    function bindDrag(item) {
        if (item._dragBound) return;
        item._dragBound = true;
        item.addEventListener('dragstart', function(event) {
            if (event.target.closest && event.target.closest('.menu-nav, .menu-act')) { event.preventDefault(); return; }
            draggedItem = item;
            draggedId = parseInt(item.getAttribute('data-id') || '0', 10) || 0;
            item.classList.add('is-dragging');
            if (event.dataTransfer) {
                try { event.dataTransfer.effectAllowed = 'copyMove'; } catch (x) {}
                try { event.dataTransfer.setData('text/menu-id', String(draggedId)); } catch (x) {}
                try { event.dataTransfer.setData('text/plain', 'menu:' + draggedId); } catch (x) {}
            }
        });
        item.addEventListener('dragend', function() {
            item.classList.remove('is-dragging');
            clearDropState();
            draggedItem = null;
            draggedId = 0;
        });
    }

    function bindZone(zone) {
        if (zone._dropBound) return;
        zone._dropBound = true;
        zone.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            try { e.dataTransfer.dropEffect = 'move'; } catch (x) {}
            zone.classList.add('is-over');
            var afterElement = getDragAfterElement(zone, e.clientY);
            if (afterElement == null) {
                zone.appendChild(placeholder);
            } else {
                zone.insertBefore(placeholder, afterElement);
            }
        });
        zone.addEventListener('dragleave', function(e) {
            if (!zone.contains(e.relatedTarget)) zone.classList.remove('is-over');
        });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var id = draggedId;
            if (!id) {
                try {
                    var p = e.dataTransfer.getData('text/plain') || '';
                    if (p.indexOf('menu:') === 0) id = parseInt(p.slice(5), 10) || 0;
                } catch (x) {}
            }
            var node = draggedItem || (id ? document.querySelector('.menu-builder-item[data-id="' + id + '"]') : null);
            if (node) {
                // Cegah induk masuk ke anaknya sendiri.
                if (node.contains(zone)) { clearDropState(); return; }
                if (placeholder.parentNode === zone) zone.insertBefore(node, placeholder);
                else zone.appendChild(node);
            }
            clearDropState();
            draggedItem = null;
            draggedId = 0;
        });
    }

    document.querySelectorAll('.menu-builder-item').forEach(bindDrag);
    document.querySelectorAll('.menu-dropzone').forEach(bindZone);

    // Panah naik/turun sebaris: geser dalam zona yang sama.
    document.querySelectorAll('.menu-builder-item').forEach(function(item) {
        item.querySelectorAll('.menu-nav').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var zone = item.parentNode;
                var sibs = Array.prototype.slice.call(zone.querySelectorAll(':scope > .menu-builder-item'));
                var i = sibs.indexOf(item);
                var j = btn.getAttribute('data-nav') === 'up' ? i - 1 : i + 1;
                if (i === -1 || j < 0 || j >= sibs.length) return;
                if (btn.getAttribute('data-nav') === 'up') zone.insertBefore(item, sibs[j]);
                else zone.insertBefore(sibs[j], item);
            });
        });
    });

    function collectMenuOrder(zone, parentId, rows) {
        Array.prototype.slice.call(zone.children).forEach(function(item, index) {
            if (!item.classList || !item.classList.contains('menu-builder-item')) return;
            var id = parseInt(item.getAttribute('data-id'), 10);
            if (!id) return;
            rows.push({ id: id, parent_id: parentId, urutan: index + 1 });
            var childZone = item.querySelector(':scope > .menu-child-dropzone');
            if (childZone) {
                collectMenuOrder(childZone, id, rows);
            }
        });
    }

    var saveOrderButton = document.getElementById('btnSaveMenuOrder');
    var rootZone = document.getElementById('menuRootDropzone');

    if (saveOrderButton && rootZone) {
        saveOrderButton.addEventListener('click', function() {
            var rows = [];
            collectMenuOrder(rootZone, 0, rows);
            saveOrderButton.disabled = true;
            saveOrderButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Menyimpan';

            fetch('menu?action=save_order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ items: rows })
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Struktur menu berhasil disimpan',
                        timer: 1800,
                        timerProgressBar: true,
                        showConfirmButton: false
                    }).then(function() { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: data.error || 'Gagal menyimpan struktur menu' });
                }
            })
            .catch(function() {
                Swal.fire({ icon: 'error', title: 'Error!', text: 'Terjadi kesalahan saat menyimpan struktur menu' });
            })
            .finally(function() {
                saveOrderButton.disabled = false;
                saveOrderButton.innerHTML = <?php echo json_encode(ui_icon('save', 'w-4 h-4') . ' Simpan Urutan'); ?>;
            });
        });
    }
});
</script>

<?php
include __DIR__ . '/footer.php';
