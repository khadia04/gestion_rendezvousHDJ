<?php
require_once '../Modele/databasePatient.php';
require_once '../helpers/activity.php';
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
     RECHERCHE PATIENT
    ========================= */
    case 'search':

    $index = trim($_GET['index'] ?? '');
    $phone = trim($_GET['phone'] ?? '');

    if ($index !== '') {

        $patient = searchPatientByIndex($index);

    } elseif ($phone !== '') {

        //  chercher dans patient (avec index)
        $patient = searchPatientByPhone($phone);

        //  si rien trouvé → chercher dans patientnoindex
        if (!$patient) {
            $patient = searchPatientNoIndexByPhone($phone);
        }

    } else {

        echo json_encode(['status' => 'error']);
        exit;
    }

    if (!$patient) {
        echo json_encode(['status' => 'not_found']);
        exit;
    }

    $numero = $patient['numeroDossierPatient'] ?? null;

    echo json_encode([
        'status'    => 'success',
        'patient'   => $patient,
        'rdvsCount' => $numero ? countPatientRdvs($numero) : 0
    ]);

    exit;


    /* =========================
        GET PATIENT (MODAL)
    ========================= */
  case 'get':

    $numero     = $_GET['numero'] ?? null;
    $numeroAuto = $_GET['numeroAuto'] ?? null;
    $phone      = $_GET['phone'] ?? null;

    if ($numero) {

        $patient = getPatientFull($numero);

    } elseif ($numeroAuto) {

        $patient = getPatientNoIndexByNumeroAuto($numeroAuto);

    } elseif ($phone) {

        $patient = searchPatientByPhone($phone);

        if (!$patient) {
            $patient = searchPatientNoIndexByPhone($phone);
        }

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
        SAVE (UPDATE PATIENT)
    ========================= */
case 'save':

if (!empty($_POST['numeroDossierPatient'])) {

    // patient AVEC index
    $ok = updatePatientIndexed([
        'numeroDossierPatient' => $_POST['numeroDossierPatient'],
        'prenomPatient' => $_POST['prenomPatient'],
        'nomPatient' => $_POST['nomPatient'],
        'telephonePatient' => $_POST['telephonePatient'],
        'sexe' => $_POST['sexe'],
        'age' => $_POST['age'],
        'email' => $_POST['email'],
        'nationalite' => $_POST['nationalite'],
        'groupeSanguin' => $_POST['groupeSanguin'],
        'identiteOfficielle' => $_POST['identiteOfficielle'],
        'adresse' => $_POST['adresse'],
        'urgenceNom' => $_POST['urgenceNom'],
        'urgenceTelephone' => $_POST['urgenceTelephone'],
    ]);

    $patientRef = $_POST['numeroDossierPatient'];

} else {

    // patient SANS index
    $ok = updatePatientNoIndex([
        'numeroAuto' => $_POST['numeroAuto'],
        'prenomPatient' => $_POST['prenomPatient'],
        'nomPatient' => $_POST['nomPatient'],
        'telephonePatient' => $_POST['telephonePatient'],
        'sexe' => $_POST['sexe'],
        'age' => $_POST['age'],
        'email' => $_POST['email'],
        'nationalite' => $_POST['nationalite'],
        'adresse' => $_POST['adresse'],
        'groupeSanguin' => $_POST['groupeSanguin'],
        'identiteOfficielle' => $_POST['identiteOfficielle'],
        'urgenceNom' => $_POST['urgenceNom'],
        'urgenceTelephone' => $_POST['urgenceTelephone'],
    ]);

    $patientRef = $_POST['numeroAuto'];
}

/* LOG ACTIVITÉ */
if ($ok) {
    logActivity(
        $_SESSION['user_id'],
        "MODIFICATION_PATIENT",
        "Modification informations patient : $patientRef",
        $_SESSION['role']
    );
}

echo json_encode(['status' => $ok ? 'success' : 'error']);
exit;

/* =========================
   GET RDVS PATIENT
========================= */
case 'getRdvs':

    $numero = $_GET['numero'] ?? null;

    if (!$numero) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Numero patient manquant'
        ]);
        exit;
    }

    $rdvs = getPatientRdvs($numero);

    echo json_encode([
        'status' => 'success',
        'rdvs'   => $rdvs
    ]);
    exit;


}
