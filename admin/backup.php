<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

$backupDir = __DIR__ . '/../backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$action = $_GET['action'] ?? '';
$file = basename($_GET['file'] ?? '');

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = 'backup_' . date('Ymd_His') . '.sql';
    $path = $backupDir . '/' . $name;
    $fh = fopen($path, 'w');
    if ($fh) {
        fwrite($fh, "-- Backup portal_berita " . date('Y-m-d H:i:s') . "\n\n");
        $res = $conn->query("SHOW TABLES");
        $tables = [];
        if ($res) while ($r = $res->fetch_row()) $tables[] = $r[0];
        foreach ($tables as $t) {
            $cr = $conn->query("SHOW CREATE TABLE `$t`");
            if ($cr && ($row = $cr->fetch_row())) {
                fwrite($fh, "DROP TABLE IF EXISTS `$t`;\n" . $row[1] . ";\n\n");
            }
            $dr = $conn->query("SELECT * FROM `$t`");
            if ($dr) {
                while ($row = $dr->fetch_assoc()) {
                    $cols = array_map(function ($c) use ($conn) { return "`$c`"; }, array_keys($row));
                    $vals = array_map(function ($v) use ($conn) {
                        return $v === null ? 'NULL' : "'" . $conn->real_escape_string((string)$v) . "'";
                    }, array_values($row));
                    fwrite($fh, "INSERT INTO `$t` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n");
                }
                fwrite($fh, "\n");
            }
        }
        fclose($fh);
        admin_log($conn, 'add', "Membuat backup database: $name");
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'file' => $name]);
            exit;
        }
        header('Location: backup?success=create');
        exit;
    }
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Gagal menulis file backup.']);
        exit;
    }
    header('Location: backup?err=create');
    exit;
}

if ($action === 'download' && $file !== '' && preg_match('/^backup_\d{8}_\d{6}\.sql$/', $file)) {
    $path = $backupDir . '/' . $file;
    if (is_file($path)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }
    header('Location: backup?err=notfound');
    exit;
}

if ($action === 'delete' && $file !== '' && preg_match('/^backup_\d{8}_\d{6}\.sql$/', $file)) {
    $path = $backupDir . '/' . $file;
    $ok = is_file($path) && @unlink($path);
    if ($ok) admin_log($conn, 'delete', "Menghapus file backup: $file");
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
        exit;
    }
    header('Location: backup' . ($ok ? '?success=delete' : '?err=delete'));
    exit;
}

if ($action === 'restore' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_FILES['backup_file']['name']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['backup_file']['tmp_name'];
        $sql = file_get_contents($tmp);
        if ($sql !== false && trim($sql) !== '') {
            $conn->multi_query($sql);
            do {
                if ($res = $conn->store_result()) $res->free();
            } while ($conn->more_results() && $conn->next_result());
            if ($conn->errno === 0) {
                admin_log($conn, 'update', "Restore database dari file: " . $_FILES['backup_file']['name']);
                header('Location: backup?success=restore');
                exit;
            }
        }
    }
    header('Location: backup?err=restore');
    exit;
}

$files = [];
foreach (glob($backupDir . '/backup_*.sql') ?: [] as $p) {
    $files[] = ['name' => basename($p), 'size' => filesize($p)];
}
usort($files, function ($a, $b) { return strcmp($b['name'], $a['name']); });

