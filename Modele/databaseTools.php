<?php

/* ============================
   FONCTIONS DE CONVERSION DE DATE
============================ */

function dateEgToFr($EgDate) {
    if (isset($EgDate)) {  
        if ($EgDate[4] == '-') $parts = explode('-', $EgDate);
        else                   $parts = explode('/', $EgDate);
        
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
}

function dateFrToEg($FrDate) {
    if (isset($FrDate)) {  
        if ($FrDate[2] == '-') $parts = explode('-', $FrDate);
        else                   $parts = explode('/', $FrDate);

        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
}

function linkWithoutParam() {
    $urlCourante = $_SERVER["REQUEST_URI"];
    $urlGet = explode("?", $urlCourante);
    return $urlGet[0];
}


/* ============================
   FONCTIONS STATISTIQUES POUR TABLEAU DE BORD
============================ */

function getTotalPatients() {
    $sql = "SELECT COUNT(*) AS total FROM patient";
    return executesql($sql)->fetch()['total'];
}

function getTotalRdv() {
    $sql = "SELECT COUNT(*) AS total FROM rendezvs";
    return executesql($sql)->fetch()['total'];
}

function getTodayRdv() {
    $sql = "SELECT COUNT(*) AS total FROM rendezvs WHERE dateDemande = CURDATE()";
    return executesql($sql)->fetch()['total'];
}

function getTotalAgents() {
    $sql = "SELECT COUNT(*) AS total FROM agent";
    return executesql($sql)->fetch()['total'];
}

?>
