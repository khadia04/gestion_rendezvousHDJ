<?php
require_once '../Modele/database.php';

header('Content-Type: application/json');

try {
    $db = getConnection();

    $numero = trim($_GET['numero'] ?? '');

    if ($numero === '' || $numero === '0') {
        echo json_encode(['status' => 'error']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT prenomPatient, nomPatient, telephonePatient
        FROM patient
        WHERE numeroDossierPatient = ?
        LIMIT 1
    ");
    $stmt->execute([$numero]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    /* =========================
       PATIENT EXISTE
    ========================= */
    if ($patient) {
        echo json_encode([
            'status' => 'exists',
            'nom'    => $patient['prenomPatient'] . ' ' . $patient['nomPatient'],
            'tel'    => $patient['telephonePatient']
        ]);
        exit;
    }

    /* =========================
       PATIENT AVEC INDEX MAIS NON ENREGISTRÉ
    ========================= */
    echo json_encode([
        'status' => 'not_found'
    ]);
    exit;

} catch (Exception $e) {

    echo json_encode([
        'status'  => 'error',
        'message' => 'Erreur serveur'
    ]);
    exit;
}
