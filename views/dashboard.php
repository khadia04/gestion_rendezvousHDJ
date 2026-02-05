<?php
// =========================
  // ÉTAPE 1 : RÉCUPÉRATION DONNÉES

 $mois = (!empty($_GET['month']) && (int)$_GET['month'] >= 1)
    ? (int)$_GET['month']
    : null;

$annee = isset($_GET['year']) 
    ? (int) $_GET['year'] 
    : date('Y');

$rdvPerMonth   = getRdvPerMonth($mois, $annee);

$isAdmin  = ($_SESSION['role'] === 'admin');
$username = $_SESSION['username'];

if ($isAdmin) {
    // ADMIN → stats globales
    $rdvPerService = getRdvPerService($mois, $annee);
} else {
    // AGENT → uniquement SES services
    $rdvPerService = getRdvPerServiceByAgent($username, $mois, $annee);
}


/* =========================
   DONNÉES GRAPHIQUE SERVICES
========================= */
$serviceLabels = [];
$serviceData   = [];
$serviceNames  = [];

if (empty($rdvPerService)) {
    $serviceLabels = ["Aucun"];
    $serviceNames  = ["Aucun service"];
    $serviceData   = [1];
} else {
    foreach ($rdvPerService as $row) {
        $serviceLabels[] = $row['codeService'];       // CUR, PED…
        $serviceNames[]  = $row['designService'];     // Chirurgie…
        $serviceData[]   = (int)$row['total'];
    }
}


$monthNames = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];

/* =========================
   DONNÉES GRAPHIQUE MOIS
========================= */
$months = ["Jan", "Fév", "Mar", "Avr", "Mai", "Juin", "Juil", "Août", "Sep", "Oct", "Nov", "Déc"];
$monthData = array_fill(0, 12, 0);

if (!empty($rdvPerMonth)) {
    foreach ($rdvPerMonth as $row) {
        $monthIndex = (int)$row['mois'] - 1;
        $monthData[$monthIndex] = (int)$row['total'];
    }
}


// =========================
  // ÉTAPE 2 : CALCUL VARIATION
$moisActuel = $mois ?? date('n');
$anneeActuelle = $annee;

$moisPrecedent = $moisActuel - 1;
$anneePrecedente = $anneeActuelle;

if ($moisPrecedent === 0) {
    $moisPrecedent = 12;
    $anneePrecedente--;
}

$rdvActuel = getRdvCountByMonth($moisActuel, $anneeActuelle);
$rdvPrecedent = getRdvCountByMonth($moisPrecedent, $anneePrecedente);

// Calcul du pourcentage
if ($rdvPrecedent > 0) {
    $variation = (($rdvActuel - $rdvPrecedent) / $rdvPrecedent) * 100;
} else {
    $variation = 100;
}

// Variables utilisées dans la carte
$totalRdv = $rdvActuel;   // Total RDV du mois sélectionné
$percent  = $variation;  // Pourcentage de variation


// Calcul variation annuelle

$rdvYearCurrent = getRdvCountByYear($annee);
$rdvYearPrev    = getRdvCountByYear($annee - 1);

if ($rdvYearPrev > 0) {
    $yearVariation = (($rdvYearCurrent - $rdvYearPrev) / $rdvYearPrev) * 100;
} else {
    $yearVariation = 100;
}


?>



