<?php
declare(strict_types=1);

/**
 * @var string $loiDangNhap
 * @var string $tenDangNhapNhap
 * @var string $tiepTuc
 */

require_once __DIR__ . '/../includes/session.php';

if (!isset($loiDangNhap, $tenDangNhapNhap, $tiepTuc)) {
    http_response_code(404);
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:,">
    <title>Đăng nhập · Tài sản QR</title>
    <link rel="stylesheet" href="<?= e(app_url('shared/login.css')) ?>">
</head>
<body>
<main class="login-layout">
    <section class="login-brand" aria-label="Giới thiệu hệ thống">
        <div class="login-brand-content">
            <div class="login-mark" aria-hidden="true">LOGO</div>
            <h1>QUẢN LÝ TÀI SẢN BẰNG MÃ QR</h1>
            <span>Phòng trực thiết bị</span>
        </div>
    </section>
    <section class="login-form-side" aria-labelledby="login-title">
        <div class="login-card">
            <h2 id="login-title">Đăng nhập</h2>
            <form action="<?= e(app_url('login.php')) ?>" method="post">
                <?= csrf_input() ?>
                <input type="hidden" name="tiep_tuc" value="<?= e(is_string($tiepTuc) ? $tiepTuc : '') ?>">
                <div class="login-field">
                    <label for="ten_dang_nhap">Tên đăng nhập</label>
                    <input id="ten_dang_nhap" name="ten_dang_nhap" type="text" maxlength="100"
                        value="<?= e($tenDangNhapNhap) ?>" placeholder="Nhập tên đăng nhập"
                        autocomplete="username" required autofocus>
                </div>
                <div class="login-field">
                    <label for="mat_khau">Mật khẩu</label>
                    <input id="mat_khau" name="mat_khau" type="password" placeholder="Nhập mật khẩu"
                        autocomplete="current-password" required>
                </div>
                <div class="login-message" aria-live="polite">
                    <?php if ($loiDangNhap !== ''): ?><p class="login-error" role="alert"><?= e($loiDangNhap) ?></p><?php endif; ?>
                    <?php if (isset($_GET['dang_xuat'])): ?><p class="login-success" role="status">Bạn đã đăng xuất thành công.</p><?php endif; ?>
                </div>
                <button class="login-submit" type="submit">Đăng nhập</button>
            </form>
            <p class="login-help"><a href="#" onclick="alert('Vui lòng liên hệ Quản trị viên để được cấp lại mật khẩu.'); return false;" class="forgot-link">Quên mật khẩu?</a></p>
        </div>
    </section>
</main>
</body>
</html>
