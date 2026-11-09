<?php
/**
 * Bộ điều khiển Lịch sử Phiếu mượn - trả (Loan History Controller).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/muon-tra-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('xem_lich_su_muon_tra');

$nguoiDung = nguoi_dung_hien_tai();

// Tiếp nhận tham số tìm kiếm và bộ lọc
$tuKhoa = trim((string)($_GET['q'] ?? ''));
$trangThai = trim((string)($_GET['trang_thai'] ?? ''));
$ngayMuon = trim((string)($_GET['ngay_muon'] ?? ''));
$phongTrucId = doc_so_nguyen('phong_truc_id', 0, $_GET);
$trang = doc_so_nguyen('trang', 1, $_GET);

// Nếu là nhân viên trực và chưa chọn phòng thì mặc định phòng của mình
if ($nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN && empty($_GET['phong_truc_id']) && !isset($_GET['tat_ca_phong'])) {
    $phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);
}

$boLoc = [
    'tu_khoa'       => $tuKhoa,
    'trang_thai'    => $trangThai !== '' ? $trangThai : null,
    'ngay_muon'     => $ngayMuon !== '' ? $ngayMuon : null,
    'phong_truc_id' => $phongTrucId > 0 ? $phongTrucId : null,
];

$ketQua = muon_danh_sach($boLoc, $trang, 15);
$danhSachPhieu = $ketQua['danh_sach'];
$tongSo = $ketQua['tong_so'];
$tongSoTrang = $ketQua['tong_so_trang'];

$danhSachPhongTruc = phong_truc_danh_sach(true);

$tieuDeTrang = 'Lịch sử mượn - trả tài sản';
require dirname(__DIR__) . '/app/views/muon-tra/danh-sach.php';
