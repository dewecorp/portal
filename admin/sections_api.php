<?php
require_once __DIR__ . '/auth.php';
admin_require_login();

header('Content-Type: application/json; charset=utf-8');
ensure_sections_table($conn);

function api_out(bool $ok, array $extra = []): void
{
    echo json_encode(array_merge(['success' => $ok], $extra));
    exit;
}

function api_cfg_clean(string $tipe, $cfg): array
{
    $base = section_default_cfg($tipe);
    if (!is_array($cfg)) $cfg = [];
    $out = [];
    foreach ($base as $k => $v) {
        if (array_key_exists($k, $cfg)) {
            $val = $cfg[$k];
            if (is_string($val)) $val = trim($val);
            $out[$k] = $val;
        } else {
            $out[$k] = $v;
        }
    }
    if (isset($out['jumlah'])) $out['jumlah'] = max(1, min(20, (int)$out['jumlah']));
    if (isset($out['kolom'])) $out['kolom'] = max(2, min(6, (int)$out['kolom']));
    if (isset($out['kategori_id'])) $out['kategori_id'] = (int)$out['kategori_id'];
    if (isset($out['autoplay'])) $out['autoplay'] = max(0, min(60, (int)$out['autoplay']));
    if (isset($out['tampil_tombol'])) $out['tampil_tombol'] = !empty($out['tampil_tombol']) ? 1 : 0;
    if (isset($out['style_grid'])) $out['style_grid'] = section_normalize_style((string)$out['style_grid']);
    if (isset($out['galeri']) && !is_array($out['galeri'])) {
        $d = json_decode((string)$out['galeri'], true);
        $out['galeri'] = is_array($d) ? array_values($d) : [];
    }
    return $out;
}

function api_tipe_valid(string $area, string $tipe): bool
{
    $list = $area === 'footer' ? section_tipe_footer() : section_tipe_home();
    return isset($list[$tipe]);
}

$aksi = $_GET['aksi'] ?? $_POST['aksi'] ?? '';
$raw = file_get_contents('php://input');
$json = json_decode($raw ?: '[]', true);
if (!is_array($json)) $json = [];
$req = array_merge($_POST, $json);

