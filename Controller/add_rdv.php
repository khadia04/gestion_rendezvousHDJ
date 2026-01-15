<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../Modele/database.php';

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
    $codeService = $_POST['codeService'] ?? '';
    $dateRvServ  = $_POST['dateRvServ'] ?? '';
    $dateDemande = date('Y-m-d');

    if (!$patientType || !$codeService || !$dateRvServ) {
        throw new Exception("Données obligatoires manquantes.");
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

        if (!$check->fetch()) {
            throw new Exception("Patient introuvable.");
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
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
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

        // Historique SANS numeroDossier
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

    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
