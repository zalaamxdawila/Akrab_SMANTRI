<?php
require_once __DIR__ . '/operations_list.php';
renderOperationsList(
    $pdo, $_SESSION, 'Jadwal dan Log Notifikasi',
    ['schedule' => 'Jadwal', 'delivery' => 'Log pengiriman'],
    'notification_action.php'
);
