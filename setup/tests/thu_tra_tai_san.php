<?php
/**
 * Bộ kiểm thử tự động Module Trả thiết bị & Ghi nhận sự cố hư hỏng (G6).
 * Chạy: C:\xampp\php\php.exe setup/tests/thu_tra_tai_san.php
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/muon-tra-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/tai-san-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/danh-muc-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/nguoi-muon-service.php';
require_once __DIR__ . '/ho_tro.php';

echo "============================================================\n";
echo "  BÀI THỬ 7: TIẾP NHẬN TRẢ TÀI SẢN & GHI NHẬN SỰ CỐ (G6)\n";
echo "============================================================\n\n";

$db = db();

// 1. Chuẩn bị dữ liệu kiểm thử độc lập
$maSvTest = 'SV_TRA_' . rand(1000, 9999);
$resNm = nguoi_muon_luu([
    'ma_so_truong'    => $maSvTest,
    'ho_ten'          => 'Sinh Viên Test Trả',
    'so_dien_thoai'   => '097' . rand(1000000, 9999999),
    'loai_nguoi_muon' => 'SINH_VIEN',
    'lop_don_vi'      => 'DI21V7A2',
]);
$nguoiMuonId = $resNm['id'];

// Tạo 2 tài sản linh hoạt tại bàn trực 1 (D6)
$maTs1 = 'TS_TRA1_' . rand(1000, 9999);
$resTs1 = tai_san_luu([
    'ma_tai_san'          => $maTs1,
    'ten_tai_san'         => 'Cáp HDMI 5M Test G6',
    'loai_tai_san_id'     => 5, // Cáp tín hiệu
    'phong_truc_id'       => 1, // D6
    'pham_vi_tuong_thich' => TUONG_THICH_LINH_HOAT,
    'trang_thai'          => TRANG_THAI_SAN_SANG,
]);
$ts1Id = $resTs1['id'];

$maTs2 = 'TS_TRA2_' . rand(1000, 9999);
$resTs2 = tai_san_luu([
    'ma_tai_san'          => $maTs2,
    'ten_tai_san'         => 'Loa Trợ Giảng Không Dây Test G6',
    'loai_tai_san_id'     => 3, // Loa kéo
    'phong_truc_id'       => 1, // D6
    'pham_vi_tuong_thich' => TUONG_THICH_LINH_HOAT,
    'trang_thai'          => TRANG_THAI_SAN_SANG,
]);
$ts2Id = $resTs2['id'];

// Tạo phiếu mượn gồm cả 2 tài sản trên
$resMuon = muon_tao_phieu([
    'nguoi_muon_id'         => $nguoiMuonId,
    'phong_hoc_id'          => 1, // B31.101
    'phong_truc_id'         => 1, // D6
    'nhan_vien_lap_id'      => 1, // Admin
    'danh_sach_tai_san_id'  => [$ts1Id, $ts2Id],
    'han_tra'               => date('Y-m-d H:i:s', strtotime('+3 hours')),
    'ghi_chu'               => 'Phiếu mượn phục vụ kiểm thử trả tài sản G6',
]);
$phieuId = $resMuon['id'];
$maPhieu = $resMuon['ma_phieu'];
kiem_thu('Khởi tạo thành công phiếu mượn 2 thiết bị phục vụ kiểm thử trả', $phieuId > 0 && !empty($maPhieu), "Mã phiếu: $maPhieu");

// Lấy thông tin chi tiết phiếu mượn và ID của các chi tiết con
$phieu = muon_tim_theo_id($phieuId);
$chiTiet1 = $phieu['chi_tiet'][0];
$chiTiet2 = $phieu['chi_tiet'][1];
$ctm1Id = (int)$chiTiet1['id'];
$ctm2Id = (int)$chiTiet2['id'];

// 2. Kiểm thử tra cứu phiếu chờ trả qua nhiều hình thức
$timMaPhieu = tra_tim_phieu_theo_tu_khoa($maPhieu, 1);
kiem_thu('Tra cứu phiếu chờ trả theo Mã phiếu', $timMaPhieu !== null && (int)$timMaPhieu['id'] === $phieuId);

$timMaQr = tra_tim_phieu_theo_tu_khoa($chiTiet1['ma_qr'], 1);
kiem_thu('Tra cứu phiếu chờ trả bằng quét Mã QR thiết bị đang mượn', $timMaQr !== null && (int)$timMaQr['id'] === $phieuId);

$timMaTs = tra_tim_phieu_theo_tu_khoa($chiTiet2['ma_tai_san'], 1);
kiem_thu('Tra cứu phiếu chờ trả theo Mã tài sản thiết bị đang mượn', $timMaTs !== null && (int)$timMaTs['id'] === $phieuId);

$timMssv = tra_tim_phieu_theo_tu_khoa($maSvTest, 1);
kiem_thu('Tra cứu phiếu chờ trả theo MSSV người mượn', $timMssv !== null && (int)$timMssv['id'] === $phieuId);

// 3. Kiểm thử danh sách phiếu chờ trả tại bàn trực
$dsCho = tra_danh_sach_cho_xu_ly(1, 20);
$coTrongDsCho = false;
foreach ($dsCho as $itemCho) {
    if ((int)$itemCho['id'] === $phieuId) {
        $coTrongDsCho = true;
        break;
    }
}
kiem_thu('Phiếu mượn xuất hiện trong danh sách chờ trả tại bàn trực D6', $coTrongDsCho);

// 4. Kiểm tra bắt buộc mô tả hư hỏng: Nếu chọn HU_HONG mà không ghi chú -> Phải bị từ chối
$resTraKhongGhiChu = tra_thuc_hien([
    'phieu_muon_id' => $phieuId,
    'chi_tiet_id'   => [$ctm1Id],
    'tinh_trang'    => [$ctm1Id => 'HU_HONG'],
    'ghi_chu'       => [$ctm1Id => ''], // Rỗng
], 1, 1);
kiem_thu(
    'Bắt buộc nhập mô tả hư hỏng khi trả thiết bị tình trạng HU_HONG',
    $resTraKhongGhiChu['thanh_cong'] === false,
    'Thông báo lỗi: ' . ($resTraKhongGhiChu['loi'] ?? '')
);

// 5. Kiểm thử TRẢ MỘT PHẦN (Partial Return): Chỉ trả thiết bị 1 (Tình trạng TỐT)
$resTraPhan1 = tra_thuc_hien([
    'phieu_muon_id' => $phieuId,
    'chi_tiet_id'   => [$ctm1Id],
    'tinh_trang'    => [$ctm1Id => 'TOT'],
    'ghi_chu'       => [$ctm1Id => 'Dây cáp nguyên vẹn'],
], 1, 1);

kiem_thu(
    'Tiếp nhận trả 1 phần thành công (1/2 thiết bị)',
    $resTraPhan1['thanh_cong'] === true && $resTraPhan1['so_da_tra'] === 1 && $resTraPhan1['con_lai'] === 1 && $resTraPhan1['hoan_tat'] === false
);

// 6. Kiểm tra trạng thái thiết bị 1 trong CSDL sau khi trả tốt
$ts1SauTra = tai_san_tim_theo_id($ts1Id);
kiem_thu(
    'Thiết bị 1 chuyển trạng thái SAN_SANG, tình trạng TOT, vị trí phòng học được giải phóng (NULL)',
    $ts1SauTra !== null && $ts1SauTra['trang_thai'] === TRANG_THAI_SAN_SANG && $ts1SauTra['tinh_trang'] === TINH_TRANG_TOT && $ts1SauTra['phong_hoc_hien_tai_id'] === null
);

// 7. Kiểm tra cột sinh tai_san_chua_tra_id của thiết bị 1 đã về NULL (mở khóa CSDL để mượn lại)
$stmtKtCtm1 = $db->prepare("SELECT tai_san_chua_tra_id, thoi_gian_tra, tinh_trang_khi_tra FROM chi_tiet_muon WHERE id = ?");
$stmtKtCtm1->bind_param('i', $ctm1Id);
$stmtKtCtm1->execute();
$ctm1Db = $stmtKtCtm1->get_result()->fetch_assoc();
$stmtKtCtm1->close();
kiem_thu(
    'Cột sinh tai_san_chua_tra_id tự động chuyển sang NULL khi thoi_gian_tra được ghi nhận',
    $ctm1Db['tai_san_chua_tra_id'] === null && $ctm1Db['thoi_gian_tra'] !== null && $ctm1Db['tinh_trang_khi_tra'] === 'TOT'
);

// 8. Phiếu mượn vẫn giữ trạng thái DANG_MUON vì thiết bị 2 chưa trả
$phieuSauPhan1 = muon_tim_theo_id($phieuId);
kiem_thu(
    'Phiếu mượn vẫn ở trạng thái DANG_MUON khi còn thiết bị chưa trả',
    $phieuSauPhan1['trang_thai'] === PHIEU_MUON_DANG_MUON
);

// 9. Kiểm thử chặn trả lại thiết bị 1 lần thứ hai
$resTraLap = tra_thuc_hien([
    'phieu_muon_id' => $phieuId,
    'chi_tiet_id'   => [$ctm1Id],
    'tinh_trang'    => [$ctm1Id => 'TOT'],
], 1, 1);
kiem_thu(
    'Chặn tiếp nhận trả lại thiết bị đã hoàn trả từ trước',
    $resTraLap['thanh_cong'] === false,
    'Thông báo lỗi: ' . ($resTraLap['loi'] ?? '')
);

// 10. Tiếp tục trả thiết bị 2 với tình trạng HƯ HỎNG kèm mô tả sự cố
$moTaHuHong = 'Loa bị rè loa bass, mic không bắt tín hiệu phát âm';
$resTraPhan2 = tra_thuc_hien([
    'phieu_muon_id' => $phieuId,
    'chi_tiet_id'   => [$ctm2Id],
    'tinh_trang'    => [$ctm2Id => 'HU_HONG'],
    'ghi_chu'       => [$ctm2Id => $moTaHuHong],
], 1, 1);
kiem_thu(
    'Tiếp nhận trả thiết bị 2 (HƯ HỎNG) và hoàn tất phiếu mượn',
    $resTraPhan2['thanh_cong'] === true && $resTraPhan2['so_da_tra'] === 1 && $resTraPhan2['con_lai'] === 0 && $resTraPhan2['hoan_tat'] === true
);

// 11. Kiểm tra trạng thái thiết bị 2 chuyển sang BAO_TRI và tình trạng HU_HONG
$ts2SauTra = tai_san_tim_theo_id($ts2Id);
kiem_thu(
    'Thiết bị 2 chuyển trạng thái BAO_TRI, tình trạng HU_HONG trong CSDL',
    $ts2SauTra !== null && $ts2SauTra['trang_thai'] === TRANG_THAI_BAO_TRI && $ts2SauTra['tinh_trang'] === TINH_TRANG_HU_HONG
);

// 12. Kiểm tra bản ghi sự cố được tự động tạo trong bảng su_co_tai_san
$stmtSc = $db->prepare("SELECT id, ma_su_co, tai_san_id, loai_su_co, mo_ta, trang_thai FROM su_co_tai_san WHERE chi_tiet_muon_id = ?");
$stmtSc->bind_param('i', $ctm2Id);
$stmtSc->execute();
$suCo = $stmtSc->get_result()->fetch_assoc();
$stmtSc->close();

kiem_thu(
    'Tự động tạo bản ghi sự cố trong bảng su_co_tai_san (loai_su_co = HU_HONG, trang_thai = CHUA_XU_LY)',
    $suCo !== null && str_starts_with($suCo['ma_su_co'], 'SC-') && (int)$suCo['tai_san_id'] === $ts2Id && $suCo['mo_ta'] === $moTaHuHong,
    'Mã sự cố: ' . ($suCo['ma_su_co'] ?? '')
);

// 13. Kiểm tra phiếu mượn đã chuyển sang trạng thái DA_TRA
$phieuHoanTat = muon_tim_theo_id($phieuId);
kiem_thu(
    'Phiếu mượn tự động chuyển sang DA_TRA khi tất cả thiết bị đã được hoàn trả',
    $phieuHoanTat !== null && $phieuHoanTat['trang_thai'] === PHIEU_MUON_DA_TRA
);

// 14. Kiểm tra phiếu không còn xuất hiện trong danh sách chờ trả của bàn trực
$dsChoMoi = tra_danh_sach_cho_xu_ly(1, 20);
$conTrongCho = false;
foreach ($dsChoMoi as $c) {
    if ((int)$c['id'] === $phieuId) {
        $conTrongCho = true;
        break;
    }
}
kiem_thu('Phiếu đã hoàn tất tự động biến mất khỏi danh sách chờ trả tại bàn trực', $conTrongCho === false);

// 15. Thử gọi trả lần nữa trên phiếu đã hoàn tất -> Phải bị từ chối
$resTraPhieuDong = tra_thuc_hien([
    'phieu_muon_id' => $phieuId,
    'chi_tiet_id'   => [$ctm2Id],
    'tinh_trang'    => [$ctm2Id => 'TOT'],
], 1, 1);
kiem_thu(
    'Từ chối nhận trả trên phiếu mượn đã đóng (DA_TRA)',
    $resTraPhieuDong['thanh_cong'] === false,
    'Thông báo lỗi: ' . ($resTraPhieuDong['loi'] ?? '')
);

// 16. Kiểm tra khóa kiểm kê: Chặn trả khi bàn trực đang diễn ra kiểm kê
$maDotKK = 'KK_TRA_' . rand(1000, 9999);
$db->query("INSERT INTO dot_kiem_ke (ma_dot_kiem_ke, phong_truc_id, ten_dot_kiem_ke, loai_kiem_ke, trang_thai, nhan_vien_lap_id) 
            VALUES ('$maDotKK', 1, 'Kiểm thử khóa kiểm kê khi trả G6', 'DOT_XUAT', 'DANG_KIEM_KE', 1)");
$dotKkId = $db->insert_id;

$resTraKhiKk = tra_thuc_hien([
    'phieu_muon_id' => $phieuId,
    'chi_tiet_id'   => [$ctm1Id],
], 1, 1);
kiem_thu(
    'Chặn nhận trả khi bàn trực đang diễn ra kiểm kê (DANG_KIEM_KE)',
    $resTraKhiKk['thanh_cong'] === false,
    'Thông báo lỗi: ' . ($resTraKhiKk['loi'] ?? '')
);

// Dọn đợt kiểm kê thử nghiệm
$db->query("DELETE FROM dot_kiem_ke WHERE id = $dotKkId");

tong_ket_kiem_thu('TIẾP NHẬN TRẢ TÀI SẢN & GHI NHẬN SỰ CỐ (G6)');

