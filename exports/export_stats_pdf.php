<?php
date_default_timezone_set('Africa/Dakar');

ob_start();

require_once "../libs/fpdf/fpdf.php";
require_once "../modele/database.php";
require_once "../modele/databaseRv.php";

/* =========================
   FONCTION TEXTE UTF-8
========================= */
function txt($str) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
}

/* =========================
   PARAMÈTRES
========================= */
$mois = (isset($_GET['month']) && (int)$_GET['month'] >= 1 && (int)$_GET['month'] <= 12)
    ? str_pad((int)$_GET['month'], 2, '0', STR_PAD_LEFT)
    : null;

$annee = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$data = getRdvPerService($mois, $annee);

$moisNom = [
    '01'=>'Janvier','02'=>'Fevrier','03'=>'Mars','04'=>'Avril',
    '05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Aout',
    '09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Decembre'
];


$numeroRapport = 'CHNDJ-RDV-' . date('Ymd');



/* =========================
   CLASSE PDF
========================= */
class PDF extends FPDF
{
    function Header()
    {
        // Logo
        $this->Image('../assets/img/logo.png', 10, 10, 22);

        // Titre principal
        $this->SetFont('Arial','B',14);
        $this->Cell(0,7, txt('CENTRE HOSPITALIER NATIONAL DALAL JAMM'), 0, 1, 'C');

        // Sous-titre
        $this->SetFont('Arial','',12);
        $this->Cell(0,7, txt('Rapport statistique des rendez-vous'), 0, 1, 'C');

        // Ligne
        $this->Ln(4);
        $this->SetDrawColor(180,180,180);
        $this->Line(10, 38, 200, 38);
        $this->Ln(12);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(
            0,
            10,
            txt('Document genere automatiquement - CHNDJ | ')
            .date('d/m/Y H:i')
            .' | Page '.$this->PageNo().'/{nb}',
            0,
            0,
            'C'
        );
    }
}

/* =========================
   CRÉATION PDF
========================= */
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();

/* =========================
   TITRE PÉRIODE
========================= */
$pdf->SetFont('Arial','B',12);

if ($mois !== null) {
    $pdf->Cell(
        0,
        10,
        txt('Periode : '.$moisNom[$mois].' '.$annee),
        0,
        1,
        'C'
    );
} else {
    $pdf->Cell(
        0,
        10,
        txt('Periode : Annee '.$annee),
        0,
        1,
        'C'
    );
}

$pdf->Ln(6);


/* =========================
   NUMÉRO RAPPORT
   ========================= */
$pdf->SetFont('Arial','I',10);
$pdf->Cell(
    0,
    8,
    txt('Rapport N° : '.$numeroRapport),
    0,
    1,
    'C'
);
$pdf->Ln(4);


/* =========================
   TABLEAU (CENTRÉ)
========================= */
$tableWidth = 160;
$startX = (210 - $tableWidth) / 2;
$pdf->SetX($startX);

$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(41,128,185);
$pdf->SetTextColor(255);

$pdf->Cell(110,9, txt('Service'), 1, 0, 'C', true);
$pdf->Cell(50,9, txt('Nombre de RDV'), 1, 1, 'C', true);

$pdf->SetFont('Arial','',11);
$pdf->SetTextColor(0);

$fill = false;
$total = 0;

foreach ($data as $row) {
    $pdf->SetX($startX);
    $pdf->SetFillColor(245,247,250);

    $pdf->Cell(
        110,
        8,
        txt($row['designService']),
        1,
        0,
        'L',
        $fill
    );

    $pdf->Cell(
        50,
        8,
        $row['total'],
        1,
        1,
        'R',
        $fill
    );

    $total += $row['total'];
    $fill = !$fill;
}

/* =========================
   TOTAL
========================= */
$pdf->SetX($startX);
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(220,220,220);

$pdf->Cell(110,9, txt('TOTAL'), 1, 0, 'R', true);
$pdf->Cell(50,9, $total, 1, 1, 'R', true);


$pdf->Ln(20);

// On se place à droite
$pdf->SetX(110);

// Lieu & date
$pdf->SetFont('Arial','',10);
$pdf->Cell(
    80,
    6,
    txt('Fait à Dakar, le '.date('d/m/Y')),
    0,
    1,
    'L'
);

$pdf->Ln(6);
$pdf->SetX(110);

// Responsable
$pdf->SetFont('Arial','B',11);
$pdf->Cell(
    80,
    6,
    txt('Le Responsable des Statistiques'),
    0,
    1,
    'L'
);

$pdf->Ln(10);
$pdf->SetX(110);

// Signature
$pdf->SetFont('Arial','',10);
$pdf->Cell(
    80,
    6,
    txt('Signature :'),
    0,
    1,
    'L'
);

// Cachet (en bas à droite)
$pdf->Image(
    '../assets/img/cachet.png',
    125,                // X → bien à droite
    $pdf->GetY() + 2,   // Y → juste sous "Signature"
    70                  // largeur cachet
);

/* =========================
   SORTIE
========================= */
ob_end_clean();
$pdf->Output('I','rapport_rendez_vous_CHNDJ.pdf');
