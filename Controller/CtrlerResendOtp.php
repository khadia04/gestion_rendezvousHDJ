<?php
session_start();

require_once '../Modele/database.php';

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
    $_SESSION['toast'] = "Session expirée. Veuillez recommencer.";
    $_SESSION['toast_type'] = "danger";
    header("Location: ../views/forgot.php");
    exit;
}

$email = $_SESSION['otp_email'];
$db = getConnection();

/* =========================
   2. Vérifier cooldown serveur
========================= */
$stmt = $db->prepare("
    SELECT last_sent_at
    FROM password_otp
    WHERE email = ?
");
$stmt->execute([$email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && $row['last_sent_at']) {
    $seconds = time() - strtotime($row['last_sent_at']);

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
$otp = random_int(100000, 999999);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
$now = date('Y-m-d H:i:s');

/* =========================
   4. Supprimer ancien OTP
========================= */
$db->prepare("DELETE FROM password_otp WHERE email = ?")
   ->execute([$email]);

/* =========================
   5. Sauvegarder nouvel OTP
========================= */
$db->prepare("
    INSERT INTO password_otp (email, otp_hash, expires_at, attempts, last_sent_at)
    VALUES (?, ?, ?, 0, ?)
")->execute([$email, $otpHash, $expires, $now]);

/* =========================
   6. Envoi email OTP
========================= */
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contactchndj@gmail.com';
    $mail->Password   = 'lkwfpdojqscnekar';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('contactchndj@gmail.com', 'CHNDJ - Sécurité');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Nouveau code OTP';
    $mail->Body = "
        <p>Bonjour,</p>
        <p>Voici votre nouveau <strong>code OTP</strong> :</p>
        <h2 style='letter-spacing:2px;'>$otp</h2>
        <p>Valable 10 minutes.</p>
    ";

    $mail->send();

} catch (Exception $e) {
    $_SESSION['toast'] = "Erreur lors de l'envoi du mail.";
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
