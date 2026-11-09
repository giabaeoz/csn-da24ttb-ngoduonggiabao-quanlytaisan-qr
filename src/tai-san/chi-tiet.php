<?php
/**
 * Bộ điều khiển Chi tiết Tài sản & Xem Mã QR (Asset Detail Controller).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';

yeu_cau_quyen('xem_danh_sach_tai_san');

$id = doc_so_nguyen('id', 0, $_GET);
$taiSan = tai_san_tim_theo_id($id);

if ($taiSan === null) {
    phan_hoi_loi_404('Không tìm thấy thiết bị yêu cầu.');
}

$db = db();

// 1. Lấy lịch sử mượn trả gần nhất của tài sản này
$sqlMuon = "SELECT ctm.*, pm.ma_phieu, pm.thoi_gian_muon, pm.han_tra,
                   nm.ho_ten AS ten_nguoi_muon, nm.ma_so_truong,
                   ph.ten_phong AS ten_phong_hoc, ph.ma_phong AS ma_phong_hoc
            FROM chi_tiet_muon ctm
            JOIN phieu_muon pm ON ctm.phieu_muon_id = pm.id
            JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
            JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
            WHERE ctm.tai_san_id = ?
            ORDER BY pm.thoi_gian_muon DESC
            LIMIT 10";
$stmtMuon = $db->prepare($sqlMuon);
$stmtMuon->bind_param('i', $id);
$stmtMuon->execute();
$lichSuMuon = $stmtMuon->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtMuon->close();

// 2. Lấy danh sách sự cố đã phát sinh
$sqlSc = "SELECT sc.*, nv.ho_ten AS ten_nhan_vien_bao
          FROM su_co_tai_san sc
          LEFT JOIN nhan_vien nv ON sc.nhan_vien_bao_id = nv.id
          WHERE sc.tai_san_id = ?
          ORDER BY sc.thoi_gian_bao DESC";
$stmtSc = $db->prepare($sqlSc);
$stmtSc->bind_param('i', $id);
$stmtSc->execute();
$danhSachSuCo = $stmtSc->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtSc->close();

$tieuDeTrang = 'Chi tiết thiết bị: ' . ($taiSan['ten_tai_san'] ?? '');
require dirname(__DIR__) . '/app/views/tai-san/chi-tiet.php';

