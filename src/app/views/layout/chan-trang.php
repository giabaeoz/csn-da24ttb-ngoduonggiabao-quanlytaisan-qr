<?php
/**
 * Layout phần chân trang dùng chung (Footer & Scripts).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}
?>
</main>
    </div>
</div>

<footer class="footer mt-auto py-3 bg-white border-top text-center text-muted small">
    <div class="container">
        <span>&copy; <?= date('Y') ?> Đồ án Cơ sở ngành - Hệ thống quản lý mượn trả và kiểm kê tài sản bằng mã QR</span>
    </div>
</footer>

<script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
