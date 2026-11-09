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

// Nếu có truyền phòng học đích, kiểm tra tính tương thích Hãng/Model/Phòng
$kiemTraTuongThich = null;
if ($phongHocDichId > 0) {
    $phongHoc = phong_hoc_tim_theo_id($phongHocDichId);
    if ($phongHoc !== null) {
        $kiemTraTuongThich = tai_san_kiem_tra_tuong_thich($taiSan, $phongHoc);
    }
}

// Kiểm tra điều kiện sẵn sàng cho mượn
$coTheMuon = ($taiSan['trang_thai'] === TRANG_THAI_SAN_SANG && $taiSan['tinh_trang'] === TINH_TRANG_TOT);

phan_hoi_json([
    'thanh_cong'        => true,
    'du_lieu'           => $taiSan,
    'co_the_muon'       => $coTheMuon,
    'kiem_tra_tuong_thich' => $kiemTraTuongThich,
]);
