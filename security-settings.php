<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth($pdo);

if (!$auth->isLoggedIn()) {
    redirect('login.php');
}

$pageTitle = "الإعدادات الأمنية | Password Vault";
$success = '';
$error = '';

// جلب معلومات المستخدم
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// معالجة تغيير كلمة المرور
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // التحقق من كلمة المرور الحالية
    if (!password_verify($currentPassword . PEPPER, $user['password'])) {
        $error = "كلمة المرور الحالية غير صحيحة";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "كلمتا المرور الجديدتان غير متطابقتين";
    } else {
        $passwordStrength = checkPasswordStrength($newPassword);
        if ($passwordStrength['strength'] < 3) {
            $error = "كلمة المرور ضعيفة جداً. " . implode(' ', $passwordStrength['messages']);
        } else {
            // تحديث كلمة المرور
            $hashedPassword = password_hash($newPassword . PEPPER, PASSWORD_ARGON2ID);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($stmt->execute([$hashedPassword, $_SESSION['user_id']])) {
                $success = "تم تغيير كلمة المرور بنجاح";
                logActivity("تم تغيير كلمة المرور للمستخدم: " . $user['username'], 'info', $_SESSION['user_id']);
                
                // إرسال إشعار بالبريد الإلكتروني
                sendEmail($user['email'], "تم تغيير كلمة المرور", 
                    "تم تغيير كلمة المرور لحسابك في " . SITE_NAME . " بتاريخ " . date('Y-m-d H:i:s'));
            } else {
                $error = "حدث خطأ أثناء تحديث كلمة المرور";
            }
        }
    }
}

// معالجة تفعيل المصادقة الثنائية
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enable_2fa'])) {
    require_once 'includes/TwoFactorAuth.php';
    $tfa = new TwoFactorAuth();
    
    $secret = $tfa->createSecret();
    $qrCodeUrl = $tfa->getQRCodeImageAsDataUri(SITE_NAME . ':' . $user['email'], $secret);
    
    $_SESSION['2fa_secret'] = $secret;
    $_SESSION['2fa_qrcode'] = $qrCodeUrl;
}

// معالجة تأكيد المصادقة الثنائية
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_2fa'])) {
    require_once 'includes/TwoFactorAuth.php';
    $tfa = new TwoFactorAuth();
    
    $code = $_POST['2fa_code'];
    $secret = $_SESSION['2fa_secret'];
    
    if ($tfa->verifyCode($secret, $code)) {
        $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = ? WHERE id = ?");
        if ($stmt->execute([$secret, $_SESSION['user_id']])) {
            $success = "تم تفعيل المصادقة الثنائية بنجاح";
            logActivity("تم تفعيل المصادقة الثنائية للمستخدم: " . $user['username'], 'info', $_SESSION['user_id']);
            unset($_SESSION['2fa_secret'], $_SESSION['2fa_qrcode']);
        } else {
            $error = "حدث خطأ أثناء حفظ إعدادات المصادقة الثنائية";
        }
    } else {
        $error = "رمز التحقق غير صحيح";
    }
}

// معالجة تعطيل المصادقة الثنائية
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['disable_2fa'])) {
    $stmt = $pdo->prepare("UPDATE users SET two_factor_secret = NULL WHERE id = ?");
    if ($stmt->execute([$_SESSION['user_id']])) {
        $success = "تم تعطيل المصادقة الثنائية بنجاح";
        logActivity("تم تعطيل المصادقة الثنائية للمستخدم: " . $user['username'], 'info', $_SESSION['user_id']);
    } else {
        $error = "حدث خطأ أثناء تعطيل المصادقة الثنائية";
    }
}

