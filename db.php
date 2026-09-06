<?php
require_once __DIR__ . '/bootstrap.php';
$local = is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : [];
$host = $local['host'] ?? getenv('BLOG_DB_HOST') ?: '127.0.0.1';
$port = $local['port'] ?? getenv('BLOG_DB_PORT') ?: '3306';
$db = $local['database'] ?? getenv('BLOG_DB_NAME') ?: 'portfolio_blog';
$user = $local['user'] ?? getenv('BLOG_DB_USER') ?: 'root';
$pass = $local['password'] ?? getenv('BLOG_DB_PASSWORD') ?: '';
$pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
