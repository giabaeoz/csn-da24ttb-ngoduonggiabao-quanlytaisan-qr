<?php
/**
 * Bộ điều khiển Thêm mới Tài sản / Thiết bị.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('quan_ly_tai_san');

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucMacDinh = ($nguoiDung['vai_tro'] !== VAI_TRO_QUAN_TRI && !empty($nguoiDung['phong_truc_id']))
    ? (int)$nguoiDung['phong_truc_id']
    : 0;

$thongBaoLoi = '';
$taiSan = [
    'id'                   => 0,
    'ma_tai_san'           => '',
    'ma_qr'                => '',
    'ten_tai_san'          => '',
    'loai_tai_san_id'      => 0,
    'phong_truc_id'        => $phongTrucMacDinh,
    'hang_san_xuat'        => '',
    'model'                => '',
    'hinh_anh'             => '',
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
        $loaiIdNhap = doc_so_nguyen('loai_tai_san_id', 0, $_POST);
        $loaiTenNhap = trim((string)($_POST['loai_tai_san_nhap'] ?? ''));
        if ($loaiTenNhap !== '') {
            $loaiIdNhap = loai_tai_san_tim_hoac_tao_nhanh($loaiTenNhap);
        }

        $phongPhuHopId = doc_so_nguyen('phong_hoc_phu_hop_id', 0, $_POST) ?: null;
        $phongPhuHopNhap = trim((string)($_POST['phong_hoc_phu_hop_nhap'] ?? ''));
        if ($phongPhuHopNhap !== '') {
            $phongPhuHopId = phong_hoc_tim_hoac_tao_nhanh($phongPhuHopNhap) ?: null;
        }

        // Xử lý upload ảnh đại diện nếu có
        $duongDanAnh = null;
        if (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] !== UPLOAD_ERR_NO_FILE) {
            $kqUpload = tai_san_tai_anh_len($_FILES['hinh_anh']);
            if (!$kqUpload['thanh_cong']) {
                $thongBaoLoi = $kqUpload['loi'];
            } else {
                $duongDanAnh = $kqUpload['duong_dan'];
            }
        }

        $phongTrucChon = ($phongTrucMacDinh > 0) ? $phongTrucMacDinh : doc_so_nguyen('phong_truc_id', 0, $_POST);

        if ($thongBaoLoi === '') {
            $duLieu = [
                'ma_tai_san'           => (string)($_POST['ma_tai_san'] ?? ''),
                'ma_qr'                => (string)($_POST['ma_qr'] ?? ''),
                'ten_tai_san'          => (string)($_POST['ten_tai_san'] ?? ''),
                'loai_tai_san_id'      => $loaiIdNhap,
                'phong_truc_id'        => $phongTrucChon,
                'hang_san_xuat'        => (string)($_POST['hang_san_xuat'] ?? ''),
                'model'                => (string)($_POST['model'] ?? ''),
                'hinh_anh'             => $duongDanAnh,
                'phong_hoc_phu_hop_id' => $phongPhuHopId,
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
                if ($duongDanAnh !== null) {
                    tai_san_xoa_file_anh($duongDanAnh);
                }
            }
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

