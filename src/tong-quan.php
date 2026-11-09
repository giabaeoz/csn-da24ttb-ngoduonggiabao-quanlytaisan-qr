<?php
/**
 * Bộ điều khiển Bảng điều khiển Tổng quan (Dashboard Controller).
 * Yêu cầu người dùng phải đăng nhập trước khi xem trang.
 */

declare(strict_types=1);

require_once __DIR__ . '/app/core/khoi-dong.php';
require_once __DIR__ . '/app/services/tai-san-service.php';

// Kiểm tra phiên đăng nhập
$nguoiDung = yeu_cau_dang_nhap();

// Thống kê tài sản: nếu là nhân viên trực thì thống kê theo bàn trực phụ trách
$phongTrucId = ($nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN) ? (int)($nguoiDung['phong_truc_id'] ?? 0) : null;
$thongKe = tai_san_thong_ke_nhanh($phongTrucId);

// Nạp giao diện tổng quan
require __DIR__ . '/app/views/tong-quan.php';

