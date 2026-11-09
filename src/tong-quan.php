<?php
/**
 * Bộ điều khiển Bảng điều khiển Tổng quan (Dashboard Controller) - Bám sát phác thảo
 */

declare(strict_types=1);

require_once __DIR__ . '/app/core/khoi-dong.php';
require_once __DIR__ . '/app/services/tai-san-service.php';
require_once __DIR__ . '/app/services/thong-ke-service.php';
require_once __DIR__ . '/app/services/muon-tra-service.php';

// Kiểm tra phiên đăng nhập
$nguoiDung = yeu_cau_dang_nhap();

// Thống kê tài sản: nếu là nhân viên trực thì thống kê theo bàn trực phụ trách
$phongTrucId = ($nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN) ? (int)($nguoiDung['phong_truc_id'] ?? 0) : null;
$thongKe = tai_san_thong_ke_nhanh($phongTrucId);

// Lấy dữ liệu KPI tổng quan và biểu đồ
$kpi = thong_ke_tong_quan($phongTrucId);
$muonTra7Ngay = thong_ke_muon_tra_theo_thoi_gian('7_ngay', $phongTrucId);
$taiSanTrangThai = thong_ke_tai_san_theo_trang_thai($phongTrucId);
$nhomTaiSan = thong_ke_tai_san_theo_loai($phongTrucId);

// Lấy danh sách lượt mượn cần theo dõi (đang mượn hoặc quá hạn)
$ketQuaMuon = muon_danh_sach(['trang_thai' => 'DANG_MUON', 'phong_truc_id' => $phongTrucId], 1, 5);
$phieuTheoDoi = $ketQuaMuon['danh_sach'] ?? [];

// Nạp giao diện tổng quan
require __DIR__ . '/app/views/tong-quan.php';
