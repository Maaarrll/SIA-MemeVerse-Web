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

$comment_id = isset($input['comment_id']) ? (int)$input['comment_id'] : 0;

if ($comment_id <= 0) {
    jsonResponse([
        'success' => false,
        'error' => 'Invalid comment ID'
    ], 400);
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT user_id FROM comments WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $comment_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    jsonResponse([
        'success' => false,
        'error' => 'Comment not found'
    ], 404);
}

$comment = $result->fetch_assoc();

if ((int)$comment['user_id'] !== (int)$user_id) {
    jsonResponse([
        'success' => false,
        'error' => 'You can only delete your own comment'
    ], 403);
}

$delete = $conn->prepare("DELETE FROM comments WHERE id = ?");
$delete->bind_param('i', $comment_id);

if ($delete->execute()) {
    jsonResponse([
        'success' => true,
        'message' => 'Comment deleted'
    ]);
}

jsonResponse([
    'success' => false,
    'error' => 'Failed to delete comment'
], 500);
?>