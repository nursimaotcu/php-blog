<?php
require 'db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = post_text('email');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT id, username, password FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && is_string($password) && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        header('Location: index.php'); exit;
    }
    http_response_code(422);
    $error = 'Geçersiz e-posta veya şifre.';
}
include 'header.php';
?>
<h2>Giriş Yap</h2>
<p role="alert"><?= htmlspecialchars($error) ?></p>
<form method="POST">
    <?= csrf_field() ?>
    <label>E-posta <input type="email" name="email" required autocomplete="username"></label>
    <label>Şifre <input type="password" name="password" required autocomplete="current-password"></label>
    <button type="submit">Giriş Yap</button>
</form>
<p>Üye değil misiniz? <a href="register.php">Kayıt Ol</a></p>
<?php include 'footer.php'; ?>
