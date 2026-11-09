<?php
/**
 * Controller: Danh sách các đợt kiểm kê tài sản theo phòng trực.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/kiem-ke-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_dang_nhap();
yeu_cau_mot_trong_cac_quyen(['xem_kiem_ke', 'thuc_hien_kiem_ke', 'mo_dot_kiem_ke']);

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

// Kiểm tra đợt kiểm kê đang diễn ra tại phòng trực hiện tại
$dotDangMo = null;
if ($phongTrucId > 0) {
    $dotDangMo = kiem_ke_tim_dot_dang_mo($phongTrucId);
}

$boLoc = [
    'tu_khoa'      => trim((string)($_GET['tu_khoa'] ?? '')),
    'phong_truc_id'=> doc_so_nguyen('phong_truc_id', $phongTrucId, $_GET),
    'trang_thai'   => trim((string)($_GET['trang_thai'] ?? '')),
    'loai_kiem_ke' => trim((string)($_GET['loai_kiem_ke'] ?? '')),
    'tu_ngay'      => trim((string)($_GET['tu_ngay'] ?? '')),
    'den_ngay'     => trim((string)($_GET['den_ngay'] ?? '')),
];

// Nếu là nhân viên, cố định bàn trực của nhân viên
if ($nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN && $phongTrucId > 0) {
    $boLoc['phong_truc_id'] = $phongTrucId;
}

$trangHienTai = doc_so_nguyen('trang', 1, $_GET);
$soMoiTrang = 15;

$ketQua = kiem_ke_danh_sach($boLoc, $trangHienTai, $soMoiTrang);
$danhSachDotKiemKe = $ketQua['danh_sach'];
$tongSo = $ketQua['tong_so'];
$tongTrang = $ketQua['tong_trang'];

$danhSachPhongTruc = phong_truc_danh_sach(true);

$tieuDeTrang = 'Kiểm kê tài sản theo phòng trực';
require dirname(__DIR__) . '/app/views/kiem-ke/danh-sach.php';
