<?php
/**
 * Bộ kiểm thử tự động Module Quản lý Tài khoản Cán bộ (tai-khoan-service.php).
 * Chạy: C:\xampp\php\php.exe setup/tests/thu_tai_khoan.php
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/tai-khoan-service.php';
require_once __DIR__ . '/ho_tro.php';

echo "============================================================\n";
echo "  BÀI THỬ 12: QUẢN LÝ TÀI KHOẢN CÁN BỘ HỆ THỐNG\n";
echo "============================================================\n\n";

// 1. Lấy danh sách và thống kê tài khoản
$dsTk = tai_khoan_danh_sach();
kiem_thu('Lấy danh sách tài khoản cán bộ thành công', is_array($dsTk['danh_sach']) && $dsTk['tong_so'] >= 3, 'Tổng số: ' . $dsTk['tong_so']);

$tkNhanh = tai_khoan_thong_ke_nhanh();
kiem_thu('Thống kê nhanh số lượng tài khoản theo trạng thái & vai trò', $tkNhanh['tong_so'] >= 3 && $tkNhanh['quan_tri'] >= 1);

// 2. Chặn tạo Nhân viên trực khi không phân công phòng trực (ck_nhan_vien_phong_truc)
$suffix = (string)rand(1000, 9999);
$resThieuPt = tai_khoan_luu([
    'ma_nhan_vien'      => 'NV_TEST_ERR_' . $suffix,
    'ten_dang_nhap'     => 'nv_err_' . $suffix,
    'ho_ten'            => 'Nhân Viên Thiếu Phòng Trực',
    'vai_tro'           => VAI_TRO_NHAN_VIEN,
    'phong_truc_id'     => null,
    'mat_khau'          => 'matkhau123',
    'xac_nhan_mat_khau' => 'matkhau123',
]);
kiem_thu('Chặn tạo tài khoản Nhân viên trực khi chưa chọn phòng trực', $resThieuPt['thanh_cong'] === false, $resThieuPt['loi']);

// 3. Thêm mới tài khoản Nhân viên trực hợp lệ
$maNvTest = 'NV_TEST_' . $suffix;
$userTest = 'nv_test_' . $suffix;
$resThem = tai_khoan_luu([
    'ma_nhan_vien'      => $maNvTest,
    'ten_dang_nhap'     => $userTest,
    'ho_ten'            => 'Nhân Viên Kiểm Thử Tự Động',
    'so_dien_thoai'     => '0909' . rand(100000, 999999),
    'vai_tro'           => VAI_TRO_NHAN_VIEN,
    'phong_truc_id'     => 1,
    'dang_hoat_dong'    => 1,
    'mat_khau'          => 'matkhau123',
    'xac_nhan_mat_khau' => 'matkhau123',
]);
$idTkTest = (int)($resThem['id'] ?? 0);
kiem_thu('Thêm mới tài khoản Nhân viên trực hợp lệ thành công', $resThem['thanh_cong'] === true && $idTkTest > 0, "ID: $idTkTest");

// 4. Chặn tạo trùng mã nhân viên và tên đăng nhập
$resTrungMa = tai_khoan_luu([
    'ma_nhan_vien'      => $maNvTest,
    'ten_dang_nhap'     => 'khac_' . $suffix,
    'ho_ten'            => 'Trùng Mã Nhân Viên',
    'vai_tro'           => VAI_TRO_QUAN_LY,
    'mat_khau'          => 'matkhau123',
    'xac_nhan_mat_khau' => 'matkhau123',
]);
kiem_thu('Chặn tạo trùng Mã nhân viên', $resTrungMa['thanh_cong'] === false);

$resTrungUser = tai_khoan_luu([
    'ma_nhan_vien'      => 'NV_KHAC_' . $suffix,
    'ten_dang_nhap'     => $userTest,
    'ho_ten'            => 'Trùng Tên Đăng Nhập',
    'vai_tro'           => VAI_TRO_QUAN_LY,
    'mat_khau'          => 'matkhau123',
    'xac_nhan_mat_khau' => 'matkhau123',
]);
kiem_thu('Chặn tạo trùng Tên đăng nhập', $resTrungUser['thanh_cong'] === false);

// 5. Cập nhật thông tin tài khoản
$resCapNhat = tai_khoan_luu([
    'ma_nhan_vien'   => $maNvTest,
    'ten_dang_nhap'  => $userTest,
    'ho_ten'         => 'Cán Bộ Quản Lý Đã Cập Nhật',
    'so_dien_thoai'  => '0912345678',
    'vai_tro'        => VAI_TRO_QUAN_LY,
    'phong_truc_id'  => null,
    'dang_hoat_dong' => 1,
], $idTkTest);
$tkSauCapNhat = tai_khoan_tim_theo_id($idTkTest);
kiem_thu(
    'Cập nhật thông tin và chuyển vai trò sang Cán bộ Quản lý thành công',
    $resCapNhat['thanh_cong'] === true && $tkSauCapNhat !== null && $tkSauCapNhat['vai_tro'] === VAI_TRO_QUAN_LY
);

// 6. Đặt lại mật khẩu và xác thực đăng nhập
$resReset = tai_khoan_dat_lai_mat_khau($idTkTest, 'matkhaumoi456');
$okLoginMoi = dang_nhap($userTest, 'matkhaumoi456');
dang_xuat();
kiem_thu('Đặt lại mật khẩu và đăng nhập với mật khẩu mới thành công', $resReset['thanh_cong'] === true && $okLoginMoi === true);

// 7. Đổi trạng thái khóa tài khoản -> Chặn đăng nhập
$resKhoa = tai_khoan_doi_trang_thai($idTkTest, 1);
$tkSauKhoa = tai_khoan_tim_theo_id($idTkTest);
$loginKhiKhoa = dang_nhap($userTest, 'matkhaumoi456');
kiem_thu(
    'Khóa tài khoản thành công và từ chối đăng nhập khi tài khoản bị khóa',
    $resKhoa['thanh_cong'] === true && (int)$tkSauKhoa['dang_hoat_dong'] === 0 && $loginKhiKhoa === false
);

// 8. Chặn quản trị viên tự khóa hoặc tự xóa chính mình
$resTuKhoa = tai_khoan_doi_trang_thai(1, 1);
kiem_thu('Chặn người dùng tự khóa tài khoản đang đăng nhập của chính mình', $resTuKhoa['thanh_cong'] === false, $resTuKhoa['loi']);

$resTuXoa = tai_khoan_xoa(1, 1);
kiem_thu('Chặn người dùng tự xóa tài khoản đang đăng nhập của chính mình', $resTuXoa['thanh_cong'] === false, $resTuXoa['loi']);

// 9. Xóa tài khoản thử nghiệm khi chưa có giao dịch liên kết
$resXoa = tai_khoan_xoa($idTkTest, 1);
$tkSauXoa = tai_khoan_tim_theo_id($idTkTest);
kiem_thu('Xóa tài khoản an toàn khi chưa phát sinh giao dịch', $resXoa['thanh_cong'] === true && $tkSauXoa === null);

tong_ket_kiem_thu('Quản lý Tài khoản Cán bộ');
