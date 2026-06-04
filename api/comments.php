<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

function cleanJson($data, $statusCode = 200) {
    if (ob_get_length()) {
        ob_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;

    if ($post_id <= 0) {
        cleanJson(['error' => 'Invalid post ID'], 400);
    }

    $stmt = $conn->prepare("
        SELECT 
            c.id,
            c.content,
            c.created_at,
            u.id AS user_id,
            u.username
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id = ?
        ORDER BY c.created_at DESC
    ");

    if (!$stmt) {
        cleanJson(['error' => 'Prepare failed: ' . $conn->error], 500);
    }

    $stmt->bind_param('i', $post_id);

    if (!$stmt->execute()) {
        cleanJson(['error' => 'Execute failed: ' . $stmt->error], 500);
    }

    $result = $stmt->get_result();
    $comments = [];

    while ($row = $result->fetch_assoc()) {
        $comments[] = [
            'id' => (int)$row['id'],
            'content' => $row['content'],
            'created_at' => $row['created_at'],
            'user_id' => (int)$row['user_id'],
            'username' => $row['username']
        ];
    }

    cleanJson([
        'comments' => $comments
    ]);
}

if ($method === 'POST') {
    if (!isLoggedIn()) {
        cleanJson(['error' => 'Authentication required'], 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        cleanJson(['error' => 'Invalid JSON'], 400);
    }

    $post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
    $content = isset($input['content']) ? trim($input['content']) : '';

    if ($post_id <= 0 || empty($content)) {
        cleanJson(['error' => 'Post ID and content required'], 400);
    }

    $stmt = $conn->prepare("
        INSERT INTO comments (user_id, post_id, content)
        VALUES (?, ?, ?)
    ");

    if (!$stmt) {
        cleanJson(['error' => 'Prepare failed: ' . $conn->error], 500);
    }

    $user_id = $_SESSION['user_id'];
    $stmt->bind_param('iis', $user_id, $post_id, $content);

    if ($stmt->execute()) {
        cleanJson([
            'success' => true,
            'message' => 'Comment added',
            'comment_id' => $stmt->insert_id
        ], 201);
    }

    cleanJson(['error' => 'Failed to add comment: ' . $stmt->error], 500);
}

cleanJson(['error' => 'Method not allowed'], 405);
?>