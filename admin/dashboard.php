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

$logs = [];
$logsResult = $conn->query("SELECT admin_logs.*, admin_users.username AS admin_username
                             FROM admin_logs
                             LEFT JOIN admin_users ON admin_users.id = admin_logs.admin_id
                             ORDER BY admin_logs.id DESC LIMIT 50");
if ($logsResult) {
    while ($lr = $logsResult->fetch_assoc()) {
        $logs[] = $lr;
    }
}
$activityCount = count($logs);

include __DIR__ . '/header.php';
?>
<?php if (isset($_GET['login']) && $_GET['login'] === 'success'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.Swal) {
        Swal.fire({ icon: 'success', title: 'Login Berhasil!', text: 'Selamat datang kembali.', timer: 2200, timerProgressBar: true, showConfirmButton: false });
    }
});
</script>
<?php endif; ?>

<div class="mb-6 stat-grid">
    <div class="relative overflow-hidden rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-500 via-purple-500 to-purple-600 p-5 text-white shadow-lg shadow-violet-200/70 transition hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-violet-300/50">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="mb-5 flex items-center justify-between">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black uppercase tracking-wide">Kategori</span>
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/20"><?php echo ui_icon('layers', 'w-5 h-5'); ?></span>
            </div>
            <div class="text-4xl font-black tracking-tight" style="font-size: clamp(1.5rem, 5vw, 2.25rem);"><?php echo $totalKategori; ?></div>
        </div>
    </div>
    <div class="relative overflow-hidden rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-500 via-blue-500 to-blue-600 p-5 text-white shadow-lg shadow-sky-200/70 transition hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-sky-300/50">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="mb-5 flex items-center justify-between">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black uppercase tracking-wide">Berita</span>
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/20"><?php echo ui_icon('news', 'w-5 h-5'); ?></span>
            </div>
            <div class="text-4xl font-black tracking-tight" style="font-size: clamp(1.5rem, 5vw, 2.25rem);"><?php echo $totalBerita; ?></div>
        </div>
    </div>
    <div class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-500 via-teal-500 to-teal-600 p-5 text-white shadow-lg shadow-emerald-200/70 transition hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-emerald-300/50">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="mb-5 flex items-center justify-between">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black uppercase tracking-wide">Publish</span>
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/20"><?php echo ui_icon('check', 'w-5 h-5'); ?></span>
            </div>
            <div class="text-4xl font-black tracking-tight" style="font-size: clamp(1.5rem, 5vw, 2.25rem);"><?php echo $totalPublish; ?> <span class="text-xl font-extrabold text-white/70" style="font-size: clamp(0.875rem, 3vw, 1.25rem);"><?php echo $publishRate; ?>%</span></div>
        </div>
    </div>
    <a href="komentar?status=pending" class="relative overflow-hidden rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-500 via-orange-500 to-orange-600 p-5 text-white shadow-lg shadow-amber-200/70 transition hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-amber-300/50">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="mb-5 flex items-center justify-between">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black uppercase tracking-wide">Komentar</span>
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-white/20"><?php echo ui_icon('mail', 'w-5 h-5'); ?></span>
            </div>
            <div class="text-4xl font-black tracking-tight" style="font-size: clamp(1.5rem, 5vw, 2.25rem);"><?php echo (int)$totalKomentarPending; ?></div>
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
        <div class="quick-grid">
            <a href="berita?action=add" class="group rounded-2xl border border-slate-200 bg-white p-5 transition duration-200 hover:-translate-y-1.5 hover:border-teal-300 hover:shadow-xl hover:shadow-teal-100">
                <div class="flex items-start justify-between">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-teal-500 to-teal-600 text-white shadow-lg shadow-teal-200/60"><?php echo ui_icon('plus', 'w-5 h-5'); ?></span>
                    <span class="text-xs font-black uppercase tracking-wide text-teal-600">Editor</span>
                </div>
                <div class="mt-4 font-extrabold text-slate-950">Tulis berita baru</div>
                <div class="mt-1 text-sm text-slate-500">Buat artikel, atur kategori, dan publikasi sekaligus.</div>
            </a>
            <a href="menu?action=add" class="group rounded-2xl border border-slate-200 bg-white p-5 transition duration-200 hover:-translate-y-1.5 hover:border-sky-300 hover:shadow-xl hover:shadow-sky-100">
                <div class="flex items-start justify-between">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 text-white shadow-lg shadow-sky-200/60"><?php echo ui_icon('panel', 'w-5 h-5'); ?></span>
                    <span class="text-xs font-black uppercase tracking-wide text-sky-600">Struktur</span>
                </div>
                <div class="mt-4 font-extrabold text-slate-950">Tambah menu navigasi</div>
                <div class="mt-1 text-sm text-slate-500">Susun kategori dan struktur halaman depan.</div>
            </a>
            <a href="kategori" class="group rounded-2xl border border-slate-200 bg-white p-5 transition duration-200 hover:-translate-y-1.5 hover:border-violet-300 hover:shadow-xl hover:shadow-violet-100">
                <div class="flex items-start justify-between">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 text-white shadow-lg shadow-violet-200/60"><?php echo ui_icon('layers', 'w-5 h-5'); ?></span>
                    <span class="text-xs font-black uppercase tracking-wide text-violet-600"><?php echo $totalKategori; ?> Item</span>
                </div>
                <div class="mt-4 font-extrabold text-slate-950">Kelola kategori berita</div>
                <div class="mt-1 text-sm text-slate-500">Tambah, ubah, atau hapus kategori berita.</div>
            </a>
            <a href="settings" class="group rounded-2xl border border-slate-200 bg-white p-5 transition duration-200 hover:-translate-y-1.5 hover:border-amber-300 hover:shadow-xl hover:shadow-amber-100">
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

