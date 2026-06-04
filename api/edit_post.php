<?php
require_once "db.php";

$user_id = require_login();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "error" => "Invalid request method"
    ]);
    exit;
}

$post_id = isset($_POST["post_id"]) ? (int)$_POST["post_id"] : 0;
$title = trim($_POST["title"] ?? "");
$description = trim($_POST["description"] ?? "");
$category_id = isset($_POST["category_id"]) ? (int)$_POST["category_id"] : 1;

if ($post_id <= 0) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid post ID"
    ]);
    exit;
}

if (empty($title)) {
    echo json_encode([
        "success" => false,
        "error" => "Title is required"
    ]);
    exit;
}

try {
    $check = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
    $check->execute([$post_id]);
    $post = $check->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo json_encode([
            "success" => false,
            "error" => "Post not found"
        ]);
        exit;
    }

    if ((int)$post["user_id"] !== (int)$user_id) {
        echo json_encode([
            "success" => false,
            "error" => "You can only edit your own post"
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE posts
        SET title = ?, description = ?, category_id = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $title,
        $description,
        $category_id,
        $post_id
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Post updated"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => "Update failed: " . $e->getMessage()
    ]);
}
?>