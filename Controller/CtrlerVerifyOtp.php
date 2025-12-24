<?php
session_start();
require_once '../modele/database.php';

if (!isset($_POST['otp'], $_SESSION['otp_email'])) {
    $_SESSION['error'] = "Requête invalide";
    header("Location: ../views/verify_otp.php");
    exit;
}

$email = $_SESSION['otp_email'];
$otp   = trim($_POST['otp']);
$db    = getConnection();

/* =========================
   RÉCUPÉRATION OTP
========================= */
$stmt = $db->prepare("
    SELECT otp_hash, expires_at, attempts
    FROM password_otp
    WHERE email = ?
");
$stmt->execute([$email]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $_SESSION['error'] = "Code OTP introuvable. Veuillez recommencer.";
    header("Location: ../views/forgot.php");
    exit;
}

/* =========================
   EXPIRATION OTP
========================= */
if (strtotime($data['expires_at']) < time()) {
    $_SESSION['error'] = "Code expiré. Veuillez demander un nouveau code.";
    header("Location: ../views/forgot.php");
    exit;
}

/* =========================
   BLOCAGE APRÈS 3 ESSAIS
========================= */
if ($data['attempts'] >= 3) {
    $_SESSION['error'] = "Trop de tentatives. Un nouveau code est requis.";
    header("Location: ../views/forgot.php");
    exit;
}

/* =========================
   VÉRIFICATION OTP
========================= */
if (!password_verify($otp, $data['otp_hash'])) {

    // Incrémenter les tentatives
    $db->prepare("
        UPDATE password_otp
        SET attempts = attempts + 1,
            last_attempt = NOW()
        WHERE email = ?
    ")->execute([$email]);

    $_SESSION['error'] = "Code OTP incorrect";
    header("Location: ../views/verify_otp.php");
    exit;
}

/* =========================
   OTP VALIDE
========================= */

// Marquer OTP comme vérifié
$_SESSION['otp_verified'] = true;

// Nettoyer OTP
$db->prepare("DELETE FROM password_otp WHERE email = ?")
   ->execute([$email]);

$_SESSION['success'] = "Code vérifié avec succès. Choisissez un nouveau mot de passe.";

header("Location: ../views/new_password.php");
exit;
?>