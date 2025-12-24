<?php
require_once '../modele/database.php';

$db = getConnection();

$db->prepare("
    DELETE FROM password_otp
    WHERE expires_at < NOW()
")->execute();
?>