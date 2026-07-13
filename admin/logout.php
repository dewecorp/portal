<?php
require_once __DIR__ . '/auth.php';

if (admin_is_logged_in()) {
    admin_logout();
}

header('Location: login');
exit;
