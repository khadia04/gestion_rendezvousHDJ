<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../Modele/database.php';
require_once '../Modele/databaseAgent.php';

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
    $dateDemande = date('Y-m-d');

    if (!$patientType || !$codeService || !$dateRvServ) {
        throw new Exception("Données obligatoires manquantes.");
    }

    /* =========================
       NETTOYAGE SÉCURITÉ
       (chiffres uniquement)
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
       SÉCURITÉ : SERVICE AGENT
    ========================= */
    $role     = $_SESSION['role'] ?? '';
    $username = $_SESSION['username'] ?? '';

    if ($role === 'agent') {

        $checkService = $db->prepare("
            SELECT 1
            FROM agent_service
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

        /* 🟡 Patient avec index mais nouveau sur la plateforme */
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

        // RDV
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

        // Insertion patientnoindex
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

        // Historique
        $hist = $db->prepare("
            INSERT INTO rendezvs_history
            (numeroDossierPatient, codeService, dateDemande, dateRvServ, typePatient, telephonePatient, sourceTable)
            VALUES (NULL, ?, ?, ?, 'noindex', ?, 'patientnoindex')
        ");
        $hist->execute([
            $codeService,
            $dateDemande,
            $dateRvServ,
            $telephone
        ]);
    }

    /* =========================
       LOG & COMMIT
    ========================= */
    $log = $db->prepare("
        INSERT INTO agent_logs (agent_username, action, details)
        VALUES (?, ?, ?)
    ");
    $log->execute([
        $_SESSION['username'],
        'CREATION_RDV',
        "Service: $codeService | Date: $dateRvServ"
    ]);

    $db->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'Rendez-vous enregistré avec succès'
    ]);
    exit;

} catch (Exception $e) {

    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    $message = $e->getMessage();

    // Erreur index unique
    if ($e instanceof PDOException && $e->getCode() === '23000') {
        $message = "Ce numéro de dossier est déjà attribué à un patient.";
    }

    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit;
}
