<?php


if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false, // mets TRUE en HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}


function requireAuth(?string $role = null): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        header("Location: /rendezvous/index.php?session=expired");
        exit;
    }

    if ($role !== null && $_SESSION['role'] !== $role) {
        http_response_code(403);
        die(" Accès interdit");
    }

    // Expiration session (30 min)
    $timeout = 30 * 60;

    if (
        isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity']) > $timeout
    ) {
        session_destroy();
        header("Location: /rendezvous/index.php?session=expired");
        exit;
    }

    $_SESSION['last_activity'] = time();
}


function requireRole(array $roles): void
{
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
        header("Location: /rendezvous/index.php?session=expired");
        exit;
    }

    $userRole = trim(strtolower($_SESSION['role']));
    $roles = array_map(fn($r) => strtolower(trim($r)), $roles);


    if (!in_array($userRole, $roles)) {
        http_response_code(403);
        die("⛔ Accès refusé");
    }
}

function requirePermission($permission)
{
    $role = $_SESSION['role'] ?? null;

    $permissions = [
        'super_admin' => ['*'],

        'admin' => [
            'services',
            'rendezvous',
            'patients',
            'profile'
        ],

        'medecin' => [
            'rendezvous',
            'patients',
            'profile'
        ],

        'agent' => [
            'rendezvous',
            'patients',
            'profile'
        ]
    ];

    if (!$role || !isset($permissions[$role])) {
        header("HTTP/1.1 403 Forbidden");
        exit("Accès interdit");
    }

    if (
        !in_array('*', $permissions[$role]) &&
        !in_array($permission, $permissions[$role])
    ) {
        header("HTTP/1.1 403 Forbidden");
        exit("Accès refusé");
    }
}
