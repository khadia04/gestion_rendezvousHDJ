
<?php 

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../modele/database.php';
require_once '../modele/databasePatient.php';
require_once '../modele/databaseTools.php';
require_once '../modele/databaseRv.php';

echo "<h3 class='text-secondary text-center'>Nombre de Rendez-Vs offert par Service </h3>";
?>
<div class="row">
    <div class="col-md-12 text-center">

        <form action="" data-bs-toggle="tab" method="post" id="continueForm"> 
            <div class="card">
                <div class="card-header">
                    <h4 class="text-center">Veuillez choisir un intervalle de date</h4>
                </div>
                <div class="card-body">
                    <div class="row offset-md-3">															
                        <div class="form-group col-md-4">	
                            <label class="control-label">Date de Début</label>
                            <input type="date" name="dateDebut" required min="2024-10-20" class="form-control border-primary" autocomplete="off" 
                                   value="<?php echo isset($_POST['dateDebut']) ? $_POST['dateDebut'] : date('Y-m-d'); ?>" />
                        </div>
                        <div class="form-group col-md-4">	
                            <label class="control-label">Date de Fin</label>
                            <input type="date" name="dateFin" required min="2024-10-20" class="form-control border-primary" autocomplete="off" 
                                   value="<?php echo isset($_POST['dateFin']) ? $_POST['dateFin'] : date('Y-m-d'); ?>" />
                        </div>
                    </div>
                    <hr/>
                    <div class="row mb-10">
                        <div class="form-group col-md-4 offset-md-4">
                            <input type="submit" name="continueReport" value="AFFICHER" class="w-100 btn btn-secondary" />
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <table class="table table-striped mt-2 table-sm">
            <tr>
                <th>Service</th>
                <th>NB Rendez-Vous offert</th>
            </tr>
            <tr>
                <td class="bg-danger" colspan="2"></td>
            </tr>

            <?php

                $dateDebut = $_POST['dateDebut'] ?? date('Y-m-d'); // Utiliser la date actuelle par défaut
                $dateFin = $_POST['dateFin'] ?? date('Y-m-d'); // Utiliser la date actuelle par défaut

                // Récupérer les résultats des trois tables
                $rvTable = showcontinueRvReport($dateDebut, $dateFin);
                $rvHistoTable = showcontinueRvHistoReport($dateDebut, $dateFin);
                $rvNoIndexTable = showcontinueRvNoIndexReport($dateDebut, $dateFin);

                // Créer un tableau pour stocker les totaux par service
                $totalRvParService = [];

                // Fonction pour ajouter ou mettre à jour le total de rendez-vous par service
                function ajouterAuTotal(&$totalRvParService, $service, $total) {
                    if (isset($totalRvParService[$service])) {
                        $totalRvParService[$service] += $total;  // Ajouter au total existant
                    } else {
                        $totalRvParService[$service] = $total;   // Créer une nouvelle entrée
                    }
                }

                // Parcourir les résultats des trois tables et mettre à jour le total par service
                foreach ($rvTable as $rv) {
                    ajouterAuTotal($totalRvParService, $rv['designService'], $rv['totalRv']);
                }

                foreach ($rvHistoTable as $rv) {
                    ajouterAuTotal($totalRvParService, $rv['designService'], $rv['totalRv']);
                }

                foreach ($rvNoIndexTable as $rv) {
                    ajouterAuTotal($totalRvParService, $rv['designService'], $rv['totalRv']);
                }

                // Affichage des résultats
                if (!empty($totalRvParService)) {
                    
                    $T=0; // Total des Totaux
                    foreach ($totalRvParService as $service => $total) {
                        echo "<tr>
                                <td>".$service."</td>
                                <td>".$total."</td>
                            </tr>";   
                            $T = $T + $total;    
                    }

                    echo "<tr>
                            <td class='bg-danger' colspan='2'></td>
                        </tr>"; 

                    echo "<tr>
                            <td>Total RV période du <span class='text-success'>" . (dateEgToFr($_POST['dateDebut'] ?? date('Y-m-d'))) . "</span> au <span class='text-success'>" . (dateEgToFr($_POST['dateFin'] ?? date('Y-m-d'))) . "</span></td>
                            <td>" . $T . "</td>
                        </tr>";

                    $dateDebut = new DateTime($_POST['dateDebut'] ?? date('Y-m-d'));
                    $dateFin = new DateTime($_POST['dateFin'] ?? date('Y-m-d'));
                    $interval = $dateFin->diff($dateDebut);
                    $daysDiff = $interval->format('%a') + 1;

                    if ($daysDiff > 0) {
                        echo "<tr>
                                <td>Nombre de RV en moyenne pour cette periode - ".$daysDiff." jour(s)</td>
                                <td>" . round($T / $daysDiff, 2) . "</td>
                            </tr>";
                    }
                    echo "</table>";
                 
                } else {
                    echo "<p class='text-center'>Aucun rendez-vous trouvé pour la période donnée.</p>";
                }
              
                echo "<tr><td class='bg-danger' colspan='2'></td></tr>";
            ?>
        </table>
    </div>
</div>