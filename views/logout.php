<?php
session_start();

/**
 * Vérification CSRF
 */
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('Action non autorisée');
}

/**
 * Nettoyage de la session
 */
$_SESSION = [];

/**
 * Suppression du cookie de session
 */
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

/**
 * Destruction finale
 */
session_destroy();

/**
 * Redirection propre
 */
header("Location: ../index.php?logout=success");
exit;
