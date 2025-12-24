<?php

// On inclut le fichier de connexion à la base de données
require_once __DIR__ . '/database.php';

// ==============================================
// FONCTION : Vérifier l'existence d'un agent
// Par son nom d'utilisateur (username)
// Utilisée pour la CONNEXION
// ==============================================
function checkAgent($username) {

    // Requête SQL préparée pour éviter les injections SQL
    $sql_prepare = "SELECT * FROM agent WHERE username = :username";

    // Paramètre envoyé à la requête
    $parameters = array(
        'username' => $username
    );

    // Exécution de la requête avec les paramètres
    return prepare_executeSQL($sql_prepare, $parameters);
}

// ==============================================
// FONCTION : Mettre à jour le profil de l'agent
// (prénom, nom, téléphone, mot de passe)
// ==============================================
function updateAgent($username, $prenom_agent, $nom_agent, $password, $telephone_agent) {

    // Requête SQL de mise à jour
    $sql_prepare = "UPDATE agent 
                    SET prenom_agent = :prenom_agent,
                        nom_agent = :nom_agent,
                        telephone_agent = :telephone_agent,
                        password = :password
                    WHERE username = :username";

    // Paramètres de la requête
    $parameters = array(
        'prenom_agent'   => $prenom_agent,
        'nom_agent'      => $nom_agent,
        'password'       => $password,
        'telephone_agent'=> $telephone_agent,
        'username'       => $username
    );

    return prepare_executeSQL($sql_prepare, $parameters);
}

// ==============================================
// FONCTION : Diminuer le nombre de tentatives
// de connexion après un échec
// ==============================================
function decrementStatus($username, $status) {

    // On diminue le compteur
    $status--;

    // Requête SQL de mise à jour du statut
    $sql_prepare = "UPDATE agent SET status = :status WHERE username = :username";

    $parameters = array(
        'status'   => $status,
        'username' => $username
    );

    return prepare_executeSQL($sql_prepare, $parameters);
}

// ==============================================
// FONCTION : Réinitialiser les tentatives de connexion
// après une connexion réussie
// ==============================================
function incrementStatus($username) {

    $sql_prepare = "UPDATE agent SET status = 3 WHERE username = :username";

    $parameters = array(
        'username' => $username
    );

    return prepare_executeSQL($sql_prepare, $parameters);
}

// ==============================================
// ADMIN : Récupérer tous les agents
// ==============================================
function getAllAgents() {

    $sql = "SELECT 
                username,
                prenom_agent,
                nom_agent,
                email,
                telephone_agent,
                role,
                status,
                created_at
            FROM agent
            ORDER BY created_at DESC";

    return executeSQL($sql);
}



// ==============================================
// ADMIN : Activer / Désactiver un agent
// ==============================================
function toggleAgentStatus($username, $status) {

    $sql_prepare = "UPDATE agent SET status = :status WHERE username = :username";

    $parameters = [
        'status'   => $status,
        'username' => $username
    ];

    return prepare_executeSQL($sql_prepare, $parameters);
}

// ==============================================
// ADMIN : Récupérer un agent par username
// ==============================================
function getAgentByUsername($username) {

    $sql_prepare = "SELECT * FROM agent WHERE username = :username";

    $parameters = [
        'username' => $username
    ];

    return prepare_executeSQL($sql_prepare, $parameters);
}



// ==============================================
// AJOUTER UN AGENT
// ==============================================
function addAgent($username, $email, $prenom, $nom, $telephone, $role) {

    // Mot de passe temporaire
    $password = password_hash("agent123", PASSWORD_DEFAULT);

    $sql = "INSERT INTO agent 
            (username, email, prenom_agent, nom_agent, telephone_agent, password, role, status)
            VALUES 
            (:username, :email, :prenom, :nom, :telephone, :password, :role, 1)";

    $params = [
        'username'  => $username,
        'email'     => $email,
        'prenom'    => $prenom,
        'nom'       => $nom,
        'telephone' => $telephone,
        'password'  => $password,
        'role'      => $role
    ];

    return prepare_executeSQL($sql, $params);
}


require_once 'database.php';

function getAgentsPaginated($search, $role, $limit, $offset)
{
    $db = getConnection(); // ✅ OBLIGATOIRE

    $sql = "SELECT * FROM agent WHERE 1=1";
    $params = [];

    if (!empty($search)) {
    $sql .= " AND (
        email LIKE :search_email
        OR username LIKE :search_username
        OR prenom_agent LIKE :search_prenom
        OR nom_agent LIKE :search_nom
    )";

    $params['search_email']    = "%$search%";
    $params['search_username'] = "%$search%";
    $params['search_prenom']   = "%$search%";
    $params['search_nom']      = "%$search%";
}



    if (!empty($role)) {
        $sql .= " AND role = :role";
        $params['role'] = $role;
    }

    $sql .= " LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(":$key", $value);
    }

    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    // var_dump($sql, $params); exit;


    $stmt->execute();

    return $stmt->fetchAll();
}



?>
