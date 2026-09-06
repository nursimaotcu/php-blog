<?php
include 'header.php';
include "db.php";

$id = $_GET['id'];

try {
    $sql = "SELECT posts.*, users.username, categories.name AS category_name FROM posts 
            JOIN users ON posts.user_id = users.id 
            LEFT JOIN categories ON posts.category_id = categories.id -- LEFT JOIN kullanıldı (Kategori zorunlu değilse)
            WHERE posts.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        echo "Gönderi bulunamadı.";
        exit();
    }

    // Beğeni Sayısını Çekme 
    $likesSql = "SELECT COUNT(*) AS total_likes FROM likes WHERE post_id = :id";
    $likesStmt = $pdo->prepare($likesSql);
    $likesStmt->execute([':id' => $id]);
    $likeCount = $likesStmt->fetch(PDO::FETCH_ASSOC)['total_likes'];

    // Kullanıcının beğenip beğenmediğini kontrol et
    $isLiked = false;
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $checkLikeSql = "SELECT 1 FROM likes WHERE user_id = :user_id AND post_id = :post_id";
        $checkLikeStmt = $pdo->prepare($checkLikeSql);
        $checkLikeStmt->execute([':user_id' => $user_id, ':post_id' => $id]);
        if ($checkLikeStmt->rowCount() > 0) {
            $isLiked = true;
        }
    }
    //ETİKETLERİ ÇEKME (Üç Tablolu JOIN SORGUSU)
    $tagsSql = "SELECT t.name FROM tags t
                JOIN post_tags pt ON t.id = pt.tag_id
                WHERE pt.post_id = :post_id";
    $tagsStmt = $pdo->prepare($tagsSql);
    $tagsStmt->execute([':post_id' => $id]); 
    $postTags = $tagsStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    throw $e;
    exit();
}
?>

<h2><?php echo htmlspecialchars($post['title']); ?></h2>
  <p><strong>Yazar:</strong> <a href="profile.php?user_id=<?php echo $post['user_id']; ?>"><?php echo htmlspecialchars($post['username']); ?></a></p>
<p><strong>Tarih:</strong> <?php echo htmlspecialchars($post['created_at']); ?></p>

<?php if (!empty($post['category_name'])): ?>
    <p><strong>Kategori:</strong> <?php echo htmlspecialchars($post['category_name']); ?></p>
<?php endif; ?>

<p>
    Toplam Beğeni: <strong><?php echo $likeCount; ?></strong>
    <?php if (isset($_SESSION['user_id'])): ?>
        <form method="POST" action="like_post.php" class="inline-action">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?php echo $post['id']; ?>"><button type="submit">
            <?php echo $isLiked ? '❤️ Beğenmekten Vazgeç' : '🤍 Beğen'; ?>
        </button></form>
    <?php endif; ?>
</p>
<div class="content-section">
    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
</div>

<?php if (!empty($postTags)): ?>
    <p class="post-tags" style="margin-top: 15px;">
        <strong>Etiketler:</strong> 
        <?php foreach ($postTags as $tag): ?>
            <span>  #<?php echo htmlspecialchars($tag); ?>
        </span>
        <?php endforeach; ?>
    </p>
<?php endif; ?>
<h3>Yorumlar</h3>
<?php
try {
    // SQL: Yorumları ve yorumu yapan kullanıcı adlarını çeken sorgu (JOIN kullanımı)
    $commentsSql = "SELECT comments.*, users.username FROM comments 
                    JOIN users ON comments.user_id = users.id 
                    WHERE comments.post_id = :post_id 
                    ORDER BY comments.created_at DESC";
    $commentsStmt = $pdo->prepare($commentsSql);
    $commentsStmt->execute([':post_id' => $id]);
    $comments = $commentsStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($comments) {
        foreach ($comments as $comment) {
            echo "<div>";
            // Yorum içeriğini 'content' sütunundan okur
            echo "<p><strong>" . htmlspecialchars($comment['username']) . ":</strong> " . nl2br(htmlspecialchars($comment['content'])) . "</p>";
            echo "<small>" . htmlspecialchars($comment['created_at']) . "</small>";
            echo "<hr>";
            echo "</div>";
        }
    } else {
        echo "<p>Henüz yorum yok. İlk yorumu siz yapın!</p>";
    }

} catch (PDOException $e) {
    throw $e;
}
?>

<?php if (isset($_SESSION['user_id'])): ?>
    <div class="comment-form-section">
        <form method="POST" action="add_comment.php">
        <?= csrf_field() ?>
            <h4>Yorum Yazın</h4>
            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
            <textarea name="comment_content" placeholder="Yorumunuzu buraya yazın..." required></textarea><br>
            <button type="submit">Yorumu Gönder</button>
        </form>
    </div>
<?php else: ?>
    <p>Yorum yapmak için lütfen <a href="login.php">giriş yapın</a>.</p>
<?php endif; ?>
<a href="index.php">← Geri dön</a>

<?php include 'footer.php'; ?>
