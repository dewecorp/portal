<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

ensure_visitor_logs_table($conn);

$allowedPeriods = [7, 30, 90, 0];
$period = isset($_GET['period']) ? (int)$_GET['period'] : 30;
if (!in_array($period, $allowedPeriods, true)) {
    $period = 30;
}

$where = $period > 0 ? "WHERE visited_at >= DATE_SUB(NOW(), INTERVAL $period DAY)" : '';
$periodLabel = $period > 0 ? $period . ' hari terakhir' : 'Semua data';

function visitor_scalar(mysqli $conn, string $sql): int
{
    $result = $conn->query($sql);
    if (!$result) return 0;
    $row = $result->fetch_assoc();
    return (int)($row['total'] ?? 0);
}

function visitor_rows(mysqli $conn, string $sql): array
{
    $rows = [];
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

$totalVisits = visitor_scalar($conn, "SELECT COUNT(*) AS total FROM visitor_logs $where");
$uniqueVisitors = visitor_scalar($conn, "SELECT COUNT(DISTINCT ip_hash) AS total FROM visitor_logs $where");
$newsReads = visitor_scalar($conn, "SELECT COUNT(*) AS total FROM visitor_logs $where" . ($where ? " AND" : " WHERE") . " berita_id IS NOT NULL");
$categoryViews = visitor_scalar($conn, "SELECT COUNT(*) AS total FROM visitor_logs $where" . ($where ? " AND" : " WHERE") . " kategori_id IS NOT NULL");

$countryStats = visitor_rows($conn, "SELECT country AS label, COUNT(*) AS total FROM visitor_logs $where GROUP BY country ORDER BY total DESC, country ASC LIMIT 10");
$osStats = visitor_rows($conn, "SELECT os AS label, COUNT(*) AS total FROM visitor_logs $where GROUP BY os ORDER BY total DESC, os ASC LIMIT 10");
$categoryStats = visitor_rows($conn, "SELECT COALESCE(k.nama, 'Tidak diketahui') AS label, COUNT(v.id) AS total
                                      FROM visitor_logs v
                                      LEFT JOIN kategori k ON k.id = v.kategori_id
                                      $where" . ($where ? " AND" : " WHERE") . " v.kategori_id IS NOT NULL
                                      GROUP BY v.kategori_id, k.nama
                                      ORDER BY total DESC, label ASC
                                      LIMIT 10");
$newsStats = visitor_rows($conn, "SELECT COALESCE(b.judul, 'Berita dihapus') AS label, COUNT(v.id) AS total
                                  FROM visitor_logs v
                                  LEFT JOIN berita b ON b.id = v.berita_id
                                  $where" . ($where ? " AND" : " WHERE") . " v.berita_id IS NOT NULL
                                  GROUP BY v.berita_id, b.judul
                                  ORDER BY total DESC, label ASC
                                  LIMIT 10");

include __DIR__ . '/header.php';
?>

<div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="m-0 text-2xl font-extrabold text-slate-950">Data Pengunjung</h1>
        <p class="mt-1 text-sm font-medium text-slate-500">Statistik pengunjung berdasarkan negara, OS, kategori, dan berita yang dibaca.</p>
    </div>
    <form method="get" class="flex items-center gap-2">
        <label for="period" class="text-sm font-bold text-slate-600">Periode</label>
        <select id="period" name="period" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-bold text-slate-700 shadow-sm" onchange="this.form.submit()">
            <option value="7" <?php echo $period === 7 ? 'selected' : ''; ?>>7 hari</option>
            <option value="30" <?php echo $period === 30 ? 'selected' : ''; ?>>30 hari</option>
            <option value="90" <?php echo $period === 90 ? 'selected' : ''; ?>>90 hari</option>
            <option value="0" <?php echo $period === 0 ? 'selected' : ''; ?>>Semua data</option>
        </select>
    </form>
</div>

<div class="mb-6 grid gap-4 md:grid-cols-4">
    <div class="relative overflow-hidden rounded-2xl border border-sky-100 bg-gradient-to-br from-sky-500 via-blue-500 to-blue-600 p-5 text-white shadow-lg shadow-sky-200/70 transition hover:-translate-y-0.5 hover:shadow-xl">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="text-sm font-bold text-white/80">Total Kunjungan</div>
            <div class="mt-2 text-3xl font-black text-white"><?php echo number_format($totalVisits); ?></div>
        </div>
    </div>
    <div class="relative overflow-hidden rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-500 via-teal-500 to-teal-600 p-5 text-white shadow-lg shadow-emerald-200/70 transition hover:-translate-y-0.5 hover:shadow-xl">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="text-sm font-bold text-white/80">Pengunjung Unik</div>
            <div class="mt-2 text-3xl font-black text-white"><?php echo number_format($uniqueVisitors); ?></div>
        </div>
    </div>
    <div class="relative overflow-hidden rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-500 via-orange-500 to-orange-600 p-5 text-white shadow-lg shadow-amber-200/70 transition hover:-translate-y-0.5 hover:shadow-xl">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="text-sm font-bold text-white/80">Berita Dibaca</div>
            <div class="mt-2 text-3xl font-black text-white"><?php echo number_format($newsReads); ?></div>
        </div>
    </div>
    <div class="relative overflow-hidden rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-500 via-purple-500 to-purple-600 p-5 text-white shadow-lg shadow-violet-200/70 transition hover:-translate-y-0.5 hover:shadow-xl">
        <div class="absolute -right-7 -top-7 h-28 w-28 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-9 -left-9 h-24 w-24 rounded-full bg-white/5"></div>
        <div class="relative">
            <div class="text-sm font-bold text-white/80">Kategori Dikunjungi</div>
            <div class="mt-2 text-3xl font-black text-white"><?php echo number_format($categoryViews); ?></div>
        </div>
    </div>
</div>

<?php
$palette = ['#0d9488', '#6366f1', '#f59e0b', '#ef4444', '#84cc16', '#06b6d4', '#8b5cf6', '#ec4899', '#f97316', '#475569'];

$chartData = [];
foreach (['Negara Pengunjung' => $countryStats, 'OS yang Digunakan' => $osStats, 'Kategori Paling Banyak Dikunjungi' => $categoryStats, 'Berita yang Dibaca' => $newsStats] as $bTitle => $bRows) {
    $labels = [];
    $vals = [];
    foreach ($bRows as $r) {
        $labels[] = $r['label'] ?? 'Tidak diketahui';
        $vals[] = (int)$r['total'];
    }
    $chartData[$bTitle] = ['labels' => $labels, 'data' => $vals];
}
?>

<div class="grid gap-5 xl:grid-cols-2">
    <?php $bi = 0; foreach ($chartData as $title => $chart): ?>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="m-0 text-base font-extrabold text-slate-950"><?php echo htmlspecialchars($title); ?></h2>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Top 10</span>
            </div>
            <?php if (empty($chart['data'])): ?>
                <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-400">Belum ada data pengunjung.</div>
            <?php else: ?>
                <div class="mb-4" style="position:relative;height:190px;">
                    <canvas data-pie="<?php echo $bi; ?>"></canvas>
                </div>
                <div class="space-y-2">
                    <?php foreach ($chart['labels'] as $li => $label): ?>
                        <?php $color = $palette[$li % count($palette)]; ?>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex min-w-0 items-center gap-2">
                                <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" style="background:<?php echo $color; ?>;"></span>
                                <span class="line-clamp-1 font-bold text-slate-700"><?php echo htmlspecialchars($label); ?></span>
                            </span>
                            <span class="font-black text-slate-950"><?php echo number_format((int)$chart['data'][$li]); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php $bi++; endforeach; ?>
</div>

<div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">
    Negara diperkirakan dari bahasa browser pengunjung. Untuk akurasi negara berbasis IP, website perlu memakai layanan GeoIP atau CDN yang mengirim header negara.
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
(function () {
    var pieData = <?php echo json_encode(array_values($chartData), JSON_UNESCAPED_UNICODE); ?>;
    var palette = <?php echo json_encode($palette); ?>;
    var canvases = document.querySelectorAll('canvas[data-pie]');
    if (!window.Chart || !canvases.length) return;
    canvases.forEach(function (cv) {
        var item = pieData[parseInt(cv.getAttribute('data-pie'), 10)];
        if (!item || !item.data.length) return;
        var colors = item.data.map(function (_, i) { return palette[i % palette.length]; });
        new Chart(cv, {
            type: 'doughnut',
            data: {
                labels: item.labels,
                datasets: [{ data: item.data, backgroundColor: colors, borderColor: '#ffffff', borderWidth: 2 }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total > 0 ? Math.round(ctx.parsed * 100 / total) : 0;
                                return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    });
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
