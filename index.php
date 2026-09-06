<?php
include 'db.php';
include 'auth.php';
// KATEGORİLERİ ÇEK 
try {
    $catSql = "SELECT * FROM categories ORDER BY name ASC";
    $catStmt = $pdo->query($catSql);
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $categories = []; }

// Filtre seçilmiş mi kontrol etme
$category_filter = isset($_GET['category']) ? $_GET['category'] : null;
?>
<?php include 'header.php'; ?>

<div class="container">
    <h1>Blog Uygulaması</h1>

    <?php if (isset($_SESSION['user_id'])): ?>
        <p>Hoş geldin, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>

        <div class="category-nav">
            <a href="index.php" class="<?php echo !$category_filter ? 'active' : ''; ?>">Tümü</a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?category=<?php echo $cat['id']; ?>" 
                   class="<?php echo $category_filter == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <h3><?php echo $category_filter ? "Seçili Kategori Yazıları" : "🔥 En Popüler Bloglar (Top Likes)"; ?></h3>

        <?php
        try {
            // (JOIN VE LIKES SAYIMI)
            $sql = "SELECT p.*, u.username, c.name AS category_name,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.id
                    JOIN categories c ON p.category_id = c.id";

            if ($category_filter) {
                $sql .= " WHERE p.category_id = :cat_id";
            }

            // Önce beğeni sayısı (en popüler), sonra tarih sıralaması
            $sql .= " ORDER BY like_count DESC, p.created_at DESC";

            $stmt = $pdo->prepare($sql);
            if ($category_filter) {
                $stmt->execute([':cat_id' => $category_filter]);
            } else {
                $stmt->execute();
            }

            // YAZILARIN LİSTELENMESİ
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
        ?>
                <div class="post">
                    <span class="category-badge"><?php echo htmlspecialchars($row['category_name']); ?></span>
                    
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <p>Yazar: <strong><?php echo htmlspecialchars($row['username']); ?></strong> | 
                       <span style="color: #e74c3c;">❤️ <?php echo $row['like_count']; ?> Beğeni</span>
                    </p>
                    <p><?php echo htmlspecialchars(substr($row['content'], 0, 100)); ?>...</p>
                    
                    <a href="post_detail.php?id=<?php echo $row['id']; ?>" class="btn-read">Detay</a> 
                    
                    <?php if ($row['user_id'] == $_SESSION['user_id']): ?>
                        | <a href="edit_post.php?id=<?php echo $row['id']; ?>">Düzenle</a> 
                        | <form method="POST" action="delete_post.php" class="inline-action">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?php echo $row['id']; ?>"><button type="submit">Sil</button></form>
                    <?php endif; ?>
                </div>
        <?php
            endwhile;
        } catch (PDOException $e) {
            throw $e;
        }
        ?>

    <?php else: ?>
        <p>Lütfen <a href="login.php">giriş</a> yapın veya <a href="register.php">kayıt olun</a>.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>