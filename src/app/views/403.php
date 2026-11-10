<?php
/**
 * Giao diện trang thông báo lỗi 403 Forbidden.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var string|null $noiDungLoi
 */

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
<body class="d-flex align-items-center min-vh-100 py-5" style="background-color: var(--nen-trang);">
<div class="container text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card the-noi-dung p-4 p-md-5">
                <div class="display-1 fw-bold mb-3" style="color: var(--nguy-hiem);">403</div>
                <h2 class="tieu-de-khoi mb-2">Truy cập bị từ chối</h2>
                <p class="mo-ta-phu mb-4">
                    <?= e($noiDungLoi ?? 'Bạn không có đủ quyền hạn để truy cập vào tài nguyên hoặc thực thi chức năng này.') ?>
                </p>
                <div>
                    <a href="<?= url('tong-quan.php') ?>" class="btn btn-chinh me-2">
                        Về trang Tổng quan
                    </a>
                    <a href="<?= url('login.php') ?>" class="btn btn-phu">
                        Đăng nhập tài khoản khác
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
