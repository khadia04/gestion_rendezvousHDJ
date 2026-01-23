<?php
session_start();
require_once '../modele/database.php';

if (
    !isset($_SESSION['user_id'], $_POST['current_password'], $_POST['password'], $_POST['confirm'])
) {
    header("Location: ../views/admin.php?page=profile");
    exit;
}

$current = $_POST['current_password'];
$password = $_POST['password'];
$confirm  = $_POST['confirm'];
$userId   = $_SESSION['user_id'];

if ($password !== $confirm) {
    $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
    header("Location: ../views/admin.php?page=profile");
    exit;
}

if (!preg_match(
    '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
    $password
)) {
    $_SESSION['error'] = "Mot de passe trop faible.";
    header("Location: ../views/admin.php?page=profile");
    exit;
}

$db = getConnection();

// Vérifier ancien mot de passe
$stmt = $db->prepare("SELECT password FROM agent WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($current, $user['password'])) {
    $_SESSION['error'] = "Mot de passe actuel incorrect.";
    header("Location: ../views/admin.php?page=profile");
    exit;
}

// Update
$newHash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare("UPDATE agent SET password = ? WHERE id = ?");
$stmt->execute([$newHash, $userId]);

$_SESSION['success'] = "Mot de passe modifié avec succès.";
header("Location: ../views/admin.php?page=profile");
exit;
