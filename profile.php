<?php
include 'header.php';
include "db.php";

// Eğer ID gelmezse oturumdaki kullanıcının profilini göster
$profile_id = isset($_GET['user_id']) ? $_GET['user_id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

if (!$profile_id) {
    echo "Görüntülenecek kullanıcı ID'si bulunamadı.";
    include 'footer.php';
    exit();
}

try {
    // 1. Kullanıcı bilgilerini çekme
    $userSql = "SELECT username FROM users WHERE id = :id";
    $userStmt = $pdo->prepare($userSql);
    $userStmt->execute([':id' => $profile_id]);
    $profileUser = $userStmt->fetch(PDO::FETCH_ASSOC);

    if (!$profileUser) {
        echo "Kullanıcı bulunamadı.";
        include 'footer.php';
        exit();
    }
    
    // 2. Takipçi ve Takip Edilen sayılarını çekme 
    $followersSql = "SELECT COUNT(*) FROM following WHERE following_id = :id";
    $followersStmt = $pdo->prepare($followersSql);
    $followersStmt->execute([':id' => $profile_id]);
    $followerCount = $followersStmt->fetchColumn();

    $followingSql = "SELECT COUNT(*) FROM following WHERE follower_id = :id";
    $followingStmt = $pdo->prepare($followingSql);
    $followingStmt->execute([':id' => $profile_id]);
    $followingCount = $followingStmt->fetchColumn();

    // 3. Oturumdaki kullanıcının bu profili takip edip etmediğini kontrol et
    $isFollowing = false;
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $profile_id) {
        $checkFollowSql = "SELECT 1 FROM following WHERE follower_id = :follower AND following_id = :followed";
        $checkFollowStmt = $pdo->prepare($checkFollowSql);
        $checkFollowStmt->execute([':follower' => $_SESSION['user_id'], ':followed' => $profile_id]);
        if ($checkFollowStmt->rowCount() > 0) {
            $isFollowing = true;
        }
    }
    
    // 4. Kullanıcının tüm gönderilerini çekme
    $postsSql = "SELECT id, title, created_at FROM posts WHERE user_id = :id ORDER BY created_at DESC";
    $postsStmt = $pdo->prepare($postsSql);
    $postsStmt->execute([':id' => $profile_id]);
    $posts = $postsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    throw $e;
    include 'footer.php';
    exit();
}
?>

<h2><?php echo htmlspecialchars($profileUser['username']); ?> Profili</h2>

<?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $profile_id): ?>
    <form method="POST" action="follow_user.php" class="inline-action">
        <?= csrf_field() ?><input type="hidden" name="user_id" value="<?php echo $profile_id; ?>"><button type="submit">
        <?php echo $isFollowing ? 'Takipten Çık' : 'Takip Et'; ?>
    </button></form>
    <br><br>
<?php endif; ?>

<p>Takipçi: <strong><?php echo $followerCount; ?></strong> | Takip Edilen: <strong><?php echo $followingCount; ?></strong></p>

<h3>Gönderileri (<?php echo count($posts); ?>)</h3>

<?php if ($posts): ?>
    <ul>
        <?php foreach ($posts as $post): ?>
            <li>
                <a href="post_detail.php?id=<?php echo $post['id']; ?>">
                    <?php echo htmlspecialchars($post['title']); ?>
                </a> 
                <small>(<?php echo date('d.m.Y', strtotime($post['created_at'])); ?>)</small>
            </li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Bu kullanıcının henüz bir gönderisi yok.</p>
<?php endif; ?>

<a href="index.php">← Ana Sayfa</a>

<?php include 'footer.php'; ?>