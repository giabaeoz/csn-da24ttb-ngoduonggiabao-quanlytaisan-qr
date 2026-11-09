<?php
/**
 * Tệp điều phối trung tâm của ứng dụng web.
 * Điều hướng người dùng về trang Tổng quan nếu đã đăng nhập,
 * hoặc chuyển hướng về trang Đăng nhập nếu chưa xác thực.
 */

declare(strict_types=1);

require_once __DIR__ . '/app/core/khoi-dong.php';

if (nguoi_dung_hien_tai() !== null) {
    chuyen_huong('tong-quan.php');
} else {
    chuyen_huong('login.php');
}

