<?php
date_default_timezone_set('Africa/Dakar');

require_once '../middlewares/auth.php';
require_once '../modele/database.php';
require_once '../helpers/activity.php';
require_once '../libs/fpdf/fpdf.php';

requireAuth('super_admin' , 'admin');

$db = getConnection();

/* =========================
   LOG ACTIVITÉ
========================= */
logActivity(
    $_SESSION['user_id'],
    'Export PDF',
    'Export des rendez-vous',
    $_SESSION['role']
);

/* =========================
   UTF-8 SAFE
========================= */
function txt($str)
{
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $str);
}

/* =========================
   FILTRES (IDENTIQUES PAGE RDV)
========================= */
$service = $_GET['service'] ?? '';
$periode = $_GET['periode'] ?? 'jour';
$date    = $_GET['date'] ?? date('Y-m-d');

// =========================
// RÉSUMÉ DES FILTRES
// =========================
$labelService = 'Tous les services';
if ($service) {
    $stmtSrv = $db->prepare("SELECT designService FROM service WHERE codeService = ?");
    $stmtSrv->execute([$service]);
    $labelService = $stmtSrv->fetchColumn() ?: $service;
}

$labelPeriode = match ($periode) {
    'jour'  => 'Jour',
    'mois'  => 'Mois',
    'annee' => 'Année',
    default => 'Jour'
};

$labelDate = date('d/m/Y', strtotime($date));


/* =========================
   SQL BASE (IDENTIQUE PAGE RDV)
========================= */
$sql = "
SELECT *
FROM (
    SELECT
        r.numeroDossierPatient AS dossier,
        CONCAT(p.prenomPatient,' ',p.nomPatient) AS patient,
        p.telephonePatient AS telephone,
        s.designService AS service,
        s.codeService AS codeService,
        r.dateDemande,
        r.dateRvServ
    FROM rendezvs r
    JOIN patient p ON p.numeroDossierPatient = r.numeroDossierPatient
    JOIN service s ON s.codeService = r.codeService

    UNION ALL

    SELECT
        'Sans index',
        CONCAT(n.prenomPatient,' ',n.nomPatient),
        n.telephonePatient,
        s.designService,
        s.codeService,
        n.dateDemande,
        n.dateDisponible AS dateRvServ
    FROM patientnoindex n
    JOIN service s ON s.codeService = n.codeService
) t
WHERE 1=1
";

$params = [];

/* =========================
   FILTRES
========================= */
if ($service) {
    $sql .= " AND t.codeService = ?";
    $params[] = $service;
}

if ($periode === 'jour') {
    $sql .= " AND t.dateRvServ >= ? AND t.dateRvServ < DATE_ADD(?, INTERVAL 1 DAY)";
    $params[] = $date;
    $params[] = $date;
} elseif ($periode === 'mois') {
    $sql .= " AND MONTH(t.dateRvServ)=MONTH(?) AND YEAR(t.dateRvServ)=YEAR(?)";
    $params[] = $date;
    $params[] = $date;
} elseif ($periode === 'annee') {
    $sql .= " AND YEAR(t.dateRvServ)=YEAR(?)";
    $params[] = $date;
}

/* =========================
   TRI (LOGIQUE MÉTIER)
========================= */
if ($periode === 'jour') {
    // 🔥 File d’attente
    $sql .= " ORDER BY t.dateDemande ASC";
} else {
    // 🔥 Planning
    $sql .= " ORDER BY t.dateRvServ ASC";
}

/* =========================
   EXECUTION
========================= */
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rdvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   PDF CLASS (STYLE ACTIVITÉS)
========================= */
class PDF extends FPDF
{
    function Header()
    {
        $this->Image('../assets/img/logo.png', 10, 10, 22);

        $this->SetFont('Arial','B',14);
        $this->Cell(0,7, txt('CENTRE HOSPITALIER NATIONAL DALAL JAMM'), 0, 1, 'C');

        $this->SetFont('Arial','',12);
        $this->Cell(0,7, txt('Liste des rendez-vous'), 0, 1, 'C');

        $this->Ln(3);
        $this->SetFont('Arial','',9);
        $this->SetTextColor(60,60,60);

        $this->Cell(0,6, txt(''), 0, 1, 'C');


        $this->Ln(4);
        $this->SetDrawColor(180,180,180);
        $this->Line(10, 38, 285, 38);
        $this->Ln(12);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(
            0,
            10,
            txt('Document généré automatiquement - CHNDJ | ')
            .date('d/m/Y H:i')
            .' | Page '.$this->PageNo().'/{nb}',
            0,
            0,
            'C'
        );
    }
}

/* =========================
   CREATE PDF
========================= */
ob_end_clean();

$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();

$pdf->SetFont('Arial','',9);
$pdf->SetTextColor(50,50,50);

$pdf->Cell(0,6, txt('Service : '.$labelService), 0, 1, 'L');
$pdf->Cell(0,6, txt('Période : '.$labelPeriode), 0, 1, 'L');
$pdf->Cell(0,6, txt('Date : '.$labelDate), 0, 1, 'L');

$pdf->Ln(4);


/* =========================
   TABLE HEADER
========================= */
$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(41,128,185);
$pdf->SetTextColor(255);

$pdf->Cell(30, 8, txt('Dossier'), 1, 0, 'C', true);
$pdf->Cell(60, 8, txt('Patient'), 1, 0, 'C', true);
$pdf->Cell(40, 8, txt('Téléphone'), 1, 0, 'C', true);
$pdf->Cell(45, 8, txt('Service'), 1, 0, 'C', true);
$pdf->Cell(35, 8, txt('Date demande'), 1, 0, 'C', true);
$pdf->Cell(35, 8, txt('Date RDV'), 1, 1, 'C', true);

$pdf->SetFont('Arial','',8);
$pdf->SetTextColor(0);

/* =========================
   DATA
========================= */
$rang = 1;

foreach ($rdvs as $r) {
    $pdf->Cell(30, 7, txt($r['dossier']), 1);
    $pdf->Cell(60, 7, txt($r['patient']), 1);
    $pdf->Cell(40, 7, txt($r['telephone']), 1);
    $pdf->Cell(45, 7, txt($r['service']), 1);
    $pdf->Cell(35, 7, date('d/m/Y', strtotime($r['dateDemande'])), 1);
    $pdf->Cell(35, 7, date('d/m/Y', strtotime($r['dateRvServ'])), 1, 1);

    $rang++;
}

/* =========================
   SIGNATURE
========================= */
$pdf->Ln(15);
$pdf->SetX(200);
$pdf->SetFont('Arial','',10);
$pdf->Cell(80, 6, txt('Fait à Dakar, le '.date('d/m/Y')), 0, 1, 'L');

$pdf->Ln(6);
$pdf->SetX(200);
$pdf->SetFont('Arial','B',11);
$pdf->Cell(80, 6, txt('Le Responsable Informatique'), 0, 1, 'L');

$pdf->Ln(10);
$pdf->SetX(200);
$pdf->SetFont('Arial','',10);
$pdf->Cell(80, 6, txt('Signature :'), 0, 1, 'L');

/* =========================
   OUTPUT
========================= */
$pdf->Output('I', 'liste_rendezvous_CHNDJ.pdf');
exit;
