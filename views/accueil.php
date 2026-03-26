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

// TOP 3 SERVICES LES PLUS UTILISÉS
if (in_array($role, ['agent', 'medecin'])) {

    $services = prepare_executeSQL("
        SELECT s.codeService, s.designService, COUNT(r.codeService) as totalRdv
        FROM agent_service a
        JOIN service s ON s.codeService = a.codeService
        LEFT JOIN rendezvs r ON r.codeService = s.codeService
        WHERE a.agent_username = :username
        GROUP BY s.codeService
        ORDER BY totalRdv DESC
        LIMIT 3
    ", ['username' => $username])->fetchAll();

} else {

    $services = executeSQL("
        SELECT s.codeService, s.designService, COUNT(r.codeService) as totalRdv
        FROM service s
        LEFT JOIN rendezvs r ON r.codeService = s.codeService
        GROUP BY s.codeService
        ORDER BY totalRdv DESC
        LIMIT 3
    ")->fetchAll();
}

?>



<div class="container-fluid accueil-premium">

<!-- SLIDER -->
<div class="row mb-4 justify-content-center">

    <div class="col-md-12"> <!--  largeur réduite -->
        
        <div id="carouselAccueil" class="carousel slide carousel-pro" data-bs-ride="carousel" data-bs-interval="4000">

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

            <!--  BOUTONS GAUCHE / DROITE -->
            <button class="carousel-control-prev custom-control" type="button" data-bs-target="#carouselAccueil" data-bs-slide="prev">
                <i class="bi bi-chevron-left"></i>
            </button>

            <button class="carousel-control-next custom-control" type="button" data-bs-target="#carouselAccueil" data-bs-slide="next">
                <i class="bi bi-chevron-right"></i>
            </button>

        </div>

    </div>

</div>

<!-- STATS -->
<div class="row g-4 mb-5 text-center justify-content-center">

    <div class="col-md-3" >
        <div class="stat-card">
            <i class="bi bi-calendar-check"></i>
            <h2><?= $nbRdv ?></h2>
            <p>Rendez-vous</p>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <i class="bi bi-people"></i>
            <h2><?= $nbPatients ?></h2>
            <p>Patients</p>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <i class="bi bi-hospital"></i>
            <h2><?= $nbServices ?></h2>
            <p>Services</p>
        </div>
    </div>

</div>

<!-- SEARCH -->
<div class="search-container">
    <input type="text" id="searchService" 
           class="form-control search-input"
           placeholder="Rechercher un service...">
</div>

<!-- SERVICES -->
<div class="row g-4" id="serviceContainer">

<?php foreach ($services as $service): ?>

<div class="col-md-4">
    <div class="service-card-image">

        <!-- IMAGE -->
       <?php
            $nomImage = iconv('UTF-8', 'ASCII//TRANSLIT', $service['designService']);
            $nomImage = strtolower($nomImage);
            $nomImage = preg_replace('/[^a-z0-9]/', '-', $nomImage);
            $nomImage = preg_replace('/-+/', '-', $nomImage);
            $nomImage = trim($nomImage, '-');
        ?>

        <img src="/rendezvous/assets/img/services/<?= $nomImage ?>.jpg"
            onerror="this.src='/rendezvous/assets/img/services/default.jpg'"
            loading="lazy">
        <!-- TEXTE PAR DÉFAUT -->
        <div class="overlay-main">
            <h5><?= htmlspecialchars($service['designService']) ?></h5>
            <p><?= $service['totalRdv'] ?> RDV / mois</p>
        </div>

        <!-- HOVER -->
        <div class="overlay-hover">
            <h5><?= htmlspecialchars($service['designService']) ?></h5>
            <p>
                Ce service prend en charge les patients avec un suivi rapide, 
                des consultations spécialisées et une prise en charge optimale.
            </p>
        </div>

    </div>
</div>

<?php endforeach; ?>

</div>

<div class="text-center mt-4">
    <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#modalServices">
        Voir tous les services
    </button>
</div>

</div>

<!-- MODAL TOUS LES SERVICES -->
 <div class="modal fade" id="modalServices">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5>Tous les services</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="row g-4">

            <?php
            $allServices = executeSQL("SELECT * FROM service")->fetchAll();

            foreach ($allServices as $s):

                $nomImage = iconv('UTF-8', 'ASCII//TRANSLIT', $s['designService']);
                $nomImage = strtolower($nomImage);
                $nomImage = preg_replace('/[^a-z0-9]/', '-', $nomImage);
                $nomImage = preg_replace('/-+/', '-', $nomImage);
                $nomImage = trim($nomImage, '-');
            ?>

            <div class="col-md-4">
                <div class="service-card-image modal-card">

                    <!-- IMAGE -->
                    <img src="/rendezvous/assets/img/services/<?= $nomImage ?>.jpg"
                        onerror="this.src='/rendezvous/assets/img/services/default.jpg'">

                    <!-- TEXTE NORMAL -->
                    <div class="overlay-main">
                        <h6><?= htmlspecialchars($s['designService']) ?></h6>
                    </div>

                    <!-- HOVER -->
                    <div class="overlay-hover">
                        <h6><?= htmlspecialchars($s['designService']) ?></h6>
                        <p>
                            Service médical spécialisé avec prise en charge rapide, 
                            suivi efficace et consultations adaptées.
                        </p>
                    </div>

                </div>
            </div>

            <?php endforeach; ?>

            </div>

        </div>
    </div>
</div>


<script>
document.getElementById("searchService").addEventListener("keyup", function() {
    let value = this.value.toLowerCase();
    let cards = document.querySelectorAll("#serviceContainer .col-md-4");

    cards.forEach(card => {
        let text = card.innerText.toLowerCase();
        card.style.display = text.includes(value) ? "block" : "none";
    });
});
</script>