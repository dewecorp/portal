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

function ensure_admin_logs_table(mysqli $conn): void
{
    static $done = false;
    if ($done) return;
    $conn->query("CREATE TABLE IF NOT EXISTS admin_logs (
        id INT PRIMARY KEY AUTO_INCREMENT,
        admin_id INT,
        action VARCHAR(50),
        details VARCHAR(255),
        created_at DATETIME
    )");
    $done = true;
}

function admin_log(mysqli $conn, string $action, string $details) {
    ensure_admin_logs_table($conn);
    $admin_id = $_SESSION['admin_id'] ?? 0;
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param('iss', $admin_id, $action, $details);
        $stmt->execute();
        $stmt->close();
    }
}

function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    return floor($diff / 86400) . ' hari lalu';
}

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
    'footer_admin_link_url' => '/admin/login',
    'footer_admin_link_title' => 'Login Admin',
    'footer_admin_link_show' => 1,
    'theme_id' => 'indigo',
    'theme_color_id' => 'purple',
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
        "theme_id VARCHAR(50) NOT NULL DEFAULT 'indigo'",
        "theme_color_id VARCHAR(50) NOT NULL DEFAULT 'purple'",
    ];
    foreach ($settingsCols as $def) {
        $name = explode(' ', trim($def))[0];
        $chk = $conn->query("SHOW COLUMNS FROM settings LIKE '$name'");
        if ($chk && $chk->num_rows === 0) {
            $conn->query("ALTER TABLE settings ADD COLUMN $def");
        }
    }
}

function site_theme_styles(): array
{
    static $styles = null;
    if ($styles !== null) return $styles;
    $styles = [
        'indigo' => [
            'label' => 'Indigo Klasik', 'desc' => 'Sentral ungu-biru klasik, bersih dan fokus.',
            'nav1' => '#7e22ce', 'nav2' => '#9333ea', 'nav3' => '#2563eb',
            'p1' => '#9333ea', 'p2' => '#2563eb',
            'soft1' => '#f3e8ff', 'soft2' => '#dbeafe',
            'tint1' => '#faf5ff', 'tint2' => '#eff6ff', 'foot' => '#581c87',
        ],
        'ocean' => [
            'label' => 'Samudra Dingin', 'desc' => 'Biru laut dengan sentuhan nila, cerdas dan profesional.',
            'nav1' => '#0369a1', 'nav2' => '#0ea5e9', 'nav3' => '#6366f1',
            'p1' => '#0ea5e9', 'p2' => '#4f46e5',
            'soft1' => '#e0f2fe', 'soft2' => '#e0e7ff',
            'tint1' => '#f0f9ff', 'tint2' => '#eef2ff', 'foot' => '#1e3a8a',
        ],
        'emerald' => [
            'label' => 'Zamrud Sejuk', 'desc' => 'Hijau zamrud yang menenangkan, alami dan segar.',
            'nav1' => '#065f46', 'nav2' => '#10b981', 'nav3' => '#0d9488',
            'p1' => '#10b981', 'p2' => '#0f766e',
            'soft1' => '#d1fae5', 'soft2' => '#ccfbf1',
            'tint1' => '#ecfdf5', 'tint2' => '#f0fdfa', 'foot' => '#064e3b',
        ],
        'royal' => [
            'label' => 'Royal Keemasan', 'desc' => 'Violet kerajaan berpadu emas, mewah dan anggun.',
            'nav1' => '#3b0764', 'nav2' => '#7c3aed', 'nav3' => '#b45309',
            'p1' => '#7c3aed', 'p2' => '#d97706',
            'soft1' => '#f3e8ff', 'soft2' => '#fef3c7',
            'tint1' => '#faf5ff', 'tint2' => '#fffbeb', 'foot' => '#4c1d95',
        ],
        'sunset' => [
            'label' => 'Senja Hangat', 'desc' => 'Oranye ke merah jambu hangat, berenergi dan dinamis.',
            'nav1' => '#9a3412', 'nav2' => '#f97316', 'nav3' => '#e11d48',
            'p1' => '#f97316', 'p2' => '#e11d48',
            'soft1' => '#ffedd5', 'soft2' => '#ffe4e6',
            'tint1' => '#fff7ed', 'tint2' => '#fff1f2', 'foot' => '#9f1239',
        ],
        'rose' => [
            'label' => 'Rosa Memesona', 'desc' => 'Pink lembut ke violet, elegan dan feminin.',
            'nav1' => '#9d174d', 'nav2' => '#ec4899', 'nav3' => '#8b5cf6',
            'p1' => '#ec4899', 'p2' => '#8b5cf6',
            'soft1' => '#fce7f3', 'soft2' => '#ede9fe',
            'tint1' => '#fdf2f8', 'tint2' => '#f5f3ff', 'foot' => '#831843',
        ],
        'midnight' => [
            'label' => 'Midnight Kristal', 'desc' => 'Header gelap pekat dengan aksen kristal, dramatis dan premium.',
            'nav1' => '#0f172a', 'nav2' => '#1e293b', 'nav3' => '#312e81',
            'p1' => '#4f46e5', 'p2' => '#0ea5e9',
            'soft1' => '#e0e7ff', 'soft2' => '#e0f2fe',
            'tint1' => '#eef2ff', 'tint2' => '#f0f9ff', 'foot' => '#0f172a',
        ],
        'zen' => [
            'label' => 'Zen Abu Elegan', 'desc' => 'Abu-abu premium minimalis, tenang dan modern.',
            'nav1' => '#1e293b', 'nav2' => '#334155', 'nav3' => '#475569',
            'p1' => '#334155', 'p2' => '#0ea5e9',
            'soft1' => '#f1f5f9', 'soft2' => '#e0f2fe',
            'tint1' => '#f8fafc', 'tint2' => '#f0f9ff', 'foot' => '#0f172a',
        ],
    ];
    return $styles;
}

