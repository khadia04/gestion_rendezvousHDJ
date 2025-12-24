<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récupération de compte Afin de protéger votre compte </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Même CSS que login -->
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

<div class="login-container" style="opacity: 2;">
    <div class="login-card" style="background-color: rgba(131, 130, 130, 0.28); height: 800px;">

        <h2 class="text-center mb-3" style="color: white; font-size: 35px;">Récupération de compte</h2>

        <p class="text-center small text-muted" style="font-size: 15px;">
            Entrez votre email pour recevoir un code de vérification
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

        <form method="POST" action="../Controller/CtrlerForgot.php">

            <div class="form-group mb-3">
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="exemple@email.com"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary w-100" style="font-size: 13px; text-align: center; justify-content: center; align-items: center; margin-top: -10px;">
                Recevoir le code OTP
            </button>
        </form>

        <div class="text-center mt-3" style="margin-top: -20px;">
            <a href="login.php" class="small" style="font-size: 15px; text-decoration: none;">
                ← Retour à la connexion
            </a>
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
</body>
</html>
