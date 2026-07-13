<?php
require_once __DIR__ . '/config.php';

$kategoriId = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$kategoriSlug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

// Try to fetch category by ID or slug
if ($kategoriId > 0) {
    $stmtKategori = $conn->prepare("SELECT id, nama, slug, grid_count, grid_style FROM kategori WHERE id = ?");
    $stmtKategori->bind_param('i', $kategoriId);
} elseif (!empty($kategoriSlug)) {
    $stmtKategori = $conn->prepare("SELECT id, nama, slug, grid_count, grid_style FROM kategori WHERE slug = ?");
    $stmtKategori->bind_param('s', $kategoriSlug);
} else {
    $kategori = null;
}

if (isset($stmtKategori)) {
    $stmtKategori->execute();
    $kategoriResult = $stmtKategori->get_result();
    $kategori = $kategoriResult->fetch_assoc();
    $stmtKategori->close();
}

// Set dynamic page title
if ($kategori) {
    $pageTitle = htmlspecialchars($kategori['nama'] . ' - ' . ($settings['site_name'] ?? 'Portal Berita'));
} else {
    $pageTitle = 'Kategori Tidak Ditemukan - ' . htmlspecialchars($settings['site_name'] ?? 'Portal Berita');
}

$berita = [];

if ($kategori) {
    $gridCount = (int)($kategori['grid_count'] ?? 12);
    $stmt = $conn->prepare("SELECT b.*, k.nama AS kategori_nama
                            FROM berita b
                            LEFT JOIN kategori k ON k.id = b.kategori_id
                            WHERE b.kategori_id = ? AND b.status = 'publish'
                            ORDER BY b.tanggal_publikasi DESC, b.id DESC
                            LIMIT ?");
    $stmt->bind_param('ii', $kategoriId, $gridCount);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $berita[] = $row;
    }
    $stmt->close();
}

include __DIR__ . '/header.php';
?>

<?php if (!$kategori): ?>
    <div class="rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-8 text-center">
        <svg class="w-16 h-16 text-amber-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <p class="text-lg font-bold text-slate-700 mb-2">Kategori tidak ditemukan</p>
    </div>
