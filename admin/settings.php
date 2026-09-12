<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$error = '';
$success = '';

$result = $conn->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1");
$current = $result ? $result->fetch_assoc() : null;

if (!$current) {
    $stmt = $conn->prepare("INSERT INTO settings (site_name, site_tagline, logo_path, favicon_path, latest_news_count) VALUES (?, ?, ?, ?, ?)");
    $name = 'Portal Berita';
    $tagline = 'Portal berita modern dan informatif';
    $logo = null;
    $favicon = null;
    $latestNewsCount = 5;
    $stmt->bind_param('ssssi', $name, $tagline, $logo, $favicon, $latestNewsCount);
    $stmt->execute();
    $stmt->close();
    $result = $conn->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1");
    $current = $result ? $result->fetch_assoc() : null;
}

$checkLatestNewsCount = $conn->query("SHOW COLUMNS FROM settings LIKE 'latest_news_count'");
if ($checkLatestNewsCount && $checkLatestNewsCount->num_rows === 0) {
    $conn->query("ALTER TABLE settings ADD COLUMN latest_news_count INT NOT NULL DEFAULT 5 AFTER favicon_path");
    $result = $conn->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1");
    $current = $result ? $result->fetch_assoc() : null;
}

$extraSettingsCols = [
    'footer_email VARCHAR(255) NULL',
    'footer_address TEXT NULL',
    'footer_phone VARCHAR(50) NULL',
    'footer_social_facebook VARCHAR(255) NULL',
    'footer_social_twitter VARCHAR(255) NULL',
    'footer_social_instagram VARCHAR(255) NULL',
    'komentar_aktif TINYINT(1) NOT NULL DEFAULT 1',
    'komentar_moderasi TINYINT(1) NOT NULL DEFAULT 1',
    'komentar_captcha TINYINT(1) NOT NULL DEFAULT 1',
    'komentar_max_links TINYINT NOT NULL DEFAULT 2',
    'komentar_interval_detik INT NOT NULL DEFAULT 30',
    'komentar_kata_kasar TEXT NULL',
];
$needRefresh = false;
foreach ($extraSettingsCols as $col) {
    $colName = explode(' ', $col)[0];
    $check = $conn->query("SHOW COLUMNS FROM settings LIKE '$colName'");
    if ($check && $check->num_rows === 0) {
        if ($conn->query("ALTER TABLE settings ADD COLUMN $col")) $needRefresh = true;
    }
}
if ($needRefresh) {
    $result = $conn->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1");
    $current = $result ? $result->fetch_assoc() : $current;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name'] ?? '');
    $site_tagline = trim($_POST['site_tagline'] ?? '');
    $latest_news_count = max(1, min(20, (int)($_POST['latest_news_count'] ?? 5)));
    $footer_email = trim($_POST['footer_email'] ?? '');
    $footer_address = trim($_POST['footer_address'] ?? '');
    $footer_phone = trim($_POST['footer_phone'] ?? '');
    $footer_social_facebook = trim($_POST['footer_social_facebook'] ?? '');
    $footer_social_twitter = trim($_POST['footer_social_twitter'] ?? '');
    $footer_social_instagram = trim($_POST['footer_social_instagram'] ?? '');
    $komentar_aktif = isset($_POST['komentar_aktif']) ? 1 : 0;
    $komentar_moderasi = isset($_POST['komentar_moderasi']) ? 1 : 0;
    $komentar_captcha = isset($_POST['komentar_captcha']) ? 1 : 0;
    $komentar_max_links = max(0, min(10, (int)($_POST['komentar_max_links'] ?? 2)));
    $komentar_interval_detik = max(5, min(600, (int)($_POST['komentar_interval_detik'] ?? 30)));
    $komentar_kata_kasar = trim((string)($_POST['komentar_kata_kasar'] ?? ''));
    $logo_path = $current['logo_path'] ?? null;
    $favicon_path = $current['favicon_path'] ?? null;

    if ($site_name === '') {
        $error = 'Nama portal wajib diisi.';
    } else {
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
            $fileName = $_FILES['logo']['name'];
            $tmpName = $_FILES['logo']['tmp_name'];
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed, true)) {
                $uploadDir = __DIR__ . '/../uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $safeName = uniqid('logo_') . '.' . $ext;
                $destination = $uploadDir . '/' . $safeName;

                if (move_uploaded_file($tmpName, $destination)) {
                    if ($logo_path) {
                        delete_uploaded_file($logo_path);
                    }
                    $logo_path = 'uploads/' . $safeName;
                } else {
                    $error = 'Gagal mengunggah logo.';
                }
            } else {
                $error = 'Format logo tidak didukung.';
            }
        }

        if ($error === '' && !empty($_FILES['favicon']['name']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $allowedFav = ['ico', 'png', 'jpg', 'jpeg', 'svg'];
            $favFileName = $_FILES['favicon']['name'];
            $favTmpName = $_FILES['favicon']['tmp_name'];
            $favExt = strtolower(pathinfo($favFileName, PATHINFO_EXTENSION));

            if (in_array($favExt, $allowedFav, true)) {
                $uploadDir = __DIR__ . '/../uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $safeName = uniqid('favicon_') . '.' . $favExt;
                $destination = $uploadDir . '/' . $safeName;

                if (move_uploaded_file($favTmpName, $destination)) {
                    if ($favicon_path) {
                        delete_uploaded_file($favicon_path);
                    }
                    $favicon_path = 'uploads/' . $safeName;
                } else {
                    $error = 'Gagal mengunggah favicon.';
                }
            } else {
                $error = 'Format favicon tidak didukung.';
            }
        }

        if ($error === '') {
            $stmt = $conn->prepare("UPDATE settings SET site_name = ?, site_tagline = ?, logo_path = ?, favicon_path = ?, latest_news_count = ?, footer_email = ?, footer_address = ?, footer_phone = ?, footer_social_facebook = ?, footer_social_twitter = ?, footer_social_instagram = ?, komentar_aktif = ?, komentar_moderasi = ?, komentar_captcha = ?, komentar_max_links = ?, komentar_interval_detik = ?, komentar_kata_kasar = ? WHERE id = ?");
            $id = (int)$current['id'];
            $stmt->bind_param('ssssissssssiiiiisi', $site_name, $site_tagline, $logo_path, $favicon_path, $latest_news_count, $footer_email, $footer_address, $footer_phone, $footer_social_facebook, $footer_social_twitter, $footer_social_instagram, $komentar_aktif, $komentar_moderasi, $komentar_captcha, $komentar_max_links, $komentar_interval_detik, $komentar_kata_kasar, $id);
            if ($stmt->execute()) {
                $success = 'Pengaturan portal berhasil disimpan.';
                $current['site_name'] = $site_name;
                $current['site_tagline'] = $site_tagline;
                $current['logo_path'] = $logo_path;
                $current['favicon_path'] = $favicon_path;
                $current['latest_news_count'] = $latest_news_count;
                $current['footer_email'] = $footer_email;
                $current['footer_address'] = $footer_address;
                $current['footer_phone'] = $footer_phone;
                $current['footer_social_facebook'] = $footer_social_facebook;
                $current['footer_social_twitter'] = $footer_social_twitter;
                $current['footer_social_instagram'] = $footer_social_instagram;
                $current['komentar_aktif'] = $komentar_aktif;
                $current['komentar_moderasi'] = $komentar_moderasi;
                $current['komentar_captcha'] = $komentar_captcha;
                $current['komentar_max_links'] = $komentar_max_links;
                $current['komentar_interval_detik'] = $komentar_interval_detik;
                $current['komentar_kata_kasar'] = $komentar_kata_kasar;
            } else {
                $error = 'Terjadi kesalahan saat menyimpan pengaturan.';
            }
            $stmt->close();
        }
    }
}

