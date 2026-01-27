<?php
session_start();

/* =========================
   PROTECTION CSRF
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée');
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('CSRF token invalide');
}

/* =========================
   SAUVEGARDER USER ID AVANT DE TOUT DÉTRUIRE
========================= */
$userId = $_SESSION['user_id'] ?? null;

/* =========================
   LOG AVANT DE CASSER LA SESSION
========================= */

$duration = null;

if (!empty($_SESSION['login_time'])) {
    $duration = time() - $_SESSION['login_time'];
}

require_once '../helpers/activity.php';

logActivity(
    $_SESSION['user_id'],
    'Déconnexion',
    'Déconnexion du compte',
    $_SESSION['role'],
    $duration
);


/* =========================
   DESTRUCTION PROPRE
========================= */
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

/* =========================
   REDIRECTION
========================= */
header("Location: ../index.php");
exit;
