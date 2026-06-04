<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['error' => 'Invalid JSON'], 400);
}

$post_id = isset($input['post_id']) ? (int)$input['post_id'] : 0;
$vote = isset($input['vote']) ? (int)$input['vote'] : 0;

if ($post_id <= 0) {
    jsonResponse(['error' => 'Invalid post ID'], 400);
}

if (!in_array($vote, [-1, 0, 1])) {
    jsonResponse(['error' => 'Invalid vote value'], 400);
}

$user_id = (int)$_SESSION['user_id'];

$checkPost = $conn->prepare("SELECT id FROM posts WHERE id = ?");
$checkPost->bind_param('i', $post_id);
$checkPost->execute();
$checkPost->store_result();

if ($checkPost->num_rows === 0) {
    jsonResponse(['error' => 'Post not found'], 404);
}

$checkPost->close();

if ($vote === 0) {
    $stmt = $conn->prepare("DELETE FROM votes WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param('ii', $user_id, $post_id);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("
        INSERT INTO votes (user_id, post_id, vote)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE vote = VALUES(vote)
    ");

    $stmt->bind_param('iii', $user_id, $post_id, $vote);
    $stmt->execute();
    $stmt->close();
}

$countStmt = $conn->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN vote = 1 THEN 1 ELSE 0 END), 0) AS upvotes,
        COALESCE(SUM(CASE WHEN vote = -1 THEN 1 ELSE 0 END), 0) AS downvotes
    FROM votes
    WHERE post_id = ?
");

$countStmt->bind_param('i', $post_id);
$countStmt->execute();

$result = $countStmt->get_result();
$counts = $result->fetch_assoc();

$countStmt->close();

jsonResponse([
    'success' => true,
    'upvotes' => (int)$counts['upvotes'],
    'downvotes' => (int)$counts['downvotes'],
    'user_vote' => $vote
]);
?>