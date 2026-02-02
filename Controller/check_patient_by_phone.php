<?php
require_once '../Modele/database.php';
$db = getConnection();

$phone = $_GET['phone'] ?? '';
$phone = preg_replace('/\D+/', '', $phone);

if (!$phone) {
    echo json_encode(['status' => 'empty']);
    exit;
}

/* ============================
   1. PRIORITÉ : patient (avec index)
============================ */
$stmt = $db->prepare("
    SELECT 
        numeroDossierPatient,
        prenomPatient,
        nomPatient,
        telephonePatient
    FROM patient
    WHERE telephonePatient LIKE ?
    LIMIT 1
");
$stmt->execute(["%$phone%"]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if ($patient) {
    echo json_encode([
        'status' => 'patient',
        'data' => $patient
    ]);
    exit;
}

/* ============================
   2. patient sans index
============================ */
$stmt = $db->prepare("
    SELECT *
    FROM patientnoindex
    WHERE telephonePatient LIKE ?
    ORDER BY numeroAuto DESC
    LIMIT 1
");
$stmt->execute(["%$phone%"]);
$noIndex = $stmt->fetch(PDO::FETCH_ASSOC);

if ($noIndex) {
    echo json_encode([
        'status' => 'noindex',
        'data' => $noIndex
    ]);
    exit;
}

/* ============================
   3. Aucun patient
============================ */
echo json_encode(['status' => 'not_found']);
