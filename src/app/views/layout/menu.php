<?php
/**
 * Thanh điều hướng bên sườn (Sidebar Navigation) phân quyền theo vai trò.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tepHienTai = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!-- Sidebar điều hướng bên trái -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-white sidebar shadow-sm py-3">
    <div class="position-sticky">
        <ul class="nav flex-column gap-1">
            
            <!-- Mục Tổng quan -->
            <?php if (co_quyen('xem_tong_quan')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($tepHienTai === 'tong-quan.php') ? 'active fw-bold' : '' ?>" href="<?= url('tong-quan.php') ?>">
                    <span class="nav-icon">📊</span> Tổng quan
                </a>
            </li>
            <?php endif; ?>

            <!-- Nhóm: Nghiệp vụ trực phòng -->
            <?php if (co_quyen('nghiep_vu_muon_tra') || co_quyen('xem_lich_su_muon_tra') || co_quyen('thuc_hien_kiem_ke') || co_quyen('tao_dieu_chuyen')): ?>
            <li class="nav-header text-uppercase text-muted small fw-bold px-3 mt-3 mb-1">Nghiệp vụ trực</li>
            
            <?php if (co_quyen('nghiep_vu_muon_tra')): ?>
            <li class="nav-item">
                <a class="nav-link <?= ($tepHienTai === 'muon.php') ? 'active fw-bold' : '' ?>" href="<?= url('muon-tra/muon.php') ?>">
                    <span class="nav-icon">📤</span> Mượn tài sản
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= ($tepHienTai === 'tra.php') ? 'active fw-bold' : '' ?>" href="<?= url('muon-tra/tra.php') ?>">
                    <span class="nav-icon">📥</span> Trả tài sản
                </a>
            </li>
            <?php endif; ?>

            <?php if (co_quyen('xem_lich_su_muon_tra')): ?>
            <li class="nav-item">
                <a class="nav-link <?= (str_contains($_SERVER['PHP_SELF'] ?? '', 'muon-tra') && $tepHienTai === 'index.php') ? 'active fw-bold' : '' ?>" href="<?= url('muon-tra/index.php') ?>">
                    <span class="nav-icon">📋</span> Lịch sử mượn - trả
                </a>
            </li>
            <?php endif; ?>

            <?php if (co_quyen('thuc_hien_kiem_ke') || co_quyen('xem_kiem_ke')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'] ?? '', 'kiem-ke') ? 'active fw-bold' : '' ?>" href="<?= url('kiem-ke/index.php') ?>">
                    <span class="nav-icon">🔍</span> Kiểm kê tài sản
                </a>
            </li>
            <?php endif; ?>

            <?php if (co_quyen('tao_dieu_chuyen') || co_quyen('xem_dieu_chuyen')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'] ?? '', 'dieu-chuyen') ? 'active fw-bold' : '' ?>" href="<?= url('dieu-chuyen/index.php') ?>">
                    <span class="nav-icon">🔄</span> Điều chuyển thiết bị
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Nhóm: Quản lý thiết bị & sự cố -->
            <?php if (co_quyen('xem_danh_sach_tai_san') || co_quyen('ghi_nhan_su_co')): ?>
            <li class="nav-header text-uppercase text-muted small fw-bold px-3 mt-3 mb-1">Thiết bị & Sự cố</li>
            
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'] ?? '', 'tai-san') ? 'active fw-bold' : '' ?>" href="<?= url('tai-san/index.php') ?>">
                    <span class="nav-icon">💻</span> Quản lý tài sản
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'] ?? '', 'su-co') ? 'active fw-bold' : '' ?>" href="<?= url('su-co/index.php') ?>">
                    <span class="nav-icon">⚠️</span> Báo cáo sự cố
                </a>
            </li>
            <?php endif; ?>

            <!-- Nhóm: Danh mục hệ thống -->
            <?php if (co_quyen('quan_ly_nguoi_muon') || co_quyen('quan_ly_danh_muc')): ?>
            <li class="nav-header text-uppercase text-muted small fw-bold px-3 mt-3 mb-1">Danh mục</li>

            <?php if (co_quyen('quan_ly_nguoi_muon')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'] ?? '', 'nguoi-muon') ? 'active fw-bold' : '' ?>" href="<?= url('nguoi-muon/index.php') ?>">
                    <span class="nav-icon">👥</span> Người mượn
                </a>
            </li>
            <?php endif; ?>

            <?php if (co_quyen('quan_ly_danh_muc')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'] ?? '', 'danh-muc') ? 'active fw-bold' : '' ?>" href="<?= url('danh-muc/index.php') ?>">
                    <span class="nav-icon">🏢</span> Phòng trực & Phòng học
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>

            <!-- Nhóm: Quản trị & Thống kê -->
            <?php if (co_quyen('xem_thong_ke') || co_quyen('quan_ly_tai_khoan')): ?>
            <li class="nav-header text-uppercase text-muted small fw-bold px-3 mt-3 mb-1">Hệ thống</li>

            <?php if (co_quyen('xem_thong_ke')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'] ?? '', 'thong-ke') ? 'active fw-bold' : '' ?>" href="<?= url('thong-ke/index.php') ?>">
                    <span class="nav-icon">📈</span> Báo cáo thống kê
                </a>
            </li>
            <?php endif; ?>

            <?php if (co_quyen('quan_ly_tai_khoan')): ?>
            <li class="nav-item">
                <a class="nav-link <?= str_contains($_SERVER['PHP_SELF'] ?? '', 'tai-khoan') ? 'active fw-bold' : '' ?>" href="<?= url('tai-khoan/index.php') ?>">
                    <span class="nav-icon">🛡️</span> Quản lý tài khoản
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>

        </ul>
    </div>
</nav>

<!-- Vùng nội dung chính của trang -->
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
    <?php require dirname(__DIR__) . '/partials/thong-bao.php'; ?>

