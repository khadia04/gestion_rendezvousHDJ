<?php
require_once '../modele/database.php';
require_once __DIR__ . '/../helpers/activity.php';

/* =========================================================
   SÉCURITÉ
========================================================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php?session=expired");
    exit;
}

$db     = getConnection();
$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'];

/* =========================================================
   UPDATE PROFIL
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    if (
        empty($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== $_SESSION['csrf_token']
    ) {
        $_SESSION['error'] = "Session invalide.";
        header("Location: admin.php?page=profile#info");
        exit;
    }

    $prenom    = trim($_POST['prenom']);
    $nom       = trim($_POST['nom']);
    $telephone = trim($_POST['telephone']);

    if ($prenom === '' || $nom === '') {
        $_SESSION['error'] = "Le prénom et le nom sont obligatoires.";
        header("Location: admin.php?page=profile#info");
        exit;
    }

    $photoSql   = '';
    $photoParam = [];

    if (!empty($_FILES['photo']['name'])) {

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png'])) {
            $_SESSION['error'] = "Format image invalide.";
            header("Location: admin.php?page=profile#info");
            exit;
        }

        if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = "Image trop lourde (2 Mo max).";
            header("Location: admin.php?page=profile#info");
            exit;
        }

        $photoName = "admin_$userId.$ext";
        move_uploaded_file($_FILES['photo']['tmp_name'], "../assets/img/$photoName");

        $photoSql = ", photo = :photo";
        $photoParam['photo'] = $photoName;
    }

    $sql = "
        UPDATE agent
        SET prenom_agent = :prenom,
            nom_agent = :nom,
            telephone_agent = :tel
            $photoSql
        WHERE id = :id
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge([
        'prenom' => $prenom,
        'nom'    => $nom,
        'tel'    => $telephone,
        'id'     => $userId
    ], $photoParam));

    logActivity(
        $userId,
        'Mise à jour du profil',
        'Modification des informations personnelles',
        $role
    );

    $_SESSION['success'] = "Profil mis à jour avec succès.";
    header("Location: admin.php?page=profile#info");
    exit;
}

/* =========================================================
   PAGINATION ACTIVITÉS
========================================================= */
$perPage = 10;
$pageAct = isset($_GET['page_act']) && is_numeric($_GET['page_act']) ? (int) $_GET['page_act'] : 1;
$pageAct = max($pageAct, 1);
$offset  = ($pageAct - 1) * $perPage;

/* =========================================================
   REQUÊTE ACTIVITÉS
========================================================= */
$sql = "
SELECT
    al.action,
    al.description,
    al.ip_address,
    al.session_duration,
    al.created_at,
    al.role,
    a.prenom_agent,
    a.nom_agent
FROM activity_logs al
INNER JOIN agent a ON a.id = al.user_id
WHERE 1=1
";

$params = [];

if ($role === 'admin') {
    $sql .= " AND (al.user_id = :uid OR al.role = 'agent')";
    $params['uid'] = $userId;
} else {
    $sql .= " AND al.user_id = :uid";
    $params['uid'] = $userId;
}

if (!empty($_GET['q'])) {
    $sql .= " AND (
        a.nom_agent LIKE :q
        OR a.prenom_agent LIKE :q
        OR al.action LIKE :q
    )";
    $params['q'] = '%' . trim($_GET['q']) . '%';
}

if (!empty($_GET['date_from'])) {
    $sql .= " AND al.created_at >= :date_from";
    $params['date_from'] = $_GET['date_from'] . ' 00:00:00';
}

if (!empty($_GET['date_to'])) {
    $sql .= " AND al.created_at <= :date_to";
    $params['date_to'] = $_GET['date_to'] . ' 23:59:59';
}

/* =========================================================
   COUNT (pagination)
========================================================= */
$countSql = "
SELECT COUNT(*)
FROM activity_logs al
INNER JOIN agent a ON a.id = al.user_id
WHERE 1=1
" . substr($sql, strpos($sql, 'AND'));

$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalActivities = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($totalActivities / $perPage);

