<?php
/**
 * API JSON: Cung cấp dữ liệu thời gian thực cho các biểu đồ Chart.js (G10).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/thong-ke-service.php';

yeu_cau_dang_nhap();
yeu_cau_mot_trong_cac_quyen(['xem_thong_ke', 'xem_tong_quan']);

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

// Nếu là quản trị viên/quản lý, cho phép lọc theo tham số phong_truc_id
$phongTrucFilter = null;
if ($nguoiDung['vai_tro'] !== VAI_TRO_QUAN_TRI && $phongTrucId > 0) {
    $phongTrucFilter = $phongTrucId;
} else {
    $reqPt = doc_so_nguyen('phong_truc_id', 0, $_GET);
    $phongTrucFilter = $reqPt > 0 ? $reqPt : null;
}

$loaiBieuDo = trim((string)($_GET['loai'] ?? 'muon_tra'));
$khoangThoiGian = trim((string)($_GET['khoang'] ?? '7_ngay'));

switch ($loaiBieuDo) {
    case 'muon_tra':
        $data = thong_ke_muon_tra_theo_thoi_gian($khoangThoiGian, $phongTrucFilter);
        phan_hoi_json(['thanh_cong' => true, 'du_lieu' => $data]);
        break;

    case 'tai_san_loai':
        $data = thong_ke_tai_san_theo_loai($phongTrucFilter);
        phan_hoi_json(['thanh_cong' => true, 'du_lieu' => $data]);
        break;

    case 'tai_san_trang_thai':
        $data = thong_ke_tai_san_theo_trang_thai($phongTrucFilter);
        phan_hoi_json(['thanh_cong' => true, 'du_lieu' => $data]);
        break;

    case 'doi_tuong':
        $data = thong_ke_doi_tuong_muon($phongTrucFilter);
        phan_hoi_json(['thanh_cong' => true, 'du_lieu' => $data]);
        break;

    case 'su_co':
        $data = thong_ke_su_co_chi_tiet($phongTrucFilter);
        phan_hoi_json(['thanh_cong' => true, 'du_lieu' => $data]);
        break;

    case 'tong_quan':
        $data = thong_ke_tong_quan($phongTrucFilter);
        phan_hoi_json(['thanh_cong' => true, 'du_lieu' => $data]);
        break;

    default:
        phan_hoi_json(['thanh_cong' => false, 'loi' => 'Loại biểu đồ không hợp lệ.'], 400);
        break;
}
