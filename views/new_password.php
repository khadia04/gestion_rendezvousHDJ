<?php
session_start();

if (!isset($_SESSION['otp_verified'], $_SESSION['otp_email'])) {
    header("Location: forgot.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nouveau mot de passe</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/img/logo.png" rel="icon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

<div class="login-container">
    <div class="login-card" style="background-color: rgba(163, 161, 161, 0.53);">

        <h2 class="text-center mb-3" style="color: white; font-size: 35px;">
            Modifier le mot de passe
        </h2>

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

        <form method="POST" action="../Controller/CtrlerNewPassword.php">

            <div class="form-group mb-3">
                <label style="font-size: 15px;">Nouveau mot de passe</label>

                <div class="input-group">
                    <input 
                        type="password" 
                        name="password" 
                        id="password"
                        class="form-control"
                        required
                    >
                    <span class="input-group-text toggle-password" data-target="password">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>

                <!-- BARRE DE FORCE (cachée par défaut) -->
                <div id="strengthWrapper" style="display:none;">
                    <div class="progress mt-2" style="height: 6px;">
                        <div id="strengthBar" class="progress-bar"></div>
                    </div>
                    <small id="strengthText" class="text-light"></small>
                </div>
            </div>

            <div class="form-group mb-3" style="margin-top: -20px;">
                <label style="font-size: 15px;">Confirmer le mot de passe</label>

                <div class="input-group">
                    <input 
                        type="password" 
                        name="confirm" 
                        id="confirm"
                        class="form-control"
                        required
                    >
                <span class="input-group-text toggle-password" data-target="confirm">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <ul class="small " style="font-size: 13px; color: white;">
                <li>Minimum 8 caractères</li>
                <li>1 majuscule et 1 minuscule</li>
                <li>1 chiffre et 1 caractère spécial</li>
            </ul>

            <button disabled class="btn btn-success w-100"
                style="font-size: 15px; margin-top: -10px;"
                id="submitBtn">
                Enregistrer
            </button>
        </form>

    </div>
</div>

<!-- Toast -->
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

<script src="../assets/js/password-strength.js"></script>
<script src="../assets/js/main.js"></script>


<script>
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        const icon = btn.querySelector('i');

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
});
</script>


</body>
</html>
