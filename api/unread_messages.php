<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'count' => 0
]);
?>