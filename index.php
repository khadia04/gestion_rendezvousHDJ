<?php


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
