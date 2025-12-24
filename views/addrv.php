    <?php      
        if ( isset($cpt) && isset($full) && $cpt < $full) {
            echo "<h3 class='text-secondary text-center' >Ajout Nouveau Rendez-Vs.</h3><br/>";
    ?>
    <div class="box">
        <div class="notify" id="Notify"></div>
    </div>
    <form action="../Controller/ctrlerRv.php" method="post">
        <div class="row">
            <div class="form-group col-md-4 offset-md-2 mb-3">	 
                <label class="control-label">Service Demandé</label>    
                <select name='servDemande' id='servDemande' class="form-control border-primary">
                    <option value="0">Veuillez choisir un service</option> 
                    <?php
                        require_once '../modele/database.php';
                        require_once '../modele/databaseRv.php';
                        while($data=$list->fetch()) {
                            if (!in_array($data['codeService'], $offer) ) { // On affiche ici que les service qui ne sont pas encore offert
                                echo "<option value='".$data['codeService']."'> ".$data['designService']."</option>";
                            }                               
                        }
                    ?>
                </select>
            </div>
            <div class="form-group col-md-4">	 
                <label class="control-label">Prochaine Date Disponible</label>   
                <input type='date' name='dateDisponible' min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required class="form-control border-primary" />
            </div>
        </div>
        <div class="row">
            <div class="form-group col-md-4 offset-md-4 mt-3 mb-3">
                <input type="submit" name="ajoutrv" id="AJOUTERRV" Value="Ajouter un Rendez-Vs" class="w-100 btn btn-secondary"/>
            </div>
        </div>

    </form>
<?php
    }
    else {
        echo "<h3 class='text-secondary text-center'>Tous les services ont ètè offért veuillez directement mèttre à jour les dates</h3>";
    }
?>
</article>
                               

                               