<?php
require_once 'config.php';
require_once 'encryption.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // تسجيل مستخدم جديد
    public function register($username, $email, $password) {
        // التحقق من عدم وجود المستخدم مسبقًا
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if($stmt->rowCount() > 0) {
            return false;
        }
        
        // إنشاء hash لكلمة المرور
        $hashedPassword = password_hash($password . PEPPER, PASSWORD_ARGON2ID);
        
        // إضافة المستخدم إلى قاعدة البيانات
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
        if($stmt->execute([$username, $email, $hashedPassword])) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }
    
    // تسجيل الدخول
    public function login($username, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password . PEPPER, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['logged_in'] = true;
            
            // إنشاء مثيل للتشفير المخصص لهذا المستخدم
            $_SESSION['encryptor'] = new CustomEncryption($user['id'], $password);
            
            return true;
        }
        
        return false;
    }
    
    // تسجيل الخروج
    public function logout() {
        session_unset();
        session_destroy();
    }
    
    // التحقق من تسجيل الدخول
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'];
    }
}
?>
