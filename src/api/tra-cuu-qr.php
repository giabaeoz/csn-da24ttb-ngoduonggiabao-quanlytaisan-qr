<?php
/**
 * API JSON Tra cứu đa năng qua Mã QR (QR Universal Lookup API).
 * Nhận diện tự động mã QR thiết bị, mã QR phiếu mượn hoặc mã phòng học.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/app/services/muon-tra-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('xem_danh_sach_tai_san');

$maQr = trim((string)($_GET['ma_qr'] ?? $_GET['code'] ?? ''));
if ($maQr === '') {
    phan_hoi_json(['thanh_cong' => false, 'loi' => 'Thiếu tham số ma_qr'], 400);
}

// 1. Kiểm tra xem có phải mã tài sản / tem QR thiết bị không
$taiSan = tai_san_tim_theo_qr($maQr) ?? tai_san_tim_theo_ma($maQr);
if ($taiSan !== null) {
    $phongHocDichId = doc_so_nguyen('phong_hoc_id', 0, $_GET);
    $tuongThich = null;
    if ($phongHocDichId > 0) {
        $phongHoc = phong_hoc_tim_theo_id($phongHocDichId);
        if ($phongHoc !== null) {
            $tuongThich = tai_san_kiem_tra_tuong_thich($taiSan, $phongHoc);
        }
    }

    phan_hoi_json([
        'thanh_cong'           => true,
        'loai'                 => 'TAI_SAN',
        'du_lieu'              => $taiSan,
        'co_the_muon'          => ($taiSan['trang_thai'] === TRANG_THAI_SAN_SANG && $taiSan['tinh_trang'] === TINH_TRANG_TOT),
        'kiem_tra_tuong_thich' => $tuongThich,
    ]);
}

// 2. Kiểm tra xem có phải mã phiếu mượn (PM-...)
$phongTrucId = doc_so_nguyen('phong_truc_id', 0, $_GET);
$phieu = tra_tim_phieu_theo_tu_khoa($maQr, $phongTrucId > 0 ? $phongTrucId : null);
if ($phieu !== null) {
    phan_hoi_json([
        'thanh_cong' => true,
        'loai'       => 'PHIEU_MUON',
        'du_lieu'    => $phieu,
    ]);
}

// 3. Kiểm tra xem có phải mã phòng học (phong_hoc)
$db = db();
$stmtPh = $db->prepare("SELECT * FROM phong_hoc WHERE ma_phong = ? LIMIT 1");
$stmtPh->bind_param('s', $maQr);
$stmtPh->execute();
$phongHoc = $stmtPh->get_result()->fetch_assoc();
$stmtPh->close();

if ($phongHoc !== null) {
    phan_hoi_json([
        'thanh_cong' => true,
        'loai'       => 'PHONG_HOC',
        'du_lieu'    => $phongHoc,
    ]);
}

phan_hoi_json([
    'thanh_cong' => false,
    'loi'        => "Không tìm thấy dữ liệu khớp với mã QR '$maQr'.",
], 404);
