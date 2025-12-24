<?php

// Sécurité : accès admin uniquement
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Gestion de l'inactivité
if (isset($_SESSION['lastAction'], $_SESSION['timeframe'])) {
    if ((time() - $_SESSION['lastAction']) > $_SESSION['timeframe']) {
        session_destroy();
        header("Location: ../index.php?session=expired");
        exit;
    }
}

// Mise à jour de l’activité
$_SESSION['lastAction'] = time();


require_once "../modele/database.php";
require_once "../modele/databasePatient.php";
require_once "../modele/databaseTools.php";
require_once "../modele/databaseRv.php";


// Vérifier rôle admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

// Déterminer la page demandée
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Définir le titre
switch ($page) {
    case 'agents': 
        $title = "Gestion des Agents"; 
        break;

    case 'services': 
        $title = "Gestion des Services"; 
        break;

    case 'rendezvous': 
        $title = "Gestion des Rendez-vous"; 
        break;

    case 'stats': 
        $title = "Statistiques"; 
        break;

    case 'profile': 
        $title = "Mon profil"; 
        break;

    default: 
        $title = "Tableau de bord"; 
        break;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?> - Administration HDJ</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- CSS Admin -->
    <link rel="stylesheet" href="../assets/css/admin.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body class="<?= $page ?>"  data-theme="light" >

<div class="dashboard-container">


    <!-- ============================
        SIDEBAR
    ============================= -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/img/logo.png" class="sidebar-logo">
            <h4>ADMIN</h4>
        </div>

        <ul class="sidebar-menu">

            <!-- Tableau de bord -->
            <li>
                <a href="admin.php?page=dashboard" 
                   class="<?= ($page == 'dashboard' ? 'active' : '') ?>">
                    <i class="bi bi-speedometer2"></i> Tableau de bord
                </a>
            </li>

            <!-- Agents -->
            <li>
                <a href="admin.php?page=agents"
                   class="<?= ($page == 'agents' ? 'active' : '') ?>">
                    <i class="bi bi-people"></i> Agents
                </a>
            </li>

            <!-- Services -->
            <li>
                <a href="admin.php?page=services"
                   class="<?= ($page == 'services' ? 'active' : '') ?>">
                    <i class="bi bi-hospital"></i> Services
                </a>
            </li>

            <!-- Rendez-vous -->
            <li>
                <a href="admin.php?page=rendezvous"
                   class="<?= ($page == 'rendezvous' ? 'active' : '') ?>">
                    <i class="bi bi-calendar-check"></i> Rendez-vous
                </a>
            </li>

            <!-- Statistiques -->
            <li>
                <a href="admin.php?page=stats"
                   class="<?= ($page == 'stats' ? 'active' : '') ?>">
                    <i class="bi bi-bar-chart"></i> Statistiques
                </a>
            </li>

            <!-- Mon Profil -->
            <li>
                <a href="admin.php?page=profile"
                   class="<?= ($page == 'profile' ? 'active' : '') ?>">
                    <i class="bi bi-person-circle"></i> Mon profil
                </a>
            </li>

            <!-- Déconnexion -->
            <li>
                <a href="logout.php" class="logout">
                    <i class="bi bi-box-arrow-right"></i> Déconnexion
                </a>
            </li>

        </ul>
    </aside>

    <!-- ============================
        MAIN CONTENT
    ============================= -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <div class="theme-toggle-dashboard" onclick="toggleDashboardTheme()">
                <i id="dashboardThemeIcon" class="bi bi-sun-fill"></i>
            </div>

            <h2><?= $title ?></h2>

            <div class="topbar-user">
                <i class="bi bi-person-circle"></i>
                <span><?= $_SESSION['username'] ?></span>
            </div>
        </header>

        <!-- PAGE CONTENT -->
        <section class="content-wrapper">
            <?php
                $file = $page . ".php";

                if (file_exists($file)) {
                    require $file;
                } else {
                    echo "<div class='alert alert-danger'>Page introuvable : $file</div>";
                }
            ?>
        </section>

    </main>

</div>


<script>
function toggleDashboardTheme() {
    const body = document.body;
    const icon = document.getElementById("dashboardThemeIcon");

    if (!icon) return;

    const isDark = body.classList.contains("dark-dashboard");

    if (isDark) {
        body.classList.remove("dark-dashboard");
        icon.classList.replace("bi-moon-fill", "bi-sun-fill");
        localStorage.setItem("dashboardTheme", "light");
    } else {
        body.classList.add("dark-dashboard");
        icon.classList.replace("bi-sun-fill", "bi-moon-fill");
        localStorage.setItem("dashboardTheme", "dark");
    }
}

// attendre que le DOM soit prêt
document.addEventListener("DOMContentLoaded", () => {
    const icon = document.getElementById("dashboardThemeIcon");
    if (!icon) return;

    if (localStorage.getItem("dashboardTheme") === "dark") {
        document.body.classList.add("dark-dashboard");
        icon.classList.replace("bi-sun-fill", "bi-moon-fill");
    }
});
</script>

<!-- Synchroniser le thème avec le cookie -->
<script>
document.cookie = "dashboardTheme=" + localStorage.getItem("dashboardTheme") + "; path=/";
</script>



<!-- Bootstrap JS (OBLIGATOIRE POUR MODALS) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
