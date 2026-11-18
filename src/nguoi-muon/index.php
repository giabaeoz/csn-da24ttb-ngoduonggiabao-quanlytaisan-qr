<?php
/**
 * Bộ điều khiển Quản lý Người mượn (Borrower Controller) - Bám sát phác thảo
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/nguoi-muon-service.php';
require_once dirname(__DIR__) . '/app/services/muon-tra-service.php';

// Yêu cầu quyền quản lý hồ sơ người mượn (QUAN_TRI, QUAN_LY, NHAN_VIEN)
yeu_cau_quyen('quan_ly_nguoi_muon');

// Xử lý các thao tác POST (xóa hoặc cập nhật nhanh)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra()) {
        dat_thong_bao('loi', 'Phiên bảo mật đã hết hạn. Vui lòng thử lại.');
        chuyen_huong('nguoi-muon/index.php');
    }

    $hanhDong = (string)($_POST['hanh_dong'] ?? '');
    if ($hanhDong === 'xoa') {
        $id = doc_so_nguyen('id', 0, $_POST);
        $ketQua = nguoi_muon_xoa($id);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã xóa hồ sơ người mượn thành công.');
        } else {
            dat_thong_bao('loi', $ketQua['loi']);
        }
        chuyen_huong('nguoi-muon/index.php');
    } elseif ($hanhDong === 'cap_nhat_nhanh') {
        $id = doc_so_nguyen('id', 0, $_POST);
        $nmHienTai = nguoi_muon_tim_theo_id($id);
        if ($nmHienTai !== null) {
            $duLieu = [
                'ma_nguoi_muon'   => (string)($nmHienTai['ma_nguoi_muon'] ?? ''),
                'ma_so_truong'    => (string)($_POST['ma_so_truong'] ?? $nmHienTai['ma_so_truong']),
                'ho_ten'          => (string)($_POST['ho_ten'] ?? $nmHienTai['ho_ten']),
                'loai_nguoi_muon' => (string)($nmHienTai['loai_nguoi_muon'] ?? 'SINH_VIEN'),
                'so_dien_thoai'   => (string)($_POST['so_dien_thoai'] ?? $nmHienTai['so_dien_thoai']),
                'lop_don_vi'      => (string)($_POST['lop_don_vi'] ?? $nmHienTai['lop_don_vi']),
            ];
            $ketQua = nguoi_muon_luu($duLieu, $id);
            if ($ketQua['thanh_cong']) {
                dat_thong_bao('thanh_cong', 'Đã cập nhật thông tin người mượn thành công.');
            } else {
                dat_thong_bao('loi', $ketQua['loi']);
            }
        } else {
            dat_thong_bao('loi', 'Không tìm thấy thông tin người mượn.');
        }
        chuyen_huong('nguoi-muon/index.php?chon_id=' . $id);
    }
}

// Tiếp nhận tham số tìm kiếm và phân trang
$tabHienTai = (string)($_GET['tab'] ?? 'tat_ca');
if (!in_array($tabHienTai, ['dang_muon', 'tat_ca', 'qua_han'], true)) {
    $tabHienTai = 'tat_ca';
}
$tuKhoa = trim((string)($_GET['q'] ?? ''));
$phongLoc = trim((string)($_GET['phong'] ?? ''));
$trang = doc_so_nguyen('trang', 1, $_GET);

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = doc_so_nguyen('phong_truc_id', 0, $_GET);

// Nếu không phải Quản trị viên (QUAN_TRI) và có gán phòng trực (QUAN_LY hoặc NHAN_VIEN),
// cố định phạm vi theo phòng trực của mình
if ($nguoiDung['vai_tro'] !== VAI_TRO_QUAN_TRI && !empty($nguoiDung['phong_truc_id'])) {
    $phongTrucId = (int)$nguoiDung['phong_truc_id'];
}

$ketQua = nguoi_muon_danh_sach([
    'tu_khoa'         => $tuKhoa,
    'phong'           => $phongLoc,
    'tab'             => $tabHienTai,
    'loai_nguoi_muon' => '',
    'phong_truc_id'   => $phongTrucId > 0 ? $phongTrucId : null,
], $trang, 100);

$danhSachNguoiMuon = $ketQua['danh_sach'];
$tongSo = $ketQua['tong_so'];
$tongSoTrang = $ketQua['tong_so_trang'];

// Đếm nhanh số lượng cho 3 tab
$dbNm = db();
$wherePtPm = "";
$wherePtNm = "";
if ($phongTrucId > 0) {
    $wherePtPm = " AND pm.phong_truc_id = " . (int)$phongTrucId;
    $wherePtNm = " WHERE EXISTS (SELECT 1 FROM phieu_muon pm WHERE pm.nguoi_muon_id = nm.id AND pm.phong_truc_id = " . (int)$phongTrucId . ")";
}

$demTatCa = (int)($dbNm->query("SELECT COUNT(*) FROM nguoi_muon nm $wherePtNm")->fetch_row()[0] ?? 0);
$demDangMuon = (int)($dbNm->query("SELECT COUNT(DISTINCT pm.nguoi_muon_id) FROM phieu_muon pm JOIN chi_tiet_muon ctm ON pm.id = ctm.phieu_muon_id WHERE ctm.thoi_gian_tra IS NULL $wherePtPm")->fetch_row()[0] ?? 0);
$demQuaHan = (int)($dbNm->query("SELECT COUNT(DISTINCT pm.nguoi_muon_id) FROM phieu_muon pm JOIN chi_tiet_muon ctm ON pm.id = ctm.phieu_muon_id WHERE ctm.thoi_gian_tra IS NULL AND pm.han_tra < NOW() $wherePtPm")->fetch_row()[0] ?? 0);

// Người mượn đang chọn hiển thị ở khối chi tiết
$chonId = doc_so_nguyen('chon_id', 0, $_GET);
if ($chonId <= 0 && !empty($danhSachNguoiMuon)) {
    $chonId = (int)$danhSachNguoiMuon[0]['id'];
}
$nguoiMuonChon = ($chonId > 0) ? nguoi_muon_tim_theo_id($chonId) : null;
$lichSuMuonChon = ($chonId > 0) ? nguoi_muon_lich_su_muon($chonId, $phongTrucId > 0 ? $phongTrucId : null) : [];

// Nạp giao diện
require dirname(__DIR__) . '/app/views/nguoi-muon/danh-sach.php';
