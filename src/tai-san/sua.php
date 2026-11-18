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

$nguoiDung = nguoi_dung_hien_tai();

if ($taiSan === null) {
    phan_hoi_loi_404('Không tìm thấy tài sản yêu cầu chỉnh sửa.');
}

// Kiểm tra thẩm quyền quản lý tài sản theo phòng trực
if ($nguoiDung['vai_tro'] !== VAI_TRO_QUAN_TRI && !empty($nguoiDung['phong_truc_id'])) {
    if ((int)$taiSan['phong_truc_id'] !== (int)$nguoiDung['phong_truc_id']) {
        phan_hoi_loi_403('Bạn chỉ có quyền quản lý và chỉnh sửa tài sản thuộc phòng trực của mình.');
    }
}

$thongBaoLoi = '';

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

        // Xử lý cập nhật ảnh đại diện
        $duongDanAnh = $taiSan['hinh_anh'] ?? null;
        $xoaAnh = !empty($_POST['xoa_hinh_anh']);

        if ($xoaAnh) {
            tai_san_xoa_file_anh($taiSan['hinh_anh'] ?? null);
            $duongDanAnh = null;
        } elseif (isset($_FILES['hinh_anh']) && $_FILES['hinh_anh']['error'] !== UPLOAD_ERR_NO_FILE) {
            $kqUpload = tai_san_tai_anh_len($_FILES['hinh_anh'], $taiSan['hinh_anh'] ?? null);
            if (!$kqUpload['thanh_cong']) {
                $thongBaoLoi = $kqUpload['loi'];
            } else {
                $duongDanAnh = $kqUpload['duong_dan'];
            }
        }

        if ($thongBaoLoi === '') {
            $duLieu = [
                'ma_tai_san'           => (string)($_POST['ma_tai_san'] ?? ''),
                'ma_qr'                => (string)($_POST['ma_qr'] ?? ''),
                'ten_tai_san'          => (string)($_POST['ten_tai_san'] ?? ''),
                'loai_tai_san_id'      => $loaiIdNhap,
                'phong_truc_id'        => doc_so_nguyen('phong_truc_id', 0, $_POST),
                'hang_san_xuat'        => (string)($_POST['hang_san_xuat'] ?? ''),
                'model'                => (string)($_POST['model'] ?? ''),
                'hinh_anh'             => $duongDanAnh,
                'phong_hoc_phu_hop_id' => $phongPhuHopId,
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
}

// Lấy danh mục phục vụ chọn lựa
$danhSachLoai = loai_tai_san_danh_sach(true);
$danhSachPhongTruc = phong_truc_danh_sach(true);
$danhSachPhongHoc = phong_hoc_danh_sach(true);

$laThemMoi = false;
$tieuDeTrang = 'Chỉnh sửa tài sản: ' . ($taiSan['ma_tai_san'] ?? '');
require dirname(__DIR__) . '/app/views/tai-san/form.php';

