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

// ===============================
// GROUPER LES ACTIVITÉS PAR JOUR
// ===============================
$groupedActivities = [];

foreach ($activities as $act) {

    $dateKey = date('Y-m-d', strtotime($act['created_at']));

    // Libellé humain
    if ($dateKey === date('Y-m-d')) {
        $label = "Aujourd’hui";
    } elseif ($dateKey === date('Y-m-d', strtotime('-1 day'))) {
        $label = "Hier";
    } else {
        $dateObj = new DateTime($dateKey);
        $label = $dateObj->format('d F Y');

    }

    // Icône selon l’action
    $icon = 'bi-activity';
    $iconClass = 'icon-default';

    if (stripos($act['action'], 'connexion') !== false) {
        $icon = 'bi-box-arrow-in-right';
        $iconClass = 'icon-success';
    } elseif (stripos($act['action'], 'déconnexion') !== false) {
        $icon = 'bi-box-arrow-left';
        $iconClass = 'icon-muted';
    } elseif (stripos($act['action'], 'export') !== false) {
        $icon = 'bi-file-earmark-pdf';
        $iconClass = 'icon-danger';
    } elseif (stripos($act['action'], 'mise à jour') !== false) {
        $icon = 'bi-pencil-square';
        $iconClass = 'icon-primary';
    } elseif (stripos($act['action'], 'suppression') !== false) {
        $icon = 'bi-trash';
        $iconClass = 'icon-danger';
    }

    $act['icon'] = $icon;
    $act['iconClass'] = $iconClass;
    $groupedActivities[$label][] = $act;
    

}

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

                    <div class="row">

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

                <h5>🔒 Pour votre sécurité, choisissez un mot de passe fort.</h5>

                <!-- Mot de passe actuel -->
                 <div class="row">
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

            <!-- FILTRES -->
            <form method="get" action="admin.php#activity" class="row g-3 mb-4">

                <input type="hidden" name="page" value="profile">

                <!-- Recherche -->
                <div class="col-md-3">
                    <label for="q">Recherche</label>
                    <input
                        id="q"
                        name="q"
                        type="text"
                        class="form-control"
                        placeholder="Nom, prénom ou action"
                        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                    >
                </div>

                <!-- Date début -->
                <div class="col-md-2">
                    <label>Date début</label>
                    <input
                        type="date"
                        name="date_from"
                        class="form-control"
                        value="<?= $_GET['date_from'] ?? '' ?>"
                    >
                </div>

                <!-- Date fin -->
                <div class="col-md-2">
                    <label>Date fin</label>
                    <input
                        type="date"
                        name="date_to"
                        class="form-control"
                        value="<?= $_GET['date_to'] ?? '' ?>"
                    >
                </div>

                <!-- Actions -->
                <div class="col-md-3 d-flex gap-3 align-items-end">

                    <button class="icon-action primary" title="Filtrer">
                        <i class="bi bi-funnel"></i>
                    </button>

                    <a
                        href="admin.php?page=profile#activity"
                        class="icon-action"
                        title="Réinitialiser"
                    >
                        <i class="bi bi-arrow-clockwise"></i>
                    </a>

                    <a
                        href="../exports/activities_pdf.php?<?= http_build_query($_GET) ?>"
                        target="_blank"
                        class="icon-action danger"
                        title="Exporter en PDF"
                    >
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>

                </div>
            </form>

            <!-- CONTENU ACTIVITÉS -->
            <?php if (empty($groupedActivities)): ?>
                <p class="text-muted">Aucune activité enregistrée.</p>
            <?php else: ?>

                <?php foreach ($groupedActivities as $day => $acts): ?>

                    <!-- TITRE JOUR -->
                    <div class="activity-day">
                        <i class="bi bi-calendar-event"></i>
                        <?= htmlspecialchars($day) ?>
                    </div>

                    <?php foreach ($acts as $act): ?>
                        <div class="activity-card d-flex align-items-start gap-3"
                            data-action="<?= strtolower($act['action']) ?>"
                            data-user="<?= strtolower($act['prenom_agent'].' '.$act['nom_agent']) ?>"
                            data-role="<?= strtolower($act['role']) ?>"
                            data-ip="<?= $act['ip_address'] ?>"
                        >

                        <!-- ICÔNE -->
                        <div class="activity-icon <?= $act['iconClass'] ?>">
                            <i class="bi <?= $act['icon'] ?>"></i>
                        </div>

                        <!-- CONTENU -->
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">

                            <div>
                                <div class="activity-title">
                                <?= htmlspecialchars($act['action']) ?>
                                <span class="badge <?= $act['role'] === 'admin' ? 'bg-primary' : 'bg-secondary' ?> ms-2">
                                    <?= ucfirst($act['role']) ?>
                                </span>
                                </div>

                                <div class="activity-desc">
                                <?= htmlspecialchars($act['prenom_agent'].' '.$act['nom_agent']) ?>
                                </div>

                                <?php if (!empty($act['description'])): ?>
                                <div class="activity-meta">
                                    <?= htmlspecialchars($act['description']) ?>
                                </div>
                                <?php endif; ?>

                                <div class="activity-meta">
                                IP : <?= htmlspecialchars($act['ip_address']) ?>
                                </div>
                            </div>

                            <div class="activity-meta">
                                <?= date('H:i', strtotime($act['created_at'])) ?>
                            </div>

                            </div>
                        </div>

                        </div>
                    <?php endforeach; ?>

                <?php endforeach; ?>

                <!-- PAGINATION -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $pageAct ? 'active' : '' ?>">
                                    <a
                                        class="page-link"
                                        href="admin.php?page=profile&page_act=<?= $i ?>#activity"
                                    >
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                        </ul>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>

        </div>

    </div>  <!-- fermeture tab content -->
