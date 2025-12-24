
<!DOCTYPE html>

<?php
	
	session_start();
    if ( !isset($_SESSION['username']) || (isset($_SESSION['lastAction']) and (time() - $_SESSION['timeframe']) > $_SESSION['lastAction'])) {
		header('Location:../views/logout.php');   // Fermeture de Session Forcee
		exit;
	}
	else {
  		$_SESSION['lastAction'] = time(); // Mise à jour de la variable derniere action
	}
	
?>
<html>
<head>
    <title>AJOUT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link type="text/css" href="../assets/css/forcontroller.css" rel = "stylesheet">
</head>

<body>
<?php

	require_once '../modele/database.php';
	require_once '../modele/databasePatient.php';
	require_once '../modele/databaseRv.php';


	if (isset($_POST['ajoutrv'])) { // Contrôle de traitement pour l'ajout d'un rv d'un patient

		try {
			// Vérifier le nombre total de rendez-vous (avec index)
			// 🔒 Vérifier si le service est actif
			$stmt = $db->prepare("
    			SELECT is_active 
    			FROM service_config 
    			WHERE codeService = ?
			");
			$stmt->execute([$_POST['servDemande']]);
			$isActive = $stmt->fetchColumn();

			if ($isActive != 1) {
    			header("Location:../views/managerendezvs.php?add=failed");
    		exit;
			}

			$nombrerv = countdailyRv($_POST['servDemande'], $_POST['dateDisponible']);
			$nbrv = 0;
			if ($data = $nombrerv->fetch()) {
				$nbrv += $data[0]; // Ajout des rv avec index
			}

			// 📅 Vérifier si le jour est autorisé pour ce service
			$jourSemaine = date('l', strtotime($_POST['dateDisponible']));

			$joursMap = [
    			'Monday'    => 'Lundi',
    			'Tuesday'   => 'Mardi',
    			'Wednesday' => 'Mercredi',
    			'Thursday'  => 'Jeudi',
    			'Friday'    => 'Vendredi',
   				'Saturday'  => 'Samedi'
			];

			$jourFr = $joursMap[$jourSemaine] ?? null;

			$stmt = $db->prepare("
    			SELECT COUNT(*) 
    			FROM service_jour 
    			WHERE codeService = ? AND jour = ?
			");
			$stmt->execute([$_POST['servDemande'], $jourFr]);

			if ($stmt->fetchColumn() == 0) {
    			header("Location:../views/managerendezvs.php?add=failed");
    		exit;
			}


			// Vérifier le nombre total de rendez-vous (sans index)
			$nombrervnoindex = countdailyRvNoIndex($_POST['servDemande'], $_POST['dateDisponible']);
			if ($data = $nombrervnoindex->fetch()) {
				$nbrv += $data[0]; // Ajout des rv sans index
			}
	
			// Vérifier si la limite du service est atteinte
			if ($nbrv >= limitByService($_POST['servDemande'])) {
				// Si la limite est atteinte, redirection vers une page indiquant que la liste est pleine
				header("Location:../views/managerendezvs.php?add=listfull&dd=" . $_POST['dateDisponible'] . "&sd=" . $_POST['servDemande']);
			} else {
				// Sinon, ajouter un nouveau rendez-vous pour le patient
				$traitement = addrv($_POST['servDemande'], $_POST['dateDisponible']);
				
				// Vérifier si l'ajout a réussi
				if ($traitement) {
					// Si l'ajout est réussi
					header("Location:../views/managerendezvs.php?add=success");
				} else {
					// Si l'ajout échoue
					header("Location:../views/managerendezvs.php?add=failed");
				}
			}
	
		} catch (Exception $e) {
			// Gestion des erreurs
			die('Une erreur est survenue lors du traitement de votre demande.</br>Cliquez <a href="../index.php">ici</a> pour retourner à la page de connexion.');
		}
	}
	

	if (isset($_POST['misajourrv'])){ // Controle de traitement pour la mise a jour des rv d'un patient

		try {
			
			$days=$_POST['days'] ; // Récupération des jours Postés avec leur service
			if (isset($_SESSION['np'])) {
				$traitement = showRvProcess ($_SESSION['np']) ; // recuperation des rv existants en base
					$cpt=0 ;
					$full=false;
					while ( $data=$traitement->fetch()){ 

						$nombrerv = countdailyRvUpdate($_SESSION['np'], $data['codeService'], $days[$cpt]);
						$nbrv = 0; // nombre rv patient avec index et sans index
						if ($dataserv=$nombrerv->fetch()) {
							$nbrv = $nbrv + $dataserv[0]; // ajout nb avec index
						}

						$nombrervnoindex = countdailyRvNoIndex($data['codeService'],$days[$cpt]);
						if ($dataserv=$nombrervnoindex->fetch()){
							$nbrv = $nbrv + $dataserv[0];
						}
				
						if (intval($nbrv >= limitByService($data['codeService']))) {
							$full = true;
							$fullyday = $days[$cpt];
							$fullyserv = $data['codeService'];
							break;
						}

						else {
							$upd= updateRv($data['idRv'], $data['codeService'] ,$days[$cpt] );
					    }

					$cpt++;
					} 

					if($full == true)
						header("Location:../views/managerendezvs.php?add=listfull&dd=".$fullyday."&sd=".$fullyserv) ;
					else if ($upd) // Mis a jour rv reussi 
						header("Location:../views/managerendezvs.php?add=success") ;
					else 
						header("Location:../views/managerendezvs.php?add=failed") ;
			}

		} catch (Exception $e) {
			die('Une erreur est survenue lors du traitement de votre demande.</br>Clickez <a href="../index.php">ici</a> pour retourner a la page de connexion.') ;
		}
	}
	
	
	// Fonction pour compter les rendez-vous existants
	function countRendezvous($serviceCode, $requestedDate, $patientId) {
		$nbrv = 0;
		// Compter les rendez-vous avec index
		$nombrerv = countdailyRvUpdate($patientId, $serviceCode, $requestedDate);
		if ($dataserv = $nombrerv->fetch()) {
			$nbrv += $dataserv[0]; // Ajout nb avec index
		}
	
		// Compter les rendez-vous sans index
		$nombrervnoindex = countdailyRvNoIndex($serviceCode, $requestedDate);
		if ($dataserv = $nombrervnoindex->fetch()) {
			$nbrv += $dataserv[0]; // Ajout nb sans index
		}	
		return $nbrv;
	}	

	function limitByService ($service) { //Fonction permettant de renvoyer la limite du nombre de rv par service

		switch ($service) {

			case "orl": 
			case "orth":
			  return 30;
			  break;
			case "uro":
			case "opht":
			  return 25;
			  break;
			case "hem":
			case "neur":
			case "pneu":
			case "dern":
			case "chir":
			case "endi":
			case "endo":
			  return 20;
			  break;
			case "rhum":
			case "cur":
			case "diet":
			case "gas":
			  return 15;
			  break;
			case "doul":
			case "neph":
			  return 10;
			  break;
			case "tens":
			case "cha_v": 
			case "pach":
			  return 5;
			  break;
			case "ett_ef":
			case "ett_ad":
				return 7;
				break;
			default:
			  return 15;
		}
	}


?>
	         
	


