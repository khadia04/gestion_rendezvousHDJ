<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récupération de compte Afin de protéger votre compte </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../assets/img/logo.png">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons (pour l’icône œil) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Même CSS que login -->
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

<div class="login-container">

    <!-- Toggle thème (IDENTIQUE login) -->
    <div class="theme-toggle-dashboard" onclick="toggleLoginTheme()">
        <i id="loginThemeIcon" class="bi bi-sun-fill"></i>
    </div>

    <!-- CARD -->
    <div class="login-card">

        <div class="logo-container text-center mb-2">
            <img src="../assets/img/logo.png" class="logo-hdj" alt="Logo HDJ">
            <p class="login-slogan">Votre santé, notre priorité.</p>
        </div>

        <p class="text-center" style="font-size: 18px; color:#D3D3D3;">
            Réinitialisation du mot de passe
        </p>

        <!-- MESSAGE -->
        <?php if (isset($_GET['status']) && $_GET['status'] === 'sent'): ?>
            <div class="alert alert-success text-center">
                Un lien de réinitialisation a été envoyé.
            </div>
        <?php endif; ?>

        <!-- FORM -->
        <form method="post" action="/rendezvous/Controller/CtrlerForgot.php">

            <div class="mb-3">
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Entrer votre adresse email"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Recevoir le lien
            </button>
        </form>

        <!-- RETOUR LOGIN -->
        <p class="text-center mt-3">
            <a href="/rendezvous/index.php" class="forgot-link">
                ← Retour à la connexion
            </a>
        </p>

    </div>

    <div class="login-footer">
        © Khardiata Thiam - 2025
    </div>

</div>

<script src="../assets/js/login-theme.js"></script>

</body>
</html>
