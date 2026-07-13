@echo off
setlocal

:: ===== KONFIGURASI =====
set "REPO=D:\laragon\www\portal_berita"
set "BACKUP=%REPO%\backup"
set "ZIP=portal_backup.zip"
set "REMOTE=https://github.com/dewecorp/portal.git"
set "BRANCH=master"

cd /d "%REPO%"

:: ===== 1. BACKUP ZIP =====
echo.
echo === 1. BUAT ZIP BACKUP ===

if not exist "%BACKUP%" mkdir "%BACKUP%"

:: Hapus zip lama (overwrite/timpa)
if exist "%BACKUP%\%ZIP%" del /f /q "%BACKUP%\%ZIP%"

:: Buat zip baru (PS, works di Windows 10/11)
powershell -Command ^
  "$src = '%REPO%';" ^
  "$dst = '%BACKUP%\%ZIP%';" ^
  "$tmp = '%BACKUP%\_tmp';" ^
  "if (Test-Path $tmp) { Remove-Item -Recurse -Force $tmp };" ^
  "$includes = @('admin','uploads','*.php','.htaccess','PANDUAN_DROPDOWN_MENU.md');" ^
  "New-Item -ItemType Directory -Path $tmp -Force | Out-Null;" ^
  "Get-ChildItem -Path $src -Include $includes -Recurse -File | ForEach-Object {" ^
  "  $rel = $_.FullName.Substring($src.Length+1);" ^
  "  $dest = Join-Path $tmp $rel;" ^
  "  $dir = Split-Path $dest -Parent;" ^
  "  if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null };" ^
  "  Copy-Item $_.FullName $dest -Force" ^
  "};" ^
  "if (Test-Path $dst) { Remove-Item -Force $dst };" ^
  "Compress-Archive -Path $tmp\* -DestinationPath $dst -Force;" ^
  "Remove-Item -Recurse -Force $tmp;" ^
  "Write-Host 'Backup OK:' $dst"

if errorlevel 1 (
    echo GAGAL buat backup!
    pause
    exit /b 1
)

:: ===== 2. GIT COMMIT & PUSH =====
echo.
echo === 2. GIT ADD, COMMIT, PUSH ===

git add -A
if errorlevel 1 (
    echo GAGAL git add!
    pause
    exit /b 1
)

:: Minta pesan commit
set /p MSG="Masukkan pesan commit: "
if "%MSG%"=="" set "MSG=update %date% %time:~0,5%"

git commit -m "%MSG%"
if errorlevel 1 (
    echo Tidak ada perubahan baru untuk di-commit.
)

git push origin %BRANCH%
if errorlevel 1 (
    echo Gagal push! Mungkin butuh credential/token.
    pause
    exit /b 1
)

echo.
echo ============================
echo  SELESAI!
echo  Backup : %BACKUP%\%ZIP%
echo  Commit + Push ke %REMOTE%
echo ============================
echo.
pause
