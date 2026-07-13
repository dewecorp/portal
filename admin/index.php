<?php
require_once __DIR__ . '/auth.php';

if (admin_is_logged_in()) {
    header('Location: dashboard');
    exit;
}

header('Location: login');
exit;
