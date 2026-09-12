<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/rss+xml; charset=utf-8');
$site = $settings['site_name'] ?? 'Portal Berita';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$base = rtrim(str_replace('\\', '/', $base), '/') . '/';
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0">
<channel>
<title><?php echo htmlspecialchars($site); ?></title>
<link><?php echo htmlspecialchars($base); ?></link>
<description><?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?></description>
<language>id-ID</language>
<?php
$res = $conn->query("SELECT b.judul, b.slug, b.id, b.ringkasan, b.tanggal_publikasi FROM berita b WHERE b.status='publish' ORDER BY b.tanggal_publikasi DESC, b.id DESC LIMIT 20");
if ($res) while ($r = $res->fetch_assoc()):
    $slug = trim((string)($r['slug'] ?? ''));
    $link = $slug !== '' ? $base . rawurlencode($slug) : $base . 'detail?id=' . (int)$r['id'];
    $pub = !empty($r['tanggal_publikasi']) ? date(DATE_RSS, strtotime($r['tanggal_publikasi'])) : date(DATE_RSS);
?>
<item>
<title><?php echo htmlspecialchars($r['judul']); ?></title>
<link><?php echo htmlspecialchars($link); ?></link>
<guid><?php echo htmlspecialchars($link); ?></guid>
<pubDate><?php echo $pub; ?></pubDate>
<description><![CDATA[<?php echo mb_substr(strip_tags((string)($r['ringkasan'] ?? '')), 0, 300); ?>]]></description>
</item>
<?php endwhile; ?>
</channel>
</rss>
