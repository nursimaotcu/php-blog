<?php
include "db.php";
include "auth.php"; 


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = post_text('title');
    $content = post_text('content');
    if ($title === '' || strlen($title) > 1000 || $content === '' || strlen($content) > 60000) fail_request(422, 'Başlık ve içerik gerekli veya çok uzun.');
    $user_id = $_SESSION["user_id"];
    $category_id = $_POST['category_id']; 
    $tags_input = isset($_POST['tags']) ? trim($_POST['tags']) : '';

    try {
        $pdo->beginTransaction();
        $sql = "INSERT INTO posts (user_id, title, content, category_id) VALUES (:user_id, :title, :content, :category_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $user_id, ':title' => $title, ':content' => $content, ':category_id' => $category_id]);
        $new_post_id = $pdo->lastInsertId();

        if (!empty($tags_input)) {
            $tag_names = array_filter(array_map('trim', explode(',', strtolower($tags_input))));
            foreach ($tag_names as $tag_name) {
                if (empty($tag_name)) continue;
                $tagCheckSql = "SELECT id FROM tags WHERE name = :name";
                $tagCheckStmt = $pdo->prepare($tagCheckSql);
                $tagCheckStmt->execute([':name' => $tag_name]);
                $tag = $tagCheckStmt->fetch(PDO::FETCH_ASSOC);

                if (!$tag) {
                    $tagInsertSql = "INSERT INTO tags (name) VALUES (:name)";
                    $tagInsertStmt = $pdo->prepare($tagInsertSql);
                    $tagInsertStmt->execute([':name' => $tag_name]);
                    $tag_id = $pdo->lastInsertId();
                } else { $tag_id = $tag['id']; }

                $postTagSql = "INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)";
                $postTagStmt = $pdo->prepare($postTagSql);
                $postTagStmt->execute([':post_id' => $new_post_id, ':tag_id' => $tag_id]);
            }
        }
        $pdo->commit();
        header("Location: post_detail.php?id=" . $new_post_id);
        exit();
    } catch (PDOException $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}

try {
    $categoriesSql = "SELECT id, name FROM categories ORDER BY name ASC";
    $categoriesStmt = $pdo->prepare($categoriesSql);
    $categoriesStmt->execute();
    $categories = $categoriesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $categories = []; }
?>
<?php include 'header.php'; ?>

<style>
    /* Sayfadaki tüm input ve select yapılarını eşitleyen zorunlu stil */
    form { max-width: 600px !important; margin: 40px auto !important; padding: 20px; background: #fff; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); }
    .form-group { width: 100% !important; margin-bottom: 20px !important; display: flex !important; flex-direction: column !important; }
    label { font-weight: 600; color: #4a6058; margin-bottom: 8px; display: block; text-align: left; }
    
    input[type="text"], textarea {
        width: 100% !important;
        box-sizing: border-box !important;
        padding: 15px !important;
        border: 1.5px solid #c9c1ac !important;
        background-color: #fcfbf6 !important;
        border-radius: 10px !important;
        font-size: 16px !important;
        font-family: inherit;
    }

    .select-wrapper {
        position: relative !important;
        width: 100% !important;
        height: 55px !important;
        background-color: #fcfbf6 !important;
        border: 1.5px solid #c9c1ac !important;
        border-radius: 10px !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2020%2020'%3E%3Cpath%20fill='%234a6058'%20d='M9.293%2012.95l.707.707L15.657%208l-1.414-1.414L10%2010.828%205.757%206.586%204.343%208z'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: right 15px center !important;
        background-size: 15px !important;
    }

    .select-wrapper select {
        width: 100% !important;
        height: 100% !important;
        background: transparent !important;
        border: none !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        padding: 0 40px 0 15px !important;
        font-size: 16px !important;
        cursor: pointer !important;
        outline: none !important;
    }
</style>

<div class="container">
    <form method="POST">
        <?= csrf_field() ?>
        <h2>✨ Yeni Gönderi Paylaş</h2>
        
        <div class="form-group">
            <label>Başlık:</label>
            <input type="text" name="title" placeholder="Harika bir başlık yazın..." required>
        </div>
        
        <div class="form-group">
            <label>İçerik:</label>
            <textarea name="content" rows="6" placeholder="Neler düşünüyorsun?" required></textarea>
        </div>

        <div class="form-group">
            <label>Kategori Seçin:</label>
            <div class="select-wrapper">
                <select name="category_id" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Etiketler (Virgülle ayırın):</label>
            <input type="text" name="tags" placeholder="php, blog, seyahat...">
        </div>
        
        <button type="submit">Yayınla</button>
        <a href="index.php" style="display:block; text-align:center; margin-top:15px; color:#72876f;">← Vazgeç ve Geri Dön</a>
    </form>
</div>

<?php include 'footer.php'; ?>
