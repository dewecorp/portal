#!/usr/bin/env php
<?php
/**
 * deploy.php - Commit, push ke GitHub, backup zip (overwrite)
 * 
 * Cara pakai: php deploy.php "pesan commit"
 * Contoh:     php deploy.php "fix security auth, add brute-force protection"
 */

// ===== KONFIGURASI =====
$repoPath  = __DIR__;
$remoteUrl = 'https://github.com/dewecorp/portal.git';
$branch    = 'master';
$backupDir = __DIR__ . '\backup';           // folder backup
$zipName   = 'portal_backup.zip';           // nama file zip (selalu timpa)
$commitMsg = $argv[1] ?? 'update ' . date('Y-m-d H:i');

// ===== BACKUP ZIP =====
echo "=== 1. BUAT ZIP BACKUP ===\n";

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
    echo "Folder backup dibuat: $backupDir\n";
}

$zip = new ZipArchive();
$zipPath = $backupDir . '/' . $zipName;

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("GAGAL buka zip: $zipPath\n");
}

// Daftar file/folder yg di-backup (kecuali folder dan file tertentu)
$exclude = [
    '.git', 'backup', '.agents', '.qoder',
    'node_modules', '.idea', '.vscode'
];

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($repoPath, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$count = 0;
foreach ($files as $file) {
    $realPath = $file->getRealPath();
    $localPath = substr($realPath, strlen($repoPath) + 1);

    // Skip yang di exclude
    $skip = false;
    foreach ($exclude as $e) {
        if (strpos($localPath, $e) === 0) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;

    $zip->addFile($realPath, $localPath);
    $count++;
}

$zip->close();
echo "Backup selesai: $count file -> $zipPath\n";

// ===== GIT COMMIT & PUSH =====
echo "\n=== 2. GIT COMMIT & PUSH ===\n";

// Cek remote ada
$remoteCheck = shell_exec("git -C " . escapeshellarg($repoPath) . " remote -v 2>&1");
if (empty($remoteCheck)) {
    echo "Remote origin belum ada. Menambahkan remote origin...\n";
    system("git -C " . escapeshellarg($repoPath) . " remote add origin " . escapeshellarg($remoteUrl), $ret);
    if ($ret !== 0) {
        die("GAGAL tambah remote origin. Cek URL atau koneksi.\n");
    }
}

// Tambah semua file
system("git -C " . escapeshellarg($repoPath) . " add -A", $ret);
if ($ret !== 0) {
    die("GAGAL git add.\n");
}
echo "File ditambahkan.\n";

// Commit
$msg = escapeshellarg($commitMsg);
system("git -C " . escapeshellarg($repoPath) . " commit -m $msg", $ret);
if ($ret !== 0) {
    echo "Tidak ada perubahan baru untuk di-commit.\n";
} else {
    echo "Commit sukses: $commitMsg\n";
}

// Push
echo "Push ke remote origin...\n";
system("git -C " . escapeshellarg($repoPath) . " push -u origin " . escapeshellarg($branch), $ret);
if ($ret !== 0) {
    die("GAGAL push. Mungkin butuh token/password.\n");
}

echo "\n============================\n";
echo "SELESAI.\n";
echo "- Backup: $zipPath\n";
echo "- Commit + Push sukses ke $remoteUrl\n";
echo "============================\n";
