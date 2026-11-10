<?php

/**
 * Layout phần đầu trang dùng chung (Header & Topbar) - Bám sát phác thảo
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var string|null $tieuDeTrang
 */

$nguoiDung = nguoi_dung_hien_tai();
$tieuDe = $tieuDeTrang ?? cau_hinh('ung_dung.ten', 'Quản lý tài sản bằng mã QR');
$tepHienTai = basename($_SERVER['PHP_SELF'] ?? '');
$thuMucHienTai = basename(dirname($_SERVER['PHP_SELF'] ?? ''));

// Xác định chữ viết tắt avatar và nhãn vai trò
$chuVietTat = match ($nguoiDung['vai_tro'] ?? '') {
    'QUAN_TRI' => 'QT',
    'QUAN_LY'  => 'QL',
    default    => 'NV',
};

$nhanVaiTro = match ($nguoiDung['vai_tro'] ?? '') {
    'QUAN_TRI' => 'Quản trị viên',
    'QUAN_LY'  => 'Cán bộ quản lý',
    default    => 'Nhân viên phòng trực',
};

// Xác định breadcrumb
$tenTrang = match ($thuMucHienTai) {
    'tai-san'     => 'Tài sản',
    'nguoi-muon'  => 'Người mượn',
    'muon-tra'    => 'Mượn - trả',
    'dieu-chuyen' => 'Điều chuyển',
    'kiem-ke'     => 'Kiểm kê',
    'thong-ke'    => 'Tra cứu, thống kê',
    'danh-muc'    => 'Danh mục',
    'tai-khoan'   => 'Tài khoản',
    'su-co'       => 'Sự cố & Bảo trì',
    default       => ($tepHienTai === 'tong-quan.php' ? 'Tổng quan' : $tieuDe),
};
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tieuDe) ?> - <?= e(cau_hinh('ung_dung.ten', 'TÀI SẢN QR')) ?></title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
</head>

<body>

    <div class="khung-ung-dung">
        <!-- Sidebar điều hướng bên trái -->
        <?php require __DIR__ . '/menu.php'; ?>

        <!-- Vùng nội dung bên phải -->
        <div class="vung-noi-dung">
            <!-- Topbar điều hướng trên -->
            <header class="topbar-app">
                <div class="breadcrumb-app">
                    <a href="<?= url('tong-quan.php') ?>">Trang chủ</a> / <span><?= e($tenTrang) ?></span>
                </div>

                <?php if ($nguoiDung !== null): ?>
                    <div class="user-badge">
                        <div class="avatar-tron"><?= e($chuVietTat) ?></div>
                        <div class="user-info d-none d-sm-block">
                            <div class="user-label">Tài khoản</div>
                            <div class="user-role"><?= e($nhanVaiTro) ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </header>

            <!-- Khối hiển thị nội dung trang -->
            <main class="noi-dung-chinh">
                <?php require dirname(__DIR__) . '/partials/thong-bao.php'; ?>