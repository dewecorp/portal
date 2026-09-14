<?php
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
require_once __DIR__ . '/../config.php';

function admin_client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function ensure_login_attempts_table(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS login_attempts (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ip VARCHAR(45) NOT NULL,
        attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_login_attempts_ip (ip),
        INDEX idx_login_attempts_time (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function admin_login_locked(mysqli $conn, string $ip, int $maxAttempts = 5, int $windowMinutes = 15): bool
{
    ensure_login_attempts_table($conn);
    $stmt = $conn->prepare("SELECT COUNT(*) AS jml FROM login_attempts WHERE ip = ? AND attempted_at > (NOW() - INTERVAL ? MINUTE)");
    $stmt->bind_param('si', $ip, $windowMinutes);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['jml'] ?? 0) >= $maxAttempts;
}

function admin_login_fail(mysqli $conn, string $ip): void
{
    ensure_login_attempts_table($conn);
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip) VALUES (?)");
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $stmt->close();
}

function admin_login_success(mysqli $conn, string $ip): void
{
    ensure_login_attempts_table($conn);
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip = ?");
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $stmt->close();
    admin_log($conn, 'login', "Login berhasil dari IP $ip");
}

function admin_is_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

function admin_idle_timeout(): int
{
    return 7200;
}

function admin_touch_activity(): void
{
    $_SESSION['admin_last_activity'] = time();
}

function admin_check_idle(): bool
{
    if (!admin_is_logged_in()) return false;
    $last = (int)($_SESSION['admin_last_activity'] ?? 0);
    if ($last > 0 && (time() - $last) > admin_idle_timeout()) {
        admin_logout();
        header('Location: login?expired=1');
        exit;
    }
    admin_touch_activity();
    return true;
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        header('Location: login');
        exit;
    }
    admin_check_idle();
}

function admin_get_role(): string
{
    return $_SESSION['admin_role'] ?? 'author';
}

function admin_can_manage_users(): bool
{
    return admin_get_role() === 'administrator';
}

function admin_can_edit_content(): bool
{
    $role = admin_get_role();
    return in_array($role, ['administrator', 'author', 'editor'], true);
}

function admin_can_publish(): bool
{
    $role = admin_get_role();
    return in_array($role, ['administrator', 'author'], true);
}

function admin_require_role(string $role): void
{
    if (admin_get_role() !== $role && admin_get_role() !== 'administrator') {
        http_response_code(403);
        die('Akses ditolak. Role tidak sesuai.');
    }
}

function admin_login(array $user): void
{
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_nama'] = $user['nama'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_role'] = $user['role'] ?? 'author';
    admin_touch_activity();
}

function admin_logout(): void
{
    global $conn;
    if (isset($conn)) {
        admin_log($conn, 'logout', 'Logout dari panel admin');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
