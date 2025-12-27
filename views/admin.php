<?php
require_once __DIR__ . '/../security_headers.php';
session_start();

require_once '../middlewares/auth.php';
require_once '../middlewares/csrf.php';

requireAuth('admin');
verifyCsrfToken();


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // destruction propre
    session_unset();
    session_destroy();
    header("Location: ../index.php?session=expired");

    exit;
}

require 'admin_layout.php';

require_once '../middlewares/auth.php';
requireAuth('admin');
