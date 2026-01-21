<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../Modele/database.php';
$db = getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
$sql = "
SELECT *
FROM (
    SELECT
        r.numeroDossierPatient AS dossier,
        CONCAT(p.prenomPatient, ' ', p.nomPatient) AS patient,
        p.telephonePatient AS telephone,
        s.designService AS service,
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
        n.dateDemande,
        n.dateDisponible
    FROM patientnoindex n
    JOIN service s ON s.codeService = n.codeService
) t
WHERE 1=1
";

$params = [];

if ($service) {
    $sql .= " AND t.service = ?";
    $params[] = $service;
}

if ($periode === 'jour') {
    $sql .= " AND t.dateRvServ = ?";
    $params[] = $date;
} elseif ($periode === 'mois') {
    $sql .= " AND MONTH(t.dateRvServ)=MONTH(?) AND YEAR(t.dateRvServ)=YEAR(?)";
    $params[] = $date;
    $params[] = $date;
} elseif ($periode === 'annee') {
    $sql .= " AND YEAR(t.dateRvServ)=YEAR(?)";
    $params[] = $date;
}

$sql .= " ORDER BY t.dateRvServ DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php
// fin de ton traitement PHP
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <p class="text-muted mb-0">--</p>
</div>

<!-- =========================
     TABLEAU
========================= -->

<div class="chart-card">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="text-muted mb-0">Liste des rendez-vous</h6>

        <?php if ($_SESSION['role'] === 'admin'): ?>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRdvModal">
                <i class="bi bi-plus-circle"></i> Ajouter un RDV
            </button>
        <?php endif; ?>
    </div>
    
    
    <div class="table-responsive">

        <div class="row g-3 mb-3">
            <!-- FILTRE SERVICE -->
            <div class="col-md-4">
                <label class="form-label">Service</label>
                <select class="form-select" id="filterService">
                    <option value="">Tous les services</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['codeService'] ?>">
                            <?= htmlspecialchars($s['designService']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- FILTRE PÉRIODE -->
            <div class="col-md-4">
                <label class="form-label">Période</label>
                <select class="form-select" id="filterPeriod">
                    <option value="jour">Jour</option>
                    <option value="mois">Mois</option>
                    <option value="annee">Année</option>
                </select>
            </div>

            <!-- FILTRE DATE -->
            <div class="col-md-4">
                <label class="form-label">Date</label>
                <input type="date" class="form-control" id="filterDate"
                    value="<?= date('Y-m-d') ?>">
            </div>

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
                <tr>
                    <td><?= htmlspecialchars($rv['dossier']) ?></td>
                    <td><?= htmlspecialchars($rv['patient']) ?></td>
                    <td><?= htmlspecialchars($rv['telephone']) ?></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($rv['service']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($rv['dateDemande'])) ?></td>
                    <td><?= date('d/m/Y', strtotime($rv['dateRvServ'])) ?></td>

                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
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


<div class="modal-header">
    <h5 class="modal-title"><i class="bi bi-calendar-plus"></i> Ajouter un rendez-vous</h5>
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
    </div>

    <!-- Champ caché envoyé au backend -->
    <input type="hidden" name="patient_type" id="patientType" value="index">
</div>


<div class="row g-3">

    <div id="indexFields">
    <!-- numéro de dossier + feedback -->
        <!-- Numéro de dossier -->
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
    </div>

     <!-- champs pour patient sans index -->
    <!-- PATIENT SANS INDEX -->
    <div id="noIndexFields" class="d-none">

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
    const numero = patientInput.value.trim();
    if (!numero) {
        patientFeedback.innerHTML = '';
        return;
    }

    fetch('../Controller/check_patient.php?numero=' + numero)
        .then(r => r.json())
        .then(d => {
            patientFeedback.innerHTML =
                d.status === 'ok'
                ? `<div class="alert alert-success py-2">
                     <strong>${d.nom}</strong><br>
                     Téléphone : ${d.tel}
                   </div>`
                : `<div class="alert alert-danger py-2">
                     Numéro de dossier invalide
                   </div>`;
        });
});

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

serviceDropdown.querySelectorAll('.service-item').forEach(item => {
    item.onclick = () => {
        serviceSearch.value = item.querySelector('strong').innerText;
        hiddenService.value = item.dataset.code;

        serviceDropdown.classList.add('d-none');
        calendarWrapper.classList.remove('d-none');

        selectedDateInput.value = '';
        updateSaveButton();
        loadCalendar();
        enableModalScroll();
    };
});

