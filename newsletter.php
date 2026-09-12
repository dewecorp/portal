<?php
require_once __DIR__ . '/config.php';

ensure_newsletter_table($conn);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index');
    exit;
}

$email = trim((string)($_POST['email'] ?? ''));
$back = trim((string)($_SERVER['HTTP_REFERER'] ?? 'index'));
if ($back === '') $back = 'index';
$sep = strpos($back, '?') === false ? '?' : '&';
$base = preg_replace('/([?&])nl=[^&]*/', '', $back);
$base = rtrim($base, '?&');
$sep = strpos($base, '?') === false ? '?' : '&';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $base . $sep . 'nl=err#index');
    exit;
}

$stmt = $conn->prepare("SELECT id FROM newsletter_subscribers WHERE email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$ada = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($ada) {
    header('Location: ' . $base . $sep . 'nl=ada#index');
    exit;
}

$stmt = $conn->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
$stmt->bind_param('s', $email);
$ok = $stmt->execute();
$stmt->close();

header('Location: ' . $base . $sep . 'nl=' . ($ok ? 'ok' : 'err'));
exit;
