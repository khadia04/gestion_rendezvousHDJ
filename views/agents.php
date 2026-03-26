<?php

require_once '../middlewares/auth.php';
require_once '../middlewares/csrf.php';
require_once '../modele/databaseAgent.php';
require_once __DIR__ . '/../modele/database.php';
require_once '../helpers/activity.php';

requireRole(['super_admin']);
requireAuth('super_admin');

  
function formatRole($role) {
    return match($role) {
        'super_admin' => 'Super admin',
        'admin' => 'Admin',
        'medecin' => 'Médecin',
        'agent' => 'Agent',
        default => ucfirst($role)
    };
}


if (
    isset($_POST['ajax']) &&
    $_POST['ajax'] === 'save_services'
) {
    verifyCsrfToken();

    $username = $_POST['username'];
    $services = json_decode($_POST['services'], true);

    prepare_executeSQL(
        "DELETE FROM agent_service WHERE agent_username = :username",
        ['username' => $username]
    );

    foreach ($services as $codeService) {
        prepare_executeSQL(
            "INSERT INTO agent_service (agent_username, codeService)
             VALUES (:username, :code)",
            [
                'username' => $username,
                'code' => $codeService
            ]
        );
    }

    exit;
}


/* =========================
   PAGINATION & FILTRES
========================= */
$limit   = 5;
$pageNum = isset($_GET['p']) && (int)$_GET['p'] > 0 ? (int)$_GET['p'] : 1;
$offset = ($pageNum - 1) * $limit;

$search = $_GET['search'] ?? '';
$role   = $_GET['role'] ?? '';

$agents = getAgentsPaginated($search, $role, $limit, $offset);

/* =========================
   SERVICES
========================= */
$services = executeSQL(
    "SELECT codeService, designService FROM service"
)->fetchAll();

/* =========================
   SERVICES PAR AGENT
========================= */
$agentServicesMap = [];
$agentServiceCount = [];

foreach ($agents as $a) {
    if ($a['role'] === 'agent') {
        $agentServicesMap[$a['username']] = getAgentServices($a['username']);
        $agentServiceCount[$a['username']] =
            count($agentServicesMap[$a['username']] ?? []);
    } else {
        $agentServiceCount[$a['username']] = 'ALL';
    }
}

/* =========================
   AJOUT AGENT
========================= */
if (isset($_POST['add_agent'])) {

    $prenom    = trim($_POST['prenom_agent']);
    $nom       = trim($_POST['nom_agent']);
    $telephone = !empty($_POST['telephone_agent_full'])
        ? $_POST['telephone_agent_full']
        : ($_POST['telephone_agent'] ?? null);

    $username  = trim($_POST['username']);
    $email     = trim($_POST['email']);
    $role      = $_POST['role'];

    $allowedRoles = ['admin', 'medecin', 'agent'];

    if (!in_array($role, $allowedRoles)) {
        $_SESSION['error'] = "Rôle invalide.";
        header("Location: admin.php?page=agents");
        exit;
    }

    // téléphone
    if (!preg_match('/^\+221(70|71|75|76|77|78|33)[0-9]{7}$/', $telephone)) {
        $_SESSION['error'] = "Numéro invalide.";
        header("Location: admin.php?page=agents");
        exit;
    }

    // username unique
    $exists = prepare_executeSQL(
        "SELECT COUNT(*) as total FROM agent WHERE username = :username",
        ['username' => $username]
    )->fetch();

    if ($exists['total'] > 0) {
        $_SESSION['error'] = "Username déjà utilisé.";
        header("Location: admin.php?page=agents");
        exit;
    }

    // email unique
    $emailExists = prepare_executeSQL(
        "SELECT COUNT(*) as total FROM agent WHERE email = :email",
        ['email' => $email]
    )->fetch();

    if ($emailExists['total'] > 0) {
        $_SESSION['error'] = "Email déjà utilisé.";
        header("Location: admin.php?page=agents");
        exit;
    }

    // service obligatoire
    if ($role === 'agent' && empty($_POST['services'])) {
        $_SESSION['error'] = "Un agent doit avoir au moins un service.";
        header("Location: admin.php?page=agents");
        exit;
    }

    // mot de passe par défaut
    $password = password_hash('123456', PASSWORD_DEFAULT);

    try {
        prepare_executeSQL(
            "INSERT INTO agent 
            (prenom_agent, nom_agent, telephone_agent, username, email, role, password, status)
            VALUES 
            (:prenom, :nom, :tel, :username, :email, :role, :password, 1)",
            [
                'prenom'   => $prenom,
                'nom'      => $nom,
                'tel'      => $telephone,
                'username' => $username,
                'email'    => $email,
                'role'     => $role,
                'password' => $password
            ]
        );

        // services
        if ($role === 'agent') {
            foreach ($_POST['services'] as $codeService) {
                prepare_executeSQL(
                    "INSERT INTO agent_service (agent_username, codeService)
                     VALUES (:username, :codeService)",
                    [
                        'username'    => $username,
                        'codeService' => $codeService
                    ]
                );
            }
        }

        // LOG (IMPORTANT)
        logActivity(
            $_SESSION['user_id'],
            "Création utilisateur",
            "Création d’un $role : $username",
            $_SESSION['role']
        );

        $_SESSION['success'] = ucfirst($role) . " ajouté avec succès";

    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur : utilisateur déjà existant.";
    }

    echo "<script>window.location.href='admin.php?page=agents';</script>";
    exit;
}
/* =========================
   MODIFICATION AGENT
========================= */

