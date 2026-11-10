<?php
/**
 * Bộ điều khiển Thêm mới Tài khoản Cán bộ.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-khoan-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('quan_ly_tai_khoan');

$nguoiDungHienTai = nguoi_dung_hien_tai();
$nguoiThucHienId = (int)($nguoiDungHienTai['id'] ?? 0);

$thongBaoLoi = '';
$taiKhoan = [
    'id'             => 0,
    'ma_nhan_vien'   => '',
    'ten_dang_nhap'  => '',
    'ho_ten'         => '',
    'so_dien_thoai'  => '',
    'vai_tro'        => VAI_TRO_NHAN_VIEN,
    'phong_truc_id'  => 0,
    'dang_hoat_dong' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra()) {
        $thongBaoLoi = 'Phiên làm việc bảo mật đã hết hạn. Vui lòng thử lại.';
    } else {
        $duLieu = [
            'ma_nhan_vien'      => (string)($_POST['ma_nhan_vien'] ?? ''),
            'ten_dang_nhap'     => (string)($_POST['ten_dang_nhap'] ?? ''),
            'ho_ten'            => (string)($_POST['ho_ten'] ?? ''),
            'so_dien_thoai'     => (string)($_POST['so_dien_thoai'] ?? ''),
            'vai_tro'           => (string)($_POST['vai_tro'] ?? VAI_TRO_NHAN_VIEN),
            'phong_truc_id'     => doc_so_nguyen('phong_truc_id', 0, $_POST) ?: null,
            'dang_hoat_dong'    => isset($_POST['dang_hoat_dong']) ? (int)$_POST['dang_hoat_dong'] : 0,
            'mat_khau'          => (string)($_POST['mat_khau'] ?? ''),
            'xac_nhan_mat_khau' => (string)($_POST['xac_nhan_mat_khau'] ?? ''),
        ];

        $taiKhoan = array_merge($taiKhoan, $duLieu);

        $ketQua = tai_khoan_luu($duLieu, null, $nguoiThucHienId);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã thêm tài khoản cán bộ mới thành công.');
            chuyen_huong('tai-khoan/index.php');
        } else {
            $thongBaoLoi = $ketQua['loi'];
        }
    }
}

$danhSachPhongTruc = phong_truc_danh_sach(true);
$laThemMoi = true;

require dirname(__DIR__) . '/app/views/tai-khoan/form.php';
