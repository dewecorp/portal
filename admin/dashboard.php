<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$totalKategori = 0;
$totalBerita = 0;
$totalPublish = 0;
$totalDraft = 0;
$totalKomentarPending = 0;
$totalKomentarSpam = 0;
$siteName = 'Portal Berita';
$recentBerita = [];

$bulanIndonesia = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// Ambil data berita publish per bulan (12 bulan terakhir)
$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $ts = strtotime("-$i months");
    $y = (int)date('Y', $ts);
    $m = (int)date('n', $ts);
    $monthlyData[] = ['year' => $y, 'month' => $m, 'label' => $bulanIndonesia[$m - 1] . ' ' . $y, 'count' => 0];
}

$sqlMonthly = "SELECT YEAR(tanggal_publikasi) AS y, MONTH(tanggal_publikasi) AS m, COUNT(*) AS jml
               FROM berita
               WHERE status = 'publish' AND tanggal_publikasi IS NOT NULL
               GROUP BY YEAR(tanggal_publikasi), MONTH(tanggal_publikasi)";
$resMonthly = $conn->query($sqlMonthly);
if ($resMonthly) {
    $dbMonthly = [];
    while ($row = $resMonthly->fetch_assoc()) {
        $dbMonthly[(int)$row['y']][(int)$row['m']] = (int)$row['jml'];
    }
    foreach ($monthlyData as &$md) {
        $md['count'] = $dbMonthly[$md['year']][$md['month']] ?? 0;
    }
    unset($md);
}

