<?php
require_once __DIR__ . '/database.php';

function searchPatientByIndex(string $index) {
    $db = getConnection();
    $stmt = $db->prepare("
        SELECT * FROM patient
        WHERE numeroDossierPatient = :index
        LIMIT 1
    ");
    $stmt->execute(['index' => $index]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function searchPatientByPhone(string $phone)
{
    $db = getConnection();
    $clean = preg_replace('/\D/', '', $phone);

    if (strlen($clean) < 9) return null;

    $stmt = $db->prepare("
        SELECT *
        FROM patient
        WHERE RIGHT(REPLACE(REPLACE(telephonePatient,'+',''),' ',''),9)
              = RIGHT(:phone,9)
        LIMIT 1
    ");

    $stmt->execute(['phone' => $clean]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


function updatePatient($numero, $prenom, $nom, $telephone) {
    $db = getConnection();
    $stmt = $db->prepare("
        UPDATE patient
        SET prenomPatient = :prenom,
            nomPatient = :nom,
            telephonePatient = :telephone
        WHERE numeroDossierPatient = :numero
    ");
    return $stmt->execute(compact('prenom','nom','telephone','numero'));
}

function getPatientRdvs(string $numero) {
    $db = getConnection();
    $stmt = $db->prepare("
        SELECT r.idRv, r.dateRvServ, s.designService
        FROM rendezvs r
        JOIN service s ON s.codeService = r.codeService
        WHERE r.numeroDossierPatient = :numero
        ORDER BY r.dateRvServ DESC
    ");
    $stmt->execute(['numero' => $numero]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function countPatientRdvs(string $numero): int {
    $db = getConnection();
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM rendezvs
        WHERE numeroDossierPatient = :numero
    ");
    $stmt->execute(['numero' => $numero]);
    return (int)$stmt->fetchColumn();
}

function getPatientFull(string $numero)
{
    $db = getConnection();

    $stmt = $db->prepare("
        SELECT *
        FROM patient
        WHERE numeroDossierPatient = :numero
        LIMIT 1
    ");

    $stmt->execute(['numero' => $numero]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updatePatientFull(array $data)
{
    $db = getConnection();

    $stmt = $db->prepare("
        UPDATE patient SET
            prenomPatient = :prenomPatient,
            nomPatient = :nomPatient,
            sexe = :sexe,
            age = :age,
            email = :email,
            nationalite = :nationalite,
            groupeSanguin = :groupeSanguin,
            identiteOfficielle = :identiteOfficielle,
            telephonePatient = :telephonePatient,
            adresse = :adresse,
            urgenceNom = :urgenceNom,
            urgenceTelephone = :urgenceTelephone
        WHERE numeroDossierPatient = :numeroDossierPatient
    ");

    return $stmt->execute([
        'prenomPatient'      => $data['prenomPatient'],
        'nomPatient'         => $data['nomPatient'],
        'sexe'               => $data['sexe'],
        'age'                => $data['age'],
        'email'              => $data['email'],
        'nationalite'        => $data['nationalite'],
        'groupeSanguin'      => $data['groupeSanguin'],
        'identiteOfficielle' => $data['identiteOfficielle'],
        'telephonePatient'   => preg_replace('/\D/', '', $data['telephonePatient']),
        'adresse'            => $data['adresse'],
        'urgenceNom'         => $data['urgenceNom'],
        'urgenceTelephone'   => preg_replace('/\D/', '', $data['urgenceTelephone']),
        'numeroDossierPatient'=> $data['numeroDossierPatient']
    ]);
}

