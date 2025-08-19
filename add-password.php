<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);

if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = $_POST['service'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $notes = $_POST['notes'];
    
    // تشفير كلمة المرور باستخدام نظام التشفير المخصص
    $encryptedPassword = $_SESSION['encryptor']->encrypt($password);
    
    // حفظ البيانات في قاعدة البيانات
    $stmt = $pdo->prepare("INSERT INTO passwords (user_id, service, username, password, notes, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if($stmt->execute([$_SESSION['user_id'], $service, $username, $encryptedPassword, $notes])) {
        header("Location: dashboard.php?success=1");
        exit();
    } else {
        $error = "حدث خطأ أثناء حفظ كلمة المرور";
    }
}

include 'templates/header.php';
?>

<div class="container">
    <h2>إضافة كلمة مرور جديدة</h2>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-group">
            <label for="service">الموقع/التطبيق:</label>
            <input type="text" class="form-control" id="service" name="service" required>
        </div>
        
        <div class="form-group">
            <label for="username">اسم المستخدم:</label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>
        
        <div class="form-group">
            <label for="password">كلمة المرور:</label>
            <div class="input-group">
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" id="generate-password">توليد</button>
                    <button class="btn btn-outline-secondary" type="button" id="toggle-password">إظهار</button>
                </div>
            </div>
            <small class="form-text text-muted">قوة كلمة المرور: <span id="password-strength">غير معروف</span></small>
        </div>
        
        <div class="form-group">
            <label for="notes">ملاحظات:</label>
            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
        </div>
        
        <button type="submit" class="btn btn-primary">حفظ</button>
        <a href="dashboard.php" class="btn btn-secondary">إلغاء</a>
    </form>
</div>

<script src="../assets/js/script.js"></script>
<?php include 'templates/footer.php'; ?>
