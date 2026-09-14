<?php
require_once __DIR__ . '/config.php';

$kategoriId = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$kategoriSlug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$kategori = null;

// Try to fetch category by ID or slug
if ($kategoriId > 0) {
    $stmtKategori = $conn->prepare("SELECT id, nama, slug, grid_count, grid_style, animasi FROM kategori WHERE id = ?");
    $stmtKategori->bind_param('i', $kategoriId);
    $stmtKategori->execute();
    $kategori = $stmtKategori->get_result()->fetch_assoc();
    $stmtKategori->close();
} elseif ($kategoriSlug !== '') {
    $stmtKategori = $conn->prepare("SELECT id, nama, slug, grid_count, grid_style, animasi FROM kategori WHERE slug = ?");
    $stmtKategori->bind_param('s', $kategoriSlug);
    $stmtKategori->execute();
    $kategori = $stmtKategori->get_result()->fetch_assoc();
    $stmtKategori->close();
}

if ($kategori) {
    $kategoriId = (int)$kategori['id'];
    $pageTitle = $kategori['nama'] . ' - ' . ($settings['site_name'] ?? 'Portal Berita');
} else {
    $pageTitle = 'Kategori Tidak Ditemukan - ' . ($settings['site_name'] ?? 'Portal Berita');
}

$berita = [];
$totalBerita = 0;
$halaman = max(1, (int)($_GET['hal'] ?? 1));
$perHal = 12;

