<?php
session_start();
require_once '../Modele/database.php';

if (
    !isset($_POST['password'], $_POST['confirm'],
            $_SESSION['otp_email'], $_SESSION['otp_verified'])
) {
    header("Location: ../views/forgot.php");
    exit;
}

$password = trim($_POST['password']);
$confirm  = trim($_POST['confirm']);
$email    = $_SESSION['otp_email'];

/* =========================
   VALIDATIONS
========================= */
if ($password !== $confirm) {
    $_SESSION['error'] = "Les mots de passe ne correspondent pas.";
    header("Location: ../views/new_password.php");
    exit;
}

if (!preg_match(
    '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/',
    $password
)) {
    $_SESSION['error'] = "Mot de passe trop faible.";
    header("Location: ../views/new_password.php");
    exit;
}

/* =========================
   HASH & UPDATE
========================= */
$hash = password_hash($password, PASSWORD_DEFAULT);
$db = getConnection();

try {
    $db->beginTransaction();

    // Update password
    $stmt = $db->prepare("
        UPDATE agent
        SET password = ?
        WHERE email = ?
    ");
    $stmt->execute([$hash, $email]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Utilisateur introuvable");
    }

    // Suppression OTP
    $stmt = $db->prepare("DELETE FROM password_otp WHERE email = ?");
    $stmt->execute([$email]);

    $db->commit();

    // Nettoyage session
    session_unset();
    session_destroy();

    session_start();
    $_SESSION['toast'] = "Mot de passe modifié avec succès.";
    $_SESSION['toast_type'] = "success";

    header("Location: ../index.php");
    exit;

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = "Erreur lors de la mise à jour.";
    header("Location: ../views/new_password.php");
    exit;
}
