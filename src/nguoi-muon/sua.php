<?php
/**
 * Bộ điều khiển Chỉnh sửa Người mượn.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/nguoi-muon-service.php';

yeu_cau_quyen('quan_ly_nguoi_muon');

$id = doc_so_nguyen('id', 0, $_REQUEST);
$nguoiMuon = nguoi_muon_tim_theo_id($id);

if ($nguoiMuon === null) {
    phan_hoi_loi_404('Không tìm thấy người mượn yêu cầu chỉnh sửa.');
}

$thongBaoLoi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra()) {
        $thongBaoLoi = 'Phiên làm việc bảo mật đã hết hạn. Vui lòng thử lại.';
    } else {
        $duLieu = [
            'ma_nguoi_muon'   => (string)($_POST['ma_nguoi_muon'] ?? ''),
            'ma_so_truong'    => (string)($_POST['ma_so_truong'] ?? ''),
            'ho_ten'          => (string)($_POST['ho_ten'] ?? ''),
            'loai_nguoi_muon' => (string)($_POST['loai_nguoi_muon'] ?? 'SINH_VIEN'),
            'lop_don_vi'      => (string)($_POST['lop_don_vi'] ?? ''),
            'so_dien_thoai'   => (string)($_POST['so_dien_thoai'] ?? ''),
        ];

        $nguoiMuon = array_merge($nguoiMuon, $duLieu);

        $ketQua = nguoi_muon_luu($duLieu, $id);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã cập nhật hồ sơ người mượn thành công!');
            chuyen_huong('nguoi-muon/index.php');
        } else {
            $thongBaoLoi = $ketQua['loi'];
        }
    }
}

$laThemMoi = false;
$tieuDeTrang = 'Chỉnh sửa người mượn: ' . ($nguoiMuon['ho_ten'] ?? '');
require dirname(__DIR__) . '/app/views/nguoi-muon/form.php';

