<?php
/**
 * Bộ điều khiển Danh sách Tài sản / Thiết bị (Asset List Controller).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('xem_danh_sach_tai_san');

$nguoiDung = nguoi_dung_hien_tai();

// Xử lý thao tác xóa hoặc ngừng sử dụng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra()) {
        dat_thong_bao('loi', 'Phiên bảo mật đã hết hạn. Vui lòng thử lại.');
        chuyen_huong('tai-san/index.php');
    }

    // Yêu cầu quyền quản lý tài sản khi thực hiện xóa/ngừng sử dụng
    yeu_cau_quyen('quan_ly_tai_san');

    $hanhDong = (string)($_POST['hanh_dong'] ?? '');
    if ($hanhDong === 'xoa') {
        $id = doc_so_nguyen('id', 0, $_POST);
        $ketQua = tai_san_xoa_hoac_ngung_su_dung($id);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', $ketQua['thong_diep']);
        } else {
            dat_thong_bao('loi', $ketQua['loi']);
        }
        chuyen_huong('tai-san/index.php');
    }
}

// Tiếp nhận các tiêu chí lọc
$tuKhoa = trim((string)($_GET['q'] ?? ''));
$loaiId = doc_so_nguyen('loai_id', 0, $_GET);
$phongTrucId = doc_so_nguyen('phong_truc_id', 0, $_GET);
$tinhTrang = trim((string)($_GET['tinh_trang'] ?? ''));
$trangThai = trim((string)($_GET['trang_thai'] ?? ''));
$trang = doc_so_nguyen('trang', 1, $_GET);

// Nếu không phải Quản trị viên (QUAN_TRI) và tài khoản được phân công phòng trực cụ thể (QUAN_LY hoặc NHAN_VIEN),
// cố định xem theo phạm vi phòng trực của mình
if ($nguoiDung['vai_tro'] !== VAI_TRO_QUAN_TRI && !empty($nguoiDung['phong_truc_id'])) {
    $phongTrucId = (int)$nguoiDung['phong_truc_id'];
}

$boLoc = [
    'tu_khoa'         => $tuKhoa,
    'loai_tai_san_id' => $loaiId > 0 ? $loaiId : null,
    'phong_truc_id'   => $phongTrucId > 0 ? $phongTrucId : null,
    'tinh_trang'      => $tinhTrang !== '' ? $tinhTrang : null,
    'trang_thai'      => $trangThai !== '' ? $trangThai : null,
];

$ketQua = tai_san_danh_sach($boLoc, 1, 5000);
$danhSachTaiSan = $ketQua['danh_sach'];
$tongSo = $ketQua['tong_so'];
$tongSoTrang = 1;

// Lấy danh mục cho dropdown bộ lọc
$danhSachLoai = loai_tai_san_danh_sach(true);
$danhSachPhongTruc = phong_truc_danh_sach(true);

// Thống kê nhanh theo bộ lọc phòng trực
$thongKe = tai_san_thong_ke_nhanh($phongTrucId > 0 ? $phongTrucId : null);

require dirname(__DIR__) . '/app/views/tai-san/danh-sach.php';

