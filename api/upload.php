<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

if ($category_id <= 0) {
    jsonResponse(['error' => 'Please select a category'], 400);
}

$stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
$stmt->bind_param('i', $category_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    jsonResponse(['error' => 'Invalid category'], 400);
}

$stmt->close();

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'No valid image uploaded'], 400);
}

$file = $_FILES['image'];

if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(['error' => 'File size must be less than 5MB'], 400);
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowedMimes)) {
    jsonResponse(['error' => 'Only JPG, PNG, and GIF images are allowed'], 400);
}

$uploadDir = dirname(__DIR__) . '/assets/uploads/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!is_writable($uploadDir)) {
    jsonResponse(['error' => 'Upload directory is not writable'], 500);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('meme_', true) . '_' . time() . '.' . $ext;
$destination = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(['error' => 'Failed to save uploaded file'], 500);
}

$user_id = $_SESSION['user_id'];
$image_path = 'assets/uploads/' . $filename;

$stmt = $conn->prepare("
    INSERT INTO posts (user_id, category_id, title, description, image_path)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->bind_param('iisss', $user_id, $category_id, $title, $description, $image_path);

if ($stmt->execute()) {
    $post_id = $stmt->insert_id;

    jsonResponse([
        'success' => true,
        'message' => 'Upload successful',
        'post_id' => $post_id,
        'image_url' => BASE_URL . '/' . $image_path
    ], 201);
} else {
    unlink($destination);
    jsonResponse(['error' => 'Database error: ' . $stmt->error], 500);
}
?>