document.addEventListener('click', e => {
    if (!e.target.closest('.position-relative')) {
        serviceDropdown.classList.add('d-none');
    }
});

/* =========================
   CALENDRIER
========================= */
function loadCalendar() {
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

            // Décalage du premier jour (lundi = 0)
            const firstDay = new Date(year, month - 1, 1);
            const offset = (firstDay.getDay() + 6) % 7;

            for (let i = 0; i < offset; i++) {
                calendar.appendChild(document.createElement('div'));
            }

            Object.entries(data).forEach(([date, info]) => {
                const day = document.createElement('div');
                day.className = `calendar-day ${info.status}`;
                day.textContent = date.split('-')[2];

                // ✅ Tooltip : nombre de RDV pris
                if (typeof info.count !== 'undefined') {
                    day.setAttribute(
                        'title',
                        `${info.count} rendez-vous déjà pris`
                    );
                    day.setAttribute('data-bs-toggle', 'tooltip');
                }

                // ✅ Sélection possible uniquement si dispo
                if (info.status === 'disponible' || info.status === 'moyen') {
                    day.addEventListener('click', () => {
                        document
                            .querySelectorAll('.calendar-day.selected')
                            .forEach(d => d.classList.remove('selected'));

                        day.classList.add('selected');
                        selectedDateInput.value = date;
                        updateSaveButton();
                    });
                }

                calendar.appendChild(day);
            });

            // 🔥 Activer les tooltips Bootstrap
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

/* =========================
   SWITCH PATIENT TYPE
========================= */
document.querySelectorAll('.patient-type-btn').forEach(btn => {
    btn.onclick = () => {

        // reset boutons
        document.querySelectorAll('.patient-type-btn').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
        });

        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-primary');

        const type = btn.dataset.value;
        patientTypeInput.value = type; // 🔴 CRUCIAL

        if (type === 'index') {
            indexFields.classList.remove('d-none');
            noIndexFields.classList.add('d-none');
            patientInput.disabled = false;
        } else {
            indexFields.classList.add('d-none');
            noIndexFields.classList.remove('d-none');

            // 🔴 neutraliser totalement le champ index
            patientInput.value = '';
            patientInput.disabled = true;
        }

        patientFeedback.innerHTML = '';
        selectedDateInput.value = '';
        calendarWrapper.classList.add('d-none');
        updateSaveButton();
        disableModalScroll();
    };
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

    let patientType = patientTypeInput.value;// 🔥 valeur réelle
    const form = document.querySelector('#addRdvModal form');

    if (!hiddenService.value || !selectedDateInput.value) {
        showMessage("Erreur","Service et date requis","warning");
        isSubmitting=false;
        return;
    }

    // 🔒 BLOQUER ABSOLUMENT LES CAS FAUX
    if (patientType === 'index') {
        const dossier = patientInput.value.trim();
        if (!dossier || dossier === '0') {
            showMessage("Erreur","Numéro de dossier obligatoire","danger");
            isSubmitting=false;
            return;
        }
    }

    if (patientType === 'noindex') {
        // 🔥 IMPORTANT : supprimer toute trace de dossier
        patientInput.value = '';
    }

    if (patientType === 'noindex') {
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
        recapHtml += `
            <li class="list-group-item"><strong>Patient :</strong> ${patientFeedback.innerText || '—'}</li>
            <li class="list-group-item"><strong>Dossier :</strong> ${patientInput.value}</li>
        `;
    } else {
        recapHtml += `
            <li class="list-group-item"><strong>Patient :</strong>
                ${document.querySelector('[name="prenomComplet"]').value}
                ${document.querySelector('[name="nom"]').value}
            </li>
            <li class="list-group-item"><strong>Dossier :</strong> Sans index</li>
        `;
    }

    recapHtml += `
        <li class="list-group-item"><strong>Téléphone :</strong>
            ${document.querySelector('[name="telephonePatient"]').value || '—'}
        </li>
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
                    location.reload();
                } else {
                    showMessage("Erreur", r.message, "danger");
                }
            })
            .finally(() => isSubmitting = false);
        }
    );

};


/* =========================
   CALCUL ÂGE
========================= */
const dateNaissance = document.getElementById('dateNaissance');
const ageInput      = document.getElementById('ageInput');

if (dateNaissance && ageInput) {
    dateNaissance.addEventListener('change', () => {
        const birth = new Date(dateNaissance.value);
        if (isNaN(birth)) {
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
    });
}


</script>