<?php
require_once __DIR__ . '/config.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim(htmlspecialchars($data)));
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getUserById($id) {
    global $conn;

    $id = (int)$id;

    $result = $conn->query("
        SELECT id, username, email, created_at 
        FROM users 
        WHERE id = $id
    ");

    return $result->fetch_assoc();
}
?>