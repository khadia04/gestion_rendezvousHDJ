<?php
require_once '../modele/database.php';

$db = getConnection();

$search = $_GET['q'] ?? '';

$sql = "
    SELECT s.designService, COUNT(r.codeService) as totalRdv
    FROM service s
    LEFT JOIN rendezvs r ON r.codeService = s.codeService
";

$params = [];

if (!empty($search)) {
    $sql .= " WHERE s.designService LIKE :search ";
    $params['search'] = "%$search%";
}

$sql .= " GROUP BY s.codeService ORDER BY s.designService ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// fonction image
function getImageName($name) {
    $name = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9]/', '-', $name);
    $name = preg_replace('/-+/', '-', $name);
    return trim($name, '-');
}

foreach ($services as $s) {

    $img = getImageName($s['designService']);

    echo '
    <div class="col-md-4">
        <div class="service-card-image modal-card">

            <img src="/rendezvous/assets/img/services/'.$img.'.jpg"
                 onerror="this.src=\'/rendezvous/assets/img/services/default.jpg\'">

            <div class="overlay-main">
                <h6>'.$s['designService'].'</h6>
                <p>'.$s['totalRdv'].' RDV / mois</p>
            </div>

            <div class="overlay-hover">
                <h6>'.$s['designService'].'</h6>
                <p>Service médical avec suivi et prise en charge optimale.</p>
            </div>

        </div>
    </div>';
}

if (empty($services)) {
    echo "<p class='text-center'>Aucun service trouvé</p>";
    exit;
}