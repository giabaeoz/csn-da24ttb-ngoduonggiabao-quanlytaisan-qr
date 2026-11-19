-- =====================================================================
-- HỆ THỐNG QUẢN LÝ TÀI SẢN BẰNG MÃ QR (QLTS-QR)
-- KHỞI TẠO TỰ ĐỘNG 3 TÀI KHOẢN MẪU CHO DOCKER
-- MÃ HÓA: UTF-8 / utf8mb4
-- =====================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

USE `dacsn_qlts_qr`;

-- 1. Quản trị viên: admin / admin123 (Ngô Dương Gia Bảo)
INSERT INTO `nhan_vien` (`ma_nhan_vien`, `ho_ten`, `so_dien_thoai`, `ten_dang_nhap`, `mat_khau_hash`, `vai_tro`, `phong_truc_id`, `dang_hoat_dong`)
VALUES ('NV-ADMIN01', 'Ngô Dương Gia Bảo (Quản trị)', '0788919073', 'admin', '$2y$10$F80wU16iRnVLJ8kS6M3c/.c981Yk3LVN4LOlqY/yeq9Y2RkHjkr1q', 'QUAN_TRI', NULL, 1)
ON DUPLICATE KEY UPDATE `ho_ten` = VALUES(`ho_ten`), `dang_hoat_dong` = 1;

-- 2. Cán bộ Quản lý: quanly01 / quanly123 (ThS. Nguyễn Ngọc Đan Thanh)
INSERT INTO `nhan_vien` (`ma_nhan_vien`, `ho_ten`, `so_dien_thoai`, `ten_dang_nhap`, `mat_khau_hash`, `vai_tro`, `phong_truc_id`, `dang_hoat_dong`)
VALUES ('NV-QL01', 'ThS. Nguyễn Ngọc Đan Thanh', '0901234567', 'quanly01', '$2y$10$rb51xyEO72Gb938hLUmni.8ZPADhC/QW5GUqJh8NoNwm3mRCuNXGa', 'QUAN_LY', NULL, 1)
ON DUPLICATE KEY UPDATE `ho_ten` = VALUES(`ho_ten`), `dang_hoat_dong` = 1;

-- 3. Nhân viên trực phòng D6: nhanvien01 / nhanvien123 (gán phong_truc_id = 1 là phòng D6)
INSERT INTO `nhan_vien` (`ma_nhan_vien`, `ho_ten`, `so_dien_thoai`, `ten_dang_nhap`, `mat_khau_hash`, `vai_tro`, `phong_truc_id`, `dang_hoat_dong`)
VALUES ('NV-TRUC01', 'Nhân Viên Trực Phòng D6', '0903456789', 'nhanvien01', '$2y$10$VcIiNArWiN7aQNoByEQpQuAGXLDoBdPN.miZAY.Zbf57g6kCJg7Uy', 'NHAN_VIEN', 1, 1)
ON DUPLICATE KEY UPDATE `ho_ten` = VALUES(`ho_ten`), `dang_hoat_dong` = 1;
