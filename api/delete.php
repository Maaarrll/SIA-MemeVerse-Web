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

if ($post_id <= 0) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid post ID"
    ]);
    exit;
}

try {
    $check = $pdo->prepare("SELECT user_id, image_path FROM posts WHERE id = ?");
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
            "error" => "You can only delete your own post"
        ]);
        exit;
    }

    if (!empty($post["image_path"])) {
        $file_path = "../" . $post["image_path"];

        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $delete = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $delete->execute([$post_id]);

    echo json_encode([
        "success" => true,
        "message" => "Post deleted"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => "Delete failed: " . $e->getMessage()
    ]);
}
?>