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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse([
        'success' => false,
        'error' => 'Invalid JSON'
    ], 400);
}

$sender_id = $_SESSION['user_id'];
$receiver_id = isset($input['receiver_id']) ? (int)$input['receiver_id'] : 0;
$message = trim($input['message'] ?? '');

if ($receiver_id <= 0) {
    jsonResponse([
        'success' => false,
        'error' => 'Invalid receiver'
    ], 400);
}

if (empty($message)) {
    jsonResponse([
        'success' => false,
        'error' => 'Message cannot be empty'
    ], 400);
}

if ($sender_id == $receiver_id) {
    jsonResponse([
        'success' => false,
        'error' => 'You cannot message yourself'
    ], 400);
}

$check = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
$check->bind_param('i', $receiver_id);
$check->execute();

$result = $check->get_result();

if ($result->num_rows === 0) {
    jsonResponse([
        'success' => false,
        'error' => 'User not found'
    ], 404);
}

$stmt = $conn->prepare("
    INSERT INTO messages (sender_id, receiver_id, message)
    VALUES (?, ?, ?)
");

if (!$stmt) {
    jsonResponse([
        'success' => false,
        'error' => 'Prepare failed: ' . $conn->error
    ], 500);
}

$stmt->bind_param('iis', $sender_id, $receiver_id, $message);

if ($stmt->execute()) {
    jsonResponse([
        'success' => true,
        'message' => 'Message sent',
        'message_id' => $stmt->insert_id
    ]);
}

jsonResponse([
    'success' => false,
    'error' => 'Failed to send message'
], 500);
?>