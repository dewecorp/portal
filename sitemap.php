<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/xml; charset=utf-8');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$base = rtrim(str_replace('\\', '/', $base), '/') . '/';
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<url><loc><?php echo htmlspecialchars($base); ?></loc><changefreq>hourly</changefreq><priority>1.0</priority></url>
<?php
$res = $conn->query("SELECT slug, id, updated_at, tanggal_publikasi FROM berita WHERE status='publish' ORDER BY id DESC LIMIT 500");
if ($res) while ($r = $res->fetch_assoc()):
    $slug = trim((string)($r['slug'] ?? ''));
    $loc = $slug !== '' ? $base . rawurlencode($slug) : $base . 'detail?id=' . (int)$r['id'];
    $last = $r['updated_at'] ?? $r['tanggal_publikasi'] ?? null;
?>
<url><loc><?php echo htmlspecialchars($loc); ?></loc><?php if ($last): ?><lastmod><?php echo date('Y-m-d', strtotime($last)); ?></lastmod><?php endif; ?><changefreq>daily</changefreq><priority>0.8</priority></url>
<?php endwhile; ?>
</urlset>