function site_theme_accents(): array
{
    static $accents = null;
    if ($accents !== null) return $accents;
    $accents = [
        'purple'  => ['label' => 'Ungu',   'main' => '#9333ea', 'deep' => '#7e22ce', 'soft' => '#c084fc'],
        'violet'  => ['label' => 'Violet', 'main' => '#7c3aed', 'deep' => '#6d28d9', 'soft' => '#c4b5fd'],
        'sky'     => ['label' => 'Biru',   'main' => '#0284c7', 'deep' => '#0369a1', 'soft' => '#7dd3fc'],
        'teal'    => ['label' => 'Teal',   'main' => '#0f9488', 'deep' => '#0f766e', 'soft' => '#5eead4'],
        'emerald' => ['label' => 'Zamrud', 'main' => '#10b981', 'deep' => '#059669', 'soft' => '#6ee7b7'],
        'amber'   => ['label' => 'Amber',  'main' => '#d97706', 'deep' => '#b45309', 'soft' => '#fcd34d'],
        'rose'    => ['label' => 'Mawar',  'main' => '#e11d48', 'deep' => '#be123c', 'soft' => '#fda4af'],
        'slate'   => ['label' => 'Baja',   'main' => '#475569', 'deep' => '#334155', 'soft' => '#94a3b8'],
    ];
    return $accents;
}

function site_theme_css(array $settings): string
{
    $styles = site_theme_styles();
    $accents = site_theme_accents();
    $st = $styles[ $settings['theme_id'] ] ?? $styles['indigo'];
    $ac = $accents[ $settings['theme_color_id'] ] ?? $accents['purple'];

    $v = "    --pb-nav1:{$st['nav1']}; --pb-nav2:{$st['nav2']}; --pb-nav3:{$st['nav3']};\n"
       . "    --pb-nav-brd:color-mix(in srgb,var(--pb-nav1) 22%,transparent);\n"
       . "    --pb-p1:{$st['p1']}; --pb-p2:{$st['p2']};\n"
       . "    --pb-soft1:{$st['soft1']}; --pb-soft2:{$st['soft2']};\n"
       . "    --pb-tint1:{$st['tint1']}; --pb-tint2:{$st['tint2']};\n"
       . "    --pb-foot:{$st['foot']};\n"
       . "    --pb-accent:{$ac['main']}; --pb-accent-deep:{$ac['deep']}; --pb-accent-soft:{$ac['soft']};\n"
       . "    --pb-hover:color-mix(in srgb,var(--pb-accent) 7%,white);\n"
       . "    --pb-hover2:color-mix(in srgb,var(--pb-accent) 12%,white);\n"
       . "    --pb-border:color-mix(in srgb,var(--pb-accent) 22%,white);\n"
       . "    --pb-border-soft:color-mix(in srgb,var(--pb-accent) 12%,white);\n"
       . "    --pb-border-strong:color-mix(in srgb,var(--pb-accent) 42%,white);\n"
       . "    --pb-border-blue:color-mix(in srgb,var(--pb-accent) 18%,white);\n"
       . "    --pb-focus:color-mix(in srgb,var(--pb-accent) 16%,white);\n"
       . "    --pb-shadow:color-mix(in srgb,var(--pb-accent) 25%,white);\n";
    return ":root{\n$v}\n";
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
        'cta' => 'CTA',
        'teks' => 'Teks',
        'kategori' => 'Grid Kategori',
    ];
}

