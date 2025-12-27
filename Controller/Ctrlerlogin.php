<?php
session_start();
require_once '../modele/database.php';

/* =========================
   VALIDATION FORM
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

$user = $stmt->fetch();

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
if ($user['failed_attempts'] >= 5) {
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

$_SESSION['user_id'] = $user['id'];
$_SESSION['email']   = $user['email'];
$_SESSION['role']    = $user['role'];
$_SESSION['username'] = $user['email']; // ou $user['username'] si tu as ce champ


$_SESSION['toast'] = "Connexion réussie";
$_SESSION['toast_type'] = "success";

/* =========================
   JETON CSRF
========================= */
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));


/* =========================
   REDIRECTION PAR RÔLE
========================= */
if ($user['role'] === 'admin') {
    header("Location: /rendezvous/views/admin.php");
    exit;
}

if ($user['role'] === 'agent') {
    header("Location: ../views/agents.php");
    exit;
}

/* Sécurité fallback */
header("Location: ../index.php");

exit;

/* =========================
   COMPTE VERROUILLÉ ?  
========================= */

if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
    $_SESSION['error'] = "Compte temporairement verrouillé";
    header("Location: ../index.php");
    exit;
    
}

