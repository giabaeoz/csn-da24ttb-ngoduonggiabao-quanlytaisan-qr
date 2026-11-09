<?php
/**
 * Bộ điều khiển Bảng điều khiển Tổng quan (Dashboard Controller).
 * Yêu cầu người dùng phải đăng nhập trước khi xem trang.
 */

declare(strict_types=1);

require_once __DIR__ . '/app/core/khoi-dong.php';

// Kiểm tra phiên đăng nhập
$nguoiDung = yeu_cau_dang_nhap();

// Nạp giao diện tổng quan
require __DIR__ . '/app/views/tong-quan.php';

