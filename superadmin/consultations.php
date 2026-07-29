<?php
require_once __DIR__ . '/operations_list.php';
renderOperationsList(
    $pdo, $_SESSION, 'Konsultasi dan Balasan',
    ['consultation' => 'Konsultasi', 'reply' => 'Balasan'],
    'consultation_action.php'
);
