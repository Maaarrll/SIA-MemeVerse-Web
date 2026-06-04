<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'No valid file uploaded'], 400);
}

$file = $_FILES['avatar'];

if ($file['size'] > 2 * 1024 * 1024) {
    jsonResponse(['error' => 'Avatar must be less than 2MB'], 400);
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedMimes)) {
    jsonResponse(['error' => 'Only JPG, PNG, and GIF images are allowed'], 400);
}

$avatarDir = dirname(__DIR__) . '/assets/uploads/avatars/';

if (!is_dir($avatarDir)) {
    mkdir($avatarDir, 0755, true);
}

if (!is_writable($avatarDir)) {
    jsonResponse(['error' => 'Avatar directory is not writable'], 500);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
$destination = $avatarDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(['error' => 'Failed to save avatar'], 500);
}

$avatar_path = 'assets/uploads/avatars/' . $filename;

$stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
$stmt->bind_param('si', $avatar_path, $_SESSION['user_id']);

if ($stmt->execute()) {
    jsonResponse([
        'success' => true,
        'avatar_url' => BASE_URL . '/' . $avatar_path,
        'message' => 'Avatar updated'
    ]);
}

unlink($destination);

jsonResponse(['error' => 'Database update failed'], 500);
?>