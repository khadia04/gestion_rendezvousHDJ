<?php
// 1️⃣ TRAITEMENT DU FORMULAIRE (AVANT LE HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    // Sécurité CSRF
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Session invalide.";
    }

    $prenom    = trim($_POST['prenom']);
    $nom       = trim($_POST['nom']);
    $telephone = trim($_POST['telephone']);
    $userId    = $_SESSION['user_id'];

    if (!$prenom || !$nom) {
        $_SESSION['error'] = "Le prénom et le nom sont obligatoires.";
    }

    $db = getConnection();

    /* =========================
       UPLOAD PHOTO (OPTIONNEL)
    ========================= */
    $photoName = null;

    if (!empty($_FILES['photo']['name'])) {

        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = "Format de photo invalide (jpg, png uniquement).";
        }

        if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = "La photo ne doit pas dépasser 2 Mo.";
        }

        $photoName = 'admin_' . $userId . '.' . $ext;
        move_uploaded_file(
            $_FILES['photo']['tmp_name'],
            "../assets/img/" . $photoName
        );
    }

    /* =========================
       UPDATE DB
    ========================= */
    $sql = "
        UPDATE agent
        SET prenom_agent = :prenom,
            nom_agent = :nom,
            telephone_agent = :tel
            " . ($photoName ? ", photo = :photo" : "") . "
        WHERE id = :id
    ";

    $params = [
        'prenom' => $prenom,
        'nom'    => $nom,
        'tel'    => $telephone,
        'id'     => $userId
    ];

    if ($photoName) {
        $params['photo'] = $photoName;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);


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
             INFORMATIONS
        ====================== -->
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


            <form method="POST" enctype="multipart/form-data" class="row g-3">
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
</script>


