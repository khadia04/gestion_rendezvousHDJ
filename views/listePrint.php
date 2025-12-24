<html>
<head>
	<title>LISTE RENDEZVS</title>
	<meta charset="UTF-8" content="width-device-width,initial-scale=1.0">
	<link type="text/css" href="../assets/css/forlisteprint.css" rel = "stylesheet">
</head>
<body>


<?php
	
	session_start();
    if ( !isset($_SESSION['username']) || (isset($_SESSION['lastAction']) and (time() - $_SESSION['timeframe']) > $_SESSION['lastAction'])) {
		header('Location:../views/logout.php');   // Fermeture de Session Forcee
		exit;
	}
	else {
  		$_SESSION['lastAction'] = time(); // Mise à jour de la variable derniere action
	}

	require_once '../modele/database.php';
	require_once '../modele/databasePatient.php';
	require_once '../modele/databaseRv.php';
	require_once '../modele/databaseTools.php';

	if ( isset($_POST['service_view']) && isset($_POST['date_view']) ) {

		echo "<center><h4> LISTE DES PATIENTS QUI ONT RV LE : <span class='fortitle'>".dateEgToFr($_POST['date_view'])."</span></h4>";
		$service=getServicebyCod($_POST['service_view'])->fetch();
		echo "<h4> SERVICE : <span class='fortitle'>".$service['designService']."</span></h4></center>";
		echo "<div style='overflow-x:auto;'>";

			echo "<table table table-striped>";
				echo "<tr>
						<th>NUMÉRO</th>
						<th>IDENTIFIANT</th>
						<th>PRÉNOMS</th>
						<th>NOM</th>
						<th>TÉLÉPHONE</th>
					  </tr>" ;
															
		$traitement = showdailyRv ( $_POST['service_view'] , $_POST['date_view'] ) ;
		$traitementNoindex = showdailyRvNoindex ( $_POST['service_view'] , $_POST['date_view'] );
		$cpt=0 ;

		while ( $data=$traitement->fetch()){
								
			$cpt++;
				echo "<tr>
						<td>".$cpt."</td>
						<td>".$data['numeroDossierPatient']."</td>
						<td>".$data['prenomPatient']."</td>
						<td>".$data['nomPatient']."</td>
						<td>".$data['telephonePatient']."</td>
					  </tr>";
		}

		while ( $dataNoindex=$traitementNoindex->fetch()){

			$cpt++;
				echo "<tr>
						<td>".$cpt."</td>
						<td>Sans Index</td>
						<td>".$dataNoindex['prenomPatient']."</td>
						<td>".$dataNoindex['nomPatient']."</td>
						<td>".$dataNoindex['telephonePatient']."</td>
					  </tr>";
		}

			if (!$cpt)	
				echo "<tr><td colspan='5'><center><span class='forneant'>NEANT</span></center></td></tr>";
			echo "</table>";
		echo "</div>";									
	}
?>
					
</body>
</html>