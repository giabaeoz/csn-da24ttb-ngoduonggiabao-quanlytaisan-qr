<?php

/**
 * Thanh điều hướng Sidebar bên trái - Bám sát phác thảo
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once dirname(__DIR__) . '/partials/icon.php';

$tepHienTai = basename($_SERVER['PHP_SELF'] ?? '');
$duongDanHienTai = $_SERVER['PHP_SELF'] ?? '';
$nguoiDung = nguoi_dung_hien_tai();
$tenPhong = $nguoiDung['ten_phong_truc'] ?? 'Phòng trực thiết bị';
?>
<aside class="sidebar-app">
    <div class="sidebar-dau">
        <div class="d-flex align-items-center gap-3">
            <div class="logo-box">
                <img src="<?= url('assets/images/logo-he-thong.jpg') ?>" alt="Logo TÀI SẢN QR" class="logo-anh" width="36" height="36">
            </div>
            <div>
                <div class="ten-he-thong">TÀI SẢN QR</div>
                <div class="ten-phong-truc"><?= e($tenPhong) ?></div>
            </div>
        </div>
    </div>

    <?php
    $coNhomTongQuan = co_quyen('xem_tong_quan') || co_quyen('xem_thong_ke');
    $coNhomHoSo     = co_quyen('xem_danh_sach_tai_san') || co_quyen('quan_ly_nguoi_muon') || co_quyen('nghiep_vu_muon_tra');
    $coNhomNghiepVu = co_quyen('nghiep_vu_muon_tra') || co_quyen('xem_lich_su_muon_tra')
        || co_quyen('tao_dieu_chuyen') || co_quyen('xem_dieu_chuyen')
        || co_quyen('thuc_hien_kiem_ke') || co_quyen('xem_kiem_ke')
        || co_quyen('ghi_nhan_su_co') || co_quyen('xem_su_co');
    $coNhomQuanTri  = co_quyen('quan_ly_danh_muc') || co_quyen('quan_ly_tai_khoan');
    ?>
    <nav class="sidebar-menu">
        <?php if ($coNhomTongQuan): ?>
            <div class="menu-nhom">
                <div class="menu-tieu-de"><?= co_quyen('xem_thong_ke') ? 'TỔNG QUAN & THỐNG KÊ' : 'BẢNG ĐIỀU KHIỂN' ?></div>

                <?php if (co_quyen('xem_tong_quan')): ?>
                    <a class="menu-item <?= ($tepHienTai === 'tong-quan.php') ? 'active' : '' ?>" href="<?= url('tong-quan.php') ?>">
                        <?= icon('home') ?>
                        <span>Tổng quan</span>
                    </a>
                <?php endif; ?>

                <?php if (co_quyen('xem_thong_ke')): ?>
                    <a class="menu-item <?= (str_contains($duongDanHienTai, '/thong-ke/')) ? 'active' : '' ?>" href="<?= url('thong-ke/index.php') ?>">
                        <?= icon('bar-chart-3') ?>
                        <span>Tra cứu, thống kê</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($coNhomHoSo): ?>
            <div class="menu-nhom">
                <div class="menu-tieu-de">QUẢN LÝ HỒ SƠ</div>

                <?php if (co_quyen('xem_danh_sach_tai_san')): ?>
                    <a class="menu-item <?= (str_contains($duongDanHienTai, '/tai-san/')) ? 'active' : '' ?>" href="<?= url('tai-san/index.php') ?>">
                        <?= icon('package') ?>
                        <span>Tài sản</span>
                    </a>
                <?php endif; ?>

                <?php if (co_quyen('quan_ly_nguoi_muon') || co_quyen('nghiep_vu_muon_tra')): ?>
                    <a class="menu-item <?= (str_contains($duongDanHienTai, '/nguoi-muon/')) ? 'active' : '' ?>" href="<?= url('nguoi-muon/index.php') ?>">
                        <?= icon('users') ?>
                        <span>Người mượn</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($coNhomNghiepVu): ?>
            <div class="menu-nhom">
                <div class="menu-tieu-de">NGHIỆP VỤ</div>

                <?php if (co_quyen('nghiep_vu_muon_tra') || co_quyen('xem_lich_su_muon_tra')): ?>
                    <a class="menu-item <?= (str_contains($duongDanHienTai, '/muon-tra/')) ? 'active' : '' ?>" href="<?= url('muon-tra/muon.php') ?>">
                        <?= icon('repeat') ?>
                        <span>Mượn - trả</span>
                    </a>
                <?php endif; ?>

                <?php if (co_quyen('tao_dieu_chuyen') || co_quyen('xem_dieu_chuyen')): ?>
                    <a class="menu-item <?= (str_contains($duongDanHienTai, '/dieu-chuyen/')) ? 'active' : '' ?>" href="<?= url('dieu-chuyen/index.php') ?>">
                        <?= icon('arrow-left-right') ?>
                        <span>Điều chuyển</span>
                    </a>
                <?php endif; ?>

                <?php if (co_quyen('thuc_hien_kiem_ke') || co_quyen('xem_kiem_ke')): ?>
                    <a class="menu-item <?= (str_contains($duongDanHienTai, '/kiem-ke/')) ? 'active' : '' ?>" href="<?= url('kiem-ke/index.php') ?>">
                        <?= icon('clipboard-check') ?>
                        <span>Kiểm kê</span>
                    </a>
                <?php endif; ?>

                <?php if (co_quyen('ghi_nhan_su_co') || co_quyen('xem_su_co')): ?>
                    <a class="menu-item <?= (str_contains($duongDanHienTai, '/su-co/')) ? 'active' : '' ?>" href="<?= url('su-co/index.php') ?>">
                        <?= icon('wrench') ?>
                        <span>Sự cố & Bảo trì</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($coNhomQuanTri): ?>
            <div class="menu-nhom">
                <div class="menu-tieu-de">QUẢN TRỊ HỆ THỐNG</div>

                <?php if (co_quyen('quan_ly_danh_muc')): ?>
                    <a class="menu-item <?= (str_contains($duongDanHienTai, '/danh-muc/')) ? 'active' : '' ?>" href="<?= url('danh-muc/phong-truc.php') ?>">
                        <?= icon('folder-tree') ?>
                        <span>Danh mục</span>
                    </a>
                <?php endif; ?>

                <?php if (co_quyen('quan_ly_tai_khoan')): ?>
                    <a class="menu-item <?= (str_contains($duongDanHienTai, '/tai-khoan/')) ? 'active' : '' ?>" href="<?= url('tai-khoan/index.php') ?>">
                        <?= icon('user-cog') ?>
                        <span>Tài khoản</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </nav>

    <div class="sidebar-song-bg" aria-hidden="true">
        <svg viewBox="0 0 248 120" fill="none" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 60 C80 20 160 90 248 40 L248 120 L0 120 Z" fill="rgba(255, 255, 255, 0.05)" />
            <path d="M0 80 C90 50 170 110 248 70 L248 120 L0 120 Z" fill="rgba(255, 255, 255, 0.07)" />
        </svg>
    </div>
</aside>