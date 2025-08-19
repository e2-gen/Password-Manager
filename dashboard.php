<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth($pdo);

if(!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// جلب كلمات المرور للمستخدم
$stmt = $pdo->prepare("SELECT * FROM passwords WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$passwords = $stmt->fetchAll();

include 'templates/header.php';
?>

<div class="container">
    <h2>مرحبًا <?php echo htmlspecialchars($_SESSION['username']); ?></h2>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">إضافة كلمة مرور جديدة</h5>
                    <a href="add-password.php" class="btn btn-primary">إضافة</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">كلمات المرور الخاصة بك</h5>
                    
                    <?php if(count($passwords) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>الموقع/التطبيق</th>
                                    <th>اسم المستخدم</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($passwords as $password): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($password['service']); ?></td>
                                        <td><?php echo htmlspecialchars($password['username']); ?></td>
                                        <td>
                                            <a href="view-password.php?id=<?php echo $password['id']; ?>" class="btn btn-sm btn-info">عرض</a>
                                            <a href="edit-password.php?id=<?php echo $password['id']; ?>" class="btn btn-sm btn-warning">تعديل</a>
                                            <a href="delete-password.php?id=<?php echo $password['id']; ?>" class="btn btn-sm btn-danger">حذف</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>لا توجد كلمات مرور مسجلة بعد.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
