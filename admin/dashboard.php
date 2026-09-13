<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$totalKategori = 0;
$totalBerita = 0;
$totalPublish = 0;
$totalKomentarPending = 0;
$totalKomentarSpam = 0;

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

$rk = $conn->query("SELECT status, COUNT(*) jml FROM komentar GROUP BY status");
if ($rk) {
    while ($kr = $rk->fetch_assoc()) {
        if ($kr['status'] === 'pending') $totalKomentarPending = (int)$kr['jml'];
        if ($kr['status'] === 'spam') $totalKomentarSpam = (int)$kr['jml'];
    }
}

$publishRate = $totalBerita > 0 ? round(($totalPublish / $totalBerita) * 100) : 0;

include __DIR__ . '/header.php';
?>

<div class="mb-6 grid gap-4 md:grid-cols-4">
    <div class="relative overflow-hidden rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-500 via-purple-500 to-purple-600 p-5 text-white shadow-lg shadow-violet-200/70">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="mb-5 flex items-center justify-between">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black uppercase tracking-wide">Kategori</span>
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/20"><?php echo ui_icon('layers', 'w-5 h-5'); ?></span>
            </div>
            <div class="text-4xl font-black tracking-tight"><?php echo $totalKategori; ?></div>
            <p class="mt-2 text-sm text-white/80">Kategori pengelompokan berita.</p>
        </div>
    </div>
    <div class="relative overflow-hidden rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-500 via-blue-500 to-blue-600 p-5 text-white shadow-lg shadow-sky-200/70">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="mb-5 flex items-center justify-between">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black uppercase tracking-wide">Berita</span>
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/20"><?php echo ui_icon('news', 'w-5 h-5'); ?></span>
            </div>
            <div class="text-4xl font-black tracking-tight"><?php echo $totalBerita; ?></div>
            <p class="mt-2 text-sm text-white/80">Semua artikel yang tersimpan.</p>
        </div>
    </div>
    <div class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-500 via-teal-500 to-teal-600 p-5 text-white shadow-lg shadow-emerald-200/70">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="mb-5 flex items-center justify-between">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black uppercase tracking-wide">Publish</span>
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/20"><?php echo ui_icon('check', 'w-5 h-5'); ?></span>
            </div>
            <div class="text-4xl font-black tracking-tight"><?php echo $totalPublish; ?> <span class="text-xl font-extrabold text-white/70"><?php echo $publishRate; ?>%</span></div>
            <p class="mt-2 text-sm text-white/80">Artikel yang sudah tampil publik.</p>
        </div>
    </div>
    <a href="komentar?status=pending" class="relative overflow-hidden rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-500 via-orange-500 to-orange-600 p-5 text-white shadow-lg shadow-amber-200/70 transition hover:-translate-y-0.5 hover:shadow-xl">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="mb-5 flex items-center justify-between">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black uppercase tracking-wide">Komentar</span>
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/20"><?php echo ui_icon('mail', 'w-5 h-5'); ?></span>
            </div>
            <div class="text-4xl font-black tracking-tight"><?php echo (int)$totalKomentarPending; ?></div>
            <p class="mt-2 text-sm text-white/80"><?php echo (int)$totalKomentarSpam; ?> spam &mdash; klik untuk kelola.</p>
        </div>
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

<div class="card">
    <div class="card-body">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h2 class="h6 mb-1">Aksi Cepat</h2>
                <p class="text-muted small mb-0">Pintasan untuk pekerjaan yang sering dilakukan di portal.</p>
            </div>
            <span class="rounded-full bg-teal-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-teal-700">4 Aksi</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <a href="berita?action=add" class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-xl hover:shadow-teal-100">
                <div class="flex items-start justify-between">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 text-white shadow-lg shadow-teal-200/60"><?php echo ui_icon('plus', 'w-5 h-5'); ?></span>
                    <span class="text-xs font-black uppercase tracking-wide text-teal-600">Editor</span>
                </div>
                <div class="mt-4 font-extrabold text-slate-950">Tulis berita baru</div>
                <div class="mt-1 text-sm text-slate-500">Buat artikel, atur kategori, dan publikasi sekaligus.</div>
            </a>
            <a href="menu?action=add" class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-sky-300 hover:shadow-xl hover:shadow-sky-100">
                <div class="flex items-start justify-between">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-200/60"><?php echo ui_icon('panel', 'w-5 h-5'); ?></span>
                    <span class="text-xs font-black uppercase tracking-wide text-sky-600">Struktur</span>
                </div>
                <div class="mt-4 font-extrabold text-slate-950">Tambah menu navigasi</div>
                <div class="mt-1 text-sm text-slate-500">Susun kategori dan struktur halaman depan.</div>
            </a>
            <a href="kategori" class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-violet-300 hover:shadow-xl hover:shadow-violet-100">
                <div class="flex items-start justify-between">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-200/60"><?php echo ui_icon('layers', 'w-5 h-5'); ?></span>
                    <span class="text-xs font-black uppercase tracking-wide text-violet-600"><?php echo $totalKategori; ?> Item</span>
                </div>
                <div class="mt-4 font-extrabold text-slate-950">Kelola kategori berita</div>
                <div class="mt-1 text-sm text-slate-500">Tambah, ubah, atau hapus kategori berita.</div>
            </a>
            <a href="settings" class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-xl hover:shadow-amber-100">
                <div class="flex items-start justify-between">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-lg shadow-amber-200/60"><?php echo ui_icon('sliders', 'w-5 h-5'); ?></span>
                    <span class="text-xs font-black uppercase tracking-wide text-amber-600">Identitas</span>
                </div>
                <div class="mt-4 font-extrabold text-slate-950">Perbarui identitas portal</div>
                <div class="mt-1 text-sm text-slate-500">Ubah nama, tagline, dan logo portal.</div>
            </a>
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
