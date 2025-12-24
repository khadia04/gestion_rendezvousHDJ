<?php
require_once '../Modele/databaseRv.php';

$mois = (isset($_GET['month']) && (int)$_GET['month'] >= 1 && (int)$_GET['month'] <= 12)
    ? (int)$_GET['month']
    : null;

$annee = isset($_GET['year']) && $_GET['year'] !== ''
    ? (int)$_GET['year']
    : date('Y');

$rdvPerService = getRdvPerService($mois, $annee);
$rdvActuel = getRdvCountByMonth($mois ?? date('n'), $annee);

echo json_encode([
    'mois' => $mois,
    'annee' => $annee,
    'totalRdv' => $rdvActuel,
    'services' => $rdvPerService
]);

