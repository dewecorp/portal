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

// Bulk delete
if ($action === 'bulk_delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = (string)($_POST['ids'] ?? '');
    $ids = array_values(array_filter(array_map('intval', explode(',', $raw))));
    $deleted = 0;
    foreach ($ids as $bid) {
        if ($bid <= 0) continue;
        $stmt = $conn->prepare("DELETE FROM komentar WHERE id = ?");
        $stmt->bind_param('i', $bid);
        if ($stmt->execute() && $stmt->affected_rows > 0) $deleted++;
        $stmt->close();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'deleted' => $deleted, 'message' => "Berhasil menghapus $deleted komentar."]);
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
<div class="mb-3 d-flex align-items-center gap-3">
    <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
        <?php echo ui_icon('trash', 'w-4 h-4'); ?> Hapus Terpilih (<span id="bulkCount">0</span>)
    </button>
    <span class="small text-muted">Centang komentar yang ingin dihapus.</span>
</div>
<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Berhasil!', timer: 1800, showConfirmButton: false }); });</script>
<?php endif; ?>
<div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="table-responsive">
<table class="table align-middle table-sm">
<thead><tr><th width="40"><input type="checkbox" class="form-check-input" id="bulkAll" title="Pilih semua"></th><th width="50">#</th><th>Komentar</th><th width="220">Berita</th><th width="110">Status</th><th width="200" class="text-end">Aksi</th></tr></thead>
<tbody>
<?php if (empty($list)): ?><tr><td colspan="6" class="text-center text-muted py-3">Belum ada komentar.</td></tr>
<?php else: foreach ($list as $i => $km): ?>
<tr>
<td><input type="checkbox" class="form-check-input bulk-cb" value="<?php echo (int)$km['id']; ?>"></td>
<td><?php echo $i + 1; ?></td>
<td><div class="font-extrabold text-slate-950"><?php echo htmlspecialchars($km['nama']); ?> <span class="small text-muted"><?php echo htmlspecialchars($km['email'] ?? ''); ?></span></div>
<div class="small"><?php echo nl2br(htmlspecialchars(mb_substr($km['isi'], 0, 300))); ?></div>
<div class="small text-muted"><?php echo htmlspecialchars($km['created_at']); ?></div></td>
<td><a href="../<?php echo htmlspecialchars(berita_url(['slug' => $km['berita_slug'] ?? '', 'id' => $km['berita_id']])); ?>" target="_blank"><?php echo htmlspecialchars(mb_substr($km['berita_judul'] ?? ('#' . $km['berita_id']), 0, 60)); ?></a></td>
<td><span class="badge <?php echo $km['status'] === 'approved' ? 'bg-success-subtle text-success' : ($km['status'] === 'spam' ? 'bg-info-subtle text-info' : 'bg-secondary-subtle text-secondary'); ?>"><?php echo htmlspecialchars($km['status']); ?></span></td>
<td class="text-end">
<div class="d-inline-flex gap-3" style="justify-content:flex-end;">
<?php if ($km['status'] !== 'approved'): ?><a class="btn-icon btn-ok" title="Setujui" aria-label="Setujui" href="komentar?action=approve&id=<?php echo (int)$km['id']; ?>&status=<?php echo urlencode($filter); ?>"><?php echo ui_icon('check', 'w-5 h-5'); ?></a><?php endif; ?>
<?php if ($km['status'] !== 'spam'): ?><a class="btn-icon" title="Tandai spam" aria-label="Tandai spam" href="komentar?action=spam&id=<?php echo (int)$km['id']; ?>&status=<?php echo urlencode($filter); ?>"><?php echo ui_icon('ban', 'w-5 h-5'); ?></a><?php endif; ?>
<button type="button" class="btn-icon btn-del btn-del-km" title="Hapus" aria-label="Hapus" data-id="<?php echo (int)$km['id']; ?>"><?php echo ui_icon('trash', 'w-5 h-5'); ?></button>
</div>
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

    var cbs = Array.prototype.slice.call(document.querySelectorAll('.bulk-cb'));
    var all = document.getElementById('bulkAll');
    var btn = document.getElementById('bulkDeleteBtn');
    var countEl = document.getElementById('bulkCount');

    function refresh() {
        var sel = cbs.filter(function (c) { return c.checked; }).length;
        if (all) all.checked = cbs.length > 0 && sel === cbs.length;
        if (countEl) countEl.textContent = sel;
        if (btn) btn.disabled = sel === 0;
    }

    cbs.forEach(function (c) { c.addEventListener('change', refresh); });
    if (all) all.addEventListener('change', function () {
        cbs.forEach(function (c) { c.checked = all.checked; });
        refresh();
    });

    if (btn) btn.addEventListener('click', function () {
        var sel = cbs.filter(function (c) { return c.checked; }).map(function (c) { return c.value; });
        if (!sel.length) return;
        Swal.fire({
            title: 'Hapus ' + sel.length + ' Komentar?',
            text: 'Komentar yang dipilih akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (!result.isConfirmed) return;
            fetch('komentar?action=bulk_delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'ids=' + encodeURIComponent(sel.join(','))
            }).then(function (x) { return x.json(); }).then(function (d) {
                if (d.success) {
                    Swal.fire({ icon: 'success', title: 'Berhasil!', text: d.message || 'Komentar berhasil dihapus', timer: 1800, showConfirmButton: false }).then(function () { location.reload(); });
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal!', text: d.error || 'Gagal menghapus komentar' });
                }
            });
        });
    });

    refresh();
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
