<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 10;
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
$types = '';

if (isset($_GET['category_slug']) && !empty($_GET['category_slug'])) {
    $where[] = "c.slug = ?";
    $params[] = $_GET['category_slug'];
    $types .= 's';
}

if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $where[] = "p.user_id = ?";
    $params[] = (int)$_GET['user_id'];
    $types .= 'i';
}

$whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

$sql = "
    SELECT 
        p.id,
        p.title,
        p.description,
        p.image_path,
        p.created_at,

        u.id AS user_id,
        u.username,
        u.nickname,
        u.profile_pic,

        c.id AS category_id,
        c.name AS category_name,
        c.slug AS category_slug,

        COALESCE(up.upvotes, 0) AS upvotes,
        COALESCE(down.downvotes, 0) AS downvotes,
        COALESCE(com.comment_count, 0) AS comment_count

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

    LEFT JOIN (
        SELECT post_id, COUNT(*) AS comment_count
        FROM comments
        GROUP BY post_id
    ) com ON p.id = com.post_id

    $whereClause
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
";

$queryParams = $params;
$queryTypes = $types;

$queryParams[] = $limit;
$queryParams[] = $offset;
$queryTypes .= 'ii';

$stmt = $conn->prepare($sql);

if (!$stmt) {
    jsonResponse([
        'error' => 'Prepare failed: ' . $conn->error
    ], 500);
}

$stmt->bind_param($queryTypes, ...$queryParams);
$stmt->execute();

$result = $stmt->get_result();
$posts = [];

while ($row = $result->fetch_assoc()) {
    $imagePath = $row['image_path'];

    if (strpos($imagePath, '/') === 0) {
        $imagePath = substr($imagePath, 1);
    }

    $avatarPath = null;

    if (!empty($row['profile_pic'])) {
        $avatarPath = $row['profile_pic'];

        if (strpos($avatarPath, '/') === 0) {
            $avatarPath = substr($avatarPath, 1);
        }
    }

    $voteScore = (int)$row['upvotes'] - (int)$row['downvotes'];

    $posts[] = [
        // Android-friendly flat fields
        'id' => (int)$row['id'],
        'user_id' => (int)$row['user_id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'image_path' => $imagePath,
        'created_at' => $row['created_at'],

        'username' => $row['username'],
        'nickname' => $row['nickname'],
        'avatar_url' => $avatarPath,

        'category_id' => (int)$row['category_id'],
        'category_name' => $row['category_name'],
        'category_slug' => $row['category_slug'],

        'vote_score' => $voteScore,
        'comment_count' => (int)$row['comment_count'],
        'user_vote' => 0,

        // Web-friendly nested fields
        'user' => [
            'id' => (int)$row['user_id'],
            'username' => $row['username'],
            'profile_pic' => $avatarPath ? BASE_URL . '/' . $avatarPath : null
        ],

        'category' => [
            'id' => (int)$row['category_id'],
            'name' => $row['category_name'],
            'slug' => $row['category_slug']
        ],

        'upvotes' => (int)$row['upvotes'],
        'downvotes' => (int)$row['downvotes'],
        'comments' => (int)$row['comment_count']
    ];
}

$stmt->close();

$countSql = "
    SELECT COUNT(*) AS total
    FROM posts p
    JOIN categories c ON p.category_id = c.id
    $whereClause
";

$countStmt = $conn->prepare($countSql);

if (!$countStmt) {
    jsonResponse([
        'error' => 'Count prepare failed: ' . $conn->error
    ], 500);
}

if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}

$countStmt->execute();
$totalRow = $countStmt->get_result()->fetch_assoc();

$total = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
$totalPages = $limit > 0 ? ceil($total / $limit) : 0;

$countStmt->close();

jsonResponse([
    'posts' => $posts,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_posts' => $total,
        'has_more' => $page < $totalPages
    ]
]);
?>