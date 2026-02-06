<?php
require_once '../Modele/databasePatient.php';
header('Content-Type: application/json');

$action = $_REQUEST['action'] ?? null;

if (!$action) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Action manquante'
    ]);
    exit;
}

switch ($action) {

    /* =========================
       🔍 RECHERCHE PATIENT
    ========================= */
    case 'search':

        $index = trim($_GET['index'] ?? '');
        $phone = trim($_GET['phone'] ?? '');

        if ($index !== '') {
            $patient = searchPatientByIndex($index);
        } elseif ($phone !== '') {
            $patient = searchPatientByPhone($phone);
        } else {
            echo json_encode(['status' => 'error']);
            exit;
        }

        if (!$patient) {
            echo json_encode(['status' => 'not_found']);
            exit;
        }

        echo json_encode([
            'status'    => 'success',
            'patient'   => $patient,
            'rdvsCount' => countPatientRdvs($patient['numeroDossierPatient'])
        ]);
        exit;


    /* =========================
       📄 GET PATIENT (MODAL)
    ========================= */
    case 'get':

    $numero = $_GET['numero'] ?? null;
    $phone  = $_GET['phone'] ?? null;

    if ($numero) {
        $patient = getPatientFull($numero);
    } elseif ($phone) {
        $patient = searchPatientByPhone($phone);
    } else {
        echo json_encode(['status' => 'error']);
        exit;
    }

    echo json_encode([
        'status'  => $patient ? 'success' : 'not_found',
        'patient' => $patient
    ]);
    exit;


    /* =========================
       💾 SAVE (UPDATE PATIENT)
    ========================= */
    case 'save':

    if (empty($_POST['numeroDossierPatient'])) {
        echo json_encode(['status' => 'error']);
        exit;
    }

    $ok = updatePatientFull([
        'numeroDossierPatient' => $_POST['numeroDossierPatient'],
        'prenomPatient'        => $_POST['prenomPatient'] ?? null,
        'nomPatient'           => $_POST['nomPatient'] ?? null,
        'sexe'                 => $_POST['sexe'] ?? null,
        'age'                  => $_POST['age'] ?? null,
        'email'                => $_POST['email'] ?? null,
        'nationalite'          => $_POST['nationalite'] ?? null,
        'groupeSanguin'        => $_POST['groupeSanguin'] ?? null,
        'identiteOfficielle'   => $_POST['identiteOfficielle'] ?? null,
        'telephonePatient'     => $_POST['telephonePatient'] ?? null,
        'adresse'              => $_POST['adresse'] ?? null,
        'urgenceNom'           => $_POST['urgenceNom'] ?: null,
        'urgenceTelephone'     => $_POST['urgenceTelephone'] ?: null,
    ]);

    echo json_encode(['status' => $ok ? 'success' : 'error']);
    exit;


}