function section_tipe_sidebar(): array
{
    return [
        'berita_terbaru' => 'Berita Terbaru',
        'populer' => 'Paling Dibaca',
        'search' => 'Pencarian',
        'kategori_list' => 'Daftar Kategori',
        'iklan' => 'Iklan / Banner',
        'newsletter' => 'Newsletter',
        'sosmed' => 'Ikuti Kami',
        'teks' => 'Teks',
        'html' => 'Custom HTML',
        'image' => 'Image',
        'video' => 'Video',
    ];
}

function section_tipe_footer(): array
{
    return [
        'brand' => 'Brand',
        'links' => 'Tautan Cepat',
        'contact' => 'Info Kontak',
        'newsletter' => 'Newsletter',
        'sosmed' => 'Sosial Media',
        'teks' => 'Teks Bebas',
        'image' => 'Image',
        'html' => 'Custom HTML',
        'cta' => 'CTA',
        'bottom' => 'Copyright Bawah',
    ];
}

function section_widget_order(string $area): array
{
    if ($area === 'footer') {
        return ['brand', 'links', 'contact', 'newsletter', 'sosmed', 'image', 'cta', 'teks', 'html', 'bottom'];
    }
    if ($area === 'sidebar') {
        return ['search', 'berita_terbaru', 'populer', 'kategori_list', 'iklan', 'newsletter', 'sosmed', 'teks', 'html', 'image', 'video'];
    }
    return ['hero', 'carousel', 'carousel_berita', 'kategori_berita', 'countdown', 'image', 'video', 'audio', 'html', 'latest', 'cta', 'populer', 'teks', 'kategori'];
}