/* =========================================================
   FETCH PAGINÉ
========================================================= */
$sql .= " ORDER BY al.created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(":$k", $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="profile-page">

    <!-- TITRE -->
    <h4 class="mb-4">Mon profil</h4>

    <!-- ONGLET NAV -->
    <ul class="nav nav-tabs mb-5" id="profileTabs" role="tablist">

        <li class="nav-item" role="presentation">
            <button class="nav-link active"
                    id="info-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#info"
                    type="button"
                    role="tab"
                    style="font-size: 17px;">
                <i class="bi bi-person"></i> Informations
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link"
                    id="security-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#security"
                    type="button"
                    role="tab"
                    style="font-size: 17px;">
                <i class="bi bi-shield-lock"></i> Sécurité
            </button>
        </li>

        <li class="nav-item" role="presentation">
            <button class="nav-link"
                    id="activity-tab"
                    data-bs-toggle="tab"
                    data-bs-target="#activity"
                    type="button"
                    role="tab"
                    style="font-size: 17px;">
                <i class="bi bi-clock-history"></i> Activité
            </button>
        </li>

    </ul>

    <!-- CONTENU ONGLET -->
    <div class="tab-content">

        <!-- =====================
             INFORMATIONS PERSONNELLES
        ====================== -->
        <div class="tab-pane fade show active" id="info" role="tabpanel" >

            <div class="profile-page">

                <h4 class="mb-4">Informations personnelles</h4>

                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>


                <form method="POST" action="admin.php?page=profile#info" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="update_profile" value="1">

                    <!-- Photo de profil -->
                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Photo de profil</label>
                        <div class="d-flex align-items-center gap-3">
                            <div class="profile-avatar-wrapper">
                                <img
                                    src="<?= $avatar ?>"
                                    id="profilePreview"
                                    class="profile-avatar"
                                    alt="Photo de profil"
                                    title="Cliquez sur “Choisir un fichier” pour changer votre photo"
                                >
                            </div>

                            <input
                                type="file"
                                name="photo"
                                class="form-control"
                                accept="image/*"
                                onchange="previewProfilePhoto(this)"
                            >
                        </div>

                        <small class="text-muted" style="font-size: 11px;">Formats autorisés : JPG, PNG – max 2Mo</small>

                    </div>

                    <!-- Prénom -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Prénom</label>
                        <input
                            type="text"
                            name="prenom"
                            id="prenomInput"
                            class="form-control"
                            value="<?= htmlspecialchars($prenom) ?>"
                            required
                        >
                    </div>

                    <!-- Nom -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nom</label>
                        <input
                            type="text"
                            name="nom"
                            id="nomInput"
                            class="form-control"
                            value="<?= htmlspecialchars($nom) ?>"
                            required
                        >
                    </div>

                    <!-- Email (non modifiable) -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Adresse email
                        </label>
                        <input
                            type="email"
                            class="form-control"
                            value="<?= htmlspecialchars($email) ?>"
                            disabled
                        >
                    </div>

                    <!-- Téléphone -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Téléphone</label>
                        <input
                            type="tel"
                            name="telephone"
                            class="form-control"
                            value="<?= htmlspecialchars($tel ?? '') ?>"
                        >
                    </div>

                    <!-- Bouton -->
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> Enregistrer les modifications
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <!-- =====================
            SÉCURITÉ
        ====================== -->
        <div class="tab-pane fade show" id="security" role="tabpanel">

            <h4 class="mb-4">Sécurité du compte</h4>

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="../controller/updatePassword.php#security">

                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <!-- Mot de passe actuel -->
                <div class="col-md-6">
                    <label class="form-label">Mot de passe actuel</label>
                    <input
                        type="password"
                        id="currentPassword"
                        name="current_password"
                        class="form-control"
                        required
                    >
                    <small id="currentPasswordMsg" class="d-none"></small>

                </div>

                <!-- Nouveau mot de passe -->
                <div class="col-md-6">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        required
                    >

                </div>

                <!-- Confirmation -->
                <div class="col-md-6">
                    <label class="form-label">Confirmer le mot de passe</label>
                    <input
                        type="password"
                        id="confirm"
                        name="confirm"
                        class="form-control"
                        required
                    >
                    <small id="confirmPasswordMsg" class="d-none"></small>

                </div>

                <!-- FORCE MOT DE PASSE -->
                <div class="col-md-6" id="strengthWrapper" style="display:none;">
                    <label class="form-label">Force du mot de passe</label>
                    <div class="progress mb-1">
                        <div id="strengthBar" class="progress-bar" style="width:0%"></div>
                    </div>
                    <small id="strengthText"></small>
                </div>

                <!-- BOUTON -->
                <div class="col-12 mt-3">
                    <button
                        type="submit"
                        id="submitBtn"
                        class="btn btn-warning px-4"
                        disabled
                    >
                        <i class="bi bi-shield-lock"></i>
                        Modifier le mot de passe
                    </button>
                </div>

            </form>
        </div>

        <!-- =====================
            ACTIVITÉ
        ====================== -->
        <div class="tab-pane fade" id="activity" role="tabpanel">

            <h4 class="mb-4">Historique des activités</h4>


            <form method="get" action="admin.php#activity" class="row g-3 mb-4">

                <input type="hidden" name="page" value="profile">

                <!-- Recherche par nom -->
                <div class="col-md-3">
                    <input type="text" name="q" class="form-control"
                        placeholder="Nom, prénom ou actions"
                        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                </div>

                <!-- Date début -->
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control"
                        value="<?= $_GET['date_from'] ?? '' ?>">
                </div>

                <!-- Date fin -->
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control"
                        value="<?= $_GET['date_to'] ?? '' ?>">
                </div>

                <!-- Boutons -->
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-primary w-100">Filtrer</button>
                    <a href="admin.php?page=profile#activity" class="btn btn-outline-secondary w-100">
                        Réinitialiser
                    </a>
                    <a
                        href="../exports/activities_pdf.php?
                            q=<?= urlencode($_GET['q'] ?? '') ?>
                            &date_from=<?= urlencode($_GET['date_from'] ?? '') ?>
                            &date_to=<?= urlencode($_GET['date_to'] ?? '') ?>"
                        class="btn btn-outline-danger"
                        target="_blank"
                    >
                        Export PDF
                    </a>


                </div>
            </form>


            <?php if (empty($activities)): ?>
                <p class="text-muted">Aucune activité enregistrée.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($activities as $act): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <strong><?= htmlspecialchars($act['action']) ?></strong>
                                <span >– <?= htmlspecialchars($act['prenom_agent'] . ' ' . $act['nom_agent']) ?></span>

                                <?php if ($act['role'] === 'admin'): ?>
                                    <span class="badge bg-primary ms-2">Admin</span>
                                <?php endif; ?>

                                <?php if ($act['role'] === 'agent'): ?>
                                    <span class="badge bg-secondary ms-2">Agent</span>
                                <?php endif; ?>

                                <?php if ($act['description']): ?>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars($act['description']) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="text-muted small">
                                    IP : <?= htmlspecialchars($act['ip_address']) ?>
                                </div>

                                <?php if ($act['action'] === 'Déconnexion' && !empty($act['session_duration'])): ?>
                                    <div class="text-muted small">
                                        Durée de session : <?= gmdate('H:i:s', $act['session_duration']) ?>
                                    </div>
                                <?php endif; ?>

                            </div>

                            <span class="badge bg-light text-dark">
                                <?= date('d/m/Y H:i', strtotime($act['created_at'])) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
            <?php endif; ?>
            <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $pageAct ? 'active' : '' ?>">
                            <a class="page-link"
                            href="admin.php?page=profile&page_act=<?= $i ?>#activity">
                            <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>

                    </ul>
                </nav>
            <?php endif; ?>


        </div>


    </div>  <!-- fermeture tab content -->

