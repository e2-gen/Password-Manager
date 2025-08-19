<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth($pdo);

// إذا كان المستخدم مسجل الدخول بالفعل، توجيهه إلى لوحة التحكم
if ($auth->isLoggedIn()) {
    redirect('dashboard.php');
}

$pageTitle = "إنشاء حساب | Password Vault";
$errors = [];
$success = '';

// معالجة نموذج التسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // التحقق من صحة البيانات
    if (strlen($username) < 4 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = "اسم المستخدم يجب أن يكون بين 4-20 حرفاً (أحرف، أرقام، _ فقط)";
    }
    
    if (!validateEmail($email)) {
        $errors['email'] = "البريد الإلكتروني غير صالح";
    }
    
    if ($password !== $confirmPassword) {
        $errors['password'] = "كلمتا المرور غير متطابقتين";
    }
    
    $passwordStrength = checkPasswordStrength($password);
    if ($passwordStrength['strength'] < 3) {
        $errors['password'] = "كلمة المرور ضعيفة جداً. " . implode(' ', $passwordStrength['messages']);
    }
    
    if (empty($errors)) {
        $userId = $auth->register($username, $email, $password);
        
        if ($userId) {
            logActivity("حساب جديد تم إنشاؤه: $username", 'info', $userId);
            
            // تسجيل الدخول تلقائياً بعد التسجيل
            $auth->login($username, $password);
            
            $_SESSION['message'] = "تم إنشاء حسابك بنجاح! يرجى إكمال إعدادات الأمان الخاصة بك.";
            $_SESSION['message_type'] = 'success';
            redirect('security-settings.php');
        } else {
            $errors['general'] = "حدث خطأ أثناء إنشاء الحساب. قد يكون اسم المستخدم أو البريد الإلكتروني مستخدماً مسبقاً.";
        }
    }
}

include 'templates/header.php';
?>

<div class="auth-container">
    <div class="card shadow-lg">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <img src="assets/images/logo.png" alt="Password Vault" class="mb-3" width="100">
                <h2 class="card-title">إنشاء حساب جديد</h2>
                <p class="text-muted">املأ النموذج أدناه لإنشاء حسابك</p>
            </div>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">اسم المستخدم</label>
                    <input type="text" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                           id="username" name="username" required>
                    <?php if (isset($errors['username'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['username']; ?></div>
                    <?php else: ?>
                        <small class="text-muted">يجب أن يكون بين 4-20 حرفاً (أحرف، أرقام، _ فقط)</small>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                           id="email" name="email" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                    <?php else: ?>
                        <small class="text-muted">سنستخدم هذا البريد للتواصل معك</small>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <div class="input-group">
                        <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                               id="password" name="password" required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary" type="button" id="generatePassword">
                            <i class="fas fa-random"></i>
                        </button>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="invalid-feedback d-block"><?php echo $errors['password']; ?></div>
                    <?php endif; ?>
                    <div class="password-strength mt-2">
                        <small>قوة كلمة المرور: <span id="password-strength-text">غير معروف</span></small>
                        <div class="progress mt-1" style="height: 5px;">
                            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="agree_terms" name="agree_terms" required>
                    <label class="form-check-label" for="agree_terms">
                        أوافق على <a href="terms.php" target="_blank">الشروط والأحكام</a> و <a href="privacy.php" target="_blank">سياسة الخصوصية</a>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-success w-100 py-2 mb-3">إنشاء حساب</button>
                
                <div class="text-center mt-4">
                    <p class="text-muted">لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
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
    
    // توليد كلمة مرور قوية
    const generatePassword = document.getElementById('generatePassword');
    if (generatePassword) {
        generatePassword.addEventListener('click', function() {
            const length = 16;
            const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=";
            let password = "";
            
            // تأكد من وجود أنواع مختلفة من الأحرف
            password += getRandomChar("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
            password += getRandomChar("abcdefghijklmnopqrstuvwxyz");
            password += getRandomChar("0123456789");
            password += getRandomChar("!@#$%^&*()_+~`|}{[]:;?><,./-=");
            
            // إكمال باقي كلمة المرور
            for (let i = password.length; i < length; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            
            // خلط الأحرف
            password = shuffleString(password);
            
            document.getElementById('password').value = password;
            document.getElementById('confirm_password').value = password;
            checkPasswordStrength({ target: { value: password } });
        });
    }
    
    // تحليل قوة كلمة المرور
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }
    
    // دوال مساعدة
    function getRandomChar(charSet) {
        return charSet.charAt(Math.floor(Math.random() * charSet.length));
    }
    
    function shuffleString(str) {
        const array = str.split('');
        for (let i = array.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [array[i], array[j]] = [array[j], array[i]];
        }
        return array.join('');
    }
    
    function checkPasswordStrength(e) {
        const password = e.target.value;
        const strengthText = document.getElementById('password-strength-text');
        const strengthBar = document.getElementById('password-strength-bar');
        
        if (!password) {
            strengthText.textContent = 'غير معروف';
            strengthBar.style.width = '0%';
            strengthBar.className = 'progress-bar';
            return;
        }
        
        let strength = 0;
        
        // حساب القوة بناء على عدة عوامل
        strength += Math.min(password.length * 3, 30);
        
        const hasLower = /[a-z]/.test(password);
        const hasUpper = /[A-Z]/.test(password);
        const hasNumbers = /[0-9]/.test(password);
        const hasSpecial = /[^A-Za-z0-9]/.test(password);
        
        if (hasLower) strength += 5;
        if (hasUpper) strength += 5;
        if (hasNumbers) strength += 5;
        if (hasSpecial) strength += 10;
        
        // تحديد مستوى القوة
        let strengthLevel, strengthClass;
        
        if (strength < 30) {
            strengthLevel = 'ضعيفة';
            strengthClass = 'bg-danger';
        } else if (strength < 60) {
            strengthLevel = 'متوسطة';
            strengthClass = 'bg-warning';
        } else {
            strengthLevel = 'قوية';
            strengthClass = 'bg-success';
        }
        
        strengthText.textContent = strengthLevel;
        strengthBar.style.width = Math.min(strength, 100) + '%';
        strengthBar.className = 'progress-bar ' + strengthClass;
    }
});
</script>

<?php
include 'templates/footer.php';
?>