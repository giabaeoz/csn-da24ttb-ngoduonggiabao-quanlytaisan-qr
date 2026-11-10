<?php
/**
 * Giao diện trang Đăng nhập hệ thống - Khớp chính xác Trang đăng nhập.pdf
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/partials/icon.php';

/**
 * @var string $thongBaoLoi
 * @var string $tenDangNhapCu
 */

$tieuDe = 'Đăng nhập';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tieuDe) ?> - <?= e(cau_hinh('ung_dung.ten', 'Quản lý tài sản bằng mã QR')) ?></title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/dang-nhap.css') ?>">
</head>
<body>

<div class="trang-dang-nhap-khung">
    <!-- Cột bên trái: Thương hiệu & Thông tin hệ thống -->
    <div class="cot-thuong-hieu">
        <div></div>
        <div class="vung-giua">
            <div class="logo-lon">LOGO</div>
            <div class="tieu-de-thuong-hieu">QUẢN LÝ TÀI SẢN BẰNG MÃ QR</div>
            <div class="mo-ta-thuong-hieu">Hệ thống quản lý mượn - trả và kiểm kê tài sản bằng mã QR</div>
        </div>
        <div class="chan-thuong-hieu">Phòng trực thiết bị</div>
    </div>

    <!-- Cột bên phải: Khung đăng nhập -->
    <div class="cot-bieu-mau">
        <div class="hop-dang-nhap">
            <div class="tieu-de-dang-nhap">Đăng nhập</div>

            <?php if (!empty($thongBaoLoi)): ?>
            <div class="alert alert-danger small mb-3" role="alert">
                <?= icon('alert-circle') ?>
                <span><?= e($thongBaoLoi) ?></span>
            </div>
            <?php endif; ?>

            <form action="<?= url('login.php') ?>" method="POST" autocomplete="off">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="ten_dang_nhap" class="form-label">Tên đăng nhập</label>
                    <input type="text" 
                           class="form-control" 
                           id="ten_dang_nhap" 
                           name="ten_dang_nhap" 
                           value="<?= e($tenDangNhapCu ?? '') ?>" 
                           placeholder="Nhập tên đăng nhập" 
                           required 
                           autofocus>
                </div>

                <div class="mb-4">
                    <label for="mat_khau" class="form-label">Mật khẩu</label>
                    <input type="password" 
                           class="form-control" 
                           id="mat_khau" 
                           name="mat_khau" 
                           placeholder="Nhập mật khẩu" 
                           required>
                </div>

                <button type="submit" class="btn btn-chinh w-100">
                    Đăng nhập
                </button>
            </form>

            <a class="lien-ket-quen-mat-khau" onclick="alert('Vui lòng liên hệ Quản trị viên hệ thống để được hỗ trợ cấp lại mật khẩu.')">
                Quên mật khẩu?
            </a>
        </div>
    </div>
</div>

<script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
