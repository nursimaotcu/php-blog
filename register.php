<?php
require 'db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post_text('username');
    $email = post_text('email');
    $password = $_POST['password'] ?? '';
    if ($username === '' || mb_strlen($username) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100 || !is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
        $error = 'Geçerli kullanıcı adı, e-posta ve en az 8 karakterli şifre girin (en çok 72 bayt).';
        http_response_code(422);
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
            $stmt->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
            header('Location: login.php'); exit;
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) !== 1062) throw $e;
            http_response_code(409);
            $error = 'Bu e-posta adresi zaten kayıtlı.';
        }
    }
}
include 'header.php';
?>
<h2>Kayıt Ol</h2>
<p role="alert"><?= htmlspecialchars($error) ?></p>
<form method="POST">
    <?= csrf_field() ?>
    <label>Kullanıcı adı <input name="username" maxlength="50" required autocomplete="nickname"></label>
    <label>E-posta <input type="email" name="email" maxlength="100" required autocomplete="email"></label>
    <label>Şifre <input type="password" name="password" minlength="8" required autocomplete="new-password"></label>
    <button type="submit">Kayıt Ol</button>
</form>
<?php include 'footer.php'; ?>