function ui_icon(string $name, string $cls = 'w-5 h-5'): string
{
    $b = [
        'eye' => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
        'eye-off' => '<path d="M3 3l18 18M10 5.2A9.8 9.8 0 0112 5c6.5 0 10 7 10 7a17 17 0 01-3.2 3.9M6.6 6.6A16.6 16.6 0 002 12s3.5 7 10 7c1.4 0 2.7-.3 3.8-.8"/><path d="M9.9 9.9a3 3 0 004.2 4.2"/>',
        'edit' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4z"/>',
        'trash' => '<path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6M10 11v6M14 11v6"/>',
        'ban' => '<circle cx="12" cy="12" r="9"/><path d="M5.5 5.5l13 13"/>',
        'check' => '<path d="M4 12.5l5 5L20 6.5"/>',
        'copy' => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>',
        'grip' => '<circle cx="9" cy="6" r="1.2" fill="currentColor" stroke="none"/><circle cx="15" cy="6" r="1.2" fill="currentColor" stroke="none"/><circle cx="9" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="15" cy="12" r="1.2" fill="currentColor" stroke="none"/><circle cx="9" cy="18" r="1.2" fill="currentColor" stroke="none"/><circle cx="15" cy="18" r="1.2" fill="currentColor" stroke="none"/>',
        'chev-up' => '<path d="M6 15l6-6 6 6"/>',
        'chev-down' => '<path d="M6 9l6 6 6-6"/>',
        'chev-left' => '<path d="M15 6l-6 6 6 6"/>',
        'chev-right' => '<path d="M9 6l6 6-6 6"/>',
        'home' => '<path d="M3 11.5L12 4l9 7.5M5 10v10h5v-6h4v6h5V10"/>',
        'panel' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M15 4v16"/>',
        'news' => '<path d="M5 4h11a3 3 0 013 3v13H7a2 2 0 01-2-2z"/><path d="M8 8h7M8 12h7M8 16h4"/>',
        'layers' => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
        'sliders' => '<path d="M4 8h10M18 8h2M4 16h4M12 16h8"/><circle cx="16" cy="8" r="2"/><circle cx="10" cy="16" r="2"/>',
        'tool' => '<path d="M14.5 6.5a4 4 0 00-5.6 5L3 17.4V21h3.6l5.9-5.9a4 4 0 005-5.6l-3 3-2.1-2.1z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'image' => '<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M4 17l5-4 4 3 3-2 4 3"/>',
        'link' => '<path d="M10 14a4 4 0 006 0l3-3a4 4 0 00-6-6l-1.5 1.5M14 10a4 4 0 00-6 0l-3 3a4 4 0 006 6L12.5 17.5"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11a7 7 0 0114 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 7l8 6 8-6"/>',
        'phone' => '<path d="M5 4h4l2 5-2.5 1.5a12 12 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/>',
        'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
        'mega' => '<path d="M4 5h16v11H4z"/><path d="M4 19h16M8 8h8M8 11h5"/>',
        'cal' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-8M21 20H3"/>',
        'globe' => '<circle cx="12" cy="12" r="8"/><path d="M4 12h16M12 4c3 3.5 3 12.5 0 16-3-3.5-3-12.5 0-16z"/>',
        'device' => '<rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M3 20a6 6 0 0112 0M16 5a3.5 3.5 0 010 7M21 20a6 6 0 00-4-5.6"/>',
        'fire' => '<path d="M12 3c1 3-2 4-2 7a4 4 0 008 0c0-1-.5-2-1-2.5-.5 1-1.5 1.5-1.5 1.5.5-2.5-.5-5-3.5-6z"/><path d="M9 21h6"/>',
        'info' => '<circle cx="12" cy="12" r="8"/><path d="M12 11v5M12 8v.1"/>',
        'save' => '<path d="M5 4h11l3 3v13H5z"/><path d="M8 4v5h7V4M8 21v-7h8v7"/>',
        'horn' => '<path d="M4 10v4h3l5 4V6l-5 4z"/><path d="M16 9a4 4 0 010 6M18.5 6.5a8 8 0 010 11"/>',
        'trophy' => '<path d="M7 4h10v5a5 5 0 01-10 0z"/><path d="M7 5H4a3 3 0 003 5M17 5h3a3 3 0 01-3 5M12 14v4M8 21h8"/>',
        'ball' => '<circle cx="12" cy="12" r="8"/><path d="M12 7l3 2-1 3.5h-4L9 9zM12 4v3M5 10l3 1M19 10l-3 1M7 17l2-2M17 17l-2-2"/>',
        'cal' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'user' => '<circle cx="12" cy="8" r="3.5"/><path d="M4 21a8 8 0 0116 0"/>',
        'key' => '<path d="M6 10v8h8v-3M10 13a3 3 0 100-6 3 3 0 000 6z"/>',
        'exit' => '<path d="M14 6l-8 6 8 6M8 12h10M6 4h4"/>',
        'refresh' => '<path d="M21 12a9 9 0 11-3-6.7M21 4v5h-5"/>',
        'file-text' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/>',
        'folder' => '<path d="M3 6h6l2 3h10v10H3z"/>',
        'database' => '<ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M3 6v12c0 1.7 4 3 9 3s9-1.3 9-3V6M3 12c0 1.7 4 3 9 3s9-1.3 9-3"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/>',
        'settings' => '<path d="M12 9a3 3 0 100 6 3 3 0 000-6M19 12h3M2 12h3M12 4V2M12 20v-2M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
        'bell' => '<path d="M6 8a6 6 0 0112 0c0 4-2 5-3 6H9c-1-1-3-2-3-6M9 17a3 3 0 006 0"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-8M21 20H3"/>',
    ];
    $body = $b[$name] ?? $b['info'];
    return '<svg class="' . htmlspecialchars($cls) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
}