<section class="mt-8 mb-6 card">
    <div class="card-body">
        <div class="mb-6 flex items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
                <h2 class="h6 mb-1">Aktivitas Admin</h2>
                <p class="text-muted small mb-0">Catatan CRUD, login, dan logout secara real-time. Otomatis terhapus setelah 24 jam.</p>
            </div>
            <?php if ($activityCount > 0): ?>
                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-black uppercase tracking-wide text-rose-600 border border-rose-100 shadow-sm"><?php echo $activityCount; ?> Aktivitas</span>
            <?php endif; ?>
        </div>
        <?php if (empty($logs)): ?>
            <div class="rounded-2xl border border-dashed border-slate-200 p-8 text-center text-sm font-semibold text-slate-400">Belum ada aktivitas tercatat hari ini.</div>
        <?php else: ?>
            <div class="relative ml-2 pl-12 pr-2 space-y-8 py-2" style="max-height:420px;overflow-y:auto;">
                <div class="absolute left-[19px] top-3 bottom-3 w-0.5 bg-slate-100 rounded" aria-hidden="true"></div>
                <?php foreach ($logs as $log):
                    $action = $log['action'] ?? '';
                    $details = htmlspecialchars($log['details'] ?? '');
                    $time = time_ago($log['created_at'] ?? '');
                    $badge = match ($action) {
                        'login' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'logout' => 'bg-slate-50 text-slate-600 border-slate-200',
                        'add' => 'bg-sky-50 text-sky-700 border-sky-200',
                        'edit', 'update' => 'bg-amber-50 text-amber-700 border-amber-200',
                        'delete' => 'bg-rose-50 text-rose-700 border-rose-200',
                        default => 'bg-slate-50 text-slate-600 border-slate-200',
                    };
                    $icon = match ($action) {
                        'login' => 'user',
                        'logout' => 'exit',
                        'add' => 'plus',
                        'edit', 'update' => 'edit',
                        'delete' => 'trash',
                        default => 'info',
                    };
                    $iconColor = match ($action) {
                        'login' => 'text-emerald-500',
                        'add' => 'text-sky-500',
                        'edit', 'update' => 'text-amber-500',
                        'delete' => 'text-rose-500',
                        default => 'text-slate-400',
                    };
                ?>
                    <div class="relative">
                        <!-- Dot on timeline -->
                        <span class="absolute -left-12 top-0 flex h-8 w-8 items-center justify-center rounded-full border-2 border-white bg-slate-50 shadow-sm transition group-hover:scale-110">
                            <?php echo ui_icon($icon, 'w-3.5 h-3.5 ' . $iconColor); ?>
                        </span>
                        
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-wider <?php echo $badge; ?>">
                                    <?php echo htmlspecialchars($action); ?>
                                </span>
                                <span class="text-xs font-bold text-slate-400"><?php echo $time; ?></span>
                            </div>
                            <div class="text-sm font-bold text-slate-900 break-words"><?php echo $details; ?></div>
                            <div class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-400">
                                <?php echo ui_icon('user', 'w-3 h-3'); ?>
                                <?php echo htmlspecialchars($log['admin_username'] ?? 'Admin'); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

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

<style>
    @media (max-width: 767px) {
        .p-5 { padding: 1rem; }
        .relative.ml-2.pl-12 { margin-left: 0.25rem; padding-left: 2rem; }
        .absolute.-left-12 { left: -2rem; }
    }
</style>

<?php
include __DIR__ . '/footer.php';
