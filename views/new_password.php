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
    <title>Nouveau mot de passe | HDJ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Favicon -->
    <link rel="icon" href="../assets/img/logo.png">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- CSS partagé -->
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body class="login-page">

<div class="login-container">

    <!-- Toggle Dark / Light -->
    <div class="theme-toggle-dashboard" onclick="toggleLoginTheme()">
        <i id="loginThemeIcon" class="bi bi-sun-fill"></i>
    </div>

    <!-- CARD -->
    <div class="login-card">

        <!-- Logo -->
        <div class="logo-container text-center mb-2">
            <img src="../assets/img/logo.png" class="logo-hdj" alt="Logo HDJ">
            <p class="login-slogan">Votre santé, notre priorité.</p>
        </div>

        <p class="text-center" style="font-size: 18px; color: #D3D3D3;">Modifier le mot de passe</p>

        <!-- ZONE MESSAGE FIXE -->
        <div class="login-alert-zone">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger text-center">
                    <?= $_SESSION['error']; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- FORM -->
        <form method="POST" action="../Controller/CtrlerNewPassword.php">

            <!-- Nouveau mot de passe -->
            <div class="mb-3">
                <label class="form-label" style="color: #D3D3D3;">Nouveau mot de passe</label>
                <div class="input-group">
                    <input type="password" name="password" id="password"
                           class="form-control" required>
                    <span class="input-group-text toggle-password" data-target="password">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>

                <!-- Force mot de passe -->
                <div id="strengthWrapper" style="display:none;">
                    <div class="progress mt-2" style="height: 6px;">
                        <div id="strengthBar" class="progress-bar"></div>
                    </div>
                    <small id="strengthText"></small>
                </div>
            </div>

            <!-- Confirmation -->
            <div class="mb-3">
                <label class="form-label"  style="color: #D3D3D3;">Confirmer le mot de passe</label>
                <div class="input-group">
                    <input type="password" name="confirm" id="confirm"
                           class="form-control" required>
                    <span class="input-group-text toggle-password" data-target="confirm">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>

            <!-- Règles -->
            <ul class="password-rules"  style="color: #D3D3D3;">
                <li>Minimum 8 caractères</li>
                <li>1 majuscule et 1 minuscule</li>
                <li>1 chiffre et 1 caractère spécial</li>
            </ul>

            <button id="submitBtn" class="btn btn-primary w-100" disabled>
                Enregistrer
            </button>
        </form>

    </div>
    <div class="login-footer">
        © Khardiata Thiam - 2025
    </div>
</div>

<?php unset($_SESSION['error']); ?>

<!-- JS -->
<script src="../assets/js/login-theme.js"></script>
<script src="../assets/js/password-strength.js"></script>

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