include __DIR__ . '/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Pengaturan Portal</h1>
</div>

<?php if ($error): ?>
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

<div class="row">
    <div class="col-md-7">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">Identitas Portal</h2>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Nama Portal</label>
                        <input type="text" name="site_name" class="form-control" required value="<?php echo htmlspecialchars($current['site_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tagline Portal</label>
                        <input type="text" name="site_tagline" class="form-control" value="<?php echo htmlspecialchars($current['site_tagline'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo Portal</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <div class="form-text">Disarankan gambar horizontal (format: JPG, PNG, WEBP, atau SVG).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Favicon</label>
                        <input type="file" name="favicon" class="form-control" accept="image/x-icon,image/png,image/svg+xml">
                        <div class="form-text">Ikon kecil yang tampil di tab browser (format: ICO, PNG, JPG, atau SVG, disarankan 32×32px).</div>
                    </div>
                    <hr class="my-4">
                    <h3 class="h6 mb-3">Berita Terkini</h3>
                    <div class="mb-3">
                        <label class="form-label">Jumlah Berita di Detail</label>
                        <input type="number" name="latest_news_count" class="form-control" min="1" max="20" value="<?php echo (int)($current['latest_news_count'] ?? 5); ?>">
                        <div class="form-text">Jumlah berita terbaru yang tampil di kolom samping halaman detail berita.</div>
                    </div>
                    <hr class="my-4">
                    <h3 class="h6 mb-3">Informasi Footer</h3>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="footer_email" class="form-control" value="<?php echo htmlspecialchars($current['footer_email'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="footer_address" class="form-control" rows="2"><?php echo htmlspecialchars($current['footer_address'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="footer_phone" class="form-control" value="<?php echo htmlspecialchars($current['footer_phone'] ?? ''); ?>">
                    </div>
                    <hr class="my-4">
                    <h3 class="h6 mb-3">Media Sosial</h3>
                    <div class="mb-3">
                        <label class="form-label">Facebook URL</label>
                        <input type="url" name="footer_social_facebook" class="form-control" value="<?php echo htmlspecialchars($current['footer_social_facebook'] ?? ''); ?>" placeholder="https://facebook.com/username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Twitter/X URL</label>
                        <input type="url" name="footer_social_twitter" class="form-control" value="<?php echo htmlspecialchars($current['footer_social_twitter'] ?? ''); ?>" placeholder="https://twitter.com/username">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Instagram URL</label>
                        <input type="url" name="footer_social_instagram" class="form-control" value="<?php echo htmlspecialchars($current['footer_social_instagram'] ?? ''); ?>" placeholder="https://instagram.com/username">
                    </div>
                    <hr class="my-4">
                    <h3 class="h6 mb-3">Komentar & Anti-Spam</h3>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="komentar_aktif" id="komentar_aktif" <?php echo ((int)($current['komentar_aktif'] ?? 1) === 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="komentar_aktif">Aktifkan komentar</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="komentar_moderasi" id="komentar_moderasi" <?php echo ((int)($current['komentar_moderasi'] ?? 1) === 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="komentar_moderasi">Moderasi manual (tahan sebelum tampil)</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="komentar_captcha" id="komentar_captcha" <?php echo ((int)($current['komentar_captcha'] ?? 1) === 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="komentar_captcha">Aktifkan captcha hitung</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Maksimal link per komentar</label>
                        <input type="number" name="komentar_max_links" class="form-control" min="0" max="10" value="<?php echo (int)($current['komentar_max_links'] ?? 2); ?>">
                        <div class="form-text">Komentar dengan link lebih banyak otomatis ditandai spam.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jeda kirim per IP (detik)</label>
                        <input type="number" name="komentar_interval_detik" class="form-control" min="5" max="600" value="<?php echo (int)($current['komentar_interval_detik'] ?? 30); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kata terlarang (pisah koma/baris)</label>
                        <textarea name="komentar_kata_kasar" class="form-control" rows="3" placeholder="judi, slot, togel"><?php echo htmlspecialchars($current['komentar_kata_kasar'] ?? ''); ?></textarea>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            Simpan Pengaturan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">Pratinjau Logo</h2>
                <?php if (!empty($current['logo_path'])): ?>
                    <div class="mb-2">
                        <div class="border rounded p-3 bg-light d-inline-block">
                            <img src="../<?php echo htmlspecialchars($current['logo_path']); ?>" alt="<?php echo htmlspecialchars($current['site_name'] ?? 'Portal'); ?>" style="max-height:60px;">
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">Belum ada logo yang diunggah.</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">Pratinjau Favicon</h2>
                <?php if (!empty($current['favicon_path'])): ?>
                    <div class="mb-2">
                        <div class="border rounded p-3 bg-light d-inline-flex align-items-center gap-3">
                            <img src="../<?php echo htmlspecialchars($current['favicon_path']); ?>" alt="Favicon" style="width:32px;height:32px;object-fit:contain;">
                            <span class="small text-muted">32×32px</span>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted small mb-0">Belum ada favicon yang diunggah.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/footer.php';
