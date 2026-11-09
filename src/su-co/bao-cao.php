<?php
/**
 * Controller: Báo cáo sự cố thiết bị mới (thủ công / đột xuất) (G9).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/su-co-service.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_dang_nhap();
yeu_cau_quyen('ghi_nhan_su_co');

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

$taiSanPreselectId = doc_so_nguyen('tai_san_id', 0, $_GET);
$taiSanChon = null;
if ($taiSanPreselectId > 0) {
    $taiSanChon = tai_san_tim_theo_id($taiSanPreselectId);
}

$loi = '';
$duLieu = [
    'tai_san_id' => $taiSanPreselectId > 0 ? $taiSanPreselectId : doc_so_nguyen('tai_san_id', 0, $_POST),
    'loai_su_co' => (string)($_POST['loai_su_co'] ?? 'HU_HONG'),
    'mo_ta'      => (string)($_POST['mo_ta'] ?? ''),
];

if (la_post()) {
    if (!csrf_kiem_tra()) {
        $loi = 'Phiên làm việc bảo mật đã hết hạn. Vui lòng thử lại.';
        dat_thong_bao('nguy_hiem', $loi);
    } else {
        $limitPt = ($phongTrucId > 0 && $nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN) ? $phongTrucId : null;
        $ketQua = su_co_bao_cao($_POST, (int)$nguoiDung['id'], $limitPt);

        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', "Đã ghi nhận sự cố <strong>{$ketQua['ma_su_co']}</strong> thành công! Trạng thái thiết bị đã được cập nhật tương ứng.");
            chuyen_huong('su-co/chi-tiet.php?id=' . $ketQua['id']);
        } else {
            $loi = $ketQua['loi'];
            dat_thong_bao('nguy_hiem', $loi);
        }
    }
}

// Lấy danh sách thiết bị thuộc bàn trực để chọn nhanh nếu cần
$danhSachTaiSan = [];
if ($phongTrucId > 0 && $nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN) {
    $danhSachTaiSan = tai_san_danh_sach(['phong_truc_id' => $phongTrucId, 'trang_thai' => 'SAN_SANG'], 1, 100)['danh_sach'] ?? [];
} else {
    $danhSachTaiSan = tai_san_danh_sach(['trang_thai' => 'SAN_SANG'], 1, 100)['danh_sach'] ?? [];
}

$tieuDeTrang = 'Báo cáo sự cố tài sản mới';
require dirname(__DIR__) . '/app/views/su-co/form.php';

