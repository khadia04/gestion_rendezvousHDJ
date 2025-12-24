<?php
require_once 'database.php';


function listServices ( ) {	

	//Requete permettant d'obtenir l'ensemble des services disponibles
	$sql="SELECT * FROM Service ORDER BY designService ASC" ;
    return executesql($sql);
}

function getServicebyCod ( $codeService ) {

	//Requete permettant d'obtenir un service à partir de son code
	$sql="SELECT * FROM Service WHERE codeService = '$codeService' " ;
    return executesql($sql);
}

function addpatientNoIndex ($prenoms, $nom, $telephone, $codeService, $dateDisponible ) {	

	//Requete permettant d'ajouter un rendez-vs pour un nouveau patient sans indentifiant
	$sql_prepare="INSERT INTO patientnoindex VALUES ( :nauto , :prenoms, :nom , :telephone , :codeService, NOW(), :dateDisponible )" ;
	$parameters = Array ( 'nauto' => NULL , 'prenoms' => $prenoms , 'nom' => $nom , 'telephone' => $telephone , 'codeService' => $codeService , 'dateDisponible' => $dateDisponible );
	return prepare_executesql($sql_prepare,$parameters);
}

function showdailyRvNoindex ( $service , $jour ) {
	//Requete permettant de lister un ensemble de rendez-Vs avec le couple [service-jour] Patient sans identifiant
	$sql="SELECT * FROM PatientNoindex WHERE codeService='$service' AND dateDisponible = '$jour' " ;
    return executesql($sql);
}

function countdailyRvNoIndex ( $service , $jour) {

	//Requete permettant de compter le nombre de rv service - jour (PatientNoIndex)
	$sql="SELECT count(*)
	FROM patientnoindex
	WHERE patientnoindex.dateDisponible = '$jour' AND patientnoindex.codeService = '$service' ";
    return executesql($sql);
}

function showdailyRv ( $service , $jour) {

	//Requete permettant de lister un ensemble de rendez-Vs avec le couple [service-jour] Avec Jointure interne - Patient avec identifiant
	$sql="SELECT patient.numeroDossierPatient , prenomPatient , nomPatient , telephonePatient FROM Patient 
	INNER JOIN rendezvs 
		ON patient.numeroDossierPatient = rendezvs.numeroDossierPatient 
	WHERE rendezvs.dateRvServ	= '$jour' AND rendezvs.codeService = '$service'
	ORDER BY numeroDossierPatient ASC" ;
    return executesql($sql);
}

function countdailyRv ( $service , $jour) {

	//Requete permettant de compter le nombre de rv service - jour (patient avec id) pour Mis a jour
	//Ne pas compter rendez vs patient en cour
	$sql="SELECT count(*)
	FROM rendezvs 
	WHERE rendezvs.dateRvServ = '$jour' AND rendezvs.codeService = '$service' ";
    return executesql($sql);
}

function countdailyRvUpdate ( $np, $service , $jour) {

	//Requete permettant de compter le nombre de rv service - jour (patient avec id) pour Mis a jour
	//Ne pas compter rendez vs patient en cour
	$sql="SELECT count(*)
	FROM rendezvs 
	WHERE rendezvs.dateRvServ = '$jour' AND rendezvs.codeService = '$service' AND rendezvs.numeroDossierPatient <> $np";
    return executesql($sql);
}

function showdailyRvReport ($date = null) {

    // Si aucun paramètre date n'est fourni, on utilise la date du jour
    if ($date === null) {
        $date = date('Y-m-d'); // Obtenir la date actuelle au format 'YYYY-MM-DD'
    }

	//Requete permettant de lister un ensemble de rendez-Vs offert pour un jour
	$sql="SELECT dateDemande , prenomPatient , nomPatient , designService , dateRvServ  FROM Patient p
	INNER JOIN rendezvs r
		ON p.numeroDossierPatient = r.numeroDossierPatient 
    INNER JOIN service s
		ON s.codeService = r.codeService
	WHERE r.dateDemande	= '$date'
	ORDER BY idRv DESC" ;
    return executesql($sql);
}

function showcontinueRvReport($dateDebut = null, $dateFin = null) {
    // Si aucun paramètre date n'est fourni, on utilise la date du jour
    if ($dateDebut === null || $dateFin === null) {
        $dateDebut = $dateFin = date('Y-m-d'); // Date du jour par défaut
    }

    // Requête pour compter le nombre de rendez-vous par service dans la période donnée
    $sql = "SELECT s.designService, COUNT(*) as totalRv
            FROM rendezvs r
            INNER JOIN service s ON s.codeService = r.codeService
            WHERE r.dateDemande BETWEEN '$dateDebut' AND '$dateFin'
            GROUP BY s.designService
            ORDER BY totalRv DESC";

    return executesql($sql);
}



