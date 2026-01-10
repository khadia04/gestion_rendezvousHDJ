<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once '../Modele/database.php';
$db = getConnection();


/* SERVICES */
$services = $db->query("
    SELECT codeService, designService
    FROM service
    ORDER BY designService
")->fetchAll(PDO::FETCH_ASSOC);

if (!is_array($services)) {
    $services = [];
}

/* =========================
   FILTRES
========================= */
$service = $_GET['service'] ?? '';
$periode = $_GET['periode'] ?? 'jour';
$date    = $_GET['date'] ?? date('Y-m-d');

/* =========================
   REQUÊTE RDV
========================= */
$sql = "
SELECT
    r.numeroDossierPatient,
    p.prenomPatient,
    p.nomPatient,
    p.telephonePatient,
    s.designService,
    r.dateDemande,
    r.dateRvServ
FROM rendezvs r
JOIN patient p ON p.numeroDossierPatient = r.numeroDossierPatient
JOIN service s ON s.codeService = r.codeService
WHERE 1=1
";

$params = [];

if ($service) {
    $sql .= " AND r.codeService = ?";
    $params[] = $service;
}

if ($periode === 'jour') {
    $sql .= " AND r.dateRvServ = ?";
    $params[] = $date;
} elseif ($periode === 'mois') {
    $sql .= " AND MONTH(r.dateRvServ)=MONTH(?) AND YEAR(r.dateRvServ)=YEAR(?)";
    $params[] = $date;
    $params[] = $date;
} elseif ($periode === 'annee') {
    $sql .= " AND YEAR(r.dateRvServ)=YEAR(?)";
    $params[] = $date;
}

$sql .= " ORDER BY r.dateRvServ DESC";

/* =========================
   EXÉCUTION
========================= */
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rendezvous = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!is_array($rendezvous)) {
    $rendezvous = [];
}


?>

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
                    <td><?= $rv['numeroDossierPatient'] ?></td>
                    <td><?= htmlspecialchars($rv['prenomPatient'].' '.$rv['nomPatient']) ?></td>
                    <td><?= htmlspecialchars($rv['telephonePatient']) ?></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($rv['designService']) ?></span></td>
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

        <!-- prêt pour plus tard -->
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

    <!-- Numéro de dossier -->
    <div class="col-md-6">
        <label class="form-label">Numéro de dossier</label>
        <input
            type="number"
            name="numeroDossierPatient"
            id="patientIndexInput"
            class="form-control"
            required
        >
        <div id="patientFeedback" class="mt-2"></div>
    </div>

    <!-- Service -->
    <div class="col-md-6 position-relative">
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
const actionModal = new bootstrap.Modal(document.getElementById('actionModal'));
const modalTitle  = document.getElementById('actionModalTitle');
const modalBody   = document.getElementById('actionModalBody');
const btnConfirm  = document.getElementById('modalConfirm');
const btnCancel   = document.getElementById('modalCancel');
const btnOk       = document.getElementById('modalOk');
const filterService = document.getElementById('filterService');
const filterPeriod  = document.getElementById('filterPeriod');
const filterDate    = document.getElementById('filterDate');


let currentDate = new Date();
let direction   = 'right';

/* =========================
   PATIENT – CHECK INDEX
========================= */
patientInput.addEventListener('blur', () => {
    const numero = patientInput.value.trim();
    if (!numero) {
        patientFeedback.innerHTML = '';
        return;
    }

    fetch('../Controller/check_patient.php?numero=' + numero)
        .then(res => res.json())
        .then(data => {
            patientFeedback.innerHTML = data.status === 'ok'
                ? `<div class="alert alert-success py-2">
                        <strong>${data.nom}</strong><br>
                        Téléphone : ${data.tel}
                   </div>`
                : `<div class="alert alert-danger py-2">
                        Numéro de dossier invalide
                   </div>`;
        });
});

/* =========================
   SERVICE – RECHERCHE
========================= */
serviceSearch.addEventListener('focus', () => {
    serviceDropdown.classList.remove('d-none');
});

serviceSearch.addEventListener('input', () => {
    const value = serviceSearch.value.toLowerCase();
    let visible = 0;

    serviceDropdown.querySelectorAll('.service-item').forEach(item => {
        const match = item.innerText.toLowerCase().includes(value);
        item.style.display = match ? 'block' : 'none';
        if (match) visible++;
    });

    serviceDropdown.classList.toggle('d-none', visible === 0);
});

