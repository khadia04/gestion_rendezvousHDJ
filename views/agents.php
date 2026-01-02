<?php
require_once '../middlewares/auth.php';
require_once '../middlewares/csrf.php';
require_once '../modele/databaseAgent.php';

requireAuth('admin');

/* CSRF UNIQUEMENT EN POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
}

/* PAGINATION & FILTRES */
$limit = 5;
$pageNum = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$offset = ($pageNum - 1) * $limit;

$search = $_GET['search'] ?? '';
$role   = $_GET['role'] ?? '';

$agents = getAgentsPaginated($search, $role, $limit, $offset);

/* ACTIVER */
if (isset($_POST['activate_agent'], $_POST['username'])) {
    toggleAgentStatus($_POST['username'], 1);
    $_SESSION['success'] = "Agent activé avec succès";
    header("Location: admin.php?page=agents");
    exit;
}

/* DESACTIVER */
if (isset($_POST['deactivate_agent'], $_POST['username'], $_POST['role'])) {

    if ($_POST['username'] === $_SESSION['username']) {
        $_SESSION['error'] = "Vous ne pouvez pas vous désactiver.";
    } elseif ($_POST['role'] === 'admin') {
        $_SESSION['error'] = "Impossible de désactiver un administrateur.";
    } else {
        toggleAgentStatus($_POST['username'], 0);
        $_SESSION['success'] = "Agent désactivé avec succès";
    }

    header("Location: admin.php?page=agents");
    exit;

     // Mise à jour rôle
    prepare_executeSQL(
        "UPDATE agent SET email = :email, role = :role WHERE username = :username",
        [
            'email' => $_POST['email'],
            'role' => $_POST['role'],
            'username' => $_POST['username']
        ]
    );

    echo "<script>
        alert('Agent modifié avec succès');
        window.location.href='admin.php?page=agents';
    </script>";

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
        <div class="col-md-4" style="margin: 5px; " >
            <input
                type="text"
                name="search"
                style="height: 50px"
                class="form-control"
                placeholder="Rechercher (email, nom, prénom, username)"
                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
            >
        </div>

        <!-- SELECT ROLE -->
        <div class="col-md-3" style="margin: 5px; ">
            <select name="role" class="form-select" style="height: 50px">
                <option value="">Tous les rôles</option>
                <option value="admin" <?= ($_GET['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="agent" <?= ($_GET['role'] ?? '') === 'agent' ? 'selected' : '' ?>>Agent</option>
            </select>
        </div>

        <!-- BOUTON RECHERCHE -->
        <div class="col-md-1 d-grid" style="margin: 5px; font-weight: bold; font-size: 2rem; background-color: rgb(13, 110, 253);border:#0d6efd solid 2px; border-radius: 5px; height: 50px;"  >
            <button type="submit" class="btn" style="font-size:2rem; background:rgb(13, 110, 253); ">
                <i class="bi bi-search" style=" color:#ffffff ; border:1px solid rgb(13, 110, 253);"></i>
            </button>
        </div>

    </div>
</form>
    <table class="table table-bordered table-hover agents-table">
        <thead class="table-primary">
            <tr>
                <th>Username</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Rôle</th>
                <th>Statut</th>
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
        <span class="badge bg-info"><?= $agent['role'] ?></span>
    </td>

    <td>
        <?= $agent['status'] 
            ? '<span class="badge bg-success">Actif</span>' 
            : '<span class="badge bg-secondary">Désactivé</span>' ?>
    </td>

    <td><?= date('d/m/Y', strtotime($agent['created_at'])) ?></td>

    <td class="actions-cell"
      <div class="d-flex gap-2 justify-content-center align-items-center">
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

<?php elseif ($agent['role'] === 'agent'): ?>
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
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content agent-modal">

      

      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Ajouter un agent</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="col-md-6">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required style="border: 1px solid black ;">
            </div>


            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control " required style="border: 1px solid black ;">
            </div>

            <div class="col-md-6">
              <label class="form-label">Prénom</label>
              <input type="text" name="prenom_agent" class="form-control " required style="border: 1px solid black ;">
            </div>

            <div class="col-md-6">
              <label class="form-label">Nom</label>
              <input type="text" name="nom_agent" class="form-control " required style="border: 1px solid black ;">
            </div>

            <div class="col-md-6">
              <label class="form-label">Téléphone</label>
              <input type="text" name="telephone_agent" class="form-control " required style="border: 1px solid black ;">
            </div>

            <div class="col-md-6">
              <label class="form-label " >Rôle</label>
              <select name="role" class="form-select " style="border: 1px solid black ;" >
                <option value="agent">Agent</option>
                <option value="admin">Admin</option>
              </select>
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
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content agent-modal">

      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Modifier un agent</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="username">

          <div class="row g-3">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" required>
            </div>


            <div class="col-md-6">
              <label class="form-label">Téléphone</label>
              <input type="text" name="telephone_agent" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Prénom</label>
              <input type="text" name="prenom_agent" class="form-control" required style="border: 1px solid black ;">
            </div>

            <div class="col-md-6">
              <label class="form-label">Nom</label>
              <input type="text" name="nom_agent" class="form-control" required style="border: 1px solid black ;">
            </div>

            <div class="col-md-6">
              <label class="form-label">Rôle</label>
              <select name="role" class="form-select">
                <option value="agent">Agent</option>
                <option value="admin">Admin</option>
              </select>
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
            <i class="bi bi-person-x"></i> Désactiver l’agent
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

<!-- script MODAL CONFIRMER ACTIVATION -->

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
const editModal = document.getElementById('editAgentModal');

editModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;

    editModal.querySelector('[name="username"]').value = button.dataset.username;
    editModal.querySelector('[name="email"]').value = button.dataset.email;
    editModal.querySelector('[name="prenom_agent"]').value = button.dataset.prenom;
    editModal.querySelector('[name="nom_agent"]').value = button.dataset.nom;
    editModal.querySelector('[name="telephone_agent"]').value = button.dataset.telephone;
    editModal.querySelector('[name="role"]').value = button.dataset.role;
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const tooltipTriggerList = [].slice.call(
        document.querySelectorAll('[data-bs-toggle="tooltip"]')
    );
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
});
</script>
</div>
</div>
