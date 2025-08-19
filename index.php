<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth($pdo);

// إذا كان المستخدم مسجل الدخول، توجيهه إلى لوحة التحكم
if ($auth->isLoggedIn()) {
    redirect('dashboard.php');
}

// معالجة تسجيل الدخول إذا تم إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if ($auth->login($username, $password)) {
        logActivity("تسجيل دخول ناجح للمستخدم: " . $username, 'info', $_SESSION['user_id']);
        redirect('dashboard.php');
    } else {
        logActivity("محاولة تسجيل دخول فاشلة للمستخدم: " . $username, 'warning');
        $loginError = "اسم المستخدم أو كلمة المرور غير صحيحة";
    }
}

// معالجة التسجيل إذا تم إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // التحقق من تطابق كلمتي المرور
    if ($password !== $confirmPassword) {
        $registerError = "كلمتا المرور غير متطابقتين";
    } elseif (!validateEmail($email)) {
        $registerError = "البريد الإلكتروني غير صالح";
    } else {
        // التحقق من قوة كلمة المرور
        $passwordStrength = checkPasswordStrength($password);
        if ($passwordStrength['strength'] < 3) {
            $registerError = "كلمة المرور ضعيفة جداً. " . implode(' ', $passwordStrength['messages']);
        } else {
            // تسجيل المستخدم الجديد
            $userId = $auth->register($username, $email, $password);
            if ($userId) {
                logActivity("حساب جديد تم إنشاؤه: " . $username, 'info', $userId);
                $_SESSION['message'] = "تم إنشاء حسابك بنجاح! يرجى تسجيل الدخول الآن.";
                $_SESSION['message_type'] = 'success';
                redirect('login.php');
            } else {
                $registerError = "حدث خطأ أثناء إنشاء الحساب. قد يكون اسم المستخدم أو البريد الإلكتروني مستخدماً مسبقاً.";
            }
        }
    }
}

$pageTitle = "Password Vault - نظام إدارة كلمات المرور الآمن";
include 'templates/header.php';
?>

<!-- القسم الرئيسي -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">أمان كلمات المرور بين يديك</h1>
                <p class="lead mb-4">نظام متكامل لإدارة وحفظ كلمات المرور بشكل آمن ومشفر. وفر وقتك وحساباتك من الاختراق باستخدام خزنة كلمات المرور الخاصة بنا.</p>
                <div class="d-flex gap-3">
                    <a href="#login-section" class="btn btn-light btn-lg px-4">تسجيل الدخول</a>
                    <a href="#register-section" class="btn btn-outline-light btn-lg px-4">إنشاء حساب</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="assets/images/hero-image.png" alt="Password Vault" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<!-- ميزات النظام -->
<section class="features-section py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">لماذا تختار نظامنا؟</h2>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto" style="width: 60px; height: 60px;">
                            <i class="fas fa-lock fa-2x"></i>
                        </div>
                        <h3 class="h5">تشفير متقدم</h3>
                        <p class="mb-0">نستخدم تشفير AES-256 مع خوارزميات مخصصة لكل مستخدم لضمان أقصى درجات الأمان.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto" style="width: 60px; height: 60px;">
                            <i class="fas fa-user-shield fa-2x"></i>
                        </div>
                        <h3 class="h5">مصادقة ثنائية</h3>
                        <p class="mb-0">إمكانية تفعيل المصادقة الثنائية (2FA) لطبقة أمان إضافية لحسابك.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3 mb-3 mx-auto" style="width: 60px; height: 60px;">
                            <i class="fas fa-mobile-alt fa-2x"></i>
                        </div>
                        <h3 class="h5">متعدد الأجهزة</h3>
                        <p class="mb-0">استخدم حسابك على جميع أجهزتك مع مزامنة آمنة وفورية.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- قسم تسجيل الدخول -->
<section id="login-section" class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">تسجيل الدخول</h2>
                        
                        <?php if (isset($loginError)): ?>
                            <div class="alert alert-danger"><?php echo $loginError; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="login" value="1">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">اسم المستخدم</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">كلمة المرور</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-login-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">تذكرني</label>
                                </div>
                                <a href="forgot-password.php" class="text-muted">نسيت كلمة المرور؟</a>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">تسجيل الدخول</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- قسم إنشاء حساب -->
