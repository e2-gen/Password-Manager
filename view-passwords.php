<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth($pdo);

if (!$auth->isLoggedIn()) {
    redirect('login.php');
}

$pageTitle = "كلمات المرور | Password Vault";
$search = '';
$category = '';
$sort = 'service';

// معالجة البحث والتصفية
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : '';
    $sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'service';
}

// بناء استعلام SQL مع عوامل التصفية
$sql = "SELECT * FROM passwords WHERE user_id = :user_id";
$params = [':user_id' => $_SESSION['user_id']];

if (!empty($search)) {
    $sql .= " AND (service LIKE :search OR username LIKE :search OR notes LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($category)) {
    $sql .= " AND category = :category";
    $params[':category'] = $category;
}

// إضافة الترتيب
$validSorts = ['service', 'username', 'last_updated'];
$sort = in_array($sort, $validSorts) ? $sort : 'service';
$sql .= " ORDER BY $sort ASC";

// جلب كلمات المرور
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$passwords = $stmt->fetchAll();

// جلب الفئات للمستخدم
$stmt = $pdo->prepare("SELECT DISTINCT category FROM passwords WHERE user_id = ? AND category IS NOT NULL");
$stmt->execute([$_SESSION['user_id']]);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

include 'templates/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="assets/images/user-avatar.png" alt="صورة المستخدم" class="rounded-circle" width="100">
                        <h5 class="mt-3"><?php echo htmlspecialchars($_SESSION['username']); ?></h5>
                    </div>
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> لوحة التحكم
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="view-passwords.php">
                                <i class="fas fa-key me-2"></i> كلمات المرور
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="security-settings.php">
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
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-key me-2"></i> كلمات المرور</h4>
                        <a href="add-password.php" class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i> إضافة جديدة
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- شريط البحث والتصفية -->
                    <form method="GET" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="ابحث في كلمات المرور..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="category">
                                    <option value="">كل الفئات</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="sort">
                                    <option value="service" <?php echo $sort === 'service' ? 'selected' : ''; ?>>ترتيب حسب الخدمة</option>
                                    <option value="username" <?php echo $sort === 'username' ? 'selected' : ''; ?>>ترتيب حسب اسم المستخدم</option>
                                    <option value="last_updated" <?php echo $sort === 'last_updated' ? 'selected' : ''; ?>>ترتيب حسب آخر تحديث</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <!-- قائمة كلمات المرور -->
                    <?php if (count($passwords) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>الخدمة</th>
                                        <th>اسم المستخدم</th>
                                        <th>الفئة</th>
                                        <th>آخر تحديث</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($passwords as $password): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($password['logo_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($password['logo_url']); ?>" alt="<?php echo htmlspecialchars($password['service']); ?>" class="rounded-circle me-2" width="30" height="30">
                                                <?php endif; ?>
                                                <strong><?php echo htmlspecialchars($password['service']); ?></strong>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($password['username']); ?></td>
                                        <td>
                                            <?php if (!empty($password['category'])): ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($password['category']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">بدون فئة</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatDate($password['last_updated'], 'Y-m-d H:i'); ?></td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-info view-password" data-id="<?php echo $password['id']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="edit-password.php?id=<?php echo $password['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete-password.php?id=<?php echo $password['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف هذه كلمة المرور؟');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i> 
                            <?php echo empty($search) ? 'لا توجد كلمات مرور مسجلة بعد.' : 'لم يتم العثور على نتائج مطابقة للبحث.'; ?>
                            <a href="add-password.php" class="alert-link">إضافة كلمة مرور جديدة</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نموذج عرض كلمة المرور -->
<div class="modal fade" id="viewPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">عرض كلمة المرور</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">الخدمة:</label>
                    <p class="form-control-static" id="modal-service"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label">رابط الموقع:</label>
                    <p class="form-control-static" id="modal-url"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label">اسم المستخدم:</label>
                    <p class="form-control-static" id="modal-username"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label">كلمة المرور:</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="modal-password" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="toggleModalPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-outline-secondary" type="button" id="copyPassword">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">الفئة:</label>
                    <p class="form-control-static" id="modal-category"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label">ملاحظات:</label>
                    <p class="form-control-static" id="modal-notes"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label">آخر تحديث:</label>
                    <p class="form-control-static" id="modal-updated"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // عرض كلمة المرور في المودال
    const viewButtons = document.querySelectorAll('.view-password');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordId = this.getAttribute('data-id');
            
            // جلب بيانات كلمة المرور عبر AJAX
            fetch('get-password.php?id=' + passwordId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // تعبئة البيانات في المودال
                        document.getElementById('modal-service').textContent = data.password.service;
                        document.getElementById('modal-url').innerHTML = data.password.url 
                            ? `<a href="${data.password.url}" target="_blank">${data.password.url}</a>` 
                            : 'غير متوفر';
                        document.getElementById('modal-username').textContent = data.password.username;
                        document.getElementById('modal-password').value = data.password.password;
                        document.getElementById('modal-category').textContent = data.password.category || 'بدون فئة';
                        document.getElementById('modal-notes').textContent = data.password.notes || 'لا توجد ملاحظات';
                        document.getElementById('modal-updated').textContent = data.password.last_updated;
                        
                        // عرض المودال
                        const modal = new bootstrap.Modal(document.getElementById('viewPasswordModal'));
                        modal.show();
                    } else {
                        alert('حدث خطأ أثناء جلب بيانات كلمة المرور');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ أثناء جلب بيانات كلمة المرور');
                });
        });
    });
    
    // إظهار/إخفاء كلمة المرور في المودال
    const toggleModalPassword = document.getElementById('toggleModalPassword');
    const modalPasswordInput = document.getElementById('modal-password');
    
    if (toggleModalPassword && modalPasswordInput) {
        toggleModalPassword.addEventListener('click', function() {
            const icon = this.querySelector('i');
            
            if (modalPasswordInput.type === 'password') {
                modalPasswordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                modalPasswordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }
    
    // نسخ كلمة المرور إلى الحافظة
    const copyPassword = document.getElementById('copyPassword');
    if (copyPassword) {
        copyPassword.addEventListener('click', function() {
            const password = document.getElementById('modal-password').value;
            
            navigator.clipboard.writeText(password).then(() => {
                const originalIcon = this.querySelector('i').className;
                this.querySelector('i').className = 'fas fa-check';
                
                setTimeout(() => {
                    this.querySelector('i').className = originalIcon;
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy password: ', err);
                alert('تعذر نسخ كلمة المرور');
            });
        });
    }
});
</script>

<?php
include 'templates/footer.php';
?>