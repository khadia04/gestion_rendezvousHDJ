<?php
require_once "../modele/database.php";

header('Content-Type: application/json');

// 1️⃣ Total patients
$patients = executesql("SELECT COUNT(*) AS total FROM patient")->fetch()['total'];

// 2️⃣ Total agents
$agents = executesql("SELECT COUNT(*) AS total FROM agent")->fetch()['total'];

// 3️⃣ RDV par mois (12 derniers mois)
$rdvParMois = executesql("
    SELECT 
        MONTH(dateRvServ) AS mois, 
        COUNT(*) AS total
    FROM rendezvs
    WHERE dateRvServ IS NOT NULL
    GROUP BY MONTH(dateRvServ)
    ORDER BY mois ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 4️⃣ RDV par service
$rdvParService = executesql("
    SELECT 
        s.nomService AS service,
        COUNT(r.idRv) AS total
    FROM rendezvs r
    INNER JOIN service s ON s.codeService = r.codeService
    GROUP BY r.codeService
")->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "patients" => $patients,
    "agents" => $agents,
    "rdvParMois" => $rdvParMois,
    "rdvParService" => $rdvParService
]);
