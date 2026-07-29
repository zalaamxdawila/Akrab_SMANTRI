<?php
require_once __DIR__ . '/operations_list.php';
renderOperationsList(
    $pdo, $_SESSION, 'Artikel dan Saran Edukasi',
    ['article' => 'Artikel', 'advice' => 'Saran anemia'],
    'education_action.php'
);
