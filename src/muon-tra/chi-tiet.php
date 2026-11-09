<?php
/**
 * Bộ điều khiển Chi tiết Phiếu mượn - trả (Loan Detail Controller).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/muon-tra-service.php';

yeu_cau_quyen('xem_lich_su_muon_tra');

$id = doc_so_nguyen('id', 0, $_GET);
$phieu = muon_tim_theo_id($id);

if ($phieu === null) {
    phan_hoi_loi_404('Không tìm thấy phiếu mượn yêu cầu.');
}

$tieuDeTrang = 'Chi tiết phiếu mượn: ' . ($phieu['ma_phieu'] ?? '');
require dirname(__DIR__) . '/app/views/muon-tra/chi-tiet.php';
