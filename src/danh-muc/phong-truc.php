<?php
/**
 * Bộ điều khiển Quản lý Phòng trực thiết bị (Desk Controller).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

// Yêu cầu quyền quản lý danh mục (QUAN_TRI hoặc QUAN_LY)
yeu_cau_quyen('quan_ly_danh_muc');

// Xử lý các thao tác POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra()) {
        dat_thong_bao('loi', 'Phiên bảo mật đã hết hạn. Vui lòng thử lại.');
        chuyen_huong('danh-muc/phong-truc.php');
    }

    $hanhDong = (string)($_POST['hanh_dong'] ?? '');

    // 1. Thêm mới hoặc Cập nhật phòng trực
    if ($hanhDong === 'luu') {
        $id = doc_so_nguyen('id', 0, $_POST);
        $ketQua = phong_truc_luu([
            'ma_phong_truc'  => (string)($_POST['ma_phong_truc'] ?? ''),
            'ten_phong_truc' => (string)($_POST['ten_phong_truc'] ?? ''),
            'vi_tri'         => (string)($_POST['vi_tri'] ?? ''),
            'dang_hoat_dong' => (int)($_POST['dang_hoat_dong'] ?? 1),
        ], $id > 0 ? $id : null);

        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', $id > 0 ? 'Đã cập nhật phòng trực thành công!' : 'Đã thêm phòng trực mới thành công!');
        } else {
            dat_thong_bao('loi', $ketQua['loi']);
        }
        chuyen_huong('danh-muc/phong-truc.php');
    }

    // 2. Đổi trạng thái hoạt động
    if ($hanhDong === 'doi_trang_thai') {
        $id = doc_so_nguyen('id', 0, $_POST);
        if ($id > 0 && phong_truc_doi_trang_thai($id)) {
            dat_thong_bao('thanh_cong', 'Đã thay đổi trạng thái phòng trực.');
        } else {
            dat_thong_bao('loi', 'Không thể thay đổi trạng thái phòng trực.');
        }
        chuyen_huong('danh-muc/phong-truc.php');
    }

    // 3. Xóa phòng trực
    if ($hanhDong === 'xoa') {
        $id = doc_so_nguyen('id', 0, $_POST);
        $ketQua = phong_truc_xoa($id);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã xóa phòng trực thành công.');
        } else {
            dat_thong_bao('loi', $ketQua['loi']);
        }
        chuyen_huong('danh-muc/phong-truc.php');
    }
}

// Lấy danh sách phòng trực
$danhSachPhongTruc = phong_truc_danh_sach();

// Nạp giao diện
require dirname(__DIR__) . '/app/views/danh-muc/phong-truc.php';

