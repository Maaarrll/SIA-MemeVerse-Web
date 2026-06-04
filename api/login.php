<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

/*
    Accept both:
    1. JSON from web/fetch
    2. form-data / x-www-form-urlencoded from Android Retrofit
*/
$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $login = trim($input['login'] ?? '');
    $password = $input['password'] ?? '';
} else {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
}

if (empty($login) || empty($password)) {
    jsonResponse([
        'success' => false,
        'error' => 'Login and password required'
    ], 400);
}

$stmt = $conn->prepare("
    SELECT id, username, email, password 
    FROM users 
    WHERE username = ? OR email = ?
    LIMIT 1
");

if (!$stmt) {
    jsonResponse([
        'success' => false,
        'error' => 'Prepare failed: ' . $conn->error
    ], 500);
}

$stmt->bind_param('ss', $login, $login);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    jsonResponse([
        'success' => false,
        'error' => 'Invalid credentials'
    ], 401);
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    jsonResponse([
        'success' => false,
        'error' => 'Invalid credentials'
    ], 401);
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];

unset($user['password']);

jsonResponse([
    'success' => true,
    'message' => 'Login successful',
    'user_id' => (int)$user['id'],
    'username' => $user['username'],
    'user' => $user
]);
?>