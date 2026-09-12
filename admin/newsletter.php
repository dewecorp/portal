<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$conn->query("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => !empty($ok)]);
        exit;
    }
    header('Location: newsletter' . ($ok ? '?success=1' : '?err=1'));
    exit;
}

$list = [];
$res = $conn->query("SELECT * FROM newsletter_subscribers ORDER BY id DESC LIMIT 500");
if ($res) while ($r = $res->fetch_assoc()) $list[] = $r;

include __DIR__ . '/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Newsletter (<?php echo count($list); ?>)</h1>
    <a href="sections?area=footer" class="btn btn-outline-secondary btn-sm">Kelola Widget Footer</a>
</div>
<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Berhasil!', timer: 1800, showConfirmButton: false }); });</script>
<?php endif; ?>
<div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="table-responsive">
<table class="table align-middle table-sm">
<thead><tr><th width="60">#</th><th>Email</th><th width="200">Tanggal</th><th width="120" class="text-end">Aksi</th></tr></thead>
<tbody>
<?php if (empty($list)): ?><tr><td colspan="4" class="text-center text-muted py-3">Belum ada pendaftar.</td></tr>
<?php else: foreach ($list as $i => $s): ?>
<tr><td><?php echo $i + 1; ?></td><td><?php echo htmlspecialchars($s['email']); ?></td><td><?php echo htmlspecialchars($s['created_at']); ?></td>
<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-del-nl" data-id="<?php echo (int)$s['id']; ?>">Hapus</button></td></tr>
<?php endforeach; endif; ?>
</tbody></table>
</div></div></div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-del-nl').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            Swal.fire({ title: 'Hapus Email?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then(function(r) {
                if (!r.isConfirmed) return;
                fetch('newsletter?action=delete&id=' + id, { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function(x) { return x.json(); }).then(function(d) {
                    if (d.success) Swal.fire({ icon: 'success', title: 'Dihapus', timer: 1500, showConfirmButton: false }).then(function() { location.reload(); });
                    else Swal.fire({ icon: 'error', title: 'Gagal' });
                });
            });
        });
    });
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
