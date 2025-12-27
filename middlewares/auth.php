<?php

function requireAuth(?string $role = null): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        header("Location: /rendezvous/index.php?session=expired");
        exit;
    }

    if ($role !== null && $_SESSION['role'] !== $role) {
        http_response_code(403);
        echo "Accès interdit";
        exit;
    }

    // Expiration session (15 min)
    $timeout = 15 * 60;

    if (
        isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity']) > $timeout
    ) {
        session_unset();
        session_destroy();
        header("Location: /rendezvous/index.php?session=expired");
        exit;
    }

    $_SESSION['last_activity'] = time();
}
