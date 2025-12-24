<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Ajout Patient</title>

    <?php require("top.php"); ?>

    <div class="container">
        <main role="main" class="pb-3">
            <main id="main">

                <!-- ======= Breadcrumbs Section ======= -->
                <section class="breadcrumbs">
                    <div class="container">

                        <div class="d-flex justify-content-between align-items-center">
                            <h2>Ajouter un Patient</h2>
                            <ol>
                                <li><a href="home.php">Accueil</a></li>
                                <li>Ajout Patient</li>
                            </ol>
                        </div>

                    </div>
                </section><!-- End Breadcrumbs Section -->

                <section class="inner-page">
                    <?php
                    require_once '../modele/database.php';
                    require_once '../modele/databasePatient.php';
                    require_once '../modele/databaseTools.php';
                    require_once '../modele/databaseRv.php';

                    // ...

                    if (isset($_GET['np']) && $_GET['np'] > 0) {
                        // Récupération des données du Patient en cours - Pour Modification
                        $patient = checkPatient($_GET['np']);
                        if ($patient->rowCount() > 0) { // Si le patient existe ?
                            $data = $patient->fetch();
                            $exist = true;
                        }
                    }

					if (isset($_GET['add']) && $_GET['add'] == 'success') {
						echo "<p class='text-bg-success text-center mt-5 mb-5'><strong>Les données ont été ajoutées avec succès.</strong>";
					}

                    ?>

                    <div class="box mt-3">
                        <div class="notify" id="Notify"></div>
                    </div>
                    <section id="departments" class="departments">
                        <div class="container">

                            <div class="row gy-4">
                                <div class="col-lg-2">
                                    <ul class="nav nav-tabs flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link active show" data-bs-toggle="tab" href="#tab-1">Identifiant Connu</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#tab-2">Sans Identifiant</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-lg-10">
                                    <div class="tab-content">
                                        <div class="tab-pane active show" id="tab-1">
                                            <div class="row gy-4">

                                                <form action="../Controller/ctrlerPatient.php" method="post">
													<div class="card">
														<div class="card-header">
															<h4 class="text-center">
																Veuillez renseigner les informations du Patient
															</h4>
														</div>
														<div class="card-body">
															<div class="row">
																<div class="form-group col-md-2">	
																	<label class="control-label">Numero Dossier</label>
																	<input type="number" id="numeroDossierPatient" name="numeroDossierPatient" value="<?php if ( isset($exist) && isset($data['numeroDossierPatient'])) echo $data['numeroDossierPatient']; ?>" <?php if ( isset($exist) && isset($data['numeroDossierPatient'])) echo 'readonly'; ?>  min="1" class="form-control border-primary" required autocomplete="off" />
																</div>
																<div class="form-group col-md-5">	
																	<label class="control-label">Prénom(s)</label>
																	<input type="text" id="prenomsPatient" name="prenomsPatient" value="<?php if ( isset($exist) && isset($data['prenomPatient'])) echo $data['prenomPatient'] ; ?>" minlength="2" maxlength="50" class="form-control border-primary" required autocomplete="on" />	
																</div>
																<div class="form-group col-md-2">	
																	<label class="control-label">Nom</label>
																	<input type="text" id="nomPatient" name="nomPatient" value="<?php if ( isset($exist) && isset($data['nomPatient'])) echo $data['nomPatient']; ?>" minlength="2" maxlength="20" class="form-control border-primary" required autocomplete="on" />
																</div>
																<div class="form-group col-md-3">	
																	<label class="control-label">Téléphone</label>
																	<input type="number" id="telephonePatient" name="telephonePatient"value="<?php if ( isset($exist) && isset($data['telephonePatient'])) echo $data['telephonePatient']; else echo "221"?>" min="221700000000" max="221789999999" class="form-control border-primary" required autocomplete="off" />
																</div>
															</div>
															<hr/>
															<div class="row mb-10">
																<div class="form-group col-md-2 offset-md-4">
																	<input type="submit" id="AJOUTER" class="w-100 btn btn-secondary"<?php if (isset($exist)){ echo 'name="updatePatient"'; echo 'value="Méttre à Jour"'; } else { echo 'name="ajoutPatient"'; echo 'value="Enregistrer"';} ?> />
																</div>
																<div class="form-group col-md-2">
																	<input type="reset" class="w-100 btn btn-outline-danger" value="Tout Effacer" />
																</div>
															</div>
														</div>
													</div>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="tab-pane" id="tab-2">
                                            <div class="row gy-4">
                                                <form action="../Controller/ctrlerPatient.php" method="post">
													<div class="card">													
														<div class="card-header">
															<h4 class="text-center">Veuillez renseigner les informations du Patient</h4>
														</div>
														<div class="card-body">
															<div class="row">
																<div class="form-group col-md-3">	
																	<label class="control-label">Prénom(s)</label>
																	<input type="text" id="prenomsPatientNoi" name="prenomsPatientNoi" minlength="2" maxlength="50" 
																		class="form-control border-primary" required autocomplete="on" />	
																</div>
																<div class="form-group col-md-2">	
																	<label class="control-label">Nom</label>
																	<input type="text" id="nomPatientNoi" name="nomPatientNoi" minlength="2" maxlength="20" 
																		class="form-control border-primary" required autocomplete="on" />
																</div>
																<div class="form-group col-md-3">	
																	<label class="control-label">Téléphone</label>
																	<input type="number" id="telephonePatientNoi" name="telephonePatientNoi" value="221" min="221700000000" max="221789999999" 
																		class="form-control border-primary" required autocomplete="off" />
																</div>
																<div class="form-group col-md-2">	
																	<label class="control-label">Service </label>
																	<select name='servDemande' id='servDemande' class="form-control border-primary" >
																		<option value="0">Veuiller Choisir</option>  
																			<?php
																				$liste=listServices();
																				while($data=$liste->fetch()){
																					echo "<option value='".$data['codeService']."'> ".$data['designService']." </option>";
																				}
																			?>
																	</select>
																</div>
																<div class="form-group col-md-2">	
																	<label class="control-label">Date disponible</label>
																	<input type="date" name='dateDisponible' min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required  class="form-control border-primary"/>
																</div>
															</div>
															<hr/>
															<div class="row mb-10">
																	<div class="form-group col-md-2 offset-md-4">
																		<input type="submit" id="AJOUTERNOI" name="ajoutPatientNoIndex" class="w-100 btn btn-secondary" value="Enregistrer"/>
																	</div>
																	<div class="form-group col-md-2">
																		<input type="reset" class="w-100 btn btn-outline-danger" value="Tout Effacer" />
																	</div>
															</div>
														</div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section><!-- End Departments Section -->
            </main>
        </main>
    </div>

    <?php require("bottom.php"); ?>

</html>