<section id="register-section" class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-body p-4">
                        <h2 class="text-center mb-4">إنشاء حساب جديد</h2>
                        
                        <?php if (isset($registerError)): ?>
                            <div class="alert alert-danger"><?php echo $registerError; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="register" value="1">
                            
                            <div class="mb-3">
                                <label for="reg-username" class="form-label">اسم المستخدم</label>
                                <input type="text" class="form-control" id="reg-username" name="username" required>
                                <small class="text-muted">يجب أن يكون بين 4-20 حرفاً (أحرف، أرقام، _)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg-email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="reg-email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg-password" class="form-label">كلمة المرور</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="reg-password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-reg-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary" type="button" id="generate-password">
                                        <i class="fas fa-random"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2">
                                    <small>قوة كلمة المرور: <span id="password-strength-text">غير معروف</span></small>
                                    <div class="progress mt-1" style="height: 5px;">
                                        <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reg-confirm-password" class="form-label">تأكيد كلمة المرور</label>
                                <input type="password" class="form-control" id="reg-confirm-password" name="confirm_password" required>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="agree-terms" name="agree_terms" required>
                                <label class="form-check-label" for="agree-terms">أوافق على <a href="terms.php">الشروط والأحكام</a> و <a href="privacy.php">سياسة الخصوصية</a></label>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 py-2">إنشاء حساب</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- الأسئلة الشائعة -->
<section class="faq-section py-5">
    <div class="container">
        <h2 class="text-center mb-5">الأسئلة الشائعة</h2>
        
        <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingOne">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                        كيف يتم حماية كلمات المرور الخاصة بي؟
                    </button>
                </h3>
                <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        نستخدم تشفير AES-256 القوي مع خوارزميات مخصصة لكل مستخدم. كلمات المرور يتم تشفيرها قبل تخزينها ولا يمكن لأي شخص، حتى فريقنا، الوصول إليها.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingTwo">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                        ماذا لو نسيت كلمة المرور الرئيسية؟
                    </button>
                </h3>
                <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        للأسف، لا يمكننا استعادة كلمة المرور الرئيسية بسبب نظام التشفير الذي نستخدمه. يمكنك إعادة تعيين كلمة المرور ولكنك ستحتاج إلى استخدام نسخة احتياطية لاستعادة كلمات المرور المشفرة.
                    </div>
                </div>
            </div>
            
            <div class="accordion-item">
                <h3 class="accordion-header" id="headingThree">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                        هل الخدمة مجانية؟
                    </button>
                </h3>
                <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                    <div class="accordion-body">
                        نعم، الخدمة الأساسية مجانية بالكامل وتشمل جميع الميزات الأمنية. لدينا خطة مميزة مستقبلاً لميزات إضافية مثل التخزين السحابي والمزيد.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// كود JavaScript لإدارة عرض/إخفاء كلمة المرور وتوليدها
document.addEventListener('DOMContentLoaded', function() {
    // إظهار/إخفاء كلمة المرور لتسجيل الدخول
    const toggleLoginPassword = document.getElementById('toggle-login-password');
    if (toggleLoginPassword) {
        toggleLoginPassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
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
    
    // إظهار/إخفاء كلمة المرور للتسجيل
    const toggleRegPassword = document.getElementById('toggle-reg-password');
    if (toggleRegPassword) {
        toggleRegPassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('reg-password');
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
    const generatePasswordBtn = document.getElementById('generate-password');
    if (generatePasswordBtn) {
        generatePasswordBtn.addEventListener('click', function() {
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
            
            document.getElementById('reg-password').value = password;
            document.getElementById('reg-confirm-password').value = password;
            checkPasswordStrength({ target: { value: password } });
        });
    }
    
    // تحليل قوة كلمة المرور
    const regPasswordInput = document.getElementById('reg-password');
    if (regPasswordInput) {
        regPasswordInput.addEventListener('input', checkPasswordStrength);
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