</div>


<script>
// ===============================
// PREVIEW PHOTO INSTANTANÉE
// ===============================
function previewProfilePhoto(input) {
    if (!input.files || !input.files[0]) return;

    const file = input.files[0];

    if (!file.type.startsWith('image/')) {
        alert("Veuillez sélectionner une image valide.");
        input.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
        // Image dans le formulaire
        const preview = document.getElementById('profilePreview');
        if (preview) preview.src = e.target.result;

        // Image dans la topbar
        const topbarAvatar = document.querySelector('.topbar .avatar');
        if (topbarAvatar) topbarAvatar.src = e.target.result;
    };

    reader.readAsDataURL(file);
}

// ===============================
// MISE À JOUR NOM / PRÉNOM LIVE
// ===============================
document.addEventListener('DOMContentLoaded', () => {

    const prenomInput = document.getElementById('prenomInput');
    const nomInput = document.getElementById('nomInput');
    const topbarName = document.querySelector('.profile-name');

    function updateTopbarName() {
        if (!topbarName) return;
        topbarName.textContent = prenomInput.value + ' ' + nomInput.value;
    }

    if (prenomInput && nomInput) {
        prenomInput.addEventListener('input', updateTopbarName);
        nomInput.addEventListener('input', updateTopbarName);
    }
});

