<?php

// ==============================
// FONCTION DE CONNEXION À LA BASE DE DONNÉES
// ==============================
function getConnection() {
    try {
        $db = new PDO(
            "mysql:host=127.0.0.1;port=3306;dbname=gestion_rendezvs;charset=utf8mb4",
            "root",
            "",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );

        return $db;

    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
}


// ==============================
// FONCTION POUR EXÉCUTER UNE REQUÊTE SQL SIMPLE
// (SELECT sans paramètres)
// ==============================
function executeSQL($sql) {
    // On récupère la connexion
    $db = getConnection();

    // On exécute directement la requête SQL
    return $db->query($sql);
}

// ==============================
// FONCTION POUR EXÉCUTER UNE REQUÊTE SQL PRÉPARÉE
// (INSERT, UPDATE, DELETE, SELECT avec paramètres)
// ==============================
function prepare_executeSQL($sql, $parameters = []) {
    // On récupère la connexion
    $db = getConnection();

    // On prépare la requête SQL
    $stmt = $db->prepare($sql);

    // On exécute la requête avec les paramètres
    $stmt->execute($parameters);

    // On retourne l'objet de la requête
    return $stmt;
}

?>
