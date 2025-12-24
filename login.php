<?
session_regenerate_id(true);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion | HDJ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons (pour l’icône œil) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Ton CSS -->
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<div class="login-container">
    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger text-center">
            <?= $error ?>
        </div>
    <?php endif; ?>

    <div class="theme-toggle" onclick="toggleTheme()">
        <i id="themeIcon" class="bi bi-moon"></i>
    </div>
    <div id="theme-message"></div>


    <!-- FORMULAIRE PAR-DESSUS L’IMAGE -->
    <div class="login-card">
        


        <div class="logo-container text-center mb-2">
            <img src="assets/img/logo.png" class="logo-hdj" alt="Logo HDJ">
            <p class="login-slogan">Votre santé, notre priorité.</p>
        </div>

        <p class="text-center">Connexion à votre espace</p>

        <!-- Messages d’erreur -->
        <?php 
        if (isset($_GET['status']) && $_GET['status'] == 'blocked') {
            echo '<div class="alert alert-danger" style="font-size: 15px;">Votre compte a été bloqué.</div>';
        }
        else if (isset($_GET['exist']) && $_GET['exist'] == 'false') {
            echo '<div class="alert alert-danger" style="font-size: 15px;" >Nom d’utilisateur ou mot de passe incorrect.</div>';
        }
        else if (isset($_GET['pass']) && $_GET['pass'] == 'false') {
            echo '<div class="alert alert-warning" style="font-size: 15px;">Mot de passe incorrect. Tentatives restantes : '.$_GET['rest'].'</div>';
        }
        else if (isset($_GET['reset']) && $_GET['reset'] == 'success') {
            echo '<div class="alert alert-success" style="font-size: 15px;">Mot de passe réinitialisé avec succès.</div>';
        }
        if (isset($_GET['session']) && $_GET['session'] == 'expired') {
            echo '<div class="alert alert-warning" style="font-size: 15px;">Votre session a expiré. Veuillez vous reconnecter.</div>';
        }


        ?>

        <form action="Controller/Ctrlerlogin.php" method="post">

            <!-- USERNAME -->
            <div class="mb-3">
                <input type="text" name="username" class="form-control" placeholder="Nom d'utilisateur" required>
            </div>

            <!-- PASSWORD AVEC ICÔNE ŒIL -->
            <div class="mb-3 input-group">
                <input type="password" name="pwd" id="password" class="form-control" placeholder="Mot de passe" required>
                <span class="input-group-text" onclick="togglePassword()">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </span>
            </div>


            <!-- BOUTON -->
            <button type="submit" name="login" class="btn btn-primary w-100">
                Se connecter
            </button>
            <p class="text-center mt-3">
                <a href="views/forgot.php" class="forgot-link">Mot de passe oublié ?</a>
            </p>


        </form>

    </div>
    <div class="login-footer">
        © Khardiata Thiam - 2025
    </div>



</div>

<script>
// =========================
//   VARIABLES GLOBALES
// =========================
const body = document.body;
const themeIcon = document.getElementById("themeIcon");


// =========================
//   SCRIPT MOT DE PASSE
// =========================
function togglePassword() {
    const passwordInput = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    if (passwordInput.type === "password") {
        passwordInput.type = "text";
        eyeIcon.classList.remove("bi-eye");
        eyeIcon.classList.add("bi-eye-slash");
    } else {
        passwordInput.type = "password";
        eyeIcon.classList.remove("bi-eye-slash");
        eyeIcon.classList.add("bi-eye");
    }
}


// =========================
//   MESSAGE ANIMÉ (thème)
// =========================
function showThemeMessage(msg) {
    const messageBox = document.getElementById("theme-message");
    messageBox.textContent = msg;

    // Apparition
    messageBox.style.opacity = "1";
    messageBox.style.transform = "translateY(0)";

    // Disparition
    setTimeout(() => {
        messageBox.style.opacity = "0";
        messageBox.style.transform = "translateY(-10px)";
    }, 1500);
}


// =========================
//   CHARGER LE THÈME
// =========================
function loadTheme() {
    const savedTheme = localStorage.getItem("theme");

    if (savedTheme === "dark") {
        body.classList.add("dark-mode");
        themeIcon.classList.remove("bi-moon");
        themeIcon.classList.add("bi-sun-fill", "light-icon");
    } else {
        body.classList.remove("dark-mode");
        themeIcon.classList.remove("bi-sun-fill", "light-icon");
        themeIcon.classList.add("bi-moon", "dark-icon");
    }
}

loadTheme();


// =========================
//   TOGGLE DU THÈME
// =========================
function toggleTheme() {

    // Animation icône
    themeIcon.style.transform = "rotate(180deg)";
    themeIcon.style.opacity = "0";

    setTimeout(() => {

        body.classList.toggle("dark-mode");

        if (body.classList.contains("dark-mode")) {

            // Mode sombre
            themeIcon.classList.remove("bi-moon", "dark-icon");
            themeIcon.classList.add("bi-sun-fill", "light-icon");
            localStorage.setItem("theme", "dark");
            showThemeMessage("Mode sombre activé");

        } else {

            // Mode clair
            themeIcon.classList.remove("bi-sun-fill", "light-icon");
            themeIcon.classList.add("bi-moon", "dark-icon");
            localStorage.setItem("theme", "light");
            showThemeMessage("Mode clair activé");
        }

        // Retour normal
        themeIcon.style.transform = "rotate(0deg)";
        themeIcon.style.opacity = "1";

    }, 200);
}
</script>



</body>
</html>
