<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Recherche Patient</title>
    
	<?php require("top.php");?>

    <div class="container">
        <main role="main" class="pb-3">
			<main id="main">

				<!-- ======= Breadcrumbs Section ======= -->
				<section class="breadcrumbs">
				<div class="container">

					<div class="d-flex justify-content-between align-items-center">
						<h2>Rechercher un Patient</h2>
						<ol>
							<li><a href="home.php">Accueil</a></li>
							<li>Recherche Patient</li>
						</ol>
					</div>

				</div>
				</section><!-- End Breadcrumbs Section -->

				<section class="inner-page">
						<div class="box">
							<div class="notify" id="Notify"></div>
						</div>
						<form action="../Controller/ctrlerPatient.php" method="post">
							<div class="card">
								<div class="card-header">
									<h4 class="text-center">Veuillez renseigner le numero de dossier ou le téléphone (221)</h4>
								</div>
								<div class="card-body">
									<div class="row offset-md-3">
										<div class="form-group col-md-4">	
											<label class="control-label">Numero Dossier</label>
											<input type="number" name="snumeroDossierPatient" id="snumeroDossierPatient" min="0"  require autocomplete="off"
												   class="form-control border-primary" autocomplete="off" />
										</div>
                                        <div class="form-group col-md-4">	
                                        <label class="control-label">Téléphone</label>
											<input type="number"  name="stelephonePatient" id="stelephonePatient" autocomplete="off"
												   class="form-control border-primary" autocomplete="off" value="221" />
										</div>
									</div>
									<hr/>
									<div class="row mb-10">
											<div class="form-group col-md-2 offset-md-3">
												<input type="submit"  id="RECHERCHER" value="Rechercher" name="recherchePatient" class="w-100 btn btn-secondary" />
											</div>
									</div>
							</div>
						</form>

						<?php
							// Information de Recherche Patient
							if (isset($_GET['found']) AND $_GET['found'] == 'false' ) {
								echo '<p class="text-bg-warning text-center mt-5">Aucun résultat trouvé, Veuillez penser à  mettre à jour les filtres.</p>';
							}
						?>
				</section>
			</main>
		</main>
	</div>

    <?php require("bottom.php");?>








