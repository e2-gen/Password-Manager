<?php
// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'password_manager');

// إعدادات التطبيق
define('SITE_NAME', 'Password Vault');
define('BASE_URL', 'http://localhost/password-manager');

// إعدادات الأمان
define('PEPPER', 'your-unique-pepper-string-here');
define('ENCRYPTION_KEY', 'your-encryption-key-here');

// بدء الجلسة
session_start();

// الاتصال بقاعدة البيانات
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