function section_widget_icon(string $tipe): string
{
    $p = function ($body) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $body . '</svg>';
    };
    switch ($tipe) {
        case 'hero': return $p('<rect x="3" y="4" width="18" height="10" rx="2"/><path d="M3 18h18M7 8h6M7 11h10"/>');
        case 'carousel': return $p('<rect x="3" y="5" width="18" height="12" rx="2"/><path d="M10 9l2 2-2 2M14 9l-2 2 2 2"/>');
        case 'carousel_berita': return $p('<rect x="3" y="5" width="18" height="12" rx="2"/><path d="M7 9h7M7 12h10M7 15h5"/>');
        case 'kategori_berita':
        case 'kategori':
        case 'kategori_list': return $p('<path d="M4 5h7l2 2h7v10H4z"/><path d="M8 13h8M8 16h5"/>');
        case 'latest':
        case 'berita_terbaru': return $p('<path d="M5 4h11a3 3 0 013 3v13H7a2 2 0 01-2-2z"/><path d="M8 8h7M8 12h7M8 16h4"/>');
        case 'populer': return $p('<path d="M12 3c1 3-2 4-2 7a4 4 0 008 0c0-1-.5-2-1-2.5-.5 1-1.5 1.5-1.5 1.5.5-2.5-.5-5-3.5-6z"/><path d="M9 21h6"/>');
        case 'countdown': return $p('<circle cx="12" cy="13" r="7"/><path d="M12 10v3l2 2M9 3h6"/>');
        case 'image': return $p('<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M4 17l5-4 4 3 3-2 4 3"/>');
        case 'video': return $p('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M10 9l5 3-5 3z"/>');
        case 'audio': return $p('<path d="M9 18V6l10-2v11"/><circle cx="7" cy="18" r="2.5"/><circle cx="17" cy="15" r="2.5"/>');
        case 'html': return $p('<path d="M8 6l-5 6 5 6M16 6l5 6-5 6"/>');
        case 'cta': return $p('<path d="M4 12h12M12 6l6 6-6 6"/><path d="M20 4v16"/>');
        case 'teks': return $p('<path d="M5 5h14M12 5v14M9 19h6"/>');
        case 'search': return $p('<circle cx="11" cy="11" r="6"/><path d="M16 16l4 4"/>');
        case 'iklan': return $p('<path d="M4 5h16v11H4z"/><path d="M4 19h16M8 8h8M8 11h5"/>');
        case 'sosmed': return $p('<circle cx="12" cy="12" r="8"/><path d="M9 12a3 3 0 106 0 3 3 0 00-6 0zM12 4v2M12 18v2M4 12h2M18 12h2"/>');
        case 'brand': return $p('<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>');
        case 'links': return $p('<path d="M10 14a4 4 0 006 0l3-3a4 4 0 00-6-6l-1.5 1.5M14 10a4 4 0 00-6 0l-3 3a4 4 0 006 6L12.5 17.5"/>');
        case 'contact': return $p('<path d="M5 5h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/><path d="M4 7l8 6 8-6"/>');
        case 'newsletter': return $p('<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 7l8 6 8-6M4 17h4M18 17h2"/>');
        case 'bottom': return $p('<circle cx="12" cy="12" r="8"/><path d="M15 9.5A3.5 3.5 0 109 15c1 1 2.5 1.5 4.5 1.5H16"/>');
        default: return $p('<rect x="4" y="4" width="16" height="16" rx="3"/><path d="M9 9h6v6H9z"/>');
    }
}

