<?php
include "db.php";
include "auth.php";
require_post(); // Oturum kontrolü

if (!isset($_POST['user_id'])) {
    header("Location: index.php");
    exit();
}

$following_id = $_POST['user_id']; // Takip Edilen
$follower_id = $_SESSION['user_id']; // Takip Eden (Oturumdaki kişi)

// Kullanıcı kendi kendini takip edemez
if ($following_id == $follower_id) {
    header("Location: profile.php?user_id=$following_id");
    exit();
}

try {
    // 1. Takip durumunu kontrol et
    $checkSql = "SELECT * FROM following WHERE follower_id = :follower AND following_id = :followed";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([':follower' => $follower_id, ':followed' => $following_id]);

    if ($checkStmt->rowCount() > 0) {
        // Eğer Takip Ediyorsa: Takipten Çık 
        $sql = "DELETE FROM following WHERE follower_id = :follower AND following_id = :followed";
    } else {
        // Eğer Takip Etmiyorsa: Takip Et 
        $sql = "INSERT INTO following (follower_id, following_id) VALUES (:follower, :followed)";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':follower' => $follower_id, ':followed' => $following_id]);

    // Profil sayfasına geri yönlendir
    header("Location: profile.php?user_id=$following_id");
    exit();

} catch (PDOException $e) {
    throw $e;
}
?>