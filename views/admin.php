<?php
session_start();
require_once '../middlewares/auth.php';
require_once '../middlewares/csrf.php';
require_once '../modele/database.php';
require_once '../helpers/activity.php';

requireAuth();
requireRole(['super_admin', 'admin', 'medecin', 'agent']);


/* =========================
   FORCE CHANGEMENT MDP
========================= */
if (
    isset($_SESSION['user_id']) &&
    isset($_SESSION['role'])
) {
    $db = getConnection();

    $stmt = $db->prepare("SELECT must_change_password FROM agent WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (
        $user &&
        $user['must_change_password'] == 1 &&
        ($_GET['page'] ?? '') !== 'profile'
    ) {
        header("Location: admin.php?page=profile&tab=security&force=1");
        exit;
    }
}

/* =========================
   TRAITEMENT SERVICES (POST)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['page'] ?? '') === 'services') {

    verifyCsrfToken();
    $db = getConnection();

    /* AJOUT SERVICE */
    if (isset($_POST['add_service'])) {

        $designService = strtoupper(trim($_POST['designService']));
        $max_rdv_jour  = $_POST['max_rdv_jour'];
        $is_active     = $_POST['is_active'];
        $jours         = $_POST['jours'] ?? [];

        $codeService = substr(
            strtolower(preg_replace('/[^a-zA-Z]/', '', $designService)),
            0,
            6
        );

        try {
            $db->beginTransaction();

            $db->prepare(
                "INSERT INTO service (codeService, designService) VALUES (?, ?)"
            )->execute([$codeService, $designService]);

            $db->prepare(
                "INSERT INTO service_config (codeService, max_rdv_jour, is_active)
                 VALUES (?, ?, ?)"
            )->execute([$codeService, $max_rdv_jour, $is_active]);

            if ($jours) {
                $stmt = $db->prepare(
                    "INSERT INTO service_jour (codeService, jour) VALUES (?, ?)"
                );
                foreach ($jours as $jour) {
                    $stmt->execute([$codeService, $jour]);
                }
            }

            $db->commit();

            logActivity(
                $_SESSION['user_id'],
                "Ajout d’un service",
                "Service ajouté : ".$designService,
                $_SESSION['role']
            );

            $_SESSION['success'] = "Service ajouté avec succès";
            header("Location: admin.php?page=services");
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = "Erreur lors de l’ajout du service";
            header("Location: admin.php?page=services");
            exit;
        }
    }
}

/* ==================================================
   AFFICHAGE
================================================== */
require 'admin_layout.php';
