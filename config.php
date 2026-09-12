<?php
// Set timezone to Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'portal_berita';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// Set MySQL timezone
$conn->query("SET time_zone = '+07:00'");

$settings = [
    'site_name' => 'Portal Berita',
    'site_tagline' => 'Portal berita modern dan informatif',
    'logo_path' => null,
    'favicon_path' => null,
    'latest_news_count' => 5,
    'footer_email' => null,
    'footer_address' => null,
    'footer_phone' => null,
    'footer_social_facebook' => null,
    'footer_social_twitter' => null,
    'footer_social_instagram' => null,
];

function berita_image_url(?string $gambar, string $prefix = ''): string
{
    $gambar = trim((string)$gambar);
    if ($gambar === '' || $gambar === '0') {
        return '';
    }

    if (preg_match('~^(https?:)?//|^data:~i', $gambar)) {
        return $gambar;
    }

    $gambar = ltrim(str_replace('\\', '/', $gambar), '/');
    if (strpos($gambar, 'uploads/') === 0) {
        return $prefix . $gambar;
    }

    return $prefix . 'uploads/' . $gambar;
}

function berita_url(array $berita, string $prefix = ''): string
{
    $slug = trim((string)($berita['slug'] ?? $berita['berita_slug'] ?? ''));
    if ($slug !== '') {
        return $prefix . rawurlencode($slug);
    }

    return $prefix . 'detail?id=' . (int)($berita['id'] ?? 0);
}

function upload_file_path(?string $path): string
{
    $path = trim((string)$path);
    if ($path === '' || $path === '0') {
        return '';
    }

    if (preg_match('~^(https?:)?//|^data:~i', $path)) {
        return '';
    }

    $fileName = basename(str_replace('\\', '/', $path));
    if ($fileName === '' || $fileName === '.' || $fileName === '..') {
        return '';
    }

    return __DIR__ . '/uploads/' . $fileName;
}

function delete_uploaded_file(?string $path): bool
{
    $filePath = upload_file_path($path);
    if ($filePath === '') {
        return false;
    }

    $uploadDir = realpath(__DIR__ . '/uploads');
    $realPath = realpath($filePath);
    if (!$uploadDir || !$realPath || strpos($realPath, $uploadDir . DIRECTORY_SEPARATOR) !== 0) {
        return false;
    }

    return is_file($realPath) && @unlink($realPath);
}

