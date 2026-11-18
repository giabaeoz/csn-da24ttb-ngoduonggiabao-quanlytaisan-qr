<?php
/**
 * Controller: Danh sách lịch sử điều chuyển thiết bị giữa các phòng học.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/dieu-chuyen-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_dang_nhap();
yeu_cau_mot_trong_cac_quyen(['xem_dieu_chuyen', 'tao_dieu_chuyen']);

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

$boLoc = [
    'tu_khoa'          => trim((string)($_GET['tu_khoa'] ?? '')),
    'phong_hoc_di_id'  => doc_so_nguyen('phong_hoc_di_id', 0, $_GET),
    'phong_hoc_den_id' => doc_so_nguyen('phong_hoc_den_id', 0, $_GET),
    'tu_ngay'          => trim((string)($_GET['tu_ngay'] ?? '')),
    'den_ngay'         => trim((string)($_GET['den_ngay'] ?? '')),
];

// Nếu không phải QUAN_TRI, giới hạn trong phạm vi phòng trực của mình
if ($nguoiDung['vai_tro'] !== VAI_TRO_QUAN_TRI && $phongTrucId > 0) {
    $boLoc['phong_truc_id'] = $phongTrucId;
}

$trangHienTai = doc_so_nguyen('trang', 1, $_GET);
$soMoiTrang = 15;

$ketQua = dieu_chuyen_danh_sach($boLoc, $trangHienTai, $soMoiTrang);
$danhSachDieuChuyen = $ketQua['danh_sach'];
$tongSo = $ketQua['tong_so'];
$tongTrang = $ketQua['tong_trang'];

// Danh sách phòng học để đổ vào combobox lọc
$danhSachPhongHoc = phong_hoc_danh_sach_tat_ca();

$tieuDeTrang = 'Điều chuyển thiết bị';
require dirname(__DIR__) . '/app/views/dieu-chuyen/danh-sach.php';

