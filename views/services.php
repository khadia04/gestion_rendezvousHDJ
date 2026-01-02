<?php
require_once '../middlewares/auth.php';
require_once '../middlewares/csrf.php';
require_once '../modele/database.php';

requireAuth('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
}

$db = getConnection();


/* =========================
   FILTRES
========================= */
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';


/* =========================
  Pagination
========================= */
$limit = 7;
$pageNum = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($pageNum - 1) * $limit;

$countSql = "
    SELECT COUNT(DISTINCT s.codeService)
    FROM service s
    LEFT JOIN service_config sc ON sc.codeService = s.codeService
    WHERE s.designService LIKE :search
";

$countParams = ['search' => "%$search%"];

if ($status !== '') {
    $countSql .= " AND (
        sc.is_active = :status
        OR (sc.is_active IS NULL AND :status_null = 0)
    )";
    $countParams['status'] = $status;
    $countParams['status_null'] = $status;
}

$stmtCount = $db->prepare($countSql);
$stmtCount->execute($countParams);
$totalServices = $stmtCount->fetchColumn();

$totalPages = ceil($totalServices / $limit);



/* =========================
   AJOUT SERVICE
========================= */
if (isset($_POST['add_service'])) {

    $designService = strtoupper(trim($_POST['designService']));
    $max_rdv_jour  = $_POST['max_rdv_jour'];
    $is_active     = $_POST['is_active'];
    $jours         = $_POST['jours'] ?? [];

    $codeService = substr(strtolower(preg_replace('/[^a-zA-Z]/', '', $designService)), 0, 6);

    try {
        $db->beginTransaction();

        $db->prepare("
            INSERT INTO service (codeService, designService)
            VALUES (?, ?)
        ")->execute([$codeService, $designService]);

        $db->prepare("
            INSERT INTO service_config (codeService, max_rdv_jour, is_active)
            VALUES (?, ?, ?)
        ")->execute([$codeService, $max_rdv_jour, $is_active]);

        if (!empty($jours)) {
            $stmtJour = $db->prepare("
                INSERT INTO service_jour (codeService, jour)
                VALUES (?, ?)
            ");
            foreach ($jours as $jour) {
                $stmtJour->execute([$codeService, $jour]);
            }
        }

        $db->commit();
        header("Location: admin.php?page=services");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        echo "<div class='alert alert-danger'>{$e->getMessage()}</div>";
    }
}

/* =========================
   UPDATE SERVICE
========================= */
if (isset($_POST['update_service'])) {

    $codeService   = $_POST['codeService'];
    $designService = strtoupper(trim($_POST['designService']));
    $max_rdv_jour  = $_POST['max_rdv_jour'];
    $is_active     = $_POST['is_active'];
    $jours         = $_POST['jours'] ?? [];

    try {
        $db->beginTransaction();

        $db->prepare("
            UPDATE service SET designService = ?
            WHERE codeService = ?
        ")->execute([$designService, $codeService]);

        $db->prepare("
            INSERT INTO service_config (codeService, max_rdv_jour, is_active)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
            max_rdv_jour = VALUES(max_rdv_jour),
            is_active = VALUES(is_active)
        ")->execute([$codeService, $max_rdv_jour, $is_active]);


        $db->prepare("
            DELETE FROM service_jour WHERE codeService = ?
        ")->execute([$codeService]);

        if (!empty($jours)) {
            $stmtJour = $db->prepare("
                INSERT INTO service_jour (codeService, jour)
                VALUES (?, ?)
            ");
            foreach ($jours as $jour) {
                $stmtJour->execute([$codeService, $jour]);
            }
        }

        $db->commit();
        header("Location: admin.php?page=services");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        echo "<div class='alert alert-danger'>{$e->getMessage()}</div>";
    }
}

/* =========================
   SUPPRESSION SERVICE
========================= */
if (isset($_POST['delete_service'])) {

    $codeService = $_POST['codeService'];

    try {
        $db->beginTransaction();

        // Supprimer jours
        $stmt = $db->prepare("DELETE FROM service_jour WHERE codeService = ?");
        $stmt->execute([$codeService]);

        // Supprimer config
        $stmt = $db->prepare("DELETE FROM service_config WHERE codeService = ?");
        $stmt->execute([$codeService]);

        // Supprimer service
        $stmt = $db->prepare("DELETE FROM service WHERE codeService = ?");
        $stmt->execute([$codeService]);

        $db->commit();
        header("Location: admin.php?page=services");
        exit;

    } catch (Exception $e) {
        $db->rollBack();
        echo "<div class='alert alert-danger'>Suppression impossible</div>";
    }
}


/* =========================
   LISTE SERVICES
========================= */
$sql = "
    SELECT 
        s.codeService,
        s.designService,
        sc.max_rdv_jour,
        sc.is_active,
        GROUP_CONCAT(DISTINCT sj.jour ORDER BY sj.jour SEPARATOR ', ') AS jours_rdv
    FROM service s
    LEFT JOIN service_config sc ON sc.codeService = s.codeService
    LEFT JOIN service_jour sj ON sj.codeService = s.codeService
    WHERE s.designService LIKE :search
";

$params = ['search' => "%$search%"];

if ($status !== '') {
    $sql .= " AND (
        sc.is_active = :status
        OR (sc.is_active IS NULL AND :status_null = 0)
    )";
    $params['status'] = $status;
    $params['status_null'] = $status;
}



$sql .= "
    GROUP BY s.codeService, s.designService, sc.max_rdv_jour, sc.is_active
    ORDER BY s.designService ASC
    LIMIT :limit OFFSET :offset
";


$stmt = $db->prepare($sql);

foreach ($params as $key => $value) {
    $stmt->bindValue(":$key", $value);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$services = $stmt->fetchAll();


/* Préparer récupération jours */
$stmtJours = $db->prepare("
    SELECT jour FROM service_jour WHERE codeService = ?
");


?>






<!-- =========================
    HEADER
========================= -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Liste des services</h4>

    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
        <i class="bi bi-plus-circle"></i> Ajouter un service
    </button>
</div>

<!-- =========================
    FILTRES
========================= -->
<form method="GET" class="row g-3 align-items-center mb-4">
    <input type="hidden" name="page" value="services">

    <div class="col-md-5">
        <input
            type="text"
            name="search"
            class="form-control"
            placeholder="Rechercher un service"
            value="<?= htmlspecialchars($search) ?>"
        >
    </div>

    <div class="col-md-4">
        <select name="status" class="form-select">
            <option value="">Tous les statuts</option>
            <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Actif</option>
            <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Inactif</option>
        </select>
    </div>

    <div class="col-md-1 d-grid">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search"></i>
        </button>
    </div>
</form>

<!-- =========================
    TABLE
========================= -->
<div class="table-responsive">
    <table class="table table-bordered align-middle">
        <thead class="table-primary">
            <tr>
                <th class="text-center">Service</th>
                <th class="text-center">Jours de RDV</th>
                <th class="text-center">Max RDV/jour</th>
                <th class="text-center">Statut</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>

        <?php if (empty($services)): ?>
            <tr>
                <td colspan="5" class="text-center text-muted">
                    Aucun service trouvé
                </td>
            </tr>
        <?php endif; ?>

       <?php foreach ($services as $service): ?>
        <?php
$stmtJours->execute([$service['codeService']]);
$serviceJours = $stmtJours->fetchAll(PDO::FETCH_COLUMN);
?>

<tr>
    <td><?= htmlspecialchars($service['designService']) ?></td>

    <td class="text-center">
    <?= $service['jours_rdv'] 
        ? htmlspecialchars($service['jours_rdv']) 
        : '<span class="text-muted">Non configuré</span>' ?>
</td>



    <td class="text-center"><?= $service['max_rdv_jour'] ?></td>

    <td class="text-center">
        <?php if ($service['is_active'] == 1): ?>
            <span class="badge bg-success">Actif</span>
        <?php else: ?>
            <span class="badge bg-secondary">Inactif</span>
        <?php endif; ?>
    </td>

    <td class="text-center">
        <button 
            class="btn btn-sm btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#editServiceModal<?= $service['codeService'] ?>"
            title="Modifier le service">
            <i class="bi bi-pencil"></i>
        </button>
        <form method="POST" class="d-inline"
      onsubmit="return confirm('Supprimer définitivement ce service ?');">
    <input type="hidden" name="codeService" value="<?= $service['codeService'] ?>">
    <button
  type="button"
  class="btn btn-sm btn-danger"
  data-bs-toggle="modal"
  data-bs-target="#deleteServiceModal"
  data-code="<?= $service['codeService'] ?>"
  title="Supprimer le service">
  <i class="bi bi-trash"></i>
</button>

</form>

    </td>
</tr>




<!-- MODAL MODIFIER -->
<div class="modal fade" id="editServiceModal<?= $service['codeService'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content service-modal">

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="codeService" value="<?= $service['codeService'] ?>">

        <!-- HEADER -->
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-pencil-square me-2"></i>
            Modifier le service
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body">
          <div class="row g-4">

            <div class="col-12">
              <label class="form-label fw-semibold">Nom du service</label>
              <input type="text"
                     name="designService"
                     class="form-control form-control-lg"
                     value="<?= htmlspecialchars($service['designService']) ?>"
                     required>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Max RDV / jour</label>
              <select name="max_rdv_jour" class="form-select form-select-lg">
                <?php foreach ([10,15,20,30] as $val): ?>
                  <option value="<?= $val ?>" <?= $service['max_rdv_jour'] == $val ? 'selected' : '' ?>>
                    <?= $val ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Statut</label>
              <select name="is_active" class="form-select form-select-lg">
                <option value="1" <?= $service['is_active'] == 1 ? 'selected' : '' ?>>Actif</option>
                <option value="0" <?= $service['is_active'] == 0 ? 'selected' : '' ?>>Inactif</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label fw-semibold">Jours de RDV</label>
              <div class="d-flex flex-wrap gap-2 service-days">
                <?php foreach (['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'] as $jour): ?>
                  <div class="form-check form-switch">
                    <input class="form-check-input"
                           type="checkbox"
                           name="jours[]"
                           value="<?= $jour ?>"
                           <?= in_array($jour, $serviceJours) ? 'checked' : '' ?>>
                    <label class="form-check-label"><?= $jour ?></label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

          </div>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer">
          <button type="submit" name="update_service" class="btn btn-primary btn-lg">
            <i class="bi bi-save me-1"></i> Enregistrer
          </button>
          <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal">
            Annuler
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

</div>
<?php endforeach; ?>



        </tbody>
    </table>
    <!-- Pagination -->
<?php if ($totalPages > 1): ?>
<nav class="mt-3">
  <ul class="pagination justify-content-center">

    <?php if ($pageNum > 1): ?>
      <li class="page-item">
        <a class="page-link"
           href="?page=services&p=<?= $pageNum-1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
          Précédent
        </a>
      </li>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <li class="page-item <?= $i == $pageNum ? 'active' : '' ?>">
        <a class="page-link"
           href="?page=services&p=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
          <?= $i ?>
        </a>
      </li>
    <?php endfor; ?>

    <?php if ($pageNum < $totalPages): ?>
      <li class="page-item">
        <a class="page-link"
           href="?page=services&p=<?= $pageNum+1 ?>&search=<?= urlencode($search) ?>&status=<?= $status ?>">
          Suivant
        </a>
      </li>
    <?php endif; ?>

  </ul>
</nav>
<?php endif; ?>

</div>
<!-- =========================
    MODAL SUPPRIMER SERVICE   
========================= -->
<div class="modal fade service-delete-modal" id="deleteServiceModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="codeService" id="deleteCodeService">

        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-trash"></i> Supprimer le service
          </h5>
        </div>

        <div class="modal-body">
          Voulez-vous vraiment supprimer ce service ?
        </div>

        <div class="modal-footer">
          <button type="submit" name="delete_service" class="btn btn-danger">
            Supprimer
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Annuler
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- =========================
    MODAL AJOUT SERVICE
========================= -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content service-modal">

      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- HEADER -->
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="bi bi-plus-circle me-2"></i>
            Ajouter un service
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body">
          <div class="row g-4">

            <!-- NOM -->
            <div class="col-12">
              <label class="form-label fw-semibold">Nom du service</label>
              <input type="text"
                     name="designService"
                     class="form-control form-control-lg"
                     placeholder="Ex : Dermatologie"
                     required>
            </div>

            <!-- MAX RDV -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Max RDV / jour</label>
              <select name="max_rdv_jour" class="form-select form-select-lg">
                <?php foreach ([10,15,20,30] as $val): ?>
                  <option value="<?= $val ?>"><?= $val ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- STATUT -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Statut</label>
              <select name="is_active" class="form-select form-select-lg">
                <option value="1">Actif</option>
                <option value="0">Inactif</option>
              </select>
            </div>

            <!-- JOURS -->
            <div class="col-12">
              <label class="form-label fw-semibold">Jours de RDV</label>
              <div class="d-flex flex-wrap gap-2 service-days">
                <?php foreach (['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'] as $jour): ?>
                  <div class="form-check form-switch">
                    <input class="form-check-input"
                           type="checkbox"
                           name="jours[]"
                           value="<?= $jour ?>">
                    <label class="form-check-label"><?= $jour ?></label>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

          </div>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer">
          <button type="submit" name="add_service" class="btn btn-primary btn-lg">
            <i class="bi bi-check-circle me-1"></i> Enregistrer
          </button>
          <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal">
            Annuler
          </button>
        </div>

      </form>
    </div>
  </div>
</div>


</div>


<script>
const deleteModal = document.getElementById('deleteServiceModal');
if (deleteModal) {
  deleteModal.addEventListener('show.bs.modal', e => {
    document.getElementById('deleteCodeService').value =
      e.relatedTarget.dataset.code;
  });
}

</script>
