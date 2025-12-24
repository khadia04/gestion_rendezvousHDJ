
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Liste des Rendez - Vous</title>
    
	<?php require("top.php");?>

    <div class="container">
        <main role="main" class="pb-3">
			<main id="main">

				<!-- ======= Breadcrumbs Section ======= -->
				<section class="breadcrumbs">
				<div class="container">

					<div class="d-flex justify-content-between align-items-center">
						<h2>Affichage Rendez - Vous</h2>
						<ol>
							<li><a href="home.php">Accueil</a></li>
							<li>Liste des Rendez - Vous</li>
						</ol>
					</div>

				</div>
				</section><!-- End Breadcrumbs Section -->

				<section class="inner-page">			 
						<div class="box">
							<div class="notify" id="Notify"></div>
						</div>
						<form action="listePrint.php" method="post" target="_blank" >
							<div class="card">
								<div class="card-header">
									<h4 class="text-center">Veuillez choisir le service et la date à afficher</h4>
								</div>
								<div class="card-body">
									<div class="row offset-md-3">
										<div class="form-group col-md-4">	
											<label class="control-label">Service </label>
                                            <select name="service_view" id="service_view" class="form-control border-primary" >
                                                <option value="0">Veuiller Choisir</option>  
                                                <?php
                                                    require_once '../modele/database.php';
                                                    require_once '../modele/databaseRv.php';
                                                    $liste=listServices();
                                                    while($data=$liste->fetch()){
                                                        echo "<option value='".$data['codeService']."'> ".$data['designService']." </option>";
                                                    }
                                                ?>
                                            </select>

										</div>
                                        <div class="form-group col-md-4">	
                                        <label class="control-label">Date à Afficher</label>
											<input type="date" name="date_view" required min="2020-12-15" class="form-control border-primary" autocomplete="off" />
										</div>
									</div>
									<hr/>
									<div class="row mb-10">
											<div class="form-group col-md-2 offset-md-3">
												<input type="submit" id="LISTER" value="Lister" class="w-100 btn btn-secondary" />
											</div>
									</div>
							</div>
						</form>
				</section>
			</main>
		</main>
	</div>

    <?php require("bottom.php");?>








