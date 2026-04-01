<?php
require_once '../modele/database.php';

$db = getConnection();

// ✅ FIX 3 : Fonction centralisée de normalisation — cohérente avec accueil.php
// On normalise en uppercase SANS conserver les espaces, pour matcher les clés du tableau
function normalizeServiceName(string $name): string {
    $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    // ✅ FIX 4 : iconv peut retourner false, on sécurise avec fallback
    if ($normalized === false) $normalized = $name;
    $normalized = strtoupper(trim($normalized));
    // On retire tout sauf lettres, chiffres et espaces (les espaces sont conservés pour les clés du tableau)
    $normalized = preg_replace('/[^A-Z0-9 ]/', '', $normalized);
    // On normalise les espaces multiples en un seul
    $normalized = preg_replace('/\s+/', ' ', $normalized);
    return $normalized;
}

// descriptions automatiques
// ✅ FIX 3 : Clés normalisées avec espaces simples (cohérent avec normalizeServiceName)
$descriptions = [

    "ANAPATH"              => "Analyse anatomopathologique des tissus pour diagnostiquer les maladies et anomalies cellulaires.",
    "ANDROLOGIE"           => "Prise en charge des troubles de l'appareil reproducteur masculin et de la fertilité.",
    "CARDIOLOGIE"          => "Consultation spécialisée pour les maladies du cœur et du système cardiovasculaire.",
    "CHAMP VISUEL"         => "Examen permettant d'évaluer la vision périphérique et détecter certaines pathologies oculaires.",
    "CHIRURGIE GENERALE"   => "Prise en charge chirurgicale des pathologies abdominales et générales.",
    "CURIE"                => "Service spécialisé en oncologie et traitement des cancers.",
    "DERMATOLOGIE"         => "Diagnostic et traitement des maladies de la peau, des cheveux et des ongles.",
    "DIETETIQUE"           => "Suivi nutritionnel et conseils alimentaires adaptés aux besoins du patient.",
    "DOULEUR"              => "Prise en charge des douleurs aiguës et chroniques avec approche spécialisée.",
    "ENDOSCOPIE BLOC"      => "Exploration interne des organes à l'aide d'un endoscope en bloc opératoire.",
    "ETT ADULTE"           => "Échocardiographie transthoracique pour l'exploration cardiaque chez l'adulte.",
    "ETT ENFANT"           => "Échocardiographie adaptée à l'exploration cardiaque chez l'enfant.",
    "ENDOSCOPIE DIGESTIVE" => "Examen permettant d'explorer le système digestif et détecter des anomalies.",
    "GASTRO"               => "Prise en charge des maladies du système digestif.",
    "GASTRO SOIR"          => "Consultations digestives en horaires de soirée pour plus de flexibilité.",
    "HEMATOLOGIE"          => "Diagnostic et traitement des maladies du sang.",
    "KADIA"                => "Consultation spécialisée selon les protocoles médicaux du service Kadia.",
    "MALADIE INFECTIEUSE"  => "Prise en charge des infections et maladies transmissibles.",
    "NEPHROLOGIE"          => "Suivi des maladies rénales et troubles du rein.",
    "NEUROLOGIE"           => "Diagnostic et traitement des maladies du système nerveux.",
    "ONCO MEDICALE"        => "Traitement médical des cancers par chimiothérapie et suivi oncologique.",
    "ONCOLOGIE"            => "Prise en charge globale des patients atteints de cancer.",
    "OPHTALMOLOGIE"        => "Diagnostic et traitement des troubles de la vision et des yeux.",
    "OPHTALMOLOGIE SOIR"   => "Consultations ophtalmologiques en horaires de soirée.",
    "ORL"                  => "Traitement des maladies de l'oreille, du nez et de la gorge.",
    "ORTHOPEDIE"           => "Prise en charge des troubles des os, articulations et muscles.",
    "PACHYMETRIE"          => "Mesure de l'épaisseur de la cornée pour le suivi oculaire.",
    "PEDIATRIE"            => "Suivi médical des nourrissons, enfants et adolescents.",
    "PNEUMOLOGIE"          => "Diagnostic et traitement des maladies respiratoires.",
    "RHUMATOLOGIE"         => "Prise en charge des maladies des articulations et des rhumatismes.",
    "TENS"                 => "Traitement de la douleur par stimulation électrique transcutanée.",
    "TESTT"                => "Service de test et de validation médicale.",
    "UROLOGIE"             => "Traitement des troubles urinaires et génitaux.",
    "UROLOGIE SOIR"        => "Consultations urologiques en horaires de soirée.",

];

// récupérer services
$services = $db->query("SELECT codeService, designService FROM service")->fetchAll();

$updated = 0;
$fallback = 0;

foreach ($services as $service) {

    // ✅ FIX 3 : Utilisation de la fonction centralisée
    $nom = normalizeServiceName($service['designService']);

    $description = $descriptions[$nom] ?? null;

    if ($description === null) {
        // ✅ Meilleur debug : on log les services non trouvés dans le tableau
        error_log("[seed_descriptions] Clé non trouvée pour : '{$nom}' (original: '{$service['designService']}')");
        $description = "Service médical spécialisé avec prise en charge adaptée aux patients.";
        $fallback++;
    } else {
        $updated++;
    }

    $stmt = $db->prepare("UPDATE service SET description = :desc WHERE codeService = :code");
    $stmt->execute([
        'desc' => $description,
        'code' => $service['codeService']
    ]);
}

echo "Descriptions ajoutées avec succès. ({$updated} matchées, {$fallback} en fallback)";
