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
$tabHienTai = (string)($_GET['tab'] ?? 'dang_muon');
$tuKhoa = trim((string)($_GET['q'] ?? ''));
$phongLoc = trim((string)($_GET['phong'] ?? ''));
$trang = doc_so_nguyen('trang', 1, $_GET);

$ketQua = nguoi_muon_danh_sach([
    'tu_khoa'         => $tuKhoa,
    'loai_nguoi_muon' => '',
], $trang, 10);

$danhSachNguoiMuon = $ketQua['danh_sach'];
$tongSo = $ketQua['tong_so'];
$tongSoTrang = $ketQua['tong_so_trang'];

// Người mượn đang chọn hiển thị ở khối chi tiết
$chonId = doc_so_nguyen('chon_id', 0, $_GET);
if ($chonId <= 0 && !empty($danhSachNguoiMuon)) {
    $chonId = (int)$danhSachNguoiMuon[0]['id'];
}
$nguoiMuonChon = ($chonId > 0) ? nguoi_muon_tim_theo_id($chonId) : null;
$lichSuMuonChon = ($chonId > 0) ? nguoi_muon_lich_su_muon($chonId, 5) : [];

// Nạp giao diện
require dirname(__DIR__) . '/app/views/nguoi-muon/danh-sach.php';
