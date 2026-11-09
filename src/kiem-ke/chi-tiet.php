<?php
/**
 * Controller: Xem chi tiết báo cáo đợt kiểm kê tài sản và in biên bản kiểm kê.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/kiem-ke-service.php';

yeu_cau_dang_nhap();
yeu_cau_mot_trong_cac_quyen(['xem_kiem_ke', 'thuc_hien_kiem_ke', 'mo_dot_kiem_ke']);

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

$id = doc_so_nguyen('id', 0, $_GET);
$dot = kiem_ke_tim_theo_id($id, $phongTrucId > 0 ? $phongTrucId : null);

if ($dot === null) {
    dat_thong_bao('nguy_hiem', 'Không tìm thấy đợt kiểm kê theo yêu cầu.');
    chuyen_huong('kiem-ke/index.php');
}

$boLocChiTiet = [
    'tu_khoa'        => trim((string)($_GET['tu_khoa'] ?? '')),
    'trang_thai_loc' => trim((string)($_GET['trang_thai_loc'] ?? '')),
];
$danhSachChiTiet = kiem_ke_lay_chi_tiet_dot((int)$dot['id'], $boLocChiTiet);

$tieuDeTrang = 'Báo cáo kiểm kê: ' . $dot['ma_dot_kiem_ke'];
require dirname(__DIR__) . '/app/views/kiem-ke/chi-tiet.php';
