<?php
session_start();
require_once '../Modele/database.php';

/* =========================
   PHPMailer
========================= */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/PHPMailer/Exception.php';
require '../vendor/PHPMailer/PHPMailer.php';
require '../vendor/PHPMailer/SMTP.php';

/* =========================
   1. Vérification session
========================= */
if (!isset($_SESSION['otp_email'])) {
    $_SESSION['toast'] = "Session expirée. Veuillez recommencer.";
    $_SESSION['toast_type'] = "danger";
    header("Location: ../views/forgot.php");
    exit;
}

$email = $_SESSION['otp_email'];
$db = getConnection();

/* =========================
   2. Vérifier cooldown (60s)
========================= */
$stmt = $db->prepare("
    SELECT created_at
    FROM password_otp
    WHERE email = ?
");
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && !empty($row['created_at'])) {
    $seconds = time() - strtotime($row['created_at']);

    if ($seconds < 60) {
        $_SESSION['toast'] = "Veuillez patienter " . (60 - $seconds) . " secondes avant de renvoyer un code.";
        $_SESSION['toast_type'] = "warning";
        header("Location: ../views/verify_otp.php");
        exit;
    }
}

/* =========================
   3. Génération nouvel OTP
========================= */
$otp      = random_int(100000, 999999);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
$now     = date('Y-m-d H:i:s');

/* =========================
   4. Supprimer ancien OTP
========================= */
$db->prepare("DELETE FROM password_otp WHERE email = ?")
   ->execute([$email]);

/* =========================
   5. Enregistrer nouvel OTP
========================= */
$db->prepare("
    INSERT INTO password_otp 
    (email, otp_hash, expires_at, attempts, created_at)
    VALUES (?, ?, ?, 0, ?)
")->execute([$email, $otpHash, $expires, $now]);

/* =========================
   6. Envoi email
========================= */
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contactchndj@gmail.com';
    $mail->Password   = 'lkwfpdojqscnekar'; // ⚠️ à sécuriser plus tard
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('contactchndj@gmail.com', 'CHNDJ - Sécurité');
    $mail->addAddress($email);

    $mail->AddEmbeddedImage(
    '../assets/img/logo.png',
    'logo_chndj'
    );

    $mail->isHTML(true);
    $mail->Subject = 'Votre code de vérification OTP – CHNDJ';
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
$mail->Body = '
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
</head>

<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td align="center" style="padding:30px 15px;">

<table width="100%" cellpadding="0" cellspacing="0"
style="max-width:480px;background:#ffffff;border-radius:16px;
box-shadow:0 10px 30px rgba(0,0,0,0.1);padding:30px;">

<tr>
<td align="center" style="padding-bottom:20px;">
    <img src="cid:logo_chndj" width="90" alt="CHNDJ">
</td>
</tr>

<tr>
<td align="center" style="font-size:20px;font-weight:700;color:#0e6bb7;">
    Vérification de sécurité
</td>
</tr>

<tr>
<td align="center" style="font-size:14px;color:#1f2937;padding:15px 0;">
    Bonjour,<br><br>
    Voici votre <strong>code de vérification (OTP)</strong> pour continuer
    la réinitialisation de votre mot de passe.
</td>
</tr>

<tr>
<td align="center" style="padding:20px 0;">
    <div style="
        display:inline-block;
        padding:14px 28px;
        font-size:26px;
        font-weight:700;
        letter-spacing:6px;
        color:#0e6bb7;
        background:#eaf2ff;
        border-radius:12px;
    ">
        '.$otp.'
    </div>
</td>
</tr>

<tr>
<td align="center" style="font-size:13px;color:#6b7280;">
    ⏳ Ce code est valable pendant <strong>10 minutes</strong>.
</td>
</tr>

<tr>
<td align="center" style="
    font-size:12px;
    color:#9ca3af;
    padding-top:20px;
    border-top:1px solid #e5e7eb;
">
    Si vous n’êtes pas à l’origine de cette demande,
    vous pouvez ignorer cet email en toute sécurité.
</td>
</tr>

<tr>
<td align="center" style="font-size:11px;color:#9ca3af;padding-top:15px;">
    © '.date('Y').' CHNDJ – Votre santé, notre priorité.
</td>
</tr>

</table>

</td>
</tr>
</table>

</body>
</html>';


    $mail->send();

} catch (Exception $e) {
    $_SESSION['toast'] = "Erreur lors de l'envoi de l'email.";
    $_SESSION['toast_type'] = "danger";
    header("Location: ../views/verify_otp.php");
    exit;
}

/* =========================
   7. Succès
========================= */
$_SESSION['toast'] = "Un nouveau code a été envoyé.";
$_SESSION['toast_type'] = "success";

header("Location: ../views/verify_otp.php");
exit;
