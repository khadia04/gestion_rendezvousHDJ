    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header('Content-Type: application/json');
    require_once '../Modele/database.php';

    try {
        $db = getConnection();

        /* ===== CSRF ===== */
        if (
            empty($_POST['csrf_token']) ||
            $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')
        ) {
            throw new Exception('CSRF invalide');
        }

        /* ===== DONNÉES ===== */
        $numeroPatient = trim($_POST['numeroDossierPatient'] ?? '');
        $codeService   = trim($_POST['codeService'] ?? '');
        $dateRv        = trim($_POST['dateRvServ'] ?? '');

        if ($numeroPatient === '' || $codeService === '' || $dateRv === '') {
            throw new Exception('Champs manquants');
        }

        /* ===== PATIENT ===== */
        $p = $db->prepare("
            SELECT prenomPatient, nomPatient, telephonePatient
            FROM patient
            WHERE numeroDossierPatient = ?
        ");
        $p->execute([$numeroPatient]);
        $patient = $p->fetch(PDO::FETCH_ASSOC);

        if (!$patient) {
            throw new Exception('Patient introuvable');
        }

        /* ===== INSERT ===== */
        $stmt = $db->prepare("
            INSERT INTO rendezvs
            (numeroDossierPatient, codeService, dateDemande, dateRvServ)
            VALUES (?, ?, CURDATE(), ?)
        ");
        $stmt->execute([$numeroPatient, $codeService, $dateRv]);

        /* ===== SERVICE ===== */
        $s = $db->prepare("SELECT designService FROM service WHERE codeService = ?");
        $s->execute([$codeService]);
        $service = $s->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'patient'   => $patient['prenomPatient'].' '.$patient['nomPatient'],
                'dossier'   => $numeroPatient,
                'telephone' => $patient['telephonePatient'],
                'service'   => $service['designService'] ?? '',
                'date_rdv'  => date('d/m/Y', strtotime($dateRv))
            ]
        ]);
        exit;

    } catch (PDOException $e) {

        // Doublon SQL
        if ($e->getCode() === '23000') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Ce patient a déjà un rendez-vous pour ce service à cette date'
            ]);
            exit;
        }

        echo json_encode([
            'status' => 'error',
            'message' => 'Erreur base de données',
            'debug'   => $e->getMessage()
        ]);
        exit;

    } catch (Exception $e) {

        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    }
