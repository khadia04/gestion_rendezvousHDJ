<?php 
        require_once '../modele/database.php';
        require_once '../modele/databasePatient.php';
        require_once '../modele/databaseTools.php';
        require_once '../modele/databaseRv.php';

    if (isset($_SESSION['np'])){
        $tuplet=checkPatient($_SESSION['np']); 
        if ($tuplet->rowCount() > 0 ) {
            $data=$tuplet->fetch();
            $_SESSION['initiales']=$data['prenomPatient'].' '.$data['nomPatient'];
            echo "<h3 class='text-secondary mt-5 text-center' >Informations du patient. </h3>";
            echo '<table class="table table-striped mb-5"> 
                    <tr>
                        <th>IDENTIFIANT</th> 
                        <th>PRÉNOM(S) ET NOM</th>
                        <th>TÉLÉPHONE</th> 
                        <th></th>
                    </tr>';
        
            echo '<tr>
                    <td>'.$data['numeroDossierPatient'].'</td> 
                    <td><span class="initiales">'.$_SESSION['initiales'].'</span></td> 
                    <td>'.$data['telephonePatient'].'</td>
                    <td><a href="addpatient.php?np='.$_SESSION['np'].'" class="btn btn-sm btn-warning text-dark"><i class="fas fa-edit m-1"> Modifier Infos</a><td>
                </tr>
            </table>';
        
        }
    }

        echo "<h3 class='text-secondary text-center'>Historique des Rendez-Vs enregistrès. </h3>";
?>
<div class="row">
    <div class="col-md-12 text-center">
		<form method="post" enctype="multipart/form-data" action="../controller/CtrlerRv.php">
            <table class="table table-striped mt-2">
                <tr>
                    <th>SERVICES DEMANDÉS</th>
                    <th>DATE DES RV DÉJA ENREGISTRÉS</th>
                </tr>

                <?php
                    if (isset($_SESSION['np'])) {
                        $traitement = showRvHisto($_SESSION['np']) ;
                        $cpt=0 ; // Pour verifier l'existence de rendez-vs donné
                        $offer[]="deja offer";//Services dont le patient dispose deja de rendez-vs
                        $precis=false ; //Pour verifier la precision des rendez-vs a venir
                        $rvexist=0;

                        if (isset($traitement)) {
                            while ( $data=$traitement->fetch()){
                                if ( $data['dateRvServ'] > date('Y-m-d') && $precis == false ) {
                                    echo "<tr>
                                            <td colspan='2' class='text-danger'><h4>Prochain Rendez Vous</td></h4>
                                          </tr><hr/>";
                                    $precis=true ;
                                }
                                                        
                                echo "<tr>
                                        <td class='servdemande'>".$data['designService']."</td>
                                        <td class='daterv'><input type ='date' readonly class='inputdaterv' name='days[]' value='".$data['dateRvServ']."' /></td>
                                      <tr/>";

                                $offer[]=$data['codeService'];
                                $cpt++;
                                $rvexist=1;
                                                        
                            }

                            echo "<input type='hidden' value='".$cpt."'/>" ; 
                        }
                    }

                        if ( isset($rvexist) && !$rvexist ) 
                            echo "<tr>
                                    <td colspan='2'>
                                        <p class='text-warning text-center mt-5'></strong>Ce patient n'a pas encore de Rendez-Vs dans les Historiques.</p>  
                                    </td>
                                 </tr>";   
                                     
                        $list=listServices(); 
                        $full=$list->rowCount(); //Nombre de services possible 
                                                     
                    ?>

            </table>
        </form>
    </div>
</div>


