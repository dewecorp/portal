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
        $stmt = $conn->prepare("SELECT id, nama, username, password FROM admin_users WHERE username = ? AND is_active = 1 LIMIT 1");
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
            header('Location: dashboard');
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
</head>
<body class="min-h-screen bg-slate-950 text-slate-950 antialiased">
<div class="flex min-h-screen items-center justify-center bg-[radial-gradient(circle_at_top,_#1e293b_0,_#0f172a_42%,_#020617_100%)] px-4 py-10">
    <div class="w-full max-w-md rounded-lg border border-white/10 bg-white p-6 shadow-2xl shadow-black/30 sm:p-8">
        <div class="mb-7">
            <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-red-500 text-sm font-black text-white">
                AD
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-950">Dashboard Portal Berita</h1>
            <p class="mt-1 text-sm text-slate-500">Masuk sebagai administrator</p>
        </div>
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
        <form method="post" autocomplete="off" class="space-y-4">
            <div>
                <label for="username" class="mb-2 block text-sm font-bold text-slate-700">Username</label>
                <input type="text" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-950 outline-none transition focus:border-red-500 focus:ring-4 focus:ring-red-500/10" id="username" name="username" required autofocus>
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm font-bold text-slate-700">Password</label>
                <input type="password" class="w-full rounded-lg border border-slate-300 px-4 py-3 text-slate-950 outline-none transition focus:border-red-500 focus:ring-4 focus:ring-red-500/10" id="password" name="password" required>
            </div>
            <button type="submit" class="w-full rounded-full bg-red-500 px-5 py-3 text-sm font-black uppercase tracking-wide text-white transition hover:bg-red-600">
                Masuk
            </button>
        </form>
    </div>
</div>
</body>
</html>
