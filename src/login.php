<?php
/**
 * Bộ điều khiển xử lý Đăng nhập hệ thống (Login Controller).
 * Hỗ trợ phương thức GET (hiển thị form) và POST (xử lý xác thực, kiểm tra CSRF).
 */

declare(strict_types=1);

require_once __DIR__ . '/app/core/khoi-dong.php';

// Nếu đã đăng nhập thì chuyển hướng thẳng về trang Tổng quan
if (nguoi_dung_hien_tai() !== null) {
    chuyen_huong('tong-quan.php');
}

$thongBaoLoi = '';
$tenDangNhapCu = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Kiểm tra mã xác thực CSRF
    if (!csrf_kiem_tra()) {
        $thongBaoLoi = 'Phiên làm việc bảo mật đã hết hạn. Vui lòng tải lại trang và thử lại.';
    } else {
        // 2. Tiếp nhận và làm sạch thông tin gửi lên
        $tenDangNhap = (string)($_POST['ten_dang_nhap'] ?? '');
        $matKhau = (string)($_POST['mat_khau'] ?? '');
        $tenDangNhapCu = $tenDangNhap;

        if ($tenDangNhap === '' || $matKhau === '') {
            $thongBaoLoi = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
        } else {
            // 3. Tiến hành xác thực qua hàm dang_nhap()
            if (dang_nhap($tenDangNhap, $matKhau)) {
                $nguoiDung = nguoi_dung_hien_tai();
                dat_thong_bao('thanh_cong', 'Đăng nhập thành công! Chào mừng ' . ($nguoiDung['ho_ten'] ?? 'bạn') . ' quay trở lại.');
                chuyen_huong('tong-quan.php');
            } else {
                $thongBaoLoi = 'Tên đăng nhập hoặc mật khẩu không chính xác, hoặc tài khoản đã bị vô hiệu hóa.';
            }
        }
    }
}

// Nạp giao diện đăng nhập
require __DIR__ . '/app/views/dang-nhap.php';

