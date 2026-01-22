<?php
require_once __DIR__ . '/../security_headers.php';

session_start(); // 🔥 TOUJOURS EN PREMIER

require_once '../middlewares/auth.php';
require_once '../middlewares/csrf.php';
require_once '../modele/database.php';

// ===============================
// AUTH
// ===============================
requireAuth('admin');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: ../index.php?session=expired");
    exit;
}

// ===============================
// CSRF TOKEN
// ===============================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ===============================
// ROUTING
// ===============================
$page = $_GET['page'] ?? 'dashboard';

// ===============================
// TRAITEMENT POST PROFIL
// ===============================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['update_profile']) &&
    $page === 'profile'
) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Session invalide.";
        header("Location: admin.php?page=profile");
        exit;
    }

    $db = getConnection();
    $userId = $_SESSION['user_id'];

    $prenom = trim($_POST['prenom']);
    $nom = trim($_POST['nom']);
    $tel = trim($_POST['telephone']);

    if (!$prenom || !$nom) {
        $_SESSION['error'] = "Nom et prénom obligatoires.";
        header("Location: admin.php?page=profile");
        exit;
    }

    $photoName = null;
    if (!empty($_FILES['photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png'])) {
            $_SESSION['error'] = "Format image invalide.";
            header("Location: admin.php?page=profile");
            exit;
        }

        $photoName = "admin_$userId.$ext";
        move_uploaded_file(
            $_FILES['photo']['tmp_name'],
            "../assets/img/$photoName"
        );
    }

    $sql = "
        UPDATE agent
        SET prenom_agent = :prenom,
            nom_agent = :nom,
            telephone_agent = :tel
            ".($photoName ? ", photo = :photo" : "")."
        WHERE id = :id
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'prenom' => $prenom,
        'nom' => $nom,
        'tel' => $tel,
        'photo' => $photoName,
        'id' => $userId
    ]);

    $_SESSION['success'] = "Profil mis à jour avec succès.";
    header("Location: admin.php?page=profile");
    exit;
}

// ===============================
// AFFICHAGE (HTML)
// ===============================
require 'admin_layout.php';
