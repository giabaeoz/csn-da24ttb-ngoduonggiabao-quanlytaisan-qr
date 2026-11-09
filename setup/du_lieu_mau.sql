-- =====================================================================
-- HỆ THỐNG QUẢN LÝ MƯỢN – TRẢ VÀ KIỂM KÊ TÀI SẢN BẰNG MÃ QR (QLTS-QR)
-- DỮ LIỆU MẪU MÔI TRƯỜNG THỰC TẾ TRƯỜNG ĐẠI HỌC
-- KHÔNG CHỨA MẬT KHẨU (TÀI KHOẢN KHỞI TẠO QUA: setup/tao_tai_khoan.php)
-- =====================================================================

USE `dacsn_qlts_qr`;

SET FOREIGN_KEY_CHECKS = 0;

-- Làm sạch dữ liệu giao dịch và danh mục để nạp mới
TRUNCATE TABLE `su_co_tai_san`;
TRUNCATE TABLE `chi_tiet_kiem_ke`;
TRUNCATE TABLE `dot_kiem_ke`;
TRUNCATE TABLE `chi_tiet_dieu_chuyen`;
TRUNCATE TABLE `dieu_chuyen`;
TRUNCATE TABLE `chi_tiet_muon`;
TRUNCATE TABLE `phieu_muon`;
TRUNCATE TABLE `tai_san`;
TRUNCATE TABLE `nguoi_muon`;
TRUNCATE TABLE `loai_tai_san`;
TRUNCATE TABLE `phong_hoc`;
TRUNCATE TABLE `phong_truc`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. DỮ LIỆU PHÒNG TRỰC (3 phòng trực đại diện các khu giảng đường)
-- ---------------------------------------------------------------------
INSERT INTO `phong_truc` (`id`, `ma_phong_truc`, `ten_phong_truc`, `vi_tri`, `dang_hoat_dong`) VALUES
(1, 'D6',      'Phòng trực thiết bị D6',      'Tầng trệt tòa D6',      1),
(2, 'B2',      'Phòng trực thiết bị B2',      'Tầng trệt tòa B2',      1),
(3, 'VICTORY', 'Phòng trực khu Victory',      'Tòa nhà Victory',       1);

-- ---------------------------------------------------------------------
-- 2. DỮ LIỆU PHÒNG HỌC / GIẢNG ĐƯỜNG (6 phòng học lý thuyết & máy tính)
-- ---------------------------------------------------------------------
INSERT INTO `phong_hoc` (`id`, `ma_phong`, `ten_phong`, `toa_nha`, `tang`, `dang_hoat_dong`) VALUES
(1, 'B31.101', 'Phòng học lý thuyết B31.101',    'B31', '1', 1),
(2, 'B31.102', 'Phòng học lý thuyết B31.102',    'B31', '1', 1),
(3, 'B31.103', 'Phòng máy tính chuyên đề B31.103','B31', '1', 1),
(4, 'B71.101', 'Phòng hội thảo B71.101',         'B71', '1', 1),
(5, 'B71.201', 'Phòng đa phương tiện B71.201',   'B71', '2', 1),
(6, 'D6.201',  'Phòng thực hành D6.201',         'D6',  '2', 1);

-- ---------------------------------------------------------------------
-- 3. DỮ LIỆU LOẠI TÀI SẢN (6 loại thiết bị thông dụng)
-- ---------------------------------------------------------------------
INSERT INTO `loai_tai_san` (`id`, `ma_loai`, `ten_loai`, `mo_ta`, `dang_hoat_dong`) VALUES
(1, 'MC',  'Máy chiếu di động',         'Máy chiếu EPSON, Panasonic kết nối HDMI/VGA', 1),
(2, 'RM',  'Remote điều khiển',          'Remote máy chiếu, remote máy lạnh phòng học', 1),
(3, 'MIC', 'Micro trợ giảng không dây',  'Bộ micro không dây cầm tay Shure, Soundking', 1),
(4, 'LOA', 'Loa kéo di động',           'Loa trợ giảng công suất 40W - 100W',          1),
(5, 'CAP', 'Cáp chuyển đổi / Cáp nối',   'Cáp HDMI 5m, Type-C sang HDMI, VGA',          1),
(6, 'OD',  'Ổ cắm điện di động',         'Ổ cắm cuộn dài 5m - 10m kèm chống giật',      1);

-- ---------------------------------------------------------------------
-- 4. DỮ LIỆU HỒ SƠ NGƯỜI MƯỢN (Sinh viên & Giảng viên)
-- ---------------------------------------------------------------------
INSERT INTO `nguoi_muon` (`id`, `ma_nguoi_muon`, `ma_so_truong`, `ho_ten`, `loai_nguoi_muon`, `lop_don_vi`, `so_dien_thoai`) VALUES
(1, 'NM-110124001', '110124001', 'Nguyễn Văn An',          'SINH_VIEN',  'DA21TTA',    '0912345671'),
(2, 'NM-110124002', '110124002', 'Trần Thị Bích',          'SINH_VIEN',  'DA22TTB',    '0912345672'),
(3, 'NM-110124003', '110124003', 'Lê Văn Cường',           'SINH_VIEN',  'DA23TTC',    '0912345673'),
(4, 'NM-GV001',     'GV001',     'TS. Nguyễn Ngọc Đan Thanh', 'GIANG_VIEN', 'Khoa CNTT',  '0988112233'),
(5, 'NM-GV002',     'GV002',     'ThS. Lê Hoàng Nam',       'GIANG_VIEN', 'Khoa Kỹ thuật', '0988445566');

