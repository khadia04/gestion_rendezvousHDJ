<?php
session_start();
require_once '../modele/database.php';

/* =========================
   VÉRIFICATIONS SESSION
========================= */
if (
    !isset($_SESSION['otp_verified'], $_SESSION['otp_email']) ||
    !isset($_POST['password'], $_POST['confirm'])
) {
    header("Location: ../views/forgot.php");
    exit;
}

$password = $_POST['password'];
$confirm  = $_POST['confirm'];
$email    = $_SESSION['otp_email'];

/* =========================
   VALIDATION MOT DE PASSE
========================= */
if ($password !== $confirm) {
    $_SESSION['error'] = "Les mots de passe ne correspondent pas";
    header("Location: ../views/new_password.php");
    exit;
}

/* Politique mot de passe forte */
if (!preg_match(
    '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
    $password
)) {
    $_SESSION['error'] = "Mot de passe trop faible :
    • 8 caractères minimum
    • 1 majuscule
    • 1 minuscule
    • 1 chiffre
    • 1 caractère spécial";
    header("Location: ../views/new_password.php");
    exit;
}

/* =========================
   HASH DU MOT DE PASSE
========================= */
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$db = getConnection();

try {
    $db->beginTransaction();

    /* Mise à jour mot de passe */
    $stmt = $db->prepare("
        UPDATE agent 
        SET password = ?
        WHERE email = ?
    ");
    $stmt->execute([$hashedPassword, $email]);

    /* Supprimer OTP */
    $stmt = $db->prepare("
        DELETE FROM password_otp 
        WHERE email = ?
    ");
    $stmt->execute([$email]);

    $db->commit();

    /* Nettoyage session OTP */
    unset($_SESSION['otp_verified'], $_SESSION['otp_email']);

    $_SESSION['toast'] = "Mot de passe modifié avec succès";
    $_SESSION['toast_type'] = "success";

    header("Location: ../index.php");
    exit;

} catch (Exception $e) {
    $db->rollBack();

    $_SESSION['error'] = "Erreur lors de la mise à jour du mot de passe";
    header("Location: ../views/new_password.php");
    exit;
}
