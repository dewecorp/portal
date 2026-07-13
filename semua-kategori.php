<?php
require_once __DIR__ . '/config.php';

$pageTitle = 'Semua Kategori - ' . htmlspecialchars($settings['site_name'] ?? 'Portal Berita');

// Fetch all categories with news count
$categories = [];
$query = "SELECT k.id, k.nama, k.slug, k.grid_style,
                 COUNT(b.id) AS jumlah_berita
          FROM kategori k
          LEFT JOIN berita b ON b.kategori_id = k.id AND b.status = 'publish'
          GROUP BY k.id, k.nama, k.slug, k.grid_style
          ORDER BY k.nama ASC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

include __DIR__ . '/header.php';
?>

<!-- Page Header -->
<div class="mb-8">
    <div class="flex items-center gap-3 mb-2">
        <div class="h-10 w-1.5 rounded-full bg-gradient-to-b from-purple-600 to-blue-600"></div>
        <h1 class="text-3xl font-black uppercase tracking-wide text-slate-900">Semua Kategori</h1>
    </div>
    <p class="text-slate-600 ml-4">Jelajahi semua kategori berita yang tersedia</p>
</div>

<!-- Categories Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php foreach ($categories as $kat): ?>
        <a href="kategori?kategori=<?php echo (int)$kat['id']; ?>" class="group no-underline">
            <div class="h-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition-all duration-300 hover:shadow-xl hover:shadow-purple-100 hover:border-purple-300 hover:-translate-y-1">
                <!-- Category Icon -->
                <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-purple-100 to-blue-100 flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                
                <!-- Category Name -->
                <h3 class="text-xl font-bold text-slate-900 mb-2 group-hover:text-purple-600 transition-colors">
                    <?php echo htmlspecialchars($kat['nama']); ?>
                </h3>
                
                <!-- News Count -->
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>
                        <?php if ($kat['jumlah_berita'] > 0): ?>
                            <?php echo number_format($kat['jumlah_berita']); ?> Berita
                        <?php else: ?>
                            Belum Ada Berita
                        <?php endif; ?>
                    </span>
                </div>
                
                <!-- Arrow Indicator -->
                <div class="mt-4 flex items-center gap-2 text-purple-600 font-semibold text-sm opacity-0 group-hover:opacity-100 transition-opacity">
                    <span>Lihat Kategori</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                    </svg>
                </div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php if (empty($categories)): ?>
    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-6 py-16 text-center">
        <svg class="w-20 h-20 text-slate-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
        </svg>
        <p class="text-lg font-bold text-slate-700 mb-2">Belum Ada Kategori</p>
        <p class="text-sm text-slate-600">Kategori akan ditambahkan oleh administrator.</p>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/footer.php'; ?>
