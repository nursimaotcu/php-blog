<?php
require_once __DIR__ . '/bootstrap.php';
require_post();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;
