</main>

<!-- الفوتر -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5 class="mb-3">
                    <i class="fas fa-lock me-2"></i> Password Vault
                </h5>
                <p>نظام آمن لإدارة كلمات المرور وحماية بياناتك الخاصة.</p>
                <div class="social-icons">
                    <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="text-white"><i class="fab fa-github"></i></a>
                </div>
            </div>
            
            <div class="col-md-2">
                <h5 class="mb-3">روابط سريعة</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>" class="text-white-50">الرئيسية</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/about.php" class="text-white-50">حول النظام</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/privacy.php" class="text-white-50">الخصوصية</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/contact.php" class="text-white-50">اتصل بنا</a></li>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h5 class="mb-3">الأمان والحماية</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/security-tips.php" class="text-white-50">نصائح أمنية</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/faq.php" class="text-white-50">الأسئلة الشائعة</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/report.php" class="text-white-50">الإبلاغ عن مشكلة</a></li>
                </ul>
            </div>
            
            <div class="col-md-3">
                <h5 class="mb-3">تواصل معنا</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-envelope me-2"></i> info@passwordvault.com</li>
                    <li class="mb-2"><i class="fas fa-phone me-2"></i> +966 12 345 6789</li>
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> الرياض، المملكة العربية السعودية</li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4 bg-secondary">
        
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Password Vault. جميع الحقوق محفوظة.</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="mb-0">
                    <a href="<?php echo BASE_URL; ?>/terms.php" class="text-white-50 me-3">الشروط والأحكام</a>
                    <a href="<?php echo BASE_URL; ?>/privacy.php" class="text-white-50">سياسة الخصوصية</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>

<!-- Scripts إضافية حسب الصفحة -->
<?php if (isset($customScripts)): ?>
    <?php foreach ($customScripts as $script): ?>
        <script src="<?php echo BASE_URL; ?>/assets/js/<?php echo $script; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Scripts مضمنة حسب الصفحة -->
<?php if (isset($inlineScript)): ?>
    <script>
        <?php echo $inlineScript; ?>
    </script>
<?php endif; ?>
</body>
</html>