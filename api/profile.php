<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : (isLoggedIn() ? $_SESSION['user_id'] : 0);

    if ($user_id <= 0) {
        jsonResponse(['error' => 'Invalid user'], 400);
    }

    $stmt = $conn->prepare("
        SELECT id, username, nickname, bio, profile_pic, created_at
        FROM users
        WHERE id = ?
    ");

    $stmt->bind_param('i', $user_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        jsonResponse(['error' => 'User not found'], 404);
    }

    $user = $result->fetch_assoc();

    if (!empty($user['profile_pic'])) {
        $user['profile_pic'] = BASE_URL . '/' . $user['profile_pic'];
    }

    $postCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM posts WHERE user_id = ?");
    $postCountStmt->bind_param('i', $user_id);
    $postCountStmt->execute();
    $postCount = $postCountStmt->get_result()->fetch_assoc()['total'];

    $user['post_count'] = (int)$postCount;
    $user['is_own_profile'] = isLoggedIn() && (int)$_SESSION['user_id'] === (int)$user_id;

    jsonResponse([
        'user' => $user
    ]);
}

if ($method === 'POST') {
    if (!isLoggedIn()) {
        jsonResponse(['error' => 'Authentication required'], 401);
    }

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        jsonResponse(['error' => 'Invalid JSON'], 400);
    }

    $nickname = isset($input['nickname']) ? trim($input['nickname']) : null;
    $bio = isset($input['bio']) ? trim($input['bio']) : null;

    $user_id = $_SESSION['user_id'];

    $update = [];
    $params = [];
    $types = '';

    if ($nickname !== null) {
        $update[] = "nickname = ?";
        $params[] = $nickname;
        $types .= 's';
    }

    if ($bio !== null) {
        $update[] = "bio = ?";
        $params[] = $bio;
        $types .= 's';
    }

    if (empty($update)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }

    $sql = "UPDATE users SET " . implode(', ', $update) . " WHERE id = ?";

    $params[] = $user_id;
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        jsonResponse([
            'success' => true,
            'message' => 'Profile updated'
        ]);
    }

    jsonResponse(['error' => 'Update failed: ' . $stmt->error], 500);
}

jsonResponse(['error' => 'Method not allowed'], 405);
?>