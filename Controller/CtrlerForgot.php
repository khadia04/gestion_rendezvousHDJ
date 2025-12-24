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
   Sécurité basique
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../views/forgot.php");
    exit;
}

if (empty($_POST['email'])) {
    $_SESSION['error'] = "Veuillez saisir votre email.";
    header("Location: ../views/forgot.php");
    exit;
}

$email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

if (!$email) {
    $_SESSION['error'] = "Adresse email invalide.";
    header("Location: ../views/forgot.php");
    exit;
}

$db = getConnection();

/* =========================
   1. Vérifier existence email
========================= */
$stmt = $db->prepare("SELECT email FROM agent WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() === 0) {
    $_SESSION['error'] = "Aucun compte associé à cet email.";
    header("Location: ../views/forgot.php");
    exit;
}

/* =========================
   2. Génération OTP
========================= */
$otp       = random_int(100000, 999999);
$otpHash  = password_hash($otp, PASSWORD_DEFAULT);
$expires  = date('Y-m-d H:i:s', strtotime('+10 minutes'));

/* =========================
   3. Nettoyage anciens OTP
========================= */
$db->prepare("DELETE FROM password_otp WHERE email = ?")
   ->execute([$email]);

/* =========================
   4. Sauvegarde OTP
========================= */
$db->prepare("
    INSERT INTO password_otp (email, otp_hash, expires_at)
    VALUES (?, ?, ?)
")->execute([$email, $otpHash, $expires]);

/* =========================
   5. Envoi email OTP
========================= */
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'contactchndj@gmail.com';
    $mail->Password   = 'lkwfpdojqscnekar'; // mot de passe application
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('contactchndj@gmail.com', 'CHNDJ - Sécurité');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Code de réinitialisation du mot de passe';
    $mail->Body = "
        <p>Bonjour,</p>
        <p>Voici votre <strong>code de réinitialisation</strong> :</p>
        <h2 style='letter-spacing:2px;'>$otp</h2>
        <p>Ce code est valable pendant <strong>10 minutes</strong>.</p>
        <p>Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.</p>
    ";

    $mail->send();

} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de l'envoi du code. Veuillez réessayer.";
    header("Location: ../views/forgot.php");
    exit;
}

/* =========================
   6. Session & feedback UX
========================= */
$_SESSION['otp_email'] = $email;
$_SESSION['toast'] = "Code OTP envoyé avec succès";
$_SESSION['toast_type'] = "success";

/* =========================
   7. Redirection OTP
========================= */
header("Location: ../views/verify_otp.php");
exit;
