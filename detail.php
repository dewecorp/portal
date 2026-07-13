<?php
require_once __DIR__ . '/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = trim((string)($_GET['slug'] ?? ''));

if ($slug !== '') {
    $stmt = $conn->prepare("SELECT b.*, k.nama AS kategori_nama
                            FROM berita b
                            LEFT JOIN kategori k ON k.id = b.kategori_id
                            WHERE b.slug = ? AND b.status = 'publish'
                            LIMIT 1");
    $stmt->bind_param('s', $slug);
} else {
    $stmt = $conn->prepare("SELECT b.*, k.nama AS kategori_nama
                            FROM berita b
                            LEFT JOIN kategori k ON k.id = b.kategori_id
                            WHERE b.id = ? AND b.status = 'publish'
                            LIMIT 1");
    $stmt->bind_param('i', $id);
}
$stmt->execute();
$result = $stmt->get_result();
$berita = $result->fetch_assoc();
$stmt->close();

if ($berita) {
    $id = (int)$berita['id'];
    if ($slug === '' && !empty($berita['slug'])) {
        header('Location: ' . berita_url($berita), true, 302);
        exit;
    }
}

// Set dynamic page title
if ($berita) {
    $pageTitle = htmlspecialchars(($berita['kategori_nama'] ?? 'Berita') . ' - ' . ($settings['site_name'] ?? 'Portal Berita'));
} else {
    $pageTitle = 'Berita Tidak Ditemukan - ' . htmlspecialchars($settings['site_name'] ?? 'Portal Berita');
}

include __DIR__ . '/header.php';
?>

<?php
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$latestNews = [];
if ($berita) {
    $latestNewsLimit = max(1, min(20, (int)($settings['latest_news_count'] ?? 5)));
    $latestStmt = $conn->prepare("SELECT b.id, b.judul, b.slug, b.gambar, b.tanggal_publikasi, k.nama AS kategori_nama
                                  FROM berita b
                                  LEFT JOIN kategori k ON k.id = b.kategori_id
                                  WHERE b.status = 'publish' AND b.id <> ?
                                  ORDER BY b.tanggal_publikasi DESC, b.id DESC
                                  LIMIT $latestNewsLimit");
    $latestStmt->bind_param('i', $id);
    $latestStmt->execute();
    $latestResult = $latestStmt->get_result();
    while ($latestRow = $latestResult->fetch_assoc()) {
        $latestNews[] = $latestRow;
    }
    $latestStmt->close();
}
?>

<?php if (!$berita): ?>
    <div class="rounded-2xl border border-amber-200 bg-gradient-to-r from-amber-50 to-orange-50 px-6 py-8 text-center">
        <svg class="w-16 h-16 text-amber-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <p class="text-lg font-bold text-slate-700 mb-2">Berita tidak ditemukan</p>
        <p class="text-sm text-slate-600">Berita tidak ditemukan atau belum dipublikasikan.</p>
    </div>
<?php else: ?>
    <?php
    // Determine layout class
    $layoutClass = 'lg:grid-cols-[minmax(0,1fr)_340px]';
    if ($berita['layout_style'] === 'wide') {
        $layoutClass = 'lg:grid-cols-1';
    } elseif ($berita['layout_style'] === 'boxed') {
        $layoutClass = 'lg:grid-cols-[minmax(0,900px)_340px]';
    }
    ?>
    <div class="grid min-w-0 gap-8 <?php echo $layoutClass; ?>">
        <!-- Main Article -->
        <article class="min-w-0">
            <!-- Article Header -->
            <div class="mb-6">
                <?php if ($berita['show_kategori']): ?>
                    <span class="inline-block badge-category mb-4">
                        <?php echo htmlspecialchars($berita['kategori_nama'] ?? 'Berita'); ?>
                    </span>
                <?php endif; ?>
                <h1 class="article-title text-3xl md:text-5xl font-black leading-tight text-slate-900 mb-4">
                    <?php echo htmlspecialchars($berita['judul']); ?>
                </h1>
                <?php if ($berita['show_meta']): ?>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-slate-500">
                        <?php if ($berita['show_tanggal']): ?>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="font-medium"><?php echo formatTanggalIndonesia($berita['tanggal_publikasi'], true); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($berita['show_penulis'] && !empty($berita['penulis'])): ?>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="font-medium"><?php echo htmlspecialchars($berita['penulis']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Featured Image -->
            <?php $gambarUtamaUrl = berita_image_url($berita['gambar'] ?? ''); ?>
            <?php if ($gambarUtamaUrl !== '' && (int)($berita['show_gambar_detail'] ?? 1) !== 0): ?>
                <div class="mb-8 overflow-hidden rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 shadow-lg">
                    <div class="aspect-[16/9]">
                        <img src="<?php echo htmlspecialchars($gambarUtamaUrl); ?>" class="h-full w-full object-cover" alt="<?php echo htmlspecialchars($berita['judul']); ?>">
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($berita['show_ringkasan'] && !empty($berita['ringkasan'])): ?>
                <div class="mb-8 rounded-2xl bg-gradient-to-r from-purple-50 to-blue-50 p-6 border border-purple-200">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-purple-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-bold text-purple-900 uppercase tracking-wide mb-2">Sekilas Berita</h3>
                            <p class="text-base text-purple-800 leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($berita['ringkasan'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Article Content -->
            <div class="mb-8 rounded-2xl bg-white p-6 md:p-10 border border-slate-200 shadow-sm">
                <div class="news-content max-w-none text-lg leading-8 text-slate-700">
                    <?php echo $berita['isi']; ?>
                </div>
            </div>

            <!-- Share Buttons -->
            <div class="border-t border-slate-200 pt-6">
                <div class="mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                    <span class="text-sm font-bold uppercase tracking-wide text-slate-700">Bagikan artikel ini</span>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="https://api.whatsapp.com/send?text=<?php echo rawurlencode($berita['judul'] . ' - ' . $currentUrl); ?>" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-emerald-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-600 hover:shadow-lg hover:shadow-emerald-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
                        WhatsApp
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo rawurlencode($currentUrl); ?>" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700 hover:shadow-lg hover:shadow-blue-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?php echo rawurlencode($berita['judul']); ?>&url=<?php echo rawurlencode($currentUrl); ?>" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 hover:shadow-lg hover:shadow-slate-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        X
                    </a>
                    <a href="https://t.me/share/url?url=<?php echo rawurlencode($currentUrl); ?>&text=<?php echo rawurlencode($berita['judul']); ?>" target="_blank" class="inline-flex items-center gap-2 rounded-full bg-sky-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-sky-600 hover:shadow-lg hover:shadow-sky-200">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        Telegram
                    </a>
                    <button type="button" class="inline-flex items-center gap-2 rounded-full bg-slate-200 px-5 py-2.5 text-sm font-bold text-slate-800 transition hover:bg-slate-300" id="btnCopyLink" data-url="<?php echo htmlspecialchars($currentUrl); ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        Salin link
                    </button>
                    <button type="button" class="inline-flex items-center gap-2 rounded-full border-2 border-purple-600 bg-white px-5 py-2.5 text-sm font-bold text-purple-600 transition hover:bg-purple-50" id="btnWebShare" data-url="<?php echo htmlspecialchars($currentUrl); ?>" data-title="<?php echo htmlspecialchars($berita['judul']); ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path></svg>
                        Bagikan
                    </button>
                </div>
            </div>
        </article>

        <!-- Sidebar -->
        <?php if ($berita['layout_style'] !== 'wide'): ?>
            <aside class="min-w-0">
            <div>
                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-sm mb-6">
                    <h2 class="mb-4 text-sm font-black uppercase tracking-wide text-slate-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Berita Terkini
                    </h2>
                    <?php if (empty($latestNews)): ?>
                        <p class="text-sm text-slate-500">Belum ada berita terbaru.</p>
                    <?php else: ?>
                        <div class="space-y-4">
                        <?php foreach ($latestNews as $latestItem): ?>
                            <?php $latestImageUrl = berita_image_url($latestItem['gambar'] ?? ''); ?>
                            <a href="<?php echo htmlspecialchars(berita_url($latestItem)); ?>" class="group flex gap-3 no-underline">
                                <div class="h-20 w-24 flex-shrink-0 overflow-hidden rounded-xl bg-slate-100">
                                    <?php if ($latestImageUrl !== ''): ?>
                                        <img src="<?php echo htmlspecialchars($latestImageUrl); ?>" alt="<?php echo htmlspecialchars($latestItem['judul']); ?>" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                    <?php else: ?>
                                        <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-purple-100 to-blue-100 text-purple-400">
                                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="mb-1 text-[11px] font-black uppercase tracking-wide text-purple-600"><?php echo htmlspecialchars($latestItem['kategori_nama'] ?? 'Berita'); ?></div>
                                    <h3 class="line-clamp-2 text-sm font-extrabold leading-snug text-slate-900 transition group-hover:text-purple-700"><?php echo htmlspecialchars($latestItem['judul']); ?></h3>
                                    <div class="mt-1 text-xs font-medium text-slate-500"><?php echo formatTanggalIndonesia($latestItem['tanggal_publikasi']); ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
    (function () {
        var btnCopy = document.getElementById('btnCopyLink');
        if (btnCopy) {
            btnCopy.addEventListener('click', function () {
                var url = this.getAttribute('data-url') || window.location.href;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function () {
                        alert('Link berita disalin.');
                    }, function () {
                        alert('Gagal menyalin link.');
                    });
                } else {
                    var input = document.createElement('input');
                    input.value = url;
                    document.body.appendChild(input);
                    input.select();
                    try {
                        document.execCommand('copy');
                        alert('Link berita disalin.');
                    } catch (e) {
                        alert('Gagal menyalin link.');
                    }
                    document.body.removeChild(input);
                }
            });
        }

        var btnShare = document.getElementById('btnWebShare');
        if (btnShare && navigator.share) {
            btnShare.addEventListener('click', function () {
                var url = this.getAttribute('data-url') || window.location.href;
                var title = this.getAttribute('data-title') || document.title;
                navigator.share({
                    title: title,
                    url: url
                });
            });
        } else if (btnShare) {
            btnShare.style.display = 'none';
        }
    })();
</script>

<?php
include __DIR__ . '/footer.php';
