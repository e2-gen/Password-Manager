<?php
require_once 'config.php';

/**
 * دالة لإعادة توجيه المستخدم إلى صفحة أخرى
 * 
 * @param string $url الصفحة المراد التوجيه إليها
 * @param int $statusCode كود حالة HTTP (اختياري)
 */
function redirect($url, $statusCode = 303) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * دالة لتنظيف بيانات الإدخال
 * 
 * @param string $data البيانات المراد تنظيفها
 * @return string البيانات النظيفة
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * دالة للتحقق من صحة عنوان البريد الإلكتروني
 * 
 * @param string $email البريد الإلكتروني المراد التحقق منه
 * @return bool true إذا كان البريد صحيحًا، false إذا كان غير صحيح
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * دالة لعرض رسائل التنبيه
 * 
 * @param string $type نوع التنبيه (success, error, warning, info)
 * @param string $message نص الرسالة
 * @return string كود HTML للتنبيه
 */
function displayAlert($type, $message) {
    $alertTypes = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $alertTypes[$type] ?? $alertTypes['info'];
    
    return '<div class="alert ' . $class . ' alert-dismissible fade show" role="alert">
              ' . $message . '
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>';
}

/**
 * دالة للتحقق من قوة كلمة المرور
 * 
 * @param string $password كلمة المرور المراد التحقق منها
 * @return array تحتوي على مستوى القوة ورسالة توضيحية
 */
function checkPasswordStrength($password) {
    $strength = 0;
    $messages = [];
    
    // التحقق من الطول
    if (strlen($password) >= 12) {
        $strength += 2;
    } elseif (strlen($password) >= 8) {
        $strength += 1;
        $messages[] = 'كلمة المرور قصيرة، يفضل أن تكون 12 حرفًا أو أكثر';
    } else {
        $messages[] = 'كلمة المرور قصيرة جدًا';
    }
    
    // التحقق من وجود أحرف متنوعة
    if (preg_match('/[A-Z]/', $password)) $strength += 1;
    if (preg_match('/[a-z]/', $password)) $strength += 1;
    if (preg_match('/[0-9]/', $password)) $strength += 1;
    if (preg_match('/[^A-Za-z0-9]/', $password)) $strength += 1;
    
    // تحديد مستوى القوة
    if ($strength >= 6) {
        $level = 'قوية جدًا';
    } elseif ($strength >= 4) {
        $level = 'قوية';
    } elseif ($strength >= 2) {
        $level = 'متوسطة';
        $messages[] = 'يفضل إضافة رموز خاصة وأحرف كبيرة وصغيرة';
    } else {
        $level = 'ضعيفة';
        $messages[] = 'كلمة المرور ضعيفة جدًا ويجب تعزيزها';
    }
    
    // الكشف عن كلمات المرور الشائعة
    $commonPasswords = ['123456', 'password', '123456789', '12345678', '12345'];
    if (in_array(strtolower($password), array_map('strtolower', $commonPasswords))) {
        $level = 'ضعيفة جدًا';
        $messages[] = 'كلمة المرور مستخدمة بشكل شائع وسهلة الاختراق';
    }
    
    return [
        'level' => $level,
        'messages' => $messages,
        'strength' => $strength
    ];
}

/**
 * دالة لتسجيل أحداث النظام (Logging)
 * 
 * @param string $message رسالة السجل
 * @param string $level مستوى السجل (info, warning, error)
 * @param int $userId معرّف المستخدم (اختياري)
 */
function logActivity($message, $level = 'info', $userId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, activity, level, ip_address, user_agent, created_at) 
                              VALUES (:user_id, :activity, :level, :ip_address, :user_agent, NOW())");
        
        $stmt->execute([
            ':user_id' => $userId,
            ':activity' => $message,
            ':level' => $level,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN'
        ]);
    } catch (PDOException $e) {
        // في حالة فشل التسجيل، يمكنك تسجيل الخطأ في ملف السجل
        error_log('Failed to log activity: ' . $e->getMessage());
    }
}

/**
 * دالة للتحقق من وجود كلمة مرور منتهية الصلاحية
 * 
 * @param int $userId معرّف المستخدم
 * @param int $days عدد الأيام للتحقق
 * @return array كلمات المرور المنتهية الصلاحية
 */
function checkExpiredPasswords($userId, $days = 90) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM passwords 
                          WHERE user_id = :user_id 
                          AND last_updated < DATE_SUB(NOW(), INTERVAL :days DAY)");
    $stmt->execute([':user_id' => $userId, ':days' => $days]);
    
    return $stmt->fetchAll();
}

/**
 * دالة لإنشاء token CSRF
 * 
 * @return string token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * دالة للتحقق من token CSRF
 * 
 * @param string $token token المراد التحقق منه
 * @return bool true إذا كان صحيحًا، false إذا كان غير صحيح
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * دالة لإنشاء كلمة مرور مؤقتة
 * 
 * @param int $length طول كلمة المرور
 * @return string كلمة المرور المؤقتة
 */
function generateTemporaryPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * دالة للتحقق من صحة URL
 * 
 * @param string $url URL المراد التحقق منه
 * @return bool true إذا كان URL صحيحًا، false إذا كان غير صحيح
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * دالة لتنسيق التاريخ
 * 
 * @param string $date التاريخ المراد تنسيقه
 * @param string $format التنسيق المطلوب (اختياري)
 * @return string التاريخ المنسق
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

/**
 * دالة للتحقق من صلاحية الصلاحيات
 * 
 * @param int $userId معرّف المستخدم
 * @param string $permission الصلاحية المطلوبة
 * @return bool true إذا كان لديه الصلاحية، false إذا لم يكن لديه
 */
function checkPermission($userId, $permission) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_permissions 
                          WHERE user_id = :user_id AND permission = :permission");
    $stmt->execute([':user_id' => $userId, ':permission' => $permission]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * دالة لإنشاء slug من النص
 * 
 * @param string $text النص المراد تحويله
 * @return string النص بعد التحويل إلى slug
 */
function createSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    return empty($text) ? 'n-a' : $text;
}