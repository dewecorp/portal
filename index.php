<?php
require_once __DIR__ . '/config.php';

$pageTitle = $settings['site_name'] ?? 'Portal Berita';
$homeSections = get_active_sections($conn, 'home');

include __DIR__ . '/header.php';
?>

<?php if (empty($homeSections)): ?>
    <div class="rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-purple-50 px-6 py-8 text-center">
        <p class="text-lg font-bold text-slate-700 mb-2">Belum ada section aktif</p>
        <p class="text-sm text-slate-600">Tambahkan section Home via Admin &gt; Sections.</p>
    </div>
<?php else: ?>
    <?php foreach ($homeSections as $sec): ?>
        <?php render_home_section($conn, $sec); ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php
include __DIR__ . '/footer.php';
