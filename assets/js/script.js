document.addEventListener('DOMContentLoaded', function() {
    // توليد كلمة مرور قوية
    const generatePasswordBtn = document.getElementById('generate-password');
    if (generatePasswordBtn) {
        generatePasswordBtn.addEventListener('click', generateStrongPassword);
    }

    // إظهار/إخفاء كلمة المرور
    const togglePasswordBtn = document.getElementById('toggle-password');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', togglePasswordVisibility);
    }

    // تحليل قوة كلمة المرور
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', checkPasswordStrength);
    }

    // نسخ كلمة المرور إلى الحافظة
    const copyButtons = document.querySelectorAll('.copy-password');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', copyToClipboard);
    });

    // إدارة الأحداث الأخرى
    initTooltips();
    initPasswordVisibilityToggles();
});

/**
 * توليد كلمة مرور قوية عشوائية
 */
function generateStrongPassword() {
    const length = 16;
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*()_+~`|}{[]:;?><,./-=";
    let password = "";
    
    // تأكد من احتواء كلمة المرور على أنواع مختلفة من الأحرف
    password += getRandomChar("ABCDEFGHIJKLMNOPQRSTUVWXYZ"); // حرف كبير
    password += getRandomChar("abcdefghijklmnopqrstuvwxyz"); // حرف صغير
    password += getRandomChar("0123456789"); // رقم
    password += getRandomChar("!@#$%^&*()_+~`|}{[]:;?><,./-="); // رمز خاص
    
    // إكمال باقي كلمة المرور
    for (let i = password.length; i < length; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    // خلط الأحرف للحصول على عشوائية أفضل
    password = shuffleString(password);
    
    document.getElementById('password').value = password;
    checkPasswordStrength({ target: { value: password } });
}

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

/**
 * تبديل إظهار/إخفاء كلمة المرور
 */
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('toggle-password');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleBtn.textContent = 'إخفاء';
        toggleBtn.classList.remove('btn-outline-secondary');
        toggleBtn.classList.add('btn-outline-primary');
    } else {
        passwordInput.type = 'password';
        toggleBtn.textContent = 'إظهار';
        toggleBtn.classList.remove('btn-outline-primary');
        toggleBtn.classList.add('btn-outline-secondary');
    }
}

/**
 * تحليل قوة كلمة المرور وعرض النتيجة
 */
function checkPasswordStrength(e) {
    const password = e.target.value;
    const strengthText = document.getElementById('password-strength');
    
    if (!password) {
        strengthText.textContent = 'غير معروف';
        strengthText.className = '';
        return;
    }
    
    // حساب القوة بناء على عدة عوامل
    let strength = 0;
    
    // طول كلمة المرور
    strength += Math.min(password.length * 3, 30);
    
    // تنوع الأحرف
    const hasLower = /[a-z]/.test(password);
    const hasUpper = /[A-Z]/.test(password);
    const hasNumbers = /[0-9]/.test(password);
    const hasSpecial = /[^A-Za-z0-9]/.test(password);
    
    if (hasLower) strength += 5;
    if (hasUpper) strength += 5;
    if (hasNumbers) strength += 5;
    if (hasSpecial) strength += 10;
    
    // تحقق من الأنماط الشائعة
    const commonPatterns = [
        '123', 'abc', 'qwerty', 'password', 'admin', 'welcome'
    ];
    
    commonPatterns.forEach(pattern => {
        if (password.toLowerCase().includes(pattern)) {
            strength -= 15;
        }
    });
    
    // تحديد مستوى القوة
    let strengthLevel, strengthClass;
    
    if (strength < 30) {
        strengthLevel = 'ضعيفة';
        strengthClass = 'strength-weak';
    } else if (strength < 60) {
        strengthLevel = 'متوسطة';
        strengthClass = 'strength-medium';
    } else {
        strengthLevel = 'قوية';
        strengthClass = 'strength-strong';
    }
    
    strengthText.textContent = strengthLevel;
    strengthText.className = strengthClass;
}

/**
 * نسخ النص إلى الحافظة
 */
function copyToClipboard(e) {
    const button = e.target;
    const passwordElement = button.closest('.password-item').querySelector('.password-value');
    const textToCopy = passwordElement.textContent;
    
    navigator.clipboard.writeText(textToCopy).then(() => {
        // تغيير نص الزر مؤقتًا للإشارة إلى النجاح
        const originalText = button.textContent;
        button.textContent = 'تم النسخ!';
        button.classList.remove('btn-info');
        button.classList.add('btn-success');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-info');
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy text: ', err);
        alert('تعذر نسخ النص. يرجى المحاولة مرة أخرى.');
    });
}

/**
 * تهيئة أداة التلميحات
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-toggle="tooltip"]');
    
    tooltipElements.forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const tooltipText = e.target.getAttribute('title') || e.target.dataset.tooltip;
    if (!tooltipText) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'custom-tooltip';
    tooltip.textContent = tooltipText;
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.position = 'absolute';
    tooltip.style.left = `${rect.left + window.scrollX}px`;
    tooltip.style.top = `${rect.top + window.scrollY - tooltip.offsetHeight - 10}px`;
    tooltip.style.opacity = '1';
}

function hideTooltip() {
    const tooltip = document.querySelector('.custom-tooltip');
    if (tooltip) {
        tooltip.remove();
    }
}

/**
 * تهيئة أزرار إظهار/إخفاء كلمات المرور في لوحة التحكم
 */
function initPasswordVisibilityToggles() {
    const toggleButtons = document.querySelectorAll('.toggle-password-visibility');
    
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const passwordId = this.dataset.passwordId;
            const passwordElement = document.getElementById(`password-${passwordId}`);
            const icon = this.querySelector('i');
            
            if (passwordElement.type === 'password') {
                passwordElement.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.title = 'إخفاء كلمة المرور';
            } else {
                passwordElement.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.title = 'إظهار كلمة المرور';
            }
        });
    });
}

/**
 * تأكيد قبل حذف كلمة المرور
 */
function confirmDelete(e) {
    e.preventDefault();
    const confirmDelete = confirm('هل أنت متأكد أنك تريد حذف كلمة المرور هذه؟ لا يمكن التراجع عن هذا الإجراء.');
    
    if (confirmDelete) {
        window.location.href = e.target.href;
    }
}

// إضافة تأكيد الحذف لجميع أزرار الحذف
const deleteButtons = document.querySelectorAll('.btn-delete');
deleteButtons.forEach(btn => {
    btn.addEventListener('click', confirmDelete);
});

// أنماط التلميحات المخصصة
const style = document.createElement('style');
style.textContent = `
    .custom-tooltip {
        position: absolute;
        background-color: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 14px;
        z-index: 1000;
        opacity: 0;
        transition: opacity 0.3s;
        pointer-events: none;
        max-width: 200px;
        text-align: center;
    }
`;
document.head.appendChild(style);