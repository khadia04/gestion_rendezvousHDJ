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
            <?php if (!empty($_SESSION['error'])) : ?>
                <div class="alert alert-danger text-center">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- Messages d’erreur -->
        <?php 
        if (isset($_GET['status']) && $_GET['status'] == 'blocked') {
            echo '<div class="alert alert-danger"  py-1 >Votre compte a été bloqué.</div>';
        }
        else if (isset($_GET['exist']) && $_GET['exist'] == 'false') {
            echo '<div class="alert alert-danger" py-1 >Nom d’utilisateur ou mot de passe incorrect.</div>';
        }
        else if (isset($_GET['pass']) && $_GET['pass'] == 'false') {
            echo '<div class="alert alert-warning" py-1 >Mot de passe incorrect. Tentatives restantes : '.$_GET['rest'].'</div>';
        }
        else if (isset($_GET['reset']) && $_GET['reset'] == 'success') {
            echo '<div class="alert alert-success" py-1 >Mot de passe réinitialisé avec succès.</div>';
        }
        if (isset($_GET['session']) && $_GET['session'] == 'expired') {
            echo '<div class="alert alert-warning" py-1 >Votre session a expiré. Veuillez vous reconnecter.</div>';
        }


        ?>

        <form action="/rendezvous/Controller/Ctrlerlogin.php" method="post">

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