function debounce(fn, delay = 500) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

// ===============================
// VÉRIFICATION MOT DE PASSE ACTUEL
document.addEventListener('DOMContentLoaded', () => {
    const currentInput = document.getElementById('currentPassword');
    const msg = document.getElementById('currentPasswordMsg');

    if (!currentInput || !msg || !submitBtn) return;

    const debouncedCheckCurrentPassword = debounce(() => {
    const value = currentInput.value.trim();

    if (value.length < 4) {
        msg.className = 'd-none';
        currentPasswordOk = false;
        updateSubmitState();
        return;
    }

    fetch('../controller/checkCurrentPassword.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'password=' + encodeURIComponent(value)
    })
    .then(res => res.json())
    .then(data => {
        msg.classList.remove('d-none');

        if (data.status === 'ok') {
            msg.textContent = 'Mot de passe correct';
            msg.className = 'text-success fade-in';
            currentPasswordOk = true;
        } else {
            msg.textContent = 'Mot de passe incorrect';
            msg.className = 'text-danger shake';
            currentPasswordOk = false;
        }

        updateSubmitState();
    });
}, 500);

currentInput.addEventListener('input', debouncedCheckCurrentPassword);

});

// ===============================
// VÉRIFICATION CONFIRMATION MOT DE PASSE   
// ===============================
// SÉLECTEURS
// ===============================
const newPasswordInput = document.getElementById('password');
const confirmInput = document.getElementById('confirm');
const confirmMsg = document.getElementById('confirmPasswordMsg');

const profileBtn = document.querySelector('.profile-btn');
const dropdown = document.querySelector('.profile-dropdown');


// ===============================
// VALIDATION CONFIRMATION MOT DE PASSE
// ===============================
function checkPasswordMatch() {
    const newPass = newPasswordInput.value.trim();
    const confirmPass = confirmInput.value.trim();

    // Champ vide → reset
    if (confirmPass.length === 0) {
        confirmMsg.classList.add('d-none');
        confirmInput.classList.remove('is-valid', 'is-invalid');
        confirmPasswordOk = false;
        updateSubmitState();
        return;
    }

    confirmMsg.classList.remove('d-none');

    if (newPass === confirmPass) {
        confirmMsg.textContent = 'Les mots de passe correspondent';
        confirmMsg.className = 'text-success fade-in';
        confirmInput.classList.add('is-valid');
        confirmInput.classList.remove('is-invalid');
        confirmPasswordOk = true;
    } else {
        confirmMsg.textContent = 'Les mots de passe ne correspondent pas';
        confirmMsg.className = 'text-danger shake';
        confirmInput.classList.add('is-invalid');
        confirmInput.classList.remove('is-valid');
        confirmPasswordOk = false;
    }

    updateSubmitState();
}


// ===============================
// ÉTAT DU BOUTON SUBMIT
// ===============================
function updateSubmitState() {
    submitBtn.disabled = !(
        currentPasswordOk &&
        passwordStrong &&
        confirmPasswordOk
    );
}


// ===============================
// EVENTS
// ===============================
confirmInput.addEventListener('input', checkPasswordMatch);
newPasswordInput.addEventListener('input', checkPasswordMatch);


// ===============================
// DROPDOWN PROFIL
// ===============================
if (profileBtn && dropdown) {
    profileBtn.addEventListener('click', () => {
        dropdown.classList.toggle('show');
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const hash = window.location.hash;

    if (!hash) return;

    const map = {
        '#info': 'info-tab',
        '#security': 'security-tab',
        '#activity': 'activity-tab'
    };

    if (map[hash]) {
        const tabTrigger = document.getElementById(map[hash]);
        if (tabTrigger) {
            new bootstrap.Tab(tabTrigger).show();
        }
    }
});


document.addEventListener('DOMContentLoaded', () => {

  const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');

  tabs.forEach(tab => {
    tab.addEventListener('shown.bs.tab', e => {
      localStorage.setItem('activeProfileTab', e.target.getAttribute('data-bs-target'));
    });
  });

  const activeTab = localStorage.getItem('activeProfileTab');
  if (activeTab) {
    const trigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
    if (trigger) {
      new bootstrap.Tab(trigger).show();
    }
  }
});


</script>



