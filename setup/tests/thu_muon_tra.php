<?php
/**
 * Bộ kiểm thử tự động Module Quản lý Mượn thiết bị bằng mã QR (G5).
 * Chạy: C:\xampp\php\php.exe setup/tests/thu_muon_tra.php
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/muon-tra-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/tai-san-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/danh-muc-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/nguoi-muon-service.php';
require_once __DIR__ . '/ho_tro.php';

echo "============================================================\n";
echo "  BÀI THỬ 6: QUY TRÌNH MƯỢN THIẾT BỊ BẰNG MÃ QR (G5)\n";
echo "============================================================\n\n";

$db = db();

// 1. Kiểm tra khóa kiểm kê khi bàn trực bình thường
$khoaKK = muon_kiem_tra_khoa_kiem_ke(1);
kiem_thu('Bàn trực D6 không bị khóa kiểm kê khi hoạt động bình thường', $khoaKK === false);

// 2. Tạo dữ liệu người mượn và tài sản riêng để kiểm thử độc lập
$maSvTest = 'SV_TEST_' . rand(1000, 9999);
$resNm = nguoi_muon_luu([
    'ma_so_truong'    => $maSvTest,
    'ho_ten'          => 'Sinh Viên Test Mượn',
    'so_dien_thoai'   => '098' . rand(1000000, 9999999),
    'email'           => 'svtest_' . rand(100, 999) . '@ctu.edu.vn',
    'loai_nguoi_muon' => 'SINH_VIEN',
    'lop_don_vi'      => 'DI21V7A1',
]);
$nguoiMuonId = $resNm['id'];
kiem_thu('Tạo người mượn thử nghiệm', $nguoiMuonId > 0, "ID: $nguoiMuonId, MSSV: $maSvTest");

// Tạo 2 tài sản sẵn sàng tại bàn trực 1 (D6)
// Tài sản A: Tương thích linh hoạt (mọi phòng)
$maTsA = 'TS_MUON_A_' . rand(1000, 9999);
$resTsA = tai_san_luu([
    'ma_tai_san'          => $maTsA,
    'ten_tai_san'         => 'Ổ cắm điện đa năng Test G5',
    'loai_tai_san_id'     => 4, // Ổ điện
    'phong_truc_id'       => 1, // D6
    'pham_vi_tuong_thich' => TUONG_THICH_LINH_HOAT,
    'hang_san_xuat'       => 'Lioa',
    'model'               => '6D32N',
    'trang_thai'          => TRANG_THAI_SAN_SANG,
]);
$tsAId = $resTsA['id'];

// Tài sản B: Tương thích theo thiết bị (Panasonic PT-LB386 - khớp phòng B31.101)
$maTsB = 'TS_MUON_B_' . rand(1000, 9999);
$resTsB = tai_san_luu([
    'ma_tai_san'          => $maTsB,
    'ten_tai_san'         => 'Remote Máy Chiếu Panasonic Test G5',
    'loai_tai_san_id'     => 2, // Remote
    'phong_truc_id'       => 1, // D6
    'pham_vi_tuong_thich' => TUONG_THICH_THEO_THIET_BI,
    'hang_san_xuat'       => 'Panasonic',
    'model'               => 'PT-LB386',
    'trang_thai'          => TRANG_THAI_SAN_SANG,
]);
$tsBId = $resTsB['id'];

// Tài sản C: Tương thích theo thiết bị Sony (không tương thích phòng B31.101 dùng Panasonic)
$maTsC = 'TS_MUON_C_' . rand(1000, 9999);
$resTsC = tai_san_luu([
    'ma_tai_san'          => $maTsC,
    'ten_tai_san'         => 'Remote Máy Chiếu Sony Test G5',
    'loai_tai_san_id'     => 2, // Remote
    'phong_truc_id'       => 1, // D6
    'pham_vi_tuong_thich' => TUONG_THICH_THEO_THIET_BI,
    'hang_san_xuat'       => 'Sony',
    'model'               => 'VPL-DX221',
    'trang_thai'          => TRANG_THAI_SAN_SANG,
]);
$tsCId = $resTsC['id'];

kiem_thu('Khởi tạo 3 thiết bị thử nghiệm (Linh hoạt, Panasonic, Sony)', $tsAId > 0 && $tsBId > 0 && $tsCId > 0);

// Phòng 1 (B31.101): có máy chiếu Panasonic PT-LB386 và máy lạnh Daikin FTKB25W
// 3. Thử mượn thiết bị không tương thích (Tài sản C - Sony vào phòng B31.101)
$resMuonSai = muon_tao_phieu([
    'nguoi_muon_id'         => $nguoiMuonId,
    'phong_hoc_id'          => 1, // B31.101
    'phong_truc_id'         => 1, // D6
    'nhan_vien_lap_id'      => 1, // Admin / NV
    'danh_sach_tai_san_id'  => [$tsCId],
    'han_tra'               => date('Y-m-d H:i:s', strtotime('+4 hours')),
]);
kiem_thu(
    'Chặn mượn thiết bị không tương thích thương hiệu/model với phòng học',
    $resMuonSai['thanh_cong'] === false,
    'Lỗi thông báo: ' . ($resMuonSai['loi'] ?? '')
);

// 4. Mượn hợp lệ (Tài sản A - Linh hoạt và Tài sản B - Khớp model Sony)
$resMuonDung = muon_tao_phieu([
    'nguoi_muon_id'         => $nguoiMuonId,
    'phong_hoc_id'          => 1, // B31.101
    'phong_truc_id'         => 1, // D6
    'nhan_vien_lap_id'      => 1,
    'danh_sach_tai_san_id'  => [$tsAId, $tsBId],
    'han_tra'               => date('Y-m-d H:i:s', strtotime('+4 hours')),
    'ghi_chu'               => 'Kiểm thử mượn thiết bị chuẩn G5',
]);
kiem_thu(
    'Tạo phiếu mượn thiết bị hợp lệ thành công',
    $resMuonDung['thanh_cong'] === true && !empty($resMuonDung['ma_phieu']),
    'Mã phiếu: ' . ($resMuonDung['ma_phieu'] ?? '')
);
$phieuId = $resMuonDung['id'] ?? 0;

// 5. Kiểm tra trạng thái thiết bị trong CSDL đã chuyển sang DANG_MUON
$stmtKtTs = $db->prepare("SELECT id, trang_thai FROM tai_san WHERE id IN (?, ?)");
$stmtKtTs->bind_param('ii', $tsAId, $tsBId);
$stmtKtTs->execute();
$rowsTs = $stmtKtTs->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtKtTs->close();

$tatCaDangMuon = true;
foreach ($rowsTs as $r) {
    if ($r['trang_thai'] !== TRANG_THAI_DANG_MUON) {
        $tatCaDangMuon = false;
    }
}
kiem_thu('Trạng thái tài sản trong CSDL tự động chuyển sang DANG_MUON', $tatCaDangMuon && count($rowsTs) === 2);

// 6. Kiểm tra bản ghi trong bảng chi_tiet_muon và cột sinh tai_san_chua_tra_id
$stmtCtm = $db->prepare("SELECT id, tai_san_id, tai_san_chua_tra_id, thoi_gian_tra FROM chi_tiet_muon WHERE phieu_muon_id = ?");
$stmtCtm->bind_param('i', $phieuId);
$stmtCtm->execute();
$rowsCtm = $stmtCtm->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtCtm->close();

$cotSinhDung = (count($rowsCtm) === 2);
foreach ($rowsCtm as $ct) {
    if ((int)$ct['tai_san_chua_tra_id'] !== (int)$ct['tai_san_id'] || $ct['thoi_gian_tra'] !== null) {
        $cotSinhDung = false;
    }
}
kiem_thu('Bảng chi_tiet_muon ghi nhận đủ 2 bản ghi, cột tai_san_chua_tra_id khớp tai_san_id', $cotSinhDung);

// 7. Thử mượn lại chính thiết bị A (đang mượn) -> Kiểm tra chặn ở tầng nghiệp vụ
$resMuonTrung = muon_tao_phieu([
    'nguoi_muon_id'         => $nguoiMuonId,
    'phong_hoc_id'          => 1,
    'phong_truc_id'         => 1,
    'nhan_vien_lap_id'      => 1,
    'danh_sach_tai_san_id'  => [$tsAId],
    'han_tra'               => date('Y-m-d H:i:s', strtotime('+4 hours')),
]);
kiem_thu(
    'Chặn mượn thiết bị đang trong trạng thái ĐANG_MƯỢN (tầng nghiệp vụ)',
    $resMuonTrung['thanh_cong'] === false,
    'Lỗi thông báo: ' . ($resMuonTrung['loi'] ?? '')
);

// 8. Kiểm tra ràng buộc duy nhất CSDL (UNIQUE constraint tai_san_chua_tra_id)
// Nếu ai đó cố chèn trực tiếp bằng SQL bỏ qua tầng nghiệp vụ, CSDL phải tự động văng lỗi trùng khóa
$loiKhoaNgoaiCSDL = false;
try {
    $db->begin_transaction();
    $sqlInsertEpBuoc = "INSERT INTO chi_tiet_muon (phieu_muon_id, tai_san_id, tinh_trang_khi_muon) VALUES (?, ?, 'TOT')";
    $stmtEp = $db->prepare($sqlInsertEpBuoc);
    $stmtEp->bind_param('ii', $phieuId, $tsAId);
    $stmtEp->execute();
    $db->commit();
} catch (mysqli_sql_exception $e) {
    $db->rollback();
    $loiKhoaNgoaiCSDL = true; // Bị chặn đúng thiết kế
}
kiem_thu('Ràng buộc UNIQUE cột sinh tai_san_chua_tra_id chặn mượn 2 lần ở cấp độ CSDL', $loiKhoaNgoaiCSDL);

// 9. Kiểm tra cập nhật vị trí thiết bị tới phòng học đích
$tsSauMuon = tai_san_tim_theo_id($tsAId);
kiem_thu(
    'Cập nhật vị trí phòng học hiện tại của thiết bị (phong_hoc_hien_tai_id = 1)',
    $tsSauMuon !== null && (int)$tsSauMuon['phong_hoc_hien_tai_id'] === 1 && $tsSauMuon['trang_thai'] === TRANG_THAI_DANG_MUON
);

// 10. Kiểm tra đọc chi tiết phiếu mượn qua hàm muon_tim_theo_id
$phieuChiTiet = muon_tim_theo_id($phieuId);
kiem_thu(
    'Truy vấn chi tiết phiếu mượn (kèm danh sách thiết bị con)',
    $phieuChiTiet !== null && count($phieuChiTiet['chi_tiet']) === 2 && $phieuChiTiet['ma_phieu'] === $resMuonDung['ma_phieu']
);

// 11. Kiểm tra danh sách phiếu mượn có lọc theo mã phiếu
$dsTimKiem = muon_danh_sach(['tu_khoa' => $resMuonDung['ma_phieu']], 1, 10);
kiem_thu(
    'Tìm kiếm phiếu mượn theo mã phiếu',
    $dsTimKiem['tong_so'] >= 1 && $dsTimKiem['danh_sach'][0]['ma_phieu'] === $resMuonDung['ma_phieu']
);

// 12. Kiểm tra khóa kiểm kê: Tạo đợt kiểm kê đang diễn ra tại bàn trực 1 (D6)
$maDotKK = 'KK_TEST_' . rand(1000, 9999);
$db->query("INSERT INTO dot_kiem_ke (ma_dot_kiem_ke, phong_truc_id, ten_dot_kiem_ke, loai_kiem_ke, trang_thai, nhan_vien_lap_id) 
            VALUES ('$maDotKK', 1, 'Đợt kiểm kê thử nghiệm G5', 'DOT_XUAT', 'DANG_KIEM_KE', 1)");
$dotKkId = $db->insert_id;

$khoaKkSauKhiBat = muon_kiem_tra_khoa_kiem_ke(1);
kiem_thu('Phát hiện bàn trực đang có đợt kiểm kê (DANG_KIEM_KE)', $khoaKkSauKhiBat === true);

// 13. Thử tạo phiếu mượn khi đang kiểm kê -> Phải bị từ chối
$resMuonKhiKk = muon_tao_phieu([
    'nguoi_muon_id'         => $nguoiMuonId,
    'phong_hoc_id'          => 1,
    'phong_truc_id'         => 1,
    'nhan_vien_lap_id'      => 1,
    'danh_sach_tai_san_id'  => [$tsBId],
    'han_tra'               => date('Y-m-d H:i:s', strtotime('+4 hours')),
]);
kiem_thu(
    'Chặn lập phiếu mượn khi bàn trực đang diễn ra kiểm kê',
    $resMuonKhiKk['thanh_cong'] === false,
    'Lỗi thông báo: ' . ($resMuonKhiKk['loi'] ?? '')
);

// Dọn dẹp đợt kiểm kê thử nghiệm
$db->query("DELETE FROM dot_kiem_ke WHERE id = $dotKkId");
$khoaKkSauKhiXoa = muon_kiem_tra_khoa_kiem_ke(1);
kiem_thu('Mở khóa bàn trực sau khi hoàn tất/hủy đợt kiểm kê', $khoaKkSauKhiXoa === false);

// 14. Kiểm tra chặn mượn thiết bị HỎNG hoặc ĐANG SỬA CHỮA
$maTsHong = 'TS_HONG_' . rand(1000, 9999);
$resTsHong = tai_san_luu([
    'ma_tai_san'          => $maTsHong,
    'ten_tai_san'         => 'Microphone Hỏng Test G5',
    'loai_tai_san_id'     => 1,
    'phong_truc_id'       => 1,
    'pham_vi_tuong_thich' => TUONG_THICH_LINH_HOAT,
    'tinh_trang'          => TINH_TRANG_HU_HONG,
    'trang_thai'          => TRANG_THAI_BAO_TRI,
]);
$tsHongId = $resTsHong['id'];

$resMuonHong = muon_tao_phieu([
    'nguoi_muon_id'         => $nguoiMuonId,
    'phong_hoc_id'          => 1,
    'phong_truc_id'         => 1,
    'nhan_vien_lap_id'      => 1,
    'danh_sach_tai_san_id'  => [$tsHongId],
    'han_tra'               => date('Y-m-d H:i:s', strtotime('+4 hours')),
]);
kiem_thu(
    'Chặn mượn thiết bị có trạng thái HỎNG / KHÔNG SẴN SÀNG',
    $resMuonHong['thanh_cong'] === false,
    'Lỗi thông báo: ' . ($resMuonHong['loi'] ?? '')
);

tong_ket_kiem_thu('QUY TRÌNH MƯỢN THIẾT BỊ BẰNG MÃ QR (G5)');
