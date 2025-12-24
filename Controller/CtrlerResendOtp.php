<?php
session_start();

require_once '../modele/database.php';

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/PHPMailer/Exception.php';
require '../vendor/PHPMailer/PHPMailer.php';
require '../vendor/PHPMailer/SMTP.php';

/* =========================
   1. Vérification session
========================= */
if (!isset($_SESSION['otp_email'])) {
    header("Location: ../views/forgot.php");
    exit;
}

$email = $_SESSION['otp_email'];
$db = getConnection();

/* =========================
   2. Vérifier dernier OTP
========================= */
$stmt = $db->prepare("
    SELECT created_at, resend_count
    FROM password_otp
    WHERE email = ?
");
$stmt->execute([$email]);
$currentOtp = $stmt->fetch(PDO::FETCH_ASSOC);

if ($currentOtp) {

    // Limite de renvoi : 60 secondes
    if (strtotime($currentOtp['created_at']) > time() - 60) {
        $_SESSION['error'] = "Veuillez patienter avant de demander un nouveau code";
        header("Location: ../views/verify_otp.php");
        exit;
    }

    // Max 3 renvois
    if ($currentOtp['resend_count'] >= 3) {
        $_SESSION['error'] = "Nombre maximum de renvois atteint";
        header("Location: ../views/forgot.php");
        exit;
    }
}

/* =========================
   3. Générer nouvel OTP
========================= */
$otp      = random_int(100000, 999999);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

/* =========================
   4. Supprimer ancien OTP
========================= */
$db->prepare("DELETE FROM password_otp WHERE email = ?")
   ->execute([$email]);

/* =========================
   5. Enregistrer nouvel OTP
========================= */
$db->prepare("
    INSERT INTO password_otp (email, otp_hash, expires_at, attempts, resend_count, created_at)
    VALUES (?, ?, ?, 0, COALESCE(?, 0) + 1, NOW())
")->execute([
    $email,
    $otpHash,
    $expires,
    $currentOtp['resend_count'] ?? 0
]);

/* =========================
   6. Envoi email
========================= */
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contactchndj@gmail.com';
    $mail->Password   = 'lkwfpdojqscnekar'; // app password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('contactchndj@gmail.com', 'CHNDJ - Sécurité');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Nouveau code de vérification';
    $mail->Body = "
        <p>Bonjour,</p>
        <p>Voici votre <strong>nouveau code OTP</strong> :</p>
        <h2 style='letter-spacing:2px;'>$otp</h2>
        <p>Ce code est valable pendant <strong>10 minutes</strong>.</p>
    ";

    $mail->send();

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de l'envoi du mail";
    header("Location: ../views/verify_otp.php");
    exit;
}

/* =========================
   7. Message succès
========================= */
$_SESSION['toast'] = "Un nouveau code a été envoyé par email";
$_SESSION['toast_type'] = "success";

/* =========================
   8. Redirection
========================= */
header("Location: ../views/verify_otp.php");
exit;
