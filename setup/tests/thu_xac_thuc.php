<?php
/**
 * Bộ kiểm thử tự động Module Xác thực & Quản lý Phiên (Auth & Session Tests).
 * Chạy: C:\xampp\php\php.exe setup/tests/thu_xac_thuc.php
 */

declare(strict_types=1);

// Khởi động hệ thống trước khi xuất dữ liệu ra màn hình
require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once __DIR__ . '/ho_tro.php';

echo "============================================================\n";
echo "  BÀI THỬ 1: XÁC THỰC NGƯỜI DÙNG & QUẢN LÝ PHIÊN (AUTH)\n";
echo "============================================================\n\n";

// 1. Kiểm thử CSRF Token
$token1 = csrf_token();
kiem_thu('Khởi tạo CSRF token an toàn 64 ký tự hex', is_string($token1) && strlen($token1) === 64, "Độ dài: " . strlen($token1));

$_POST['csrf_token'] = $token1;
kiem_thu('CSRF token hợp lệ được xác thực thành công', csrf_kiem_tra() === true);

$_POST['csrf_token'] = 'token_gia_mao_1234567890';
kiem_thu('CSRF token giả mạo bị từ chối', csrf_kiem_tra() === false);
unset($_POST['csrf_token']);

// 2. Kiểm thử phiên đăng nhập ban đầu
dang_xuat();
phien_khoi_dong();
kiem_thu('Phiên làm việc ban đầu trống (chưa đăng nhập)', nguoi_dung_hien_tai() === null);

// 3. Kiểm thử đăng nhập sai thông tin
$dangNhapSai = dang_nhap('nhanvien01', 'mat_khau_khong_dung');
kiem_thu('Đăng nhập với mật khẩu sai bị từ chối', $dangNhapSai === false);

$dangNhapKhongTonTai = dang_nhap('tai_khoan_ao_khong_co_that', '123456');
kiem_thu('Đăng nhập với tài khoản không tồn tại bị từ chối', $dangNhapKhongTonTai === false);

// 4. Kiểm thử đăng nhập đúng thông tin (Quản trị viên)
$dangNhapAdmin = dang_nhap('admin', 'admin123');
kiem_thu('Đăng nhập đúng tài khoản Quản trị viên (admin/admin123)', $dangNhapAdmin === true);

$userAdmin = nguoi_dung_hien_tai();
kiem_thu(
    'Thông tin cán bộ trong Session chính xác',
    $userAdmin !== null && $userAdmin['vai_tro'] === VAI_TRO_QUAN_TRI && $userAdmin['ten_dang_nhap'] === 'admin',
    'Tên: ' . ($userAdmin['ho_ten'] ?? '') . ', Vai trò: ' . ($userAdmin['vai_tro'] ?? '')
);

// 5. Kiểm thử đăng nhập Nhân viên trực bàn (có liên kết phòng trực)
dang_xuat();
phien_khoi_dong();
$dangNhapNhanVien = dang_nhap('nhanvien01', 'nhanvien123');
kiem_thu('Đăng nhập đúng tài khoản Nhân viên trực (nhanvien01/nhanvien123)', $dangNhapNhanVien === true);

$userNV = nguoi_dung_hien_tai();
kiem_thu(
    'Nhân viên trực có thông tin phòng trực phân công',
    $userNV !== null && $userNV['vai_tro'] === VAI_TRO_NHAN_VIEN && !empty($userNV['phong_truc_id']),
    'Phòng trực: ' . ($userNV['ten_phong_truc'] ?? '') . ' (ID: ' . ($userNV['phong_truc_id'] ?? '') . ')'
);

// 6. Kiểm thử Đăng xuất
dang_xuat();
phien_khoi_dong();
kiem_thu('Đăng xuất thành công, thông tin cán bộ bị xóa sạch khỏi Session', nguoi_dung_hien_tai() === null);

// Tổng kết
tong_ket_kiem_thu('Xác thực & Phiên');
