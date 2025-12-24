<?php
require_once "../modele/database.php";
$db = getConnection();

/* =========================
   FILTRES
========================= */
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

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
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
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
                <th>Service</th>
                <th>Jours de RDV</th>
                <th>Max RDV / jour</th>
                <th>Statut</th>
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
<tr>
    <td><?= htmlspecialchars($service['designService']) ?></td>

    <td>
    <?= $service['jours_rdv'] 
        ? htmlspecialchars($service['jours_rdv']) 
        : '<span class="text-muted">Non configuré</span>' ?>
</td>



    <td><?= $service['max_rdv_jour'] ?></td>

    <td>
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
        >
            <i class="bi bi-pencil"></i>
        </button>
        <form method="POST" class="d-inline"
      onsubmit="return confirm('Supprimer définitivement ce service ?');">
    <input type="hidden" name="codeService" value="<?= $service['codeService'] ?>">
    <button type="submit" name="delete_service"
            class="btn btn-sm btn-danger">
        <i class="bi bi-trash"></i>
    </button>
</form>

    </td>
</tr>

<!-- MODAL MODIFIER -->
<div class="modal fade" id="editServiceModal<?= $service['codeService'] ?>" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="codeService" value="<?= $service['codeService'] ?>">

        <div class="modal-header">
          <h5 class="modal-title">Modifier le service</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          
        </div>
        

        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-12">
              <label class="form-label">Nom du service</label>
              <input
                type="text"
                name="designService"
                class="form-control"
                value="<?= htmlspecialchars($service['designService']) ?>"
                required
              >
            </div>

            <div class="col-md-6">
              <label class="form-label">Max RDV / jour</label>
              <select name="max_rdv_jour" class="form-select">
                <?php foreach ([10,15,20,30] as $val): ?>
                  <option value="<?= $val ?>" <?= ($service['max_rdv_jour'] == $val) ? 'selected' : '' ?>>
                    <?= $val ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

           <?php
$stmtJours->execute([$service['codeService']]);
$serviceJours = $stmtJours->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="col-md-12">
  <label class="form-label">Jours de RDV</label>
  <div class="d-flex flex-wrap gap-3">
    <?php foreach (['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'] as $jour): ?>
      <div class="form-check">
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



            <div class="col-md-6">
              <label class="form-label">Statut</label>
              <select name="is_active" class="form-select">
                <option value="1" <?= $service['is_active'] == 1 ? 'selected' : '' ?>>Actif</option>
                <option value="0" <?= $service['is_active'] == 0 ? 'selected' : '' ?>>Inactif</option>
              </select>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="update_service" class="btn btn-primary">
            Enregistrer
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Annuler
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endforeach; ?>



        </tbody>
    </table>
</div>

<!-- =========================
    MODAL AJOUT SERVICE
========================= -->
<div class="modal fade" id="addServiceModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Ajouter un service</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <div class="col-md-12">
              <label class="form-label">Nom du service</label>
              <input type="text" name="designService" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Max RDV / jour</label>
              <select name="max_rdv_jour" class="form-select">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
                <option value="30">30</option>
              </select>
            </div>

            <?php
$stmtJours->execute([$service['codeService']]);
$serviceJours = $stmtJours->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="col-md-12">
  <label class="form-label">Jours de RDV</label>
  <div class="d-flex flex-wrap gap-3">
    <?php foreach (['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'] as $jour): ?>
      <div class="form-check">
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



            <div class="col-md-6">
              <label class="form-label">Statut</label>
              <select name="is_active" class="form-select">
                <option value="1">Actif</option>
                <option value="0">Inactif</option>
              </select>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="submit" name="add_service" class="btn btn-success">
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


