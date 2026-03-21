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

function searchPatientNoIndexByPhone(string $phone)
{
    $db = getConnection();
    $clean = preg_replace('/\D/', '', $phone);

    if (strlen($clean) < 9) return null;

    $stmt = $db->prepare("
        SELECT *
        FROM patientnoindex
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
        SELECT 
            r.idRv,
            r.codeService,
            r.dateRvServ,
            s.designService,

            DATEDIFF(r.dateRvServ, CURDATE()) AS diff_jours,

            CASE
                WHEN r.dateRvServ = CURDATE() THEN 'programme_du_jour'
                WHEN r.dateRvServ < CURDATE() THEN 'depasse'
                ELSE 'en_attente'
            END AS statut

        FROM rendezvs r
        JOIN service s ON s.codeService = r.codeService
        WHERE r.numeroDossierPatient = :numero
        ORDER BY r.dateRvServ DESC
    ");

    $stmt->execute(['numero' => $numero]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getPatientNoIndexByNumeroAuto($numeroAuto)
{
    $db = getConnection();
    $stmt = $db->prepare("
        SELECT *
        FROM patientnoindex
        WHERE numeroAuto = :numeroAuto
        LIMIT 1
    ");
    $stmt->execute(['numeroAuto' => $numeroAuto]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
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

function updatePatientIndexed(array $data)
{
    $db = getConnection();
    $stmt = $db->prepare("
        UPDATE patient SET
            prenomPatient = :prenomPatient,
            nomPatient = :nomPatient,
            telephonePatient = :telephonePatient,
            sexe = :sexe,
            age = :age,
            email = :email,
            nationalite = :nationalite,
            groupeSanguin = :groupeSanguin,
            identiteOfficielle = :identiteOfficielle,
            adresse = :adresse,
            urgenceNom = :urgenceNom,
            urgenceTelephone = :urgenceTelephone
        WHERE numeroDossierPatient = :numeroDossierPatient
    ");

    return $stmt->execute($data);
}

function updatePatientNoIndex(array $data)
{
    $db = getConnection();
    $stmt = $db->prepare("
        UPDATE patientnoindex SET
            prenomPatient = :prenomPatient,
            nomPatient = :nomPatient,
            telephonePatient = :telephonePatient,
            sexe = :sexe,
            age = :age,
            email = :email,
            nationalite = :nationalite,
            adresse = :adresse,
            groupeSanguin = :groupeSanguin,
            identiteOfficielle = :identiteOfficielle,
            urgenceNom = :urgenceNom,
            urgenceTelephone = :urgenceTelephone
        WHERE numeroAuto = :numeroAuto
    ");

    return $stmt->execute($data);
}

