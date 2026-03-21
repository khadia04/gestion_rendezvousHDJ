<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../Modele/database.php';
require_once '../Modele/databaseAgent.php';
require_once '../helpers/activity.php';

try {

    /* =========================
       CSRF
    ========================= */
    if (
        empty($_POST['csrf_token']) ||
        $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')
    ) {
        throw new Exception("Session invalide. Veuillez recharger la page.");
    }

    $db = getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->beginTransaction();

    /* =========================
       DONNÉES COMMUNES
    ========================= */
    $patientType = $_POST['patient_type'] ?? '';
    $isNewIndex  = $_POST['is_new_index'] ?? '0';

    $codeService = $_POST['codeService'] ?? '';
    $dateRvServ  = $_POST['dateRvServ'] ?? '';
    $idRv = $_POST['idRv'] ?? null;
    $dateDemande = date('Y-m-d');

    if (!$patientType || !$codeService || !$dateRvServ) {
        throw new Exception("Données obligatoires manquantes.");
    }

    /* =========================
       NETTOYAGE SÉCURITÉ
    ========================= */
    if (isset($_POST['numeroDossierPatient'])) {
        $_POST['numeroDossierPatient'] = preg_replace('/\D+/', '', $_POST['numeroDossierPatient']);
    }

    if (isset($_POST['telephonePatient'])) {
        $_POST['telephonePatient'] = preg_replace('/\D+/', '', $_POST['telephonePatient']);
    }

    if (isset($_POST['urgenceTelephone'])) {
        $_POST['urgenceTelephone'] = preg_replace('/\D+/', '', $_POST['urgenceTelephone']);
    }

    /* =========================
       SÉCURITÉ SERVICE AGENT
    ========================= */
    $role     = $_SESSION['role'] ?? '';
    $username = $_SESSION['username'] ?? '';

    if ($role === 'agent') {

        $checkService = $db->prepare("
            SELECT 1 FROM agent_service
            WHERE agent_username = ?
            AND codeService = ?
            LIMIT 1
        ");
        $checkService->execute([$username, $codeService]);

        if (!$checkService->fetch()) {
            throw new Exception("Service non autorisé pour cet agent.");
        }
    }

    /* =====================================================
       PATIENT AVEC INDEX
    ===================================================== */
    if ($patientType === 'index') {

        $numeroDossier = trim($_POST['numeroDossierPatient'] ?? '');

        if (!$numeroDossier || $numeroDossier === '0') {
            throw new Exception("Numéro de dossier invalide.");
        }

        // Vérifier existence patient
        $check = $db->prepare("
            SELECT numeroDossierPatient
            FROM patient
            WHERE numeroDossierPatient = ?
        ");
        $check->execute([$numeroDossier]);

        $patientExiste = $check->fetch();

        /* Nouveau patient avec index */
        if (!$patientExiste && $isNewIndex === '1') {

            $prenom    = trim($_POST['prenomComplet'] ?? '');
            $nom       = trim($_POST['nom'] ?? '');
            $telephone = trim($_POST['telephonePatient'] ?? '');

            if (!$prenom || !$nom || !$telephone) {
                throw new Exception("Informations patient obligatoires.");
            }

            $insertPatient = $db->prepare("
                INSERT INTO patient (
                    numeroDossierPatient,
                    prenomPatient,
                    nomPatient,
                    telephonePatient,
                    sexe,
                    age,
                    nationalite,
                    email,
                    groupeSanguin,
                    identiteOfficielle,
                    adresse,
                    urgenceNom,
                    urgenceTelephone
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertPatient->execute([
                $numeroDossier,
                $prenom,
                $nom,
                $telephone,
                $_POST['sexe'] ?? null,
                $_POST['age'] ?? null,
                $_POST['nationalite'] ?? null,
                $_POST['emailPatient'] ?? null,
                $_POST['groupeSanguin'] ?? null,
                $_POST['identiteOfficielle'] ?? null,
                $_POST['adresse'] ?? null,
                $_POST['urgenceNom'] ?? null,
                $_POST['urgenceTelephone'] ?? null
            ]);
        }

        // Vérifier si un RDV existe déjà pour ce patient
        $checkDuplicate = $db->prepare("
            SELECT idRv
            FROM rendezvs
            WHERE numeroDossierPatient = ?
            AND codeService = ?
            AND dateRvServ = ?
        ");

        $checkDuplicate->execute([
            $numeroDossier,
            $codeService,
            $dateRvServ
        ]);

        $existing = $checkDuplicate->fetch();

        if ($existing && (!$idRv || $existing['idRv'] != $idRv)) {
            throw new Exception("Ce patient a déjà un rendez-vous dans ce service à cette date.");
        }


        // RDV (création ou modification)

        if ($idRv) {

            // vérifier existence RDV
            $checkRdv = $db->prepare("
                SELECT idRv
                FROM rendezvs
                WHERE idRv = ?
            ");
            $checkRdv->execute([$idRv]);

            if (!$checkRdv->fetch()) {
                throw new Exception("Rendez-vous introuvable.");
            }

            // modification RDV existant
            $stmt = $db->prepare("
                UPDATE rendezvs
                SET codeService = ?, dateRvServ = ?
                WHERE idRv = ?
            ");

            $stmt->execute([
                $codeService,
                $dateRvServ,
                $idRv
            ]);

            logActivity(
                $_SESSION['user_id'] ?? 0,
                "Modification de RDV",
                "Modification RDV ID $idRv service $codeService date $dateRvServ",
                $_SESSION['role'] ?? null
            );

        } else {

            // nouveau RDV
            $stmt = $db->prepare("
                INSERT INTO rendezvs
                (numeroDossierPatient, codeService, dateDemande, dateRvServ)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $numeroDossier,
                $codeService,
                $dateDemande,
                $dateRvServ
            ]);

            logActivity(
                $_SESSION['user_id'] ?? 0,
                "Creation de RDV",
                "Création RDV patient $numeroDossier service $codeService date $dateRvServ",
                $_SESSION['role'] ?? null
            );
        }

        // Historique
        $hist = $db->prepare("
            INSERT INTO rendezvs_history
            (numeroDossierPatient, codeService, dateDemande, dateRvServ, typePatient, sourceTable)
            VALUES (?, ?, ?, ?, 'index', 'rendezvs')
        ");
        $hist->execute([
            $numeroDossier,
            $codeService,
            $dateDemande,
            $dateRvServ
        ]);
    }

    /* =====================================================
       PATIENT SANS INDEX
    ===================================================== */
    else {

        $prenom    = trim($_POST['prenomComplet'] ?? '');
        $nom       = trim($_POST['nom'] ?? '');
        $telephone = trim($_POST['telephonePatient'] ?? '');

        if (!$prenom || !$nom || !$telephone) {
            throw new Exception("Nom, prénom et téléphone obligatoires.");
        }

        // Insert patientnoindex
        $stmt = $db->prepare("
            INSERT INTO patientnoindex (
                prenomPatient,
                nomPatient,
                telephonePatient,
                codeService,
                dateDemande,
                dateDisponible,
                sexe,
                age,
                nationalite,
                email,
                groupeSanguin,
                identiteOfficielle,
                adresse,
                urgenceNom,
                urgenceTelephone
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $prenom,
            $nom,
            $telephone,
            $codeService,
            $dateDemande,
            $dateRvServ,
            $_POST['sexe'] ?? null,
            $_POST['age'] ?? null,
            $_POST['nationalite'] ?? null,
            $_POST['emailPatient'] ?? null,
            $_POST['groupeSanguin'] ?? null,
            $_POST['identiteOfficielle'] ?? null,
            $_POST['adresse'] ?? null,
            $_POST['urgenceNom'] ?? null,
            $_POST['urgenceTelephone'] ?? null
        ]);

        $patientNoIndexId = $db->lastInsertId();

        // Historique noindex
        $hist = $db->prepare("
            INSERT INTO rendezvs_history
            (patientnoindex_id, codeService, dateDemande, dateRvServ, typePatient, telephonePatient, sourceTable)
            VALUES (?, ?, ?, ?, 'noindex', ?, 'patientnoindex')
        ");

        $hist->execute([
            $patientNoIndexId,
            $codeService,
            $dateDemande,
            $dateRvServ,
            $telephone
        ]);
    }

    /* =========================
       LOG
    ========================= */
    $log = $db->prepare("
        INSERT INTO agent_logs (agent_username, action, details)
        VALUES (?, ?, ?)
    ");

    $actionType = $idRv ? 'MODIFICATION_RDV' : 'CREATION_RDV';

    $log->execute([
        $_SESSION['username'],
        $actionType,
        "Service: $codeService | Date: $dateRvServ"
    ]);

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Rendez-vous enregistré avec succès'
    ]);
    exit;

} catch (Throwable $e) {

    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
