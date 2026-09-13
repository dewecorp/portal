<?php
require_once __DIR__ . '/config.php';

$pageTitle = $settings['site_name'] ?? 'Portal Berita';
$homeSections = get_active_sections($conn, 'home');
$sidebarWidgets = get_active_sections($conn, 'sidebar');

include __DIR__ . '/header.php';
?>

<?php if (empty($homeSections)): ?>
    <div class="rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-purple-50 px-6 py-8 text-center">
        <p class="text-lg font-bold text-slate-700 mb-2">Belum ada section aktif</p>
        <p class="text-sm text-slate-600">Tambahkan section Home via Admin &gt; Sections.</p>
    </div>
<?php elseif (empty($sidebarWidgets)): ?>
    <?php foreach ($homeSections as $sec): ?>
        <?php render_home_section($conn, $sec); ?>
    <?php endforeach; ?>
<?php else: ?>
    <div class="grid gap-6 lg:grid-cols-4">
        <div class="min-w-0 lg:col-span-3">
            <?php foreach ($homeSections as $sec): ?>
                <?php render_home_section($conn, $sec); ?>
            <?php endforeach; ?>
        </div>
        <div class="min-w-0">
            <?php render_sidebar_widgets($conn, $settings, $sidebarWidgets); ?>
        </div>
    </div>
<?php endif; ?>

<?php
include __DIR__ . '/footer.php';
