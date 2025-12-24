<?php 
    require_once '../modele/database.php';
    require_once '../modele/databasePatient.php';
    require_once '../modele/databaseTools.php';
    require_once '../modele/databaseRv.php';

    echo "<h3 class='text-secondary text-center'>Liste des Rendez-Vs offert par jour. </h3>";
?>
<div class="row">
    <div class="col-md-12 text-center">

        <form action="" method="post">
            <div class="card">
                <div class="card-header">
                    <h4 class="text-center">Veuillez choisir une date</h4>
                </div>
                <div class="card-body">
                    <div class="row justify-content-center">
                        <!-- Div pour l'input date et le label -->
                        <div class="form-group col-md-4 d-flex align-items-center">
                            <input type="date" required name="dailyDate" min="2024-10-19" class="form-control border-primary w-75" autocomplete="off" 
                                   value="<?php echo isset($_POST['dailyDate']) ? $_POST['dailyDate'] : date('Y-m-d'); ?>" />
                        </div>
                        <!-- Div pour le bouton de soumission -->
                        <div class="form-group col-md-3 d-flex align-items-center">
                            <input type="submit" name="dailyReport" value="AFFICHER" class="btn btn-secondary w-100" />
                        </div>
                    </div>
                    <hr />
                </div>
            </div>
        </form>

        <table class="table table-striped mt-2 table-sm" style="font-size : 80%;">
            <tr>
                <th>Numéro</th>
                <th>Patient</th>
                <th>Service Demandé</th>
                <th>Date de demande</th>
                <th>Date obtenu</th>
                <th>Jour(s) en attente</th>
            </tr>
            <tr>
                <td class="bg-danger" colspan="6"></td>
            </tr>

            <?php
                $cpt = 0;
                $jours = 0;
                $date = $_POST['dailyDate'] ?? null;

                // Si une date est envoyée, récupère les rapports sinon affiche le rapport par défaut
                $reportFunctions = ['showdailyRvReport', 'showdailyRvHistoReport', 'showdailyRvNoIndexReport'];
                $isNewPatient = false;

                foreach ($reportFunctions as $functionName) {
                    $traitement = $functionName($date);

                    // Vérifier s'il y a des données dans showdailyRvNoIndexReport avant d'afficher "Nouveau Patient"
                    if ($functionName === 'showdailyRvNoIndexReport' && isset($traitement) && $traitement->rowCount() > 0) {
                        echo "<tr><td class='bg-secondary' colspan='6'>Nouveau Patient</td></tr>";
                        $isNewPatient = true;
                    }

                    while ($data = $traitement->fetch()) {
                        $cpt++;
                        $dateDemande = new DateTime($data['dateDemande']);
                        $dateRvServ = isset($data['dateRvServ']) ? new DateTime($data['dateRvServ']) : new DateTime($data['dateDisponible']);
                        $interval = $dateRvServ->diff($dateDemande);
                        $daysDiff = $interval->format('%a');
                        $jours += $daysDiff;

                        echo "<tr>
                                <td>$cpt</td>
                                <td class='servdemande'>{$data['prenomPatient']} {$data['nomPatient']}</td>
                                <td class='servdemande'>{$data['designService']}</td>
                                <td class='daterv'><input type='date' class='inputdaterv' readonly value='{$data['dateDemande']}' /></td>
                                <td class='daterv'><input type='date' class='inputdaterv' readonly value='{$dateRvServ->format('Y-m-d')}' /></td>
                                <td>$daysDiff jour(s)</td>
                            </tr>";
                    }
                }

                echo "<tr><td class='bg-danger' colspan='6'></td></tr>";

                if ($cpt > 0) {
                    echo "<tr>
                            <td colspan='3'>Nombre de jours moyenne en attente</td>
                            <td colspan='3'>" . round($jours / $cpt, 2) . " jour(s)</td>
                          </tr>";
                } else {
                    echo "<tr><td colspan='6'>Aucun rendez-vous trouvé.</td></tr>";
                }
            ?>
        </table>
    </div>
</div>
