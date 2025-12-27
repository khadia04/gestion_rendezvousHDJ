<?php

// Générer le token si inexistant
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Vérifie le token CSRF pour les requêtes POST
 */
function verifyCsrfToken(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (
            !isset($_POST['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
        ) {
            http_response_code(403);
            die("Action non autorisée (CSRF détecté)");
        }
    }
}