serviceDropdown.querySelectorAll('.service-item').forEach(item => {
    item.addEventListener('click', () => {
        serviceSearch.value = item.querySelector('strong').innerText;
        hiddenService.value = item.dataset.code;

        serviceDropdown.classList.add('d-none');
        calendarWrapper.classList.remove('d-none');

        selectedDateInput.value = '';
        updateSaveButton();
        loadCalendar();
    });
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

    fetch(`../controller/calendar_data.php?service=${hiddenService.value}&year=${year}&month=${month}`)
        .then(res => res.json())
        .then(data => {
            calendar.innerHTML = '';

            calendarTitle.innerText = currentDate.toLocaleDateString('fr-FR', {
                month: 'long',
                year: 'numeric'
            });

            const firstDay = new Date(year, month - 1, 1).getDay();
            const offset   = firstDay === 0 ? 6 : firstDay - 1;

            for (let i = 0; i < offset; i++) {
                calendar.appendChild(document.createElement('div'));
            }

            Object.entries(data).forEach(([date, info]) => {
                const day = document.createElement('div');
                day.className = `calendar-day ${info.status}`;
                day.textContent = date.split('-')[2];

                let tooltip = '';
                if (info.status === 'disponible') tooltip = `Disponible (${info.count ?? 0} RDV)`;
                if (info.status === 'moyen') tooltip = `Disponibilité moyenne (${info.count ?? 0} RDV)`;
                if (info.status === 'plein') tooltip = 'Complet – plus de rendez-vous disponibles';
                if (info.status === 'disabled') tooltip = 'Service indisponible';
                if (info.status === 'ferie') tooltip = `Jour férié${info.label ? ' : ' + info.label : ''}`;

                day.title = tooltip;

                if (info.status === 'disponible' || info.status === 'moyen') {
                    day.addEventListener('click', () => {
                        document.querySelectorAll('.calendar-day.selected')
                            .forEach(d => d.classList.remove('selected'));

                        day.classList.add('selected');
                        selectedDateInput.value = date;
                        updateSaveButton();
                    });
                }

                calendar.appendChild(day);
            });
        })
        .catch(() => alert('Erreur chargement calendrier'));
}

/* =========================
   NAVIGATION MOIS
========================= */
function animateCalendar() {
    calendar.classList.add(direction === 'right' ? 'slide-left' : 'slide-right');

    setTimeout(() => {
        loadCalendar();
        calendar.classList.remove('slide-left', 'slide-right');
    }, 200);
}

document.getElementById('prevMonth').onclick = () => {
    direction = 'left';
    currentDate.setMonth(currentDate.getMonth() - 1);
    animateCalendar();
};

document.getElementById('nextMonth').onclick = () => {
    direction = 'right';
    currentDate.setMonth(currentDate.getMonth() + 1);
    animateCalendar();
};

/* =========================
   BOUTON ENREGISTRER
========================= */
function updateSaveButton() {
    saveBtn.disabled = !selectedDateInput.value;
}
updateSaveButton();

/* =========================
   RAFRAÎCHIR LE TABLEAU SANS RELOAD
========================= */
function addRdvToTable(data) {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>${data.dossier}</td>
        <td>${data.patient}</td>
        <td>${data.telephone}</td>
        <td><span class="badge bg-info">${data.service}</span></td>
        <td>${new Date().toLocaleDateString('fr-FR')}</td>
        <td>${data.date_rdv}</td>
    `;

    tbody.prepend(tr); // ajoute le RDV en haut du tableau
}


/* =========================
   AFFICHER RÉCAPITULATIF   
========================= */
function showRecap(data) {
    const recap = document.getElementById('rdvRecap');
    const list  = document.getElementById('recapContent');

    list.innerHTML = `
        <li class="list-group-item"><strong>Patient :</strong> ${data.patient}</li>
        <li class="list-group-item"><strong>Dossier :</strong> ${data.dossier}</li>
        <li class="list-group-item"><strong>Téléphone :</strong> ${data.telephone}</li>
        <li class="list-group-item"><strong>Service :</strong> ${data.service}</li>
        <li class="list-group-item"><strong>Date :</strong> ${data.date_rdv}</li>
    `;

    recap.classList.remove('d-none');
}

/* =========================
   CONFIRMATION + ENVOI RDV
========================= */
let isSubmitting = false;

saveBtn.addEventListener('click', () => {

    if (isSubmitting) return;
    isSubmitting = true;
    saveBtn.disabled = true;

    const dossier   = patientInput.value;
    const service   = serviceSearch.value;
    const date      = selectedDateInput.value;
    const patient   = patientFeedback.querySelector('strong')?.innerText || '';
    const telephone = patientFeedback.innerText.match(/Téléphone\s*:\s*(\d+)/)?.[1] || '';

    if (!hiddenService.value || !dossier || !date) {
        showMessageModal(
            'Informations manquantes',
            'Veuillez renseigner le patient, le service et la date.',
            'warning'
        );
        isSubmitting = false;
        saveBtn.disabled = false;
        return;
    }

    showConfirmModal(
        'Confirmer le rendez-vous',
        `
        <ul class="list-group">
            <li class="list-group-item"><strong>Patient :</strong> ${patient}</li>
            <li class="list-group-item"><strong>Dossier :</strong> ${dossier}</li>
            <li class="list-group-item"><strong>Téléphone :</strong> ${telephone}</li>
            <li class="list-group-item"><strong>Service :</strong> ${service}</li>
            <li class="list-group-item"><strong>Date :</strong> ${new Date(date).toLocaleDateString('fr-FR')}</li>
        </ul>
        `,
        () => {
            const form = document.querySelector('#addRdvModal form');
            const formData = new FormData(form);

            fetch('../Controller/add_rdv.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {

                    // 🔥 AJOUT ICI
                    addRdvToTable(res.data);

                    //coherent avec les filtres  
                    loadRendezVous();

                    // Modal succès
                    showSuccessModal(res.data);
                    
                } else {
                    showMessageModal(
                        'Impossible d’enregistrer',
                        res.message,
                        'danger'
                    );
                }
    })

            .catch(() => {
                showMessageModal(
                    'Erreur serveur',
                    'Une erreur est survenue côté serveur.',
                    'danger'
                );
            })
            .finally(() => {
                isSubmitting = false;
                saveBtn.disabled = false;
            });
        }
    );
});








function showMessageModal(title, message, type = 'info') {
    modalTitle.innerText = title;
    modalBody.innerHTML = `<div class="alert alert-${type} mb-0">${message}</div>`;

    btnConfirm.classList.add('d-none');
    btnCancel.classList.add('d-none');
    btnOk.classList.remove('d-none');

    actionModal.show();
}

function showConfirmModal(title, message, onConfirm) {
    modalTitle.innerText = title;
    modalBody.innerHTML = message;

    btnOk.classList.add('d-none');
    btnConfirm.classList.remove('d-none');
    btnCancel.classList.remove('d-none');

    btnConfirm.onclick = () => {
        actionModal.hide();
        onConfirm();
    };

    actionModal.show();
}


const successModal = new bootstrap.Modal(
    document.getElementById('successModal')
);

const successRecap = document.getElementById('successRecap');
const btnNewRdv    = document.getElementById('btnNewRdv');

function showSuccessModal(data) {
    successRecap.innerHTML = `
        <li class="list-group-item"><strong>Patient :</strong> ${data.patient}</li>
        <li class="list-group-item"><strong>Dossier :</strong> ${data.dossier}</li>
        <li class="list-group-item"><strong>Téléphone :</strong> ${data.telephone}</li>
        <li class="list-group-item"><strong>Service :</strong> ${data.service}</li>
        <li class="list-group-item"><strong>Date :</strong> ${data.date_rdv}</li>
    `;

    successModal.show();
}


btnNewRdv.addEventListener('click', () => {
    successModal.hide();

    // Reset formulaire
    const form = document.querySelector('#addRdvModal form');
    form.reset();

    patientFeedback.innerHTML = '';
    calendarWrapper.classList.add('d-none');
    selectedDateInput.value = '';
    updateSaveButton();

    // Réouvrir le modal d'ajout
    const addModal = new bootstrap.Modal(
        document.getElementById('addRdvModal')
    );
    addModal.show();
});

function loadRendezVous() {

    const params = new URLSearchParams({
        service: filterService.value,
        periode: filterPeriod.value,
        date: filterDate.value
    });

    fetch('../Controller/filter_rendezvous.php?' + params.toString())
        .then(res => res.json())
        .then(data => {
            const tbody = document.querySelector('table tbody');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Aucun rendez-vous trouvé
                        </td>
                    </tr>`;
                return;
            }

            data.forEach(rv => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${rv.numeroDossierPatient}</td>
                    <td>${rv.patient}</td>
                    <td>${rv.telephonePatient}</td>
                    <td><span class="badge bg-info">${rv.designService}</span></td>
                    <td>${new Date(rv.dateDemande).toLocaleDateString('fr-FR')}</td>
                    <td>${new Date(rv.dateRvServ).toLocaleDateString('fr-FR')}</td>
                `;
                tbody.appendChild(tr);
            });
        });
}

filterService.addEventListener('change', loadRendezVous);
filterPeriod.addEventListener('change', loadRendezVous);
filterDate.addEventListener('change', loadRendezVous);




</script>






