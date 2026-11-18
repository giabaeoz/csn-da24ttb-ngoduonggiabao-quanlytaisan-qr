<?php
/**
 * Bộ kiểm thử tự động Phân quyền dựa trên vai trò (RBAC Tests).
 * Chạy: C:\xampp\php\php.exe setup/tests/thu_phan_quyen.php
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once __DIR__ . '/ho_tro.php';

echo "============================================================\n";
echo "  BÀI THỬ 2: PHÂN QUYỀN TRUY CẬP THEO VAI TRÒ (RBAC)\n";
echo "============================================================\n\n";

// 1. Kiểm thử vai trò QUẢN TRỊ VIÊN (Toàn quyền)
$adminUser = [
    'id'       => 1,
    'vai_tro'  => VAI_TRO_QUAN_TRI,
    'ho_ten'   => 'Quản Trị Viên',
];

kiem_thu('Quản trị viên có quyền quản lý tài khoản cán bộ', co_quyen('quan_ly_tai_khoan', $adminUser) === true);
kiem_thu('Quản trị viên có quyền cấu hình danh mục', co_quyen('quan_ly_danh_muc', $adminUser) === true);
kiem_thu('Quản trị viên có quyền nghiệp vụ mượn trả QR', co_quyen('nghiep_vu_muon_tra', $adminUser) === true);
kiem_thu('Quản trị viên có quyền xem báo cáo thống kê', co_quyen('xem_thong_ke', $adminUser) === true);
kiem_thu('Quản trị viên có quyền kiểm kê tài sản', co_quyen('thuc_hien_kiem_ke', $adminUser) === true);

// 2. Kiểm thử vai trò CÁN BỘ QUẢN LÝ (QUAN_LY)
$quanLyUser = [
    'id'       => 2,
    'vai_tro'  => VAI_TRO_QUAN_LY,
    'ho_ten'   => 'Cán Bộ Quản Lý',
];

kiem_thu('Cán bộ Quản lý KHÔNG có quyền quản lý tài khoản cán bộ', co_quyen('quan_ly_tai_khoan', $quanLyUser) === false);
kiem_thu('Cán bộ Quản lý có quyền quản lý danh mục', co_quyen('quan_ly_danh_muc', $quanLyUser) === true);
kiem_thu('Cán bộ Quản lý có quyền quản lý tài sản', co_quyen('quan_ly_tai_san', $quanLyUser) === true);
kiem_thu('Cán bộ Quản lý có quyền xem thống kê báo cáo', co_quyen('xem_thong_ke', $quanLyUser) === true);
kiem_thu('Cán bộ Quản lý KHÔNG trực tiếp thực hiện mượn trả tại phòng trực', co_quyen('nghiep_vu_muon_tra', $quanLyUser) === false);
kiem_thu('Cán bộ Quản lý có quyền xem lịch sử mượn trả', co_quyen('xem_lich_su_muon_tra', $quanLyUser) === true);

// 3. Kiểm thử vai trò NHÂN VIÊN TRỰC BÀN (NHAN_VIEN)
$nhanVienUser = [
    'id'       => 3,
    'vai_tro'  => VAI_TRO_NHAN_VIEN,
    'ho_ten'   => 'Nhân Viên Trực Bàn',
];

kiem_thu('Nhân viên trực KHÔNG có quyền quản lý tài khoản cán bộ', co_quyen('quan_ly_tai_khoan', $nhanVienUser) === false);
kiem_thu('Nhân viên trực KHÔNG có quyền quản lý danh mục hệ thống', co_quyen('quan_ly_danh_muc', $nhanVienUser) === false);
kiem_thu('Nhân viên trực CÓ quyền tra cứu, thống kê tại phòng trực', co_quyen('xem_thong_ke', $nhanVienUser) === true);
kiem_thu('Nhân viên trực CÓ quyền thực hiện nghiệp vụ mượn - trả QR', co_quyen('nghiep_vu_muon_tra', $nhanVienUser) === true);
kiem_thu('Nhân viên trực CÓ quyền thực hiện kiểm kê tài sản', co_quyen('thuc_hien_kiem_ke', $nhanVienUser) === true);
kiem_thu('Nhân viên trực CÓ quyền tạo phiếu điều chuyển thiết bị', co_quyen('tao_dieu_chuyen', $nhanVienUser) === true);
kiem_thu('Nhân viên trực CÓ quyền quản lý hồ sơ người mượn', co_quyen('quan_ly_nguoi_muon', $nhanVienUser) === true);

// 4. Kiểm thử khách vãng lai (Chưa đăng nhập)
kiem_thu('Chưa đăng nhập KHÔNG có bất kỳ quyền nào', co_quyen('xem_tong_quan', null) === false);

// Tổng kết
tong_ket_kiem_thu('Phân quyền RBAC');