if (isset($_POST['edit_agent'])) {

    $username  = $_POST['username'];
    $role      = $_POST['role'];
    $prenom    = trim($_POST['prenom_agent']);
    $nom       = trim($_POST['nom_agent']);
    $telephone = !empty($_POST['telephone_agent_full'])
    ? $_POST['telephone_agent_full']
    : ($_POST['telephone_agent'] ?? null);



    if ($role === 'agent' && empty($_POST['edit_services'])) {
        $_SESSION['error'] = "Un agent doit avoir au moins un service.";
        header("Location: admin.php?page=agents");
        exit;
    }

    if (!preg_match('/^\+221(70|71|75|76|77|78|33)[0-9]{7}$/', $telephone)) {
    $_SESSION['error'] = "Numéro de téléphone sénégalais invalide.";
    header("Location: admin.php?page=agents");
    exit;
    }

    prepare_executeSQL(
        "UPDATE agent 
         SET prenom_agent = :prenom,
             nom_agent = :nom,
             telephone_agent = :telephone,
             role = :role
         WHERE username = :username",
        [
            'prenom'    => $prenom,
            'nom'       => $nom,
            'telephone' => $telephone,
            'role'      => $role,
            'username'  => $username
        ]
    );

    prepare_executeSQL(
        "DELETE FROM agent_service WHERE agent_username = :username",
        ['username' => $username]
    );

    if ($role === 'agent') {
        foreach ($_POST['edit_services'] as $codeService) {
            prepare_executeSQL(
                "INSERT INTO agent_service (agent_username, codeService)
                 VALUES (:username, :codeService)",
                [
                    'username'    => $username,
                    'codeService' => $codeService
                ]
            );
        }
    }

    logActivity(
    $_SESSION['user_id'],
    "Modification d’un agent",
    "Modification agent : " . $username,
    $_SESSION['role']
);

    $_SESSION['success'] = "Agent modifié avec succès";
    
}

/* ========================= 
   ACTIVATION
========================= */
if (isset($_POST['activate_agent'], $_POST['username'], $_POST['role'])) {

    if ($_SESSION['role'] !== 'super_admin') {
        $_SESSION['error'] = "Action non autorisée.";
        header("Location: admin.php?page=agents");
        exit;
    }

    try {

        toggleAgentStatus($_POST['username'], 1);

        logActivity(
            $_SESSION['user_id'],
            "Activation utilisateur",
            "Utilisateur activé : " . $_POST['username'],
            $_SESSION['role']
        );

        $_SESSION['success'] = "Utilisateur activé avec succès";

    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de l’activation.";
    }

    header("Location: admin.php?page=agents");
    exit;
}