$result = $conn->query("SELECT COUNT(*) AS total FROM kategori");
if ($result) {
    $row = $result->fetch_assoc();
    $totalKategori = (int)$row['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM berita");
if ($result) {
    $row = $result->fetch_assoc();
    $totalBerita = (int)$row['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM berita WHERE status = 'publish'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalPublish = (int)$row['total'];
}

$result = $conn->query("SELECT COUNT(*) AS total FROM berita WHERE status = 'draft'");
if ($result) {
    $row = $result->fetch_assoc();
    $totalDraft = (int)$row['total'];
}

$rk = $conn->query("SELECT status, COUNT(*) jml FROM komentar GROUP BY status");
if ($rk) {
    while ($kr = $rk->fetch_assoc()) {
        if ($kr['status'] === 'pending') $totalKomentarPending = (int)$kr['jml'];
        if ($kr['status'] === 'spam') $totalKomentarSpam = (int)$kr['jml'];
    }
}

$resSettings = $conn->query("SELECT site_name FROM settings ORDER BY id ASC LIMIT 1");
if ($resSettings && $resSettings->num_rows > 0) {
    $row = $resSettings->fetch_assoc();
    $siteName = $row['site_name'];
}

$resultRecent = $conn->query("SELECT b.id, b.judul, b.status, b.tanggal_publikasi, k.nama AS kategori_nama
                              FROM berita b
                              LEFT JOIN kategori k ON k.id = b.kategori_id
                              ORDER BY b.id DESC
                              LIMIT 5");
if ($resultRecent) {
    while ($row = $resultRecent->fetch_assoc()) {
        $recentBerita[] = $row;
    }
}

$publishRate = $totalBerita > 0 ? round(($totalPublish / $totalBerita) * 100) : 0;

include __DIR__ . '/header.php';
?>

<div class="mb-6 overflow-hidden rounded-2xl border border-teal-100 bg-white shadow-xl shadow-teal-100/60">
    <div class="relative p-6 sm:p-8">
        <div class="absolute inset-y-0 right-0 hidden w-1/2 bg-gradient-to-l from-teal-100 via-cyan-50 to-transparent md:block"></div>
        <div class="absolute -right-20 -top-24 h-64 w-64 rounded-full bg-orange-200/40 blur-3xl"></div>
        <div class="relative max-w-3xl">
            <p class="mb-3 text-xs font-black uppercase tracking-[0.22em] text-teal-600">Dashboard Admin</p>
            <h1 class="mb-3 text-3xl font-black tracking-tight text-slate-950 sm:text-4xl">
                Ringkasan <?php echo htmlspecialchars($siteName); ?>
            </h1>
            <p class="max-w-2xl text-sm leading-6 text-slate-600 sm:text-base">
                Pantau kategori, status publikasi, tren berita bulanan, dan aktivitas konten terbaru dari satu layar kerja yang lebih fokus.
            </p>
            <div class="mt-5 flex flex-wrap gap-3">
                <a href="berita?action=add" class="btn btn-primary">Tambah Berita</a>
                <a href="../index" target="_blank" class="btn btn-outline-light">Lihat Portal</a>
            </div>
        </div>
    </div>
</div>

<div class="mb-6 grid gap-4 md:grid-cols-4">
    <div class="rounded-2xl border border-white bg-white p-5 shadow-sm">
        <div class="mb-5 flex items-center justify-between">
            <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-purple-600">Kategori</span>
            <span class="text-sm font-bold text-slate-400">Berita</span>
        </div>
        <div class="text-4xl font-black tracking-tight text-slate-950"><?php echo $totalKategori; ?></div>
        <p class="mt-2 text-sm text-slate-500">Kategori pengelompokan berita.</p>
    </div>
    <div class="rounded-2xl border border-white bg-white p-5 shadow-sm">
        <div class="mb-5 flex items-center justify-between">
            <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-emerald-600">Berita</span>
            <span class="text-sm font-bold text-slate-400">Konten</span>
        </div>
        <div class="text-4xl font-black tracking-tight text-slate-950"><?php echo $totalBerita; ?></div>
        <p class="mt-2 text-sm text-slate-500">Semua artikel yang tersimpan.</p>
    </div>
    <div class="rounded-2xl border border-white bg-white p-5 shadow-sm">
        <div class="mb-5 flex items-center justify-between">
            <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-red-600">Publish</span>
            <span class="text-sm font-bold text-slate-400"><?php echo $publishRate; ?>%</span>
        </div>
        <div class="text-4xl font-black tracking-tight text-slate-950"><?php echo $totalPublish; ?></div>
        <p class="mt-2 text-sm text-slate-500">Artikel yang sudah tampil publik.</p>
    </div>
    <div class="rounded-2xl border border-white bg-white p-5 shadow-sm">
        <div class="mb-5 flex items-center justify-between">
            <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-amber-600">Draft</span>
            <span class="text-sm font-bold text-slate-400">Antrian</span>
        </div>
        <div class="text-4xl font-black tracking-tight text-slate-950"><?php echo $totalDraft; ?></div>
        <p class="mt-2 text-sm text-slate-500">Artikel yang belum dipublikasikan.</p>
    </div>
    <a href="komentar?status=pending" class="rounded-2xl border border-white bg-white p-5 shadow-sm transition hover:shadow-md">
        <div class="mb-5 flex items-center justify-between">
            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-blue-600">Komentar</span>
            <span class="text-sm font-bold text-slate-400"><?php echo (int)$totalKomentarSpam; ?> spam</span>
        </div>
        <div class="text-4xl font-black tracking-tight text-slate-950"><?php echo (int)$totalKomentarPending; ?></div>
        <p class="mt-2 text-sm text-slate-500">Menunggu moderasi. Klik untuk kelola.</p>
    </a>
</div>

<!-- Grafik Berita Publish Per Bulan -->
<div class="mb-6 card">
    <div class="card-body">
        <div class="mb-4">
            <h2 class="h6 mb-1">Berita Terbit Per Bulan</h2>
            <p class="text-muted small mb-0">Jumlah berita yang dipublikasikan dalam 12 bulan terakhir.</p>
        </div>
        <div style="position:relative;height:260px;">
            <canvas id="chartBulanan"></canvas>
        </div>
    </div>
</div>

<div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
    <div class="card">
        <div class="card-body">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <h2 class="h6 mb-1">Berita Terbaru</h2>
                    <p class="text-muted small mb-0">Aktivitas konten terakhir di portal.</p>
                </div>
                <a href="berita" class="btn btn-outline-secondary btn-sm">Kelola</a>
            </div>
            <div class="space-y-3">
                <?php if (empty($recentBerita)): ?>
                    <div class="rounded-xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500">
                        Belum ada berita yang dibuat.
                    </div>
                <?php else: ?>
                    <?php foreach ($recentBerita as $item): ?>
                        <div class="flex items-start justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                            <div>
                                <div class="mb-1 text-xs font-black uppercase tracking-wide text-slate-400">
                                    <?php echo htmlspecialchars($item['kategori_nama'] ?? 'Tanpa kategori'); ?>
                                </div>
                                <a href="berita?action=edit&id=<?php echo (int)$item['id']; ?>" class="font-extrabold text-slate-950 transition hover:text-red-600">
                                    <?php echo htmlspecialchars($item['judul']); ?>
                                </a>
                                <div class="mt-1 text-xs font-medium text-slate-400">
                                    <?php echo $item['tanggal_publikasi'] ? date('d M Y H:i', strtotime($item['tanggal_publikasi'])) : 'Belum dijadwalkan'; ?>
                                </div>
                            </div>
                            <?php if ($item['status'] === 'publish'): ?>
                                <span class="badge bg-success-subtle text-success">Publish</span>
                            <?php else: ?>
                                <span class="badge bg-secondary-subtle text-secondary">Draft</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h6 mb-1">Aksi Cepat</h2>
            <p class="text-muted small mb-4">Pintasan untuk pekerjaan yang sering dilakukan.</p>
            <div class="grid gap-3">
                <a href="berita?action=add" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-teal-200 hover:bg-teal-50">
                    <div class="font-extrabold text-slate-950">Tulis berita baru</div>
                    <div class="mt-1 text-sm text-slate-500">Buat artikel, atur kategori, dan publikasi.</div>
                </a>
                <a href="menu?action=add" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-teal-200 hover:bg-teal-50">
                    <div class="font-extrabold text-slate-950">Tambah menu navigasi</div>
                    <div class="mt-1 text-sm text-slate-500">Susun kategori dan struktur halaman depan.</div>
                </a>
                <a href="kategori" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-teal-200 hover:bg-teal-50">
                    <div class="font-extrabold text-slate-950">Kelola kategori berita</div>
                    <div class="mt-1 text-sm text-slate-500">Tambah, ubah, atau hapus kategori berita.</div>
                </a>
                <a href="settings" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-teal-200 hover:bg-teal-50">
                    <div class="font-extrabold text-slate-950">Perbarui identitas portal</div>
                    <div class="mt-1 text-sm text-slate-500">Ubah nama, tagline, dan logo portal.</div>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var labels = <?php echo json_encode(array_map(function ($d) { return $d['label']; }, $monthlyData)); ?>;
    var data   = <?php echo json_encode(array_map(function ($d) { return $d['count']; }, $monthlyData)); ?>;
    var ctx = document.getElementById('chartBulanan');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Berita Terbit',
                data: data,
                backgroundColor: 'rgba(15, 159, 148, 0.75)',
                borderColor: 'rgba(15, 159, 148, 1)',
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: function (items) { return items[0].label; },
                        label: function (item) { return item.raw + ' berita'; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { weight: '700' },
                        color: '#64748b'
                    },
                    grid: { color: 'rgba(0,0,0,0.04)' }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 0,
                        font: { size: 10, weight: '600' },
                        color: '#64748b',
                        callback: function (val, index) {
                            var parts = labels[index].split(' ');
                            return parts[0].substring(0, 3) + (parts[1] ? " '" + parts[1].substring(2) : '');
                        }
                    },
                    grid: { display: false }
                }
            }
        }
    });
})();
</script>

<?php
include __DIR__ . '/footer.php';
