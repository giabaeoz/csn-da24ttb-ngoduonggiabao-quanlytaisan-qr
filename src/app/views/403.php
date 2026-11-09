<?php
/**
 * Giao diện trang thông báo lỗi 403 Forbidden.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = '403 - Truy cập bị từ chối';
$nguoiDung = nguoi_dung_hien_tai();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tieuDeTrang) ?> - <?= e(cau_hinh('ung_dung.ten', 'QLTS-QR')) ?></title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body class="bg-light d-flex align-items-center min-vh-100 py-5">
<div class="container text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
                <div class="display-1 text-danger fw-bold mb-3">403</div>
                <h3 class="fw-bold mb-2">Truy cập bị từ chối</h3>
                <p class="text-secondary mb-4">
                    <?= e($noiDungLoi ?? 'Bạn không có đủ quyền hạn để truy cập vào tài nguyên hoặc thực thi chức năng này.') ?>
                </p>
                <div>
                    <a href="<?= url('tong-quan.php') ?>" class="btn btn-primary fw-semibold px-4 py-2 me-2">
                        Về trang Tổng quan
                    </a>
                    <a href="<?= url('login.php') ?>" class="btn btn-outline-secondary fw-semibold px-4 py-2">
                        Đăng nhập tài khoản khác
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>

