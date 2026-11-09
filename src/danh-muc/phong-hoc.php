<?php
/**
 * Bộ điều khiển Quản lý Phòng học (Classroom Controller).
 * Quản lý danh sách phòng học và cấu hình thiết bị cố định (hãng/model).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('quan_ly_danh_muc');

// Xử lý các thao tác POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra()) {
        dat_thong_bao('loi', 'Phiên bảo mật đã hết hạn. Vui lòng thử lại.');
        chuyen_huong('danh-muc/phong-hoc.php');
    }

    $hanhDong = (string)($_POST['hanh_dong'] ?? '');

    // 1. Thêm mới hoặc Cập nhật phòng học
    if ($hanhDong === 'luu') {
        $id = doc_so_nguyen('id', 0, $_POST);

        // Thu thập danh sách thiết bị cố định phòng
        $cacThietBi = [];
        $danhSachTbPost = $_POST['thiet_bi_phong'] ?? [];
        if (is_array($danhSachTbPost)) {
            foreach ($danhSachTbPost as $tb) {
                if (is_array($tb)) {
                    $ten = trim((string)($tb['ten'] ?? ''));
                    $hang = trim((string)($tb['hang'] ?? ''));
                    $model = trim((string)($tb['model'] ?? ''));
                    if ($ten !== '' || $hang !== '') {
                        $cacThietBi[] = [
                            'ten'   => $ten,
                            'hang'  => $hang,
                            'model' => $model,
                        ];
                    }
                }
            }
        }

        $ketQua = phong_hoc_luu([
            'ma_phong'       => (string)($_POST['ma_phong'] ?? ''),
            'ten_phong'      => (string)($_POST['ten_phong'] ?? ''),
            'toa_nha'        => (string)($_POST['toa_nha'] ?? ''),
            'tang'           => (string)($_POST['tang'] ?? ''),
            'thiet_bi_phong' => $cacThietBi,
            'dang_hoat_dong' => (int)($_POST['dang_hoat_dong'] ?? 1),
        ], $id > 0 ? $id : null);

        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', $id > 0 ? 'Đã cập nhật phòng học thành công!' : 'Đã thêm phòng học mới thành công!');
        } else {
            dat_thong_bao('loi', $ketQua['loi']);
        }
        chuyen_huong('danh-muc/phong-hoc.php');
    }

    // 2. Đổi trạng thái hoạt động
    if ($hanhDong === 'doi_trang_thai') {
        $id = doc_so_nguyen('id', 0, $_POST);
        if ($id > 0 && phong_hoc_doi_trang_thai($id)) {
            dat_thong_bao('thanh_cong', 'Đã thay đổi trạng thái hoạt động của phòng học.');
        } else {
            dat_thong_bao('loi', 'Không thể đổi trạng thái phòng học.');
        }
        chuyen_huong('danh-muc/phong-hoc.php');
    }

    // 3. Xóa phòng học
    if ($hanhDong === 'xoa') {
        $id = doc_so_nguyen('id', 0, $_POST);
        $ketQua = phong_hoc_xoa($id);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã xóa phòng học thành công.');
        } else {
            dat_thong_bao('loi', $ketQua['loi']);
        }
        chuyen_huong('danh-muc/phong-hoc.php');
    }
}

// Tìm kiếm lọc
$tuKhoa = trim((string)($_GET['q'] ?? ''));
$danhSachPhongHoc = phong_hoc_danh_sach(false, $tuKhoa !== '' ? $tuKhoa : null);

// Nạp giao diện
require dirname(__DIR__) . '/app/views/danh-muc/phong-hoc.php';
