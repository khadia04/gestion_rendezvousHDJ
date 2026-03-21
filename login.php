<?php
session_start();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion | HDJ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/img/logo.png" rel="icon" >

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (pour l’icône œil) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Ton CSS -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<div class="login-container" >
    <div class="theme-toggle-dashboard" onclick="toggleLoginTheme()">
        <i id="loginThemeIcon" class="bi bi-sun-fill"></i>
    </div>
    <div id="theme-message"></div>

    <!-- FORMULAIRE PAR-DESSUS L’IMAGE -->
    <div class="login-card">
        <div class="logo-container text-center mb-2" >
            <img src="assets/img/logo.png" class="logo-hdj" alt="Logo HDJ"> 
            <p class="login-slogan">Votre santé, notre priorité.</p>
        </div>

        <p class="text-center" style="font-size: 20px; color:#ffffff;">Connexion à votre espace</p>

        <div class="login-alert-zone">
            <?php
            if (!empty($_SESSION['error'])) {
                echo '<div class="alert alert-danger text-center">'
                    . $_SESSION['error'] .
                    '</div>';
                unset($_SESSION['error']);
            }
            elseif (isset($_GET['status']) && $_GET['status'] === 'blocked') {
                echo '<div class="alert alert-danger text-center">
                        Compte bloqué.
                    </div>';
            }
            elseif (isset($_GET['exist']) && $_GET['exist'] === 'false') {
                echo '<div class="alert alert-danger text-center">
                        Email ou mot de passe incorrect.
                    </div>';
            }
            elseif (isset($_GET['reset']) && $_GET['reset'] === 'success') {
                echo '<div class="alert alert-success text-center">
                        Mot de passe réinitialisé.
                    </div>';
            }
            elseif (isset($_GET['session']) && $_GET['session'] === 'expired') {
                echo '<div class="alert alert-warning text-center">
                        Session expirée. Veuillez vous reconnecter.
                    </div>';
            }
            ?>
        </div>


        <form action="/rendezvous/Controller/Ctrlerlogin.php" method="post">
            <!-- TOKEN CSRF -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <!-- USERNAME -->
            <div class="mb-3" style="padding-bottom: 10px;">
                <input type="email" name="email" class="form-control" required>

            </div>

            <!-- PASSWORD AVEC ICÔNE ŒIL -->
            <div class="mb-3 input-group" >
                <input type="password" name="pwd" id="password" class="form-control" required style="padding-bottom: 10px;">
                <span class="input-group-text" onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </span>
            </div>


            <!-- BOUTON -->
            <button type="submit" name="login" class="btn btn-primary w-100" style="margin-top: 20px;">
                Se connecter
            </button>
            <p class="text-center" style="margin-top: 30px;">
                <a href="views/forgot.php" class="forgot-link">Mot de passe oublié ?</a>
            </p>


        </form>

    </div>
    <div class="login-footer"  >
        © Khardiata Thiam - 2025
    </div>



</div>

<script src="assets/js/login-theme.js"></script>





</body>
</html>
