<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse([
        'success' => false,
        'error' => 'Authentication required'
    ], 401);
}

$current_user_id = $_SESSION['user_id'];
$with_user_id = isset($_GET['with']) ? (int)$_GET['with'] : 0;

if ($with_user_id <= 0) {
    jsonResponse([
        'success' => false,
        'error' => 'Invalid user'
    ], 400);
}

$readStmt = $conn->prepare("
    UPDATE messages 
    SET is_read = 1 
    WHERE sender_id = ? AND receiver_id = ?
");

$readStmt->bind_param('ii', $with_user_id, $current_user_id);
$readStmt->execute();

$stmt = $conn->prepare("
    SELECT 
        id,
        sender_id,
        receiver_id,
        message,
        is_read,
        created_at
    FROM messages
    WHERE 
        (sender_id = ? AND receiver_id = ?)
        OR
        (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");

if (!$stmt) {
    jsonResponse([
        'success' => false,
        'error' => 'Prepare failed: ' . $conn->error
    ], 500);
}

$stmt->bind_param(
    'iiii',
    $current_user_id,
    $with_user_id,
    $with_user_id,
    $current_user_id
);

$stmt->execute();
$result = $stmt->get_result();

$messages = [];

while ($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => (int)$row['id'],
        'sender_id' => (int)$row['sender_id'],
        'receiver_id' => (int)$row['receiver_id'],
        'message' => $row['message'],
        'is_read' => (int)$row['is_read'],
        'created_at' => $row['created_at'],
        'is_mine' => (int)$row['sender_id'] === (int)$current_user_id
    ];
}

jsonResponse([
    'success' => true,
    'messages' => $messages
]);
?>