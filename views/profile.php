<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>User Profile</title>
    
	<?php require("top.php");?>

    <div class="container">
        <main role="main" class="pb-3">
			<main id="main">

				<!-- ======= Breadcrumbs Section ======= -->
				<section class="breadcrumbs">
				<div class="container">

					<div class="d-flex justify-content-between align-items-center">
						<h2>Gerer Mon profil</h2>
						<ol>
							<li><a href="home.php">Accueil</a></li>
							<li>Profil Utilisateur</li>
						</ol>
					</div>

				</div>
				</section><!-- End Breadcrumbs Section -->

				<section class="inner-page">
					<?php 

						require_once '../modele/database.php';
						require_once '../modele/databaseAgent.php';

						//Information de mis a jour Profil
						if (isset($_GET['update']) && $_GET['update'] == 'failed') {
							echo " <p class='text-bg-danger text-center mt-5'><strong>Une érreur a survenue pendant le traitement.</strong> 
								Veuillez contacter votre administrateur !</p>";
						}

						else if (isset($_GET['update']) && $_GET['update'] == 'success') {
							echo "<p class='text-bg-success text-center mt-5'><strong>Les mises à jour ont été ajoutées avec succès.</strong>";
						}
														
						if ( isset($_SESSION['username'])) {
							$agent=checkAgent($_SESSION['username']) ; // Recuperation des donnees de l'utiisateur en cours
							$data=$agent->fetch();
						}
					?>
						<div class="box">
							<div class="notify" id="Notify"></div>
						</div>
						<form action="../Controller/ctrlerlogin.php" method="post">
							<div class="card">
								<div class="card-header">
									<h4 class="text-center">Veuillez modifier les informations que vous désirez, puis sauvegardez</h4>
								</div>
								<div class="card-body">
									<div class="row offset-md-2">
										<div class="form-group col-md-2">	
											<label class="control-label">Nom d'utilisaeur</label>
											<input type="text" value="<?php if (isset($data['username'])) echo $data['username']; ?>" 
												   class="form-control bg-light border-warning" readonly name="username" />
			
										</div>

										<div class="form-group col-md-4">	
											<label class="control-label">Prénom(s)</label>
											<input type="text" value="<?php if (isset($data['prenom_agent'])) echo $data['prenom_agent']; ?>" 
												   class="form-control border-primary" name="prenom_agent" minlength="2" 
												   id="prenom_agent" maxlength="50" required autocomplete="off" />
			
										</div>
										<div class="form-group col-md-3">	
											<label class="control-label">Nom</label>
											<input type="text"  value="<?php if (isset($data['nom_agent'])) echo $data['nom_agent']; ?>"  
												   class="form-control border-primary" name="nom_agent" minlength="2" 
												   id="nom_agent" maxlength="20" required autocomplete="off"  />
										</div>					
									</div>	
									<div class="row offset-md-2">
										<div class="form-group col-md-3">	
											<label class="control-label">Mot de Passe</label>
											<input type="password" name="password" id="password1" minlength="6" required
												   class="form-control border-primary"/>
										</div>

										<div class="form-group col-md-3">	
											<label class="control-label">Confirmation Mot de Passe</label>
											<input type="password" name="password" id="password2" minlength="6" required
												   class="form-control border-primary"/>			
										</div>
										<div class="form-group col-md-3">	
											<label class="control-label">Téléphone</label>
											<input type="number" value="<?php if (isset($data['telephone_agent'])) echo $data['telephone_agent']; ?>" 
												   class="form-control border-primary" name="telephone_agent" 
												   required min="221700000000" max="221789999999" autocomplete="off" value="221" />
										</div>					
									</div>	
									<hr/>
									<div class="row mb-10">
											<div class="form-group col-md-4 offset-md-4">
												<input type="submit" name="updateagent" id="UPDATEAGENT" value="Sauvegarder" class="w-100 btn btn-secondary" />
											</div>
									</div>			
								</div>
							</div>
						</form>
				</section>					
			</main>
		</main>
	</div>

    <?php require("bottom.php");?>
						
		