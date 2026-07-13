<?php
require_once __DIR__ . '/config.php';

// Set dynamic page title
$pageTitle = htmlspecialchars($settings['site_name'] ?? 'Portal Berita');

$hero = null;
$carouselNews = [];
$headlineList = [];
$sectionByKategori = [];

// Fetch 5 latest news for carousel
$sqlCarousel = "SELECT b.*, k.nama AS kategori_nama
            FROM berita b
            LEFT JOIN kategori k ON k.id = b.kategori_id
            WHERE b.status = 'publish'
            ORDER BY b.tanggal_publikasi DESC, b.id DESC
            LIMIT 5";

$resultCarousel = $conn->query($sqlCarousel);

if ($resultCarousel && $resultCarousel->num_rows > 0) {
    while ($row = $resultCarousel->fetch_assoc()) {
        $carouselNews[] = $row;
    }
    $hero = $carouselNews[0]; // Set first news as default
}

$kategorisSection = [];
$resultKategoris = $conn->query("SELECT id, nama FROM kategori ORDER BY nama ASC");
if ($resultKategoris) {
    while ($row = $resultKategoris->fetch_assoc()) {
        $kategorisSection[] = $row;
    }
}

foreach ($kategorisSection as $kategori) {
    $stmt = $conn->prepare("SELECT b.*, k.nama AS kategori_nama
                            FROM berita b
                            LEFT JOIN kategori k ON k.id = b.kategori_id
                            WHERE b.kategori_id = ? AND b.status = 'publish'
                            ORDER BY b.tanggal_publikasi DESC, b.id DESC
                            LIMIT 4");
    $stmt->bind_param('i', $kategori['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $stmt->close();
    $sectionByKategori[$kategori['id']] = [
        'kategori' => $kategori,
        'items' => $items
    ];
}

// Fetch all latest news for "Berita Terkini" section
$latestNews = [];
$sqlLatest = "SELECT b.*, k.nama AS kategori_nama
              FROM berita b
              LEFT JOIN kategori k ON k.id = b.kategori_id
              WHERE b.status = 'publish'
              ORDER BY b.tanggal_publikasi DESC, b.id DESC
              LIMIT 12";
$resultLatest = $conn->query($sqlLatest);
if ($resultLatest) {
    while ($row = $resultLatest->fetch_assoc()) {
        $latestNews[] = $row;
    }
}

include __DIR__ . '/header.php';
?>

<?php if (!empty($carouselNews)): ?>
    <!-- Hero Carousel Section -->
    <section class="mb-12">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-600 to-blue-600 shadow-xl" id="heroCarousel">
            <!-- Carousel Slides -->
            <div class="relative" style="min-height: 500px;">
                <?php foreach ($carouselNews as $index => $news): ?>
                    <div class="carousel-slide absolute inset-0 transition-opacity duration-700 <?php echo $index === 0 ? 'opacity-100 z-10' : 'opacity-0 z-0'; ?>" data-slide="<?php echo $index; ?>">
                        <a href="<?php echo htmlspecialchars(berita_url($news)); ?>" class="block h-full">
                            <?php if (!empty($news['gambar'])): ?>
                                <div class="absolute inset-0 image-zoom">
                                    <img src="<?php echo htmlspecialchars(berita_image_url($news['gambar'])); ?>" alt="<?php echo htmlspecialchars($news['judul']); ?>" class="h-full w-full object-cover">
                                </div>
                            <?php else: ?>
                                <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-purple-500 to-blue-500">
                                    <svg class="w-32 h-32 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 01-2 2v12a2 2 0 012 2h14a2 2 0 012-2z"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent"></div>
                            <div class="absolute bottom-0 left-0 right-0 p-8 md:p-12">
                                <span class="inline-block badge-category mb-4">
                                    <?php echo htmlspecialchars($news['kategori_nama'] ?? 'Berita Terbaru'); ?>
                                </span>
                                <h1 class="text-3xl md:text-5xl font-black leading-tight text-white mb-4 max-w-4xl group-hover:text-purple-100 transition">
                                    <?php echo htmlspecialchars($news['judul']); ?>
                                </h1>
                                <p class="text-base md:text-lg text-white/90 line-clamp-2 mb-4 max-w-3xl">
                                    <?php echo htmlspecialchars($news['ringkasan'] ?? ''); ?>
                                </p>
                                <div class="flex items-center gap-4 text-sm text-white/85">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span><?php echo formatTanggalIndonesia($news['tanggal_publikasi'], true); ?></span>
                                    <?php if (!empty($news['penulis'])): ?>
                                        <span class="text-white/40">•</span>
                                        <span><?php echo htmlspecialchars($news['penulis']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Carousel Controls -->
            <button type="button" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm text-white flex items-center justify-center hover:bg-white/30 transition" id="carouselPrev">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            <button type="button" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm text-white flex items-center justify-center hover:bg-white/30 transition" id="carouselNext">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>

            <!-- Carousel Indicators -->
            <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex gap-2" id="carouselIndicators">
                <?php foreach ($carouselNews as $index => $news): ?>
                    <button type="button" class="w-3 h-3 rounded-full transition-all <?php echo $index === 0 ? 'bg-white w-8' : 'bg-white/50'; ?>" data-slide="<?php echo $index; ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <script>
    // Hero Carousel Functionality
    (function() {
        var slides = document.querySelectorAll('#heroCarousel .carousel-slide');
        var indicators = document.querySelectorAll('#carouselIndicators button');
        var prevBtn = document.getElementById('carouselPrev');
        var nextBtn = document.getElementById('carouselNext');
        var currentSlide = 0;
        var totalSlides = slides.length;
        var autoPlayInterval;

        function showSlide(index) {
            // Hide all slides
            slides.forEach(function(slide) {
                slide.classList.remove('opacity-100', 'z-10');
                slide.classList.add('opacity-0', 'z-0');
            });
            
            // Reset all indicators
            indicators.forEach(function(ind) {
                ind.classList.remove('bg-white', 'w-8');
                ind.classList.add('bg-white/50');
            });

            // Show current slide
            if (slides[index]) {
                slides[index].classList.remove('opacity-0', 'z-0');
                slides[index].classList.add('opacity-100', 'z-10');
            }
            
            // Update indicator
            if (indicators[index]) {
                indicators[index].classList.remove('bg-white/50');
                indicators[index].classList.add('bg-white', 'w-8');
            }

            currentSlide = index;
        }

        function nextSlide() {
            var next = (currentSlide + 1) % totalSlides;
            showSlide(next);
        }

        function prevSlide() {
            var prev = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(prev);
        }

        function startAutoPlay() {
            autoPlayInterval = setInterval(nextSlide, 5000); // Change slide every 5 seconds
        }

        function stopAutoPlay() {
            if (autoPlayInterval) {
                clearInterval(autoPlayInterval);
            }
        }

        // Event listeners
        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                stopAutoPlay();
                nextSlide();
                startAutoPlay();
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                stopAutoPlay();
                prevSlide();
                startAutoPlay();
            });
        }

        indicators.forEach(function(ind) {
            ind.addEventListener('click', function() {
                var slideIndex = parseInt(this.getAttribute('data-slide'));
                stopAutoPlay();
                showSlide(slideIndex);
                startAutoPlay();
            });
        });

        // Pause on hover
        var carousel = document.getElementById('heroCarousel');
        if (carousel) {
            carousel.addEventListener('mouseenter', stopAutoPlay);
            carousel.addEventListener('mouseleave', startAutoPlay);
        }

        // Start autoplay
        startAutoPlay();
    })();
    </script>
<?php else: ?>
    <div class="rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-purple-50 px-6 py-8 text-center">
        <svg class="w-16 h-16 text-blue-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 01-2 2v12a2 2 0 012 2h14a2 2 0 012-2z"></path>
        </svg>
        <p class="text-lg font-bold text-slate-700 mb-2">Belum ada berita yang dipublikasikan</p>
        <p class="text-sm text-slate-600">Silakan tambahkan berita melalui dashboard admin.</p>
    </div>
<?php endif; ?>

<!-- Berita Terkini Section -->
<?php if (!empty($latestNews)): ?>
    <section class="mb-12">
        <!-- Section Header -->
        <div class="mb-6 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-1.5 rounded-full bg-gradient-to-b from-purple-600 to-blue-600"></div>
                <h2 class="text-2xl font-black text-slate-900">
                    Berita Terkini
                </h2>
            </div>
        </div>

        <!-- News Grid -->
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <?php foreach ($latestNews as $index => $item): ?>
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
                                <?php echo htmlspecialchars($item['kategori_nama'] ?? 'Berita'); ?>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span><?php echo formatTanggalIndonesia($item['tanggal_publikasi']); ?></span>
                            <?php if (!empty($item['penulis'])): ?>
                                <span class="text-slate-300">•</span>
                                <span><?php echo htmlspecialchars($item['penulis']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <!-- View More Button -->
        <?php if (count($latestNews) >= 12): ?>
            <div class="mt-8 text-center">
                <a href="kategori" class="inline-flex items-center gap-2 px-8 py-3 rounded-full bg-gradient-to-r from-purple-600 to-blue-600 text-white font-bold shadow-lg hover:shadow-xl hover:scale-105 transition transform">
                    Lihat Semua Berita
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php foreach ($sectionByKategori as $section): ?>
    <?php if (!empty($section['items'])): ?>
        <section class="mb-12">
            <!-- Section Header -->
            <div class="mb-6 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-1.5 rounded-full bg-gradient-to-b from-purple-600 to-blue-600"></div>
                    <h2 class="text-2xl font-black text-slate-900">
                        <?php echo htmlspecialchars($section['kategori']['nama']); ?>
                    </h2>
                </div>
                <a href="kategori?kategori=<?php echo (int)$section['kategori']['id']; ?>" class="inline-flex items-center gap-2 text-sm font-bold text-purple-600 transition hover:text-purple-700 group">
                    Lihat semua
                    <svg class="w-4 h-4 transition group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>

            <!-- News Grid -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <?php foreach ($section['items'] as $index => $item): ?>
                    <div>
                        <article class="group h-full overflow-hidden rounded-2xl bg-white border border-slate-200 card-hover shadow-sm">
                            <?php if (!empty($item['gambar'])): ?>
                                <div class="image-zoom">
                                    <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block">
                                        <div class="aspect-[16/10] bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
                                            <img src="<?php echo htmlspecialchars(berita_image_url($item['gambar'])); ?>" alt="<?php echo htmlspecialchars($item['judul']); ?>" class="h-full w-full object-cover">
                                        </div>
                                    </a>
                                </div>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block">
                                    <div class="aspect-[16/10] bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </a>
                            <?php endif; ?>
                            <div class="p-5">
                                <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-purple-600 mb-2">
                                    <?php echo htmlspecialchars($item['kategori_nama'] ?? $section['kategori']['nama']); ?>
                                </span>
                                <h3 class="mb-2 text-lg font-bold leading-snug text-slate-900 group-hover:text-purple-700 transition line-clamp-2">
                                    <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="no-underline">
                                        <?php echo htmlspecialchars($item['judul']); ?>
                                    </a>
                                </h3>
                                <div class="flex items-center gap-2 text-xs text-slate-500 mb-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <?php echo formatTanggalIndonesia($item['tanggal_publikasi']); ?>
                                </div>
                                <?php if ($index === 0): ?>
                                    <p class="text-sm leading-6 text-slate-600 line-clamp-3">
                                        <?php echo htmlspecialchars($item['ringkasan'] ?? ''); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
<?php endforeach; ?>

<?php
include __DIR__ . '/footer.php';
