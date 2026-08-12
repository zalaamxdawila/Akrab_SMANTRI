<?php
require 'config.php';
require 'helpers.php';
echo "ENABLED=" . (superadminFeatureEnabled() ? "TRUE" : "FALSE");
?>
