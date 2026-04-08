<?php
session_start();

require_once '../modele/database.php';
require_once '../helpers/activity.php';

/* =========================================================
   SÉCURITÉ DE BASE
========================================================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?session=expired");
    exit;
}

/* =========================================================
   VALIDATION CSRF
========================================================= */
if (
    empty($_POST['csrf_token']) ||
    $_POST['csrf_token'] !== $_SESSION['csrf_token']
) {
    $_SESSION['error'] = "Session invalide.";
    header("Location: ../views/admin.php?page=profile#security");
    exit;
}

/* =========================================================
   VALIDATION FORMULAIRE
========================================================= */
if (
    empty($_POST['current_password']) ||
    empty($_POST['password']) ||
    empty($_POST['confirm'])
) {
    $_SESSION['error'] = "Tous les champs sont obligatoires.";
    header("Location: ../views/admin.php?page=profile#security");
    exit;
}

$current  = $_POST['current_password'];
$password = $_POST['password'];
$confirm  = $_POST['confirm'];
$userId   = (int) $_SESSION['user_id'];


/* =========================================================
   CONFIRMATION MOT DE PASSE
========================================================= */
if ($password !== $confirm) {
    $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
    header("Location: ../views/admin.php?page=profile#security");
    exit;
}

/* =========================================================
   FORCE MOT DE PASSE
========================================================= */
if (!preg_match(
    '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
    $password
)) {
    $_SESSION['error'] = "Mot de passe trop faible.";
    header("Location: ../views/admin.php?page=profile#security");
    exit;
}

/* =========================================================
   VÉRIFICATION ANCIEN MOT DE PASSE
========================================================= */
$db = getConnection();

$stmt = $db->prepare("SELECT password FROM agent WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($current, $user['password'])) {
    $_SESSION['error'] = "Mot de passe actuel incorrect.";
    header("Location: ../views/admin.php?page=profile#security");
    exit;
}

/* =========================================================
   UPDATE MOT DE PASSE
========================================================= */
$newHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare("
    UPDATE agent 
    SET password = :password,
        must_change_password = 0
    WHERE id = :id
");

$stmt->execute([
    'password' => $newHash,
    'id'       => $userId
]);

$_SESSION['must_change_password'] = 0;
/* =========================================================
   LOG ACTIVITÉ
========================================================= */
logActivity(
    $userId,
    'Changement de mot de passe',
    'Modification depuis le profil (utilisateur connecté)',
    $_SESSION['role']
);

/* =========================================================
   SUCCÈS + REDIRECTION
========================================================= */
$_SESSION['success'] = "Mot de passe modifié avec succès.";
header("Location: ../views/admin.php?page=profile#security");
exit;
