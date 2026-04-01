<?php
date_default_timezone_set('Africa/Dakar');

require_once '../middlewares/auth.php';
require_once '../modele/database.php';
require_once __DIR__ . '/../helpers/activity.php';
require_once '../libs/fpdf/fpdf.php';

requireAuth('super_admin' , 'admin' , 'agent', 'medecin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Accès refusé');
}

$db = getConnection();

/* =========================
   LOG ACTIVITÉ (EXPORT)
========================= */
logActivity(
    $_SESSION['user_id'],
    'Export PDF',
    'Export de l’historique des activités',
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
   SESSION
========================= */
$userId = (int) $_SESSION['user_id'];
$role   = $_SESSION['role'];

/* =========================
   FILTRES (GET)
========================= */
$q        = trim($_GET['q'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to'] ?? '';

/* =========================
   SQL
========================= */
$sql = "
SELECT
    al.created_at,
    al.action,
    al.description,
    al.ip_address,
    al.role,
    a.prenom_agent,
    a.nom_agent
FROM activity_logs al
JOIN agent a ON a.id = al.user_id
WHERE 1=1
";

$params = [];

if ($role === 'admin') {
    $sql .= " AND (al.user_id = :uid OR al.role = 'agent')";
    $params['uid'] = $userId;
} else {
    $sql .= " AND al.user_id = :uid";
    $params['uid'] = $userId;
}

if ($q !== '') {
    $sql .= " AND (
        a.nom_agent LIKE :q
        OR a.prenom_agent LIKE :q
        OR al.action LIKE :q
    )";
    $params['q'] = "%$q%";
}

if ($dateFrom !== '') {
    $sql .= " AND al.created_at >= :date_from";
    $params['date_from'] = $dateFrom . ' 00:00:00';
}

if ($dateTo !== '') {
    $sql .= " AND al.created_at <= :date_to";
    $params['date_to'] = $dateTo . ' 23:59:59';
}

$sql .= " ORDER BY al.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   PDF CLASS
========================= */
class PDF extends FPDF
{
    function Header()
    {
        $this->Image('../assets/img/logo.png', 10, 10, 22);

        $this->SetFont('Arial','B',14);
        $this->Cell(0,7, txt('CENTRE HOSPITALIER NATIONAL DALAL JAMM'), 0, 1, 'C');

        $this->SetFont('Arial','',12);
        $this->Cell(0,7, txt('Historique des activités'), 0, 1, 'C');

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
ob_start();

$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();

/* =========================
   TABLE HEADER
========================= */
$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(41,128,185);
$pdf->SetTextColor(255);

$pdf->Cell(35, 8, txt('Date'), 1, 0, 'C', true);
$pdf->Cell(50, 8, txt('Utilisateur'), 1, 0, 'C', true);
$pdf->Cell(25, 8, txt('Rôle'), 1, 0, 'C', true);
$pdf->Cell(45, 8, txt('Action'), 1, 0, 'C', true);
$pdf->Cell(100, 8, txt('Description'), 1, 0, 'C', true);
$pdf->Cell(30, 8, txt('IP'), 1, 1, 'C', true);

$pdf->SetFont('Arial','',8);
$pdf->SetTextColor(0);

/* =========================
   DATA
========================= */
foreach ($activities as $a) {

    $pdf->Cell(35, 7, date('d/m/Y H:i', strtotime($a['created_at'])), 1);
    $pdf->Cell(50, 7, txt($a['prenom_agent'].' '.$a['nom_agent']), 1);
    $pdf->Cell(25, 7, ucfirst($a['role']), 1);
    $pdf->Cell(45, 7, txt($a['action']), 1);

    $desc = $a['description'] ?? '';
    $pdf->Cell(100, 7, txt(mb_strimwidth($desc, 0, 90, '...')), 1);

    $pdf->Cell(30, 7, $a['ip_address'], 1, 1);
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
ob_end_clean();
$pdf->Output('I', 'historique_activites_CHNDJ.pdf');
exit;
