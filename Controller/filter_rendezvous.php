<?php
require_once '../Modele/database.php';
$db = getConnection();

$service = $_GET['service'] ?? '';
$periode = $_GET['periode'] ?? 'jour';
$date    = $_GET['date'] ?? date('Y-m-d');

$sql = "
SELECT
    r.numeroDossierPatient,
    CONCAT(p.prenomPatient,' ',p.nomPatient) AS patient,
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

$stmt = $db->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
