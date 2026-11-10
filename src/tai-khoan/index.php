<?php
/**
 * Bộ điều khiển Danh sách & Quản lý Tài khoản Cán bộ (Staff Account Controller).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-khoan-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

// Chỉ Quản trị viên (QUAN_TRI) được phép quản lý tài khoản cán bộ
yeu_cau_quyen('quan_ly_tai_khoan');

$nguoiDungHienTai = nguoi_dung_hien_tai();
$nguoiThucHienId = (int)($nguoiDungHienTai['id'] ?? 0);

// Xử lý các thao tác POST (đổi trạng thái, đặt lại mật khẩu, xóa)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra()) {
        dat_thong_bao('loi', 'Phiên bảo mật đã hết hạn. Vui lòng thử lại.');
        chuyen_huong('tai-khoan/index.php');
    }

    $hanhDong = (string)($_POST['hanh_dong'] ?? '');
    $id = doc_so_nguyen('id', 0, $_POST);

    if ($hanhDong === 'doi_trang_thai' && $id > 0) {
        $ketQua = tai_khoan_doi_trang_thai($id, $nguoiThucHienId);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã cập nhật trạng thái hoạt động của tài khoản.');
        } else {
            dat_thong_bao('loi', $ketQua['loi']);
        }
        chuyen_huong('tai-khoan/index.php');
    }

    if ($hanhDong === 'dat_lai_mat_khau' && $id > 0) {
        $matKhauMoi = (string)($_POST['mat_khau_moi'] ?? '');
        $xacNhanMatKhau = (string)($_POST['xac_nhan_mat_khau'] ?? '');

        if ($matKhauMoi !== $xacNhanMatKhau) {
            dat_thong_bao('loi', 'Mật khẩu xác nhận không khớp.');
        } else {
            $ketQua = tai_khoan_dat_lai_mat_khau($id, $matKhauMoi);
            if ($ketQua['thanh_cong']) {
                dat_thong_bao('thanh_cong', 'Đã đặt lại mật khẩu cho tài khoản thành công.');
            } else {
                dat_thong_bao('loi', $ketQua['loi']);
            }
        }
        chuyen_huong('tai-khoan/index.php');
    }

    if ($hanhDong === 'xoa' && $id > 0) {
        $ketQua = tai_khoan_xoa($id, $nguoiThucHienId);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã xóa tài khoản cán bộ thành công.');
        } else {
            dat_thong_bao('loi', $ketQua['loi']);
        }
        chuyen_huong('tai-khoan/index.php');
    }
}

// Tiếp nhận tham số tìm kiếm & lọc
$tuKhoa = trim((string)($_GET['q'] ?? ''));
$vaiTro = trim((string)($_GET['vai_tro'] ?? ''));
$phongTrucId = doc_so_nguyen('phong_truc_id', 0, $_GET);
$trangThai = isset($_GET['trang_thai']) && $_GET['trang_thai'] !== '' ? (string)$_GET['trang_thai'] : '';
$trang = doc_so_nguyen('trang', 1, $_GET);

$boLoc = [
    'tu_khoa'        => $tuKhoa,
    'vai_tro'        => $vaiTro,
    'phong_truc_id'  => $phongTrucId > 0 ? $phongTrucId : null,
    'dang_hoat_dong' => $trangThai !== '' ? (int)$trangThai : null,
];

$ketQua = tai_khoan_danh_sach($boLoc, $trang, 15);
$danhSachTaiKhoan = $ketQua['danh_sach'];
$tongSo = $ketQua['tong_so'];
$tongSoTrang = $ketQua['tong_so_trang'];
$trang = $ketQua['trang'];

// Thống kê nhanh và danh sách phòng trực cho bộ lọc
$thongKe = tai_khoan_thong_ke_nhanh();
$danhSachPhongTruc = phong_truc_danh_sach();

require dirname(__DIR__) . '/app/views/tai-khoan/danh-sach.php';
