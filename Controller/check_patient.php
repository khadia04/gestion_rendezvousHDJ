<?php
require_once '../Modele/database.php';

header('Content-Type: application/json');

$db = getConnection();
$numero = $_GET['numero'] ?? '';

if (!$numero) {
    echo json_encode(['status' => 'error']);
    exit;
}

$stmt = $db->prepare("
    SELECT prenomPatient, nomPatient, telephonePatient
    FROM patient
    WHERE numeroDossierPatient = ?
");
$stmt->execute([$numero]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if ($patient) {
    echo json_encode([
        'status' => 'ok',
        'nom' => $patient['prenomPatient'].' '.$patient['nomPatient'],
        'tel' => $patient['telephonePatient']
    ]);
} else {
    echo json_encode(['status' => 'error']);
}
