<?php
include 'header.php';
include 'db.php';

try {
    // ⭐ SQL: GÖNDERİ VE YORUM SAYISINI BİRLEŞTİREN KOMPLEKS SORGULAMA
    // COUNT(DISTINCT...) kullanımı, doğru sayım yapmak için kritik öneme sahiptir.
    $sql = "SELECT 
                u.id, 
                u.username, 
                COUNT(DISTINCT p.id) AS total_posts,       -- Toplam Gönderi Sayısı
                COUNT(DISTINCT c.id) AS total_comments     -- Toplam Yorum Sayısı
            FROM users u
            LEFT JOIN posts p ON u.id = p.user_id 
            LEFT JOIN comments c ON u.id = c.user_id 
            GROUP BY u.id, u.username 
            ORDER BY total_posts DESC, total_comments DESC  -- Önce post, sonra yoruma göre sırala
            LIMIT 10"; 

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $topUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    throw $e;
    exit();
}
?>

<h2>🏆 En Aktif İçerik Üreticileri ve Yorumcular (Top 10)</h2>

<div class="content-section"> 
    <?php if (!empty($topUsers)): ?>
        <table class="top-users-table"> 
            <thead>
                <tr>
                    <th>Kullanıcı Adı</th>
                    <th>Toplam Gönderi Sayısı</th>
                    <th>Toplam Yorum Sayısı</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topUsers as $user): ?>
                    <tr>
                        <td>
                            <a href="profile.php?user_id=<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </a>
                        </td>
                        <td><?php echo $user['total_posts']; ?></td>
                        <td><?php echo $user['total_comments']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Henüz içerik üreten veya yorum yapan kullanıcı yok.</p>
    <?php endif; ?>

    <a href="index.php" class="btn-back">← Ana Sayfaya Dön</a>
</div>
<?php include 'footer.php'; ?>