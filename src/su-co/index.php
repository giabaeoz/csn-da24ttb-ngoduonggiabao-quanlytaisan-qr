<?php
/**
 * Controller: Danh sách các sự cố tài sản và quản lý bảo trì/sửa chữa (G9).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/su-co-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_dang_nhap();
yeu_cau_mot_trong_cac_quyen(['xem_su_co', 'ghi_nhan_su_co', 'xu_ly_su_co']);

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

$boLoc = [
    'tu_khoa'      => trim((string)($_GET['tu_khoa'] ?? '')),
    'phong_truc_id'=> doc_so_nguyen('phong_truc_id', $phongTrucId, $_GET),
    'loai_su_co'   => trim((string)($_GET['loai_su_co'] ?? '')),
    'trang_thai'   => trim((string)($_GET['trang_thai'] ?? '')),
    'tu_ngay'      => trim((string)($_GET['tu_ngay'] ?? '')),
    'den_ngay'     => trim((string)($_GET['den_ngay'] ?? '')),
];

// Nếu không phải Quản trị viên và có gán phòng trực, cố định lọc theo phòng trực của mình
if ($nguoiDung['vai_tro'] !== VAI_TRO_QUAN_TRI && $phongTrucId > 0) {
    $boLoc['phong_truc_id'] = $phongTrucId;
}

$trangHienTai = doc_so_nguyen('trang', 1, $_GET);
$soMoiTrang = 15;

$ketQua = su_co_danh_sach($boLoc, $trangHienTai, $soMoiTrang);
$danhSachSuCo = $ketQua['danh_sach'];
$tongSo = $ketQua['tong_so'];
$tongTrang = $ketQua['tong_trang'];
$thongKe = $ketQua['thong_ke'];

$danhSachPhongTruc = phong_truc_danh_sach(true);

$tieuDeTrang = 'Quản lý sự cố tài sản & bảo trì';
require dirname(__DIR__) . '/app/views/su-co/danh-sach.php';

