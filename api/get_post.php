<?php
require_once "db.php";

$post_id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$current_user_id = $_SESSION["user_id"] ?? 0;

if ($post_id <= 0) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid post ID"
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.user_id,
            p.title,
            p.description,
            p.tags,
            p.image_path,
            p.category_id,
            p.created_at,

            u.username,
            u.nickname,
            u.avatar AS avatar_url,

            c.name AS category_name,
            c.slug AS category_slug,

            COALESCE(SUM(v.vote), 0) AS vote_score,

            (
                SELECT COUNT(*) 
                FROM comments 
                WHERE post_id = p.id
            ) AS comment_count,

            (
                SELECT vote 
                FROM votes 
                WHERE post_id = p.id AND user_id = ?
                LIMIT 1
            ) AS user_vote

        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN votes v ON p.id = v.post_id
        WHERE p.id = ?
        GROUP BY p.id
        LIMIT 1
    ");

    $stmt->execute([$current_user_id, $post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$post) {
        echo json_encode([
            "success" => false,
            "error" => "Post not found"
        ]);
        exit;
    }

    $post["id"] = (int)$post["id"];
    $post["user_id"] = (int)$post["user_id"];
    $post["category_id"] = (int)$post["category_id"];
    $post["vote_score"] = (int)$post["vote_score"];
    $post["comment_count"] = (int)$post["comment_count"];
    $post["user_vote"] = $post["user_vote"] !== null ? (int)$post["user_vote"] : 0;

    $commentsStmt = $pdo->prepare("
        SELECT
            cm.id,
            cm.user_id,
            cm.post_id,
            cm.parent_id,
            cm.comment_text,
            cm.created_at,
            u.username,
            u.nickname,
            u.avatar AS avatar_url
        FROM comments cm
        JOIN users u ON cm.user_id = u.id
        WHERE cm.post_id = ?
        ORDER BY cm.created_at ASC
    ");

    $commentsStmt->execute([$post_id]);
    $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($comments as &$comment) {
        $comment["id"] = (int)$comment["id"];
        $comment["user_id"] = (int)$comment["user_id"];
        $comment["post_id"] = (int)$comment["post_id"];
        $comment["parent_id"] = $comment["parent_id"] !== null ? (int)$comment["parent_id"] : null;
    }

    $post["comments"] = $comments;

    echo json_encode([
        "success" => true,
        "post" => $post
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "error" => "Failed to load post: " . $e->getMessage()
    ]);
}
?>