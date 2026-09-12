<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$filter = $_GET['status'] ?? 'all';
if (!in_array($filter, ['all', 'pending', 'approved', 'spam'], true)) $filter = 'all';

if (in_array($action, ['approve', 'spam', 'delete'], true) && $id > 0) {
    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM komentar WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
    } else {
        $st = $action === 'approve' ? 'approved' : 'spam';
        $stmt = $conn->prepare("UPDATE komentar SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $st, $id);
        $ok = $stmt->execute();
        $stmt->close();
    }
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => !empty($ok)]);
        exit;
    }
    header('Location: komentar?status=' . urlencode($filter) . (empty($ok) ? '&err=1' : '&success=1'));
    exit;
}

$where = $filter === 'all' ? '' : "WHERE k.status = '" . $conn->real_escape_string($filter) . "'";
$sql = "SELECT k.*, b.judul AS berita_judul, b.slug AS berita_slug FROM komentar k LEFT JOIN berita b ON b.id = k.berita_id $where ORDER BY k.id DESC LIMIT 200";
$list = [];
$res = $conn->query($sql);
if ($res) while ($r = $res->fetch_assoc()) $list[] = $r;

$counts = ['pending' => 0, 'approved' => 0, 'spam' => 0];
$rc = $conn->query("SELECT status, COUNT(*) jml FROM komentar GROUP BY status");
if ($rc) while ($r = $rc->fetch_assoc()) $counts[$r['status']] = (int)$r['jml'];

include __DIR__ . '/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Komentar</h1>
    <div class="d-flex gap-3">
        <?php foreach (['all' => 'Semua', 'pending' => 'Pending (' . $counts['pending'] . ')', 'approved' => 'Disetujui', 'spam' => 'Spam (' . $counts['spam'] . ')'] as $key => $label): ?>
        <a href="komentar?status=<?php echo $key; ?>" class="btn btn-sm <?php echo $filter === $key ? 'btn-primary' : 'btn-outline-secondary'; ?>"><?php echo $label; ?></a>
        <?php endforeach; ?>
    </div>
</div>
<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Berhasil!', timer: 1800, showConfirmButton: false }); });</script>
<?php endif; ?>
<div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="table-responsive">
<table class="table align-middle table-sm">
<thead><tr><th width="50">#</th><th>Komentar</th><th width="220">Berita</th><th width="110">Status</th><th width="200" class="text-end">Aksi</th></tr></thead>
<tbody>
<?php if (empty($list)): ?><tr><td colspan="5" class="text-center text-muted py-3">Belum ada komentar.</td></tr>
<?php else: foreach ($list as $i => $km): ?>
<tr>
<td><?php echo $i + 1; ?></td>
<td><div class="font-extrabold text-slate-950"><?php echo htmlspecialchars($km['nama']); ?> <span class="small text-muted"><?php echo htmlspecialchars($km['email'] ?? ''); ?></span></div>
<div class="small"><?php echo nl2br(htmlspecialchars(mb_substr($km['isi'], 0, 300))); ?></div>
<div class="small text-muted"><?php echo htmlspecialchars($km['created_at']); ?></div></td>
<td><a href="../<?php echo htmlspecialchars(berita_url(['slug' => $km['berita_slug'] ?? '', 'id' => $km['berita_id']])); ?>" target="_blank"><?php echo htmlspecialchars(mb_substr($km['berita_judul'] ?? ('#' . $km['berita_id']), 0, 60)); ?></a></td>
<td><span class="badge <?php echo $km['status'] === 'approved' ? 'bg-success-subtle text-success' : ($km['status'] === 'spam' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary'); ?>"><?php echo htmlspecialchars($km['status']); ?></span></td>
<td class="text-end">
<?php if ($km['status'] !== 'approved'): ?><a class="btn btn-sm btn-outline-primary" href="komentar?action=approve&id=<?php echo (int)$km['id']; ?>&status=<?php echo urlencode($filter); ?>">Setujui</a><?php endif; ?>
<?php if ($km['status'] !== 'spam'): ?><a class="btn btn-sm btn-outline-secondary" href="komentar?action=spam&id=<?php echo (int)$km['id']; ?>&status=<?php echo urlencode($filter); ?>">Spam</a><?php endif; ?>
<button type="button" class="btn btn-sm btn-outline-danger btn-del-km" data-id="<?php echo (int)$km['id']; ?>">Hapus</button>
</td></tr>
<?php endforeach; endif; ?>
</tbody></table>
</div></div></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-del-km').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            Swal.fire({ title: 'Hapus Komentar?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then(function(r) {
                if (!r.isConfirmed) return;
                fetch('komentar?action=delete&id=' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function(x) { return x.json(); }).then(function(d) {
                    if (d.success) Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
                    else Swal.fire({ icon: 'error', title: 'Gagal' });
                });
            });
        });
    });
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
