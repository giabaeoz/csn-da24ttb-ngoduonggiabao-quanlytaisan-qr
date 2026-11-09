<?php
/**
 * Bộ điều khiển Chỉnh sửa Tài sản / Thiết bị.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('quan_ly_tai_san');

$id = doc_so_nguyen('id', 0, $_REQUEST);
$taiSan = tai_san_tim_theo_id($id);

if ($taiSan === null) {
    phan_hoi_loi_404('Không tìm thấy tài sản yêu cầu chỉnh sửa.');
}

$thongBaoLoi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra()) {
        $thongBaoLoi = 'Phiên làm việc bảo mật đã hết hạn. Vui lòng thử lại.';
    } else {
        $duLieu = [
            'ma_tai_san'           => (string)($_POST['ma_tai_san'] ?? ''),
            'ma_qr'                => (string)($_POST['ma_qr'] ?? ''),
            'ten_tai_san'          => (string)($_POST['ten_tai_san'] ?? ''),
            'loai_tai_san_id'      => doc_so_nguyen('loai_tai_san_id', 0, $_POST),
            'phong_truc_id'        => doc_so_nguyen('phong_truc_id', 0, $_POST),
            'hang_san_xuat'        => (string)($_POST['hang_san_xuat'] ?? ''),
            'model'                => (string)($_POST['model'] ?? ''),
            'phong_hoc_phu_hop_id' => doc_so_nguyen('phong_hoc_phu_hop_id', 0, $_POST) ?: null,
            'tinh_trang'           => (string)($_POST['tinh_trang'] ?? $taiSan['tinh_trang']),
            'trang_thai'           => (string)($_POST['trang_thai'] ?? $taiSan['trang_thai']),
            'ngay_dua_vao_su_dung' => (string)($_POST['ngay_dua_vao_su_dung'] ?? $taiSan['ngay_dua_vao_su_dung']),
            'mo_ta'                => (string)($_POST['mo_ta'] ?? ''),
            'pham_vi_tuong_thich'  => (string)($_POST['pham_vi_tuong_thich'] ?? $taiSan['pham_vi_tuong_thich']),
            'phong_tuong_thich'    => $_POST['phong_tuong_thich'] ?? null,
        ];

        $taiSan = array_merge($taiSan, $duLieu);

        $ketQua = tai_san_luu($duLieu, $id);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã cập nhật thông tin thiết bị thành công!');
            chuyen_huong('tai-san/index.php');
        } else {
            $thongBaoLoi = $ketQua['loi'];
        }
    }
}

// Lấy danh mục phục vụ chọn lựa
$danhSachLoai = loai_tai_san_danh_sach(true);
$danhSachPhongTruc = phong_truc_danh_sach(true);
$danhSachPhongHoc = phong_hoc_danh_sach(true);

$laThemMoi = false;
$tieuDeTrang = 'Chỉnh sửa tài sản: ' . ($taiSan['ma_tai_san'] ?? '');
require dirname(__DIR__) . '/app/views/tai-san/form.php';

