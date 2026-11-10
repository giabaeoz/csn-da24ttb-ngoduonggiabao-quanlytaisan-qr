<?php

/**
 * Thanh điều hướng Sidebar bên trái - Bám sát phác thảo
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tepHienTai = basename($_SERVER['PHP_SELF'] ?? '');
$duongDanHienTai = $_SERVER['PHP_SELF'] ?? '';
$nguoiDung = nguoi_dung_hien_tai();
$tenPhong = $nguoiDung['ten_phong_truc'] ?? 'Phòng trực thiết bị';
?>
<aside class="sidebar-app">
    <!-- Đầu Sidebar: Logo và Tên hệ thống -->
    <div class="sidebar-dau">
        <div class="d-flex align-items-center">
            <div class="logo-box">LOGO</div>
            <div>
                <div class="ten-he-thong">TÀI SẢN QR</div>
                <div class="ten-phong-truc"><?= e($tenPhong) ?></div>
            </div>
        </div>
    </div>

    <!-- Danh sách Menu chức năng -->
    <nav class="sidebar-menu">
        <div class="menu-tieu-de">CHỨC NĂNG</div>

        <?php if (co_quyen('xem_tong_quan')): ?>
            <a class="menu-item <?= ($tepHienTai === 'tong-quan.php') ? 'active' : '' ?>" href="<?= url('tong-quan.php') ?>">
                Tổng quan
            </a>
        <?php endif; ?>

        <?php if (co_quyen('xem_danh_sach_tai_san')): ?>
            <a class="menu-item <?= (str_contains($duongDanHienTai, '/tai-san/')) ? 'active' : '' ?>" href="<?= url('tai-san/index.php') ?>">
                Tài sản
            </a>
        <?php endif; ?>

        <?php if (co_quyen('quan_ly_nguoi_muon') || co_quyen('nghiep_vu_muon_tra')): ?>
            <a class="menu-item <?= (str_contains($duongDanHienTai, '/nguoi-muon/')) ? 'active' : '' ?>" href="<?= url('nguoi-muon/index.php') ?>">
                Người mượn
            </a>
        <?php endif; ?>

        <?php if (co_quyen('nghiep_vu_muon_tra') || co_quyen('xem_lich_su_muon_tra')): ?>
            <a class="menu-item <?= (str_contains($duongDanHienTai, '/muon-tra/')) ? 'active' : '' ?>" href="<?= url('muon-tra/muon.php') ?>">
                Mượn - trả
            </a>
        <?php endif; ?>

        <?php if (co_quyen('tao_dieu_chuyen') || co_quyen('xem_dieu_chuyen')): ?>
            <a class="menu-item <?= (str_contains($duongDanHienTai, '/dieu-chuyen/')) ? 'active' : '' ?>" href="<?= url('dieu-chuyen/index.php') ?>">
                Điều chuyển
            </a>
        <?php endif; ?>

        <?php if (co_quyen('thuc_hien_kiem_ke') || co_quyen('xem_kiem_ke')): ?>
            <a class="menu-item <?= (str_contains($duongDanHienTai, '/kiem-ke/')) ? 'active' : '' ?>" href="<?= url('kiem-ke/index.php') ?>">
                Kiểm kê
            </a>
        <?php endif; ?>

        <?php if (co_quyen('xem_thong_ke')): ?>
            <a class="menu-item <?= (str_contains($duongDanHienTai, '/thong-ke/')) ? 'active' : '' ?>" href="<?= url('thong-ke/index.php') ?>">
                Tra cứu, thống kê
            </a>
        <?php endif; ?>

        <!-- Các mục quản trị nâng cao cho Quản trị viên / Quản lý -->
        <?php if (co_quyen('ghi_nhan_su_co') || co_quyen('xem_su_co')): ?>
            <a class="menu-item <?= (str_contains($duongDanHienTai, '/su-co/')) ? 'active' : '' ?>" href="<?= url('su-co/index.php') ?>">
                Sự cố & Bảo trì
            </a>
        <?php endif; ?>

        <?php if (co_quyen('quan_ly_danh_muc')): ?>
            <a class="menu-item <?= (str_contains($duongDanHienTai, '/danh-muc/')) ? 'active' : '' ?>" href="<?= url('danh-muc/phong-truc.php') ?>">
                Danh mục
            </a>
        <?php endif; ?>

        <?php if (co_quyen('quan_ly_tai_khoan')): ?>
            <a class="menu-item <?= (str_contains($duongDanHienTai, '/tai-khoan/')) ? 'active' : '' ?>" href="<?= url('tai-khoan/index.php') ?>">
                Tài khoản
            </a>
        <?php endif; ?>
    </nav>

    <!-- Chân Sidebar: Nút Đăng xuất -->
    <div class="sidebar-chan">
        <form action="<?= url('logout.php') ?>" method="POST" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="btn-dang-xuat">
                Đăng xuất
            </button>
        </form>
    </div>
</aside>