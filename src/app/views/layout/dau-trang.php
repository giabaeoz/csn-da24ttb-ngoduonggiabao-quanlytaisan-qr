<?php
/**
 * Layout phần đầu trang dùng chung (Header & Topbar).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$nguoiDung = nguoi_dung_hien_tai();
$tieuDe = $tieuDeTrang ?? cau_hinh('ung_dung.ten', 'Quản lý tài sản - QR');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tieuDe) ?> - <?= e(cau_hinh('ung_dung.ten', 'QLTS-QR')) ?></title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>
<body class="bg-light">

<!-- Thanh điều hướng trên cùng (Topbar) -->
<header class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm px-3">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= url('tong-quan.php') ?>">
            <span class="me-2">📦</span>
            <span>QLTS - QR</span>
        </a>

        <?php if ($nguoiDung !== null): ?>
        <div class="d-flex align-items-center ms-auto">
            <div class="text-white me-3 text-end d-none d-sm-block">
                <div class="fw-semibold small"><?= e($nguoiDung['ho_ten']) ?></div>
                <div class="small opacity-75">
                    <span class="badge bg-white text-primary me-1">
                        <?= e(TEN_VAI_TRO[$nguoiDung['vai_tro']] ?? $nguoiDung['vai_tro']) ?>
                    </span>
                    <?php if (!empty($nguoiDung['ten_phong_truc'])): ?>
                        <span>(<?= e($nguoiDung['ten_phong_truc']) ?>)</span>
                    <?php endif; ?>
                </div>
            </div>

            <form action="<?= url('logout.php') ?>" method="POST" class="d-inline m-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-light" title="Đăng xuất khỏi hệ thống">
                    Đăng xuất
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</header>

<div class="container-fluid">
    <div class="row">
