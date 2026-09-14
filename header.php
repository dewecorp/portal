<?php
require_once __DIR__ . '/config.php';

// Set locale to Indonesian
setlocale(LC_TIME, 'id_ID.UTF-8', 'id_ID', 'indonesian');

// Function to format date in Indonesian
function formatTanggalIndonesia($tanggal, $denganJam = false) {
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $ts = strtotime($tanggal);
    $tgl = date('j', $ts);
    $bln = $bulan[(int)date('n', $ts)];
    $thn = date('Y', $ts);
    
    if ($denganJam) {
        $jam = date('H:i', $ts);
        return "$tgl $bln $thn, $jam WIB";
    }
    
    return "$tgl $bln $thn";
}

function formatHariTanggalIndonesia($tanggal) {
    $hari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    
    $ts = strtotime($tanggal);
    $namaHari = $hari[date('l', $ts)];
    $tgl = date('j', $ts);
    $bln = $bulan[(int)date('n', $ts)];
    $thn = date('Y', $ts);
    
    return "$namaHari, $tgl $bln $thn";
}

// Fetch menus with category information
$navMenus = [];
$navQuery = "SELECT m.id, m.nama, m.slug, m.menu_type, m.urutan, m.parent_id, m.kategori_id,
                    k.id AS kategori_id, k.nama AS kategori_nama, k.slug AS kategori_slug
             FROM menus m
             LEFT JOIN kategori k ON k.id = m.kategori_id
             WHERE m.is_active = 1 AND m.parent_id = 0
             ORDER BY m.urutan ASC, m.nama ASC";
$navResult = $conn->query($navQuery);
if ($navResult) {
    while ($row = $navResult->fetch_assoc()) {
        $navMenus[] = $row;
    }
}

// Also fetch all categories for footer links
$navKategoris = [];
$katQuery = "SELECT id, nama, slug FROM kategori ORDER BY nama ASC";
$katResult = $conn->query($katQuery);
if ($katResult) {
    while ($row = $katResult->fetch_assoc()) {
        $navKategoris[] = $row;
    }
}

// Fetch submenus for each parent menu
foreach ($navMenus as &$menu) {
    $submenuQuery = "SELECT m.id, m.nama, m.slug, m.menu_type, m.parent_id, m.kategori_id,
                            k.id AS kategori_id, k.nama AS kategori_nama, k.slug AS kategori_slug
                     FROM menus m
                     LEFT JOIN kategori k ON k.id = m.kategori_id
                     WHERE m.is_active = 1 AND m.parent_id = ?
                     ORDER BY m.urutan ASC, m.nama ASC";
    $stmt = $conn->prepare($submenuQuery);
    $stmt->bind_param('i', $menu['id']);
    $stmt->execute();
    $submenuResult = $stmt->get_result();
    $menu['submenus'] = [];
    while ($subRow = $submenuResult->fetch_assoc()) {
        // Fetch latest 5 news articles for this submenu's category
        $newsQuery = "SELECT b.id, b.judul, b.slug AS berita_slug, b.gambar, b.tanggal_publikasi
                      FROM berita b
                      WHERE b.kategori_id = ? AND b.status = 'publish'
                      ORDER BY b.tanggal_publikasi DESC
                      LIMIT 5";
        $newsStmt = $conn->prepare($newsQuery);
        $newsStmt->bind_param('i', $subRow['kategori_id']);
        $newsStmt->execute();
        $newsResult = $newsStmt->get_result();
        $subRow['berita'] = [];
        while ($newsRow = $newsResult->fetch_assoc()) {
            $subRow['berita'][] = $newsRow;
        }
        $newsStmt->close();
        
        $menu['submenus'][] = $subRow;
    }
    $stmt->close();
}