-- ---------------------------------------------------------------------
-- 5. DỮ LIỆU TÀI SẢN MẪU (15 tài sản trải dài các phòng trực và loại)
-- ---------------------------------------------------------------------
INSERT INTO `tai_san` (
    `id`, `ma_tai_san`, `ma_qr`, `ten_tai_san`, `loai_tai_san_id`, `phong_truc_id`,
    `phong_hoc_phu_hop_id`, `phong_hoc_hien_tai_id`, `tinh_trang`, `trang_thai`,
    `ngay_dua_vao_su_dung`, `mo_ta`, `pham_vi_tuong_thich`, `phong_tuong_thich`
) VALUES
-- Tài sản thuộc phòng trực D6 (id: 1)
(1,  'MC-0001',  'QR-D6-MC0001',  'Máy chiếu Epson EB-X06',           1, 1, NULL, NULL, 'TOT', 'SAN_SANG', '2025-01-15', 'Máy chiếu sáng 3600 Ansi Lumens', 'LINH_HOAT',   '[]'),
(2,  'MC-0002',  'QR-D6-MC0002',  'Máy chiếu Panasonic PT-LB386',     1, 1, NULL, NULL, 'TOT', 'SAN_SANG', '2025-01-20', 'Độ phân giải XGA, cổng HDMI',       'LINH_HOAT',   '[]'),
(3,  'RM-0001',  'QR-D6-RM0001',  'Remote máy chiếu Panasonic D6',    2, 1, 1,    NULL, 'TOT', 'SAN_SANG', '2025-02-01', 'Dùng riêng cho phòng B31.101/102',   'THEO_PHONG', '["B31.101", "B31.102"]'),
(4,  'MIC-0001', 'QR-D6-MIC0001', 'Micro không dây Shure SVX',        3, 1, NULL, NULL, 'TOT', 'SAN_SANG', '2025-02-10', 'Bộ 1 tay cầm và 1 bộ thu sóng',    'LINH_HOAT',   '[]'),
(5,  'LOA-0001', 'QR-D6-LOA0001', 'Loa trợ giảng di động Aker MR2500', 4, 1, NULL, NULL, 'TOT', 'SAN_SANG', '2025-02-15', 'Loa công suất 40W kèm micro cài tai', 'LINH_HOAT', '[]'),
(6,  'OD-0001',  'QR-D6-OD0001',  'Ổ cắm điện Lioa cuộn 5m',          6, 1, NULL, NULL, 'TOT', 'SAN_SANG', '2025-02-20', 'Ổ cắm 6 lỗ, có aptomat ngắt',       'LINH_HOAT',   '[]'),

-- Tài sản thuộc phòng trực B2 (id: 2)
(7,  'MC-0003',  'QR-B2-MC0003',  'Máy chiếu Sony VPL-EX435',         1, 2, NULL, NULL, 'TOT', 'SAN_SANG', '2025-03-01', 'Máy chiếu độ sáng 3200 Lumens',      'LINH_HOAT',   '[]'),
(8,  'RM-0002',  'QR-B2-RM0002',  'Remote máy chiếu Sony B2',         2, 2, 3,    NULL, 'TOT', 'SAN_SANG', '2025-03-05', 'Cố định cấu hình phòng B31.103/B71',  'THEO_PHONG', '["B31.103", "B71.101"]'),
(9,  'MIC-0002', 'QR-B2-MIC0002', 'Micro không dây Soundking',        3, 2, NULL, NULL, 'TOT', 'SAN_SANG', '2025-03-10', 'Bộ thu sóng hai kênh',             'LINH_HOAT',   '[]'),
(10, 'CAP-0001', 'QR-B2-CAP0001', 'Cáp HDMI Ugreen 5m dẹt',           5, 2, NULL, NULL, 'TOT', 'SAN_SANG', '2025-03-12', 'Chuẩn HDMI 2.0 4K@60Hz',           'LINH_HOAT',   '[]'),
(11, 'OD-0002',  'QR-B2-OD0002',  'Ổ cắm điện đa năng Comet 5m',      6, 2, NULL, NULL, 'TOT', 'SAN_SANG', '2025-03-15', 'Ổ cắm 4 lỗ 3 chấu',                 'LINH_HOAT',   '[]'),

-- Tài sản thuộc phòng trực Victory (id: 3)
(12, 'MC-0004',  'QR-VIC-MC0004', 'Máy chiếu hội thảo Epson EB-2250U', 1, 3, 4,    NULL, 'TOT', 'SAN_SANG', '2025-04-01', 'Độ phân giải WUXGA Full HD hội thảo','THEO_PHONG', '["B71.101", "B71.201"]'),
(13, 'RM-0003',  'QR-VIC-RM0003', 'Remote máy chiếu Epson hội thảo',  2, 3, NULL, NULL, 'TOT', 'SAN_SANG', '2025-04-05', 'Remote chuyên dụng hội trường',      'LINH_HOAT',   '[]'),
(14, 'LOA-0002', 'QR-VIC-LOA0002', 'Loa kéo Soundmax M-22 300W',       4, 3, NULL, NULL, 'TOT', 'SAN_SANG', '2025-04-10', 'Loa công suất lớn có bánh xe kéo',   'LINH_HOAT',   '[]'),
(15, 'CAP-0002', 'QR-VIC-CAP0002', 'Cáp chuyển Type-C sang HDMI Ugreen', 5, 3, NULL, NULL, 'TOT', 'SAN_SANG', '2025-04-15', 'Hỗ trợ laptop đời mới, Macbook',      'LINH_HOAT',   '[]');