function ensure_visitor_logs_table(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS visitor_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ip_hash CHAR(64) NOT NULL,
        user_agent TEXT NULL,
        os VARCHAR(80) NOT NULL DEFAULT 'Tidak diketahui',
        country VARCHAR(100) NOT NULL DEFAULT 'Tidak diketahui',
        page_type VARCHAR(40) NOT NULL DEFAULT 'page',
        page_url VARCHAR(500) NULL,
        kategori_id INT UNSIGNED NULL,
        berita_id INT UNSIGNED NULL,
        referer VARCHAR(500) NULL,
        visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_visitor_logs_visited_at (visited_at),
        INDEX idx_visitor_logs_kategori (kategori_id),
        INDEX idx_visitor_logs_berita (berita_id),
        INDEX idx_visitor_logs_os (os),
        INDEX idx_visitor_logs_country (country)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensure_komentar_table(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS komentar (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        berita_id INT UNSIGNED NOT NULL,
        nama VARCHAR(100) NOT NULL,
        email VARCHAR(150) NULL,
        website VARCHAR(255) NULL,
        isi TEXT NOT NULL,
        status ENUM('pending','approved','spam') NOT NULL DEFAULT 'pending',
        ip_hash CHAR(64) NULL,
        user_agent VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_komentar_berita (berita_id),
        INDEX idx_komentar_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensure_newsletter_table(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensure_portal_extras(mysqli $conn): void
{
    ensure_komentar_table($conn);
    ensure_newsletter_table($conn);
    $col = $conn->query("SHOW COLUMNS FROM berita LIKE 'views'");
    if ($col && $col->num_rows === 0) {
        $conn->query("ALTER TABLE berita ADD COLUMN views INT UNSIGNED NOT NULL DEFAULT 0 AFTER gambar");
    }
    $col = $conn->query("SHOW COLUMNS FROM kategori LIKE 'animasi'");
    if ($col && $col->num_rows === 0) {
        $conn->query("ALTER TABLE kategori ADD COLUMN animasi VARCHAR(30) NOT NULL DEFAULT 'fade-up' AFTER grid_style");
    }
    $gridCol = $conn->query("SHOW COLUMNS FROM kategori LIKE 'grid_style'");
    if ($gridCol && ($r = $gridCol->fetch_assoc()) && stripos((string)($r['Type'] ?? ''), 'varchar(20)') === false) {
        $conn->query("ALTER TABLE kategori MODIFY COLUMN grid_style VARCHAR(20) NOT NULL DEFAULT 'grid'");
    }
    $settingsCols = [
        "komentar_aktif TINYINT(1) NOT NULL DEFAULT 1",
        "komentar_moderasi TINYINT(1) NOT NULL DEFAULT 1",
        "komentar_captcha TINYINT(1) NOT NULL DEFAULT 1",
        "komentar_max_links TINYINT NOT NULL DEFAULT 2",
        "komentar_interval_detik INT NOT NULL DEFAULT 30",
        "komentar_kata_kasar TEXT NULL",
    ];
    foreach ($settingsCols as $def) {
        $name = explode(' ', trim($def))[0];
        $chk = $conn->query("SHOW COLUMNS FROM settings LIKE '$name'");
        if ($chk && $chk->num_rows === 0) {
            $conn->query("ALTER TABLE settings ADD COLUMN $def");
        }
    }
}

function komentar_bad_words(mysqli $conn): array
{
    $def = ['judi', 'slot', 'gacor', 'togel', 'viagra', 'porn', 'xxx', 'casino', 'pinjol'];
    try {
        $res = $conn->query("SHOW COLUMNS FROM settings LIKE 'komentar_kata_kasar'");
        if ($res && $res->num_rows > 0) {
            $r = $conn->query("SELECT komentar_kata_kasar FROM settings ORDER BY id ASC LIMIT 1");
            if ($r && ($row = $r->fetch_assoc()) && !empty($row['komentar_kata_kasar'])) {
                $custom = array_filter(array_map('trim', preg_split('/[,\n]+/', (string)$row['komentar_kata_kasar'])));
                if (!empty($custom)) return array_map('strtolower', $custom);
            }
        }
    } catch (Throwable $e) {}
    return $def;
}

function komentar_is_spam(string $nama, string $email, string $isi, string $website, int $maxLinks): array
{
    $isiLower = strtolower($isi . ' ' . $nama . ' ' . $website);
    if ($website !== '' && !filter_var($website, FILTER_VALIDATE_URL)) {
        return [true, 'URL website tidak valid.'];
    }
    preg_match_all('~https?://|www\.|\[url|href=~i', $isi, $m);
    if (count($m[0]) > $maxLinks) {
        return [true, 'Komentar terdeteksi sebagai spam (terlalu banyak link).'];
    }
    if (preg_match('~[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}~i', $nama) && strlen($nama) > 5 && filter_var($nama, FILTER_VALIDATE_EMAIL)) {
        return [true, 'Nama tidak boleh berupa email.'];
    }
    return [false, ''];
}

function hitung_waktu_baca(string $html): int
{
    $kata = str_word_count(strip_tags($html));
    return max(1, (int)ceil($kata / 200));
}

function section_animasi_list(): array
{
    return [
        'tanpa' => 'Tanpa Efek',
        'fade-up' => 'Fade Up',
        'fade-down' => 'Fade Down',
        'fade-left' => 'Fade Kiri',
        'fade-right' => 'Fade Kanan',
        'fade' => 'Fade Polos',
        'zoom-in' => 'Zoom In',
        'zoom-out' => 'Zoom Out',
        'flip' => 'Flip',
        'bounce' => 'Bounce',
        'slide' => 'Slide + Fade',
    ];
}

function section_anim_attr(string $anim): string
{
    $anim = trim($anim);
    if ($anim === '' || $anim === 'tanpa') return '';
    if (!isset(section_animasi_list()[$anim])) $anim = 'fade-up';
    return ' data-animate="' . htmlspecialchars($anim) . '"';
}

function section_tipe_home(): array
{
    return [
        'hero' => 'Hero',
        'carousel' => 'Carousel',
        'carousel_berita' => 'Carousel Berita',
        'kategori_berita' => 'Kategori Berita',
        'latest' => 'Berita',
        'populer' => 'Paling Dibaca',
        'countdown' => 'Countdown Timer',
        'image' => 'Image',
        'video' => 'Video',
        'audio' => 'Audio',
        'html' => 'Custom HTML',
        'sambutan' => 'Sambutan',
        'statistik' => 'Statistik',
        'agenda' => 'Agenda',
        'pengumuman' => 'Pengumuman',
        'galeri' => 'Galeri',
        'guru' => 'Guru',
        'prestasi' => 'Prestasi',
        'ekskul' => 'Ekskul',
        'cta' => 'CTA',
        'teks' => 'Teks',
        'kategori' => 'Grid Kategori',
    ];
}

function section_tipe_footer(): array
{
    return [
        'brand' => 'Brand + Sosmed',
        'links' => 'Tautan Cepat',
        'contact' => 'Info Kontak',
        'newsletter' => 'Newsletter',
        'teks' => 'Teks Bebas',
        'image' => 'Image',
        'html' => 'Custom HTML',
        'sambutan' => 'Sambutan',
        'statistik' => 'Statistik',
        'galeri' => 'Galeri',
        'pengumuman' => 'Pengumuman',
        'cta' => 'CTA',
        'bottom' => 'Copyright Bawah',
    ];
}

function section_widget_meta(): array
{
    return [
        'hero' => ['label' => 'Hero', 'icon' => '🖼️'],
        'carousel' => ['label' => 'Carousel', 'icon' => '🖼️'],
        'carousel_berita' => ['label' => 'Carousel Berita', 'icon' => '📰'],
        'kategori_berita' => ['label' => 'Kategori Berita', 'icon' => '🏷️'],
        'latest' => ['label' => 'Berita', 'icon' => '📰'],
        'populer' => ['label' => 'Paling Dibaca', 'icon' => '🔥'],
        'countdown' => ['label' => 'Countdown Timer', 'icon' => '🕒'],
        'image' => ['label' => 'Image', 'icon' => '🖼️'],
        'video' => ['label' => 'Video', 'icon' => '🎬'],
        'audio' => ['label' => 'Audio', 'icon' => '🎵'],
        'html' => ['label' => 'Custom HTML', 'icon' => '🧩'],
        'sambutan' => ['label' => 'Sambutan', 'icon' => '🧑‍💼'],
        'statistik' => ['label' => 'Statistik', 'icon' => '📊'],
        'agenda' => ['label' => 'Agenda', 'icon' => '📅'],
        'pengumuman' => ['label' => 'Pengumuman', 'icon' => '📢'],
        'galeri' => ['label' => 'Galeri', 'icon' => '🖼️'],
        'guru' => ['label' => 'Guru', 'icon' => '🧑‍🏫'],
        'prestasi' => ['label' => 'Prestasi', 'icon' => '🏆'],
        'ekskul' => ['label' => 'Ekskul', 'icon' => '⚽'],
        'cta' => ['label' => 'CTA', 'icon' => '📣'],
        'teks' => ['label' => 'Teks', 'icon' => '📝'],
        'kategori' => ['label' => 'Grid Kategori', 'icon' => '🗂️'],
        'brand' => ['label' => 'Brand + Sosmed', 'icon' => '✨'],
        'links' => ['label' => 'Tautan Cepat', 'icon' => '🔗'],
        'contact' => ['label' => 'Info Kontak', 'icon' => '📞'],
        'newsletter' => ['label' => 'Newsletter', 'icon' => '✉️'],
        'bottom' => ['label' => 'Copyright Bawah', 'icon' => '©️'],
    ];
}

function section_gaya_gambar_list(): array
{
    return [
        'statis' => 'Statis',
        'kenburns' => 'Ken Burns',
        'kenburns-balik' => 'Ken Burns Balik',
        'zoom-lambat' => 'Zoom Lambat',
        'geser-kiri' => 'Geser Kiri',
        'geser-kanan' => 'Geser Kanan',
        'fade-zoom' => 'Fade Zoom',
        'melayang' => 'Melayang',
    ];
}

function section_default_cfg(string $tipe): array
{
    switch ($tipe) {
        case 'hero': return ['jumlah' => 5, 'subjudul' => '', 'isi' => '', 'gambar' => '', 'gaya_gambar' => 'kenburns', 'tombol_teks' => '', 'tombol_link' => ''];
        case 'carousel': return ['galeri' => [], 'gaya_gambar' => 'kenburns', 'autoplay' => 5];
        case 'carousel_berita': return ['jumlah' => 8, 'kategori_id' => 0, 'autoplay' => 5];
        case 'kategori_berita':
        case 'kategori': return ['kategori_id' => 0, 'jumlah' => 4, 'kolom' => 4, 'style_grid' => 'kartu-4'];
        case 'latest': return ['jumlah' => 8, 'kolom' => 4, 'tampil_tombol' => 1, 'style_grid' => 'sorotan-list'];
        case 'populer': return ['jumlah' => 5, 'kolom' => 4, 'style_grid' => 'overlay'];
        case 'galeri': return ['galeri' => [], 'gaya_gambar' => 'statis', 'kolom' => 4, 'style_grid' => 'kartu-4'];
        case 'guru': return ['nama' => '', 'mapel' => '', 'foto' => '', 'quote' => '', 'gaya_gambar' => 'statis', 'style_grid' => 'kartu-4'];
        case 'prestasi': return ['isi' => '', 'tahun' => '', 'style_grid' => 'sorotan-list'];
        case 'ekskul': return ['nama' => '', 'jadwal' => '', 'deskripsi' => '', 'style_grid' => 'kartu-4'];
        case 'agenda': return ['isi' => '', 'jumlah' => 5, 'style_grid' => 'timeline'];
        case 'countdown': return ['target_tanggal' => '', 'isi' => ''];
        case 'image': return ['gambar' => '', 'alt' => '', 'link' => '', 'gaya_gambar' => 'statis'];
        case 'video': return ['video_url' => '', 'poster' => ''];
        case 'audio': return ['audio_url' => ''];
        case 'sambutan': return ['nama' => '', 'jabatan' => '', 'foto' => '', 'isi' => '', 'gaya_gambar' => 'statis'];
        case 'statistik': return ['stat1_angka' => '1000', 'stat1_label' => 'Siswa', 'stat2_angka' => '100', 'stat2_label' => 'Guru', 'stat3_angka' => '50', 'stat3_label' => 'Prestasi', 'stat4_angka' => '20', 'stat4_label' => 'Ekskul'];
        case 'cta': return ['deskripsi' => '', 'cta_teks' => 'Lihat Selengkapnya', 'cta_link' => 'semua-kategori'];
        case 'newsletter': return ['judul' => 'Kabar Terbaru', 'deskripsi' => 'Dapatkan info terkini lewat email.'];
        case 'brand': return ['deskripsi' => ''];
        case 'bottom': return ['isi' => ''];
        case 'teks': return ['isi' => '', 'rata' => 'kiri'];
        case 'html': return ['html' => ''];
        default: return [];
    }
}

function section_media_gaya_class(array $cfg): string
{
    $g = trim((string)($cfg['gaya_gambar'] ?? 'statis'));
    if ($g === '' || $g === 'statis') return '';
    return ' media-gaya-' . preg_replace('~[^a-z-]~', '', $g);
}

function ensure_sections_table(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS sections (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        area ENUM('home','footer') NOT NULL DEFAULT 'home',
        tipe VARCHAR(40) NOT NULL DEFAULT 'latest',
        judul VARCHAR(200) NULL,
        pengaturan TEXT NULL,
        animasi VARCHAR(30) NOT NULL DEFAULT 'fade-up',
        urutan INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_sections_area (area, urutan)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function section_pengaturan(array $row): array
{
    $def = [];
    if (!empty($row['pengaturan'])) {
        $d = json_decode((string)$row['pengaturan'], true);
        if (is_array($d)) $def = $d;
    }
    return $def;
}

function get_active_sections(mysqli $conn, string $area): array
{
    ensure_sections_table($conn);
    $area = $area === 'footer' ? 'footer' : 'home';
    $out = [];
    $stmt = $conn->prepare("SELECT * FROM sections WHERE area = ? AND is_active = 1 ORDER BY urutan ASC, id ASC");
    $stmt->bind_param('s', $area);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $out[] = $r;
    $stmt->close();
    return $out;
}

function seed_default_sections(mysqli $conn): void
{
    ensure_sections_table($conn);
    $cek = $conn->query("SELECT COUNT(*) AS jml FROM sections");
    $jml = $cek ? (int)($cek->fetch_assoc()['jml'] ?? 0) : 0;
    if ($jml > 0) return;
    $defaults = [
        ['home', 'hero', 'Headline', '{"jumlah":5}', 'fade-up', 1],
        ['home', 'latest', 'Berita Terkini', '{"jumlah":12,"kolom":4,"tampil_tombol":1}', 'fade-up', 2],
        ['home', 'kategori', 'Kategori', '{"kategori_id":0,"jumlah":4}', 'fade-up', 3],
        ['home', 'populer', 'Paling Dibaca', '{"jumlah":5}', 'fade-left', 4],
        ['footer', 'brand', 'Brand', '{}', 'fade-up', 1],
        ['footer', 'links', 'Tautan Cepat', '{}', 'fade-up', 2],
        ['footer', 'contact', 'Informasi', '{}', 'fade-up', 3],
        ['footer', 'newsletter', 'Newsletter', '{"judul":"Dapatkan kabar terbaru","deskripsi":"Masukkan email untuk info terkini."}', 'fade-up', 4],
        ['footer', 'bottom', 'Copyright', '{}', 'fade', 5],
    ];
    $stmt = $conn->prepare("INSERT INTO sections (area, tipe, judul, pengaturan, animasi, urutan, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    foreach ($defaults as $d) {
        $stmt->bind_param('sssssi', $d[0], $d[1], $d[2], $d[3], $d[4], $d[5]);
        $stmt->execute();
    }
    $stmt->close();
}

function visitor_detect_os(string $userAgent): string
{
    $ua = strtolower($userAgent);
    if (strpos($ua, 'windows nt') !== false) return 'Windows';
    if (strpos($ua, 'android') !== false) return 'Android';
    if (strpos($ua, 'iphone') !== false || strpos($ua, 'ipad') !== false || strpos($ua, 'ios') !== false) return 'iOS';
    if (strpos($ua, 'mac os') !== false || strpos($ua, 'macintosh') !== false) return 'macOS';
    if (strpos($ua, 'linux') !== false) return 'Linux';
    return 'Tidak diketahui';
}

function visitor_detect_country(string $acceptLanguage): string
{
    if (!preg_match('/(?:^|,)[a-z]{2}-([A-Z]{2})/i', $acceptLanguage, $match)) {
        return 'Tidak diketahui';
    }

    $code = strtoupper($match[1]);
    $countries = [
        'ID' => 'Indonesia', 'US' => 'Amerika Serikat', 'GB' => 'Britania Raya', 'MY' => 'Malaysia',
        'SG' => 'Singapura', 'AU' => 'Australia', 'JP' => 'Jepang', 'KR' => 'Korea Selatan',
        'CN' => 'Tiongkok', 'IN' => 'India', 'DE' => 'Jerman', 'FR' => 'Prancis', 'NL' => 'Belanda',
        'BR' => 'Brasil', 'CA' => 'Kanada', 'PH' => 'Filipina', 'TH' => 'Thailand', 'VN' => 'Vietnam'
    ];

    return $countries[$code] ?? $code;
}

function visitor_current_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        $value = trim((string)($_SERVER[$key] ?? ''));
        if ($value === '') continue;
        $parts = explode(',', $value);
        return trim($parts[0]);
    }
    return '0.0.0.0';
}

function track_public_visitor(mysqli $conn): void
{
    if (PHP_SAPI === 'cli') {
        return;
    }

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script === '' || $script === 'login.php' || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false) {
        return;
    }

    ensure_visitor_logs_table($conn);

    $kategoriId = null;
    $beritaId = null;
    $pageType = 'page';

    if ($script === 'detail.php' && (isset($_GET['id']) || isset($_GET['slug']))) {
        $pageType = 'berita';
        $beritaId = (int)($_GET['id'] ?? 0);
        $slug = trim((string)($_GET['slug'] ?? ''));
        if ($beritaId > 0) {
            $stmt = $conn->prepare('SELECT kategori_id FROM berita WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $beritaId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $kategoriId = $row ? (int)$row['kategori_id'] : null;
        } elseif ($slug !== '') {
            $stmt = $conn->prepare('SELECT id, kategori_id FROM berita WHERE slug = ? LIMIT 1');
            $stmt->bind_param('s', $slug);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                $beritaId = (int)$row['id'];
                $kategoriId = (int)$row['kategori_id'];
            }
        }
    } elseif ($script === 'kategori.php') {
        $pageType = 'kategori';
        if (isset($_GET['kategori'])) {
            $kategoriId = (int)$_GET['kategori'];
        }
    } elseif ($script === 'index.php' || $script === '') {
        $pageType = 'beranda';
    }

    $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 1000);
    $os = visitor_detect_os($userAgent);
    $country = visitor_detect_country((string)($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $pageUrl = substr($scheme . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''), 0, 500);
    $referer = substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 500);
    $ipHash = hash('sha256', visitor_current_ip() . '|portal_berita');

    $stmt = $conn->prepare('INSERT INTO visitor_logs (ip_hash, user_agent, os, country, page_type, page_url, kategori_id, berita_id, referer, visited_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->bind_param('ssssssiis', $ipHash, $userAgent, $os, $country, $pageType, $pageUrl, $kategoriId, $beritaId, $referer);
    $stmt->execute();
    $stmt->close();
}

ensure_visitor_logs_table($conn);
ensure_portal_extras($conn);
ensure_sections_table($conn);
seed_default_sections($conn);
require_once __DIR__ . '/sections_render.php';

$checkLatestNewsCount = $conn->query("SHOW COLUMNS FROM settings LIKE 'latest_news_count'");
if ($checkLatestNewsCount && $checkLatestNewsCount->num_rows === 0) {
    $conn->query("ALTER TABLE settings ADD COLUMN latest_news_count INT NOT NULL DEFAULT 5 AFTER favicon_path");
}

$resultSettings = $conn->query("SELECT * FROM settings ORDER BY id ASC LIMIT 1");
if ($resultSettings && $resultSettings->num_rows > 0) {
    $row = $resultSettings->fetch_assoc();
    $settings['site_name'] = $row['site_name'] ?? $settings['site_name'];
    $settings['site_tagline'] = $row['site_tagline'] ?? null;
    $settings['logo_path'] = $row['logo_path'] ?? null;
    $settings['favicon_path'] = $row['favicon_path'] ?? null;
    $settings['latest_news_count'] = max(1, min(20, (int)($row['latest_news_count'] ?? 5)));
    $settings['footer_email'] = $row['footer_email'] ?? null;
    $settings['footer_address'] = $row['footer_address'] ?? null;
    $settings['footer_phone'] = $row['footer_phone'] ?? null;
    $settings['footer_social_facebook'] = $row['footer_social_facebook'] ?? null;
    $settings['footer_social_twitter'] = $row['footer_social_twitter'] ?? null;
    $settings['footer_social_instagram'] = $row['footer_social_instagram'] ?? null;
    $settings['komentar_aktif'] = (int)($row['komentar_aktif'] ?? 1);
    $settings['komentar_moderasi'] = (int)($row['komentar_moderasi'] ?? 1);
    $settings['komentar_captcha'] = (int)($row['komentar_captcha'] ?? 1);
    $settings['komentar_max_links'] = max(0, min(10, (int)($row['komentar_max_links'] ?? 2)));
    $settings['komentar_interval_detik'] = max(5, min(600, (int)($row['komentar_interval_detik'] ?? 30)));
}
