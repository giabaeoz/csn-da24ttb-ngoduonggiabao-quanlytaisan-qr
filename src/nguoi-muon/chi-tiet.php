<?php
/**
 * Bộ điều khiển Chi tiết Người mượn & Lịch sử Mượn trả (Borrower Detail Controller).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/nguoi-muon-service.php';

yeu_cau_quyen('quan_ly_nguoi_muon');

$id = doc_so_nguyen('id', 0, $_GET);
$nguoiMuon = nguoi_muon_tim_theo_id($id);

if ($nguoiMuon === null) {
    phan_hoi_loi_404('Không tìm thấy thông tin người mượn.');
}

// Lấy toàn bộ lịch sử các đợt mượn trả
$lichSuMuon = nguoi_muon_lich_su_muon($id);

$tieuDeTrang = 'Hồ sơ người mượn: ' . ($nguoiMuon['ho_ten'] ?? '');
require dirname(__DIR__) . '/app/views/nguoi-muon/chi-tiet.php';

