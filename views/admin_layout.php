<?php

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: ../login.php?session=expired");
    exit;
}


// Gestion de l'inactivité
// if (isset($_SESSION['lastAction'], $_SESSION['timeframe'])) {
//    if ((time() - $_SESSION['lastAction']) > $_SESSION['timeframe']) {
 //       session_destroy();
 //       header("Location: ../index.php?session=expired");
 //       exit;
//    }
// }

// Mise à jour de l’activité
$_SESSION['lastAction'] = time();


require_once "../modele/database.php";
require_once "../modele/databasePatient.php";
require_once "../modele/databaseTools.php";
require_once "../modele/databaseRv.php";



// Vérifier rôle admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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

// ============================
// INFOS ADMIN CONNECTÉ
// ============================
$db = getConnection();

$stmt = $db->prepare("
    SELECT prenom_agent, nom_agent, email, photo
    FROM agent
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Sécurité fallback
$prenom = $admin['prenom_agent'] ?? 'Admin';
$nom    = $admin['nom_agent'] ?? '';
$email  = $admin['email'] ?? '';
$path = '../assets/img/' . $admin['photo'];
$avatar = file_exists($path)
    ? $path . '?v=' . filemtime($path)
    : '../assets/img/avatar.jpg';


$stmt = $db->prepare("
    SELECT prenom_agent, nom_agent, email, telephone_agent, photo
    FROM agent
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

$prenom = $admin['prenom_agent'] ?? 'Admin';
$nom    = $admin['nom_agent'] ?? '';
$email  = $admin['email'] ?? '';
$tel    = $admin['telephone_agent'] ?? '';

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
    <link href="../assets/img/logo.png" rel="icon" />

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body class="<?= $page ?> dark">
    <main class="main-content">
       

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>


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
            <li class="sidebar-item logout-item">
                <form method="POST" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="sidebar-link logout-link">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Déconnexion</span>
                    </button>
                </form>
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

            <div class="profile-trigger" id="profileMenuBtn">
                <img
                    src="<?= $avatar ?>"
                    class="topbar-avatar profile-preview"
                    alt="Avatar"
                >

                <div class="topbar-user">
                    <span class="topbar-name">
                        <?= htmlspecialchars($prenom . ' ' . $nom) ?>
                    </span>
                    <small class="topbar-role">Administrateur</small>
                </div>

            </div>

            <div class="profile-dropdown" id="profileDropdown">

                <!-- HEADER -->
                <div class="profile-header">
                    <img src="<?= $avatar ?>" class="dropdown-avatar profile-preview">

                    <div class="profile-info">
                        <div class="profile-hello">
                            Bonjour <strong><?= htmlspecialchars($prenom) ?></strong> 👋
                        </div>
                        <div class="profile-email">
                            <?= htmlspecialchars($email) ?>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- ACTIONS -->
                <a href="admin.php?page=profile" class="dropdown-item">
                    <i class="bi bi-gear"></i>
                    <span>Gérer votre compte</span>
                </a>

                <form method="POST" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <button type="submit" class="dropdown-item logout">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Se déconnecter</span>
                    </button>
                </form>

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
 </main>

<script src="../assets/js/dashboard-theme.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Bootstrap JS (OBLIGATOIRE POUR MODALS) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="../assets/js/password-strength.js"></script>



<script>
const btn = document.getElementById('profileMenuBtn');
const menu = document.getElementById('profileDropdown');

btn.addEventListener('click', () => {
    menu.classList.toggle('show');
});

document.addEventListener('click', (e) => {
    if (!btn.contains(e.target) && !menu.contains(e.target)) {
        menu.classList.remove('show');
    }
});
</script>


</body>
</html>
