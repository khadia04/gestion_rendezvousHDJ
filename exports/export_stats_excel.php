<?php
date_default_timezone_set('Africa/Dakar');

require_once "../modele/database.php";
require_once "../modele/databaseRv.php";

/* =========================
   PARAMÈTRES
========================= */
$mois = (isset($_GET['month']) && (int)$_GET['month'] >= 1 && (int)$_GET['month'] <= 12)
    ? (int)$_GET['month']
    : null;

$annee = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$data = getRdvPerService($mois, $annee);

$moisNom = [
    1=>'Janvier',2=>'Fevrier',3=>'Mars',4=>'Avril',
    5=>'Mai',6=>'Juin',7=>'Juillet',8=>'Aout',
    9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Decembre'
];

$periode = $mois ? $moisNom[$mois].' '.$annee : 'Annee '.$annee;
$numeroRapport = 'CHNDJ-RDV-' . date('Ymd');

/* =========================
   HEADERS EXCEL
========================= */
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=rapport_rdv_CHNDJ.xls");
header("Pragma: no-cache");
header("Expires: 0");

/* =========================
   CONTENU HTML (EXCEL)
========================= */
echo '
<html>
<head>
<meta charset="UTF-8">
<style>
body { font-family: Arial; }
.title { font-size:16px; font-weight:bold; text-align:center; }
.subtitle { font-size:14px; text-align:center; }
.info { text-align:center; font-style:italic; }
table { border-collapse: collapse; margin:20px auto; width:70%; }
th { background:#2980B9; color:white; padding:8px; border:1px solid #000; }
td { padding:6px; border:1px solid #000; }
tr:nth-child(even) { background:#F5F7FA; }
.total { font-weight:bold; background:#DCDCDC; }
.signature { margin-top:40px; text-align:right; width:90%; }
</style>
</head>
<body>

<div class="title">CENTRE HOSPITALIER NATIONAL DALAL JAMM</div>
<div class="subtitle">Rapport statistique des rendez-vous</div>
<br>
<div class="info">Période : '.$periode.'</div>
<div class="info">Rapport N° : '.$numeroRapport.'</div>

<table>
<tr>
    <th>Service</th>
    <th>Nombre de RDV</th>
</tr>
';

$total = 0;

foreach ($data as $row) {
    echo '<tr>
        <td>'.$row['designService'].'</td>
        <td style="text-align:right">'.$row['total'].'</td>
    </tr>';
    $total += $row['total'];
}

echo '
<tr class="total">
    <td style="text-align:right">TOTAL</td>
    <td style="text-align:right">'.$total.'</td>
</tr>
</table>

<div class="signature">
    Fait à Dakar, le '.date('d/m/Y').'<br><br>
    <strong>Le Responsable des Statistiques</strong><br><br>
    Signature :<br><br>
    <div style="margin-top:20px; font-style:italic;">
    Cachet et signature disponibles sur la version PDF officielle.
    </div>

</div>

</body>
</html>';
