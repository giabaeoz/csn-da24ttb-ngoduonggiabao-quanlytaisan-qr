<?php
/**
 * API JSON Tra cứu thông tin Tài sản và Kiểm tra độ tương thích với Phòng học.
 * Phục vụ quét mã QR trong giao diện Mượn, Trả, Điều chuyển và Kiểm kê.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

// Yêu cầu quyền xem tài sản
yeu_cau_quyen('xem_danh_sach_tai_san');

$maQr = trim((string)($_GET['ma_qr'] ?? ''));
$maTaiSan = trim((string)($_GET['ma_tai_san'] ?? ''));
$id = doc_so_nguyen('id', 0, $_GET);
$phongHocDichId = doc_so_nguyen('phong_hoc_id', 0, $_GET);
$phongHocNguonId = doc_so_nguyen('phong_hoc_nguon_id', 0, $_GET);

$taiSan = null;
if ($maQr !== '') {
    $taiSan = tai_san_tim_theo_qr($maQr);
} elseif ($maTaiSan !== '') {
    $taiSan = tai_san_tim_theo_ma($maTaiSan);
} elseif ($id > 0) {
    $taiSan = tai_san_tim_theo_id($id);
}

if ($taiSan === null) {
    phan_hoi_json([
        'thanh_cong' => false,
        'loi'        => 'Không tìm thấy tài sản trong hệ thống.',
    ], 404);
}

// Kiểm tra xem tài sản có đang trong phiếu mượn chưa trả không
$db = db();
$stmtM = $db->prepare("SELECT id FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL LIMIT 1");
$stmtM->bind_param('i', $taiSan['id']);
$stmtM->execute();
$dangMuon = $stmtM->get_result()->num_rows > 0;
$stmtM->close();

// Nếu có truyền phòng học đích, kiểm tra tính tương thích Hãng/Model/Phòng
$kiemTraTuongThich = null;
if ($phongHocDichId > 0) {
    $phongHoc = phong_hoc_tim_theo_id($phongHocDichId);
    if ($phongHoc !== null) {
        $kiemTraTuongThich = tai_san_kiem_tra_tuong_thich($taiSan, $phongHoc);
    }
}

// Kiểm tra điều kiện sẵn sàng cho mượn
$coTheMuon = ($taiSan['trang_thai'] === TRANG_THAI_SAN_SANG && $taiSan['tinh_trang'] === TINH_TRANG_TOT && !$dangMuon);

// Kiểm tra điều kiện có thể điều chuyển
$coTheDieuChuyen = ($taiSan['trang_thai'] === TRANG_THAI_SAN_SANG && $taiSan['tinh_trang'] === TINH_TRANG_TOT && !$dangMuon);
$lyDoKhongTheChuyen = '';

if ($dangMuon) {
    $coTheDieuChuyen = false;
    $lyDoKhongTheChuyen = 'Thiết bị đang được mượn chưa hoàn trả.';
} elseif ($taiSan['trang_thai'] !== TRANG_THAI_SAN_SANG || $taiSan['tinh_trang'] !== TINH_TRANG_TOT) {
    $coTheDieuChuyen = false;
    $lyDoKhongTheChuyen = 'Thiết bị không ở trạng thái Tốt và Sẵn sàng.';
} elseif ($phongHocNguonId > 0 && (int)($taiSan['phong_hoc_hien_tai_id'] ?? 0) !== $phongHocNguonId) {
    $coTheDieuChuyen = false;
    $lyDoKhongTheChuyen = 'Thiết bị không thuộc phòng học nguồn đã chọn.';
} elseif ($kiemTraTuongThich !== null && !$kiemTraTuongThich['tuong_thich']) {
    $coTheDieuChuyen = false;
    $lyDoKhongTheChuyen = 'Không tương thích với phòng học đích: ' . $kiemTraTuongThich['ly_do'];
}

phan_hoi_json([
    'thanh_cong'            => true,
    'du_lieu'               => $taiSan,
    'dang_muon'             => $dangMuon,
    'co_the_muon'           => $coTheMuon,
    'co_the_dieu_chuyen'    => $coTheDieuChuyen,
    'ly_do_khong_the_chuyen'=> $lyDoKhongTheChuyen,
    'kiem_tra_tuong_thich'  => $kiemTraTuongThich,
]);
