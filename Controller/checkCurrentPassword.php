<?php
session_start();
require_once '../Modele/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_POST['password'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$password = $_POST['password'];
$userId = $_SESSION['user_id'];

$db = getConnection();

$stmt = $db->prepare("SELECT password FROM agent WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'ok']);
} else {
    echo json_encode(['status' => 'fail']);
}
