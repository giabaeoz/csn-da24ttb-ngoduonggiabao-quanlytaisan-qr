<?php
/**
 * Controller: Tiếp nhận và hoàn tất xử lý sự cố tài sản (G9).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/su-co-service.php';

yeu_cau_dang_nhap();
yeu_cau_quyen('xu_ly_su_co');

if (!la_post()) {
    chuyen_huong('su-co/index.php');
}

if (!csrf_kiem_tra()) {
    dat_thong_bao('nguy_hiem', 'Phiên làm việc bảo mật đã hết hạn. Vui lòng thử lại.');
    chuyen_huong('su-co/index.php');
}

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);
$suCoId = doc_so_nguyen('su_co_id', 0, $_POST);
$hanhDong = trim((string)($_POST['hanh_dong'] ?? ''));

if ($suCoId <= 0) {
    dat_thong_bao('nguy_hiem', 'Mã sự cố không hợp lệ.');
    chuyen_huong('su-co/index.php');
}

$suCo = su_co_tim_theo_id($suCoId);
if (!$suCo) {
    dat_thong_bao('nguy_hiem', 'Không tìm thấy sự cố.');
    chuyen_huong('su-co/index.php');
}

if ($hanhDong === 'tiep_nhan') {
    $kq = su_co_chuyen_dang_xu_ly($suCoId, (int)$nguoiDung['id']);
    if ($kq['thanh_cong']) {
        dat_thong_bao('thanh_cong', "Đã tiếp nhận xử lý sự cố <strong>{$suCo['ma_su_co']}</strong>. Trạng thái chuyển sang Đang xử lý.");
    } else {
        dat_thong_bao('nguy_hiem', $kq['loi']);
    }
} elseif ($hanhDong === 'hoan_tat') {
    $kq = su_co_hoan_tat_xu_ly($suCoId, $_POST, (int)$nguoiDung['id']);
    if ($kq['thanh_cong']) {
        dat_thong_bao('thanh_cong', "Đã hoàn tất xử lý sự cố <strong>{$suCo['ma_su_co']}</strong> thành công!");
    } else {
        dat_thong_bao('nguy_hiem', $kq['loi']);
    }
} else {
    dat_thong_bao('nguy_hiem', 'Hành động xử lý không hợp lệ.');
}

$veTrang = trim((string)($_POST['ve_trang'] ?? ''));
if ($veTrang === 'danh_sach') {
    chuyen_huong('su-co/index.php');
}
chuyen_huong('su-co/chi-tiet.php?id=' . $suCoId);
