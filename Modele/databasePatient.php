<?php

	function checkPatient ( $ndp) {
		//Requete permettant de verifier l'existence d'un patient a travers son identifiant
		$sql_prepare="SELECT * FROM patient WHERE numeroDossierPatient = :ndp" ;
		$parameters = Array( 'ndp' => $ndp) ;
	    return prepare_executesql($sql_prepare,$parameters);
	}

	function checkPatientWithTel($ndp, $tp) {
		// Requête permettant de vérifier l'existence d'un patient à travers son identifiant ou son numéro de téléphone
		$sql_prepare = "SELECT * FROM patient WHERE numeroDossierPatient = :ndp OR telephonePatient = :tp  LIMIT 0,1";
		$parameters = array('ndp' => $ndp, 'tp' => $tp);
		return prepare_executesql($sql_prepare, $parameters);
	}

	
	function addpatient ($ndp, $prenoms, $nom, $telephone ) {
		//Requete permettant d'ajouter un nouveau patient
		$sql_prepare="INSERT INTO patient  VALUES ( :ndp , :prenoms , :nom , :telephone )" ;
		$parameters = Array ( 'ndp' => $ndp , 'prenoms' => $prenoms , 'nom' => $nom , 'telephone' => $telephone ) ;
	    return prepare_executesql($sql_prepare,$parameters);
	}

	function updatepatient ($ndp, $prenoms, $nom, $telephone ) {
		//Requete permettant de mettre a jour les donnees d'un patient
		$sql_prepare="UPDATE Patient SET prenomPatient = :prenoms , nomPatient = :nom , telephonePatient = :telephone WHERE numeroDossierPatient = :ndp" ;
		$parameters = Array( 'prenoms' => $prenoms , 'nom' => $nom , 'telephone' => $telephone , 'ndp' => $ndp) ;
	    return prepare_executesql($sql_prepare,$parameters);
	}







?>


 