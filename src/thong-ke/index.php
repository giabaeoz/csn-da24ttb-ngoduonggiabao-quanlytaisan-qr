<?php
/**
 * Controller: Báo cáo Thống kê & Phân tích Dữ liệu Hệ thống (G10).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/thong-ke-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_dang_nhap();
yeu_cau_quyen('xem_thong_ke');

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

$phongTrucFilter = null;
if ($nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN && $phongTrucId > 0) {
    $phongTrucFilter = $phongTrucId;
} else {
    $reqPt = doc_so_nguyen('phong_truc_id', 0, $_GET);
    $phongTrucFilter = $reqPt > 0 ? $reqPt : null;
}

$khoangThoiGian = trim((string)($_GET['khoang'] ?? '7_ngay'));
if (!in_array($khoangThoiGian, ['7_ngay', '30_ngay', '12_thang'], true)) {
    $khoangThoiGian = '7_ngay';
}

// 1. Chỉ số tổng hợp KPI
$kpi = thong_ke_tong_quan($phongTrucFilter);

// 2. Xu hướng mượn trả theo thời gian
$muonTra = thong_ke_muon_tra_theo_thoi_gian($khoangThoiGian, $phongTrucFilter);

// 3. Cơ cấu chủng loại tài sản
$taiSanLoai = thong_ke_tai_san_theo_loai($phongTrucFilter);

// 4. Phân bố trạng thái tài sản
$taiSanTrangThai = thong_ke_tai_san_theo_trang_thai($phongTrucFilter);

// 5. Đối tượng mượn
$doiTuong = thong_ke_doi_tuong_muon($phongTrucFilter);

// 6. Phân tích sự cố
$suCo = thong_ke_su_co_chi_tiet($phongTrucFilter);

// 7. Top phòng học & Top thiết bị
$topPhongHoc = thong_ke_top_phong_hoc(5, $phongTrucFilter);
$topTaiSan = thong_ke_top_tai_san_muon(5, $phongTrucFilter);

// Danh mục phòng trực cho bộ lọc
$danhSachPhongTruc = phong_truc_danh_sach(true);

$tieuDeTrang = 'Báo cáo thống kê & Biểu đồ phân tích';
require dirname(__DIR__) . '/app/views/thong-ke/index.php';