include __DIR__ . '/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Backup &amp; Restore</h1>
</div>
<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'success', title: 'Berhasil!', timer: 1800, showConfirmButton: false }); });</script>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<script>document.addEventListener('DOMContentLoaded', function() { Swal.fire({ icon: 'error', title: 'Gagal!' }); });</script>
<?php endif; ?>
<div class="mb-4 two-grid" style="align-items:stretch;">
    <div style="display:flex;">
        <div class="card border-0 shadow-sm rounded-4" style="flex:1;display:flex;flex-direction:column;">
            <div class="card-body" style="flex:1;display:flex;flex-direction:column;">
                <h2 class="h6 mb-3">Backup Database</h2>
                <p class="text-muted small">Buat salinan database saat ini ke folder backups.</p>
                <form method="post" action="backup?action=create" id="backupForm" style="margin-top:auto;">
                    <button type="button" class="btn btn-primary" id="btnBackup">Buat Backup Sekarang</button>
                </form>
            </div>
        </div>
    </div>
    <div style="display:flex;">
        <div class="card border-0 shadow-sm rounded-4" style="flex:1;display:flex;flex-direction:column;">
            <div class="card-body" style="flex:1;display:flex;flex-direction:column;">
                <h2 class="h6 mb-3">Restore Database</h2>
                <p class="text-muted small">Pulihkan database dari file .sql. Data saat ini akan ditimpa.</p>
                <form method="post" action="backup?action=restore" enctype="multipart/form-data" id="restoreForm" style="margin-top:auto;">
                    <div class="mb-3">
                        <input type="file" name="backup_file" class="form-control" accept=".sql" required>
                    </div>
                    <button type="button" class="btn btn-warning" id="btnRestore">Restore</button>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body">
        <h2 class="h6 mb-3">Data Backup (<?php echo count($files); ?>)</h2>
        <div class="table-responsive">
            <table class="table align-middle table-sm">
                <thead><tr><th width="60">No</th><th>Nama Backup</th><th width="150">Ukuran</th><th width="180" class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php if (empty($files)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">Belum ada file backup.</td></tr>
                <?php else: foreach ($files as $i => $f): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($f['name']); ?></td>
                        <td><?php echo number_format($f['size'] / 1024, 1); ?> KB</td>
                        <td class="text-end">
                            <a href="backup?action=download&amp;file=<?php echo urlencode($f['name']); ?>" class="btn-icon btn-view" title="Unduh" aria-label="Unduh"><?php echo ui_icon('save', 'w-5 h-5'); ?></a>
                            <button type="button" class="btn-icon btn-del btn-del-backup" title="Hapus" aria-label="Hapus" data-file="<?php echo htmlspecialchars($f['name']); ?>"><?php echo ui_icon('trash', 'w-5 h-5'); ?></button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('btnBackup')?.addEventListener('click', function() {
        Swal.fire({ title: 'Buat Backup?', text: 'Salinan database saat ini akan dibuat.', icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, Buat!', cancelButtonText: 'Batal' }).then(function(r) {
            if (!r.isConfirmed) return;
            var startTs = Date.now();
            Swal.fire({ title: 'Membuat backup...', text: 'Mohon tunggu.', allowOutsideClick: false, allowEscapeKey: false, didOpen: function() { Swal.showLoading(); } });
            fetch('backup?action=create', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function(x) { return x.json(); }).then(function(d) {
                var wait = Math.max(0, 1500 - (Date.now() - startTs));
                setTimeout(function() {
                    if (d.success) Swal.fire({ icon: 'success', title: 'Backup Berhasil!', text: d.file || '', confirmButtonText: 'OK' }).then(function() { location.reload(); });
                    else Swal.fire({ icon: 'error', title: 'Backup Gagal!', text: d.error || '', confirmButtonText: 'OK' });
                }, wait);
            }).catch(function() {
                Swal.fire({ icon: 'error', title: 'Backup Gagal!', text: 'Terjadi kesalahan jaringan.', confirmButtonText: 'OK' });
            });
        });
    });
    document.getElementById('btnRestore')?.addEventListener('click', function() {
        var f = document.querySelector('input[name="backup_file"]');
        if (!f || !f.files.length) {
            Swal.fire({ icon: 'warning', title: 'Pilih File', text: 'Pilih file .sql terlebih dahulu.' });
            return;
        }
        Swal.fire({ title: 'Restore Database?', text: 'Data saat ini akan ditimpa. Lanjutkan?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Restore!', cancelButtonText: 'Batal' }).then(function(r) {
            if (!r.isConfirmed) return;
            Swal.fire({ title: 'Merestore database...', text: 'Mohon tunggu.', allowOutsideClick: false, allowEscapeKey: false, didOpen: function() { Swal.showLoading(); } });
            document.getElementById('restoreForm').submit();
        });
    });
    document.querySelectorAll('.btn-del-backup').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var f = this.getAttribute('data-file');
            Swal.fire({ title: 'Hapus Backup?', text: f, icon: 'warning', showCancelButton: true, confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then(function(r) {
                if (!r.isConfirmed) return;
                fetch('backup?action=delete&file=' + encodeURIComponent(f), { headers: { 'X-Requested-With': 'XMLHttpRequest' } }).then(function(x) { return x.json(); }).then(function(d) {
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
