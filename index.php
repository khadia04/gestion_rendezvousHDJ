<?php
require_once __DIR__ . '/security_headers.php';
session_start();




if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: views/admin.php");
        exit;
    }
    if ($_SESSION['role'] === 'agent') {
        header("Location: views/agents.php");
        exit;
    }
}

require 'login.php';


ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if (!empty($_SERVER['HTTPS'])) {
    ini_set('session.cookie_secure', 1);
}


header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("Content-Security-Policy: default-src 'self'");
