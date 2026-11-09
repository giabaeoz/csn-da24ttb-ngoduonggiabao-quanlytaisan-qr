<?php
/**
 * API JSON Lấy danh sách tài sản sẵn sàng điều chuyển tại một phòng học nguồn.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/dieu-chuyen-service.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('tao_dieu_chuyen');

$phongHocDiId = doc_so_nguyen('phong_hoc_di_id', 0, $_GET);
$phongHocDenId = doc_so_nguyen('phong_hoc_den_id', 0, $_GET);

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

if ($phongHocDiId <= 0) {
    phan_hoi_json([
        'thanh_cong' => false,
        'loi'        => 'Vui lòng chọn phòng học nguồn hợp lệ.',
        'danh_sach'  => [],
    ], 400);
}

// Nếu là quản trị / quản lý không gán cố định phòng trực, lấy tất cả hoặc phòng trực đầu tiên
if ($phongTrucId <= 0) {
    $db = db();
    $sql = "SELECT ts.id, ts.ma_tai_san, ts.ma_qr, ts.ten_tai_san, ts.hang_san_xuat, ts.model,
                   ts.tinh_trang, ts.trang_thai, ts.pham_vi_tuong_thich, ts.phong_tuong_thich,
                   lts.ten_loai AS ten_loai_tai_san
            FROM tai_san ts
            LEFT JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
            WHERE ts.phong_hoc_hien_tai_id = ?
              AND ts.trang_thai = 'SAN_SANG'
              AND ts.tinh_trang = 'TOT'
              AND NOT EXISTS (
                  SELECT 1 FROM chi_tiet_muon ctm 
                  WHERE ctm.tai_san_id = ts.id AND ctm.thoi_gian_tra IS NULL
              )
            ORDER BY ts.ma_tai_san ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $phongHocDiId);
    $stmt->execute();
    $danhSach = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $danhSach = dieu_chuyen_lay_tai_san_hop_le($phongHocDiId, $phongTrucId);
}

// Nếu có phòng học đích, kiểm tra trước độ tương thích cho từng tài sản
$roomDen = null;
if ($phongHocDenId > 0) {
    $roomDen = phong_hoc_tim_theo_id($phongHocDenId);
}

foreach ($danhSach as &$item) {
    if ($roomDen !== null) {
        $kq = tai_san_kiem_tra_tuong_thich($item, $roomDen);
        $item['tuong_thich'] = $kq['tuong_thich'];
        $item['ly_do_tuong_thich'] = $kq['ly_do'];
    } else {
        $item['tuong_thich'] = true;
        $item['ly_do_tuong_thich'] = '';
    }
}
unset($item);

phan_hoi_json([
    'thanh_cong' => true,
    'danh_sach'  => $danhSach,
    'tong_so'    => count($danhSach),
]);