/* ========================= 
   DÉSACTIVATION
========================= */
if (isset($_POST['deactivate_agent'], $_POST['username'], $_POST['role'])) {

    //  Vérifier que c'est un super admin
    if ($_SESSION['role'] !== 'super_admin') {
        $_SESSION['error'] = "Action non autorisée.";
        header("Location: admin.php?page=agents");
        exit;
    }

    $username = $_POST['username'];
    $role     = $_POST['role'];

    //  Empêcher de se désactiver soi-même
    if ($username === $_SESSION['username']) {
        $_SESSION['error'] = "Vous ne pouvez pas vous désactiver.";
        header("Location: admin.php?page=agents");
        exit;
    }

    //  Empêcher de désactiver un super admin
    if ($role === 'super_admin') {
        $_SESSION['error'] = "Impossible de désactiver un super admin.";
        header("Location: admin.php?page=agents");
        exit;
    }

    //  Désactivation
    try {

        toggleAgentStatus($username, 0);

        //  Log activité
        logActivity(
            $_SESSION['user_id'],
            "Désactivation utilisateur",
            "Utilisateur désactivé : " . $username,
            $_SESSION['role']
        );

        $_SESSION['success'] = "Utilisateur désactivé avec succès";

    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la désactivation.";
    }

    header("Location: admin.php?page=agents");
    exit;
}

?>


<div class="agents-page">
    <div class="container-fluid" >
        <h3 class="mb-4">Gestion des agents</h3>

        <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i>
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i>
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgentModal">
            <i class="bi bi-person-plus"></i> Ajouter un agent
        </button>
    </div>
