<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse([
        'success' => true,
        'count' => 0
    ]);
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM messages 
    WHERE receiver_id = ? AND is_read = 0
");

$stmt->bind_param('i', $user_id);
$stmt->execute();

$result = $stmt->get_result();
$row = $result->fetch_assoc();

jsonResponse([
    'success' => true,
    'count' => (int)$row['total']
]);
?>