<?php else: ?>
    <!-- Category Header -->
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <div class="h-10 w-1.5 rounded-full bg-gradient-to-b from-purple-600 to-blue-600"></div>
            <h1 class="text-3xl md:text-4xl font-black text-slate-900">
                <?php echo htmlspecialchars($kategori['nama']); ?>
            </h1>
        </div>
        <p class="text-slate-600 ml-4">
            Menampilkan <?php echo count($berita); ?> berita
        </p>
    </div>

    <?php if (empty($berita)): ?>
        <div class="rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-purple-50 px-6 py-12 text-center">
            <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 01-2 2v12a2 2 0 012 2h14a2 2 0 012-2z"></path>
            </svg>
            <p class="text-lg font-bold text-slate-700 mb-2">Belum ada berita</p>
            <p class="text-sm text-slate-600">Belum ada berita pada kategori ini.</p>
        </div>
    <?php else: ?>
        <?php
        $gridStyle = $kategori['grid_style'] ?? 'grid';
        $gridClass = '';
        
        if ($gridStyle === 'grid') {
            $gridClass = 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4';
        } elseif ($gridStyle === 'list') {
            $gridClass = 'flex flex-col gap-4';
        } elseif ($gridStyle === 'masonry') {
            $gridClass = 'columns-1 sm:columns-2 lg:columns-3 xl:columns-4 gap-6 space-y-6';
        }
        ?>
        
        <!-- News Grid (Style: <?php echo ucfirst($gridStyle); ?>) -->
        <div class="<?php echo $gridClass; ?>">
            <?php foreach ($berita as $item): ?>
                <?php if ($gridStyle === 'list'): ?>
                    <!-- List Style -->
                    <article class="group flex flex-col sm:flex-row gap-4 p-4 rounded-2xl bg-white border border-slate-200 card-hover shadow-sm">
                        <?php if (!empty($item['gambar'])): ?>
                            <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block w-full sm:w-48 h-48 flex-shrink-0 overflow-hidden rounded-xl image-zoom">
                                <img src="<?php echo htmlspecialchars(berita_image_url($item['gambar'])); ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>" class="h-full w-full object-cover">
                            </a>
                        <?php else: ?>
                            <div class="w-full sm:w-48 h-48 flex-shrink-0 rounded-xl bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center">
                                <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 01-2 2v12a2 2 0 012 2h14a2 2 0 012-2z"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block">
                                <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-purple-600 mb-2">
                                    <?php echo htmlspecialchars($item['kategori_nama'] ?? $kategori['nama']); ?>
                                </span>
                                <h3 class="text-xl font-bold leading-snug text-slate-900 group-hover:text-purple-700 transition mb-2">
                                    <?php echo htmlspecialchars($item['judul']); ?>
                                </h3>
                                <?php if (!empty($item['ringkasan'])): ?>
                                    <p class="text-sm text-slate-600 line-clamp-2 mb-3">
                                        <?php echo htmlspecialchars($item['ringkasan']); ?>
                                    </p>
                                <?php endif; ?>
                            </a>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span><?php echo formatTanggalIndonesia($item['tanggal_publikasi']); ?></span>
                                <?php if (!empty($item['penulis'])): ?>
                                    <span class="text-slate-300">•</span>
                                    <span><?php echo htmlspecialchars($item['penulis']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php elseif ($gridStyle === 'masonry'): ?>
                    <!-- Masonry Style -->
                    <article class="group break-inside-avoid mb-6 overflow-hidden rounded-2xl bg-white border border-slate-200 card-hover shadow-sm">
                        <?php if (!empty($item['gambar'])): ?>
                            <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block image-zoom">
                                <div class="aspect-video bg-gradient-to-br from-slate-100 to-slate-200">
                                    <img src="<?php echo htmlspecialchars(berita_image_url($item['gambar'])); ?>" class="h-full w-full object-cover" alt="<?php echo htmlspecialchars($item['judul']); ?>">
                                </div>
                            </a>
                        <?php endif; ?>
                        <div class="p-5">
                            <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block">
                                <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-purple-600 mb-2">
                                    <?php echo htmlspecialchars($item['kategori_nama'] ?? $kategori['nama']); ?>
                                </span>
                                <h3 class="text-base font-bold leading-snug text-slate-900 group-hover:text-purple-700 transition line-clamp-2 mb-2">
                                    <?php echo htmlspecialchars($item['judul']); ?>
                                </h3>
                            </a>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span><?php echo formatTanggalIndonesia($item['tanggal_publikasi']); ?></span>
                            </div>
                        </div>
                    </article>
                <?php else: ?>
                    <!-- Grid Style (Default) -->
                    <article class="group h-full overflow-hidden rounded-2xl bg-white border border-slate-200 card-hover shadow-sm">
                        <?php if (!empty($item['gambar'])): ?>
                            <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block aspect-[16/10] overflow-hidden image-zoom">
                                <img src="<?php echo htmlspecialchars(berita_image_url($item['gambar'])); ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>" class="h-full w-full object-cover">
                            </a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block aspect-[16/10] bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center">
                                <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 01-2 2v12a2 2 0 012 2h14a2 2 0 012-2z"></path>
                                </svg>
                            </a>
                        <?php endif; ?>
                        <div class="p-5">
                            <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block">
                                <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-purple-600 mb-2">
                                    <?php echo htmlspecialchars($item['kategori_nama'] ?? $kategori['nama']); ?>
                                </span>
                                <h3 class="text-base font-bold leading-snug text-slate-900 group-hover:text-purple-700 transition line-clamp-2 mb-3">
                                    <?php echo htmlspecialchars($item['judul']); ?>
                                </h3>
                                <?php if (!empty($item['ringkasan'])): ?>
                                    <p class="text-sm text-slate-600 line-clamp-2 mb-3">
                                        <?php echo htmlspecialchars($item['ringkasan']); ?>
                                    </p>
                                <?php endif; ?>
                            </a>
                            <div class="flex items-center gap-2 text-xs text-slate-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span><?php echo formatTanggalIndonesia($item['tanggal_publikasi']); ?></span>
                                <?php if (!empty($item['penulis'])): ?>
                                    <span class="text-slate-300">•</span>
                                    <span><?php echo htmlspecialchars($item['penulis']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
include __DIR__ . '/footer.php';
