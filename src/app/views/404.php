<?php
/**
 * Giao diện trang thông báo lỗi 404 Not Found.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/partials/icon.php';

/**
 * @var string|null $noiDungLoi
 */

$tieuDeTrang = '404 - Không tìm thấy trang';
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
<body class="d-flex align-items-center min-vh-100 py-5 nen-trang">
<div class="container text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card the-noi-dung p-4 p-md-5">
                <div class="display-1 fw-bold mb-3 chu-canh-bao">404</div>
                <h2 class="tieu-de-khoi mb-2">Không tìm thấy trang</h2>
                <p class="mo-ta-phu mb-4">
                    <?= e($noiDungLoi ?? 'Đường dẫn hoặc chức năng bạn yêu cầu không tồn tại trên hệ thống.') ?>
                </p>
                <div>
                    <a href="<?= url('tong-quan.php') ?>" class="btn btn-chinh">
                        <?= icon('arrow-left', 'bieu-tuong-sm') ?>
                        <span>Về trang Tổng quan</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
