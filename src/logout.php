<?php
/**
 * Bộ điều khiển xử lý Đăng xuất an toàn (Logout Controller).
 * Bắt buộc sử dụng phương thức POST kèm mã CSRF token hợp lệ để
 * chống tấn công đăng xuất ngoài ý muốn (CSRF Logout).
 */

declare(strict_types=1);

require_once __DIR__ . '/app/core/khoi-dong.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (csrf_kiem_tra()) {
        dang_xuat();
        // Khởi động phiên mới để lưu thông báo flash sau khi đã hủy phiên cũ
        phien_khoi_dong();
        dat_thong_bao('thanh_cong', 'Bạn đã đăng xuất an toàn khỏi hệ thống.');
    } else {
        dat_thong_bao('canh_bao', 'Yêu cầu đăng xuất không hợp lệ (lỗi CSRF token).');
    }
    chuyen_huong('login.php');
}

// Nếu người dùng truy cập trực tiếp bằng phương thức GET thì không đăng xuất mà chuyển về Tổng quan
chuyen_huong(nguoi_dung_hien_tai() !== null ? 'tong-quan.php' : 'login.php');
