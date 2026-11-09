<?php
/**
 * Giao diện trang Đăng nhập hệ thống.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDe = 'Đăng nhập hệ thống';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tieuDe) ?> - <?= e(cau_hinh('ung_dung.ten', 'QLTS-QR')) ?></title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/dang-nhap.css') ?>">
</head>
<body class="bg-dang-nhap d-flex align-items-center min-vh-100 py-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-5 col-xl-4">
            
            <div class="card shadow border-0 rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white text-center py-4">
                    <div class="fs-1 mb-2">📦</div>
                    <h4 class="fw-bold mb-1">HỆ THỐNG QUẢN LÝ TÀI SẢN</h4>
                    <p class="small mb-0 opacity-75">Mượn - Trả & Kiểm kê bằng mã QR</p>
                </div>

                <div class="card-body p-4">
                    <?php require __DIR__ . '/partials/thong-bao.php'; ?>

                    <?php if (!empty($thongBaoLoi)): ?>
                    <div class="alert alert-danger alert-dismissible fade show small mb-3" role="alert">
                        <span><?= e($thongBaoLoi) ?></span>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form action="<?= url('login.php') ?>" method="POST" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="ten_dang_nhap" class="form-label fw-semibold small text-secondary">
                                Tên đăng nhập
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg fs-6" 
                                   id="ten_dang_nhap" 
                                   name="ten_dang_nhap" 
                                   value="<?= e($tenDangNhapCu ?? '') ?>" 
                                   placeholder="Nhập tên đăng nhập" 
                                   required 
                                   autofocus>
                        </div>

                        <div class="mb-4">
                            <label for="mat_khau" class="form-label fw-semibold small text-secondary">
                                Mật khẩu
                            </label>
                            <input type="password" 
                                   class="form-control form-control-lg fs-6" 
                                   id="mat_khau" 
                                   name="mat_khau" 
                                   placeholder="Nhập mật khẩu" 
                                   required>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold fs-6 shadow-sm">
                            Đăng nhập
                        </button>
                    </form>
                </div>

                <div class="card-footer bg-light border-0 text-center py-3">
                    <div class="text-muted small">
                        <strong>Tài khoản mẫu thử nghiệm:</strong><br>
                        <code>admin</code> / <code>admin123</code> (Quản trị)<br>
                        <code>nhanvien01</code> / <code>nhanvien123</code> (Trực D6)<br>
                        <code>quanly01</code> / <code>quanly123</code> (Quản lý)
                    </div>
                </div>
            </div>

            <div class="text-center text-muted small mt-4">
                Đồ án Cơ sở ngành &bull; Kế thừa & Rebuild 2026
            </div>

        </div>
    </div>
</div>

<script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
