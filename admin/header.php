<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$currentAdminPage = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Map admin pages to their menu names
$adminMenuNames = [
    'dashboard.php' => 'Dashboard',
    'sections.php' => 'Sections',
    'menu.php' => 'Menu Navigasi',
    'kategori.php' => 'Kategori',
    'berita.php' => 'Berita',
    'komentar.php' => 'Komentar',
    'pengguna.php' => 'Pengguna',
    'newsletter.php' => 'Newsletter',
    'data_pengunjung.php' => 'Data Pengunjung',
    'backup.php' => 'Backup & Restore',
    'settings.php' => 'Pengaturan'
];

$menuName = $adminMenuNames[$currentAdminPage] ?? 'Admin';
$siteName = $settings['site_name'] ?? 'Portal Berita';
$pageTitle = $menuName . ' - ' . $siteName;

// Jumlah komentar baru (pending) untuk badge notifikasi menu
$pendingKomentarCount = 0;
$rkPending = $conn->query("SELECT COUNT(*) AS jml FROM komentar WHERE status = 'pending'");
if ($rkPending && ($rowPending = $rkPending->fetch_assoc())) {
    $pendingKomentarCount = (int)($rowPending['jml'] ?? 0);
}

// Function to format date in Indonesian
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
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php if (!empty($settings['favicon_path'])): ?>
    <link rel="icon" href="../<?php echo htmlspecialchars($settings['favicon_path']); ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --admin-bg: #eef7f6;
            --admin-panel: #ffffff;
            --admin-ink: #163034;
            --admin-muted: #64748b;
            --admin-line: #d7e7e6;
            --admin-primary: #0f9f94;
            --admin-primary-dark: #0b8077;
            --admin-accent: #ff6b4a;
            --admin-sidebar: #f7fffd;
            --admin-sidebar-soft: #e8f8f5;
            --admin-nav-gradient: linear-gradient(135deg, #0f9f94 0%, #12b981 52%, #0b8077 100%);
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: "Plus Jakarta Sans", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at top right, rgba(20, 184, 166, 0.18), transparent 26rem),
                radial-gradient(circle at top left, rgba(255, 107, 74, 0.12), transparent 28rem),
                linear-gradient(180deg, #fbfefd 0%, var(--admin-bg) 100%);
            color: var(--admin-ink);
            letter-spacing: 0;
        }
        a {
            color: inherit;
            text-decoration: none;
        }
        .navbar-admin {
            position: sticky;
            top: 0;
            z-index: 30;
            background: var(--admin-nav-gradient);
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.14);
            box-shadow: 0 14px 40px rgba(15, 159, 148, 0.18);
            backdrop-filter: blur(16px);
        }
        .container-fluid {
            width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        .navbar .container-fluid,
        .navbar-expand {
            display: flex;
            align-items: center;
        }
        .navbar-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.95rem 0;
            font-weight: 800;
            color: #fff;
        }
        .navbar-brand-logo {
            display: inline-flex;
            width: 2.4rem;
            height: 2.4rem;
            align-items: center;
            justify-content: center;
            color: #fff;
            flex: 0 0 auto;
            border-radius: 0.5rem;
            background: radial-gradient(circle at center, rgba(255,255,255,0.6) 0%, rgba(255,255,255,0.2) 45%, rgba(255,255,255,0) 72%);
            filter: drop-shadow(0 0 6px rgba(255,255,255,0.95)) drop-shadow(0 0 20px rgba(255,255,255,0.85));
        }
        .navbar-brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 0.15rem;
        }
        .navbar-brand-logo svg {
            width: 1.3rem;
            height: 1.3rem;
            stroke-width: 2.1;
            filter: drop-shadow(0 0 5px rgba(255,255,255,0.9));
        }
        .ms-auto {
            margin-left: auto;
        }
        .d-flex {
            display: flex;
        }
        .align-items-center {
            align-items: center;
        }
        .justify-content-between {
            justify-content: space-between;
        }
        .justify-content-end {
            justify-content: flex-end;
        }
        .text-light {
            color: #f8fafc;
        }
        .text-muted {
            color: var(--admin-muted);
        }
        .text-success {
            color: #047857;
        }
        .text-secondary {
            color: #475569;
        }
        .text-end {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .small {
            font-size: 0.875rem;
        }
        .h4 {
            font-size: clamp(1.55rem, 2vw, 2rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
        }
        .h6 {
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: -0.01em;
        }
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-3 { margin-bottom: 1rem; }
        .mb-4 { margin-bottom: 1.5rem; }
        .mt-2 { margin-top: 0.5rem; }
        .me-1 { margin-right: 0.25rem; }
        .me-2 { margin-right: 0.5rem; }
        .me-3 { margin-right: 1rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
        .py-4 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
        .px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
        .px-md-4 { padding-left: 1rem; padding-right: 1rem; }
        .p-3 { padding: 1rem; }
        .p-4 { padding: 1.5rem; }
        .row {
            display: grid;
            gap: 1.25rem;
        }
        .g-3, .g-md-4 {
            gap: 1.25rem;
        }
        .d-none {
            display: none;
        }
        @media (min-width: 768px) {
            .row {
                grid-template-columns: repeat(12, minmax(0, 1fr));
            }
            .col-md-2 { grid-column: span 2 / span 2; }
            .col-md-4 { grid-column: span 4 / span 4; }
            .col-md-5 { grid-column: span 5 / span 5; }
            .col-md-6 { grid-column: span 6 / span 6; }
            .col-md-7 { grid-column: span 7 / span 7; }
            .col-md-8 { grid-column: span 8 / span 8; }
            .col-md-10 { grid-column: span 10 / span 10; }
            .col-12 { grid-column: 1 / -1; }
            .d-md-block { display: block; }
        }
        @media (min-width: 1024px) {
            .col-lg-2 { grid-column: span 2 / span 2; }
            .col-lg-10 { grid-column: span 10 / span 10; }
        }
        .admin-shell {
            padding-left: 0;
            padding-right: 0;
        }
        .admin-layout {
            display: grid;
            gap: 0;
        }
        @media (min-width: 768px) {
            .admin-layout {
                grid-template-columns: 260px minmax(0, 1fr);
            }
        }
        .sidebar {
            position: sticky;
            top: 68px;
            height: calc(100vh - 68px);
            overflow-y: auto;
            background: var(--admin-nav-gradient);
            border-right: 1px solid rgba(255,255,255,0.16);
            padding: 1.25rem 1rem;
            box-shadow: 14px 0 40px rgba(28, 82, 85, 0.05);
        }
        .sidebar-title {
            margin: 0 0 0.75rem;
            padding: 0 0.65rem;
            color: rgba(255,255,255,0.65);
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }
        .nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            border-radius: 0.7rem;
            padding: 0.55rem 0.7rem;
            color: rgba(255,255,255,0.85);
            font-size: 0.8rem;
            font-weight: 600;
            transition: 0.2s ease;
        }
        .sidebar-logout {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.16);
        }
        .sidebar-logout .nav-link {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
            box-shadow: 0 8px 20px rgba(220,38,38,0.35);
        }
        .sidebar-logout .nav-link:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: #fff;
            transform: translateX(2px);
        }
        .sidebar-logout .nav-icon {
            background: rgba(255,255,255,0.22);
            color: #fff;
        }
        .nav-icon {
            display: inline-grid;
            width: 1.65rem;
            height: 1.65rem;
            place-items: center;
            border-radius: 0.55rem;
            background: rgba(255,255,255,0.14);
            color: #fff;
            font-size: 0.68rem;
            font-weight: 700;
        }
        .nav-icon svg {
            width: 0.95rem;
            height: 0.95rem;
            stroke-width: 2.2;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.18);
            color: #fff;
            transform: translateX(2px);
        }
        .sidebar .nav-link.active {
            box-shadow: inset 3px 0 0 #fff;
        }
        .sidebar .nav-link.active .nav-icon,
        .sidebar .nav-link:hover .nav-icon {
            background: rgba(255,255,255,0.26);
            color: #fff;
        }
        main {
            min-height: calc(100vh - 68px);
            display: flex;
            flex-direction: column;
            padding: 1.75rem 1.75rem 1.5rem 1.75rem;
        }
        .admin-footer {
            margin-top: auto;
            margin-left: -1.75rem;
            margin-right: -1.75rem;
            margin-bottom: -1.5rem;
            padding: 0.75rem 1.75rem;
            border-top: 1px solid var(--admin-line);
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
        }