try {
    if ($aksi === 'list') {
        $area = ($req['area'] ?? 'home') === 'footer' ? 'footer' : 'home';
        $stmt = $conn->prepare("SELECT * FROM sections WHERE area = ? ORDER BY urutan ASC, id ASC");
        $stmt->bind_param('s', $area);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($r = $res->fetch_assoc()) {
            $r['cfg'] = array_merge(section_default_cfg($r['tipe']), section_pengaturan($r));
            $list[] = $r;
        }
        $stmt->close();
        api_out(true, ['data' => $list]);
    }

    if ($aksi === 'create') {
        $area = ($req['area'] ?? 'home') === 'footer' ? 'footer' : 'home';
        $tipe = trim((string)($req['tipe'] ?? ''));
        if (!api_tipe_valid($area, $tipe)) api_out(false, ['error' => 'Tipe tidak valid.']);
        $mx = $conn->prepare("SELECT COALESCE(MAX(urutan),0)+1 AS nxt FROM sections WHERE area = ?");
        $mx->bind_param('s', $area);
        $mx->execute();
        $nxt = (int)($mx->get_result()->fetch_assoc()['nxt'] ?? 1);
        $mx->close();
        $meta = section_widget_meta();
        $judul = trim((string)($req['judul'] ?? ($meta[$tipe]['label'] ?? $tipe)));
        $cfg = api_cfg_clean($tipe, $req['cfg'] ?? []);
        $js = json_encode($cfg, JSON_UNESCAPED_UNICODE);
        $anim = 'fade-up';
        $stmt = $conn->prepare("INSERT INTO sections (area, tipe, judul, pengaturan, animasi, urutan, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param('sssssi', $area, $tipe, $judul, $js, $anim, $nxt);
        $ok = $stmt->execute();
        $nid = $stmt->insert_id;
        $stmt->close();
        api_out((bool)$ok, ['id' => (int)$nid]);
    }

    if ($aksi === 'update') {
        $id = (int)($req['id'] ?? 0);
        if ($id <= 0) api_out(false, ['error' => 'ID tidak valid.']);
        $row = $conn->prepare("SELECT * FROM sections WHERE id = ? LIMIT 1");
        $row->bind_param('i', $id);
        $row->execute();
        $cur = $row->get_result()->fetch_assoc();
        $row->close();
        if (!$cur) api_out(false, ['error' => 'Section tidak ditemukan.']);
        $area = ($req['area'] ?? $cur['area']) === 'footer' ? 'footer' : 'home';
        $tipe = trim((string)($req['tipe'] ?? $cur['tipe']));
        if (!api_tipe_valid($area, $tipe)) api_out(false, ['error' => 'Tipe tidak valid.']);
        $judul = trim((string)($req['judul'] ?? $cur['judul'] ?? ''));
        $anim = trim((string)($req['animasi'] ?? $cur['animasi'] ?? 'fade-up'));
        if (!isset(section_animasi_list()[$anim])) $anim = 'fade-up';
        $gaya = trim((string)($req['gaya_gambar'] ?? ''));
        if ($gaya !== '' && isset(section_gaya_gambar_list()[$gaya])) {
            // disimpan di dalam cfg agar kompatibel renderer lama
        }
        $cfgReq = $req['cfg'] ?? section_pengaturan($cur);
        if ($gaya !== '') {
            if (!is_array($cfgReq)) $cfgReq = [];
            $cfgReq['gaya_gambar'] = $gaya;
        }
        $cfg = api_cfg_clean($tipe, $cfgReq);
        $js = json_encode($cfg, JSON_UNESCAPED_UNICODE);
        $aktif = isset($req['is_active']) ? ((int)$req['is_active'] ? 1 : 0) : (int)$cur['is_active'];
        $stmt = $conn->prepare("UPDATE sections SET area = ?, tipe = ?, judul = ?, pengaturan = ?, animasi = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param('sssssii', $area, $tipe, $judul, $js, $anim, $aktif, $id);
        $ok = $stmt->execute();
        $stmt->close();
        api_out((bool)$ok);
    }

    if ($aksi === 'reorder') {
        $area = ($req['area'] ?? 'home') === 'footer' ? 'footer' : 'home';
        $ids = $req['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) api_out(false, ['error' => 'Data urutan kosong.']);
        $u = 1;
        $stmt = $conn->prepare("UPDATE sections SET urutan = ? WHERE id = ? AND area = ?");
        foreach ($ids as $sid) {
            $sid = (int)$sid;
            if ($sid <= 0) continue;
            $stmt->bind_param('iis', $u, $sid, $area);
            $stmt->execute();
            $u++;
        }
        $stmt->close();
        api_out(true);
    }

    if ($aksi === 'toggle') {
        $id = (int)($req['id'] ?? 0);
        if ($id <= 0) api_out(false);
        $stmt = $conn->prepare("UPDATE sections SET is_active = 1 - is_active WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        api_out((bool)$ok);
    }

    if ($aksi === 'duplicate') {
        $id = (int)($req['id'] ?? 0);
        $row = $conn->prepare("SELECT * FROM sections WHERE id = ? LIMIT 1");
        $row->bind_param('i', $id);
        $row->execute();
        $cur = $row->get_result()->fetch_assoc();
        $row->close();
        if (!$cur) api_out(false, ['error' => 'Tidak ditemukan.']);
        $mx = $conn->prepare("SELECT COALESCE(MAX(urutan),0)+1 AS nxt FROM sections WHERE area = ?");
        $mx->bind_param('s', $cur['area']);
        $mx->execute();
        $nxt = (int)($mx->get_result()->fetch_assoc()['nxt'] ?? 1);
        $mx->close();
        $judul = (string)($cur['judul'] ?? '') . ' (Salinan)';
        $stmt = $conn->prepare("INSERT INTO sections (area, tipe, judul, pengaturan, animasi, urutan, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssii', $cur['area'], $cur['tipe'], $judul, $cur['pengaturan'], $cur['animasi'], $nxt, $cur['is_active']);
        $ok = $stmt->execute();
        $nid = $stmt->insert_id;
        $stmt->close();
        api_out((bool)$ok, ['id' => (int)$nid]);
    }

    if ($aksi === 'delete') {
        $id = (int)($req['id'] ?? 0);
        if ($id <= 0) api_out(false);
        $stmt = $conn->prepare("DELETE FROM sections WHERE id = ?");
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        api_out((bool)$ok);
    }

    if ($aksi === 'upload') {
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) api_out(false, ['error' => 'Upload gagal.']);
        $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif', 'mp3' => 'audio/mpeg', 'mp4' => 'video/mp4'];
        $name = (string)$_FILES['file']['name'];
        $tmp = (string)$_FILES['file']['tmp_name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!isset($allowed[$ext])) api_out(false, ['error' => 'Format tidak didukung.']);
        $finfo = function_exists('mime_content_type') ? @mime_content_type($tmp) : '';
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) && @getimagesize($tmp) === false) api_out(false, ['error' => 'File bukan gambar valid.']);
        if ($_FILES['file']['size'] > 10 * 1024 * 1024) api_out(false, ['error' => 'Maksimal 10MB.']);
        $dir = __DIR__ . '/../uploads';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $safe = uniqid('sec_') . '.' . $ext;
        if (!move_uploaded_file($tmp, $dir . '/' . $safe)) api_out(false, ['error' => 'Gagal menyimpan file.']);
        api_out(true, ['path' => 'uploads/' . $safe, 'url' => '../uploads/' . $safe]);
    }

    api_out(false, ['error' => 'Aksi tidak dikenal.']);
} catch (Throwable $e) {
    api_out(false, ['error' => 'Server error.']);
}
