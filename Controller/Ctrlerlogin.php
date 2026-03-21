<?php
session_start();

require_once '../modele/database.php';
require_once '../helpers/activity.php';

/* =========================
   VALIDATION FORMULAIRE
========================= */
if (empty($_POST['email']) || empty($_POST['pwd'])) {
    $_SESSION['error'] = "Veuillez remplir tous les champs";
    header("Location: ../index.php");
    exit;
}

$email    = trim($_POST['email']);
$password = $_POST['pwd'];

$db = getConnection();

/* =========================
   RÉCUPÉRER UTILISATEUR
========================= */
$stmt = $db->prepare("
    SELECT id, email, password, role, status, failed_attempts
    FROM agent
    WHERE email = ?
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = "Email ou mot de passe incorrect";
    header("Location: ../index.php");
    exit;
}

/* =========================
   COMPTE ACTIF ?
========================= */
if ((int)$user['status'] !== 1) {
    $_SESSION['error'] = "Compte désactivé";
    header("Location: ../index.php");
    exit;
}

/* =========================
   ANTI BRUTE FORCE
========================= */
if ((int)$user['failed_attempts'] >= 30) {
    $_SESSION['error'] = "Compte temporairement bloqué";
    header("Location: ../index.php");
    exit;
}

/* =========================
   VÉRIFICATION MOT DE PASSE
========================= */
if (!password_verify($password, $user['password'])) {

    $db->prepare("
        UPDATE agent
        SET failed_attempts = failed_attempts + 1
        WHERE id = ?
    ")->execute([$user['id']]);

    $_SESSION['error'] = "Email ou mot de passe incorrect";
    header("Location: ../index.php");
    exit;
}

/* =========================
   LOGIN OK
========================= */
$db->prepare("
    UPDATE agent
    SET failed_attempts = 0
    WHERE id = ?
")->execute([$user['id']]);

session_regenerate_id(true);

/* =========================
   INITIALISATION SESSION
========================= */
$_SESSION['user_id']      = (int) $user['id'];
$_SESSION['email']        = $user['email'];
$_SESSION['role']         = $user['role'];
$_SESSION['username']     = $user['email'];
$_SESSION['login_time']   = time();
$_SESSION['last_user_id'] = (int) $user['id'];

$_SESSION['toast'] = "Connexion réussie";
$_SESSION['toast_type'] = "success";

/* =========================
   LOG CONNEXION
========================= */
logActivity(
    $_SESSION['user_id'],
    'Connexion',
    'Connexion au tableau de bord',
    $_SESSION['role']
);

/* =========================
   CSRF TOKEN
========================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/* =========================
   REDIRECTION PAR RÔLE
========================= */

if ($user['role'] === 'super_admin') {
    header("Location: ../views/admin.php?page=dashboard");
} elseif ($user['role'] === 'admin') {
    header("Location: ../views/admin.php?page=services");
} elseif ($user['role'] === 'medecin' || $user['role'] === 'agent') {
    header("Location: ../views/admin.php?page=rendezvous");
} else {
    header("Location: ../index.php");
}
exit;

/* =========================
   FALLBACK SÉCURITÉ
========================= */
session_destroy();
header("Location: ../index.php");
exit;
