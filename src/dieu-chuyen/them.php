<?php
/**
 * Controller: Lập phiếu điều chuyển thiết bị mới giữa các phòng học.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/dieu-chuyen-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';

yeu_cau_dang_nhap();
yeu_cau_quyen('tao_dieu_chuyen');

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

// Kiểm tra đợt kiểm kê đang diễn ra tại phòng trực
$dangKiemKe = ($phongTrucId > 0 && dieu_chuyen_kiem_tra_khoa_kiem_ke($phongTrucId));

$danhSachPhongHoc = phong_hoc_danh_sach_tat_ca();
$loi = '';

$ketQuaGanDay = dieu_chuyen_danh_sach(['phong_truc_id' => $phongTrucId > 0 ? $phongTrucId : null], 1, 3);
$dieuChuyenGanDay = $ketQuaGanDay['danh_sach'] ?? [];

$duLieu = [
    'phong_hoc_di_id'       => doc_so_nguyen('phong_hoc_di_id', 0, $_POST ?: $_GET),
    'phong_hoc_den_id'      => doc_so_nguyen('phong_hoc_den_id', 0, $_POST ?: $_GET),
    'ly_do'                 => (string)($_POST['ly_do'] ?? ''),
    'ghi_chu'               => (string)($_POST['ghi_chu'] ?? ''),
    'xac_nhan_tuong_thich'  => (string)($_POST['xac_nhan_tuong_thich'] ?? ''),
    'tai_san_ids'           => $_POST['tai_san_ids'] ?? [],
];

if (la_post()) {
    kiem_tra_csrf();

    if ($dangKiemKe) {
        dat_thong_bao('nguy_hiem', 'Phòng trực hiện đang có đợt kiểm kê tài sản đang diễn ra. Hoạt động điều chuyển tạm thời bị khóa.');
        chuyen_huong('dieu-chuyen/index.php');
    }

    $ketQua = dieu_chuyen_tao($_POST, (int)$nguoiDung['id'], $phongTrucId > 0 ? $phongTrucId : null);

    if ($ketQua['thanh_cong']) {
        dat_thong_bao('thanh_cong', "Lập phiếu điều chuyển <strong>{$ketQua['ma_dieu_chuyen']}</strong> thành công! Vị trí các thiết bị đã được cập nhật sang phòng học mới.");
        chuyen_huong('dieu-chuyen/chi-tiet.php?id=' . $ketQua['id']);
    } else {
        $loi = $ketQua['loi'];
        dat_thong_bao('nguy_hiem', $loi);
    }
}

$tieuDeTrang = 'Lập phiếu điều chuyển thiết bị';
require dirname(__DIR__) . '/app/views/dieu-chuyen/form.php';

