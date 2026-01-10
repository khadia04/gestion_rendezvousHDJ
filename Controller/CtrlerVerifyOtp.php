<?php
session_start();
require_once '../Modele/database.php';



$email = $_SESSION['otp_email'];
$otp   = trim($_POST['otp']);

$db = getConnection();

/* =========================
   Récupération OTP
========================= */
$stmt = $db->prepare("
    SELECT otp_hash, expires_at, attempts
    FROM password_otp
    WHERE email = ?
");
$stmt->execute([$email]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    $_SESSION['error'] = "Aucun code trouvé. Veuillez recommencer.";
    header("Location: ../views/forgot.php");
    exit;
}

/* =========================
   Expiration
========================= */
if (strtotime($data['expires_at']) < time()) {
    $_SESSION['error'] = "Code expiré. Demandez un nouveau code.";
    header("Location: ../views/verify_otp.php");
    exit;
}

/* =========================
   Blocage après 3 essais
========================= */
if ($data['attempts'] >= 3) {
    $_SESSION['error'] = "Trop de tentatives. Demandez un nouveau code.";
    header("Location: ../views/forgot.php");
    exit;
}


$_SESSION['otp_error'] = true;

/* =========================
   Vérification OTP
========================= */
if (!password_verify($otp, $data['otp_hash'])) {

    $db->prepare("
        UPDATE password_otp
        SET attempts = attempts + 1,
            last_attempt = NOW()
        WHERE email = ?
    ")->execute([$email]);

    $_SESSION['error'] = "Code OTP incorrect";
    $_SESSION['otp_error'] = true;   // ⭐ IMPORTANT
    header("Location: ../views/verify_otp.php");
    exit;
}


/* =========================
   OTP VALIDE
========================= */
$db->prepare("
    UPDATE password_otp
    SET attempts = 0
    WHERE email = ?
")->execute([$email]);

$_SESSION['otp_verified'] = true;
$_SESSION['toast'] = "Code vérifié avec succès";
$_SESSION['toast_type'] = "success";

header("Location: ../views/new_password.php");
exit;
