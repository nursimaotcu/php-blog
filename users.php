<?php
include 'header.php';
include 'db.php';

try {
    // Sadece tüm kullanıcıları çekmek için en basit sorgu
    $sql = "SELECT id, username FROM users ORDER BY username ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    throw $e;
}
?>

<div class="container">
    <h2>👥 Kayıtlı Kullanıcılar</h2>
    
    <?php if ($users): ?>
        <ul style="list-style: none; padding: 0;">
            <?php foreach ($users as $user): ?>
                <li style="margin-bottom: 10px; padding: 10px; border-bottom: 1px solid #eee;">
                    <a href="profile.php?user_id=<?php echo $user['id']; ?>" 
                       style="font-size: 1.1em; font-weight: bold;">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Sistemde kayıtlı kullanıcı bulunmamaktadır.</p>
    <?php endif; ?>
    
    <a href="index.php" class="btn-back">← Geri</a>
</div>

<?php include 'footer.php'; ?>