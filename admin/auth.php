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
}

function admin_is_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

function admin_require_login(): void
{
    if (!admin_is_logged_in()) {
        header('Location: login');
        exit;
    }
}

function admin_login(array $user): void
{
    $_SESSION['admin_id'] = $user['id'];
    $_SESSION['admin_nama'] = $user['nama'];
    $_SESSION['admin_username'] = $user['username'];
}

function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