function showdailyRvHistoReport ($date = null) {

    // Si aucun paramètre date n'est fourni, on utilise la date du jour
    if ($date === null) {
        $date = date('Y-m-d'); // Obtenir la date actuelle au format 'YYYY-MM-DD'
    }

	//Requete permettant de lister un ensemble de rendez-Vs offert pour un jour dans les historiques
	$sql="SELECT dateDemande , prenomPatient , nomPatient , designService , dateRvServ  FROM Patient p
	INNER JOIN rendezvs_history r
		ON p.numeroDossierPatient = r.numeroDossierPatient 
    INNER JOIN service s
		ON s.codeService = r.codeService
	WHERE r.dateDemande	= '$date'
	ORDER BY idRv DESC" ;
    return executesql($sql);
}

function showcontinueRvHistoReport($dateDebut = null, $dateFin = null) {
    // Si aucun paramètre date n'est fourni, on utilise la date du jour
    if ($dateDebut === null || $dateFin === null) {
        $dateDebut = $dateFin = date('Y-m-d'); // Date du jour par défaut
    }

    // Requête pour compter le nombre de rendez-vous historiques par service dans la période donnée
    $sql = "SELECT s.designService, COUNT(*) as totalRv
            FROM rendezvs_history r
            INNER JOIN service s ON s.codeService = r.codeService
            WHERE r.dateDemande BETWEEN '$dateDebut' AND '$dateFin'
            GROUP BY s.designService
            ORDER BY totalRv DESC";

    return executesql($sql);
}


function showdailyRvNoIndexReport ($date = null) {

    // Si aucun paramètre date n'est fourni, on utilise la date du jour
    if ($date === null) {
        $date = date('Y-m-d'); // Obtenir la date actuelle au format 'YYYY-MM-DD'
    }

	//Requete permettant de lister un ensemble de rendez-Vs offert pour un jour dans les patients sans identifiant
	$sql="SELECT dateDemande , prenomPatient , nomPatient , designService , dateDisponible  FROM Patientnoindex p
    INNER JOIN service s
		ON s.codeService = p.codeService
	WHERE p.dateDemande	= '$date'
	ORDER BY numeroAuto DESC" ;
    return executesql($sql);
}

function showcontinueRvNoIndexReport($dateDebut = null, $dateFin = null) {
    // Si aucun paramètre date n'est fourni, on utilise la date du jour
    if ($dateDebut === null || $dateFin === null) {
        $dateDebut = $dateFin = date('Y-m-d'); // Date du jour par défaut
    }

    // Requête pour compter le nombre de rendez-vous sans identifiant par service dans la période donnée
    $sql = "SELECT s.designService, COUNT(*) as totalRv
            FROM Patientnoindex p
            INNER JOIN service s ON s.codeService = p.codeService
            WHERE p.dateDemande BETWEEN '$dateDebut' AND '$dateFin'
            GROUP BY s.designService
            ORDER BY totalRv DESC";

    return executesql($sql);
}




function addRv($codeService, $dateDisponible) {
    
    // Requête d'enregistrement d'un rendez-vous pour un patient avec un identifiant pour un service
    if (isset($_SESSION['np'])) {

        $ndp = $_SESSION['np'];
        
        // Insertion d'un nouveau rendez-vous avec les nouvelles colonnes (idRv est auto-incrémenté)
        $sql_prepare = "INSERT INTO rendezvs (numeroDossierPatient, codeService, dateRvServ, dateDemande) 
                        VALUES (:np, :codeService, :dateDisponible, NOW())";
        $parameters = array(
            'np' => $ndp,
            'codeService' => $codeService,
            'dateDisponible' => $dateDisponible
        );      
        return prepare_executesql($sql_prepare, $parameters);
    }
}

function showRv ( ) {	

	if (isset($_SESSION['np'])) {

		//Requete permettant de selectionner tous les rendez vs enregistrés pour un patient
		$ndp=$_SESSION['np'] ;
		$sql = "SELECT r.codeService, MAX(r.dateRvServ) AS dateRvServ, s.designService
        FROM rendezvs r
        INNER JOIN Service s ON r.codeService = s.codeService
        WHERE r.numeroDossierPatient = $ndp
        GROUP BY r.codeService, s.designService
        ORDER BY dateRvServ ASC";
	    return executesql($sql);
	}
}

function showRvHisto ( ) {	

	if (isset($_SESSION['np'])) {

		//Requete permettant de selectionner tous les rendez vs enregistrés pour un patient
		$ndp=$_SESSION['np'] ;
		$sql="SELECT * FROM rendezvs_History r
		INNER JOIN Service s
			ON r.codeService = s.codeService
		WHERE r.numeroDossierPatient = $ndp 
		ORDER BY dateRvServ ASC";
	    return executesql($sql);
	}
}

function showRvProcess ( ) {
	
	//Requete permettant d'afficher les rendez vs enregistrés pour un patient
	if (isset($_SESSION['np'])) {
		
		$ndp=$_SESSION['np'] ;
		$sql="SELECT idRv, service.codeService , designService , dateRvServ  FROM rendezvs 
		INNER JOIN Service 
		ON rendezvs.codeService = Service.codeService
		WHERE rendezvs.numeroDossierPatient = $ndp 
		ORDER BY dateRvServ ASC" ;
    	return executesql($sql);
	}
}

