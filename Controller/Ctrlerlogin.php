<?php
session_start(); // Démarrage de la session

// ==================================================
// 1. GESTION DE L’INACTIVITÉ DE LA SESSION
// ==================================================
// if (isset($_SESSION['username']) && isset($_SESSION['lastAction']) && isset($_SESSION['timeframe'])) {
    
    // Si le temps d'inactivité dépasse la durée autorisée
   // if ((time() - $_SESSION['lastAction']) > $_SESSION['timeframe']) {
    //    header('Location: ../views/logout.php'); // Déconnexion forcée
    //    exit;
   // } else {
        // Mise à jour de la dernière action
   //     $_SESSION['lastAction'] = time();
   // }
// }

// ==================================================
// 2. INCLUSION DES FICHIERS MODÈLES
// ==================================================
require_once '../Modele/database.php';
require_once '../Modele/databaseAgent.php';

// ==================================================
// 3. TRAITEMENT DE LA CONNEXION DE L’AGENT / ADMIN
// ==================================================
if (isset($_POST['login'])) {

    $username = trim($_POST['username']);
    $password = $_POST['pwd'] ?? '';


    $sql = "SELECT * FROM agent WHERE username = :username LIMIT 1";
    $stmt = prepare_executeSQL($sql, ['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "Identifiants incorrects";
    }
    elseif ($user['status'] == 0) {
        $error = "Votre compte est désactivé. Veuillez contacter l'administration.";
    }
    elseif (!password_verify($password, $user['password'])) {
        $error = "Identifiants incorrects";
    }
    else {
        // ✅ CONNEXION OK
        $_SESSION['logged_in'] = true;
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'];

        // Redirection selon rôle
        if ($user['role'] === 'admin') {
            header("Location: ../views/admin.php");
        } else {
            header("Location: ../views/agent.php");
        }
        exit;
    }
}


// ==================================================
// 4. MISE À JOUR DU PROFIL DE L’AGENT
// ==================================================
if (isset($_POST['updateagent'])) {

    try {
        $username        = $_POST['username'];
        $prenom_agent    = strtoupper($_POST['prenom_agent']);
        $nom_agent       = strtoupper($_POST['nom_agent']);
        $telephone_agent = $_POST['telephone_agent'];

        // Hash sécurisé du mot de passe
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $traitement = updateAgent(
            $username,
            $prenom_agent,
            $nom_agent,
            $password,
            $telephone_agent
        );

        if ($traitement) {
            header("Location: ../views/profile.php?update=success");
        } else {
            header("Location: ../views/profile.php?update=failed");
        }
        exit;

    } catch (Exception $e) {
        die("Erreur lors de la mise à jour du profil.");
    }
}
?>