</div>


<script>
// ===============================
// VARIABLES GLOBALES (OBLIGATOIRE)
// ===============================
let currentPasswordOk = false;
let confirmPasswordOk = false;
let passwordStrong = false;

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
    reader.onload = e => {
        const preview = document.getElementById('profilePreview');
        if (preview) preview.src = e.target.result;

        const topbarAvatar = document.querySelector('.topbar .avatar');
        if (topbarAvatar) topbarAvatar.src = e.target.result;
    };

    reader.readAsDataURL(file);
}

// ===============================
// DEBOUNCE
// ===============================
function debounce(fn, delay = 500) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

// ===============================
// DOM READY
// ===============================
document.addEventListener('DOMContentLoaded', () => {

    const submitBtn = document.getElementById('submitBtn');

    // ===============================
    // VÉRIFICATION MOT DE PASSE ACTUEL
    // ===============================
    const currentInput = document.getElementById('currentPassword');
    const currentMsg = document.getElementById('currentPasswordMsg');

    if (currentInput && currentMsg) {
        const checkCurrentPassword = debounce(() => {
            const value = currentInput.value.trim();

            if (value.length < 4) {
                currentMsg.className = 'd-none';
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
                currentMsg.classList.remove('d-none');

                if (data.status === 'ok') {
                    currentMsg.textContent = 'Mot de passe correct';
                    currentMsg.className = 'text-success fade-in';
                    currentPasswordOk = true;
                } else {
                    currentMsg.textContent = 'Mot de passe incorrect';
                    currentMsg.className = 'text-danger shake';
                    currentPasswordOk = false;
                }

                updateSubmitState();
            });
        }, 500);

        currentInput.addEventListener('input', checkCurrentPassword);
    }

    // ===============================
    // FORCE DU MOT DE PASSE
    // ===============================
    const newPasswordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const strengthWrapper = document.getElementById('strengthWrapper');

    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', () => {
            const value = newPasswordInput.value;
            let strength = 0;

            if (value.length >= 8) strength++;
            if (/[A-Z]/.test(value)) strength++;
            if (/[0-9]/.test(value)) strength++;
            if (/[^A-Za-z0-9]/.test(value)) strength++;

            if (!value) {
                strengthWrapper.style.display = 'none';
                passwordStrong = false;
                updateSubmitState();
                return;
            }

            strengthWrapper.style.display = 'block';
            strengthBar.style.width = (strength * 25) + '%';

            if (strength >= 3) {
                strengthBar.className = 'progress-bar bg-success';
                strengthText.textContent = 'Mot de passe fort';
                passwordStrong = true;
            } else {
                strengthBar.className = 'progress-bar bg-danger';
                strengthText.textContent = 'Mot de passe faible';
                passwordStrong = false;
            }

            updateSubmitState();
        });
    }

    // ===============================
    // CONFIRMATION MOT DE PASSE
    // ===============================
    const confirmInput = document.getElementById('confirm');
    const confirmMsg = document.getElementById('confirmPasswordMsg');

    function checkPasswordMatch() {
        if (!newPasswordInput || !confirmInput) return;

        const newPass = newPasswordInput.value.trim();
        const confirmPass = confirmInput.value.trim();

        if (!confirmPass) {
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

    if (newPasswordInput && confirmInput) {
        newPasswordInput.addEventListener('input', checkPasswordMatch);
        confirmInput.addEventListener('input', checkPasswordMatch);
    }

    // ===============================
    // ÉTAT DU BOUTON
    // ===============================
    function updateSubmitState() {
        if (!submitBtn) return;
        submitBtn.disabled = !(
            currentPasswordOk &&
            passwordStrong &&
            confirmPasswordOk
        );
    }
});
</script>

<script>
// ===============================
// MÉMORISATION ONGLET ACTIF
// ===============================
document.addEventListener('DOMContentLoaded', () => {

    const tabs = document.querySelectorAll('[data-bs-toggle="tab"]');

    // Sauvegarde l’onglet actif
    tabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', e => {
            const target = e.target.getAttribute('data-bs-target');
            if (target) {
                localStorage.setItem('activeProfileTab', target);
            }
        });
    });

    // Restaure l’onglet actif après refresh
    const activeTab = localStorage.getItem('activeProfileTab');
    if (activeTab) {
        const trigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
        if (trigger) {
            new bootstrap.Tab(trigger).show();
        }
    }
});
</script>
