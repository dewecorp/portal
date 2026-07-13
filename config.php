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

$checkLatestNewsCount = $conn->query("SHOW COLUMNS FROM settings LIKE 'latest_news_count'");
if ($checkLatestNewsCount && $checkLatestNewsCount->num_rows === 0) {
    $conn->query("ALTER TABLE settings ADD COLUMN latest_news_count INT NOT NULL DEFAULT 5 AFTER favicon_path");
}

$resultSettings = $conn->query("SELECT site_name, site_tagline, logo_path, favicon_path, latest_news_count, footer_email, footer_address, footer_phone, footer_social_facebook, footer_social_twitter, footer_social_instagram FROM settings ORDER BY id ASC LIMIT 1");
if ($resultSettings && $resultSettings->num_rows > 0) {
    $row = $resultSettings->fetch_assoc();
    $settings['site_name'] = $row['site_name'];
    $settings['site_tagline'] = $row['site_tagline'];
    $settings['logo_path'] = $row['logo_path'];
    $settings['favicon_path'] = $row['favicon_path'] ?? null;
    $settings['latest_news_count'] = max(1, min(20, (int)($row['latest_news_count'] ?? 5)));
    $settings['footer_email'] = $row['footer_email'] ?? null;
    $settings['footer_address'] = $row['footer_address'] ?? null;
    $settings['footer_phone'] = $row['footer_phone'] ?? null;
    $settings['footer_social_facebook'] = $row['footer_social_facebook'] ?? null;
    $settings['footer_social_twitter'] = $row['footer_social_twitter'] ?? null;
    $settings['footer_social_instagram'] = $row['footer_social_instagram'] ?? null;
}
