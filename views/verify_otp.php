<?php
session_start();

if (!isset($_SESSION['otp_email'])) {
    header("Location: ../views/forgot.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Vérification OTP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

<div class="login-container">
    <div class="login-card" style="background-color: rgba(131, 130, 130, 0.28);">

        <h2 class="text-center mb-3"  style="color: white; font-size: 35px;">Vérification OTP</h2>

        <p class="text-center small text-muted" style="font-size: 15px; ">
            Saisissez le code reçu par email
        </p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="../Controller/CtrlerVerifyOtp.php" style="font-size: 15px; ">

            <div class="form-group mb-3" >
                <input
                    type="text"
                    name="otp"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    class="form-control text-center"
                    placeholder="000000"
                    required
                >
            </div>

            <button class="btn btn-primary w-100" style="font-size: 13px; text-align: center;  justify-content: center; align-items: center; margin-top: -10px;">
                Vérifier le code
            </button>
        </form>

        <div class="text-center mt-3">
            <button id="resendBtn" class="btn btn-link small" disabled style="font-size: 15px; text-decoration: none; margin-top: -20px; color: white;">
                Renvoyer le code (<span id="timer">60</span>s)
            </button>
        </div>

    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3">
    <?php if (isset($_SESSION['toast'])): ?>
        <div class="toast show bg-<?= $_SESSION['toast_type'] ?>">
            <div class="toast-body text-white">
                <?= $_SESSION['toast']; ?>
            </div>
        </div>
    <?php 
        unset($_SESSION['toast'], $_SESSION['toast_type']);
    endif; ?>
</div>


<script src="../assets/js/main.js"></script>

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
        btn.onclick = () => {
            window.location.href = "../Controller/CtrlerResendOtp.php";
        };
    }
}, 1000);
</script>

</body>
</html>
