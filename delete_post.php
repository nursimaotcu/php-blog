<?php
include "db.php";
include "auth.php";
require_post();

$id = $_POST['id'];
$user_id = $_SESSION['user_id'];

try {
    $sql = "DELETE FROM posts WHERE id = :id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $id,
        ':user_id' => $user_id
    ]);
    header("Location: index.php");
    exit();
} catch (PDOException $e) {
    throw $e;
}
