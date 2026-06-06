<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse([
        'success' => false,
        'error' => 'Authentication required'
    ], 401);
}

$user_id = $_SESSION['user_id'];

$sql = "
    SELECT 
        u.id AS user_id,
        u.username,
        u.nickname,
        u.profile_pic,

        m.message AS last_message,
        m.created_at AS last_time,

        (
            SELECT COUNT(*) 
            FROM messages 
            WHERE sender_id = u.id 
            AND receiver_id = ? 
            AND is_read = 0
        ) AS unread_count

    FROM users u

    JOIN messages m 
        ON m.id = (
            SELECT id 
            FROM messages 
            WHERE 
                (sender_id = ? AND receiver_id = u.id)
                OR
                (sender_id = u.id AND receiver_id = ?)
            ORDER BY created_at DESC
            LIMIT 1
        )

    WHERE u.id != ?

    ORDER BY m.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    jsonResponse([
        'success' => false,
        'error' => 'Prepare failed: ' . $conn->error
    ], 500);
}

$stmt->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

$conversations = [];

while ($row = $result->fetch_assoc()) {
    $avatar = null;

    if (!empty($row['profile_pic'])) {
        $avatar = BASE_URL . '/' . $row['profile_pic'];
    }

    $conversations[] = [
        'user_id' => (int)$row['user_id'],
        'username' => $row['username'],
        'nickname' => $row['nickname'],
        'avatar_url' => $avatar,
        'last_message' => $row['last_message'],
        'last_time' => $row['last_time'],
        'unread_count' => (int)$row['unread_count']
    ];
}

jsonResponse([
    'success' => true,
    'conversations' => $conversations
]);
?>