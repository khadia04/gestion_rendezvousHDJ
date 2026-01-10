<?php
session_start();

if (!isset($_SESSION['otp_email'])) {
    header("Location: forgot.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification OTP | HDJ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" href="../assets/img/logo.png">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body class="login-page">


<div class="login-container">

    <!-- Toggle thème -->
    <div class="theme-toggle-dashboard" onclick="toggleLoginTheme()">
        <i id="loginThemeIcon" class="bi bi-sun-fill"></i>
    </div>

    <!-- LOGIN CARD -->
    <div class="login-card <?php echo isset($_SESSION['otp_error']) ? 'otp-shake' : ''; ?>">

        <!-- Logo -->
        <div class="logo-container text-center mb-2">
            <img src="../assets/img/logo.png" class="logo-hdj">
            <p class="login-slogan">Votre santé, notre priorité.</p>
        </div>

        <p class="text-center" style="font-size: 16px; color:#D3D3D3;">Vérification du code OTP</p>
        <p class="text-center" style="font-size: 12px; color:#D3D3D3;" >Entrez le code reçu par email</p>

        <!-- ZONE MESSAGE FIXE -->
        <div class="login-alert-zone">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger text-center">
                    <?= $_SESSION['error']; ?>
                </div>
            <?php endif; ?>
        </div>


        <!-- FORM -->
        <form method="POST" action="../Controller/CtrlerVerifyOtp.php">
            <input
                type="text"
                name="otp"
                maxlength="6"
                class="form-control text-center mb-3"
                placeholder="000000"
                required
                autofocus
            >
            <button class="btn btn-primary w-100">Vérifier le code</button>
        </form>

        <div class="text-center ">
            <button id="resendBtn" class="btn btn-link" disabled>
                Renvoyer le code (<span id="timer">60</span>s)
            </button>
        </div>

        <div class="text-center ">
            <a href="forgot.php" class="forgot-link">← Modifier l’adresse email</a>
        </div>

    </div>
    <div class="login-footer">
        © Khardiata Thiam - 2025
    </div>
</div>

<?php
// IMPORTANT : on nettoie APRES affichage
unset($_SESSION['error'], $_SESSION['otp_error']);
?>

<script src="../assets/js/login-theme.js"></script>

<script>
let time = 60;
const btn = document.getElementById("resendBtn");
const timer = document.getElementById("timer");

const interval = setInterval(() => {
    time--;
    timer.textContent = time;

    if (time <= 0) {
        clearInterval(interval);
        btn.disabled = false;
        btn.textContent = "Renvoyer le code";
        btn.onclick = () => location.href = "../Controller/CtrlerResendOtp.php";
    }
}, 1000);
</script>

</body>
</html>