<div class="dashboard-overview">

    <!-- TITRE -->
    <h3 class="mb-4">Tableau de Bord</h3>

    <!-- STAT CARDS -->
    <div class="dashboard-cards" style="margin-right: 10px;">


        <div class="col-md-3 col-sm-6">
            <div class="stat-card fade-up h-100">
                <i class="bi bi-people-fill stat-icon"></i>
                <h5>Patients</h5>
                <p class="stat-number"><?= getTotalPatients(); ?></p>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="stat-card fade-up h-100" >
                <i class="bi bi-calendar-date stat-icon" ></i>
                <h5 >RDV Aujourd’hui</h5>
                <p class="stat-number"><?= getTodayRdv(); ?></p>
            </div>
        </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card fade-up h-100 text-center">

            <i class="bi bi-calendar-check stat-icon"></i>
            <h5>Total RDV</h5>

            <!-- TOTAL -->
            <p class="stat-number" id="totalRdv"><?= $totalRdv ?></p>
            
        </div>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="stat-card fade-up h-100">
            <i class="bi bi-person-badge stat-icon"></i>
            <h5>Agents</h5>
            <p class="stat-number"><?= getTotalAgents(); ?></p>
        </div>
    </div>
    </div>


    <!-- ACTIONS RAPIDES -->
    <h4 class="mt-5">Actions Rapides</h4>
    <div class="row g-4 quick-actions">

        <div class="col-md-3 col-sm-6">
            <a href="admin.php?page=agents" class="quick-btn">
                <i class="bi bi-person-plus"></i> Ajouter un Agent
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="admin.php?page=services" class="quick-btn">
                <i class="bi bi-hospital"></i> Gérer les Services
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="admin.php?page=rendezvous" class="quick-btn">
                <i class="bi bi-calendar2-check"></i> Voir Rendez-vous
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="admin.php?page=patients" class="quick-btn">
                <i class="bi bi-search"></i> Rechercher un patient
            </a>
        </div>

    </div>

    



    <!-- GRAPHIQUES -->
    <h4 class="mt-5" id="stats">Statistiques Visuelles</h4>


    <p class="text-muted mb-4">
    Statistiques pour 
    <?= $mois && isset($monthNames[$mois]) 
        ? "le mois <strong>{$monthNames[$mois]}</strong>" 
        : "toute l’année" ?>
    - <strong><?= $annee ?></strong>
    </p>


    <!-- FILTRAGE -->
    <form method="get" id="statsFilterForm" action="admin.php#stats" class="row g-3 align-items-center mb-4">

        <!-- MOIS -->
        <div class="col-md-4">
            <select name="month" class="form-select">
                <option value="">Tous les mois</option>
                <?php foreach ($monthNames as $num => $name): ?>
                    <option value="<?= $num ?>" <?= ($mois == $num) ? 'selected' : '' ?>>
                        <?= $name ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ANNÉE -->
        <div class="col-md-3">
            <select name="year" class="form-select">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y ?>" <?= ($annee == $y) ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- BOUTONS -->
        <div class="col-md-5 d-flex gap-2">

            <!-- FILTRER -->
            <button type="submit" class="btn btn-primary" title="Filtrer">
                <i class="bi bi-funnel"></i>
            </button>

            <!-- EXPORT PDF -->
            <a
                href="../exports/export_stats_pdf.php?<?= http_build_query([
                    'month' => $mois,
                    'year'  => $annee
                ]) ?>"
                target="_blank"
                class="btn btn-danger"
                title="Exporter en PDF"
            >
                <i class="bi bi-file-earmark-pdf"></i>
            </a>

            <!-- EXPORT EXCEL -->
            <a
                href="../exports/export_stats_excel.php?<?= http_build_query([
                    'month' => $mois,
                    'year'  => $annee
                ]) ?>"
                class="btn btn-success"
                title="Exporter en Excel"
            >
                <i class="bi bi-file-earmark-excel"></i>
            </a>

        </div>
    </form>
    
    <!-- LOADER -->
    <div id="loading" class="text-center mt-2" style="display:none;">
        <div class="spinner-border text-primary spinner-border-sm"></div>
        <span class="ms-2">Chargement des statistiques…</span>
    </div>






    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="chart-card">
                <h6 class="mb-3 ">Rendez-vous par mois</h6>
                <canvas id="rdvPerMonth" ></canvas>
                <h4 class="chart-title" >
                    Évolution mensuelle des rendez-vous
                </h4>
            
            </div>

        </div>

        <div class="col-md-6">
            <div class="chart-card" >
                <p class="text-muted">
                    <?= $mois
                        ? "Mois de <strong>{$monthNames[$mois]}</strong>"
                        : "Année <strong>$annee</strong>"
                    ?>
                </p>

                <canvas id="rdvPerService"></canvas>
                 <h4 class="chart-title">
                    Répartition des rendez-vous par service
                </h4>
            </div>
        </div>
    </div>

