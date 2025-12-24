<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <title>Gestion Rendez - Vous</title>
    
	<?php require("top.php");?>

    <div class="container">
        <main role="main" class="pb-3">
			<main id="main">

				<!-- ======= Breadcrumbs Section ======= -->
				<section class="breadcrumbs">
				<div class="container">

					<div class="d-flex justify-content-between align-items-center">
						<h2>Gerer les Rendez - Vous</h2>
						<ol>
							<li><a href="home.php">Accueil</a></li>
							<li>Gestion des Rendez - Vous</li>
						</ol>
					</div>

				</div>
				</section><!-- End Breadcrumbs Section -->

				<section class="inner-page">
                    <section id="departments" class="departments">
                        <div class="container">
                            <div class="row gy-4">
                                <div class="col-lg-3">
                                    <ul class="nav nav-tabs flex-column">
                                        <li class="nav-item">
                                            <a class="nav-link active show" data-bs-toggle="tab" href="#tab-1">Rendez Vous Déja ajoutés</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#tab-2">Nouveau Rendez - Vous</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#tab-3">Historique</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-lg-9">
                                    <div class="tab-content">
                                        <div class="tab-pane active show" id="tab-1">
                                            <div class="row gy-4">
                                                <!-- rendez-vs deja enregistrés -->
                                                <?php require("rvsaved.php"); ?>
                                            </div>
                                        </div>
                                        <div class="tab-pane" id="tab-2">
                                            <div class="row gy-4">
                                                <!-- ajout nouveau rendez-vs-->
                                                <?php require("addrv.php");?>
                                            </div>
                                        </div>   
                                        <div class="tab-pane" id="tab-3">
                                            <div class="row gy-4">
                                                <!-- Historique des rendez-vs-->
                                                <?php require("rvsavedhisto.php"); ?>
                                            </div>
                                        </div>    
                                    </div>
                                </div>
                            </div>

                        </div>
                    </section><!-- End Departments Section -->	
                                                       
				</section>
			</main>
		</main>
	</div>

    <?php require("bottom.php");?>