<form method="GET" action="admin.php" style="margin: 10px;">
    
    <!-- garder la page agents -->
    <input type="hidden" name="page" value="agents">

    <div class="row g-3 align-items-center">

        <!-- INPUT EMAIL -->
        <div class="col-md-4" >
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Rechercher (email, nom, prénom, username, téléphone)"
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
            >
        </div>

        <!-- SELECT ROLE -->
        <div class="col-md-3" >
            <select name="role" class="form-select">
              <option value="">Tous les rôles</option>

              <option value="super_admin"
                <?= ($_GET['role'] ?? '') === 'super_admin' ? 'selected' : '' ?>>
                Super admin
              </option>

              <option value="admin"
                <?= ($_GET['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                Admin
              </option>

              <option value="medecin"
                <?= ($_GET['role'] ?? '') === 'medecin' ? 'selected' : '' ?>>
                Médecin
              </option>

              <option value="agent"
                <?= ($_GET['role'] ?? '') === 'agent' ? 'selected' : '' ?>>
                Agent
              </option>
            </select>
        </div>

        <!-- BOUTON RECHERCHE -->
        <div class="col-md-1 d-grid" style=" font-size: 1.5rem; "  >
            <button type="submit" class="btn" title="Rechercher"  style="color: #2563eb; background-color: none;">
                <i class="bi bi-search"></i>
            </button>
        </div>

    </div>
</form>
  <div class="card shadow-sm border-0 p-3">
    <table class="table agents-table">
      <thead class="table-light">
        <tr>
          <th>Username</th>
          <th>Nom</th>
          <th>Email</th>
          <th>Téléphone</th>
          <th>Rôle</th>
          <th>Statut</th>
          <th>Services</th>
          <th>Création</th>
          <th>Actions</th>
        </tr>    
      </thead>

      <?php if (empty($agents)): ?>
        <tr>
          <td colspan="8" class="text-center text-muted">
            Aucun agent trouvé
          </td>
        </tr>
      <?php else: ?>

      <tbody>
        <?php foreach ($agents as $agent): ?>
        <tr>
          <td><?= htmlspecialchars($agent['username']) ?></td>
          <td><?= htmlspecialchars($agent['prenom_agent'].' '.$agent['nom_agent']) ?></td>
          <td><?= htmlspecialchars($agent['email']) ?></td>
          <td><?= htmlspecialchars($agent['telephone_agent']) ?></td>

          <td>
              <span class="badge bg-info"><?= formatRole($agent['role']) ?></span>
          </td>

          <td>
              <?= $agent['status'] 
                  ? '<span class="badge bg-success">Actif</span>' 
                  : '<span class="badge bg-secondary">Désactivé</span>' ?>
          </td>

          <td class="text-center">
            <?php if (in_array($agent['role'], ['admin', 'super_admin', 'medecin'])): ?>
              <span class="badge bg-success" title="Accès à tous les services">
                Tous
              </span>

            <?php elseif ($agentServiceCount[$agent['username']] === 0): ?>
              <span class="badge bg-secondary" title="Aucun service attribué">
                0
              </span>

            <?php else: ?>
              <span class="badge bg-primary">
                <?= $agentServiceCount[$agent['username']] ?>
              </span>
            <?php endif; ?>
          </td>

          <td><?= date('d/m/Y', strtotime($agent['created_at'])) ?></td>

          <td class="actions-cell" >
            <div class="d-flex gap-2 ">
              <!-- MODIFIER -->
              <button class="btn btn-primary btn-sm "  
                  data-bs-toggle="modal"
                  data-bs-target="#editAgentModal"
                  data-username="<?= $agent['username'] ?>"
                  data-email="<?= $agent['email'] ?>"
                  data-prenom="<?= $agent['prenom_agent'] ?>"
                  data-nom="<?= $agent['nom_agent'] ?>"
                  data-telephone="<?= $agent['telephone_agent'] ?>"
                  data-role="<?= $agent['role'] ?>"
                  title="Modifier l’agent/ l'admin">
                
                  <i class="bi bi-pencil"></i>
              </button>

              <?php if ($agent['status'] == 0): ?>
              <button
                type="button"
                class="btn btn-success btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#confirmActivateModal"
                data-username="<?= $agent['username'] ?>"
                title="Activer l'agent/ l'admin">
                <i class="bi bi-person-check"></i>
              </button>

              <?php elseif ($agent['role'] !== 'super_admin'): ?>
              <button
                type="button"
                class="btn btn-danger btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#confirmDeactivateModal"
                data-username="<?= $agent['username'] ?>"
                data-role="<?= $agent['role'] ?>"
                title="Désactiver l'agent/ l'admin"
                style="background:rgb(255,0,0)">
                <i class="bi bi-person-x"></i>
              </button>
              <?php endif; ?>

            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php endif; ?>
    </table>

  </div>

<nav class="mt-3">
  <ul class="pagination justify-content-center">
    <?php if ($pageNum > 1): ?>
      <li class="page-item">
        <a class="page-link" href="?page=agents&p=<?= $pageNum-1 ?>">Précédent</a>
      </li>
    <?php endif; ?>
     <li class="page-item active">
        <span class="page-link"><?= $pageNum ?></span>
      </li>
      <li class="page-item">
        <a class="page-link" href="?page=agents&p=<?= $pageNum+1 ?>">Suivant</a>
      </li>
  </ul>
</nav>

    <!-- MODAL AJOUT AGENT -->
<div class="modal fade" id="addAgentModal" tabindex="-1" >
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

    <div class="modal-content agent-modal shadow-lg rounded-4">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Ajouter un agent</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="col-md-4">
              <label class="form-label">Prénom</label>
              <input type="text" name="prenom_agent" class="form-control " placeholder="Prenom de agent/admin" required >
            </div>

            <div class="col-md-4">
              <label class="form-label">Nom</label>
              <input type="text" name="nom_agent" class="form-control " placeholder="Nom de agent/admin" required >
            </div>

            <div class="col-md-4 col-lg-3">
              <label class="form-label">Téléphone</label>

              <div class="phone-wrapper">
                <input
                  id="telephone_agent_add"
                  type="tel"
                  name="telephone_agent"
                  class="form-control"
                  placeholder="77 123 45 67"
                  required
                >
                <!-- Icône validation -->
                <span class="phone-valid-icon d-none">
                  ✔️
                </span>
              </div>
              <input type="hidden" name="telephone_agent_full">
            </div>


            <div class="col-md-4">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" placeholder="Username de agent/admin" required >
            </div>

            <div class="col-md-4">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control " placeholder="Veillez mettre l'email de agent/admin" required >
            </div>

            <div class="col-md-4">
              <label class="form-label " >Rôle</label>
              <select name="role" class="form-select" required  >
                <option value="" selected disabled>— Choisir le rôle —</option>
                <option value="admin">Admin</option>
                <option value="agent">Agent</option>
                <option value="medecin">Medecin</option>
              </select>
            </div>

            <div id="add-services-container" class="services-container" style="display:none;">

              <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Services autorisés</strong>
                <span class="badge bg-primary services-count">0 sélectionné</span>

              </div>

              <div class="row align-items-center mb-3">

                <!-- Recherche -->
                <div class="col-md-5">
                  <input
                    type="text"
                    class="form-control service-search"
                    placeholder="Rechercher un service..."
                  >
                </div>

                <!-- Actions -->
                <div class="col-md-2 d-flex gap-2">
                  <button
                    type="button"
                    class="btn btn-outline-primary btn-sm select-all"
                    title="Tout sélectionner"
                  >
                    <i class="bi bi-check2-square"></i>
                  </button>

                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm deselect-all"
                    title="Tout désélectionner"
                  >
                    <i class="bi bi-x-square"></i>
                  </button>
                </div>
              </div>


              <!--  Liste -->
              <div class=" services-list">
                <div class="row">
                  <?php foreach ($services as $service): ?>
                    <div class="col-md-4 service-item">
                      <div class="form-check">
                        <input
                          class="form-check-input service-checkbox"
                          type="checkbox"
                          name="services[]"
                          value="<?= $service['codeService'] ?>"
                        >
                        <label class="form-check-label">
                          <?= htmlspecialchars($service['designService']) ?>
                        </label>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="text-danger mt-2 services-error" style="display:none;">
                Veuillez sélectionner au moins un service.
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="add_agent" class="btn btn-success">
            <i class="bi bi-check-circle"></i> Enregistrer
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Annuler
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

<!-- MODAL MODIFIER AGENT -->
<div class="modal fade" id="editAgentModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">

    <div class="modal-content agent-modal shadow-lg rounded-4">

      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Modifier un agent</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="username">

          <div class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="col-md-4">
              <label class="form-label">Prénom</label>
              <input type="text" name="prenom_agent" class="form-control" required >
            </div>

            <div class="col-md-4">
              <label class="form-label">Nom</label>
              <input type="text" name="nom_agent" class="form-control" required >
            </div>

            <div class="col-md-4 col-lg-3">
              <label class="form-label">Téléphone</label>

              <div class="phone-wrapper">
                <input
                  id="telephone_agent_edit"
                  type="tel"
                  name="telephone_agent"
                  class="form-control"
                  placeholder="77 123 45 67"
                  required
                >
                <!-- Icône validation -->
                <span class="phone-valid-icon d-none">
                  ✔️
                </span>
              </div>
              <input type="hidden" name="telephone_agent_full">


            </div>


            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input
                type="email"
                name="email"
                class="form-control"
                readonly
                style="background-color:#f8f9fa; cursor:not-allowed;"
              >
            </div>
    
            <div class="col-md-6">
              <label class="form-label">Rôle</label>
              <select name="role" class="form-select">
                <option value="agent">Agent</option>
                <option value="admin">Admin</option>
              </select>
            </div>

            <div id="edit-services-container" class="services-container" style="display:none;">

              <div class="d-flex justify-content-between align-items-center mb-2">
                <strong>Services autorisés</strong>
                <span class="badge bg-primary services-count">0 sélectionné</span>

              </div>
              
              <div class="row align-items-center mb-3">

                <!-- Recherche -->
                <div class="col-md-5">
                  <input
                    type="text"
                    class="form-control service-search"
                    placeholder="Rechercher un service..."
                  >
                </div>

                <!--  Actions -->
                <div class="col-md-2 d-flex gap-2">
                  <button
                    type="button"
                    class="btn btn-outline-primary btn-sm select-all"
                    title="Tout sélectionner"
                  >
                    <i class="bi bi-check2-square"></i>
                  </button>

                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm deselect-all"
                    title="Tout désélectionner"
                  >
                    <i class="bi bi-x-square"></i>
                  </button>
                </div>

              </div>

              <!-- Liste -->
              <div class=" services-list">
                <div class="row">
                  <?php foreach ($services as $service): ?>
                    <div class="col-md-4 service-item">
                      <div class="form-check">
                        <input
                          class="form-check-input service-checkbox"
                          type="checkbox"
                          name="edit_services[]"
                          value="<?= $service['codeService'] ?>"
                        >
                        <label class="form-check-label">
                          <?= htmlspecialchars($service['designService']) ?>
                        </label>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

              <div class="text-danger mt-2 services-error" style="display:none;">
                Veuillez sélectionner au moins un service.
              </div>
            </div>


           

          </div>
        </div>

        

        <div class="modal-footer">
          <button type="submit" name="edit_agent" class="btn btn-primary">
            Enregistrer les modifications
          </button>
        </div>
      </form>

    </div>
    
  </div>
  
</div>
<!-- MODAL CONFIRMER ACTIVATION -->
<div class="modal fade" id="confirmActivateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content agent-modal">

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="username" id="activateUsername">

        <div class="modal-header">
          <h5 class="modal-title text-success">
            <i class="bi bi-person-check"></i> Activer l’agent
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          Voulez-vous vraiment activer cet agent ?
        </div>

        <div class="modal-footer">
          <button type="submit" name="activate_agent" class="btn btn-success">
            Activer
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Annuler
          </button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- MODAL CONFIRMER DÉSACTIVATION -->
<div class="modal fade" id="confirmDeactivateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content agent-modal">

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="username" id="deactivateUsername">
        <input type="hidden" name="role" id="deactivateRole">

        <div class="modal-header">
          <h5 class="modal-title text-danger">
            <i class="bi bi-person-x"></i> 
            <button 
              data-username="<?= $agent['username'] ?>"
              data-role="<?= $agent['role'] ?>"
              data-bs-toggle="modal"
              data-bs-target="#confirmDeactivateModal">
              Désactiver
            </button>
          </h5>
          
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          Voulez-vous vraiment désactiver cet utilisateur ?
        </div>

        <div class="modal-footer">
          <button type="submit" name="deactivate_agent" class="btn btn-danger">
            Désactiver
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Annuler
          </button>
        </div>
      </form>

    </div>
  </div>
</div>

</div>
</div>

<!-- script MODAL CONFIRMER ACTIVATION -->

<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.3.5/build/js/intlTelInput.min.js"></script>


<script>
const activateModal   = document.getElementById('confirmActivateModal');
const deactivateModal = document.getElementById('confirmDeactivateModal');

if (activateModal) {
  activateModal.addEventListener('show.bs.modal', e => {
    document.getElementById('activateUsername').value =
      e.relatedTarget.dataset.username;
  });
}

if (deactivateModal) {
  deactivateModal.addEventListener('show.bs.modal', e => {
    document.getElementById('deactivateUsername').value =
      e.relatedTarget.dataset.username;
    document.getElementById('deactivateRole').value =
      e.relatedTarget.dataset.role;
  });
}
</script>

<!-- script MODAL MODIFIER AGENT -->
<script>
const agentServicesMap = <?= json_encode($agentServicesMap) ?>;
const editModal = document.getElementById('editAgentModal');

if (editModal) {
  editModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button) return;

    //  Champs texte
    editModal.querySelector('input[name="username"]').value =
      button.getAttribute('data-username') || '';

    editModal.querySelector('input[name="prenom_agent"]').value =
      button.getAttribute('data-prenom') || '';

    editModal.querySelector('input[name="nom_agent"]').value =
      button.getAttribute('data-nom') || '';

    editModal.querySelector('input[name="telephone_agent"]').value =
      button.getAttribute('data-telephone') || '';

    editModal.querySelector('input[name="email"]').value =
      button.getAttribute('data-email') || '';

    //  Rôle
    const role = button.getAttribute('data-role') || 'admin';
    editModal.querySelector('select[name="role"]').value = role;

    // Services
    const username = button.getAttribute('data-username');
    const servicesBox = document.getElementById('edit-services-container');
    const checkboxes = servicesBox.querySelectorAll('.edit-service-checkbox');

    // reset
    checkboxes.forEach(cb => cb.checked = false);

    if (role === 'agent') {
      servicesBox.style.display = 'block';

      if (agentServicesMap[username]) {
        agentServicesMap[username].forEach(code => {
          const cb = servicesBox.querySelector(
            `input[value="${code}"]`
          );
          if (cb) cb.checked = true;
        });
      }
    } else {
      servicesBox.style.display = 'none';
    }
  });
}
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('addAgentModal');
  if (!modal) return;

  const form = modal.querySelector('form');
  const roleSelect = modal.querySelector('select[name="role"]');
  const servicesBox = document.getElementById('services-container');
  const servicesError = document.getElementById('services-error');

  if (!form || !roleSelect || !servicesBox || !servicesError) return;

  //  Reset des services
  function resetServices() {
    servicesBox.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      cb.checked = false;
    });
    servicesBox.style.display = 'none';
    servicesError.style.display = 'none';
  }

  //  Afficher / masquer les services selon le rôle
  function toggleServices() {
    const role = roleSelect.value.toLowerCase();
    servicesBox.style.display = (role === 'agent') ? 'block' : 'none';

    // si on passe admin → cacher l’erreur
    if (role !== 'agent') {
      servicesError.style.display = 'none';
    }
  }


  //  Validation élégante à la soumission
  form.addEventListener('submit', function (e) {
    const role = roleSelect.value;
    const checked = servicesBox.querySelectorAll('input[type="checkbox"]:checked');

    servicesError.style.display = 'none';

    if (role === 'agent' && checked.length === 0) {
      e.preventDefault();
      servicesError.style.display = 'block';
    }
  });

  //  Faire disparaître l’erreur dès qu’on coche un service
  servicesBox.addEventListener('change', function () {
    servicesError.style.display = 'none';
  });

  // À chaque ouverture du modal
  modal.addEventListener('show.bs.modal', function () {
    resetServices();
  });

  // Changement de rôle
  roleSelect.addEventListener('change', toggleServices);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {

  function initServices(modalId, containerId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    const roleSelect = modal.querySelector('select[name="role"]');
    const container  = modal.querySelector(containerId);

    if (!roleSelect || !container) return;

    const searchInput = container.querySelector('.service-search');
    const checkboxes  = container.querySelectorAll('.service-checkbox');
    const countBadge  = container.querySelector('.services-count');
    const selectAll   = container.querySelector('.select-all');
    const deselectAll = container.querySelector('.deselect-all');
    const errorBox    = container.querySelector('.services-error');

    function updateCount() {
      const count = [...checkboxes].filter(cb => cb.checked).length;
      countBadge.textContent = count + ' sélectionné' + (count > 1 ? 's' : '');
      if (count > 0 && errorBox) errorBox.style.display = 'none';
    }

    function toggleServices() {
      container.style.display =
        roleSelect.value === 'agent' ? 'block' : 'none';
    }

    roleSelect.addEventListener('change', toggleServices);

    if (searchInput) {
      searchInput.addEventListener('input', () => {
        const value = searchInput.value.toLowerCase();
        container.querySelectorAll('.service-item').forEach(item => {
          item.style.display =
            item.textContent.toLowerCase().includes(value)
              ? 'block'
              : 'none';
        });
      });
    }

    if (selectAll) {
      selectAll.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = true);
        updateCount();
      });
    }

    if (deselectAll) {
      deselectAll.addEventListener('click', () => {
        checkboxes.forEach(cb => cb.checked = false);
        updateCount();
      });
    }

    checkboxes.forEach(cb =>
      cb.addEventListener('change', updateCount)
    );

    modal.addEventListener('show.bs.modal', () => {
      toggleServices();
      updateCount();
    });
  }

  // 🔥 Inalisation des deux modals
  initServices('addAgentModal', '#add-services-container');
  initServices('editAgentModal', '#edit-services-container');

});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {

  const editModal = document.getElementById('editAgentModal');
  if (!editModal) return;

  const container = editModal.querySelector('#edit-services-container');
  if (!container) return;

  const checkboxes = container.querySelectorAll('.service-checkbox');

  let saveTimeout = null;

  function autoSaveServices() {
    const username = editModal.querySelector('input[name="username"]').value;
    const services = [...checkboxes]
      .filter(cb => cb.checked)
      .map(cb => cb.value);

    clearTimeout(saveTimeout);

    saveTimeout = setTimeout(() => {
      fetch('admin.php?page=agents', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          ajax: 'save_services',
          username: username,
          services: JSON.stringify(services),
          csrf_token: '<?= $_SESSION['csrf_token'] ?>'
        })
      });
    }, 600); // anti-spam
  }

  checkboxes.forEach(cb =>
    cb.addEventListener('change', autoSaveServices)
  );

});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {

  function initPhone(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;

    const validIcon = input.parentElement.querySelector('.phone-valid-icon');

    const iti = window.intlTelInput(input, {
      initialCountry: "sn",
      separateDialCode: true,
      nationalMode: true,
      autoPlaceholder: "polite",
      utilsScript:
        "https://cdn.jsdelivr.net/npm/intl-tel-input@18.3.5/build/js/utils.js",
    });

    function isValidSenegalNumber(fullNumber) {
      // Nettoyage
      const num = fullNumber.replace(/\s+/g, '');

      // +221XXXXXXXXX
      if (!num.startsWith('+221')) return false;

      const local = num.substring(4); // après +221

      if (local.length !== 9) return false;

      const prefix = local.substring(0, 2);

      const validPrefixes = [
        '70','71','75','76','77','78','33'
      ];

      return validPrefixes.includes(prefix);
    }

    function validate() {
      const fullNumber = iti.getNumber();

      if (isValidSenegalNumber(fullNumber)) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        validIcon.classList.remove('d-none');
      } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        validIcon.classList.add('d-none');
      }
    }

    input.addEventListener('input', validate);
    input.addEventListener('blur', validate);

    return iti;
  }

  //  Initialisation ADD & EDIT
  const itiAdd  = initPhone('telephone_agent_add');
  const itiEdit = initPhone('telephone_agent_edit');

  // Sauvegarde du numéro complet à la soumission
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', e => {
      const input = form.querySelector('[type="tel"]');
      const hidden = form.querySelector('[name="telephone_agent_full"]');
      if (!input || !hidden) return;

      const iti = input.id.includes('add') ? itiAdd : itiEdit;
      const fullNumber = iti.getNumber();

      if (!fullNumber) {
        e.preventDefault();
        alert("Numéro de téléphone invalide");
        return;
      }

      hidden.value = fullNumber;
    });
  });

});



</script>


