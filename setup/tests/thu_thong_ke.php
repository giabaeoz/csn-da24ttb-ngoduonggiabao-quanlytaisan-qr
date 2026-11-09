<?php
/**
 * Bộ kiểm thử tự động BÀI THỬ 11: BÁO CÁO THỐNG KÊ & DỮ LIỆU CHART.JS (G10).
 * 
 * Kiểm tra các nghiệp vụ:
 * 1. Chỉ số tổng hợp KPIs (tổng tài sản, sẵn sàng, đang mượn, bảo trì, tỷ lệ sử dụng).
 * 2. Chuỗi thời gian xu hướng mượn trả: 7 ngày, 30 ngày, 12 tháng (labels & data series).
 * 3. Cơ cấu chủng loại tài sản (nhóm theo loại tài sản).
 * 4. Phân bố trạng thái tài sản (4 trạng thái hoạt động).
 * 5. Bảng xếp hạng Top phòng học & Top thiết bị mượn nhiều nhất.
 * 6. Phân tích đối tượng người mượn (Giảng viên vs Sinh viên).
 * 7. Phân tích chi tiết sự cố theo loại và trạng thái xử lý.
 * 8. Hoạt động mượn trả gần đây cho Bảng điều khiển (Dashboard feed).
 * 9. Phân quyền và lọc số liệu theo bàn trực phụ trách.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/thong-ke-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/dieu-chuyen-service.php';

$db = db();
$maTest = strtoupper(bin2hex(random_bytes(3)));

echo "============================================================\n";
echo "  BÀI THỬ 11: BÁO CÁO THỐNG KÊ & DỮ LIỆU CHART.JS (G10)\n";
echo "============================================================\n\n";

$soDat = 0;
$tongBai = 0;

function kt_assert(bool $dieuKien, string $thongBao): void {
    global $soDat, $tongBai;
    $tongBai++;
    if ($dieuKien) {
        $soDat++;
        echo "  [PASS] $thongBao\n";
    } else {
        echo "  [FAIL] $thongBao\n";
    }
}

// Biến lưu ID dữ liệu kiểm thử
$phongTrucId = 0;
$nhanVienId = 0;
$phongHocId = 0;
$loaiTs1Id = 0;
$loaiTs2Id = 0;
$nguoiMuonId = 0;
$phieuMuonId = 0;
$dotKiemKeId = 0;
$suCoId = 0;
$tsIds = [];

try {
    // -------------------------------------------------------------
    // BƯỚC 0: THIẾT LẬP DỮ LIỆU KIỂM THỬ BAN ĐẦU
    // -------------------------------------------------------------
    // 1. Tạo bàn trực test
    $maPt = 'PT-TK-' . $maTest;
    $db->execute_query(
        "INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc, vi_tri, dang_hoat_dong) VALUES (?, ?, 'Tòa TK', 1)",
        [$maPt, 'Bàn trực Test Thống kê ' . $maTest]
    );
    $phongTrucId = (int)$db->insert_id;

    // 2. Tạo nhân viên
    $maNv = 'NV-TK-' . $maTest;
    $db->execute_query(
        "INSERT INTO nhan_vien (phong_truc_id, ma_nhan_vien, ten_dang_nhap, mat_khau_hash, ho_ten, so_dien_thoai, vai_tro, dang_hoat_dong) 
         VALUES (?, ?, ?, 'hash', 'Cán bộ test TK', '0901112233', 'NHAN_VIEN', 1)",
        [$phongTrucId, $maNv, 'nvtk_' . strtolower($maTest)]
    );
    $nhanVienId = (int)$db->insert_id;

    // 3. Tạo phòng học
    $maPh = 'PH-TK-' . $maTest;
    $db->execute_query(
        "INSERT INTO phong_hoc (ma_phong, ten_phong, toa_nha, tang, dang_hoat_dong) 
         VALUES (?, 'Phòng Test TK', 'Tòa TK', '1', 1)",
        [$maPh]
    );
    $phongHocId = (int)$db->insert_id;

    // 4. Tạo 2 loại tài sản
    $maLoai1 = 'LT1-TK-' . $maTest;
    $db->execute_query("INSERT INTO loai_tai_san (ma_loai, ten_loai, mo_ta) VALUES (?, 'Máy chiếu Test TK', 'Mô tả')", [$maLoai1]);
    $loaiTs1Id = (int)$db->insert_id;

    $maLoai2 = 'LT2-TK-' . $maTest;
    $db->execute_query("INSERT INTO loai_tai_san (ma_loai, ten_loai, mo_ta) VALUES (?, 'Micro Test TK', 'Mô tả')", [$maLoai2]);
    $loaiTs2Id = (int)$db->insert_id;

    // 5. Tạo 4 tài sản với các trạng thái khác nhau
    // TS 0: Máy chiếu, Sẵn sàng, Tốt
    // TS 1: Máy chiếu, Đang mượn, Tốt
    // TS 2: Micro, Bảo trì, Hư hỏng
    // TS 3: Micro, Ngừng sử dụng, Tốt
    $db->execute_query(
        "INSERT INTO tai_san (loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, ma_tai_san, ma_qr, ten_tai_san, tinh_trang, trang_thai) 
         VALUES (?, ?, ?, ?, ?, 'Máy chiếu 1', 'TOT', 'SAN_SANG')",
        [$loaiTs1Id, $phongTrucId, $phongHocId, 'TS1-TK-' . $maTest, 'QR-TK-1-' . $maTest]
    );
    $tsIds[] = (int)$db->insert_id;

    $db->execute_query(
        "INSERT INTO tai_san (loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, ma_tai_san, ma_qr, ten_tai_san, tinh_trang, trang_thai) 
         VALUES (?, ?, ?, ?, ?, 'Máy chiếu 2', 'TOT', 'DANG_MUON')",
        [$loaiTs1Id, $phongTrucId, $phongHocId, 'TS2-TK-' . $maTest, 'QR-TK-2-' . $maTest]
    );
    $tsIds[] = (int)$db->insert_id;

    $db->execute_query(
        "INSERT INTO tai_san (loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, ma_tai_san, ma_qr, ten_tai_san, tinh_trang, trang_thai) 
         VALUES (?, ?, ?, ?, ?, 'Micro 1', 'HU_HONG', 'BAO_TRI')",
        [$loaiTs2Id, $phongTrucId, $phongHocId, 'TS3-TK-' . $maTest, 'QR-TK-3-' . $maTest]
    );
    $tsIds[] = (int)$db->insert_id;

    $db->execute_query(
        "INSERT INTO tai_san (loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, ma_tai_san, ma_qr, ten_tai_san, tinh_trang, trang_thai) 
         VALUES (?, ?, ?, ?, ?, 'Micro 2', 'TOT', 'NGUNG_SU_DUNG')",
        [$loaiTs2Id, $phongTrucId, $phongHocId, 'TS4-TK-' . $maTest, 'QR-TK-4-' . $maTest]
    );
    $tsIds[] = (int)$db->insert_id;

    // 6. Tạo người mượn là Giảng viên
    $maNm = 'NM-TK-' . $maTest;
    $db->execute_query(
        "INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon, lop_don_vi, so_dien_thoai) 
         VALUES (?, 'GV-001', 'Thầy Test TK', 'GIANG_VIEN', 'Khoa CNTT', '0988776655')",
        [$maNm]
    );
    $nguoiMuonId = (int)$db->insert_id;

    // 7. Tạo phiếu mượn (đang mượn TS 1)
    $maPm = 'PM-TK-' . $maTest;
    $db->execute_query(
        "INSERT INTO phieu_muon (ma_phieu_muon, nguoi_muon_id, phong_truc_id, nhan_vien_lap_id, phong_hoc_id, thoi_gian_muon, han_tra, trang_thai) 
         VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 4 HOUR), 'DANG_MUON')",
        [$maPm, $nguoiMuonId, $phongTrucId, $nhanVienId, $phongHocId]
    );
    $phieuMuonId = (int)$db->insert_id;

    $db->execute_query(
        "INSERT INTO chi_tiet_muon (phieu_muon_id, tai_san_id, tinh_trang_khi_muon) VALUES (?, ?, 'TOT')",
        [$phieuMuonId, $tsIds[1]]
    );

    // 8. Tạo 1 sự cố cho TS 2 (Hư hỏng)
    $maSc = 'SC-TK-' . $maTest;
    $db->execute_query(
        "INSERT INTO su_co_tai_san (ma_su_co, tai_san_id, nhan_vien_lap_id, loai_su_co, mo_ta, trang_thai) 
         VALUES (?, ?, ?, 'HU_HONG', 'Micro bị rè tiếng', 'CHUA_XU_LY')",
        [$maSc, $tsIds[2], $nhanVienId]
    );
    $suCoId = (int)$db->insert_id;

    // 9. Tạo 1 đợt kiểm kê
    $maDk = 'KK-TK-' . $maTest;
    $db->execute_query(
        "INSERT INTO dot_kiem_ke (ma_dot_kiem_ke, ten_dot_kiem_ke, phong_truc_id, nhan_vien_lap_id, loai_kiem_ke, trang_thai) 
         VALUES (?, 'Kiểm kê test TK', ?, ?, 'CUOI_BUOI', 'DANG_KIEM_KE')",
        [$maDk, $phongTrucId, $nhanVienId]
    );
    $dotKiemKeId = (int)$db->insert_id;

    echo "  [OK] Đã thiết lập xong dữ liệu môi trường kiểm thử thống kê.\n\n";

    // -------------------------------------------------------------
    // TEST 1: CHỈ SỐ TỔNG HỢP KPIS (thong_ke_tong_quan)
    // -------------------------------------------------------------
    $kpi = thong_ke_tong_quan($phongTrucId);
    kt_assert($kpi['tong_tai_san'] === 4, "Tổng tài sản bàn trực: 4 (Thực tế: {$kpi['tong_tai_san']}).");
    kt_assert($kpi['tai_san_san_sang'] === 1, "Tài sản sẵn sàng: 1.");
    kt_assert($kpi['tai_san_dang_muon'] === 1, "Tài sản đang mượn: 1.");
    kt_assert($kpi['tai_san_bao_tri'] === 1, "Tài sản bảo trì: 1.");
    kt_assert($kpi['tai_san_ngung_sd'] === 1, "Tài sản ngừng sử dụng: 1.");
    kt_assert($kpi['tai_san_hu_hong'] === 1, "Tài sản hư hỏng: 1.");
    kt_assert($kpi['ty_le_su_dung'] === 25.0, "Tỷ lệ khai thác sử dụng: 25.0% (1/4 tài sản).");
    kt_assert($kpi['tong_luot_muon'] === 1, "Tổng lượt mượn bàn trực: 1.");
    kt_assert($kpi['phieu_dang_muon'] === 1, "Số phiếu đang mượn: 1.");
    kt_assert($kpi['tong_su_co'] === 1, "Tổng sự cố ghi nhận: 1.");
    kt_assert($kpi['su_co_chua_xu_ly'] === 1, "Sự cố chưa xử lý: 1.");
    kt_assert($kpi['tong_dot_kiem_ke'] === 1, "Tổng đợt kiểm kê: 1.");
    kt_assert($kpi['dot_dang_mo'] === 1, "Đợt kiểm kê đang mở: 1.");

    // -------------------------------------------------------------
    // TEST 2: XU HƯỚNG MƯỢN TRẢ 7 NGÀY (thong_ke_muon_tra_theo_thoi_gian)
    // -------------------------------------------------------------
    $muonTra7 = thong_ke_muon_tra_theo_thoi_gian('7_ngay', $phongTrucId);
    kt_assert(count($muonTra7['labels']) === 7, "Chuỗi thời gian 7 ngày trả về đúng 7 mốc ngày.");
    kt_assert(count($muonTra7['so_luot_muon']) === 7, "Mảng dữ liệu mượn có đúng 7 phần tử.");
    kt_assert(count($muonTra7['so_luot_tra']) === 7, "Mảng dữ liệu trả có đúng 7 phần tử.");
    kt_assert($muonTra7['tong_muon'] === 1, "Tổng lượt mượn trong 7 ngày khớp với dữ liệu test (1).");

    // -------------------------------------------------------------
    // TEST 3: XU HƯỚNG MƯỢN TRẢ 30 NGÀY
    // -------------------------------------------------------------
    $muonTra30 = thong_ke_muon_tra_theo_thoi_gian('30_ngay', $phongTrucId);
    kt_assert(count($muonTra30['labels']) === 30, "Chuỗi thời gian 30 ngày trả về đúng 30 mốc ngày.");
    kt_assert($muonTra30['tong_muon'] === 1, "Tổng lượt mượn trong 30 ngày: 1.");

    // -------------------------------------------------------------
    // TEST 4: XU HƯỚNG MƯỢN TRẢ 12 THÁNG
    // -------------------------------------------------------------
    $muonTra12 = thong_ke_muon_tra_theo_thoi_gian('12_thang', $phongTrucId);
    kt_assert(count($muonTra12['labels']) === 12, "Chuỗi thời gian 12 tháng trả về đúng 12 mốc tháng (m/Y).");
    kt_assert($muonTra12['tong_muon'] === 1, "Tổng lượt mượn trong 12 tháng: 1.");

    // -------------------------------------------------------------
    // TEST 5: CƠ CẤU TÀI SẢN THEO LOẠI (thong_ke_tai_san_theo_loai)
    // -------------------------------------------------------------
    $loaiTs = thong_ke_tai_san_theo_loai($phongTrucId);
    kt_assert(count($loaiTs['labels']) === 2, "Nhận diện đúng 2 loại thiết bị (Máy chiếu, Micro).");
    kt_assert(array_sum($loaiTs['so_luong']) === 4, "Tổng số lượng theo loại bằng 4.");

    // -------------------------------------------------------------
    // TEST 6: PHÂN BỐ TRẠNG THÁI TÀI SẢN (thong_ke_tai_san_theo_trang_thai)
    // -------------------------------------------------------------
    $trangThaiTs = thong_ke_tai_san_theo_trang_thai($phongTrucId);
    kt_assert(count($trangThaiTs['labels']) === 4, "Phân bố đủ 4 trạng thái tài sản.");
    kt_assert($trangThaiTs['data'] === [1, 1, 1, 1], "Mỗi trạng thái (Sẵn sàng, Đang mượn, Bảo trì, Ngừng SD) có đúng 1 thiết bị.");

    // -------------------------------------------------------------
    // TEST 7: TOP PHÒNG HỌC MƯỢN NHIỀU NHẤT (thong_ke_top_phong_hoc)
    // -------------------------------------------------------------
    $topPh = thong_ke_top_phong_hoc(5, $phongTrucId);
    kt_assert(count($topPh['danh_sach']) === 1, "Tìm thấy phòng học có lượt mượn.");
    kt_assert($topPh['danh_sach'][0]['ma_phong'] === $maPh, "Mã phòng học đúng: $maPh.");
    kt_assert((int)$topPh['danh_sach'][0]['so_luot_muon'] === 1, "Số lượt mượn của phòng: 1.");

    // -------------------------------------------------------------
    // TEST 8: TOP THIẾT BỊ MƯỢN NHIỀU NHẤT (thong_ke_top_tai_san_muon)
    // -------------------------------------------------------------
    $topTs = thong_ke_top_tai_san_muon(5, $phongTrucId);
    kt_assert(count($topTs['danh_sach']) === 1, "Tìm thấy thiết bị có lượt mượn.");
    kt_assert((int)$topTs['danh_sach'][0]['id'] === $tsIds[1], "Thiết bị mượn nhiều nhất đúng là TS 2.");

    // -------------------------------------------------------------
    // TEST 9: PHÂN LOẠI ĐỐI TƯỢNG MƯỢN (thong_ke_doi_tuong_muon)
    // -------------------------------------------------------------
    $dt = thong_ke_doi_tuong_muon($phongTrucId);
    kt_assert($dt['data'] === [1, 0], "Đối tượng mượn: 1 Giảng viên, 0 Sinh viên.");

    // -------------------------------------------------------------
    // TEST 10: PHÂN TÍCH SỰ CỐ CHI TIẾT (thong_ke_su_co_chi_tiet)
    // -------------------------------------------------------------
    $sc = thong_ke_su_co_chi_tiet($phongTrucId);
    kt_assert($sc['theo_loai']['data'] === [1, 0, 0], "Sự cố theo loại: 1 Hư hỏng, 0 Mất, 0 Sai vị trí.");
    kt_assert($sc['theo_trang_thai']['data'] === [1, 0, 0], "Sự cố theo trạng thái: 1 Chưa xử lý, 0 Đang xử lý, 0 Đã xử lý.");

    // -------------------------------------------------------------
    // TEST 11: HOẠT ĐỘNG GẦN ĐÂY CHO DASHBOARD (thong_ke_hoat_dong_gan_day)
    // -------------------------------------------------------------
    $hd = thong_ke_hoat_dong_gan_day(5, $phongTrucId);
    kt_assert(count($hd) === 1, "Dashboard feed trả về đúng 1 hoạt động mượn gần đây.");
    kt_assert($hd[0]['ma_phieu_muon'] === $maPm, "Mã phiếu trong feed khớp: $maPm.");

} catch (Throwable $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . " tại " . $e->getFile() . ":" . $e->getLine() . "\n";
    $tongBai++;
} finally {
    // -------------------------------------------------------------
    // DỌN DẸP DỮ LIỆU KIỂM THỬ (CLEANUP TEARDOWN)
    // -------------------------------------------------------------
    echo "\n  [CLEANUP] Đang dọn dẹp dữ liệu kiểm thử test...\n";
    if ($suCoId > 0) {
        $db->execute_query("DELETE FROM su_co_tai_san WHERE id = ?", [$suCoId]);
    }
    if ($dotKiemKeId > 0) {
        $db->execute_query("DELETE FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?", [$dotKiemKeId]);
        $db->execute_query("DELETE FROM dot_kiem_ke WHERE id = ?", [$dotKiemKeId]);
    }
    if ($phieuMuonId > 0) {
        $db->execute_query("DELETE FROM chi_tiet_muon WHERE phieu_muon_id = ?", [$phieuMuonId]);
        $db->execute_query("DELETE FROM phieu_muon WHERE id = ?", [$phieuMuonId]);
    }
    if (!empty($tsIds)) {
        $inTs = implode(',', $tsIds);
        $db->execute_query("DELETE FROM tai_san WHERE id IN ($inTs)");
    }
    if ($nguoiMuonId > 0) {
        $db->execute_query("DELETE FROM nguoi_muon WHERE id = ?", [$nguoiMuonId]);
    }
    if ($loaiTs1Id > 0) {
        $db->execute_query("DELETE FROM loai_tai_san WHERE id = ?", [$loaiTs1Id]);
    }
    if ($loaiTs2Id > 0) {
        $db->execute_query("DELETE FROM loai_tai_san WHERE id = ?", [$loaiTs2Id]);
    }
    if ($phongHocId > 0) {
        $db->execute_query("DELETE FROM phong_hoc WHERE id = ?", [$phongHocId]);
    }
    if ($nhanVienId > 0) {
        $db->execute_query("DELETE FROM nhan_vien WHERE id = ?", [$nhanVienId]);
    }
    if ($phongTrucId > 0) {
        $db->execute_query("DELETE FROM phong_truc WHERE id = ?", [$phongTrucId]);
    }
    echo "  [OK] Đã dọn dẹp sạch sẽ toàn bộ dữ liệu test.\n";
}

echo "\n------------------------------------------------------------\n";
echo "  KẾT QUẢ [BÀI THỬ 11: BÁO CÁO THỐNG KÊ (G10)]: $soDat / $tongBai bài đạt.\n";
echo "============================================================\n\n";

if ($soDat !== $tongBai) {
    exit(1);
}
exit(0);
