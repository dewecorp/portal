<?php
require_once __DIR__ . '/auth.php';

if (admin_is_logged_in()) {
    header('Location: dashboard');
    exit;
}

$error = '';
$ip = admin_client_ip();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (admin_login_locked($conn, $ip)) {
        $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam 15 menit.';
    } elseif ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, nama, username, password, role FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        $isValid = false;

        if ($user && password_verify($password, $user['password'])) {
            $isValid = true;
            if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $up = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $up->bind_param('si', $newHash, $user['id']);
                $up->execute();
                $up->close();
            }
        }

        if ($isValid) {
            admin_login_success($conn, $ip);
            admin_login($user);
            header('Location: dashboard?login=success');
            exit;
        } else {
            admin_login_fail($conn, $ip);
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Admin - Portal Berita</title>
    <?php if (!empty($settings['favicon_path'])): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($settings['favicon_path']); ?>">
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .admin-loader { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background: rgba(2, 6, 23, 0.45); backdrop-filter: blur(4px); opacity: 0; visibility: hidden; pointer-events: none; transition: opacity 0.25s ease, visibility 0.25s ease; }
        .admin-loader.is-show { opacity: 1; visibility: visible; pointer-events: auto; }
        .admin-loader-ring { position: relative; width: 110px; height: 110px; display: grid; place-items: center; }
        .admin-loader-ring::before { content: ''; position: absolute; inset: 0; border-radius: 50%; border: 5px solid rgba(255,255,255,0.18); border-top-color: #14b8a6; animation: admin-loader-spin 0.9s linear infinite; }
        .admin-loader-logo { width: 64px; height: 64px; object-fit: contain; border-radius: 16px; background: #fff; padding: 6px; box-shadow: 0 10px 30px rgba(0,0,0,0.35); }
        @keyframes admin-loader-spin { to { transform: rotate(360deg); } }
        .login-field:focus-within { border-color: #14b8a6; box-shadow: 0 0 0 4px rgba(20,184,166,0.12); background: #fff; }
    </style>
</head>
<body class="min-h-screen text-slate-900 antialiased" style="background: radial-gradient(60rem 30rem at 15% -10%, rgba(20,184,166,0.25), transparent 60%), radial-gradient(50rem 28rem at 90% 110%, rgba(99,102,241,0.22), transparent 60%), #020617;">
<div class="admin-loader is-show" id="adminLoader" aria-hidden="true">
    <div class="admin-loader-ring">
        <?php if (!empty($settings['logo_path'])): ?>
            <img class="admin-loader-logo" src="../<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="">
        <?php else: ?>
            <span class="admin-loader-logo" style="display:grid;place-items:center;font-weight:900;color:#0d9488;">AD</span>
        <?php endif; ?>
    </div>
</div>
<div class="flex min-h-screen items-center justify-center px-4 py-10">
    <div class="w-full max-w-md overflow-hidden rounded-3xl border border-white/15 bg-white/95 shadow-2xl shadow-black/40 backdrop-blur">
        <div class="bg-gradient-to-r from-teal-600 via-teal-500 to-emerald-500 px-8 pb-7 pt-8 text-center text-white">
            <div class="mb-4 inline-flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl bg-white/95 shadow-lg">
                <?php if (!empty($settings['logo_path'])): ?>
                    <img src="../<?php echo htmlspecialchars($settings['logo_path']); ?>" alt="Logo" class="h-10 w-10 object-contain">
                <?php else: ?>
                    <span class="text-base font-black text-teal-700">AD</span>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl font-black tracking-tight">Dashboard Portal Berita</h1>
            <p class="mt-1 text-sm text-white/85">Masuk sebagai administrator</p>
        </div>
        <div class="p-6 sm:p-8">
        <?php if (isset($_GET['expired'])): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Sesi Berakhir',
                        text: 'Anda logout otomatis karena tidak ada aktivitas selama 2 jam. Silakan login kembali.',
                        confirmButtonText: 'OK'
                    });
                });
            </script>
        <?php endif; ?>
        <?php if ($error): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Gagal',
                        text: '<?php echo addslashes($error); ?>',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                });
            </script>
        <?php endif; ?>
        <form method="post" autocomplete="off" class="space-y-4" id="loginForm">
            <div>
                <label for="username" class="mb-2 block text-sm font-bold text-slate-700">Username</label>
                <div class="login-field flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 transition">
                    <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <input type="text" class="w-full bg-transparent py-3 text-slate-900 outline-none placeholder:text-slate-400" id="username" name="username" placeholder="Nama pengguna" required autofocus>
                </div>
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-bold text-slate-700">Password</label>
                <div class="login-field flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 transition">
                    <svg class="h-5 w-5 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    <input type="password" class="w-full bg-transparent py-3 text-slate-900 outline-none placeholder:text-slate-400" id="password" name="password" placeholder="Kata sandi" required>
                    <button type="button" id="togglePassword" class="shrink-0 text-slate-400 transition hover:text-teal-600" aria-label="Tampilkan password">
                        <svg id="eyeOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg id="eyeClosed" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10 5.2A9.8 9.8 0 0112 5c6.5 0 10 7 10 7a17 17 0 01-3.2 3.9M6.6 6.6A16.6 16.6 0 002 12s3.5 7 10 7c1.4 0 2.7-.3 3.8-.8"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.9 9.9a3 3 0 004.2 4.2"></path></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="w-full rounded-2xl bg-gradient-to-r from-teal-600 to-emerald-500 px-5 py-3 text-sm font-black uppercase tracking-wide text-white shadow-lg shadow-teal-500/25 transition hover:brightness-110 active:scale-[0.99]">
                Masuk
            </button>
            <p class="pt-1 text-center text-xs font-semibold text-slate-400">Akses khusus administrator portal</p>
        </form>
        </div>
    </div>
</div>
<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('adminLoader')?.classList.remove('is-show');
        }, 600);
    });
    setTimeout(function() {
        document.getElementById('adminLoader')?.classList.remove('is-show');
    }, 2500);
    document.getElementById('loginForm')?.addEventListener('submit', function() {
        document.getElementById('adminLoader')?.classList.add('is-show');
    });
    document.getElementById('togglePassword')?.addEventListener('click', function() {
        var input = document.getElementById('password');
        var open = document.getElementById('eyeOpen');
        var closed = document.getElementById('eyeClosed');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        open?.classList.toggle('hidden', show);
        closed?.classList.toggle('hidden', !show);
        this.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    });
</script>
</body>
</html>
