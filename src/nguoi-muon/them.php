<?php
/**
 * Bộ điều khiển Thêm mới Người mượn.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/nguoi-muon-service.php';

yeu_cau_quyen('quan_ly_nguoi_muon');

$thongBaoLoi = '';
$nguoiMuon = [
    'id'              => 0,
    'ma_nguoi_muon'   => '',
    'ma_so_truong'    => '',
    'ho_ten'          => '',
    'loai_nguoi_muon' => 'SINH_VIEN',
    'lop_don_vi'      => '',
    'so_dien_thoai'   => '',
];

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

        $ketQua = nguoi_muon_luu($duLieu, null);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', 'Đã thêm hồ sơ người mượn mới thành công!');
            chuyen_huong('nguoi-muon/index.php');
        } else {
            $thongBaoLoi = $ketQua['loi'];
        }
    }
}

$laThemMoi = true;
$tieuDeTrang = 'Thêm mới người mượn';
require dirname(__DIR__) . '/app/views/nguoi-muon/form.php';

