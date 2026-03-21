<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


requireRole(['super_admin', 'admin', 'medecin', 'agent']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../Modele/database.php';
$db = getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pageNum = max(1, (int)($_GET['p'] ?? 1));
$limit   = 10;
$offset  = ($pageNum - 1) * $limit;



/* =========================
   SERVICES
========================= */
if ($_SESSION['role'] === 'admin') {

    $services = $db->query("
        SELECT codeService, designService
        FROM service
        ORDER BY designService
    ")->fetchAll(PDO::FETCH_ASSOC);

} else {
    // AGENT → seulement ses services
    $stmt = $db->prepare("
        SELECT s.codeService, s.designService
        FROM service s
        INNER JOIN agent_service ags
            ON s.codeService = ags.codeService
        WHERE ags.agent_username = ?
        ORDER BY s.designService
    ");
    $stmt->execute([$_SESSION['username']]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/* =========================
   FILTRES
========================= */
$service = $_GET['service'] ?? '';
$periode = $_GET['periode'] ?? 'jour';
$date    = $_GET['date'] ?? date('Y-m-d');

/* =========================
   LISTE RDV (INDEX + NOINDEX)
========================= */
$sqlBase = "
SELECT *
FROM (
    SELECT
        r.numeroDossierPatient AS dossier,
        CONCAT(p.prenomPatient, ' ', p.nomPatient) AS patient,
        p.telephonePatient AS telephone,
        s.designService AS service,
        s.codeService AS codeService, --  AJOUT
        r.dateDemande,
        r.dateRvServ
    FROM rendezvs r
    JOIN patient p ON p.numeroDossierPatient = r.numeroDossierPatient
    JOIN service s ON s.codeService = r.codeService

    UNION ALL

    SELECT
        'Sans index',
        CONCAT(n.prenomPatient, ' ', n.nomPatient),
        n.telephonePatient,
        s.designService,
        s.codeService,
        n.dateDemande,
        n.dateDisponible AS dateRvServ
    FROM patientnoindex n
    JOIN service s ON s.codeService = n.codeService

) t
WHERE 1=1
";

$params = [];

if ($service) {
    $sqlBase .= " AND t.codeService = ?";
    $params[] = $service;
}

if ($periode === 'jour') {
    $sqlBase .= " AND t.dateRvServ >= ? AND t.dateRvServ < DATE_ADD(?, INTERVAL 1 DAY)";
    $params[] = $date;
    $params[] = $date;
} elseif ($periode === 'mois') {
    $sqlBase .= " AND MONTH(t.dateRvServ)=MONTH(?) AND YEAR(t.dateRvServ)=YEAR(?)";
    $params[] = $date;
    $params[] = $date;
} elseif ($periode === 'annee') {
    $sqlBase .= " AND YEAR(t.dateRvServ)=YEAR(?)";
    $params[] = $date;
}





$filtresActifs = false;

if (!empty($service)) $filtresActifs = true;
if ($periode !== 'jour') $filtresActifs = true;
/* =========================
   TRI SELON LA PÉRIODE
========================= */
if ($periode === 'jour') {
    // 🔥 File d’attente du jour :
    // le premier qui a demandé passe en premier
    $orderBy = "ORDER BY t.dateDemande ASC";
} else {
    // 🔥 Planning (mois / année) :
    // RDV le plus proche en premier
    $orderBy = "ORDER BY t.dateRvServ ASC";
}
if ($date !== date('Y-m-d')) $filtresActifs = true;

// COUNT
$countSql = "SELECT COUNT(*) FROM ($sqlBase) x";
$stmtCount = $db->prepare($countSql);
$stmtCount->execute($params);
$total = (int)$stmtCount->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

// DATA
$sqlFinal = $sqlBase . " $orderBy LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($sqlFinal);
$stmt->execute($params);
$rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rangJour = 1;

if ($periode === 'jour') {
    foreach ($rendezvous as $k => $rv) {
        $rendezvous[$k]['rang_jour'] = $rangJour;
        $rangJour++;
    }
}

?>

<?php if ($_SESSION['role'] === 'agent' && empty($services)): ?>
  <div class="alert alert-warning">
    Aucun service ne vous est attribué.<br>
    Veuillez contacter un administrateur.
  </div>
  <?php return; ?>
<?php endif; ?>







<!-- =========================
     PAGE RENDEZ-VOUS
========================= -->

<div class="page-header mb-3">
    <h4 class="fw-bold">Visualisation et filtrage des rendez-vous</h4>
    <br>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="text-muted mb-0">
        Liste des rendez-vous
        <?php if ($filtresActifs): ?>
            <span class="badge bg-warning text-dark ms-2">
                Filtres actifs
            </span>
        <?php endif; ?>
    </h6>


    <?php if ($_SESSION['role'] === 'admin'): ?>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRdvModal">
            <i class="bi bi-plus-circle"></i> Ajouter un RDV
        </button>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3 filters-animated">
            <!-- FILTRE SERVICE -->
            <div class="col-md-3 position-relative">
                <label class="form-label">Service</label>

                <input
                    type="text"
                    id="filterServiceSearch"
                    class="form-control "
                    placeholder="Rechercher un service..."
                    autocomplete="off"
                    value="<?= $service ? htmlspecialchars(
                        array_values(
                            array_filter($services, fn($s) => $s['codeService'] === $service)
                        )[0]['designService'] ?? ''
                    ) : '' ?>"
                >

                <input type="hidden" id="filterService" value="<?= htmlspecialchars($service) ?>">

                <div id="filterServiceDropdown" class="service-dropdown d-none">
                    <?php foreach ($services as $s): ?>
                        <div
                            class="service-item"
                            data-code="<?= $s['codeService'] ?>"
                        >
                            <strong><?= strtoupper($s['designService']) ?></strong><br>
                            <small class="text-muted"><?= $s['codeService'] ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>


            <!-- FILTRE PÉRIODE -->
            <div class="col-md-3">
                <label class="form-label">Période</label>
                <select class="form-select" id="filterPeriod">
                    <option value="jour"   <?= $periode==='jour'?'selected':'' ?>>Jour</option>
                    <option value="mois"   <?= $periode==='mois'?'selected':'' ?>>Mois</option>
                    <option value="annee"  <?= $periode==='annee'?'selected':'' ?>>Année</option>
                </select>

            </div>

            <!-- FILTRE DATE -->
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" id="filterDate"
                    value="<?= htmlspecialchars($date) ?>">

            </div>

            <!-- Actions -->
            <div class="col-md-3  actions gap-2 align-self-end mb-1 ">
                <button class="btn btn-primary" id="applyFiltersBtn" title="Filtrer">
                    <i class="bi bi-funnel"></i>
                </button>

                <?php if ($filtresActifs): ?>
                    <a href="?page=rendezvous" class="btn btn-outline-secondary" title="Réinitialiser">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                <?php endif; ?>

                <?php if ($total > 0): ?>
                    <a
                        href="../exports/export_rdv_pdf.php?<?= http_build_query($_GET) ?>"
                        class="btn btn-outline-danger"
                        target="_blank"
                        title="Exporter PDF">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                <?php endif; ?>
            </div>


        </div>
<!-- =========================
     TABLEAU
========================= -->

<div class="chart-card">

    
    
    
    <div class="table-responsive">

        

        <!-- TOTAL PATIENT (avec filtre) -->
        <div class="d-flex justify-content-between align-items-center mb-2">
            <small class="text-muted">
                <?= $total ?> rendez-vous trouvé<?= $total > 1 ? 's' : '' ?>
            </small>
        </div>
                
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Dossier</th>
                    <th>Patient</th>
                    <th>Téléphone</th>
                    <th>Service</th>
                    <th>Date demande</th>
                    <th>Date RDV</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rendezvous): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Aucun rendez-vous trouvé
                    </td>
                </tr>
            <?php else: foreach ($rendezvous as $rv): ?>
                <tr class="<?= ($periode === 'jour' && ($rv['rang_jour'] ?? 0) === 1) ? 'table-success fw-bold' : '' ?>">

                    <td>
                        <?= htmlspecialchars($rv['dossier']) ?>

                        <?php if ($periode === 'jour' && isset($rv['rang_jour'])): ?>
                            <?php if ($rv['rang_jour'] === 1): ?>
                                <span class="badge bg-danger ms-2">1er</span>
                            <?php elseif ($rv['rang_jour'] === 2): ?>
                                <span class="badge bg-warning text-dark ms-2">2e</span>
                            <?php elseif ($rv['rang_jour'] === 3): ?>
                                <span class="badge bg-secondary ms-2">3e</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    

                    <td><?= htmlspecialchars($rv['patient']) ?></td>
                    <td><?= htmlspecialchars($rv['telephone']) ?></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($rv['service']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($rv['dateDemande'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($rv['dateRvServ'])) ?></td>

                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>

        <nav>
            <ul class="pagination justify-content-center">
                <?php
                    $paramsPage = $_GET;
                    $paramsPage['page'] = 'rendezvous';
                ?>
                <?php for ($i=1; $i<=$totalPages; $i++): ?>
                    <li class="page-item <?= $i==$pageNum?'active':'' ?>">
                        <?php
                        $paramsPage['p'] = $i;
                        ?>
                        <a class="page-link" href="?<?= http_build_query($paramsPage) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>

    </div>
</div>

<!-- =========================
     MODAL AJOUT RDV
========================= -->
<div class="modal fade" id="addRdvModal" tabindex="-1">
<div class="modal-dialog modal-xl modal-dialog-centered">
<div class="modal-content">

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    
    <input type="hidden" name="idRv" id="idRv">

<div class="modal-header">
    <h5 class="modal-title" id="rdvModalTitle">
        <i class="bi bi-calendar-plus"></i> Ajouter un rendez-vous
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<?php if (!empty($errorMsg)): ?>
    <div class="alert alert-danger"><?= $errorMsg ?></div>
<?php endif; ?>

<?php if (!empty($successMsg)): ?>
    <div class="alert alert-success"><?= $successMsg ?></div>
<?php endif; ?>

<div class="mb-3">
    <label class="form-label">Type de patient</label>

    <div class="d-flex gap-2">
        <button
            type="button"
            id="btnPatientIndex"
            class="btn btn-primary patient-type-btn active"
            data-value="index">
            Patient avec index
        </button>

        <button
            type="button"
            class="btn btn-outline-secondary patient-type-btn"
            data-value="noindex">
            Patient sans index
        </button>

        <button
            type="button"
            class="btn btn-outline-success patient-type-btn"
            data-value="new_index">
            Nouveau patient
        </button>

    </div>

    <input type="hidden" name="patient_type" id="patientType" value="index">

    <!-- Champ caché envoyé au backend -->
    <input type="hidden" name="is_new_index" id="isNewIndex" value="0">
</div>


<div class="row g-3">

    <div id="indexFields" class="section-animated show">

        <!-- numéro de dossier + feedback -->
        <!-- Numéro de dossier -->
         <div class="row">
            <div class="col-md-6">
                <label class="form-label">Numéro de dossier</label>
                <input
                    type="number"
                    name="numeroDossierPatient"
                    id="patientIndexInput"
                    class="form-control"
                    placeholder="Entrez le numéro de dossier du patient"
                >
                <div id="patientFeedback" class="mt-2"></div>
            </div>

            <div class="col-md-6">
                <label class="form-label">Numéro de téléphone</label>
                <input
                    type="text"
                    id="phoneSearchInput"
                    class="form-control"
                    placeholder="Rechercher par téléphone"
                    inputmode="numeric"
                >

            </div>

        </div>
    </div>

     <!-- champs pour patient sans index -->
    <!-- PATIENT SANS INDEX -->
    <div id="noIndexFields" class="section-animated d-none">


        <h5 class="text-muted mb-2">Informations du patient</h5>

        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Prénom complet</label>
                <input type="text" name="prenomComplet" class="form-control" placeholder="Entrer le prénom complet ">
            </div>

            <div class="col-md-3">
                <label class="form-label">Nom</label>
                <input type="text" name="nom" class="form-control" placeholder="Entrer le nom du patient">
            </div>

            <div class="col-md-3">
                <label class="form-label">Sexe</label>
                <select name="sexe" class="form-select">
                    <option value="">--</option>
                    <option value="F">Féminin</option>
                    <option value="M">Masculin</option>
                </select>
            </div>

            <div class="col-md-3">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Date de naissance</label>
                        <input type="date" name="dateNaissance" id="dateNaissance" class="form-control" >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Âge</label>
                        <input type="number" name="age" id="ageInput" class="form-control">
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label">Nationalité</label>
                <input type="text" name="nationalite" class="form-control" placeholder="Entrer la nationalité du patient">
            </div>

            <div class="col-md-3">
                <label class="form-label">Email</label>
                <input type="email" name="emailPatient" class="form-control" placeholder="Entrer l'email du patient">
            </div>

            <div class="col-md-3">
                <label class="form-label">Groupe sanguin</label>
                <select name="groupeSanguin" class="form-select">
                    <option value="">--</option>
                    <option>O+</option><option>O-</option>
                    <option>A+</option><option>A-</option>
                    <option>B+</option><option>B-</option>
                    <option>AB+</option><option>AB-</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Numéro CNI / Passeport</label>
                <input type="text" name="identiteOfficielle" class="form-control" placeholder="Entrer le numéro CNI ou Passeport">
            </div>
        </div>

        <hr>

        <div class="row">
            <div class="col-md-6">
                <h5 class="text-muted mb-2">Coordonnées</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input
                            type="tel"
                            id="telephonePatient"
                            class="form-control"
                            placeholder="ex: 77 123 45 67"
                            >
                        <input type="hidden" name="telephonePatient" id="telephonePatientFull">

                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="adresse" class="form-control" placeholder="Entrer l'adresse du patient">
                    </div>
                </div>
            </div>
        
            <div class="col-md-6">
                <h5 class="text-muted mb-2">Contact d’urgence</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom du contact</label>
                        <input type="text" name="urgenceNom" class="form-control" placeholder="Entrer le nom du contact d'urgence">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Téléphone du contact</label>
                        <!-- Téléphone du contact d'urgence -->
                        <input
                            type="tel"
                            id="urgenceTelephoneInput"
                            class="form-control"
                            placeholder="ex: 77 123 45 67"
                        >
                        <input
                            type="hidden"
                            name="urgenceTelephone"
                            id="urgenceTelephoneFull"
                        >

                    </div>
                </div>
            </div>

         </div>
        

        <hr>

    </div>
    <!-- FIN PATIENT SANS INDEX -->


    <!-- Service -->
    <div class="col-md-5 position-relative">
        <label class="form-label">Service</label>

        <input
            type="text"
            id="serviceSearch"
            class="form-control"
            placeholder="Rechercher un service..."
            autocomplete="off"
            required
        >

        <input type="hidden" name="codeService" id="codeService">

        <div id="serviceDropdown" class="service-dropdown d-none">
            <?php foreach ($services as $s): ?>
                <div
                    class="service-item"
                    data-code="<?= $s['codeService'] ?>"
                >
                    <strong><?= strtoupper($s['designService']) ?></strong><br>
                    <small class="text-muted"><?= $s['codeService'] ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>


<div class="progress mb-3 d-none" id="rdvProgress">
  <div class="progress-bar" id="rdvProgressBar" style="width: 0%"></div>
</div>

<hr class="my-3">

<!-- CALENDRIER -->
<div id="calendarWrapper" class="d-none">
    <div class="calendar-container justify-content-between ">
        <div class="calendar-header d-flex justify-content-between align-items-center mb-2">
            <button type="button" id="prevMonth" class="btn btn-sm btn-outline-secondary">‹</button>
            <strong id="calendarTitle" class="calendar-title"></strong>
            <div id="monthPicker" class="month-picker d-none">
                <select id="pickerMonth" class="form-select form-select-sm">
                    <option value="0">Janvier</option>
                    <option value="1">Février</option>
                    <option value="2">Mars</option>
                    <option value="3">Avril</option>
                    <option value="4">Mai</option>
                    <option value="5">Juin</option>
                    <option value="6">Juillet</option>
                    <option value="7">Août</option>
                    <option value="8">Septembre</option>
                    <option value="9">Octobre</option>
                    <option value="10">Novembre</option>
                    <option value="11">Décembre</option>
                </select>

                <select id="pickerYear" class="form-select form-select-sm">
                    <!-- rempli en JS -->
                </select>

                <button class="btn btn-primary btn-sm w-100" id="applyMonth">
                    Valider
                </button>
            </div>

            <button type="button" id="nextMonth" class="btn btn-sm btn-outline-secondary">›</button>
        </div>

        <div class="calendar-weekdays">
            <div>lu</div><div>ma</div><div>me</div>
            <div>je</div><div>ve</div><div>sa</div><div>di</div>
        </div>

        <div class="calendar-grid" id="calendar"></div>

        <!-- Légende -->
        <div class="calendar-legend mt-3">
            <div id="calendarSuggestions" class="mt-3 d-none">
                <div id="suggestionsList" class="d-flex flex-wrap gap-2 mt-2"></div>
            </div>
            <span><i class="dot disponible"></i> Disponible</span>
            <span><i class="dot moyen"></i> Disponibilité moyenne</span>
            <span><i class="dot plein"></i> Complet</span>
            <span><i class="dot disabled"></i> Service indisponible</span>
            <span><i class="dot ferie"></i> Jour férié</span>
        </div>

        <input type="hidden" name="dateRvServ" id="selectedDate" required>
    </div>

    

</div>





</div>

<div class="modal-footer">
    <button type="button" id="btnSave" class="btn btn-primary" disabled>
        <i class="bi bi-check-circle"></i> Enregistrer le RDV
    </button>



    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
</div>

</form>
</div>
</div>
</div>



<!-- MODAL CONFIRMATION / MESSAGE -->
<div class="modal fade" id="actionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="actionModalTitle"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="actionModalBody"></div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary d-none" id="modalCancel" data-bs-dismiss="modal">
          Annuler
        </button>
        <button type="button" class="btn btn-primary d-none" id="modalConfirm">
          Confirmer
        </button>
        <button type="button" class="btn btn-primary d-none" id="modalOk" data-bs-dismiss="modal">
          OK
        </button>
      </div>

    </div>
  </div>
</div>

<!-- MODAL SUCCÈS RDV -->
<div class="modal fade" id="successModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">
          ✅ Rendez-vous enregistré avec succès
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <ul class="list-group" id="successRecap"></ul>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-primary" id="btnNewRdv">
          ➕ Ajouter un nouveau RDV
        </button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          Fermer
        </button>
      </div>

    </div>
  </div>
</div>

<!-- =========================
     INTL-TEL-INPUT
========================= -->
<link rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/css/intlTelInput.css">
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/intlTelInput.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"></script>


<!-- =========================
     JS
========================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* =========================
   VARIABLES GLOBALES
========================= */
const patientInput      = document.getElementById('patientIndexInput');
const patientFeedback   = document.getElementById('patientFeedback');
const serviceSearch     = document.getElementById('serviceSearch');
const serviceDropdown   = document.getElementById('serviceDropdown');
const hiddenService     = document.getElementById('codeService');
const calendarWrapper   = document.getElementById('calendarWrapper');
const calendar          = document.getElementById('calendar');
const calendarTitle     = document.getElementById('calendarTitle');
const selectedDateInput = document.getElementById('selectedDate');
const saveBtn           = document.getElementById('btnSave');

const patientTypeInput  = document.getElementById('patientType');
const indexFields       = document.getElementById('indexFields');
const noIndexFields     = document.getElementById('noIndexFields');

const actionModalEl = document.getElementById('actionModal');
const actionModal   = new bootstrap.Modal(actionModalEl);
const modalTitle    = document.getElementById('actionModalTitle');
const modalBody     = document.getElementById('actionModalBody');
const btnConfirm    = document.getElementById('modalConfirm');
const btnCancel     = document.getElementById('modalCancel');
const btnOk         = document.getElementById('modalOk');

let currentDate = new Date();
let isSubmitting = false;

// 🔹 Données patient courant (pour le récapitulatif)
let currentPatientName  = '';
let currentPatientPhone = '';


let skipReloadAfterSuccess = false;



/* =========================
   INTL TEL INPUT
========================= */
const phoneInput  = document.querySelector("#telephonePatient");
const phoneHidden = document.querySelector("#telephonePatientFull");

const iti = window.intlTelInput(phoneInput, {
    initialCountry: "sn",
    separateDialCode: true,
    preferredCountries: ["sn","ml","ci","gm","fr"],
    utilsScript:
      "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"
});

const urgencePhoneInput  = document.querySelector("#urgenceTelephoneInput");
const urgencePhoneHidden = document.querySelector("#urgenceTelephoneFull");

const itiUrgence = window.intlTelInput(urgencePhoneInput, {
    initialCountry: "sn",
    separateDialCode: true,
    preferredCountries: ["sn","fr","ml","gm","ci"],
    utilsScript:
      "https://cdn.jsdelivr.net/npm/intl-tel-input@18.2.1/build/js/utils.js"
});

/* =========================
   UTILITAIRES
========================= */
function updateSaveButton() {
    saveBtn.disabled = !selectedDateInput.value;
}

function enableModalScroll() {
    document.querySelector('#addRdvModal .modal-body').style.overflowY = 'auto';
}

function disableModalScroll() {
    document.querySelector('#addRdvModal .modal-body').style.overflowY = 'hidden';
}

/* =========================
   CHECK PATIENT INDEX
========================= */
patientInput.addEventListener('blur', () => {

    // NE RIEN FAIRE SI LE CHAMP EST DÉSACTIVÉ
    if (patientInput.disabled) return;
    const numero = patientInput.value.trim();
    if (!numero) {
        patientFeedback.innerHTML = '';
        return;
    }

    fetch('../Controller/check_patient.php?numero=' + numero)
        .then(r => r.json())
        .then(d => {

            //  PATIENT EXISTE
            if (d.status === 'exists') {
                // ✅ MÉMORISER LES DONNÉES DU PATIENT
                currentPatientName  = d.nom;
                currentPatientPhone = d.tel;
                patientFeedback.innerHTML = `
                    <div class="alert alert-success py-2">
                        <strong>${d.nom}</strong><br>
                        Téléphone : ${d.tel}
                    </div>
                `;

                // ✅ ACTIVER LE SCROLL DU MODAL
                enableModalScroll();

                // 🔓 Activer service
                serviceSearch.removeAttribute('disabled');
                serviceSearch.focus();

                // 🔽 Ouvrir la liste des services
                serviceDropdown.classList.remove('d-none');
                serviceDropdown.querySelectorAll('.service-item')
                    .forEach(item => item.style.display = 'block');

                return;
            }




            // PATIENT AVEC INDEX MAIS NON ENREGISTRÉ
            if (d.status === 'not_found') {
                patientFeedback.innerHTML = `
                    <div class="alert alert-warning py-2">
                        Patient non enregistré sur la plateforme.<br>
                        Veuillez compléter ses informations.
                    </div>
                `;

                //  ouvrir le flow nouveau patient
                openNewIndexPatient();
                return;
            }

            //  ERREUR
            patientFeedback.innerHTML = `
                <div class="alert alert-danger py-2">
                    Erreur lors de la vérification
                </div>
            `;
        });

});

function openNewIndexPatient() {

    // 1. état logique
    document.getElementById('isNewIndex').value = '1';
    document.querySelector('[data-value="index"]').click();


    // 2. état visuel des boutons (sans click)
    document.querySelectorAll('.patient-type-btn').forEach(b => {
        b.classList.remove('active');
    });

    const btnNewIndex = document.querySelector(
        '.patient-type-btn[data-value="new_index"]'
    );
    if (btnNewIndex) {
        btnNewIndex.classList.add('active');
    }

    // 3. affichage des sections (direct, sans animation cassante)
    indexFields.classList.remove('d-none');
    indexFields.classList.add('show');

    noIndexFields.classList.remove('d-none');
    noIndexFields.classList.add('show');

    // 4. numéro de dossier visible mais verrouillé
    patientInput.readOnly = true;
    patientInput.classList.add('bg-light');

    // 5. scroll activé
    enableModalScroll();
}





/* =========================
   SERVICE SEARCH
========================= */
serviceSearch.addEventListener('focus', () => {
    serviceDropdown.classList.remove('d-none');
});

serviceSearch.addEventListener('input', () => {
    const v = serviceSearch.value.toLowerCase();
    let found = 0;

    serviceDropdown.querySelectorAll('.service-item').forEach(item => {
        const ok = item.innerText.toLowerCase().includes(v);
        item.style.display = ok ? 'block' : 'none';
        if (ok) found++;
    });

    serviceDropdown.classList.toggle('d-none', found === 0);
});

serviceDropdown.addEventListener('mousedown', (e) => {

    const item = e.target.closest('.service-item');
    if (!item) return;

    e.stopPropagation();

    serviceSearch.value = item.querySelector('strong').innerText;
    hiddenService.value = item.dataset.code;

    serviceDropdown.classList.add('d-none');
    calendarWrapper.classList.remove('d-none');

    selectedDateInput.value = '';
    updateSaveButton();

    loadCalendar();
    enableModalScroll();

});
/* =========================
   CALENDRIER
========================= */
function loadCalendar(selectedDateToSelect = null) {

    if (!hiddenService.value) return;

    const year  = currentDate.getFullYear();
    const month = currentDate.getMonth() + 1;

    fetch(`../Controller/calendar_data.php?service=${hiddenService.value}&year=${year}&month=${month}`)
    .then(r => r.json())
    .then(data => {

        calendar.innerHTML = '';

        calendarTitle.innerText = currentDate.toLocaleDateString('fr-FR', {
            month: 'long',
            year: 'numeric'
        });

        const firstDay = new Date(year, month - 1, 1);
        const offset = (firstDay.getDay() + 6) % 7;

        // espaces avant le premier jour
        for (let i = 0; i < offset; i++) {
            calendar.appendChild(document.createElement('div'));
        }

        Object.entries(data).forEach(([date, info]) => {

            const day = document.createElement('div');
            day.className = `calendar-day ${info.status}`;
            day.textContent = date.split('-')[2];

            /* TOOLTIP */
            let tooltipText = '';

            if (info.status === 'ferie') {
                tooltipText = 'Jour férié';
            }
            else if (info.status === 'disabled') {
                tooltipText = 'Service indisponible';
            }
            else if (info.status === 'plein') {
                tooltipText = 'Complet';
            }
            else if (info.status === 'moyen') {
                tooltipText = `${info.count ?? 0} rendez-vous - disponibilité moyenne`;
            }
            
            else if (info.status === 'disponible') {
                tooltipText = `${info.count ?? 0} rendez-vous pris`;
            }
            if (tooltipText) {
                day.setAttribute('title', tooltipText);
                day.setAttribute('data-bs-toggle', 'tooltip');
            }

            /* CLICK JOUR */

            if (info.status === 'disponible' || info.status === 'moyen') {

                day.onclick = () => {

                    document
                        .querySelectorAll('.calendar-day.selected')
                        .forEach(d => d.classList.remove('selected'));

                    day.classList.add('selected');

                    selectedDateInput.value = date;

                    updateSaveButton();
                };
            }

            calendar.appendChild(day);

            if (selectedDateToSelect && date === selectedDateToSelect) {

                day.classList.add('selected');

                selectedDateInput.value = date;

                updateSaveButton();
            }

        });

        
        /* TOOLTIP BOOTSTRAP */

        document
            .querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach(el => new bootstrap.Tooltip(el));

    })
    .catch(err => {

        console.error('Erreur chargement calendrier:', err);

    });

}


document.getElementById('prevMonth').onclick = () => {
    currentDate.setMonth(currentDate.getMonth()-1);
    loadCalendar();
};
document.getElementById('nextMonth').onclick = () => {
    currentDate.setMonth(currentDate.getMonth()+1);
    loadCalendar();
};

function showSection(section) {
    section.classList.remove('d-none');
    requestAnimationFrame(() => {
        section.classList.add('show');
    });
}

function hideSection(section) {
    section.classList.remove('show');
    setTimeout(() => {
        section.classList.add('d-none');
    }, 250);
}


/* =========================
   SWITCH TYPE PATIENT (ONGLETS)
========================= */
document.querySelectorAll('.patient-type-btn').forEach(btn => {
    btn.addEventListener('click', () => {

        //  empêcher clic si déjà actif
        if (btn.classList.contains('active')) return;

        // 1️ type choisi
        const type = btn.dataset.value;

        //  mémoriser le dernier onglet utilisé
        localStorage.setItem('last_patient_type', type);

        // 2️ reset boutons
        document.querySelectorAll('.patient-type-btn').forEach(b => {
            b.classList.remove('active');
        });

        // 3️ activer bouton courant
        btn.classList.add('active');

        // 4️ valeur backend réelle
        patientTypeInput.value = (type === 'new_index') ? 'index' : type;

        // 5️ affichage champs
        if (type === 'index') {

            showSection(indexFields);
            hideSection(noIndexFields);

            patientInput.disabled = false;
            patientInput.readOnly = false;
            patientInput.classList.remove('bg-light');
            document.getElementById('isNewIndex').value = '0';

            //  PAS DE SCROLL
            disableModalScroll();

        } else if (type === 'noindex') {

            hideSection(indexFields);
            showSection(noIndexFields);

            patientInput.value = '';
            patientInput.disabled = true;
            document.getElementById('isNewIndex').value = '0';

            //  SCROLL ACTIVÉ
            enableModalScroll();

            //  âge actif
            initAgeCalculation();

        } else if (type === 'new_index') {

            showSection(indexFields);
            showSection(noIndexFields);

            patientInput.disabled = false;
            patientInput.readOnly = false;
            patientInput.focus();

            calendarWrapper.classList.add('d-none');
            document.getElementById('isNewIndex').value = '1';

            //  SCROLL ACTIVÉ
            enableModalScroll();

            //  âge actif
            initAgeCalculation();
        }


        // 6️ reset commun
        if (type !== 'new_index') {
            patientFeedback.innerHTML = '';
        }

        selectedDateInput.value = '';
        calendarWrapper.classList.add('d-none');
        updateSaveButton();
    });
});

/* =========================
   MESSAGE / CONFIRMATION
========================= */
function showMessage(title,msg,type='info'){
    modalTitle.innerText = title;
    modalBody.innerHTML = `<div class="alert alert-${type}">${msg}</div>`;
    btnOk.classList.remove('d-none');
    btnConfirm.classList.add('d-none');
    btnCancel.classList.add('d-none');
    actionModal.show();
}

function showConfirm(title,html,cb){
    modalTitle.innerText = title;
    modalBody.innerHTML = html;
    btnOk.classList.add('d-none');
    btnCancel.classList.remove('d-none');
    btnConfirm.classList.remove('d-none');

    const clone = btnConfirm.cloneNode(true);
    btnConfirm.replaceWith(clone);

    clone.onclick = () => {
        actionModal.hide();
        actionModalEl.addEventListener('hidden.bs.modal',function h(){
            actionModalEl.removeEventListener('hidden.bs.modal',h);
            cb();
        });
    };
    actionModal.show();
}

/* =========================
   ENREGISTRER RDV
========================= */
saveBtn.onclick = () => {
    if (isSubmitting) return;
    isSubmitting = true;

    let patientType = patientTypeInput.value;//  valeur réelle
    const form = document.querySelector('#addRdvModal form');

    if (!hiddenService.value || !selectedDateInput.value) {
        showMessage("Erreur","Service et date requis","warning");
        isSubmitting=false;
        return;
    }

    //  BLOQUER ABSOLUMENT LES CAS FAUX
    if (patientType === 'index') {
        const dossier = patientInput.value.trim();
        if (!dossier || dossier === '0') {
            showMessage("Erreur","Numéro de dossier obligatoire","danger");
            isSubmitting=false;
            return;
        }
    }

    if (patientType === 'noindex') {
        //  IMPORTANT : supprimer toute trace de dossier
        patientInput.value = '';
    }

    if (patientType === 'noindex' || document.getElementById('isNewIndex').value === '1') {

        // téléphone patient
        if (!iti.isValidNumber()) {
            showMessage("Erreur", "Numéro de téléphone du patient invalide", "danger");
            isSubmitting = false;
            return;
        }
        phoneHidden.value = iti.getNumber();

        // téléphone urgence (facultatif)
        if (urgencePhoneInput.value.trim() !== '') {
            if (!itiUrgence.isValidNumber()) {
                showMessage("Erreur", "Numéro du contact d’urgence invalide", "danger");
                isSubmitting = false;
                return;
            }
            urgencePhoneHidden.value = itiUrgence.getNumber();
        } else {
            urgencePhoneHidden.value = '';
        }
    }
    
    let recapHtml = '<ul class="list-group list-group-flush">';

    const type = patientTypeInput.value;
    const serviceLabel = serviceSearch.value;
    const dateLabel = new Date(selectedDateInput.value)
        .toLocaleDateString('fr-FR');

    if (type === 'index') {

        // 🔥 si nouveau patient index → lire les champs du formulaire
        if (document.getElementById('isNewIndex').value === '1') {

            const prenom = document.querySelector('[name="prenomComplet"]')?.value || '';
            const nom    = document.querySelector('[name="nom"]')?.value || '';
            const fullname = `${prenom} ${nom}`.trim() || '—';

            const tel = phoneHidden.value || '—';

            recapHtml += `
                <li class="list-group-item">
                    <strong>Patient :</strong> ${fullname}
                </li>
                <li class="list-group-item">
                    <strong>Dossier :</strong> ${patientInput.value}
                </li>
                <li class="list-group-item">
                    <strong>Téléphone :</strong> ${tel}
                </li>
            `;

        } else {

            // ✅ patient index existant
            recapHtml += `
                <li class="list-group-item">
                    <strong>Patient :</strong> ${currentPatientName || '—'}
                </li>
                <li class="list-group-item">
                    <strong>Dossier :</strong> ${patientInput.value}
                </li>
                <li class="list-group-item">
                    <strong>Téléphone :</strong> ${currentPatientPhone || '—'}
                </li>
            `;
        }
    }

    else {
        recapHtml += `
            <li class="list-group-item"><strong>Patient :</strong>
                ${document.querySelector('[name="prenomComplet"]').value}
                ${document.querySelector('[name="nom"]').value}
            </li>
            <li class="list-group-item"><strong>Dossier :</strong> Sans index</li>
        `;
    }

    if (type !== 'index') {
        recapHtml += `
            <li class="list-group-item">
                <strong>Téléphone :</strong>
                ${phoneHidden.value || '—'}
            </li>
        `;
    }

    // ✅ Service & Date TOUJOURS affichés
    recapHtml += `
        <li class="list-group-item"><strong>Service :</strong> ${serviceLabel}</li>
        <li class="list-group-item"><strong>Date :</strong> ${dateLabel}</li>
    </ul>`;

    showConfirm(
        "Confirmer le rendez-vous",
        recapHtml,
        () => {
            const formData = new FormData(form);

            fetch('../Controller/add_rdv.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(r => {

                if (r.status === 'success') {

                    const recap = document.getElementById('successRecap');
                    recap.innerHTML = recapHtml;

                    // fermer modal ajout
                    bootstrap.Modal.getInstance(
                        document.getElementById('addRdvModal')
                    ).hide();

                    // ouvrir modal succès
                    new bootstrap.Modal(
                        document.getElementById('successModal')
                    ).show();

                } else {
                    showMessage("Erreur", r.message, "danger");
                }
            })

            .finally(() => isSubmitting = false);
        }
    );

};


document.getElementById('successModal')
.addEventListener('hidden.bs.modal', () => {

    if (!skipReloadAfterSuccess) {
        window.location.href = '?page=rendezvous';
    }

    skipReloadAfterSuccess = false;
});


/* =========================
   NOUVEAU RDV APRÈS SUCCÈS avec reset FORMULAIRE RV
========================= */
document.getElementById('btnNewRdv').onclick = () => {

    // 🔥 RESET ÉTAT PATIENT COMPLET (TRÈS IMPORTANT)
    currentPatientName  = '';
    currentPatientPhone = '';

    document.getElementById('isNewIndex').value = '0';
    patientTypeInput.value = 'index';

    // remettre onglet index actif visuellement
    document.querySelectorAll('.patient-type-btn').forEach(b=>{
        b.classList.remove('active');
    });
    document.querySelector('[data-value="index"]').classList.add('active');

    // reset sections
    showSection(indexFields);
    hideSection(noIndexFields);

    patientInput.readOnly = false;
    patientInput.disabled = false;
    patientInput.classList.remove('bg-light');


    skipReloadAfterSuccess = true;

    bootstrap.Modal.getInstance(
        document.getElementById('successModal')
    ).hide();

    const form = document.querySelector('#addRdvModal form');
    form.reset();

    document.getElementById('btnSave').innerHTML =
    '<i class="bi bi-check-circle"></i> Enregistrer le RDV';

    document.getElementById('rdvModalTitle').innerHTML =
    '<i class="bi bi-calendar-plus"></i> Ajouter un rendez-vous';

    document.getElementById('idRv').value = '';
   
    // vider feedback vert
    patientFeedback.innerHTML = '';

    // vider recherche téléphone
    phoneSearchInput.value = '';

    // reset intl tel input
    iti.setNumber('');
    itiUrgence.setNumber('');
    phoneHidden.value = '';
    urgencePhoneHidden.value = '';

    // reset service + calendrier
    serviceSearch.value = '';
    hiddenService.value = '';
    calendarWrapper.classList.add('d-none');
    selectedDateInput.value = '';

    updateSaveButton();

    new bootstrap.Modal(
        document.getElementById('addRdvModal')
    ).show();
};





/* =========================
   CALCUL ÂGE
========================= */
const dateNaissance = document.getElementById('dateNaissance');
const ageInput      = document.getElementById('ageInput');

function initAgeCalculation() {
    const dateNaissance = document.getElementById('dateNaissance');
    const ageInput = document.getElementById('ageInput');

    if (!dateNaissance || !ageInput) return;

    dateNaissance.onchange = () => {
        if (!dateNaissance.value) {
            ageInput.value = '';
            return;
        }

        const birth = new Date(dateNaissance.value);
        if (isNaN(birth.getTime())) {
            ageInput.value = '';
            return;
        }

        const today = new Date();
        let age = today.getFullYear() - birth.getFullYear();
        const m = today.getMonth() - birth.getMonth();

        if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
            age--;
        }

        ageInput.value = age >= 0 ? age : '';
    };
}

// Initialiser le calcul de l’âge à l’ouverture de la modal
document.getElementById('addRdvModal')
    .addEventListener('shown.bs.modal', () => {
        initAgeCalculation();
    });


const filterService = document.getElementById('filterService');
const filterPeriod  = document.getElementById('filterPeriod');
const filterDate    = document.getElementById('filterDate');

function applyFilters() {
    const params = new URLSearchParams(window.location.search);

    params.set('page', 'rendezvous');
    params.delete('p');

    if (filterService.value) params.set('service', filterService.value);
    else params.delete('service');

    params.set('periode', filterPeriod.value);

    if (filterDate.value) params.set('date', filterDate.value);

    window.location.search = params.toString();
}


document.getElementById('applyFiltersBtn')
.addEventListener('click', applyFilters);

const filterServiceSearch   = document.getElementById('filterServiceSearch');
const filterServiceHidden   = document.getElementById('filterService');
const filterServiceDropdown = document.getElementById('filterServiceDropdown');

filterServiceSearch.addEventListener('focus', () => {
    filterServiceDropdown.classList.remove('d-none');
});

filterServiceSearch.addEventListener('input', () => {
    const v = filterServiceSearch.value.toLowerCase();
    let found = 0;

    filterServiceDropdown.querySelectorAll('.service-item').forEach(item => {
        const ok = item.innerText.toLowerCase().includes(v);
        item.style.display = ok ? 'block' : 'none';
        if (ok) found++;
    });

    filterServiceDropdown.classList.toggle('d-none', found === 0);
});

filterServiceDropdown.querySelectorAll('.service-item').forEach(item => {
    item.onclick = () => {
        filterServiceSearch.value = item.querySelector('strong').innerText;
        filterServiceHidden.value = item.dataset.code;
        filterServiceDropdown.classList.add('d-none');
    };
});

document.addEventListener('click', e => {

    if (!e.target.closest('#serviceSearch') &&
        !e.target.closest('#serviceDropdown')) {

        serviceDropdown.classList.add('d-none');

    }

});

patientInput.addEventListener('input', () => {
    patientInput.value = patientInput.value.replace(/\D+/g, '');
});


const phoneSearchInput = document.getElementById('phoneSearchInput');


phoneSearchInput.addEventListener('blur', () => {
    const phone = phoneSearchInput.value.trim();
    if (!phone) return;

    fetch('../Controller/check_patient_by_phone.php?phone=' + encodeURIComponent(phone))
        .then(r => r.json())
        .then(res => {

            patientFeedback.innerHTML = '';

            /* =========================
               CAS 1 : PATIENT AVEC INDEX
            ========================= */
            if (res.status === 'patient') {

                document.querySelector('[data-value="index"]').click();

                patientInput.value = res.data.numeroDossierPatient;
                patientInput.dispatchEvent(new Event('blur'));

                currentPatientName  = `${res.data.prenomPatient} ${res.data.nomPatient}`;
                currentPatientPhone = res.data.telephonePatient;

                patientFeedback.innerHTML = `
                    <div class="alert alert-success py-2">
                        <strong>${res.data.prenomPatient} ${res.data.nomPatient}</strong><br>
                        Téléphone : ${res.data.telephonePatient}
                    </div>
                `;

                return;
            }

            /* =========================
               CAS 2 : ANCIEN SANS INDEX → NOUVEAU PATIENT
            ========================= */
            if (res.status === 'noindex') {

                document.querySelector('[data-value="new_index"]').click();
                document.getElementById('isNewIndex').value = '1';

                document.querySelector('[name="prenomComplet"]').value = res.data.prenomPatient ?? '';
                document.querySelector('[name="nom"]').value = res.data.nomPatient ?? '';
                document.querySelector('[name="sexe"]').value = res.data.sexe ?? '';
                document.querySelector('[name="dateNaissance"]').value = res.data.dateNaissance ?? '';
                document.querySelector('[name="age"]').value = res.data.age ?? '';
                document.querySelector('[name="nationalite"]').value = res.data.nationalite ?? '';
                document.querySelector('[name="emailPatient"]').value = res.data.email ?? '';
                document.querySelector('[name="groupeSanguin"]').value = res.data.groupeSanguin ?? '';
                document.querySelector('[name="identiteOfficielle"]').value = res.data.identiteOfficielle ?? '';
                document.querySelector('[name="adresse"]').value = res.data.adresse ?? '';
                document.querySelector('[name="urgenceNom"]').value = res.data.urgenceNom ?? '';

                if (res.data.telephonePatient) {
                    iti.setNumber(res.data.telephonePatient);
                    phoneHidden.value = res.data.telephonePatient;
                }

                if (res.data.urgenceTelephone) {
                    itiUrgence.setNumber(res.data.urgenceTelephone);
                    urgencePhoneHidden.value = res.data.urgenceTelephone;
                }

                patientFeedback.innerHTML = `
                    <div class="alert alert-warning py-2">
                        Patient retrouvé sans index.<br>
                        Veuillez attribuer un numéro de dossier.
                    </div>
                `;
                return;
            }

            /* =========================
               CAS 3 : AUCUN PATIENT
            ========================= */
            if (res.status === 'not_found') {

                document.querySelector('[data-value="noindex"]').click();

                patientFeedback.innerHTML = `
                    <div class="alert alert-warning py-2">
                        Aucun patient trouvé avec ce numéro.
                    </div>
                `;
            }
        });
});

phoneSearchInput.addEventListener('input', () => {
    phoneSearchInput.value = phoneSearchInput.value.replace(/\D+/g, '');
});


function openNewIndexPatientWithData(data) {

    // passer sur onglet "Nouveau patient"
    document.querySelector('[data-value="new_index"]').click();

    // marquer nouveau index
    document.getElementById('isNewIndex').value = '1';

    // verrouiller index
    patientInput.readOnly = false;
    patientInput.focus();

    // pré-remplissage
    document.querySelector('[name="prenomComplet"]').value = data.prenomPatient ?? '';
    document.querySelector('[name="nom"]').value = data.nomPatient ?? '';
    document.querySelector('[name="sexe"]').value = data.sexe ?? '';
    document.querySelector('[name="age"]').value = data.age ?? '';
    document.querySelector('[name="nationalite"]').value = data.nationalite ?? '';
    document.querySelector('[name="emailPatient"]').value = data.email ?? '';
    document.querySelector('[name="groupeSanguin"]').value = data.groupeSanguin ?? '';
    document.querySelector('[name="identiteOfficielle"]').value = data.identiteOfficielle ?? '';
    document.querySelector('[name="adresse"]').value = data.adresse ?? '';
    document.querySelector('[name="urgenceNom"]').value = data.urgenceNom ?? '';

    // téléphone
    iti.setNumber(data.telephonePatient);
    if (data.urgenceTelephone) {
        itiUrgence.setNumber(data.urgenceTelephone);
    }

    enableModalScroll();

    patientFeedback.innerHTML = `
        <div class="alert alert-info py-2">
            Patient déjà connu sans index.<br>
            Veuillez lui attribuer un numéro de dossier.
        </div>
    `;
}

document.addEventListener('DOMContentLoaded', () => {

    const urlParams = new URLSearchParams(window.location.search);
    const idRv = urlParams.get('idRv');

    if (urlParams.get('edit') === '1') {

        const dossier = urlParams.get('dossier');
        const service = urlParams.get('service');
        const date    = urlParams.get('date');

        document.getElementById('idRv').value = idRv;

        const modalEl = document.getElementById('addRdvModal');
        const modal   = new bootstrap.Modal(modalEl);

        modal.show();

        modalEl.addEventListener('shown.bs.modal', function handler() {

            modalEl.removeEventListener('shown.bs.modal', handler);

            //  changer titre
            document.getElementById('rdvModalTitle').innerHTML =
                `<i class="bi bi-pencil-square"></i> Modifier le rendez-vous`;

            //  changer bouton save
            document.getElementById('btnSave').innerHTML =
                '<i class="bi bi-pencil-square"></i> Modifier le RDV';

            //  forcer mode index
            document.querySelector('[data-value="index"]').click();

            //  injecter dossier
            patientInput.value = dossier;
            patientInput.dispatchEvent(new Event('blur'));

            //  Sélection service sans click
            const serviceItem = document.querySelector(
                '#serviceDropdown .service-item[data-code="' + service + '"]'
            );

            if (serviceItem) {
                serviceSearch.value = serviceItem.querySelector('strong').innerText;
                hiddenService.value = service;
                serviceDropdown.classList.add('d-none');
                calendarWrapper.classList.remove('d-none');
            }

            // 🔹 Charger calendrier AVEC la date
            currentDate = new Date(date);
            loadCalendar(date);
        });
     }
});
</script>

