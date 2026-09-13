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
    'footer_admin_link_url VARCHAR(255) NULL DEFAULT "/admin/login"',
    'footer_admin_link_title VARCHAR(255) NULL DEFAULT "Login Admin"',
    'footer_admin_link_show TINYINT(1) NOT NULL DEFAULT 1',
    'komentar_aktif TINYINT(1) NOT NULL DEFAULT 1',
    'komentar_moderasi TINYINT(1) NOT NULL DEFAULT 1',
    'komentar_captcha TINYINT(1) NOT NULL DEFAULT 1',
    'komentar_max_links TINYINT NOT NULL DEFAULT 2',
    'komentar_interval_detik INT NOT NULL DEFAULT 30',
    'komentar_kata_kasar TEXT NULL',
    "theme_id VARCHAR(50) NOT NULL DEFAULT 'indigo'",
    "theme_color_id VARCHAR(50) NOT NULL DEFAULT 'purple'",
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
    $latest_news_count = max(1, min(20, (int)($_POST['latest_news_count'] ?? ($current['latest_news_count'] ?? 5))));
    $footer_email = trim($_POST['footer_email'] ?? '');
    $footer_address = trim($_POST['footer_address'] ?? '');
    $footer_phone = trim($_POST['footer_phone'] ?? '');
    $footer_social_facebook = trim($_POST['footer_social_facebook'] ?? '');
    $footer_social_twitter = trim($_POST['footer_social_twitter'] ?? '');
    $footer_social_instagram = trim($_POST['footer_social_instagram'] ?? '');
    $footer_admin_link_url = trim($_POST['footer_admin_link_url'] ?? ($current['footer_admin_link_url'] ?? '/admin/login'));
    $footer_admin_link_title = trim($_POST['footer_admin_link_title'] ?? ($current['footer_admin_link_title'] ?? 'Login Admin'));
    $footer_admin_link_show = isset($_POST['footer_admin_link_show']) ? 1 : 0;
    $komentar_aktif = isset($_POST['komentar_aktif']) ? 1 : 0;
    $komentar_moderasi = isset($_POST['komentar_moderasi']) ? 1 : 0;
    $komentar_captcha = isset($_POST['komentar_captcha']) ? 1 : 0;
    $komentar_max_links = max(0, min(10, (int)($_POST['komentar_max_links'] ?? 2)));
    $komentar_interval_detik = max(5, min(600, (int)($_POST['komentar_interval_detik'] ?? 30)));
    $komentar_kata_kasar = trim((string)($_POST['komentar_kata_kasar'] ?? ''));
    $theme_id = (string)($_POST['theme_id'] ?? ($current['theme_id'] ?? 'indigo'));
    $theme_color_id = (string)($_POST['theme_color_id'] ?? ($current['theme_color_id'] ?? 'purple'));
    if (!array_key_exists($theme_id, site_theme_styles())) $theme_id = 'indigo';
    if (!array_key_exists($theme_color_id, site_theme_accents())) $theme_color_id = 'purple';
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
            $stmt = $conn->prepare("UPDATE settings SET site_name = ?, site_tagline = ?, logo_path = ?, favicon_path = ?, latest_news_count = ?, footer_email = ?, footer_address = ?, footer_phone = ?, footer_social_facebook = ?, footer_social_twitter = ?, footer_social_instagram = ?, footer_admin_link_url = ?, footer_admin_link_title = ?, footer_admin_link_show = ?, komentar_aktif = ?, komentar_moderasi = ?, komentar_captcha = ?, komentar_max_links = ?, komentar_interval_detik = ?, komentar_kata_kasar = ?, theme_id = ?, theme_color_id = ? WHERE id = ?");
            $id = (int)$current['id'];
            $stmt->bind_param('ssssissssssssiiiiiissi', $site_name, $site_tagline, $logo_path, $favicon_path, $latest_news_count, $footer_email, $footer_address, $footer_phone, $footer_social_facebook, $footer_social_twitter, $footer_social_instagram, $footer_admin_link_url, $footer_admin_link_title, $footer_admin_link_show, $komentar_aktif, $komentar_moderasi, $komentar_captcha, $komentar_max_links, $komentar_interval_detik, $komentar_kata_kasar, $theme_id, $theme_color_id, $id);
            if ($stmt->execute()) {
                admin_log($conn, 'update', 'Memperbarui pengaturan portal');
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
                $current['footer_admin_link_url'] = $footer_admin_link_url;
                $current['footer_admin_link_title'] = $footer_admin_link_title;
                $current['footer_admin_link_show'] = $footer_admin_link_show;
                $current['komentar_aktif'] = $komentar_aktif;
                $current['komentar_moderasi'] = $komentar_moderasi;
                $current['komentar_captcha'] = $komentar_captcha;
                $current['komentar_max_links'] = $komentar_max_links;
                $current['komentar_interval_detik'] = $komentar_interval_detik;
                $current['komentar_kata_kasar'] = $komentar_kata_kasar;
                $current['theme_id'] = $theme_id;
                $current['theme_color_id'] = $theme_color_id;
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
    <form method="post" enctype="multipart/form-data" class="col-12">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">Tema & Gaya</h2>
                    <div class="mb-2 fw-semibold small text-muted">Gaya Tema</div>
                    <div class="row g-2 mb-3" id="pbThemeGrid">
                        <?php foreach (site_theme_styles() as $tKey => $tStyle): ?>
                            <div class="col-6 col-lg-4">
                                <label class="pb-theme-item <?php echo ($current['theme_id'] ?? 'indigo') === $tKey ? 'pb-selected' : ''; ?>" data-pb-id="<?php echo $tKey; ?>">
                                    <input type="radio" name="theme_id" value="<?php echo $tKey; ?>" class="pb-radio" <?php echo ($current['theme_id'] ?? 'indigo') === $tKey ? 'checked' : ''; ?>>
                                    <span class="pb-theme-bar" style="background: linear-gradient(90deg, <?php echo $tStyle['nav1']; ?>, <?php echo $tStyle['nav2']; ?>, <?php echo $tStyle['nav3']; ?>);"></span>
                                    <span class="pb-theme-btn" style="background: linear-gradient(90deg, <?php echo $tStyle['p1']; ?>, <?php echo $tStyle['p2']; ?>);"></span>
                                    <span class="pb-theme-name"><?php echo htmlspecialchars($tStyle['label']); ?></span>
                                    <span class="pb-check">&#10003;</span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mb-1 fw-semibold small text-muted">Warna Aksen</div>
                    <div class="d-flex flex-wrap gap-2 mb-3" id="pbAccentGrid">
                        <?php foreach (site_theme_accents() as $aKey => $aAccent): ?>
                            <label class="pb-accent-item <?php echo ($current['theme_color_id'] ?? 'purple') === $aKey ? 'pb-selected' : ''; ?>" title="<?php echo htmlspecialchars($aAccent['label']); ?> <?php echo htmlspecialchars($aAccent['main']); ?>">
                                <input type="radio" name="theme_color_id" value="<?php echo $aKey; ?>" class="pb-radio" <?php echo ($current['theme_color_id'] ?? 'purple') === $aKey ? 'checked' : ''; ?>>
                                <span class="pb-accent-dot" style="background: <?php echo $aAccent['main']; ?>;"></span>
                                <span class="pb-check">&#10003;</span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-text mb-3">Pilih gaya visual dan warna aksen untuk seluruh halaman pengunjung. Pratinjau tampil di panel kanan.</div>
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h3 class="h6 mb-3">Identitas Portal</h3>
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
                        <?php if (!empty($current['logo_path'])): ?>
                            <div class="mt-2">
                                <div class="border rounded p-3 bg-light d-inline-block">
                                    <img src="../<?php echo htmlspecialchars($current['logo_path']); ?>" alt="<?php echo htmlspecialchars($current['site_name'] ?? 'Portal'); ?>" style="max-height:60px;">
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Favicon</label>
                        <input type="file" name="favicon" class="form-control" accept="image/x-icon,image/png,image/svg+xml">
                        <div class="form-text">Ikon kecil yang tampil di tab browser (format: ICO, PNG, JPG, atau SVG, disarankan 32×32px).</div>
                        <?php if (!empty($current['favicon_path'])): ?>
                            <div class="mt-2">
                                <div class="border rounded p-3 bg-light d-inline-flex align-items-center gap-3">
                                    <img src="../<?php echo htmlspecialchars($current['favicon_path']); ?>" alt="Favicon" style="width:32px;height:32px;object-fit:contain;">
                                    <span class="small text-muted">32×32px</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
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
                    <hr>
                    <div class="mb-3">
                        <label class="form-label">Link Login Admin (di widget copyright)</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="footer_admin_link_show" id="footer_admin_link_show" value="1" <?php echo ((int)($current['footer_admin_link_show'] ?? 1) === 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="footer_admin_link_show">Tampilkan Link Login Admin</label>
                        </div>
                        <label class="form-label mt-2">URL Link</label>
                        <input type="text" name="footer_admin_link_url" class="form-control mb-2" value="<?php echo htmlspecialchars($current['footer_admin_link_url'] ?? '/admin/login'); ?>" placeholder="/admin/login">
                        <label class="form-label">Judul Link</label>
                        <input type="text" name="footer_admin_link_title" class="form-control" value="<?php echo htmlspecialchars($current['footer_admin_link_title'] ?? 'Login Admin'); ?>" placeholder="Login Admin">
                    </div>
                </div>
            </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Pratinjau Tema</h2>
                <div class="rounded-3 mb-2 d-flex align-items-center px-3" id="pbPrevNav" style="height:44px;background:linear-gradient(90deg,#7e22ce,#9333ea,#2563eb);">
                    <span class="fw-bold text-white" style="letter-spacing:.5px;">Portal Berita</span>
                </div>
                <div class="rounded mb-2 d-flex gap-2 align-items-center p-2" style="border:1px solid #e5e7eb;">
                    <span class="pb-theme-btn d-inline-block" id="pbPrevBtn" style="background:linear-gradient(90deg,#9333ea,#2563eb);"></span>
                    <span class="badge rounded-pill" id="pbPrevPill" style="background:linear-gradient(90deg,#9333ea,#2563eb);">Kategori</span>
                    <span class="text-decoration-underline small" id="pbPrevLink" style="color:#9333ea;">tautan artikel</span>
                </div>
                <div class="rounded p-2 small text-muted mb-2" style="border:1px solid #e5e7eb;border-left:4px solid #9333ea;" id="pbPrevQuote">
                    Kutipan & blok isi artikel mengikuti warna aksen tema.
                </div>
                <div class="rounded px-3 py-2 text-white small" id="pbPrevFoot" style="background:linear-gradient(90deg,#581c87,#0f172a);">Area footer gelap</div>
            </div>
        </div>
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body">
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
                </div>
            </div>
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
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
</div>
        </div>
        </div>
        </div>
        <div class="mt-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
        </div>
    </form>
</div>

<?php
include __DIR__ . '/footer.php';

$__pbStyles = site_theme_styles();
$__pbAccents = site_theme_accents();
?>
<style>
    .pb-theme-item, .pb-accent-item {
        position: relative;
        display: block;
        cursor: pointer;
        border: 2px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 0.5rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .pb-theme-item:hover, .pb-accent-item:hover { border-color: #c7d2fe; }
    .pb-theme-item.pb-selected, .pb-accent-item.pb-selected {
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.18);
    }
    .pb-radio { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .pb-theme-bar {
        display: block;
        height: 20px;
        border-radius: 0.4rem 0.4rem 0 0;
    }
    .pb-theme-btn {
        display: block;
        height: 10px;
        width: 46px;
        border-radius: 9999px;
        margin-top: 4px;
    }
    .pb-accent-item { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; }
    .pb-accent-dot { width: 22px; height: 22px; border-radius: 50%; display: block; box-shadow: inset 0 0 0 2px rgba(255,255,255,.35); }
    .pb-theme-name { display: block; font-size: .7rem; font-weight: 700; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pb-check {
        position: absolute; top: -6px; right: -6px;
        width: 18px; height: 18px; border-radius: 50%;
        background: #10b981; color: #fff; font-size: .7rem;
        display: none; align-items: center; justify-content: center;
        font-weight: 900;
    }
    .pb-selected .pb-check { display: flex; }
</style>
<script>
    const PB_STYLES = <?php echo json_encode($__pbStyles); ?>;
    const PB_ACCENTS = <?php echo json_encode($__pbAccents); ?>;
    function pbPreview() {
        var sid = document.querySelector('input[name="theme_id"]:checked');
        var cid = document.querySelector('input[name="theme_color_id"]:checked');
        var st = sid ? PB_STYLES[sid.value] : null;
        var ac = cid ? PB_ACCENTS[cid.value] : null;
        if (!st || !ac) return;
        var bar = (document.getElementById('pbPrevNav'));
        if (bar) bar.style.background = 'linear-gradient(90deg,' + st.nav1 + ',' + st.nav2 + ',' + st.nav3 + ')';
        var btn = document.getElementById('pbPrevBtn');
        if (btn) btn.style.background = 'linear-gradient(90deg,' + st.p1 + ',' + st.p2 + ')';
        var pill = document.getElementById('pbPrevPill');
        if (pill) pill.style.background = 'linear-gradient(90deg,' + st.p1 + ',' + st.p2 + ')';
        var link = document.getElementById('pbPrevLink');
        if (link) link.style.color = ac.main;
        var quote = document.getElementById('pbPrevQuote');
        if (quote) quote.style.borderLeftColor = ac.main;
        var foot = document.getElementById('pbPrevFoot');
        if (foot) foot.style.background = 'linear-gradient(90deg,' + st.foot + ',#0f172a)';
    }
    document.addEventListener('change', function (e) {
        if (e.target && (e.target.name === 'theme_id' || e.target.name === 'theme_color_id')) {
            document.querySelectorAll('.pb-theme-item').forEach(function (el) {
                el.classList.toggle('pb-selected', el.querySelector('input[name="theme_id"]').checked);
            });
            document.querySelectorAll('.pb-accent-item').forEach(function (el) {
                el.classList.toggle('pb-selected', el.querySelector('input[name="theme_color_id"]').checked);
            });
            pbPreview();
        }
    });
    pbPreview();
</script>