function section_widget_meta(): array
{
    return [
        'hero' => ['label' => 'Hero'],
        'carousel' => ['label' => 'Carousel'],
        'carousel_berita' => ['label' => 'Carousel Berita'],
        'kategori_berita' => ['label' => 'Kategori Berita'],
        'latest' => ['label' => 'Berita'],
        'populer' => ['label' => 'Paling Dibaca'],
        'countdown' => ['label' => 'Countdown Timer'],
        'image' => ['label' => 'Image'],
        'video' => ['label' => 'Video'],
        'audio' => ['label' => 'Audio'],
        'html' => ['label' => 'Custom HTML'],
        'cta' => ['label' => 'CTA'],
        'teks' => ['label' => 'Teks'],
        'kategori' => ['label' => 'Grid Kategori'],
        'berita_terbaru' => ['label' => 'Berita Terbaru'],
        'search' => ['label' => 'Pencarian'],
        'kategori_list' => ['label' => 'Daftar Kategori'],
        'iklan' => ['label' => 'Iklan / Banner'],
        'sosmed' => ['label' => 'Ikuti Kami'],
        'brand' => ['label' => 'Brand'],
        'links' => ['label' => 'Tautan Cepat'],
        'contact' => ['label' => 'Info Kontak'],
        'newsletter' => ['label' => 'Newsletter'],
        'bottom' => ['label' => 'Copyright Bawah'],
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
        case 'countdown': return ['target_tanggal' => '', 'isi' => ''];
        case 'image': return ['gambar' => '', 'alt' => '', 'link' => '', 'gaya_gambar' => 'statis'];
        case 'video': return ['video_url' => '', 'poster' => ''];
        case 'audio': return ['audio_url' => ''];
        case 'cta': return ['deskripsi' => '', 'cta_teks' => 'Lihat Selengkapnya', 'cta_link' => 'semua-kategori'];
        case 'berita_terbaru': return ['jumlah' => 5, 'kategori_id' => 0, 'tampil_gambar' => 1];
        case 'search': return ['placeholder' => 'Cari berita...'];
        case 'kategori_list': return ['jumlah' => 10, 'tampil_jumlah' => 1];
        case 'iklan': return ['gambar' => '', 'link' => '', 'alt' => '', 'kode' => ''];
        case 'sosmed': return ['facebook' => '', 'twitter' => '', 'instagram' => '', 'youtube' => ''];
        case 'newsletter': return ['judul' => 'Kabar Terbaru', 'deskripsi' => 'Dapatkan info terkini lewat email.'];
        case 'brand': return ['logo' => '', 'deskripsi' => ''];
        case 'links': return ['tautan' => ''];
        case 'bottom': return ['isi' => 'Semua hak dilindungi'];
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
        area ENUM('home','footer','sidebar') NOT NULL DEFAULT 'home',
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
    migrate_sections_area_enum($conn);
}

function migrate_sections_area_enum(mysqli $conn): void
{
    try {
        $res = $conn->query("SHOW COLUMNS FROM sections LIKE 'area'");
        $row = $res ? $res->fetch_assoc() : null;
        $type = strtolower((string)($row['Type'] ?? ''));
        if ($row && strpos($type, "'sidebar'") === false) {
            $conn->query("ALTER TABLE sections MODIFY COLUMN area ENUM('home','footer','sidebar') NOT NULL DEFAULT 'home'");
        }
    } catch (Throwable $e) {}
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
    $allow = ['home', 'footer', 'sidebar'];
    if (!in_array($area, $allow, true)) $area = 'home';
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
        ['sidebar', 'search', 'Cari Berita', '{"placeholder":"Cari berita..."}', 'fade-up', 1],
        ['sidebar', 'berita_terbaru', 'Berita Terbaru', '{"jumlah":5,"kategori_id":0,"tampil_gambar":1}', 'fade-up', 2],
        ['sidebar', 'populer', 'Paling Dibaca', '{"jumlah":5,"kolom":4,"style_grid":"overlay"}', 'fade-up', 3],
        ['sidebar', 'kategori_list', 'Kategori', '{"jumlah":10,"tampil_jumlah":1}', 'fade-up', 4],
        ['sidebar', 'iklan', 'Iklan', '{"gambar":"","link":"","alt":"","kode":""}', 'fade', 5],
    ];
    $stmt = $conn->prepare("INSERT INTO sections (area, tipe, judul, pengaturan, animasi, urutan, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    foreach ($defaults as $d) {
        $stmt->bind_param('sssssi', $d[0], $d[1], $d[2], $d[3], $d[4], $d[5]);
        $stmt->execute();
    }
    $stmt->close();
    seed_sidebar_defaults($conn);
}

function seed_sidebar_defaults(mysqli $conn): void
{
    ensure_sections_table($conn);
    try {
        $cek = $conn->query("SELECT COUNT(*) AS jml FROM sections WHERE area = 'sidebar'");
        $jml = $cek ? (int)($cek->fetch_assoc()['jml'] ?? 0) : 0;
        if ($jml > 0) return;
        $mx = $conn->query("SELECT COALESCE(MAX(urutan),0)+1 AS nxt FROM sections WHERE area = 'sidebar'");
        $nxt = $mx ? (int)($mx->fetch_assoc()['nxt'] ?? 1) : 1;
        $defaults = [
            ['search', 'Cari Berita', '{"placeholder":"Cari berita..."}', 'fade-up'],
            ['berita_terbaru', 'Berita Terbaru', '{"jumlah":5,"kategori_id":0,"tampil_gambar":1}', 'fade-up'],
            ['populer', 'Paling Dibaca', '{"jumlah":5}', 'fade-up'],
            ['kategori_list', 'Kategori', '{"jumlah":10,"tampil_jumlah":1}', 'fade-up'],
            ['iklan', 'Iklan', '{"gambar":"","link":"","alt":"","kode":""}', 'fade'],
        ];
        $stmt = $conn->prepare("INSERT INTO sections (area, tipe, judul, pengaturan, animasi, urutan, is_active) VALUES ('sidebar', ?, ?, ?, ?, ?, 1)");
        foreach ($defaults as $d) {
            $u = $nxt++;
            $stmt->bind_param('ssssi', $d[0], $d[1], $d[2], $d[3], $u);
            $stmt->execute();
        }
        $stmt->close();
    } catch (Throwable $e) {}
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
seed_sidebar_defaults($conn);
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
    $settings['theme_id'] = (string)($row['theme_id'] ?? $settings['theme_id']);
    $settings['theme_color_id'] = (string)($row['theme_color_id'] ?? $settings['theme_color_id']);
}
