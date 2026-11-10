<?php
/**
 * API JSON Tìm kiếm và tra cứu thông tin Người mượn.
 * Hỗ trợ giao diện mượn thiết bị bằng mã QR tự động điền hoặc gợi ý nhanh.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/nguoi-muon-service.php';

// Yêu cầu quyền xem/quản lý người mượn hoặc thực hiện nghiệp vụ mượn trả
yeu_cau_mot_trong_cac_quyen(['quan_ly_nguoi_muon', 'nghiep_vu_muon_tra']);

$maSoTruong = trim((string)($_GET['ma_so_truong'] ?? ''));
$tuKhoa = trim((string)($_GET['tu_khoa'] ?? ''));

// 1. Tìm chính xác theo mã số trường (MSSV / MSGV)
if ($maSoTruong !== '') {
    $nguoiMuon = nguoi_muon_tim_theo_ma_so_truong($maSoTruong);
    if ($nguoiMuon !== null) {
        phan_hoi_json([
            'thanh_cong' => true,
            'du_lieu'    => $nguoiMuon,
        ]);
    } else {
        phan_hoi_json([
            'thanh_cong' => false,
            'loi'        => 'Không tìm thấy hồ sơ người mượn với mã số này.',
        ], 404);
    }
}

// 2. Tìm kiếm nhanh theo từ khóa (gợi ý tự động)
$gioiHan = max(1, min(30, doc_so_nguyen('limit', 10, $_GET)));
$danhSach = nguoi_muon_tim_kiem_nhanh($tuKhoa, $gioiHan);

phan_hoi_json([
    'thanh_cong' => true,
    'so_luong'   => count($danhSach),
    'du_lieu'    => $danhSach,
]);

