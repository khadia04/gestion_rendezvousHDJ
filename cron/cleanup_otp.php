<?php
require_once '../Modele/database.php';

$db = getConnection();
$db->prepare("
    DELETE FROM password_otp
    WHERE expires_at < NOW()
")->execute();
