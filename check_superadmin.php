<?php
require 'config.php';
$stmt = $pdo->prepare("SELECT id, username, password_hash, nama FROM users WHERE role = 'superadmin' LIMIT 1");
$stmt->execute();
$superadmin = $stmt->fetch();
var_dump($superadmin['id']);
?>