</div>

<?php
// Génération automatique de couleurs
$serviceColors = [];

foreach ($serviceLabels as $index => $code) {
    $serviceColors[] = "hsl(" . ($index * 45 % 360) . ", 70%, 55%)";
}
?>


<!--========================= 
ÉTAPE 3 : SCRIPTS CHART.JS 
========================== -->
<script>
const serviceNames = <?= json_encode($serviceNames) ?>;

new Chart(document.getElementById("rdvPerService"), {
    type: "doughnut",
    data: {
        labels: <?= json_encode($serviceLabels) ?>,
        datasets: [{
            data: <?= json_encode($serviceData) ?>,
            backgroundColor: <?= json_encode($serviceColors) ?>
        }]
    },
    options: {
        responsive: true,

        animation: {
            duration: 900,
            easing: 'easeOutQuart'
        },

        plugins: {
            tooltip: {
                callbacks: {
                    title: function () {
                        return "";
                    },
                    label: function(context) {
                        const index = context.dataIndex;
                        const fullName = serviceNames[index];
                        const value = context.raw;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percent = ((value / total) * 100).toFixed(1);

                        return `${fullName} : ${value} RDV (${percent}%)`;
                    }
                }
            },
            legend: {
                position: "top"
            }
        }
    }
});
</script>




<script>
    const monthColors = [
    "#1abc9c", // Janvier
    "#3498db", // Février
    "#9b59b6", // Mars
    "#e67e22", // Avril
    "#e74c3c", // Mai
    "#f1c40f", // Juin
    "#2ecc71", // Juillet
    "#16a085", // Août
    "#2980b9", // Septembre
    "#8e44ad", // Octobre
    "#d35400", // Novembre
    "#c0392b"  // Décembre
    ];

new Chart(document.getElementById("rdvPerMonth"), {
    type: "line",
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: "Rendez-vous",
            data: <?= json_encode($monthData) ?>,
            borderColor: "#006aff",
            backgroundColor: "rgba(0,106,255,0.15)",
            tension: 0.4,
            borderWidth: 3,
            fill: true,

            pointRadius: 6,
            pointHoverRadius: 8,
            pointBackgroundColor: monthColors,
            pointBorderColor: "#fff",
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,

        plugins: {
            legend: {
                display: true,

                labels: {
                    boxWidth: 14,
                    boxHeight: 14,
                    padding: 15,
                    font: {
                        size: 12,
                        weight: 'bold'
                    },

                    generateLabels: function(chart) {
                        const labels = chart.data.labels;
                        return labels.map((label, i) => ({
                            text: label,
                            fillStyle: monthColors[i],
                            strokeStyle: monthColors[i],
                            lineWidth: 2
                        }));
                    }
                }
            }
        },

        animation: {
            duration: 900,
            easing: 'easeOutQuart'
        }
    }
});

</script>

<script>
function animateValue(id, start, end, duration = 800) {
    const element = document.getElementById(id);
    let startTimestamp = null;

    function step(timestamp) {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        element.innerText = Math.floor(progress * (end - start) + start);
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    }
    window.requestAnimationFrame(step);
}

// lancer animation
animateValue("totalRdv", 0, <?= $totalRdv ?>);


</script>


<script>
window.addEventListener('beforeunload', () => {
  localStorage.setItem('dashboardScroll', window.scrollY);
});

document.addEventListener('DOMContentLoaded', () => {
  const scrollPos = localStorage.getItem('dashboardScroll');
  if (scrollPos !== null) {
    window.scrollTo({
      top: parseInt(scrollPos),
      behavior: 'instant'
    });
    localStorage.removeItem('dashboardScroll');
  }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {

    const statsForm   = document.getElementById('statsFilterForm');
    const statsLoader = document.getElementById('loading');

    if (!statsForm || !statsLoader) return;

    statsForm.addEventListener('submit', () => {
        statsLoader.style.display = 'block';
    });

});
</script>




