<?php
/**
 * Controller: Khởi tạo đợt kiểm kê tài sản mới cho phòng trực.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/kiem-ke-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_dang_nhap();
yeu_cau_quyen('mo_dot_kiem_ke');

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

// Nếu là quản trị viên chưa gán bàn trực, cho phép chọn bàn trực
$danhSachPhongTruc = phong_truc_danh_sach(true);
if ($phongTrucId <= 0 && $nguoiDung['vai_tro'] === VAI_TRO_QUAN_TRI) {
    $phongTrucId = doc_so_nguyen('phong_truc_id', (int)($danhSachPhongTruc[0]['id'] ?? 1), $_REQUEST);
}

if ($phongTrucId <= 0) {
    dat_thong_bao('loi', 'Tài khoản chưa được phân công phụ trách phòng trực nào.');
    chuyen_huong('kiem-ke/index.php');
}

// Kiểm tra xem phòng trực hiện có đợt đang mở không
$dotDangMo = kiem_ke_tim_dot_dang_mo($phongTrucId);
if ($dotDangMo !== null) {
    dat_thong_bao('thong_tin', "Phòng trực hiện đã có đợt kiểm kê <strong>{$dotDangMo['ma_dot_kiem_ke']}</strong> đang thực hiện. Đang chuyển đến đợt kiểm kê.");
    chuyen_huong('kiem-ke/thuc-hien.php?id=' . $dotDangMo['id']);
}

$loi = '';
$duLieu = [
    'ten_dot_kiem_ke' => (string)($_POST['ten_dot_kiem_ke'] ?? ('Kiểm kê tài sản ngày ' . date('d/m/Y'))),
    'loai_kiem_ke'    => (string)($_POST['loai_kiem_ke'] ?? LOAI_KIEM_KE_CUOI_BUOI),
    'ghi_chu'         => (string)($_POST['ghi_chu'] ?? ''),
];

if (la_post()) {
    if (!csrf_kiem_tra()) {
        $loi = 'Phiên làm việc bảo mật đã hết hạn. Vui lòng thử lại.';
        dat_thong_bao('nguy_hiem', $loi);
    } else {
        $ketQua = kiem_ke_mo_dot($_POST, (int)$nguoiDung['id'], $phongTrucId);
        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', "Mở đợt kiểm kê <strong>{$ketQua['ma_dot_kiem_ke']}</strong> thành công! Đã chụp sổ sách toàn bộ tài sản bàn trực.");
            chuyen_huong('kiem-ke/thuc-hien.php?id=' . $ketQua['id']);
        } else {
            $loi = $ketQua['loi'];
            dat_thong_bao('nguy_hiem', $loi);
        }
    }
}

$tieuDeTrang = 'Mở đợt kiểm kê tài sản mới';
require dirname(__DIR__) . '/app/views/kiem-ke/form.php';
