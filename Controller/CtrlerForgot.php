<?php
session_start();
require_once '../modele/database.php';

// PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/PHPMailer/Exception.php';
require '../vendor/PHPMailer/PHPMailer.php';
require '../vendor/PHPMailer/SMTP.php';

if (!isset($_POST['email'])) {
    $_SESSION['error'] = "Requête invalide.";
    header("Location: ../views/forgot.php");
    exit;
}

$email = trim($_POST['email']);
$db = getConnection();

/* 1️⃣ Vérifier email */
$stmt = $db->prepare("SELECT email FROM agent WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() === 0) {
    $_SESSION['error'] = "Aucun compte associé à cet email.";
    header("Location: ../views/forgot.php");
    exit;
}

/* 2️⃣ Générer OTP */
$otp       = random_int(100000, 999999);
$otpHash  = password_hash($otp, PASSWORD_DEFAULT);
$expires  = date('Y-m-d H:i:s', strtotime('+10 minutes'));

/* 3️⃣ Supprimer ancien OTP */
$db->prepare("DELETE FROM password_otp WHERE email = ?")
   ->execute([$email]);

/* 4️⃣ Insérer OTP */
$db->prepare("
    INSERT INTO password_otp (email, otp_hash, expires_at, attempts)
    VALUES (?, ?, ?, 0)
")->execute([$email, $otpHash, $expires]);


/* 5️⃣ Envoi email */
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
    $mail->Subject = 'Code de réinitialisation';
    $mail->Body = "
        <p>Bonjour,</p>
        <p>Votre code de réinitialisation est :</p>
        <h2 style='letter-spacing:3px;'>$otp</h2>
        <p>Valable pendant <strong>10 minutes</strong>.</p>
    ";

    $mail->send();
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de l’envoi du mail.";
    header("Location: ../views/forgot.php");
    exit;
}

/* 6️⃣ Session + succès */
$_SESSION['otp_email'] = $email;
$_SESSION['toast'] = "Code OTP envoyé avec succès";
$_SESSION['toast_type'] = "success";


header("Location: ../views/verify_otp.php");
exit;
