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
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-bold text-slate-500">Total Kunjungan</div>
        <div class="mt-2 text-3xl font-black text-slate-950"><?php echo number_format($totalVisits); ?></div>
        <div class="mt-1 text-xs font-semibold text-slate-400"><?php echo htmlspecialchars($periodLabel); ?></div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-bold text-slate-500">Pengunjung Unik</div>
        <div class="mt-2 text-3xl font-black text-slate-950"><?php echo number_format($uniqueVisitors); ?></div>
        <div class="mt-1 text-xs font-semibold text-slate-400">Berdasarkan IP hash</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-bold text-slate-500">Berita Dibaca</div>
        <div class="mt-2 text-3xl font-black text-slate-950"><?php echo number_format($newsReads); ?></div>
        <div class="mt-1 text-xs font-semibold text-slate-400">Kunjungan halaman detail</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm font-bold text-slate-500">Kategori Dikunjungi</div>
        <div class="mt-2 text-3xl font-black text-slate-950"><?php echo number_format($categoryViews); ?></div>
        <div class="mt-1 text-xs font-semibold text-slate-400">Kategori dan detail berita</div>
    </div>
</div>

<?php
$blocks = [
    'Negara Pengunjung' => $countryStats,
    'OS yang Digunakan' => $osStats,
    'Kategori Paling Banyak Dikunjungi' => $categoryStats,
    'Berita yang Dibaca' => $newsStats,
];
?>

<div class="grid gap-5 xl:grid-cols-2">
    <?php foreach ($blocks as $title => $rows): ?>
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="m-0 text-base font-extrabold text-slate-950"><?php echo htmlspecialchars($title); ?></h2>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-500">Top 10</span>
            </div>
            <?php if (empty($rows)): ?>
                <div class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm font-semibold text-slate-400">Belum ada data pengunjung.</div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($rows as $row): ?>
                        <?php $percent = $totalVisits > 0 ? min(100, round(((int)$row['total'] / $totalVisits) * 100)) : 0; ?>
                        <div>
                            <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                <span class="line-clamp-1 font-bold text-slate-700"><?php echo htmlspecialchars($row['label'] ?? 'Tidak diketahui'); ?></span>
                                <span class="font-black text-slate-950"><?php echo number_format((int)$row['total']); ?></span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-teal-500 to-purple-500" style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>

<div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-800">
    Negara diperkirakan dari bahasa browser pengunjung. Untuk akurasi negara berbasis IP, website perlu memakai layanan GeoIP atau CDN yang mengirim header negara.
</div>

<?php include __DIR__ . '/footer.php'; ?>
