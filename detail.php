<?php
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

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

$komentarError = '';
$komentarSukses = '';
if ($berita && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_komentar'])) {
    $nama = trim((string)($_POST['nama'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $isi = trim((string)($_POST['isi'] ?? ''));
    $website = trim((string)($_POST['website'] ?? ''));
    $honeypot = trim((string)($_POST['telepon_konfirmasi'] ?? ''));
    $captchaJawab = trim((string)($_POST['captcha'] ?? ''));
    $mulaiIsi = (int)($_POST['form_mulai'] ?? 0);
    $maxLinks = (int)($settings['komentar_max_links'] ?? 2);
    $interval = (int)($settings['komentar_interval_detik'] ?? 30);
    if (($settings['komentar_aktif'] ?? 1) != 1) {
        $komentarError = 'Kolom komentar sedang ditutup.';
    } elseif ($honeypot !== '') {
        $komentarError = 'Komentar terdeteksi sebagai spam.';
    } elseif ($nama === '' || $isi === '') {
        $komentarError = 'Nama dan komentar wajib diisi.';
    } elseif (strlen($nama) > 100 || strlen($isi) > 2000) {
        $komentarError = 'Komentar terlalu panjang.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $komentarError = 'Format email tidak valid.';
    } elseif ($mulaiIsi > 0 && (time() - $mulaiIsi) < 5) {
        $komentarError = 'Terlalu cepat mengirim. Coba lagi beberapa detik.';
    } elseif (($settings['komentar_captcha'] ?? 1) == 1 && $captchaJawab === '') {
        $komentarError = 'Jawaban captcha wajib diisi.';
    } elseif (($settings['komentar_captcha'] ?? 1) == 1 && (int)$captchaJawab !== (int)($_SESSION['komentar_captcha_hasil'] ?? -999)) {
        $komentarError = 'Jawaban captcha salah.';
    } else {
        [$isSpam, $spamMsg] = komentar_is_spam($nama, $email, $isi, $website, $maxLinks);
        $badWords = komentar_bad_words($conn);
        $isiLower = strtolower($isi);
        $kenaKata = '';
        foreach ($badWords as $bw) {
            if ($bw !== '' && strpos($isiLower, strtolower($bw)) !== false) { $kenaKata = $bw; break; }
        }
        $ipHash = hash('sha256', visitor_current_ip() . '|portal_berita');
        $rateOk = true;
        $rs = $conn->prepare("SELECT created_at FROM komentar WHERE ip_hash = ? ORDER BY id DESC LIMIT 1");
        $rs->bind_param('s', $ipHash);
        $rs->execute();
        if (($last = $rs->get_result()->fetch_assoc()) && (time() - strtotime($last['created_at'])) < $interval) {
            $rateOk = false;
        }
        $rs->close();
        if (!$rateOk) {
            $komentarError = 'Terlalu sering mengirim. Tunggu ' . $interval . ' detik.';
        } elseif ($isSpam || $kenaKata !== '') {
            $st = $conn->prepare("INSERT INTO komentar (berita_id, nama, email, website, isi, status, ip_hash, user_agent) VALUES (?, ?, ?, ?, ?, 'spam', ?, ?)");
            $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
            $st->bind_param('issssss', $id, $nama, $email, $website, $isi, $ipHash, $ua);
            $st->execute();
            $st->close();
            $komentarError = $isSpam ? $spamMsg : 'Komentar mengandung kata terlarang.';
        } else {
            $status = (($settings['komentar_moderasi'] ?? 1) == 1) ? 'pending' : 'approved';
            $st = $conn->prepare("INSERT INTO komentar (berita_id, nama, email, website, isi, status, ip_hash, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
            $st->bind_param('isssssss', $id, $nama, $email, $website, $isi, $status, $ipHash, $ua);
            if ($st->execute()) {
                $komentarSukses = $status === 'approved' ? 'Komentar berhasil ditampilkan.' : 'Komentar terkirim, menunggu moderasi admin.';
                unset($_SESSION['komentar_captcha_hasil']);
            } else {
                $komentarError = 'Gagal menyimpan komentar.';
            }
            $st->close();
        }
    }
}

if ($berita) {
    $viewKey = 'viewed_berita_' . $id;
    if (empty($_SESSION[$viewKey])) {
        $conn->query("UPDATE berita SET views = views + 1 WHERE id = " . $id);
        $_SESSION[$viewKey] = time();
        $berita['views'] = ((int)($berita['views'] ?? 0)) + 1;
    }
}

if (($settings['komentar_captcha'] ?? 1) == 1 && !isset($_SESSION['komentar_captcha_hasil'])) {
    $a = random_int(1, 9); $b = random_int(1, 9);
    $_SESSION['komentar_captcha_a'] = $a;
    $_SESSION['komentar_captcha_b'] = $b;
    $_SESSION['komentar_captcha_hasil'] = $a + $b;
}
$formMulai = time();

// Set dynamic page title
if ($berita) {
    $pageTitle = ($berita['kategori_nama'] ?? 'Berita') . ' - ' . ($settings['site_name'] ?? 'Portal Berita');
} else {
    $pageTitle = 'Berita Tidak Ditemukan - ' . ($settings['site_name'] ?? 'Portal Berita');
}

include __DIR__ . '/header.php';
?>

<?php
$currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$relatedNews = [];
$komentarList = [];
$jmlKomentar = 0;
if ($berita) {
    $katId = (int)($berita['kategori_id'] ?? 0);
    if ($katId > 0) {
        $relStmt = $conn->prepare("SELECT b.id, b.judul, b.slug, b.gambar, b.tanggal_publikasi FROM berita b WHERE b.status='publish' AND b.id <> ? AND b.kategori_id = ? ORDER BY b.tanggal_publikasi DESC, b.id DESC LIMIT 4");
        $relStmt->bind_param('ii', $id, $katId);
        $relStmt->execute();
        $relRes = $relStmt->get_result();
        while ($r = $relRes->fetch_assoc()) $relatedNews[] = $r;
        $relStmt->close();
    }
    $kmStmt = $conn->prepare("SELECT nama, isi, created_at FROM komentar WHERE berita_id = ? AND status = 'approved' ORDER BY id ASC LIMIT 100");
    $kmStmt->bind_param('i', $id);
    $kmStmt->execute();
    $kmRes = $kmStmt->get_result();
    while ($kr = $kmRes->fetch_assoc()) $komentarList[] = $kr;
    $kmStmt->close();
    $jmlKomentar = count($komentarList);
}
$waktuBaca = $berita ? hitung_waktu_baca((string)($berita['isi'] ?? '')) : 0;
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
    $isWide = ($berita['layout_style'] ?? 'default') === 'wide';
    $isBoxed = ($berita['layout_style'] ?? 'default') === 'boxed';
    ?>
    <style>
    .detail-grid{display:block;min-width:0;width:100%;}
    .detail-grid>article{min-width:0;width:100%;}
    .detail-grid>aside{min-width:0;width:100%;margin-top:2rem;}
    @media (min-width:1024px){
        .detail-grid{display:flex !important;gap:2rem;align-items:flex-start;}
        .detail-grid>article{flex:1 1 0% !important;min-width:0 !important;width:auto !important;order:1 !important;}
        .detail-grid>aside{flex:0 0 340px !important;width:340px !important;min-width:0 !important;margin-top:0 !important;order:2 !important;}
        .detail-grid.is-wide>aside{display:none !important;}
        .detail-grid.is-wide>article{flex:1 1 100% !important;}
    }
    .detail-grid.is-boxed{max-width:64rem;margin-left:auto;margin-right:auto;}
    </style>
    <div id="readingProgress" style="height:4px;margin:0 0 1.5rem;border-radius:999px;background:#eef2ff;overflow:hidden;"><div id="readingProgressBar" style="height:100%;width:0;background:linear-gradient(90deg,#7c3aed,#2563eb);transition:width .1s linear;"></div></div>
    <div class="detail-grid <?php echo $isWide ? 'is-wide' : ''; ?> <?php echo $isBoxed ? 'is-boxed' : ''; ?>">
        <!-- Main Article : kiri -->
        <article class="min-w-0">
            <!-- Article Header -->
            <div class="mb-6">
                <nav class="mb-3 text-xs font-semibold text-slate-400">
                    <a href="index" class="hover:text-purple-600">Beranda</a>
                    <span class="mx-1">/</span>
                    <a href="kategori?kategori=<?php echo (int)($berita['kategori_id'] ?? 0); ?>" class="hover:text-purple-600"><?php echo htmlspecialchars($berita['kategori_nama'] ?? 'Berita'); ?></a>
                    <span class="mx-1">/</span>
                    <span class="text-slate-600">Detail</span>
                </nav>
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
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span class="font-medium"><?php echo number_format((int)($berita['views'] ?? 0)); ?> dibaca</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="font-medium"><?php echo (int)$waktuBaca; ?> mnt baca</span>
                        </div>
                        <a href="#komentar" class="flex items-center gap-2 hover:text-purple-700">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                            <span class="font-medium"><?php echo (int)$jmlKomentar; ?> komentar</span>
                        </a>
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

            <?php if (!empty($relatedNews)): ?>
            <div class="mt-10" data-animate="fade-up">
                <div class="mb-5 flex items-center gap-3">
                    <div class="h-8 w-1.5 rounded-full bg-gradient-to-b from-purple-600 to-blue-600"></div>
                    <h2 class="text-xl font-black text-slate-900">Berita Terkait</h2>
                </div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <?php foreach ($relatedNews as $ri => $rel): ?>
                    <?php $rdly = ($ri % 2) ? ' data-animate-delay="' . ($ri % 2) . '"' : ''; ?>
                    <a href="<?php echo htmlspecialchars(berita_url($rel)); ?>" class="group flex gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm card-hover" data-animate="fade-up"<?php echo $rdly; ?>>
                        <?php $ru = berita_image_url($rel['gambar'] ?? ''); ?>
                        <div class="h-20 w-24 flex-shrink-0 overflow-hidden rounded-xl bg-slate-100">
                            <?php if ($ru !== ''): ?><img loading="lazy" src="<?php echo htmlspecialchars($ru); ?>" alt="" class="h-full w-full object-cover group-hover:scale-105 transition"><?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <h3 class="line-clamp-2 text-sm font-extrabold text-slate-900 group-hover:text-purple-700"><?php echo htmlspecialchars($rel['judul']); ?></h3>
                            <div class="mt-1 text-xs text-slate-500"><?php echo formatTanggalIndonesia($rel['tanggal_publikasi']); ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div id="komentar" class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-animate="fade-up">
                <h2 class="mb-1 text-xl font-black text-slate-900">Komentar (<?php echo (int)$jmlKomentar; ?>)</h2>
                <p class="mb-5 text-sm text-slate-500">Diskusi sehat. Komentar spam otomatis ditolak.</p>
                <?php if ($komentarError !== ''): ?><div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700"><?php echo htmlspecialchars($komentarError); ?></div><?php endif; ?>
                <?php if ($komentarSukses !== ''): ?><div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700"><?php echo htmlspecialchars($komentarSukses); ?></div><?php endif; ?>
                <?php if (empty($komentarList)): ?>
                    <p class="mb-5 rounded-xl bg-slate-50 px-4 py-4 text-sm text-slate-500">Belum ada komentar. Jadilah pertama.</p>
                <?php else: ?>
                    <div class="mb-6 space-y-4">
                    <?php foreach ($komentarList as $km): ?>
                        <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                            <div class="mb-1 flex items-center gap-2">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-purple-600 to-blue-600 text-sm font-black text-white"><?php echo htmlspecialchars(mb_strtoupper(mb_substr($km['nama'], 0, 1))); ?></span>
                                <span class="text-sm font-extrabold text-slate-900"><?php echo htmlspecialchars($km['nama']); ?></span>
                                <span class="text-xs text-slate-400"><?php echo formatTanggalIndonesia($km['created_at'], true); ?></span>
                            </div>
                            <p class="text-sm leading-6 text-slate-700"><?php echo nl2br(htmlspecialchars($km['isi'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (($settings['komentar_aktif'] ?? 1) == 1): ?>
                <form method="post" action="#komentar" class="grid gap-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input type="text" name="nama" required maxlength="100" placeholder="Nama *" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-100" value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">
                        <input type="email" name="email" maxlength="150" placeholder="Email (opsional)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-100" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <textarea name="isi" required maxlength="2000" rows="4" placeholder="Tulis komentar..." class="rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-100"><?php echo htmlspecialchars($_POST['isi'] ?? ''); ?></textarea>
                    <input type="text" name="website" placeholder="Website (opsional)" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm outline-none focus:border-purple-400" value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>">
                    <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true"><label>Telepon<input type="text" name="telepon_konfirmasi" value="" autocomplete="off"></label></div>
                    <input type="hidden" name="form_mulai" value="<?php echo (int)$formMulai; ?>">
                    <?php if (($settings['komentar_captcha'] ?? 1) == 1): ?>
                    <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-4 py-3">
                        <span class="text-sm font-bold text-slate-700">Captcha: <?php echo (int)($_SESSION['komentar_captcha_a'] ?? 0); ?> + <?php echo (int)($_SESSION['komentar_captcha_b'] ?? 0); ?> = ?</span>
                        <input type="number" name="captcha" required placeholder="Jawab" class="w-28 rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none focus:border-purple-400">
                    </div>
                    <?php endif; ?>
                    <div><button type="submit" name="kirim_komentar" value="1" class="rounded-full bg-gradient-to-r from-purple-600 to-blue-600 px-8 py-2.5 text-sm font-bold text-white shadow-lg hover:shadow-xl hover:scale-105 transition">Kirim Komentar</button></div>
                </form>
                <?php else: ?><p class="text-sm text-slate-500">Kolom komentar ditutup.</p><?php endif; ?>
            </div>
        </article>

        <!-- Sidebar : kanan -->
        <?php if (($berita['layout_style'] ?? 'default') !== 'wide'): ?>
            <aside class="detail-side min-w-0">
                <?php render_sidebar_widgets($conn, $settings, get_active_sections($conn, 'sidebar')); ?>
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
                        if (window.portalToast) portalToast('Link berita disalin.');
                    }, function () {
                        if (window.portalToast) portalToast('Gagal menyalin link.');
                    });
                } else {
                    var input = document.createElement('input');
                    input.value = url;
                    document.body.appendChild(input);
                    input.select();
                    try {
                        document.execCommand('copy');
                        if (window.portalToast) portalToast('Link berita disalin.');
                    } catch (e) {
                        if (window.portalToast) portalToast('Gagal menyalin link.');
                    }
                    document.body.removeChild(input);
                }
            });
        }

        var bar = document.getElementById('readingProgressBar');
        if (bar) {
            window.addEventListener('scroll', function() {
                var h = document.documentElement;
                var max = h.scrollHeight - h.clientHeight;
                bar.style.width = (max > 0 ? (h.scrollTop / max) * 100 : 0) + '%';
            }, { passive: true });
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