if ($kategori) {
    $perHal = max(4, min(50, (int)($kategori['grid_count'] ?? 12)));
    $cntStmt = $conn->prepare("SELECT COUNT(*) AS jml FROM berita WHERE kategori_id = ? AND status = 'publish'");
    $cntStmt->bind_param('i', $kategoriId);
    $cntStmt->execute();
    $totalBerita = (int)($cntStmt->get_result()->fetch_assoc()['jml'] ?? 0);
    $cntStmt->close();
    $totalHal = max(1, (int)ceil($totalBerita / $perHal));
    $halaman = min($halaman, $totalHal);
    $offset = ($halaman - 1) * $perHal;
    $stmt = $conn->prepare("SELECT b.*, k.nama AS kategori_nama
                            FROM berita b
                            LEFT JOIN kategori k ON k.id = b.kategori_id
                            WHERE b.kategori_id = ? AND b.status = 'publish'
                            ORDER BY b.tanggal_publikasi DESC, b.id DESC
                            LIMIT ? OFFSET ?");
    $stmt->bind_param('iii', $kategoriId, $perHal, $offset);
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
    <div class="mb-8" data-animate="fade-up">
        <nav class="ml-4 mb-3 text-xs font-semibold text-slate-400">
            <a href="index" class="hover:text-purple-600">Beranda</a>
            <span class="mx-1">/</span>
            <span class="text-slate-600"><?php echo htmlspecialchars($kategori['nama']); ?></span>
        </nav>
        <div class="flex items-center gap-3 mb-2">
            <div class="h-10 w-1.5 rounded-full bg-gradient-to-b from-purple-600 to-blue-600"></div>
            <h1 class="text-3xl md:text-4xl font-black text-slate-900">
                <?php echo htmlspecialchars($kategori['nama']); ?>
            </h1>
        </div>
        <p class="text-slate-600 ml-4">
            Menampilkan <?php echo count($berita); ?> dari <?php echo number_format($totalBerita); ?> berita
            <?php if (($totalHal ?? 1) > 1): ?> &bull; Halaman <?php echo $halaman; ?> dari <?php echo $totalHal; ?><?php endif; ?>
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
        $gridStyle = strtolower(trim((string)($kategori['grid_style'] ?? 'grid')));
        if (!in_array($gridStyle, ['grid', 'list', 'masonry', 'overlay', 'magazine'], true)) $gridStyle = 'grid';
        $animasi = strtolower(trim((string)($kategori['animasi'] ?? 'fade-up')));
        if (!in_array($animasi, ['fade-up', 'fade-down', 'fade-left', 'fade-right', 'zoom-in', 'flip'], true)) $animasi = 'fade-up';
        $gridClass = 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4';
        if ($gridStyle === 'list') $gridClass = 'flex flex-col gap-4';
        elseif ($gridStyle === 'masonry') $gridClass = 'columns-1 sm:columns-2 lg:columns-3 xl:columns-4 gap-6 space-y-6';
        elseif ($gridStyle === 'overlay') $gridClass = 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3';
        elseif ($gridStyle === 'magazine') $gridClass = 'grid gap-4 md:grid-cols-5 md:items-stretch';
        ?>

        <!-- News Grid (Style: <?php echo ucfirst($gridStyle); ?>) -->
        <div class="<?php echo $gridClass; ?>">
            <?php if ($gridStyle === 'magazine'):
                $magRows = array_slice($berita, 0, 5);
                $first = $magRows[0] ?? null;
                if ($first):
            ?>
                <div class="flex md:col-span-3" data-animate="<?php echo $animasi; ?>">
                    <a href="<?php echo htmlspecialchars(berita_url($first)); ?>" class="group relative block w-full flex-1 min-h-[320px] overflow-hidden rounded-2xl shadow-sm card-hover md:min-h-0">
                        <?php if (!empty($first['gambar'])): ?>
                            <img loading="lazy" src="<?php echo htmlspecialchars(berita_image_url($first['gambar'])); ?>" alt="" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        <?php else: ?>
                            <div class="absolute inset-0 bg-gradient-to-br from-purple-600 to-blue-600"></div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>
                        <div class="absolute bottom-0 p-4 sm:p-5">
                            <span class="mb-2 inline-block rounded-full bg-purple-600 px-3 py-1 text-[11px] font-bold text-white"><?php echo htmlspecialchars($first['kategori_nama'] ?? $kategori['nama']); ?></span>
                            <h3 class="text-base sm:text-lg font-black leading-snug text-white line-clamp-3 break-words"><?php echo htmlspecialchars($first['judul']); ?></h3>
                            <div class="mt-1 text-xs text-white/80"><?php echo formatTanggalIndonesia($first['tanggal_publikasi']); ?></div>
                        </div>
                    </a>
                </div>
                <div class="grid grid-cols-2 content-between gap-3 md:col-span-2">
                    <?php foreach (array_slice($magRows, 1, 4) as $sub):
                        $subUrl = htmlspecialchars(berita_url($sub));
                        $subImg = berita_image_url($sub['gambar'] ?? '');
                    ?>
                        <article class="group h-full overflow-hidden rounded-xl bg-white border border-slate-200 card-hover shadow-sm" data-animate="<?php echo $animasi; ?>">
                            <a href="<?php echo $subUrl; ?>" class="block aspect-[16/9] overflow-hidden image-zoom">
                                <?php if ($subImg !== ''): ?>
                                    <img loading="lazy" src="<?php echo htmlspecialchars($subImg); ?>" alt="" class="h-full w-full object-cover">
                                <?php else: ?>
                                    <span class="block h-full w-full bg-gradient-to-br from-purple-500 to-blue-500"></span>
                                <?php endif; ?>
                            </a>
                            <div class="p-3">
                                <a href="<?php echo $subUrl; ?>" class="block">
                                    <span class="inline-block text-[9px] font-bold uppercase tracking-wider text-purple-600 mb-1"><?php echo htmlspecialchars($sub['kategori_nama'] ?? $kategori['nama']); ?></span>
                                    <h3 class="text-xs font-bold leading-snug text-slate-900 line-clamp-2 group-hover:text-purple-700"><?php echo htmlspecialchars($sub['judul']); ?></h3>
                                </a>
                                <div class="mt-1 text-[11px] text-slate-500"><?php echo formatTanggalIndonesia($sub['tanggal_publikasi']); ?></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
            <?php foreach ($berita as $idx => $item): ?>
                <?php $dly = ($idx % 3) ? ' data-animate-delay="' . ($idx % 3) . '"' : ''; ?>
                <?php if ($gridStyle === 'overlay'): ?>
                    <!-- Overlay Style ala Elementor -->
                    <article class="group relative h-72 overflow-hidden rounded-2xl shadow-sm card-hover" data-animate="<?php echo $animasi; ?>"<?php echo $dly; ?>>
                        <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block h-full">
                            <?php if (!empty($item['gambar'])): ?>
                                <img loading="lazy" src="<?php echo htmlspecialchars(berita_image_url($item['gambar'])); ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-110">
                            <?php else: ?>
                                <div class="absolute inset-0 bg-gradient-to-br from-purple-600 to-blue-600"></div>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent"></div>
                            <div class="absolute bottom-0 p-5">
                                <span class="inline-block badge-category mb-2"><?php echo htmlspecialchars($item['kategori_nama'] ?? $kategori['nama']); ?></span>
                                <h3 class="text-lg font-bold leading-snug text-white line-clamp-2"><?php echo htmlspecialchars($item['judul']); ?></h3>
                                <div class="mt-2 text-xs text-white/80"><?php echo formatTanggalIndonesia($item['tanggal_publikasi']); ?></div>
                            </div>
                        </a>
                    </article>
                <?php elseif ($gridStyle === 'magazine'): ?>
                    <!-- Magazine Style: featured besar -->
                    <article class="group overflow-hidden rounded-2xl bg-white border border-slate-200 card-hover shadow-sm <?php echo $idx === 0 ? 'lg:col-span-2 lg:grid lg:grid-cols-2' : ''; ?>" data-animate="<?php echo $animasi; ?>"<?php echo $dly; ?>>
                        <?php if (!empty($item['gambar'])): ?>
                            <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block overflow-hidden image-zoom <?php echo $idx === 0 ? 'aspect-[16/9] lg:aspect-auto lg:h-full' : 'aspect-[16/10]'; ?>">
                                <img loading="lazy" src="<?php echo htmlspecialchars(berita_image_url($item['gambar'])); ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>" class="h-full w-full object-cover">
                            </a>
                        <?php endif; ?>
                        <div class="p-5">
                            <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-purple-600 mb-2"><?php echo htmlspecialchars($item['kategori_nama'] ?? $kategori['nama']); ?></span>
                            <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block">
                                <h3 class="<?php echo $idx === 0 ? 'text-2xl' : 'text-base'; ?> font-bold leading-snug text-slate-900 group-hover:text-purple-700 transition line-clamp-2 mb-2"><?php echo htmlspecialchars($item['judul']); ?></h3>
                                <?php if (!empty($item['ringkasan'])): ?><p class="text-sm text-slate-600 line-clamp-2 mb-3"><?php echo htmlspecialchars($item['ringkasan']); ?></p><?php endif; ?>
                            </a>
                            <div class="text-xs text-slate-500"><?php echo formatTanggalIndonesia($item['tanggal_publikasi']); ?></div>
                        </div>
                    </article>
                <?php elseif ($gridStyle === 'list'): ?>
                    <!-- List Style -->
                    <article class="group flex flex-col sm:flex-row gap-4 p-4 rounded-2xl bg-white border border-slate-200 card-hover shadow-sm" data-animate="<?php echo $animasi; ?>"<?php echo $dly; ?>>
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
                    <article class="group break-inside-avoid mb-6 overflow-hidden rounded-2xl bg-white border border-slate-200 card-hover shadow-sm" data-animate="<?php echo $animasi; ?>"<?php echo $dly; ?>>
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
                    <article class="group h-full overflow-hidden rounded-2xl bg-white border border-slate-200 card-hover shadow-sm" data-animate="<?php echo $animasi; ?>"<?php echo $dly; ?>>
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
            <?php endif; ?>
        </div>

        <?php if (($totalHal ?? 1) > 1): ?>
        <nav class="mt-10 flex flex-wrap items-center justify-center gap-2">
            <?php
            $baseParam = $kategoriSlug !== '' ? 'slug=' . urlencode($kategori['slug']) : 'kategori=' . (int)$kategoriId;
            $showPages = [];
            for ($p = 1; $p <= $totalHal; $p++) {
                if ($p === 1 || $p === $totalHal || abs($p - $halaman) <= 2) $showPages[] = $p;
            }
            $prev = 0;
            foreach ($showPages as $p):
                if ($p - $prev > 1): ?><span class="px-2 text-slate-400">...</span><?php endif;
                $prev = $p;
            ?>
                <a href="kategori?<?php echo $baseParam; ?>&hal=<?php echo $p; ?>" class="rounded-full px-4 py-2 text-sm font-bold transition <?php echo $p === $halaman ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white shadow-lg' : 'bg-white border border-slate-200 text-slate-600 hover:border-purple-300 hover:text-purple-700'; ?>"><?php echo $p; ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php
include __DIR__ . '/footer.php';