.card {
    border-radius: 1rem;
    border: 1px solid rgba(226, 232, 240, 0.9);
    background: rgba(255,255,255,0.9);
    box-shadow: 0 18px 45px rgba(28, 82, 85, 0.08);
    backdrop-filter: blur(12px);
    transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 24px 55px rgba(28, 82, 85, 0.14);
    border-color: rgba(226, 232, 240, 1);
}
.card-body {
    padding: 1.35rem;
        }
        .rounded-4 {
            border-radius: 1rem;
        }
        .rounded-circle {
            border-radius: 999px;
        }
        .shadow-sm {
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.07);
        }
        .border-0 {
            border: 0;
        }
        .bg-primary-subtle { background: #dbeafe; }
        .bg-success-subtle { background: #dcfce7; }
        .bg-info-subtle { background: #e0f2fe; }
        .bg-secondary-subtle { background: #f1f5f9; }
        .bg-light { background: #f8fafc; }
        .text-primary { color: #2563eb; }
        .text-info { color: #0284c7; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 2.35rem;
            border-radius: 999px;
            border: 1px solid transparent;
            padding: 0.62rem 1.05rem;
            font-size: 0.875rem;
            font-weight: 800;
            transition: 0.2s ease;
            white-space: nowrap;
        }
        .btn:hover {
            transform: translateY(-1px);
        }
        .btn-sm {
            min-height: 2rem;
            padding: 0.42rem 0.8rem;
            font-size: 0.8rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #0f9f94, #14b8a6);
            color: #fff;
            box-shadow: 0 14px 28px rgba(15, 159, 148, 0.24);
        }
        .btn-primary:hover {
            filter: brightness(0.98);
            box-shadow: 0 18px 32px rgba(15, 159, 148, 0.3);
        }
        .btn-outline-light {
            border-color: rgba(255,255,255,0.45);
            color: #fff;
            background: rgba(255,255,255,0.12);
        }
        .btn-outline-light:hover {
            background: rgba(255,255,255,0.22);
        }
        .btn-outline-secondary,
        .btn-outline-primary,
        .btn-outline-danger {
            border-color: #dbe3ef;
            background: #fff;
            color: #334155;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
        }
        .btn-outline-primary:hover {
            border-color: #bfdbfe;
            color: #2563eb;
            background: #eff6ff;
        }
        .btn-outline-danger:hover {
            border-color: #fecaca;
            color: #dc2626;
            background: #fef2f2;
        }
        .form-label {
            display: block;
            margin-bottom: 0.45rem;
            font-size: 0.84rem;
            font-weight: 800;
            color: #334155;
        }
        .form-control,
        .form-select {
            width: 100%;
            border-radius: 0.85rem;
            border: 1px solid #d7dfeb;
            background: #fff;
            padding: 0.75rem 0.9rem;
            color: #0f172a;
            outline: none;
            transition: 0.2s ease;
        }
        .form-control:focus,
        .form-select:focus {
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 4px rgba(20, 184, 166, 0.14);
        }
        textarea.form-control {
            min-height: 7rem;
        }
        .form-text {
            margin-top: 0.4rem;
            font-size: 0.8125rem;
            color: var(--admin-muted);
        }
        .form-check {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .form-check-input {
            width: 1rem;
            height: 1rem;
            accent-color: var(--admin-primary);
        }
        .alert {
            border-radius: 0.9rem;
            border: 1px solid;
            padding: 0.8rem 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            font-weight: 650;
        }
        .alert-danger {
            border-color: #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }
        .alert-success {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #166534;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.9rem;
        }
        .table th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }
        .table th:first-child {
            border-top-left-radius: 0.85rem;
        }
        .table th:last-child {
            border-top-right-radius: 0.85rem;
        }
        .table th,
        .table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 0.95rem 0.8rem;
            vertical-align: middle;
        }
        .table tbody tr {
            transition: 0.18s ease;
        }
        .table tbody tr:hover {
            background: #fbfdff;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.32rem 0.65rem;
            font-size: 0.73rem;
            font-weight: 800;
        }
        .img-fluid {
            max-width: 100%;
            height: auto;
        }
        .rounded {
            border-radius: 0.65rem;
        }
        .border {
            border: 1px solid var(--admin-line);
        }
        .d-grid {
            display: grid;
        }
        .d-inline-flex {
            display: inline-flex;
        }
        .gap-3 {
            gap: 0.75rem;
        }
        .bi {
            font-style: normal;
            font-weight: 800;
        }
        .bi-speedometer2,
        .bi-person-circle,
        .bi-house-door,
        .bi-list-ul,
        .bi-newspaper,
        .bi-gear,
        .bi-broadcast-pin {
            display: none;
        }
        .btn .svg-ic, .btn-icon svg, .menu-nav svg, .menu-act svg { flex: 0 0 auto; }
        .btn-sm .svg-ic { width: 0.95rem; height: 0.95rem; }
        .btn-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.1rem;
            height: 2.1rem;
            border-radius: 0.65rem;
            border: 1px solid #dbe3ef;
            background: #fff;
            color: #475569;
            font-size: 1rem;
            line-height: 1;
            transition: 0.18s ease;
            padding: 0;
        }
        .btn-icon:hover { transform: translateY(-1px); border-color: #0f9f94; color: #0b8077; }
        .btn-icon.btn-view { color: #475569; }
        .btn-icon.btn-view:hover { border-color: #cbd5e1; background: #f8fafc; }
        .btn-icon.btn-edit { color: #2563eb; border-color: #bfdbfe; }
        .btn-icon.btn-edit:hover { background: #eff6ff; border-color: #2563eb; }
        .btn-icon.btn-del, .btn-icon.btn-danger { color: #dc2626; border-color: #fecaca; }
        .btn-icon.btn-del:hover, .btn-icon.btn-danger:hover { background: #fef2f2; border-color: #dc2626; }
        .btn-icon.btn-ok { color: #047857; border-color: #a7f3d0; }
        .btn-icon.btn-ok:hover { background: #ecfdf5; border-color: #047857; }
        .btn-icon svg { width: 1.05rem; height: 1.05rem; }
        .menu-nav svg, .menu-act svg { width: 1rem; height: 1rem; }
        .sb-iconbtn svg { width: 15px; height: 15px; }
        .sb-opt .oi svg, .sb-grid-opt svg { width: 20px; height: 20px; }
        .admin-user-menu {
            position: relative;
        }
        .admin-user-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            border: 1px solid rgba(255,255,255,0.24);
            border-radius: 999px;
            background: rgba(255,255,255,0.13);
            padding: 0.35rem 0.75rem 0.35rem 0.4rem;
            color: #fff;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
        }
        .admin-user-avatar {
            display: inline-grid;
            width: 1.9rem;
            height: 1.9rem;
            place-items: center;
            border-radius: 999px;
            background: rgba(255,255,255,0.22);
            color: #fff;
            font-size: 0.72rem;
            font-weight: 900;
        }
        .admin-user-caret {
            width: 0.85rem;
            height: 0.85rem;
            transition: 0.2s ease;
        }
        .admin-user-menu.is-open .admin-user-caret {
            transform: rotate(180deg);
        }
        .admin-user-dropdown {
            position: absolute;
            top: calc(100% + 0.65rem);
            right: 0;
            z-index: 60;
            display: none;
            min-width: 220px;
            overflow: hidden;
            border: 1px solid rgba(215, 231, 230, 0.9);
            border-radius: 0.9rem;
            background: #fff;
            box-shadow: 0 20px 55px rgba(22, 48, 52, 0.18);
        }
        .admin-user-menu.is-open .admin-user-dropdown,
        .admin-user-menu:hover .admin-user-dropdown {
            display: block;
        }
        .admin-user-menu::after {
            content: '';
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            height: 0.7rem;
        }
        .admin-user-menu:hover::after,
        .admin-user-menu.is-open::after {
            display: block;
        }
        .admin-user-menu:hover .admin-user-caret {
            transform: rotate(180deg);
        }
        .admin-user-dropdown a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            color: #314b52;
            font-size: 0.875rem;
            font-weight: 800;
        }
        .admin-user-dropdown a:hover {
            background: #eef9f7;
            color: #0b8077;
        }
        .admin-user-dropdown svg {
            width: 1.9rem;
            height: 1.9rem;
            border-radius: 0.65rem;
            background: #e8f8f5;
            padding: 0.38rem;
            color: #0b8077 !important;
            stroke: #0b8077 !important;
            fill: none !important;
            stroke-width: 2.35;
            flex: 0 0 auto;
        }
        .admin-user-dropdown svg path {
            stroke: #0b8077 !important;
        }
        .admin-user-dropdown .is-danger svg {
            background: #fef2f2;
            color: #dc2626 !important;
            stroke: #dc2626 !important;
        }
        .admin-user-dropdown .is-danger svg path {
            stroke: #dc2626 !important;
        }
        .admin-user-dropdown .is-danger:hover {
            background: #fef2f2;
            color: #dc2626;
        }
        .admin-datetime {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.75rem;
            background: rgba(255,255,255,0.14);
            border-radius: 999px;
            color: #fff;
            font-weight: 600;
            font-size: 0.8125rem;
        }
        .admin-datetime svg {
            width: 1rem;
            height: 1rem;
            color: #fff;
        }
        .admin-modal {
            position: fixed;
            inset: 0;
            z-index: 80;
            display: none;
            align-items: flex-start;
            justify-content: center;
            overflow-y: auto;
            background: rgba(22, 48, 52, 0.35);
            padding: 2rem 1rem;
            backdrop-filter: blur(8px);
        }
        .admin-modal.is-open {
            display: flex;
        }
        .admin-modal-panel {
            width: min(100%, 980px);
            border-radius: 1.25rem;
            border: 1px solid rgba(215, 231, 230, 0.9);
            background: #ffffff;
            box-shadow: 0 24px 70px rgba(22, 48, 52, 0.2);
        }
        .admin-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border-bottom: 1px solid var(--admin-line);
            padding: 1.25rem 1.5rem;
        }
        .admin-modal-body {
            padding: 1.5rem;
        }
        .admin-modal-close {
            display: inline-grid;
            width: 2.25rem;
            height: 2.25rem;
            place-items: center;
            border-radius: 999px;
            border: 1px solid var(--admin-line);
            background: #f8fcfb;
            color: #466b70;
            font-size: 1.25rem;
            font-weight: 800;
            cursor: pointer;
        }
        .cke_editor_isi {
            border-radius: 0.85rem;
            overflow: hidden;
        }
        .cke_contents {
            min-height: 260px;
        }
        .cke_contents iframe {
            color: #163034;
        }
        .cke_notification_warning {
            display: none !important;
        }
        @media (max-width: 767px) {
            .navbar .container-fluid {
                flex-wrap: wrap;
                gap: 0.75rem;
            }
            .navbar-brand {
                width: 100%;
            }
            .admin-user-menu {
                display: none;
            }
            main {
                padding: 1rem;
            }
        }
        /* Final admin theme overrides: keep navbar and sidebar visually identical. */
        .navbar-admin,
        .sidebar {
            background: #10b981 !important;
            background-image: linear-gradient(135deg, #10b981 0%, #14b8a6 52%, #0f9f94 100%) !important;
            color: #ffffff !important;
        }
        .sidebar {
            border-right-color: rgba(255,255,255,0.22) !important;
            box-shadow: 14px 0 40px rgba(15, 159, 148, 0.14) !important;
        }
        .sidebar-title,
        .sidebar .nav-link {
            color: rgba(255,255,255,0.9) !important;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.18) !important;
            color: #ffffff !important;
        }
        .nav-icon {
            background: rgba(255,255,255,0.18) !important;
            color: #ffffff !important;
        }
        .admin-loader {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(238, 247, 246, 0.55);
            backdrop-filter: blur(4px);
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }
        .admin-loader.is-show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
        }
        .admin-loader-ring {
            position: relative;
            width: 110px;
            height: 110px;
            display: grid;
            place-items: center;
        }
        .admin-loader-ring::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 5px solid rgba(15, 159, 148, 0.18);
            border-top-color: #0f9f94;
            animation: admin-loader-spin 0.9s linear infinite;
        }
        .admin-loader-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            border-radius: 16px;
            background: #fff;
            padding: 6px;
            box-shadow: 0 10px 30px rgba(22, 48, 52, 0.15);
        }
        @keyframes admin-loader-spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
<div class="admin-loader" id="adminLoader" aria-hidden="true">
    <div class="admin-loader-ring">
        <?php if (!empty($settings['logo_path'])): ?>
            <img class="admin-loader-logo" src="../<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="">
        <?php else: ?>
            <img class="admin-loader-logo" src="../assets/logo.png" alt="" onerror="this.style.display='none'">
        <?php endif; ?>
    </div>
</div>
<nav class="navbar navbar-expand navbar-dark navbar-admin">
    <div class="container-fluid">
        <a class="navbar-brand" href="dashboard">
            <span class="navbar-brand-logo" aria-hidden="true">
                <?php if (!empty($settings['logo_path'])): ?>
                    <img src="../<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="">
                <?php else: ?>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 20H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2Z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h8M8 16h5"></path>
                    </svg>
                <?php endif; ?>
            </span>
            <span>Dashboard Portal Berita</span>
        </a>
        <div class="ms-auto d-flex align-items-center">
            <span class="admin-datetime me-3">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span id="adminDateTime"><?php echo formatHariTanggalIndonesia(date('Y-m-d')) . ' ' . date('H:i') . ' WIB'; ?></span>
            </span>
            <div class="admin-user-menu" id="adminUserMenu">
                <button type="button" class="admin-user-trigger" id="adminUserTrigger" aria-expanded="false" aria-haspopup="true">
                    <span class="admin-user-avatar"><?php echo strtoupper(substr($_SESSION['admin_nama'] ?? 'A', 0, 1)); ?></span>
                    <span><?php echo htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin'); ?></span>
                    <svg class="admin-user-caret" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div class="admin-user-dropdown" role="menu">
                    <a href="settings" role="menuitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-3-6.7M21 4v5h-5"></path>
                        </svg>
                        <span>Update Sistem</span>
                    </a>
                    <a href="logout" class="is-danger" role="menuitem">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 6l-8 6 8 6M8 12h10M6 4h4"></path>
                        </svg>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>
<div class="container-fluid admin-shell">
    <div class="admin-layout">
        <aside class="d-none d-md-block sidebar">
            <p class="sidebar-title">Navigasi</p>
            <ul class="nav flex-column">
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 11.5 12 4l9 7.5M5 10v10h14V10M9 20v-6h6v6"></path>
                            </svg>
                        </span>
                        <span>Beranda</span>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'kategori.php' ? 'active' : ''; ?>" href="kategori">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M3 5.75A2.75 2.75 0 0 1 5.75 3h4.4c.73 0 1.43.29 1.94.8l8.1 8.1a2.75 2.75 0 0 1 0 3.89l-4.4 4.4a2.75 2.75 0 0 1-3.89 0l-8.1-8.1A2.75 2.75 0 0 1 3 10.15v-4.4Z"></path>
                            </svg>
                        </span>
                        <span>Kategori</span>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'berita.php' ? 'active' : ''; ?>" href="berita">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 4h11a3 3 0 0 1 3 3v13H7a2 2 0 0 1-2-2V4Z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 8h7M8 12h7M8 16h4"></path>
                            </svg>
                        </span>
                        <span>Berita</span>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'sections.php' ? 'active' : ''; ?>" href="sections">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </span>
                        <span>Sections</span>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'menu.php' ? 'active' : ''; ?>" href="menu">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h10"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m17 16 2 2 2-2"></path>
                            </svg>
                        </span>
                        <span>Menu Navigasi</span>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'komentar.php' ? 'active' : ''; ?>" href="komentar">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                            </svg>
                        </span>
                        <span>Komentar</span>
                        <?php if ($pendingKomentarCount > 0): ?>
                            <span class="ms-auto rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-black text-white"><?php echo $pendingKomentarCount > 99 ? '99+' : $pendingKomentarCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'pengguna.php' ? 'active' : ''; ?>" href="pengguna">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 8.048M12 4.354a4 4 0 110 8.048M15 19H9a6 6 0 0112 0M15 19h4a2 2 0 002-2v-6a2 2 0 00-2-2h-4M5 19H1a2 2 0 01-2-2v-6a2 2 0 012-2h4"></path>
                            </svg>
                        </span>
                        <span>Pengguna</span>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'newsletter.php' ? 'active' : ''; ?>" href="newsletter">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </span>
                        <span>Newsletter</span>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'data_pengunjung.php' ? 'active' : ''; ?>" href="data_pengunjung">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 16v-5m4 5V8m4 8v-8"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h2m2-3h2m2 1h2"></path>
                            </svg>
                        </span>
                        <span>Data Pengunjung</span>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'backup.php' ? 'active' : ''; ?>" href="backup">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 4h11l3 3v13H5z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 4v5h7V4M8 21v-7h8v7"></path>
                            </svg>
                        </span>
                        <span>Backup &amp; Restore</span>
                    </a>
                </li>
                <li class="nav-item mb-1">
                    <a class="nav-link <?php echo $currentAdminPage === 'settings.php' ? 'active' : ''; ?>" href="settings">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.3 4.4 11 3h2l.7 1.4a7.8 7.8 0 0 1 1.7.7l1.5-.5 1.4 1.4-.5 1.5c.3.5.5 1.1.7 1.7L20 10v2l-1.5.8a7.8 7.8 0 0 1-.7 1.7l.5 1.5-1.4 1.4-1.5-.5a7.8 7.8 0 0 1-1.7.7L13 19h-2l-.7-1.4a7.8 7.8 0 0 1-1.7-.7l-1.5.5-1.4-1.4.5-1.5a7.8 7.8 0 0 1-.7-1.7L4 12v-2l1.5-.8c.2-.6.4-1.2.7-1.7L5.7 6l1.4-1.4 1.5.5c.5-.3 1.1-.5 1.7-.7Z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"></path>
                            </svg>
                        </span>
                        <span>Pengaturan</span>
                    </a>
                </li>
                <li class="nav-item mb-1 sidebar-logout">
                    <a class="nav-link" href="logout" id="sidebarLogout">
                        <span class="nav-icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14 6l-8 6 8 6M8 12h10M6 4h4"></path>
                            </svg>
                        </span>
                        <span>Logout</span>
                    </a>
                </li>
            </ul>
        </aside>
        <main>
