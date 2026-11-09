<?php
/**
 * Bộ điều khiển Thêm mới Tài sản / Thiết bị.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('quan_ly_tai_san');

$thongBaoLoi = '';
$taiSan = [
    'id'                   => 0,
    'ma_tai_san'           => '',
    'ma_qr'                => '',
    'ten_tai_san'          => '',
    'loai_tai_san_id'      => 0,
    'phong_truc_id'        => 0,
    'hang_san_xuat'        => '',
    'model'                => '',
    'phong_hoc_phu_hop_id' => null,
    'ngay_dua_vao_su_dung' => date('Y-m-d'),
    'mo_ta'                => '',
    'pham_vi_tuong_thich'  => TUONG_THICH_LINH_HOAT,
    'phong_tuong_thich'    => '[]',
];

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
            'ngay_dua_vao_su_dung' => (string)($_POST['ngay_dua_vao_su_dung'] ?? date('Y-m-d')),
            'mo_ta'                => (string)($_POST['mo_ta'] ?? ''),
            'pham_vi_tuong_thich'  => (string)($_POST['pham_vi_tuong_thich'] ?? TUONG_THICH_LINH_HOAT),
            'phong_tuong_thich'    => $_POST['phong_tuong_thich'] ?? null,
        ];

        $taiSan = array_merge($taiSan, $duLieu);

        $ketQua = tai_san_luu($duLieu, null);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã thêm thiết bị mới thành công!');
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

$laThemMoi = true;
$tieuDeTrang = 'Thêm mới tài sản / thiết bị';
require dirname(__DIR__) . '/app/views/tai-san/form.php';
