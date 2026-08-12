<?php
require_once 'config.php';
destroySessionCompletely($appEnvironment);
header("Location: " . BASE_URL . "login.php");
exit;
?>
