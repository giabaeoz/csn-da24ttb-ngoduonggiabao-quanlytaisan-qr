<?php
/**
 * Controller: Xem chi tiết phiếu điều chuyển và in biên bản bàn giao vị trí.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/dieu-chuyen-service.php';

yeu_cau_dang_nhap();
yeu_cau_mot_trong_cac_quyen(['xem_dieu_chuyen', 'tao_dieu_chuyen']);

$id = doc_so_nguyen('id', 0, $_GET);
$maDieuChuyen = trim((string)($_GET['ma'] ?? ''));

$phieu = null;
if ($id > 0) {
    $phieu = dieu_chuyen_tim_theo_id($id);
} elseif ($maDieuChuyen !== '') {
    $phieu = dieu_chuyen_tim_theo_ma($maDieuChuyen);
}

if ($phieu === null) {
    dat_thong_bao('nguy_hiem', 'Không tìm thấy phiếu điều chuyển theo yêu cầu.');
    chuyen_huong('dieu-chuyen/index.php');
}

$tieuDeTrang = 'Chi tiết phiếu điều chuyển: ' . ($phieu['ma_dieu_chuyen'] ?? '');
require dirname(__DIR__) . '/app/views/dieu-chuyen/chi-tiet.php';

