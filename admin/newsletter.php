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
    if ($ok) admin_log($conn, 'delete', "Menghapus subscriber newsletter ID $id");
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
</div>
<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Berhasil!', timer: 1800, showConfirmButton: false }); });</script>
<?php endif; ?>

<!-- Daftar Subscriber -->
<div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="table-responsive">
<table class="table align-middle table-sm">
<thead><tr><th width="60">#</th><th>Email</th><th width="200">Tanggal</th><th width="120" class="text-end">Aksi</th></tr></thead>
<tbody>
<?php if (empty($list)): ?><tr><td colspan="4" class="text-center text-muted py-3">Belum ada pendaftar.</td></tr>
<?php else: foreach ($list as $i => $s): ?>
<tr><td><?php echo $i + 1; ?></td><td><?php echo htmlspecialchars($s['email']); ?></td><td><?php echo htmlspecialchars($s['created_at']); ?></td>
<td class="text-end"><button type="button" class="btn-icon btn-del btn-del-nl" title="Hapus" aria-label="Hapus" data-id="<?php echo (int)$s['id']; ?>"><?php echo ui_icon('trash', 'w-5 h-5'); ?></button></td></tr>
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

<style>
    @media (max-width: 767px) {
        .table { font-size: 0.8rem; }
        .table th, .table td { padding: 0.6rem 0.4rem; }
        .btn-icon { width: 1.8rem !important; height: 1.8rem !important; }
        .btn-icon svg { width: 0.85rem !important; height: 0.85rem !important; }
        .badge { font-size: 0.65rem; padding: 0.25rem 0.5rem; }
        .btn-primary { padding: 0.5rem 1rem; font-size: 0.8rem; }
        .h4 { font-size: 1.3rem; }
        .p-5 { padding: 1rem; }
        input.form-control, select.form-select { font-size: 0.9rem; }
        .form-label { font-size: 0.8rem; }
        .text-4xl { font-size: clamp(1.5rem, 5vw, 2.25rem); }
        .modal { padding: 1rem 0.5rem; }
    }
</style>

<?php include __DIR__ . '/footer.php'; ?>
