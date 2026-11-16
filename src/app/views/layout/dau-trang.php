<?php

/**
 * Layout phần đầu trang dùng chung (Header & Topbar) - Bám sát phác thảo
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once dirname(__DIR__) . '/partials/icon.php';

/**
 * @var string|null $tieuDeTrang
 */

$nguoiDung = nguoi_dung_hien_tai();
$tieuDe = $tieuDeTrang ?? cau_hinh('ung_dung.ten', 'Quản lý tài sản bằng mã QR');
$tepHienTai = basename($_SERVER['PHP_SELF'] ?? '');
$thuMucHienTai = basename(dirname($_SERVER['PHP_SELF'] ?? ''));

// Chữ viết tắt avatar 2 chữ cái chuẩn
$hoTenNguoiDung = (string)($nguoiDung['ho_ten'] ?? 'Nhân viên');
$chuVietTat = viet_tat_hai_chu_cai($hoTenNguoiDung);

$nhanVaiTro = match ($nguoiDung['vai_tro'] ?? '') {
    'QUAN_TRI' => 'Quản trị viên',
    'QUAN_LY'  => 'Cán bộ quản lý',
    default    => 'Nhân viên phòng trực',
};

// Xác định breadcrumb
$tenTrang = match ($thuMucHienTai) {
    'tai-san'     => 'Tài sản',
    'nguoi-muon'  => 'Người mượn',
    'muon-tra'    => 'Mượn – trả',
    'dieu-chuyen' => 'Điều chuyển',
    'kiem-ke'     => 'Kiểm kê',
    'thong-ke'    => 'Tra cứu, thống kê',
    'danh-muc'    => 'Danh mục',
    'tai-khoan'   => 'Tài khoản',
    'su-co'       => 'Sự cố & Bảo trì',
    default       => ($tepHienTai === 'tong-quan.php' ? 'Tổng quan' : $tieuDe),
};

// Kiểm tra thông báo quá hạn cho chuông
$coPhieuQuaHan = false;
if ($nguoiDung !== null) {
    $dbDau = db();
    $dieuKienPtDau = '';
    if (($nguoiDung['vai_tro'] ?? '') === 'NHAN_VIEN' && !empty($nguoiDung['phong_truc_id'])) {
        $ptIdDau = (int)$nguoiDung['phong_truc_id'];
        $dieuKienPtDau = " AND pm.phong_truc_id = $ptIdDau";
    }
    $resQhDau = $dbDau->query("SELECT 1 FROM phieu_muon pm WHERE pm.trang_thai = 'DANG_MUON' AND pm.han_tra < NOW() $dieuKienPtDau LIMIT 1");
    if ($resQhDau && $resQhDau->num_rows > 0) {
        $coPhieuQuaHan = true;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tieuDe) ?> - <?= e(cau_hinh('ung_dung.ten', 'TÀI SẢN QR')) ?></title>
    <link rel="icon" type="image/jpeg" href="<?= url('assets/images/logo-he-thong.jpg') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css?v=' . @filemtime(dirname(__DIR__, 3) . '/assets/css/app.css')) ?>">
</head>

<body>

    <div class="khung-ung-dung">
        <?php require __DIR__ . '/menu.php'; ?>

        <div class="vung-noi-dung">
            <header class="topbar-app">
                <nav class="breadcrumb-app d-flex align-items-center gap-2" aria-label="Đường dẫn">
                    <a href="<?= url('tong-quan.php') ?>" class="d-inline-flex align-items-center text-decoration-none text-muted" aria-label="Trang chủ">
                        <?= icon('home', 'bieu-tuong-sm me-1') ?>
                        <span>Trang chủ</span>
                    </a>
                    <span class="text-muted">/</span>
                    <strong class="text-dark fw-bold"><?= e($tenTrang) ?></strong>
                </nav>

                <?php if ($nguoiDung !== null): ?>
                    <div class="d-flex align-items-center gap-3">
                        <a href="<?= url('muon-tra/index.php?trang_thai=QUA_HAN') ?>" class="nut-chuong-tb position-relative text-dark d-inline-flex align-items-center justify-content-center" aria-label="Thông báo quá hạn" title="<?= $coPhieuQuaHan ? 'Có phiếu mượn quá hạn cần theo dõi' : 'Không có thông báo mới' ?>">
                            <?= icon('bell', 'bieu-tuong') ?>
                            <?php if ($coPhieuQuaHan): ?>
                                <span class="cham-do-thong-bao"></span>
                            <?php endif; ?>
                        </a>

                        <div class="dropdown">
                            <button class="btn p-0 border-0 d-flex align-items-center gap-2 text-start bg-transparent shadow-none" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Mở menu người dùng">
                                <div class="avatar-tron"><?= e($chuVietTat) ?></div>
                                <div class="user-info d-none d-sm-block">
                                    <div class="user-label">Tài khoản</div>
                                    <div class="user-role"><?= e($hoTenNguoiDung) ?></div>
                                </div>
                                <?= icon('chevron-down', 'bieu-tuong-sm text-muted ms-1') ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2 mt-2" aria-labelledby="userMenuDropdown">
                                <li class="px-3 py-1 text-muted small border-bottom mb-1">
                                    <div><?= e($hoTenNguoiDung) ?></div>
                                    <div class="fw-semibold text-primary"><?= e($nhanVaiTro) ?></div>
                                </li>
                                <li>
                                    <form action="<?= url('logout.php') ?>" method="POST" class="m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                            <?= icon('log-out', 'bieu-tuong-sm') ?>
                                            <span>Đăng xuất</span>
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </header>

            <main class="noi-dung-chinh">
                <?php require dirname(__DIR__) . '/partials/thong-bao.php'; ?>