function updateRv($idRv, $codeService, $jour) {
    if (isset($_SESSION['np'])) {	
        $ndp = $_SESSION['np'];

        // Obtenir la date actuelle sans l'heure
        $currentDate = new DateTime();
        $currentDate->setTime(0, 0); // Ignorer l'heure

        // Date soumise dans le formulaire (sans heure)
        $newDate = new DateTime($jour);
        $newDate->setTime(0, 0); // Ignorer l'heure

        // Requête pour vérifier la date actuelle du rendez-vous
        $sql_check = "SELECT dateRvServ, numeroDossierPatient FROM rendezvs WHERE idRv = :idRv";
        $parameters = array('idRv' => $idRv);
        $stmt = prepare_executesql($sql_check, $parameters);
        $data = $stmt->fetch();

        if ($data) {
            $oldDate = new DateTime($data['dateRvServ']);
            $oldDate->setTime(0, 0); // Ignorer l'heure

            // Vérifier si $jour est dans le passé ou aujourd'hui
            if ($newDate <= $currentDate) {
                // Ne rien faire si la nouvelle date est dans le passé ou aujourd'hui
                return true;
            }

            // Si $jour est dans le futur
            if ($newDate > $currentDate) {
                // Si le rendez-vous actuel est dans le futur et que la date a changé, mise à jour
                if ($oldDate > $currentDate && $oldDate != $newDate) {
                    $sql = "UPDATE rendezvs SET dateRvServ = :jour, dateDemande = NOW() WHERE idRv = :idRv";
                    $parameters = array('jour' => $jour, 'idRv' => $idRv);
                    return prepare_executesql($sql, $parameters);
                } else if ($oldDate <= $currentDate) {
                    // Si le rendez-vous actuel est dans le passé, le déplacer dans l'historique
                    $sql_insert_history = "INSERT INTO rendezvs_history VALUES ( NULL , :idRv, :numeroDossierPatient, :codeService, :dateRvServ, NOW())";
                    $parameters_history = array(
                        'idRv' => $idRv,
                        'numeroDossierPatient' => $ndp,
                        'codeService' => $codeService,
                        'dateRvServ' => $oldDate->format('Y-m-d') // Sauvegarder la date actuelle
                    );
                    prepare_executesql($sql_insert_history, $parameters_history);

                    // Ensuite, mettre à jour le rendez-vous avec la nouvelle date
                    $sql_update = "UPDATE rendezvs SET dateRvServ = :jour, dateDemande = NOW() WHERE idRv = :idRv";
                    $parameters_update = array('jour' => $jour, 'idRv' => $idRv);
                    return prepare_executesql($sql_update, $parameters_update);
                }
            }
        }
    }

    return true; // Si aucune condition ne s'applique
}

function getRdvPerMonth($annee) {

    $sql = "
        SELECT 
            MONTH(dateDemande) AS mois,
            COUNT(*) AS total
        FROM rendezvs
        WHERE YEAR(dateDemande) = YEAR(CURDATE())
        GROUP BY MONTH(dateDemande)
        ORDER BY MONTH(dateDemande)
    ";

    return executesql($sql)->fetchAll();
}



function getRdvPerService($mois = null, $annee = null) {

    $sql = "
        SELECT 
            r.codeService,
            s.designService,
            COUNT(*) AS total
        FROM rendezvs r
        INNER JOIN service s ON s.codeService = r.codeService
        WHERE 1=1
    ";

    $params = [];

    if (!empty($annee)) {
        $sql .= " AND YEAR(r.dateDemande) = :annee";
        $params['annee'] = (int) $annee;
    }

    if (!empty($mois)) {
        $sql .= " AND MONTH(r.dateDemande) = :mois";
        $params['mois'] = (int) $mois;
    }

    $sql .= "
        GROUP BY r.codeService, s.designService
        ORDER BY total DESC
    ";

    return prepare_executeSQL($sql, $params)->fetchAll();
}




function getRdvCountByMonth($mois, $annee) {

    $sql = "
        SELECT COUNT(*) AS total
        FROM rendezvs
        WHERE MONTH(dateDemande) = :mois
          AND YEAR(dateDemande)  = :annee
    ";

    $params = [
        'mois'  => $mois,
        'annee' => $annee
    ];

    $stmt = prepare_executesql($sql, $params);
    $row = $stmt->fetch();

    return $row ? (int)$row['total'] : 0;
}


function getRdvCountByYear($annee) {
    $sql = "
        SELECT COUNT(*) AS total
        FROM rendezvs
        WHERE YEAR(dateDemande) = :annee
    ";

    $stmt = prepare_executesql($sql, ['annee' => $annee]);
    $row = $stmt->fetch();

    return (int) $row['total'];
}






?>