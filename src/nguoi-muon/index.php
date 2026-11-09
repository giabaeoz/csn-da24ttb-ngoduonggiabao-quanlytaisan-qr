<?php
/**
 * Bộ điều khiển Quản lý Người mượn (Borrower Controller).
 * Hiển thị danh sách người mượn, phân trang và tìm kiếm.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/nguoi-muon-service.php';

// Yêu cầu quyền quản lý hồ sơ người mượn (QUAN_TRI, QUAN_LY, NHAN_VIEN)
yeu_cau_quyen('quan_ly_nguoi_muon');

// Xử lý thao tác xóa người mượn qua POST
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
    }
}

// Tiếp nhận tham số tìm kiếm và phân trang
$tuKhoa = trim((string)($_GET['q'] ?? ''));
$loaiNguoiMuon = trim((string)($_GET['loai'] ?? ''));
$trang = doc_so_nguyen('trang', 1, $_GET);

$ketQua = nguoi_muon_danh_sach([
    'tu_khoa'         => $tuKhoa,
    'loai_nguoi_muon' => $loaiNguoiMuon,
], $trang, 10);

$danhSachNguoiMuon = $ketQua['danh_sach'];
$tongSo = $ketQua['tong_so'];
$tongSoTrang = $ketQua['tong_so_trang'];

// Nạp giao diện
require dirname(__DIR__) . '/app/views/nguoi-muon/danh-sach.php';
