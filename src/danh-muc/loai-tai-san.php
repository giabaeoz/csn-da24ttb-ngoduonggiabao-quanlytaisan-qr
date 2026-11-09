<?php
/**
 * Bộ điều khiển Quản lý Chủng loại tài sản (Asset Category Controller).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('quan_ly_danh_muc');

// Xử lý các thao tác POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra()) {
        dat_thong_bao('loi', 'Phiên bảo mật đã hết hạn. Vui lòng thử lại.');
        chuyen_huong('danh-muc/loai-tai-san.php');
    }

    $hanhDong = (string)($_POST['hanh_dong'] ?? '');

    // 1. Thêm mới hoặc Cập nhật loại tài sản
    if ($hanhDong === 'luu') {
        $id = doc_so_nguyen('id', 0, $_POST);
        $ketQua = loai_tai_san_luu([
            'ma_loai'        => (string)($_POST['ma_loai'] ?? ''),
            'ten_loai'       => (string)($_POST['ten_loai'] ?? ''),
            'mo_ta'          => (string)($_POST['mo_ta'] ?? ''),
            'dang_hoat_dong' => (int)($_POST['dang_hoat_dong'] ?? 1),
        ], $id > 0 ? $id : null);

        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', $id > 0 ? 'Đã cập nhật loại tài sản thành công!' : 'Đã thêm loại tài sản mới thành công!');
        } else {
            dat_thong_bao('loi', $ketQua['loi']);
        }
        chuyen_huong('danh-muc/loai-tai-san.php');
    }

    // 2. Đổi trạng thái hoạt động
    if ($hanhDong === 'doi_trang_thai') {
        $id = doc_so_nguyen('id', 0, $_POST);
        if ($id > 0 && loai_tai_san_doi_trang_thai($id)) {
            dat_thong_bao('thanh_cong', 'Đã đổi trạng thái loại tài sản.');
        } else {
            dat_thong_bao('loi', 'Không thể đổi trạng thái loại tài sản.');
        }
        chuyen_huong('danh-muc/loai-tai-san.php');
    }

    // 3. Xóa loại tài sản
    if ($hanhDong === 'xoa') {
        $id = doc_so_nguyen('id', 0, $_POST);
        $ketQua = loai_tai_san_xoa($id);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã xóa loại tài sản thành công.');
        } else {
            dat_thong_bao('loi', $ketQua['loi']);
        }
        chuyen_huong('danh-muc/loai-tai-san.php');
    }
}

// Lấy danh sách loại tài sản
$danhSachLoai = loai_tai_san_danh_sach();

// Nạp giao diện
require dirname(__DIR__) . '/app/views/danh-muc/loai-tai-san.php';
