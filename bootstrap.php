<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') . '">'; }
function fail_request(int $status, string $message): never { http_response_code($status); exit($message); }
function post_text(string $key): string {
    $value = $_POST[$key] ?? '';
    if (!is_string($value)) fail_request(422, 'Geçersiz form alanı.');
    return trim($value);
}
function require_post(): void { if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Allow: POST'); fail_request(405, 'POST gerekli.'); } }
foreach (['id', 'user_id', 'post_id', 'category_id', 'category'] as $key) {
    foreach ([$_GET, $_POST] as $input) {
        if (isset($input[$key]) && filter_var($input[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) fail_request(422, 'Geçersiz kimlik.');
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) fail_request(403, 'Form doğrulaması başarısız. Sayfayı yenileyin.');
}
set_exception_handler(function (Throwable $e): void {
    error_log(get_class($e) . ': ' . $e->getMessage());
    http_response_code(500);
    echo 'İşlem tamamlanamadı. Sunucu ayarlarını kontrol edin.';
});
