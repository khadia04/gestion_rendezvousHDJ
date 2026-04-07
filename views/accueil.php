<?php
require_once '../middlewares/auth.php';
require_once '../modele/database.php';

requirePermission('profile');

$db = getConnection();

// USER
$user = $_SESSION['prenom'] ?? 'Utilisateur';
$role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';

// STATS
$nbRdv = $db->query("SELECT COUNT(*) FROM rendezvs")->fetchColumn();
$nbPatients = $db->query("SELECT COUNT(*) FROM patient")->fetchColumn();
$nbServices = $db->query("SELECT COUNT(*) FROM service")->fetchColumn();

// ✅ FIX 1 : Fonction centralisée de génération de slug (cohérente avec seed_descriptions.php)
function slugifyService(string $name): string {
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    // ✅ FIX 4 : iconv peut retourner false, on sécurise avec ?: ''
    if ($slug === false) $slug = $name;
    $slug = strtolower(trim($slug));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

// TOP 3 SERVICES
if (in_array($role, ['agent', 'medecin'])) {

    // ✅ FIX 2 : Suppression de MAX(s.description) — on sélectionne description directement
    $services = prepare_executeSQL("
        SELECT s.codeService, s.designService, s.description, s.image, COUNT(r.codeService) as totalRdv 
        FROM agent_service a
        JOIN service s ON s.codeService = a.codeService
        LEFT JOIN rendezvs r ON r.codeService = s.codeService
        WHERE a.agent_username = :username
        GROUP BY s.codeService, s.designService, s.description, s.image
        ORDER BY totalRdv DESC
        LIMIT 3
", ['username' => $username])->fetchAll();

} else {

    // ✅ FIX 2 : Suppression de MAX(s.description) — on sélectionne description directement
    $services = executeSQL("
        SELECT s.codeService, s.designService, s.description, s.image, COUNT(r.codeService) as totalRdv 
        FROM service s
        LEFT JOIN rendezvs r ON r.codeService = s.codeService
        GROUP BY s.codeService, s.designService, s.description, s.image
        ORDER BY totalRdv DESC
        LIMIT 3
")->fetchAll();
}
?>

<div class="container-fluid accueil-premium">

<!-- SLIDER -->
<div class="row mb-4 justify-content-center">

    <div class="col-md-12">
        
        <div id="carouselAccueil" class="carousel slide carousel-pro" data-bs-ride="carousel" data-bs-interval="3000">

            <!-- INDICATEURS -->
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#carouselAccueil" data-bs-slide-to="0" class="active"></button>
                <button type="button" data-bs-target="#carouselAccueil" data-bs-slide-to="1"></button>
            </div>

            <div class="carousel-inner rounded-4">

                <!-- SLIDE 1 -->
                <div class="carousel-item active">
                    <img src="https://images.unsplash.com/photo-1584515933487-779824d29309" class="d-block w-100">
                    <div class="carousel-caption custom-caption">
                        <h3>Bienvenue <?= htmlspecialchars($user) ?> 👋</h3>
                        <p>Gestion intelligente des rendez-vous</p>
                        <a href="rendezvous.php" class="btn btn-primary">Prendre RDV</a>
                    </div>
                </div>

                <!-- SLIDE 2 -->
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1579684385127-1ef15d508118" class="d-block w-100">
                    <div class="carousel-caption custom-caption">
                        <h4>Suivi médical optimal</h4>
                        <p>Une meilleure expérience patient</p>
                    </div>
                </div>

            </div>

            <button class="carousel-control-prev custom-control" type="button" data-bs-target="#carouselAccueil" data-bs-slide="prev">
                <i class="bi bi-chevron-left"></i>
            </button>

            <button class="carousel-control-next custom-control" type="button" data-bs-target="#carouselAccueil" data-bs-slide="next">
                <i class="bi bi-chevron-right"></i>
            </button>

        </div>

    </div>

</div>

<!-- PROVERBE -->
<div class="proverbe-box text-center my-5">
    <h5 class="fw-bold text-primary">“NITT NITAY GARABAM”</h5>
    <p class="text-muted fst-italic">
        L’homme est le remède de l’homme
    </p>

    <p class="text-muted small mt-2">
        Prenez rendez-vous avec les meilleurs spécialistes en toute simplicité.
    </p>
</div>

<!-- STATS -->
<div class="row g-4 mb-5 text-center justify-content-center">
    
    
    <div class="col-md-3 fade-up">
        

            <div class="stat-card">
                <i class="bi bi-calendar-check"></i>
                <h2><?= $nbRdv ?></h2>
                <p>Rendez-vous</p>
            </div>
        
    </div>

    <div class="col-md-3 fade-up">
            <div class="stat-card">
                <i class="bi bi-people"></i>
                <h2><?= $nbPatients ?></h2>
                <p>Patients</p>
            </div>
        
    </div>

    <div class="col-md-3 fade-up">
            <div class="stat-card">
                <i class="bi bi-hospital"></i>
                <h2><?= $nbServices ?></h2>
                <p>Services</p>
            </div>
        
    </div>

</div>

<!-- SEARCH -->
<div class="search-box my-4 d-flex justify-content-center">
    <input type="text" id="searchInput" 
        class="form-control search-input" 
        placeholder="Rechercher un service (cardiologie, dermatologie...)">
</div>

<!-- TOP 3 -->
<div id="defaultServices" class="row g-4">

<?php foreach ($services as $service): 
    // ✅ FIX 1 : Utilisation de la fonction centralisée slugifyService()
    $nomImage = slugifyService($service['designService']);
?>

<div class="col-lg-4 col-md-6 col-12">
    <div class="service-card-image">

        <img src="/rendezvous/assets/img/services/<?= htmlspecialchars($service['image'] ?? 'default.jpg') ?>">
             

        <div class="overlay-main">
            <h5><?= htmlspecialchars($service['designService']) ?></h5>
            <p><?= $service['totalRdv'] ?> RDV / mois</p>
        </div>

        <div class="overlay-hover">
            <h5><?= htmlspecialchars($service['designService']) ?></h5>
            <p><?= htmlspecialchars($service['description'] ?? "Service médical spécialisé.") ?></p>
        </div>

    </div>
</div>

<?php endforeach; ?>

</div>

<!-- RESULTATS AJAX -->
<div id="serviceContainer" class="row g-4"></div>

<div class="text-center mt-4">
    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalServices">
        Voir tous les services
    </button>
</div>

</div>

<!-- MODAL -->
<div class="modal fade" id="modalServices">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5>Tous les services</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="row g-4">

                <?php
                // ✅ FIX 5 : Ajout d'un ORDER BY pour un ordre stable et déterministe
                $allServices = executeSQL("SELECT * FROM service ORDER BY designService ASC")->fetchAll();

                foreach ($allServices as $s):
                    // ✅ FIX 1 : Utilisation de la fonction centralisée slugifyService()
                    $nomImage = slugifyService($s['designService']);
                ?>

                <div class="col-md-4">
                    <div class="service-card-image modal-card">

                        <img src="/rendezvous/assets/img/services/<?= htmlspecialchars($s['image'] ?? 'default.jpg') ?>">

                        <div class="overlay-main">
                            <h6><?= htmlspecialchars($s['designService']) ?></h6>
                        </div>

                        <div class="overlay-hover">
                            <h6><?= htmlspecialchars($s['designService']) ?></h6>
                            <p><?= htmlspecialchars($s['description'] ?? "Service médical spécialisé.") ?></p>
                        </div>

                    </div>
                </div>

                <?php endforeach; ?>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- AJAX SEARCH -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    const input = document.getElementById("searchInput");
    const defaultBlock = document.getElementById("defaultServices");
    const container = document.getElementById("serviceContainer");

    let timeout;

    input.addEventListener("keyup", function () {

        clearTimeout(timeout);
        let query = this.value.trim();

        timeout = setTimeout(() => {

            if (query === "") {
                defaultBlock.style.display = "flex";
                container.innerHTML = "";
                return;
            }

            fetch("search_services.php?q=" + encodeURIComponent(query))
                .then(res => res.text())
                .then(data => {

                    defaultBlock.style.display = "none";

                    if (data.trim() === "") {
                        container.innerHTML = "<p class='text-center'>Aucun service trouvé</p>";
                    } else {
                        container.innerHTML = data;
                    }

                })
                .catch(err => {
                    console.error(err);
                    container.innerHTML = "<p class='text-danger'>Erreur de chargement</p>";
                });

        }, 300);

    });

});

const elements = document.querySelectorAll('.fade-up');

window.addEventListener('scroll', () => {
    elements.forEach(el => {
        const position = el.getBoundingClientRect().top;
        if (position < window.innerHeight - 100) {
            el.classList.add('show');
        }
    });
});
</script>