?>
<!doctype html>
<html lang="id">
<head>
    <script>try{document.documentElement.classList.add('js-anim');}catch(e){}</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle ?? ($settings['site_name'] ?? 'Portal Berita')); ?></title>
    <?php
    $metaDesc = $settings['site_tagline'] ?? 'Berita terkini dan terpercaya';
    $metaImg = '';
    if (isset($berita) && is_array($berita)) {
        if (!empty($berita['ringkasan'])) $metaDesc = mb_substr(strip_tags((string)$berita['ringkasan']), 0, 160);
        $metaImg = berita_image_url($berita['gambar'] ?? '');
    }
    $metaUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
    ?>
    <meta name="description" content="<?php echo htmlspecialchars($metaDesc); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($metaUrl); ?>">
    <meta property="og:type" content="article">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle ?? ($settings['site_name'] ?? 'Portal Berita')); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDesc); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($metaUrl); ?>">
    <?php if ($metaImg !== ''): ?><meta property="og:image" content="<?php echo htmlspecialchars($metaImg); ?>"><?php endif; ?>
    <meta name="twitter:card" content="summary_large_image">
    <link rel="alternate" type="application/rss+xml" title="RSS <?php echo htmlspecialchars($settings['site_name'] ?? 'Portal Berita'); ?>" href="feed">
    <?php if (!empty($settings['favicon_path'])): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($settings['favicon_path']); ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            900: '#0c4a6e'
                        },
                        accent: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c'
                        }
                    },
                    fontFamily: {
                        heading: ['Outfit', 'sans-serif'],
                        body: ['Plus Jakarta Sans', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style id="pb-theme-css">
        <?php echo site_theme_css($settings); ?>
        /* Theme overrides: remap bootstrap Tailwind purple/blue accents ke variabel tema */
        .from-purple-600, .from-purple-500, .from-purple-400 { --tw-gradient-from: var(--pb-p1) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent) !important; }
        .to-blue-600, .to-blue-500, .to-blue-400 { --tw-gradient-to: var(--pb-p2) !important; }
        .from-purple-700 { --tw-gradient-from: var(--pb-nav1) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent) !important; }
        .via-purple-600 { --tw-gradient-stops: var(--tw-gradient-from), var(--pb-nav2), var(--pb-nav3) !important; }
        .to-purple-900 { --tw-gradient-to: var(--pb-foot) !important; }
        .from-purple-100 { --tw-gradient-from: var(--pb-soft1) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent) !important; }
        .to-blue-100 { --tw-gradient-to: var(--pb-soft2) !important; }
        .from-purple-50 { --tw-gradient-from: var(--pb-tint1) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent) !important; }
        .to-purple-50 { --tw-gradient-to: var(--pb-tint1) !important; }
        .from-blue-50 { --tw-gradient-from: var(--pb-tint2) !important; --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, transparent) !important; }
        .to-blue-50 { --tw-gradient-to: var(--pb-tint2) !important; }
        .text-purple-600 { color: var(--pb-accent) !important; }
        .text-purple-700, .text-purple-900 { color: var(--pb-accent-deep) !important; }
        .text-purple-400, .text-blue-400 { color: var(--pb-accent-soft) !important; }
        .hover\:text-purple-600:hover { color: var(--pb-accent) !important; }
        .hover\:text-purple-700:hover { color: var(--pb-accent-deep) !important; }
        .hover\:text-purple-400:hover { color: var(--pb-accent-soft) !important; }
        .group:hover .group-hover\:text-purple-700 { color: var(--pb-accent-deep) !important; }
        .group:hover .group-hover\:text-purple-600 { color: var(--pb-accent) !important; }
        .group:hover .group-hover\:bg-purple-100 { background-color: var(--pb-hover2) !important; }
        .bg-purple-600 { background-color: var(--pb-accent) !important; }
        .hover\:bg-purple-600:hover { background-color: var(--pb-accent) !important; }
        .bg-purple-50, .hover\:bg-purple-50:hover { background-color: var(--pb-hover) !important; }
        .hover\:bg-purple-100:hover { background-color: var(--pb-hover2) !important; }
        .border-purple-100 { border-color: var(--pb-border-soft) !important; }
        .border-purple-200 { border-color: var(--pb-border) !important; }
        .border-purple-300, .hover\:border-purple-300:hover, .border-purple-400 { border-color: var(--pb-border-strong) !important; }
        .border-purple-500 { border-color: var(--pb-nav-brd) !important; }
        .border-purple-600 { border-color: var(--pb-accent) !important; }
        .border-blue-200 { border-color: var(--pb-border-blue) !important; }
        .focus\:border-purple-400:focus { border-color: var(--pb-accent) !important; }
        .ring-purple-100, .focus\:ring-purple-100:focus { --tw-ring-color: var(--pb-focus) !important; }
        .shadow-purple-100, .shadow-purple-200, .shadow-blue-200 { --tw-shadow-color: var(--pb-shadow) !important; }
    </style>
    <style>
        html,
        body {
            max-width: 100%;
            overflow-x: hidden;
            overflow-x: clip;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Outfit', sans-serif;
        }
        .gradient-bg {
            background: linear-gradient(135deg, var(--pb-p1) 0%, var(--pb-p2) 100%);
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .image-zoom {
            overflow: hidden;
        }
        .image-zoom img {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .image-zoom:hover img {
            transform: scale(1.08);
        }
        .text-gradient {
            background: linear-gradient(135deg, var(--pb-p1) 0%, var(--pb-p2) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .badge-category {
            background: linear-gradient(135deg, var(--pb-p1) 0%, var(--pb-p2) 100%);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .nav-item-main {
            position: relative;
        }
        .dropdown-panel {
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 220px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.12);
            border-radius: 1rem;
            padding: 0.5rem;
            z-index: 9999;
            display: block;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            margin-top: 0px;
            transform: translateY(10px) scale(.985);
            transform-origin: top center;
            transition: opacity .2s ease, transform .24s ease, visibility .2s ease;
        }
        .dropdown-panel.align-right {
            left: auto;
            right: 0;
        }
        .nav-item-main:hover > .dropdown-panel,
        .dropdown-panel:hover {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }
        .dropdown-panel a {
            display: block;
            border-radius: 0.75rem;
            padding: 0.55rem 0.75rem;
            font-size: 0.9rem;
            color: #334155;
            text-decoration: none;
        }
        .dropdown-panel a:hover {
            background-color: var(--pb-hover);
            color: var(--pb-accent-deep);
        }
        .mega-panel {
            position: absolute;
            top: 100%;
            left: 0;
            transform: none;
            min-width: 600px;
            max-width: min(1120px, calc(100vw - 2rem));
            width: max-content;
            box-sizing: border-box;
            background: linear-gradient(135deg, rgba(255,255,255,0.88), rgba(248,250,252,0.72));
            border: 1px solid rgba(255,255,255,0.62);
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.18), inset 0 1px 0 rgba(255,255,255,0.7);
            backdrop-filter: blur(22px) saturate(1.25);
            -webkit-backdrop-filter: blur(22px) saturate(1.25);
            border-radius: 1.35rem;
            padding: 1.5rem 2rem;
            z-index: 9999;
            display: block;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            margin-top: 0px;
            transform: translateY(10px) scale(.985);
            transform-origin: top center;
            transition: opacity .2s ease, transform .24s ease, visibility .2s ease;
        }
        .mega-panel.align-right {
            left: auto;
            right: 0;
        }
        .mega-panel.align-center {
            left: 50%;
            transform: translateX(-50%);
        }
        .mega-panel.is-positioned {
            right: auto;
        }
        .nav-item-main:hover > .mega-panel,
        .mega-panel:hover {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }
        .mega-column-title {
            font-size: 0.85rem;
            font-weight: 900;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            color: var(--pb-accent);
        }
        .mega-news-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
        }
        .mega-news-card {
            display: block;
            border-radius: 1rem;
            border: 1px solid rgba(255,255,255,0.65);
            background: rgba(255,255,255,0.54);
            padding: 0.65rem;
            text-decoration: none;
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
            transition: transform .2s ease, box-shadow .2s ease, background .2s ease, border-color .2s ease;
            position: relative;
            overflow: hidden;
        }
        .mega-news-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 0%, color-mix(in srgb, var(--pb-accent) 16%, transparent), transparent 34%), radial-gradient(circle at 85% 15%, rgba(20, 184, 166, 0.14), transparent 30%);
            opacity: 0;
            transition: opacity .22s ease;
            pointer-events: none;
        }
        .mega-news-card:hover {
            transform: translateY(-4px);
            border-color: color-mix(in srgb, var(--pb-accent) 35%, transparent);
            background: rgba(255,255,255,0.78);
            box-shadow: 0 22px 46px color-mix(in srgb, var(--pb-accent) 14%, transparent);
        }
        .mega-news-card:hover::before {
            opacity: 1;
        }
        .mega-news-thumb {
            position: relative;
            height: 7rem;
            width: 100%;
            overflow: hidden;
            border-radius: 0.8rem;
            background: rgba(226, 232, 240, 0.75);
            margin-bottom: 0.65rem;
        }
        .mega-news-thumb img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transition: transform .28s ease;
        }
        .mega-news-card:hover .mega-news-thumb img {
            transform: scale(1.08);
        }
        @media (min-width: 640px) {
            .mega-news-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .mega-news-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        }
        .mega-link {
            display: block;
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
            color: #334155;
            text-decoration: none;
        }
        .mega-link:hover {
            color: var(--pb-accent);
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .news-content p {
            margin-bottom: 1rem;
        }
        .article-title,
        .news-content,
        .news-content * {
            overflow-wrap: anywhere;
            word-break: normal;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: inherit !important;
            line-height: inherit !important;
        }
        .news-content iframe,
        .news-content video,
        .news-content embed,
        .news-content object {
            max-width: 100%;
        }
        .news-content pre,
        .news-content table {
            display: block;
            max-width: 100%;
            overflow-x: auto;
        }
        .news-content h2, .news-content h3, .news-content h4,
        .news-content h5, .news-content h6 {
            font-family: 'Outfit', sans-serif;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #0f172a;
            line-height: 1.3 !important;
        }
        .news-content h2 { font-size: 1.5rem !important; }
        .news-content h3 { font-size: 1.25rem !important; }
        .news-content h4 { font-size: 1.125rem !important; }
        .news-content h5, .news-content h6 { font-size: 1rem !important; }
        .news-content p, .news-content li, .news-content div, .news-content span, .news-content a { font-size: 1.125rem !important; line-height: 2 !important; }
        .news-content ul, .news-content ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        .news-content li {
            margin-bottom: 0.35rem;
        }
        .news-content blockquote {
            border-left: 4px solid var(--pb-accent);
            padding: 0.75rem 1rem;
            margin: 1rem 0;
            background: linear-gradient(to right, var(--pb-hover), #f8fafc);
            border-radius: 0.5rem;
            color: #475569;
            font-style: italic;
        }
        .news-content img {
            max-width: 100%;
            height: auto;
            border-radius: 0.75rem;
            margin-top: 1rem;
            margin-bottom: 1rem;
        }
        .news-content img.image-center, .news-content figure.image-center { display: block; margin-left: auto; margin-right: auto; }
        .news-content p.image-center, .news-content div.image-center { text-align: center; }
        .news-content p.image-center img, .news-content div.image-center img { display: inline-block; margin-left: auto; margin-right: auto; }
        .news-content img.image-left, .news-content figure.image-left { float: left; margin: 0 1rem 1rem 0; }
        .news-content img.image-right, .news-content figure.image-right { float: right; margin: 0 0 1rem 1rem; }
        .news-content figure.image { max-width: 100%; }
        .news-content figure.image img { margin-top: 0; margin-bottom: 0; }
        .news-content figcaption { text-align: center; font-size: 0.8rem; color: #64748b; padding: 0.4rem 0; }
        .news-content a {
            color: var(--pb-accent-deep);
            text-decoration: none !important;
            font-weight: 700;
            border-bottom: 2px solid var(--pb-accent);
            padding-bottom: 1px;
            transition: color 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
        }
        .news-content a:hover {
            color: var(--pb-accent-deep);
            border-bottom-color: var(--pb-accent-deep);
            background-color: var(--pb-hover);
            border-radius: 0.2em;
        }
        .news-content a[style*="underline"], .news-content a u {
            text-decoration: none !important;
        }
        .news-content a[target="_blank"]::after {
            content: '';
            display: inline-block;
            width: 0.7em;
            height: 0.7em;
            margin-left: 0.25em;
            background-color: currentColor;
            -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='currentColor'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25'/%3E%3C/svg%3E") no-repeat center / contain;
            mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='currentColor'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25'/%3E%3C/svg%3E") no-repeat center / contain;
            vertical-align: baseline;
        }
        .news-content table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        .news-content th, .news-content td {
            border: 1px solid #e2e8f0;
            padding: 0.5rem 0.75rem;
            text-align: left;
        }
        .news-content th {
            background: #f1f5f9;
            font-weight: 600;
        }
        .news-content figure {
            margin: 1rem 0;
        }
        .news-content figcaption {
            font-size: 0.875rem;
            color: #64748b;
            text-align: center;
            margin-top: 0.5rem;
        }
        .news-content hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 1.5rem 0;
        }
        .back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 3rem;
            height: 3rem;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--pb-p1) 0%, var(--pb-p2) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px var(--pb-shadow);
            z-index: 999;
            border: none;
        }
        .back-to-top.visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .back-to-top:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px var(--pb-shadow);
        }
        .back-to-top:active {
            transform: translateY(-2px);
        }
        /* Fix dropdown overflow on mobile */
        @media (max-width: 1023px) {
            .mega-panel {
                min-width: min(600px, calc(100vw - 2rem));
            }
            .main-nav-inner {
                overflow-x: auto !important;
            }
        }
        @media (min-width: 1024px) {
            .main-nav-inner {
                overflow-x: visible !important;
            }
        }
        /* Section animations: transisi + replay tiap masuk viewport (tanpa gate JS-class) */
        [data-animate] { transition: opacity .8s ease, transform .8s cubic-bezier(.22,.61,.36,1); }
        [data-animate]:not(.animate-in) { opacity: 0 !important; }
        [data-animate="fade-up"]:not(.animate-in) { transform: translate3d(0,70px,0); }
        [data-animate="fade-down"]:not(.animate-in) { transform: translate3d(0,-70px,0); }
        [data-animate="fade-left"]:not(.animate-in) { transform: translate3d(90px,0,0); }
        [data-animate="fade-right"]:not(.animate-in) { transform: translate3d(-90px,0,0); }
        [data-animate="fade"]:not(.animate-in) { transform: none; }
        [data-animate="zoom-in"]:not(.animate-in) { transform: scale(.9); }
        [data-animate="zoom-out"]:not(.animate-in) { transform: scale(1.12); }
        [data-animate="flip"]:not(.animate-in) { transform: perspective(1000px) rotateX(10deg) translateY(48px); }
        [data-animate="bounce"]:not(.animate-in) { transform: translate3d(0,70px,0) scale(.96); }
        [data-animate="slide"]:not(.animate-in) { transform: translate3d(90px,0,0); }
        [data-animate].animate-in { opacity: 1 !important; transform: none !important; }
        [data-animate="bounce"].animate-in { transition-timing-function: cubic-bezier(.34,1.3,.64,1); transition-duration: .95s; }
        [data-animate="flip"].animate-in { transition-duration: .9s; }
        [data-animate-delay="1"].animate-in { transition-delay: .1s; }
        [data-animate-delay="2"].animate-in { transition-delay: .2s; }
        [data-animate-delay="3"].animate-in { transition-delay: .3s; }
        [data-animate-delay="4"].animate-in { transition-delay: .4s; }
        [data-animate-delay="5"].animate-in { transition-delay: .5s; }
        @media (max-width: 640px) {
            [data-animate="fade-up"] { transform: translate3d(0,28px,0); }
            [data-animate="fade-down"] { transform: translate3d(0,-28px,0); }
            [data-animate="fade-left"] { transform: translate3d(32px,0,0); }
            [data-animate="fade-right"] { transform: translate3d(-32px,0,0); }
            [data-animate="slide"] { transform: translate3d(36px,0,0); }
        }
        /* Hero carousel: hanya matikan animasi pada gambar slide, section pembungkus tetap ikut */
        #heroCarousel .carousel-slide [data-animate], [id^="heroCarousel"] .carousel-slide [data-animate] { opacity: 1 !important; transform: none !important; }
        @media (prefers-reduced-motion: reduce) {
            /* Hormati OS tapi tetap tampilkan gerak singkat, bukan matikan total */
            [data-animate] { transition-duration: .3s !important; }
        }
        /* Footer bawah + pemisah dari body */
        .pb-footer-divider {
            height: 5px;
            background: linear-gradient(90deg, var(--pb-p1), var(--pb-p2), var(--pb-foot));
        }
        .pb-site-footer {
            margin-top: auto;
            background: linear-gradient(to bottom right, #0f172a, #1e293b, var(--pb-foot));
            padding-top: 3rem;
            padding-bottom: 3rem;
        }
        /* SectionBuilder: gaya gambar ala Elementor */
        .media-gaya-kenburns img { animation: sbKenBurns 14s ease-in-out infinite alternate; }
        .media-gaya-kenburns-balik img { animation: sbKenBurnsBalik 14s ease-in-out infinite alternate; }
        .media-gaya-zoom-lambat img { animation: sbZoomLambat 18s ease-in-out infinite alternate; }
        .media-gaya-geser-kiri img { animation: sbGeserKiri 12s ease-in-out infinite alternate; }
        .media-gaya-geser-kanan img { animation: sbGeserKanan 12s ease-in-out infinite alternate; }
        .media-gaya-fade-zoom img { animation: sbFadeZoom 10s ease-in-out infinite alternate; }
        .media-gaya-melayang { animation: sbMelayang 5s ease-in-out infinite; }
        @keyframes sbKenBurns { from { transform: scale(1) translate(0,0); } to { transform: scale(1.18) translate(-2%,2%); } }
        @keyframes sbKenBurnsBalik { from { transform: scale(1.18) translate(2%,-2%); } to { transform: scale(1) translate(0,0); } }
        @keyframes sbZoomLambat { from { transform: scale(1); } to { transform: scale(1.25); } }
        @keyframes sbGeserKiri { from { transform: scale(1.1) translateX(3%); } to { transform: scale(1.1) translateX(-3%); } }
        @keyframes sbGeserKanan { from { transform: scale(1.1) translateX(-3%); } to { transform: scale(1.1) translateX(3%); } }
        @keyframes sbFadeZoom { 0% { opacity: .7; transform: scale(1); } 50% { opacity: 1; transform: scale(1.12); } 100% { opacity: .7; transform: scale(1); } }
        @keyframes sbMelayang { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }
        @media (prefers-reduced-motion: reduce) {
            .media-gaya-kenburns img, .media-gaya-kenburns-balik img, .media-gaya-zoom-lambat img,
            .media-gaya-geser-kiri img, .media-gaya-geser-kanan img, .media-gaya-fade-zoom img, .media-gaya-melayang { animation-duration: 4s !important; }
        }
        /* SectionBuilder: carousel berita horizontal */
        .sb-hscroll { scroll-snap-type: x mandatory; -webkit-overflow-scrolling: touch; scrollbar-width: thin; }
        .sb-hscroll > * { scroll-snap-align: start; }
    </style>
    <noscript><style>[data-animate]{opacity:1 !important;transform:none !important;}</style></noscript>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-purple-50 text-slate-900 antialiased flex flex-col">
<!-- Top Bar -->
<div class="border-b border-purple-500/20 bg-gradient-to-r from-purple-700 via-purple-600 to-blue-600">
    <div class="mx-auto flex max-w-[1400px] items-center justify-between px-2 py-2.5 text-xs sm:px-3 lg:px-4">
        <div class="flex items-center gap-4">
            <span class="font-semibold text-white/95"><?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?></span>
            <span class="text-white/30">|</span>
            <span class="font-semibold text-white/95"><?php echo formatHariTanggalIndonesia(date('Y-m-d')); ?></span>
            <span class="text-white/30">|</span>
            <span class="font-semibold text-white/95" id="liveClock"><?php echo date('H:i'); ?> WIB</span>
        </div>
        <div class="hidden items-center gap-2 sm:flex">
            <?php echo sosmed_icons_html(sosmed_links([], $settings), 'w-7 h-7'); ?>
        </div>
    </div>
</div>

<!-- Main Header -->
<header class="sticky top-0 z-[60] border-b border-slate-200/50 bg-white/90 backdrop-blur-md shadow-sm">
    <div class="mx-auto flex max-w-[1400px] items-center justify-between px-2 py-4 sm:px-3 lg:px-4">
        <a href="index" class="flex items-center gap-3 no-underline group">
            <?php if (!empty($settings['logo_path'])): ?>
                <img src="<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="<?php echo htmlspecialchars($settings['site_name'] ?? 'Portal'); ?>" class="h-12 w-auto transition group-hover:scale-105">
            <?php endif; ?>
            <div>
                <span class="text-3xl font-black uppercase tracking-wide bg-gradient-to-r from-purple-600 to-blue-600 bg-clip-text text-transparent"><?php echo htmlspecialchars($settings['site_name'] ?? 'Portal Berita'); ?></span>
                <p class="text-xs text-slate-500 font-medium -mt-1">Berita Terkini & Terpercaya</p>
            </div>
        </a>
        <div class="hidden lg:flex items-center gap-3">
            <form action="cari" method="get" class="relative">
                <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" placeholder="Cari berita..." class="pl-10 pr-4 py-2 rounded-full border border-slate-200 bg-slate-50 focus:bg-white focus:border-purple-400 focus:ring-2 focus:ring-purple-100 transition w-64 text-sm">
                <button type="submit" aria-label="Cari" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-purple-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                </button>
            </form>
        </div>
    </div>
</header>

<!-- Navigation -->
<nav class="border-b border-slate-200/50 bg-white/80 backdrop-blur-sm sticky top-[73px] z-50">
    <div class="mx-auto max-w-[1400px] px-2 sm:px-3 lg:px-4">
        <div class="main-nav-inner flex gap-1 overflow-x-visible py-2">
            <a href="index" class="shrink-0 rounded-full px-5 py-2 text-sm font-bold uppercase tracking-wide text-white bg-gradient-to-r from-purple-600 to-blue-600 transition hover:shadow-lg hover:shadow-purple-200">
                Beranda
            </a>
            <?php foreach ($navMenus as $navMenu): ?>
                <?php if ($navMenu['menu_type'] === 'dropdown'): ?>
                    <!-- Dropdown Menu -->
                    <div class="nav-item-main shrink-0">
                        <a class="block rounded-full px-5 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:bg-purple-50 hover:text-purple-700"
                           href="#">
                            <?php echo htmlspecialchars($navMenu['nama']); ?>
                            <svg class="w-3 h-3 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </a>
                        <?php if (!empty($navMenu['submenus'])): ?>
                        <div class="dropdown-panel">
                            <?php foreach ($navMenu['submenus'] as $submenu): ?>
                                <a href="kategori?kategori=<?php echo (int)$submenu['kategori_id']; ?>">
                                    <?php echo htmlspecialchars($submenu['nama']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($navMenu['menu_type'] === 'mega'): ?>
                    <!-- Mega Menu -->
                    <div class="nav-item-main shrink-0">
                        <a class="block rounded-full px-5 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:bg-purple-50 hover:text-purple-700"
                           href="#">
                            <?php echo htmlspecialchars($navMenu['nama']); ?>
                            <svg class="w-3 h-3 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </a>
                        <div class="mega-panel">
                            <?php if (!empty($navMenu['submenus'])): ?>
                                <!-- Mega menu with submenus - Wide horizontal layout -->
                                <div class="grid grid-cols-4 gap-6">
                                    <?php foreach ($navMenu['submenus'] as $submenu): ?>
                                        <div class="border-r border-slate-100 last:border-r-0 pr-6 last:pr-0">
                                            <div class="mega-column-title mb-4 pb-2 border-b-2 border-purple-200">
                                                <a href="kategori?kategori=<?php echo (int)$submenu['kategori_id']; ?>" class="hover:text-purple-600 transition">
                                                    <?php echo htmlspecialchars($submenu['nama']); ?>
                                                </a>
                                            </div>
                                            <?php if (!empty($submenu['berita'])): ?>
                                                <div class="space-y-4">
                                                    <?php foreach ($submenu['berita'] as $beritaItem): ?>
                                                        <a href="<?php echo htmlspecialchars(berita_url($beritaItem)); ?>" class="group block no-underline">
                                                            <?php if (!empty($beritaItem['gambar'])): ?>
                                                                <div class="w-full h-36 rounded-lg overflow-hidden mb-2">
                                                                    <img src="<?php echo htmlspecialchars(berita_image_url($beritaItem['gambar'])); ?>" 
                                                                         alt="<?php echo htmlspecialchars($beritaItem['judul']); ?>"
                                                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="w-full h-36 rounded-lg bg-gradient-to-br from-purple-100 to-blue-100 flex items-center justify-center mb-2">
                                                                    <svg class="w-12 h-12 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                                                    </svg>
                                                                </div>
                                                            <?php endif; ?>
                                                            <p class="text-sm font-semibold text-slate-800 group-hover:text-purple-600 transition line-clamp-2 leading-snug mb-1">
                                                                <?php echo htmlspecialchars($beritaItem['judul']); ?>
                                                            </p>
                                                            <p class="text-xs text-slate-500">
                                                                <?php echo formatTanggalIndonesia($beritaItem['tanggal_publikasi']); ?>
                                                            </p>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-sm text-slate-400 italic">Belum ada berita</p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php elseif (!empty($navMenu['kategori_id'])): ?>
                                <!-- Mega menu without submenus, show news from main category -->
                                <?php
                                // Fetch news for this mega menu's category
                                $megaNewsQuery = "SELECT b.id, b.judul, b.slug, b.gambar, b.tanggal_publikasi
                                                 FROM berita b
                                                 WHERE b.kategori_id = ? AND b.status = 'publish'
                                                 ORDER BY b.tanggal_publikasi DESC
                                                 LIMIT 5";
                                $megaNewsStmt = $conn->prepare($megaNewsQuery);
                                $megaNewsStmt->bind_param('i', $navMenu['kategori_id']);
                                $megaNewsStmt->execute();
                                $megaNewsResult = $megaNewsStmt->get_result();
                                $megaNews = [];
                                while ($newsRow = $megaNewsResult->fetch_assoc()) {
                                    $megaNews[] = $newsRow;
                                }
                                $megaNewsStmt->close();
                                ?>
                                <div class="w-[1040px] max-w-[calc(94vw-4rem)]">
                                    <div class="mega-column-title mb-4 pb-2 border-b-2 border-purple-100">
                                        <a href="kategori?kategori=<?php echo (int)$navMenu['kategori_id']; ?>" class="hover:text-purple-600 transition">
                                            <?php echo htmlspecialchars($navMenu['kategori_nama'] ?? $navMenu['nama']); ?>
                                        </a>
                                    </div>
                                    <?php if (!empty($megaNews)): ?>
                                        <div class="mega-news-grid">
                                            <?php foreach ($megaNews as $megaBerita): ?>
                                                <a href="<?php echo htmlspecialchars(berita_url($megaBerita)); ?>" class="mega-news-card group">
                                                    <?php if (!empty($megaBerita['gambar'])): ?>
                                                        <div class="mega-news-thumb">
                                                            <img src="<?php echo htmlspecialchars(berita_image_url($megaBerita['gambar'])); ?>" 
                                                                 alt="<?php echo htmlspecialchars($megaBerita['judul']); ?>"
                                                                 loading="lazy">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="mega-news-thumb flex items-center justify-center bg-gradient-to-br from-purple-100/80 to-blue-100/80">
                                                            <svg class="w-12 h-12 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                                            </svg>
                                                        </div>
                                                    <?php endif; ?>
                                                    <p class="text-sm font-extrabold text-slate-800 group-hover:text-purple-600 transition line-clamp-2 leading-tight">
                                                        <?php echo htmlspecialchars($megaBerita['judul']); ?>
                                                    </p>
                                                    <p class="text-xs text-slate-500 mt-1">
                                                        <?php echo formatTanggalIndonesia($megaBerita['tanggal_publikasi']); ?>
                                                    </p>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-sm text-slate-400 italic">Belum ada berita</p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Regular Link Menu -->
                    <div class="nav-item-main shrink-0">
                        <a class="block rounded-full px-5 py-2 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:bg-purple-50 hover:text-purple-700"
                           href="kategori?kategori=<?php echo (int)$navMenu['kategori_id']; ?>">
                            <?php echo htmlspecialchars($navMenu['nama']); ?>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<!-- Mega Menu & Dropdown Dynamic Positioning Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item-main');
    
    navItems.forEach(function(item) {
        const megaPanel = item.querySelector('.mega-panel');
        const dropdownPanel = item.querySelector('.dropdown-panel');
        
        // Handle Mega Menu
        if (megaPanel) {
            item.addEventListener('mouseenter', function() {
                megaPanel.classList.remove('align-right', 'align-center', 'is-positioned');
                megaPanel.style.left = '0px';
                megaPanel.style.right = 'auto';

                const navRect = item.getBoundingClientRect();
                const panelRect = megaPanel.getBoundingClientRect();
                const panelWidth = panelRect.width;
                const viewportWidth = window.innerWidth;
                const margin = 16;

                let desiredLeft = navRect.left + (navRect.width / 2) - (panelWidth / 2);
                const maxLeft = Math.max(margin, viewportWidth - panelWidth - margin);
                desiredLeft = Math.min(Math.max(desiredLeft, margin), maxLeft);

                megaPanel.style.left = (desiredLeft - navRect.left) + 'px';
                megaPanel.classList.add('is-positioned');
            });

            item.addEventListener('mouseleave', function() {
                megaPanel.classList.remove('align-right', 'align-center', 'is-positioned');
                megaPanel.style.left = '';
                megaPanel.style.right = '';
            });
        }
        
        // Handle Dropdown Menu
        if (dropdownPanel) {
            item.addEventListener('mouseenter', function() {
                // Remove alignment class
                dropdownPanel.classList.remove('align-right');
                
                // Get positions
                const navRect = item.getBoundingClientRect();
                const panelWidth = dropdownPanel.offsetWidth;
                const viewportWidth = window.innerWidth;
                
                // Calculate space on right side
                const spaceOnRight = viewportWidth - navRect.left;
                
                // If panel would overflow, align right
                if (spaceOnRight < panelWidth) {
                    dropdownPanel.classList.add('align-right');
                }
            });
        }
    });
});
</script>

<main class="min-h-[60vh] min-w-0 flex-1 overflow-x-clip py-8">
    <div class="mx-auto min-w-0 max-w-[1400px] px-2 sm:px-3 lg:px-4">
