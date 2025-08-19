<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth($pdo);

// إذا كان المستخدم مسجل الدخول بالفعل، توجيهه إلى لوحة التحكم
if ($auth->isLoggedIn()) {
    redirect('dashboard.php');
}

$pageTitle = "تسجيل الدخول | Password Vault";
$error = '';

// معالجة نموذج تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if ($auth->login($username, $password)) {
        logActivity("تسجيل دخول ناجح للمستخدم: $username", 'info', $_SESSION['user_id']);
        
        // التحقق إذا كان المستخدم قد طلب تذكر بياناته
        if (isset($_POST['remember']) && $_POST['remember'] === 'on') {
            setcookie('remember_user', $_SESSION['user_id'], time() + (30 * 24 * 60 * 60), '/');
        }
        
        // توجيه المستخدم إلى الصفحة التي كان يحاول الوصول إليها أو إلى لوحة التحكم
        $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'dashboard.php';
        unset($_SESSION['redirect_url']);
        redirect($redirect);
    } else {
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة";
        logActivity("محاولة تسجيل دخول فاشلة للمستخدم: $username", 'warning');
    }
}

// إذا كان هناك cookie تذكر، حاول تسجيل الدخول تلقائياً
if (!$auth->isLoggedIn() && isset($_COOKIE['remember_user'])) {
    $userId = $_COOKIE['remember_user'];
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $user['username'];
        $_SESSION['logged_in'] = true;
        redirect('dashboard.php');
    }
}

include 'templates/header.php';
?>

<div class="auth-container">
    <div class="card shadow-lg">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <img src="assets/images/logo.png" alt="Password Vault" class="mb-3" width="100">
                <h2 class="card-title">تسجيل الدخول إلى حسابك</h2>
                <p class="text-muted">أدخل بياناتك للوصول إلى كلمات المرور الخاصة بك</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">اسم المستخدم أو البريد الإلكتروني</label>
                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="text-end mt-2">
                        <a href="forgot-password.php" class="text-muted small">نسيت كلمة المرور؟</a>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">تذكرني على هذا الجهاز</label>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">تسجيل الدخول</button>
                
                <div class="text-center mt-4">
                    <p class="text-muted">ليس لديك حساب؟ <a href="register.php">إنشاء حساب جديد</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // إظهار/إخفاء كلمة المرور
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }
});
</script>

<?php
include 'templates/footer.php';
?>