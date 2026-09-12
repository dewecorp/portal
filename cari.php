<?php
require_once __DIR__ . '/config.php';

$q = trim((string)($_GET['q'] ?? ''));
$halaman = max(1, (int)($_GET['hal'] ?? 1));
$perHal = 12;
$hasil = [];
$total = 0;
$totalHal = 1;

if ($q !== '') {
    $like = '%' . $q . '%';
    $cnt = $conn->prepare("SELECT COUNT(*) AS jml FROM berita WHERE status='publish' AND (judul LIKE ? OR ringkasan LIKE ? OR isi LIKE ?)");
    $cnt->bind_param('sss', $like, $like, $like);
    $cnt->execute();
    $total = (int)($cnt->get_result()->fetch_assoc()['jml'] ?? 0);
    $cnt->close();
    $totalHal = max(1, (int)ceil($total / $perHal));
    $halaman = min($halaman, $totalHal);
    $offset = ($halaman - 1) * $perHal;
    $stmt = $conn->prepare("SELECT b.*, k.nama AS kategori_nama FROM berita b LEFT JOIN kategori k ON k.id=b.kategori_id WHERE b.status='publish' AND (b.judul LIKE ? OR b.ringkasan LIKE ? OR b.isi LIKE ?) ORDER BY b.tanggal_publikasi DESC, b.id DESC LIMIT ? OFFSET ?");
    $stmt->bind_param('sssii', $like, $like, $like, $perHal, $offset);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $hasil[] = $row;
    $stmt->close();
}

$pageTitle = ($q !== '' ? 'Hasil cari: ' . $q : 'Pencarian') . ' - ' . ($settings['site_name'] ?? 'Portal Berita');
include __DIR__ . '/header.php';
?>
<div class="mb-8" data-animate="fade-up">
    <div class="flex items-center gap-3 mb-2">
        <div class="h-10 w-1.5 rounded-full bg-gradient-to-b from-purple-600 to-blue-600"></div>
        <h1 class="text-3xl font-black text-slate-900">Pencarian</h1>
    </div>
    <form action="cari" method="get" class="ml-4 mt-3 flex max-w-xl gap-2">
        <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Ketik kata kunci..." class="flex-1 rounded-full border border-slate-200 px-5 py-2.5 text-sm outline-none focus:border-purple-400 focus:ring-2 focus:ring-purple-100">
        <button class="rounded-full bg-gradient-to-r from-purple-600 to-blue-600 px-6 py-2.5 text-sm font-bold text-white">Cari</button>
    </form>
    <?php if ($q !== ''): ?><p class="ml-4 mt-2 text-sm text-slate-500"><?php echo number_format($total); ?> hasil untuk "<strong><?php echo htmlspecialchars($q); ?></strong>"</p><?php endif; ?>
</div>
<?php if ($q !== '' && empty($hasil)): ?>
    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-6 py-12 text-center"><p class="font-bold text-slate-700">Tidak ditemukan. Coba kata kunci lain.</p></div>
<?php elseif (!empty($hasil)): ?>
<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" data-animate="fade-up">
    <?php foreach ($hasil as $item): ?>
    <article class="group h-full overflow-hidden rounded-2xl bg-white border border-slate-200 card-hover shadow-sm">
        <?php if (!empty($item['gambar'])): ?>
        <a href="<?php echo htmlspecialchars(berita_url($item)); ?>" class="block aspect-[16/10] overflow-hidden image-zoom"><img loading="lazy" src="<?php echo htmlspecialchars(berita_image_url($item['gambar'])); ?>" alt="" class="h-full w-full object-cover"></a>
        <?php endif; ?>
        <div class="p-5">
            <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-purple-600 mb-2"><?php echo htmlspecialchars($item['kategori_nama'] ?? 'Berita'); ?></span>
            <a href="<?php echo htmlspecialchars(berita_url($item)); ?>"><h3 class="text-base font-bold text-slate-900 group-hover:text-purple-700 line-clamp-2"><?php echo htmlspecialchars($item['judul']); ?></h3></a>
            <div class="mt-2 text-xs text-slate-500"><?php echo formatTanggalIndonesia($item['tanggal_publikasi']); ?></div>
        </div>
    </article>
    <?php endforeach; ?>
</div>
<?php if ($totalHal > 1): ?>
<nav class="mt-10 flex flex-wrap justify-center gap-2">
    <?php for ($p = 1; $p <= $totalHal; $p++): ?>
    <a href="cari?q=<?php echo urlencode($q); ?>&hal=<?php echo $p; ?>" class="rounded-full px-4 py-2 text-sm font-bold <?php echo $p === $halaman ? 'bg-gradient-to-r from-purple-600 to-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600'; ?>"><?php echo $p; ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/footer.php'; ?>
