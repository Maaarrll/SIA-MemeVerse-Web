<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($post_id <= 0) {
    jsonResponse(['error' => 'Invalid post ID'], 400);
}

$sql = "
    SELECT
        p.id,
        p.title,
        p.description,
        p.image_path,
        p.created_at,

        u.id AS user_id,
        u.username,
        u.profile_pic,

        c.id AS category_id,
        c.name AS category_name,
        c.slug AS category_slug,

        COALESCE(up.upvotes, 0) AS upvotes,
        COALESCE(down.downvotes, 0) AS downvotes

    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN categories c ON p.category_id = c.id

    LEFT JOIN (
        SELECT post_id, COUNT(*) AS upvotes
        FROM votes
        WHERE vote = 1
        GROUP BY post_id
    ) up ON p.id = up.post_id

    LEFT JOIN (
        SELECT post_id, COUNT(*) AS downvotes
        FROM votes
        WHERE vote = -1
        GROUP BY post_id
    ) down ON p.id = down.post_id

    WHERE p.id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    jsonResponse(['error' => 'Prepare failed: ' . $conn->error], 500);
}

$stmt->bind_param('i', $post_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    jsonResponse(['error' => 'Post not found'], 404);
}

$post = $result->fetch_assoc();

$imagePath = $post['image_path'];

if (strpos($imagePath, '/') === 0) {
    $imagePath = substr($imagePath, 1);
}

$fullImageUrl = BASE_URL . '/' . $imagePath;

$userAvatar = null;

if (!empty($post['profile_pic'])) {
    $avatarPath = $post['profile_pic'];

    if (strpos($avatarPath, '/') === 0) {
        $avatarPath = substr($avatarPath, 1);
    }

    $userAvatar = BASE_URL . '/' . $avatarPath;
}

$user_vote = 0;

if (isLoggedIn()) {
    $voteStmt = $conn->prepare("
        SELECT vote 
        FROM votes 
        WHERE user_id = ? AND post_id = ?
        LIMIT 1
    ");

    if ($voteStmt) {
        $voteStmt->bind_param('ii', $_SESSION['user_id'], $post_id);
        $voteStmt->execute();

        $voteResult = $voteStmt->get_result();

        if ($voteRow = $voteResult->fetch_assoc()) {
            $user_vote = (int)$voteRow['vote'];
        }

        $voteStmt->close();
    }
}

$stmt->close();

jsonResponse([
    'post' => [
        'id' => (int)$post['id'],
        'title' => $post['title'],
        'description' => $post['description'],
        'image_path' => $fullImageUrl,
        'created_at' => $post['created_at'],

        'user' => [
            'id' => (int)$post['user_id'],
            'username' => $post['username'],
            'profile_pic' => $userAvatar
        ],

        'category' => [
            'id' => (int)$post['category_id'],
            'name' => $post['category_name'],
            'slug' => $post['category_slug']
        ],

        'upvotes' => (int)$post['upvotes'],
        'downvotes' => (int)$post['downvotes']
    ],
    'user_vote' => $user_vote
]);
?>