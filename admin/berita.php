<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

function berita_slug(string $text): string
{
    $text = strtolower($text);
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = preg_replace('~[^-\w]+~', '', $text);
    return $text === '' ? uniqid('berita-') : $text;
}

function berita_field(array $data, string $key, $default = ''): string
{
    if (array_key_exists($key, $_POST)) {
        return (string)$_POST[$key];
    }
    return (string)($data[$key] ?? $default);
}

function berita_radio_checked(array $data, string $key, string $value, string $default = '1'): string
{
    $current = array_key_exists($key, $_POST) ? (string)$_POST[$key] : (string)($data[$key] ?? $default);
    return $current === $value ? 'checked' : '';
}

function berita_unique_slug(mysqli $conn, string $slug, int $ignoreId = 0): string
{
    $base = $slug === '' ? uniqid('berita-') : $slug;
    $candidate = $base;
    $counter = 2;

    while (true) {
        $stmt = $conn->prepare('SELECT id FROM berita WHERE slug = ? AND id <> ? LIMIT 1');
        $stmt->bind_param('si', $candidate, $ignoreId);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$exists) {
            return $candidate;
        }

        $candidate = $base . '-' . $counter;
        $counter++;
    }
}

function render_berita_form(array $kategoris, array $data, string $action, int $id, string $error): void
{
    $isEdit = $action === 'edit';
    $formAction = $isEdit ? 'berita?action=edit&id=' . $id : 'berita?action=add';
    $title = $isEdit ? 'Ubah Berita' : 'Tambah Berita Baru';
    $subtitle = $isEdit ? 'Edit konten, gambar, dan pengaturan tampilan berita.' : 'Tulis artikel baru dengan ruang editor yang lebih leluasa.';
    $status = berita_field($data, 'status', 'draft');
    $layout = berita_field($data, 'layout_style', 'default');
    if (!in_array($layout, ['default', 'wide', 'boxed'], true)) {
        $layout = 'default';
    }
    ?>

    <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="m-0 text-2xl font-extrabold text-slate-950"><?php echo htmlspecialchars($title); ?></h1>
            <p class="mt-1 text-sm font-medium text-slate-500"><?php echo htmlspecialchars($subtitle); ?></p>
        </div>
        <a href="berita" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
            Kembali ke Daftar
        </a>
    </div>

    <?php if ($error): ?>
        <div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($formAction); ?>" enctype="multipart/form-data" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <section class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <input type="text" name="judul" id="post_title_input" class="w-full border-0 px-0 py-1 text-3xl font-extrabold leading-tight text-slate-950 outline-none placeholder:text-slate-300 focus:ring-0" required placeholder="Tambahkan judul" value="<?php echo htmlspecialchars(berita_field($data, 'judul')); ?>">
                <div class="mt-3 flex flex-col gap-2 border-t border-slate-100 pt-3 text-xs font-semibold text-slate-500 sm:flex-row sm:items-center">
                    <span>Permalink</span>
                    <div class="flex min-w-0 flex-1 items-center gap-1 rounded-lg bg-slate-50 px-3 py-2">
                        <span class="shrink-0 text-slate-400"><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'domain'); ?>/</span>
                        <input type="text" name="slug" id="post_slug_input" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-xs font-bold text-slate-700 outline-none focus:ring-0" value="<?php echo htmlspecialchars(berita_field($data, 'slug')); ?>" placeholder="otomatis-dari-judul">
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <h2 class="m-0 text-sm font-extrabold text-slate-800">Editor Berita</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Visual Editor</span>
                </div>
                <div class="p-5">
                    <textarea name="isi" class="js-ckeditor w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700" rows="36" data-editor-height="1080"><?php echo htmlspecialchars(berita_field($data, 'isi')); ?></textarea>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <label class="mb-2 block text-sm font-extrabold text-slate-700">Kutipan / Ringkasan</label>
                <textarea name="ringkasan" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm text-slate-700 outline-none transition focus:border-teal-400 focus:ring-4 focus:ring-teal-100" rows="4" placeholder="Ringkasan singkat yang muncul di halaman detail atau daftar berita."><?php echo htmlspecialchars(berita_field($data, 'ringkasan')); ?></textarea>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="m-0 text-base font-extrabold text-slate-950">Terbitkan</h2>
                    <span class="rounded-full <?php echo $status === 'publish' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'; ?> px-3 py-1 text-xs font-extrabold"><?php echo $status === 'publish' ? 'Publish' : 'Draft'; ?></span>
                </div>
                <div class="mb-4">
                    <label class="mb-2 block text-sm font-bold text-slate-700">Penulis</label>
                    <input type="text" name="penulis" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-teal-400 focus:ring-4 focus:ring-teal-100" value="<?php echo htmlspecialchars(berita_field($data, 'penulis', $_SESSION['admin_nama'] ?? '')); ?>">
                </div>
                <div class="mb-4">
                    <label class="mb-2 block text-sm font-bold text-slate-700">Status</label>
                    <select name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none focus:border-teal-400 focus:ring-4 focus:ring-teal-100">
                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="publish" <?php echo $status === 'publish' ? 'selected' : ''; ?>>Publish</option>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">Tanggal Publikasi</label>
                    <input type="text" name="tanggal_publikasi" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-teal-400 focus:ring-4 focus:ring-teal-100" value="<?php echo htmlspecialchars(berita_field($data, 'tanggal_publikasi')); ?>" placeholder="YYYY-mm-dd HH:ii:ss">
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-extrabold text-slate-950">Kategori</h2>
                <select name="kategori_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none focus:border-teal-400 focus:ring-4 focus:ring-teal-100" required>
                    <option value="">Pilih kategori</option>
                    <?php foreach ($kategoris as $kat): ?>
                        <?php $selectedKategori = (int)berita_field($data, 'kategori_id', 0) === (int)$kat['id']; ?>
                        <option value="<?php echo (int)$kat['id']; ?>" <?php echo $selectedKategori ? 'selected' : ''; ?>><?php echo htmlspecialchars($kat['nama']); ?></option>
                    <?php endforeach; ?>
                </select>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-extrabold text-slate-950">Gambar Unggulan</h2>
                <input type="file" name="gambar" id="gambar_input" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700" accept="image/*">
                <div id="image_preview_container" class="mt-4">
                    <?php if ($isEdit && !empty($data['gambar']) && $data['gambar'] !== '0'): ?>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                            <div class="mb-2 text-xs font-bold uppercase tracking-wide text-emerald-600">Gambar Saat Ini</div>
                            <img src="<?php echo htmlspecialchars(berita_image_url($data['gambar'], '../')); ?>" alt="Preview" id="current_image" class="max-h-64 w-full rounded-lg object-contain">
                            <p class="mt-2 text-xs font-medium text-slate-500">Upload gambar baru untuk mengganti.</p>
                        </div>
                    <?php else: ?>
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-500">Belum ada gambar.</div>
                    <?php endif; ?>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-extrabold text-slate-950">Pengaturan Tampilan</h2>
                <?php
                $toggles = [
                    'show_meta' => ['Tampilkan Meta Data', 'Tanggal, penulis, dan kategori di atas judul'],
                    'show_ringkasan' => ['Tampilkan Ringkasan', 'Sekilas berita di halaman detail'],
                    'show_penulis' => ['Tampilkan Penulis', 'Nama penulis pada halaman detail'],
                    'show_tanggal' => ['Tampilkan Tanggal', 'Tanggal publikasi pada halaman detail'],
                    'show_kategori' => ['Tampilkan Kategori', 'Label kategori pada halaman detail'],
                    'show_gambar_detail' => ['Tampilkan Gambar di Detail', 'Gambar utama pada halaman detail'],
                ];
                foreach ($toggles as $name => $copy):
                ?>
                    <div class="mb-4 rounded-xl bg-slate-50 p-3">
                        <div class="mb-2 text-sm font-bold text-slate-800"><?php echo htmlspecialchars($copy[0]); ?></div>
                        <div class="flex gap-2">
                            <label class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 has-[:checked]:border-teal-400 has-[:checked]:bg-teal-50 has-[:checked]:text-teal-700">
                                <input class="sr-only" type="radio" name="<?php echo htmlspecialchars($name); ?>" value="1" <?php echo berita_radio_checked($data, $name, '1'); ?>>Ya
                            </label>
                            <label class="inline-flex flex-1 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 has-[:checked]:border-red-300 has-[:checked]:bg-red-50 has-[:checked]:text-red-700">
                                <input class="sr-only" type="radio" name="<?php echo htmlspecialchars($name); ?>" value="0" <?php echo berita_radio_checked($data, $name, '0'); ?>>Tidak
                            </label>
                        </div>
                        <p class="mt-2 text-xs font-medium text-slate-400"><?php echo htmlspecialchars($copy[1]); ?></p>
                    </div>
                <?php endforeach; ?>

                <div>
                    <div class="mb-2 text-sm font-bold text-slate-800">Layout Tampilan</div>
                    <div class="grid gap-2">
                        <?php foreach (['default' => 'Default', 'wide' => 'Wide', 'boxed' => 'Boxed'] as $value => $label): ?>
                            <label class="inline-flex items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 has-[:checked]:border-purple-400 has-[:checked]:bg-purple-50 has-[:checked]:text-purple-700">
                                <span><?php echo htmlspecialchars($label); ?></span>
                                <input type="radio" name="layout_style" value="<?php echo htmlspecialchars($value); ?>" <?php echo $layout === $value ? 'checked' : ''; ?>>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <div class="sticky bottom-4 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-xl backdrop-blur">
                <div class="flex gap-3">
                    <a href="berita" class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50">Batal</a>
                    <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-xl bg-teal-600 px-4 py-3 text-sm font-extrabold text-white shadow-lg shadow-teal-200 transition hover:bg-teal-700">Simpan</button>
                </div>
            </div>
        </aside>
    </form>
    <?php
}

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

$kategoris = [];
$result = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $kategoris[] = $row;
    }
}

if ($action === 'delete' && $id > 0) {
    $stmt = $conn->prepare("SELECT gambar FROM berita WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && !empty($row['gambar'])) {
        delete_uploaded_file($row['gambar']);
    }

    $stmt = $conn->prepare("DELETE FROM berita WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();

    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode($ok ? ['success' => true] : ['success' => false, 'error' => 'Gagal menghapus berita']);
        exit;
    }

    header('Location: berita' . ($ok ? '?success=delete' : ''));
    exit;
}

$editBerita = [];
if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM berita WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $editBerita = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    if (!$editBerita) {
        header('Location: berita');
        exit;
    }
}

if (in_array($action, ['add', 'edit'], true) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $kategori_id = (int)($_POST['kategori_id'] ?? 0);
    $ringkasan = trim($_POST['ringkasan'] ?? '');
    $isi = trim($_POST['isi'] ?? '');
    $penulis = trim($_POST['penulis'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $show_meta = isset($_POST['show_meta']) && $_POST['show_meta'] === '1' ? 1 : 0;
    $show_ringkasan = isset($_POST['show_ringkasan']) && $_POST['show_ringkasan'] === '1' ? 1 : 0;
    $show_penulis = isset($_POST['show_penulis']) && $_POST['show_penulis'] === '1' ? 1 : 0;
    $show_tanggal = isset($_POST['show_tanggal']) && $_POST['show_tanggal'] === '1' ? 1 : 0;
    $show_kategori = isset($_POST['show_kategori']) && $_POST['show_kategori'] === '1' ? 1 : 0;
    $show_gambar_detail = isset($_POST['show_gambar_detail']) && $_POST['show_gambar_detail'] === '1' ? 1 : 0;
    $layout_style = trim($_POST['layout_style'] ?? 'default');
    if (!in_array($layout_style, ['default', 'wide', 'boxed'], true)) {
        $layout_style = 'default';
    }

    if ($judul === '' || $kategori_id === 0) {
        $error = 'Judul dan kategori berita wajib diisi.';
    }

    if ($error === '') {
        $slug = $slug === '' ? berita_slug($judul) : berita_slug($slug);
        $slug = berita_unique_slug($conn, $slug, $action === 'edit' ? $id : 0);
        $tanggal_publikasi = null;
        if (!empty($_POST['tanggal_publikasi'])) {
            $tanggal_publikasi = $_POST['tanggal_publikasi'];
        } elseif ($status === 'publish') {
            $tanggal_publikasi = date('Y-m-d H:i:s');
        }

        $uploadFileName = $editBerita['gambar'] ?? null;
        if ($uploadFileName === '0' || $uploadFileName === 0 || $uploadFileName === '') {
            $uploadFileName = null;
        } elseif (is_string($uploadFileName)) {
            $uploadFileName = basename(str_replace('\\', '/', $uploadFileName));
        }

        if (!empty($_FILES['gambar']['name']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $fileName = $_FILES['gambar']['name'];
            $tmpName = $_FILES['gambar']['tmp_name'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed, true) && @getimagesize($tmpName) !== false) {
                $uploadDir = __DIR__ . '/../uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $safeName = uniqid('img_') . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . '/' . $safeName)) {
                    if ($uploadFileName) {
                        delete_uploaded_file($uploadFileName);
                    }
                    $uploadFileName = $safeName;
                } else {
                    $error = 'Gagal mengunggah gambar.';
                }
            } else {
                $error = 'Format gambar tidak didukung.';
            }
        } elseif (!empty($_FILES['gambar']['name']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = 'Gagal mengunggah gambar. Periksa ukuran file dan coba lagi.';
        }
    }

    if ($error === '') {
        $layout_style_sql = "'" . $conn->real_escape_string($layout_style) . "'";
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO berita (kategori_id, judul, slug, ringkasan, isi, penulis, status, tanggal_publikasi, gambar, show_meta, show_ringkasan, show_penulis, show_tanggal, show_kategori, show_gambar_detail, layout_style) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, $layout_style_sql)");
            $stmt->bind_param('issssssssiiiiii', $kategori_id, $judul, $slug, $ringkasan, $isi, $penulis, $status, $tanggal_publikasi, $uploadFileName, $show_meta, $show_ringkasan, $show_penulis, $show_tanggal, $show_kategori, $show_gambar_detail);
        } else {
            $stmt = $conn->prepare("UPDATE berita SET kategori_id = ?, judul = ?, slug = ?, ringkasan = ?, isi = ?, penulis = ?, status = ?, tanggal_publikasi = ?, gambar = ?, show_meta = ?, show_ringkasan = ?, show_penulis = ?, show_tanggal = ?, show_kategori = ?, show_gambar_detail = ?, layout_style = $layout_style_sql WHERE id = ?");
            $stmt->bind_param('issssssssiiiiiii', $kategori_id, $judul, $slug, $ringkasan, $isi, $penulis, $status, $tanggal_publikasi, $uploadFileName, $show_meta, $show_ringkasan, $show_penulis, $show_tanggal, $show_kategori, $show_gambar_detail, $id);
        }

        if ($stmt->execute()) {
            $stmt->close();
            header('Location: berita?success=save');
            exit;
        }
        $error = 'Terjadi kesalahan saat menyimpan berita.';
        $stmt->close();
    }
}

$beritaList = [];
if ($action === 'list') {
    $sql = "SELECT b.*, k.nama AS kategori_nama
            FROM berita b
            LEFT JOIN kategori k ON k.id = b.kategori_id
            ORDER BY b.tanggal_publikasi DESC, b.id DESC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $beritaList[] = $row;
        }
    }
}

if (isset($_GET['success'])) {
    $success = $_GET['success'] === 'delete' ? 'Berita berhasil dihapus' : 'Berita berhasil disimpan';
}

include __DIR__ . '/header.php';

if (in_array($action, ['add', 'edit'], true)) {
    render_berita_form($kategoris, $action === 'edit' ? $editBerita : [], $action, $id, $error);
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var titleInput = document.getElementById('post_title_input');
        var slugInput = document.getElementById('post_slug_input');
        var slugTouched = slugInput && slugInput.value.trim() !== '';

        function makeSlug(value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .replace(/-{2,}/g, '-');
        }

        if (slugInput) {
            slugInput.addEventListener('input', function() { slugTouched = true; });
        }

        if (titleInput && slugInput) {
            titleInput.addEventListener('input', function() {
                if (!slugTouched) {
                    slugInput.value = makeSlug(titleInput.value);
                }
            });
        }

        var input = document.getElementById('gambar_input');
        var container = document.getElementById('image_preview_container');
        if (!input || !container) return;
        input.addEventListener('change', function(event) {
            var file = event.target.files && event.target.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                container.innerHTML = '<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3">' +
                    '<div class="mb-2 text-xs font-bold uppercase tracking-wide text-emerald-700">Preview Gambar Baru</div>' +
                    '<img src="' + e.target.result + '" alt="Preview" class="max-h-64 w-full rounded-lg object-contain">' +
                    '<p class="mt-2 text-xs font-medium text-emerald-700">Gambar akan disimpan saat tombol Simpan ditekan.</p>' +
                    '</div>';
            };
            reader.readAsDataURL(file);
        });
    });
    </script>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}
?>

<div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="m-0 text-2xl font-extrabold text-slate-950">Berita</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Kelola artikel yang tampil di portal public.</p>
    </div>
    <a href="berita?action=add" class="inline-flex items-center justify-center rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-extrabold text-white shadow-lg shadow-teal-200 transition hover:bg-teal-700">
        Tambah Berita
    </a>
</div>

<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: '<?php echo addslashes($success); ?>', timer: 2200, timerProgressBar: true, showConfirmButton: false });
        });
    </script>
<?php endif; ?>

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="mb-4 text-base font-extrabold text-slate-950">Daftar Berita</h2>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead>
            <tr class="border-b border-slate-200 text-xs font-black uppercase tracking-wide text-slate-500">
                <th class="px-3 py-3">#</th>
                <th class="px-3 py-3">Judul</th>
                <th class="px-3 py-3">Kategori</th>
                <th class="px-3 py-3">Status</th>
                <th class="px-3 py-3">Publikasi</th>
                <th class="px-3 py-3 text-right">Aksi</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php if (empty($beritaList)): ?>
                <tr><td colspan="6" class="px-3 py-8 text-center font-semibold text-slate-400">Belum ada berita.</td></tr>
            <?php else: ?>
                <?php foreach ($beritaList as $index => $item): ?>
                    <tr class="text-slate-700">
                        <td class="px-3 py-3 font-bold"><?php echo $index + 1; ?></td>
                        <td class="px-3 py-3 font-bold text-slate-950"><?php echo htmlspecialchars($item['judul']); ?></td>
                        <td class="px-3 py-3"><?php echo htmlspecialchars($item['kategori_nama'] ?? '-'); ?></td>
                        <td class="px-3 py-3">
                            <?php if ($item['status'] === 'publish'): ?>
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-extrabold text-emerald-700">Publish</span>
                            <?php else: ?>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-extrabold text-slate-500">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-3"><?php echo $item['tanggal_publikasi'] ? date('d M Y H:i', strtotime($item['tanggal_publikasi'])) : '-'; ?></td>
                        <td class="px-3 py-3">
                            <div class="flex justify-end gap-2">
                                <a href="../<?php echo htmlspecialchars(berita_url($item)); ?>" target="_blank" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">Lihat</a>
                                <a href="berita?action=edit&id=<?php echo (int)$item['id']; ?>" class="rounded-lg border border-blue-200 px-3 py-1.5 text-xs font-bold text-blue-600 transition hover:bg-blue-50">Edit</a>
                                <button type="button" class="btn-delete-berita rounded-lg border border-red-200 px-3 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-50" data-id="<?php echo (int)$item['id']; ?>" data-judul="<?php echo htmlspecialchars($item['judul']); ?>">Hapus</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-delete-berita').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var beritaId = this.getAttribute('data-id');
            var beritaJudul = this.getAttribute('data-judul');
            Swal.fire({
                title: 'Hapus Berita?',
                text: 'Apakah Anda yakin ingin menghapus "' + beritaJudul + '"?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                fetch('berita?action=delete&id=' + beritaId, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        if (data.success) {
                            Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Berita berhasil dihapus', timer: 1600, showConfirmButton: false }).then(function() { location.reload(); });
                        } else {
                            Swal.fire({ icon: 'error', title: 'Gagal!', text: data.error || 'Gagal menghapus berita' });
                        }
                    })
                    .catch(function() { Swal.fire({ icon: 'error', title: 'Error!', text: 'Terjadi kesalahan saat menghapus berita' }); });
            });
        });
    });
});
</script>

<?php include __DIR__ . '/footer.php'; ?>
