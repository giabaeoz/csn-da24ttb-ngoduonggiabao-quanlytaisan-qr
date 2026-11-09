<?php
/**
 * Bộ điều khiển và Giao diện In tem Mã QR Tài sản (Offline QR Code Print).
 * Hỗ trợ in đơn lẻ hoặc in hàng loạt nhiều tem chia lưới trên khổ giấy A4.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';

yeu_cau_quyen('xem_danh_sach_tai_san');

$id = doc_so_nguyen('id', 0, $_GET);
$idsRaw = trim((string)($_GET['ids'] ?? ''));
$phongTrucId = doc_so_nguyen('phong_truc_id', 0, $_GET);

$danhSachIn = [];

if ($id > 0) {
    $ts = tai_san_tim_theo_id($id);
    if ($ts !== null) {
        $danhSachIn[] = $ts;
    }
} elseif ($idsRaw !== '') {
    $cacId = array_map('intval', explode(',', $idsRaw));
    foreach ($cacId as $i) {
        if ($i > 0) {
            $ts = tai_san_tim_theo_id($i);
            if ($ts !== null) {
                $danhSachIn[] = $ts;
            }
        }
    }
} elseif ($phongTrucId > 0) {
    $res = tai_san_danh_sach(['phong_truc_id' => $phongTrucId], 1, 100);
    $danhSachIn = $res['danh_sach'];
}

if (empty($danhSachIn)) {
    phan_hoi_loi_404('Không có tài sản nào được chọn để in tem mã QR.');
}

$tieuDeTrang = 'In tem mã QR (' . count($danhSachIn) . ' thiết bị)';
require dirname(__DIR__) . '/app/views/tai-san/in-qr.php';

