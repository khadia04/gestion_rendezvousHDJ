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

    $mail->setFrom('contactchndj@gmail.com', 'CHNDJ - Securite');
    $mail->addReplyTo('contactchndj@gmail.com', 'CHNDJ Support');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Verification de securite – Code OTP';


    $mail->Body = '
    <!DOCTYPE html>
    <html lang="fr">
    <head>
    <meta charset="UTF-8">
    <title>Verification de securite</title>
    </head>
    <body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
        <tr>
        <td align="center">

            <!-- CARD -->
            <table width="600" cellpadding="0" cellspacing="0"
            style="background:#ffffff; border-radius:12px; padding:30px; box-shadow:0 4px 12px rgba(0,0,0,0.08);">

            <!-- LOGO / TITRE -->
            <tr>
                <td align="center" style="padding-bottom:20px;">
                <h2 style="color:#2563eb; margin:0;">Vérification de sécurité</h2>
                </td>
            </tr>

            <!-- TEXTE -->
            <tr>
                <td style="color:#374151; font-size:15px; line-height:22px; text-align:center;">
                <p style="margin:0 0 10px;">Bonjour,</p>
                <p style="margin:0;">
                    Voici votre <strong>code de vérification (OTP)</strong> pour continuer la
                    réinitialisation de votre mot de passe.
                </p>
                </td>
            </tr>

            <!-- CODE OTP -->
            <tr>
                <td align="center" style="padding:25px 0;">
                <div style="
                    display:inline-block;
                    background:#eef2ff;
                    color:#1e40af;
                    font-size:28px;
                    font-weight:bold;
                    letter-spacing:6px;
                    padding:15px 30px;
                    border-radius:10px;">
                    '.$otp.'
                </div>
                </td>
            </tr>

            <!-- VALIDITÉ -->
            <tr>
                <td align="center" style="color:#6b7280; font-size:14px;">
                ⏳ Ce code est valable pendant <strong>10 minutes</strong>.
                </td>
            </tr>

            <!-- INFO -->
            <tr>
                <td style="padding-top:25px; font-size:13px; color:#9ca3af; text-align:center;">
                Si vous n’êtes pas à l’origine de cette demande, vous pouvez ignorer cet email en toute sécurité.
                </td>
            </tr>

            <!-- FOOTER -->
            <tr>
                <td align="center" style="padding-top:30px; font-size:12px; color:#9ca3af;">
                © '.date('Y').' <strong>CHNDJ</strong> — Votre santé, notre priorité.
                </td>
            </tr>

            </table>

        </td>
        </tr>
    </table>

    </body>
    </html>
    
';


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