include 'templates/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="assets/images/user-avatar.png" alt="صورة المستخدم" class="rounded-circle" width="100">
                        <h5 class="mt-3"><?php echo htmlspecialchars($user['username']); ?></h5>
                    </div>
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="view-passwords.php">
                                <i class="fas fa-key me-2"></i> كلمات المرور
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="security-settings.php">
                                <i class="fas fa-shield-alt me-2"></i> الإعدادات الأمنية
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="fas fa-user me-2"></i> الملف الشخصي
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i> الإعدادات الأمنية</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <!-- تغيير كلمة المرور -->
                    <div class="mb-5">
                        <h5 class="mb-3"><i class="fas fa-key me-2"></i> تغيير كلمة المرور</h5>
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="current_password" class="form-label">كلمة المرور الحالية</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label">كلمة المرور الجديدة</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="password-strength mt-2">
                                        <small>قوة كلمة المرور: <span id="password-strength-text">غير معروف</span></small>
                                        <div class="progress mt-1" style="height: 5px;">
                                            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">تأكيد كلمة المرور الجديدة</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> حفظ التغييرات
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- المصادقة الثنائية -->
                    <div class="mb-5">
                        <h5 class="mb-3"><i class="fas fa-mobile-alt me-2"></i> المصادقة الثنائية (2FA)</h5>
                        
                        <?php if (!empty($user['two_factor_secret'])): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i> المصادقة الثنائية مفعلة على حسابك.
                            </div>
                            <form method="POST" action="">
                                <button type="submit" name="disable_2fa" class="btn btn-danger">
                                    <i class="fas fa-times me-1"></i> تعطيل المصادقة الثنائية
                                </button>
                            </form>
                        <?php else: ?>
                            <?php if (isset($_SESSION['2fa_secret'])): ?>
                                <div class="alert alert-info">
                                    <p class="mb-3">1. قم بمسح رمز QR التالي باستخدام تطبيق المصادقة مثل Google Authenticator أو Microsoft Authenticator:</p>
                                    <div class="text-center mb-3">
                                        <img src="<?php echo $_SESSION['2fa_qrcode']; ?>" alt="QR Code" class="img-thumbnail" style="max-width: 200px;">
                                    </div>
                                    <p class="mb-3">2. أو أدخل المفتاح السري يدوياً:</p>
                                    <div class="alert alert-secondary mb-3">
                                        <code><?php echo chunk_split($_SESSION['2fa_secret'], 4, ' '); ?></code>
                                    </div>
                                    <p class="mb-3">3. أدخل رمز التحقق من التطبيق:</p>
                                    <form method="POST" action="">
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" name="2fa_code" placeholder="رمز التحقق" required>
                                            </div>
                                            <div class="col-md-8">
                                                <button type="submit" name="confirm_2fa" class="btn btn-success me-2">
                                                    <i class="fas fa-check me-1"></i> تأكيد
                                                </button>
                                                <button type="button" onclick="location.reload()" class="btn btn-secondary">
                                                    <i class="fas fa-times me-1"></i> إلغاء
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i> المصادقة الثنائية غير مفعلة على حسابك.
                                </div>
                                <form method="POST" action="">
                                    <button type="submit" name="enable_2fa" class="btn btn-success">
                                        <i class="fas fa-plus me-1"></i> تفعيل المصادقة الثنائية
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- نشاط الحساب -->
                    <div>
                        <h5 class="mb-3"><i class="fas fa-history me-2"></i> نشاط الحساب</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>النشاط</th>
                                        <th>عنوان IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $activities = $stmt->fetchAll();
                                    
                                    if (count($activities) > 0):
                                        foreach ($activities as $activity):
                                    ?>
                                    <tr>
                                        <td><?php echo formatDate($activity['created_at'], 'Y-m-d H:i'); ?></td>
                                        <td><?php echo htmlspecialchars($activity['activity']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                                    </tr>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="3" class="text-center">لا توجد سجلات نشاط</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="activity-log.php" class="btn btn-outline-primary">عرض سجل النشاط الكامل</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // إظهار/إخفاء كلمة المرور الجديدة
    const toggleNewPassword = document.getElementById('toggleNewPassword');
    const newPasswordInput = document.getElementById('new_password');
    
    if (toggleNewPassword && newPasswordInput) {
        toggleNewPassword.addEventListener('click', function() {
            const icon = this.querySelector('i');
            
            if (newPasswordInput.type === 'password') {
                newPasswordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                newPasswordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }
    
    // تحليل قوة كلمة المرور
    const passwordInput = document.getElementById('new_password');
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
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