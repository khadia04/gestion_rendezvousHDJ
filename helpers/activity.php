<?php

// Connexion à la base de données
// __DIR__ garantit que le chemin est toujours correct
require_once __DIR__ . '/../modele/database.php';

/**
 * Récupère l’adresse IP réelle du client
 * - Gère le cas proxy / reverse proxy
 * - Convertit ::1 (localhost IPv6) en 127.0.0.1
 */
function getClientIp(): string
{
    // Cas proxy (serveur intermédiaire)
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }

    // Cas standard
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        // Conversion IPv6 localhost → IPv4
        return $_SERVER['REMOTE_ADDR'] === '::1'
            ? '127.0.0.1'
            : $_SERVER['REMOTE_ADDR'];
    }

    // Fallback sécurité
    return '127.0.0.1';
}

/**
 * Enregistre une activité utilisateur dans la table activity_logs
 *
 * @param int         $userId           ID de l’utilisateur
 * @param string      $action           Type d’action (Connexion, Déconnexion, etc.)
 * @param string|null $description      Détails de l’action
 * @param string|null $role             Rôle de l’utilisateur (admin / agent)
 * @param int|null    $sessionDuration  Durée de session en secondes (uniquement à la déconnexion)
 */
function logActivity(
    int $userId,
    string $action,
    ?string $description = null,
    ?string $role = null,
    ?int $sessionDuration = null
): void {
    try {
        // Connexion DB
        $db = getConnection();

        // Préparation de la requête d’insertion
        $stmt = $db->prepare("
            INSERT INTO activity_logs 
            (user_id, role, action, description, ip_address, session_duration)
            VALUES 
            (:user_id, :role, :action, :description, :ip, :duration)
        ");

        // Exécution sécurisée
        $stmt->execute([
            'user_id'     => $userId,
            'role'        => $role,
            'action'      => $action,
            'description' => $description,
            'ip'          => getClientIp(),
            'duration'    => $sessionDuration
        ]);
    } catch (Throwable $e) {
        // On log l’erreur sans casser l’application
        error_log('[ACTIVITY ERROR] ' . $e->getMessage());
    }
}
