<?php
require_once '../Modele/database.php';
header('Content-Type: application/json');

$db = getConnection();

$service = $_GET['service'] ?? '';
$year    = (int)($_GET['year'] ?? date('Y'));
$month   = (int)($_GET['month'] ?? date('m'));

if (!$service) {
    echo json_encode([]);
    exit;
}

/* =========================
   CONFIG SERVICE
========================= */
$configStmt = $db->prepare("
    SELECT max_rdv_jour
    FROM service_config
    WHERE codeService = ? AND is_active = 1
");
$configStmt->execute([$service]);
$config = $configStmt->fetch(PDO::FETCH_ASSOC);
$MAX = $config ? (int)$config['max_rdv_jour'] : 0;

/* =========================
   JOURS AUTORISÉS
========================= */
$joursStmt = $db->prepare("
    SELECT jour FROM service_jour WHERE codeService = ?
");
$joursStmt->execute([$service]);
$joursFR = $joursStmt->fetchAll(PDO::FETCH_COLUMN);

$map = [
    'lundi'=>'monday','mardi'=>'tuesday','mercredi'=>'wednesday',
    'jeudi'=>'thursday','vendredi'=>'friday','samedi'=>'saturday','dimanche'=>'sunday'
];

$joursEN = array_map(fn($j)=>$map[strtolower($j)] ?? '', $joursFR);

/* =========================
   RDV PAR JOUR
========================= */
$rdvStmt = $db->prepare("
    SELECT date, SUM(total) total FROM (
        SELECT dateRvServ AS date, COUNT(*) total
        FROM rendezvs
        WHERE codeService=? AND YEAR(dateRvServ)=? AND MONTH(dateRvServ)=?
        GROUP BY dateRvServ

        UNION ALL

        SELECT dateDisponible AS date, COUNT(*) total
        FROM patientnoindex
        WHERE codeService=? AND YEAR(dateDisponible)=? AND MONTH(dateDisponible)=?
        GROUP BY dateDisponible
    ) t GROUP BY date
");
$rdvStmt->execute([$service,$year,$month,$service,$year,$month]);
$rdvs = $rdvStmt->fetchAll(PDO::FETCH_KEY_PAIR);

/* =========================
   JOURS FÉRIÉS
========================= */
$ferieStmt = $db->prepare("
    SELECT date_ferie, libelle
    FROM jours_feries
    WHERE YEAR(date_ferie)=? AND MONTH(date_ferie)=?
");
$ferieStmt->execute([$year,$month]);
$feries = $ferieStmt->fetchAll(PDO::FETCH_KEY_PAIR);

/* =========================
   CALENDRIER
========================= */
$daysInMonth = cal_days_in_month(CAL_GREGORIAN,$month,$year);
$today = date('Y-m-d');
$data = [];

for ($d=1; $d<=$daysInMonth; $d++) {

    $date = sprintf('%04d-%02d-%02d',$year,$month,$d);
    $jourEN = strtolower(date('l', strtotime($date)));

    if ($date < $today) {
        $data[$date] = ['status'=>'disabled','label'=>'Date passée'];
        continue;
    }

    if (!in_array($jourEN,$joursEN)) {
        $data[$date] = ['status'=>'disabled','label'=>'Service indisponible'];
        continue;
    }

    if (isset($feries[$date])) {
        $data[$date] = ['status'=>'ferie','label'=>$feries[$date]];
        continue;
    }

    $count = $rdvs[$date] ?? 0;

    if ($MAX && $count >= $MAX) {
        $status = 'plein';
    } elseif ($MAX && $count >= ceil($MAX/2)) {
        $status = 'moyen';
    } else {
        $status = 'disponible';
    }

    $data[$date] = ['status'=>$status,'count'=>$count];
}

echo json_encode($data);
