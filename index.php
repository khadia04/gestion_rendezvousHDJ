<?php


session_start();
require 'security_headers.php';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'super_admin') {
        header("Location: /rendezvous/views/admin.php");
        exit;
    }

    if ($_SESSION['role'] === 'admin') {
        header("Location: /rendezvous/views/admin.php");
        exit;
    }

    if ($_SESSION['role'] === 'medecin') {
        header("Location: /rendezvous/views/agents.php"); // ou une vue medecin si tu veux
        exit;
    }

    if ($_SESSION['role'] === 'agent') {
        header("Location: /rendezvous/views/agents.php");
        exit;
    }
}

require 'login.php';
