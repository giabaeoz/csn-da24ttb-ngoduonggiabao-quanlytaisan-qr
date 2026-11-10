<?php
/**
 * Bộ điều khiển Chỉnh sửa Tài khoản Cán bộ.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-khoan-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('quan_ly_tai_khoan');

$nguoiDungHienTai = nguoi_dung_hien_tai();
$nguoiThucHienId = (int)($nguoiDungHienTai['id'] ?? 0);

$id = doc_so_nguyen('id', 0, $_REQUEST);
$taiKhoan = tai_khoan_tim_theo_id($id);

if ($taiKhoan === null) {
    phan_hoi_loi_404('Không tìm thấy tài khoản cán bộ yêu cầu chỉnh sửa.');
}

$thongBaoLoi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra()) {
        $thongBaoLoi = 'Phiên làm việc bảo mật đã hết hạn. Vui lòng thử lại.';
    } else {
        $duLieu = [
            'ma_nhan_vien'      => (string)($_POST['ma_nhan_vien'] ?? ''),
            'ten_dang_nhap'     => (string)($_POST['ten_dang_nhap'] ?? ''),
            'ho_ten'            => (string)($_POST['ho_ten'] ?? ''),
            'so_dien_thoai'     => (string)($_POST['so_dien_thoai'] ?? ''),
            'vai_tro'           => (string)($_POST['vai_tro'] ?? $taiKhoan['vai_tro']),
            'phong_truc_id'     => doc_so_nguyen('phong_truc_id', 0, $_POST) ?: null,
            'dang_hoat_dong'    => isset($_POST['dang_hoat_dong']) ? (int)$_POST['dang_hoat_dong'] : 0,
            'mat_khau'          => (string)($_POST['mat_khau'] ?? ''),
            'xac_nhan_mat_khau' => (string)($_POST['xac_nhan_mat_khau'] ?? ''),
        ];

        $taiKhoan = array_merge($taiKhoan, $duLieu);

        $ketQua = tai_khoan_luu($duLieu, $id, $nguoiThucHienId);
        if ($ketQua['thanh_cong']) {
            // Nếu quản trị viên tự sửa thông tin của chính mình thì đồng bộ lại session hiển thị
            if ($id === $nguoiThucHienId) {
                $tkMoi = tai_khoan_tim_theo_id($id);
                if ($tkMoi !== null && isset($_SESSION['nguoi_dung'])) {
                    $_SESSION['nguoi_dung']['ma_nhan_vien']   = (string)$tkMoi['ma_nhan_vien'];
                    $_SESSION['nguoi_dung']['ten_dang_nhap'] = (string)$tkMoi['ten_dang_nhap'];
                    $_SESSION['nguoi_dung']['ho_ten']         = (string)$tkMoi['ho_ten'];
                    $_SESSION['nguoi_dung']['so_dien_thoai']  = (string)($tkMoi['so_dien_thoai'] ?? '');
                    $_SESSION['nguoi_dung']['vai_tro']        = (string)$tkMoi['vai_tro'];
                    $_SESSION['nguoi_dung']['phong_truc_id']  = $tkMoi['phong_truc_id'] !== null ? (int)$tkMoi['phong_truc_id'] : null;
                    $_SESSION['nguoi_dung']['ten_phong_truc'] = (string)($tkMoi['ten_phong_truc'] ?? '');
                    $_SESSION['nguoi_dung']['ma_phong_truc']  = (string)($tkMoi['ma_phong_truc'] ?? '');
                }
            }

            dat_thong_bao('thanh_cong', 'Đã cập nhật thông tin tài khoản cán bộ thành công.');
            chuyen_huong('tai-khoan/index.php');
        } else {
            $thongBaoLoi = $ketQua['loi'];
        }
    }
}

$danhSachPhongTruc = phong_truc_danh_sach();
$laThemMoi = false;

require dirname(__DIR__) . '/app/views/tai-khoan/form.php';
