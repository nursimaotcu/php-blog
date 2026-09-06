<?php
include "db.php";
include "auth.php"; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $post_id = $_POST["post_id"];
    $content = post_text('comment_content');
    if (strlen($content) > 60000) fail_request(422, 'Yorum çok uzun.'); 
    $user_id = $_SESSION["user_id"]; 

    if (empty($content)) {
        header("Location: post_detail.php?id=$post_id&error=empty");
        exit();
    }

    try {
        //Yorumu comments tablosuna kaydet
        $sql = "INSERT INTO comments (post_id, user_id, content) VALUES (:post_id, :user_id, :content)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':post_id' => $post_id,
            ':user_id' => $user_id,
            ':content' => $content
        ]);
        
        header("Location: post_detail.php?id=$post_id");
        exit();

    } catch (PDOException $e) {
        throw $e;
    }
} else {
    header("Location: index.php");
    exit();
}
?>