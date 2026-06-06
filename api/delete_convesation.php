<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse([
        'success' => false,
        'error' => 'Authentication required'
    ], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse([
        'success' => false,
        'error' => 'Method not allowed'
    ], 405);
}

$current_user_id = $_SESSION['user_id'];
$other_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($other_user_id <= 0) {
    jsonResponse([
        'success' => false,
        'error' => 'Invalid user'
    ], 400);
}

$stmt = $conn->prepare("
    DELETE FROM messages
    WHERE 
        (sender_id = ? AND receiver_id = ?)
        OR
        (sender_id = ? AND receiver_id = ?)
");

$stmt->bind_param(
    'iiii',
    $current_user_id,
    $other_user_id,
    $other_user_id,
    $current_user_id
);

if ($stmt->execute()) {
    jsonResponse([
        'success' => true,
        'message' => 'Conversation deleted'
    ]);
}

jsonResponse([
    'success' => false,
    'error' => 'Failed to delete conversation'
], 500);
?>