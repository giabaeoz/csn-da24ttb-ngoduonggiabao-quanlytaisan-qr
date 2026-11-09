<?php
/**
 * Controller: Chi tiết sự cố tài sản & tiếp nhận / giải quyết sự cố (G9).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/su-co-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_dang_nhap();
yeu_cau_mot_trong_cac_quyen(['xem_su_co', 'ghi_nhan_su_co', 'xu_ly_su_co']);

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

$id = doc_so_nguyen('id', 0, $_GET ?: $_POST);
$suCo = su_co_tim_theo_id($id, ($phongTrucId > 0 && $nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN) ? $phongTrucId : null);

if ($suCo === null) {
    dat_thong_bao('nguy_hiem', 'Không tìm thấy thông tin sự cố theo yêu cầu.');
    chuyen_huong('su-co/index.php');
}

// Xử lý POST hành động giải quyết sự cố (nếu có quyền xu_ly_su_co)
if (la_post()) {
    yeu_cau_quyen('xu_ly_su_co');

    if (!csrf_kiem_tra()) {
        dat_thong_bao('nguy_hiem', 'Phiên làm việc bảo mật đã hết hạn. Vui lòng thử lại.');
    } else {
        $hanhDong = trim((string)($_POST['hanh_dong'] ?? ''));

        if ($hanhDong === 'tiep_nhan') {
            $kq = su_co_chuyen_dang_xu_ly((int)$suCo['id'], (int)$nguoiDung['id']);
            if ($kq['thanh_cong']) {
                dat_thong_bao('thanh_cong', "Đã tiếp nhận xử lý sự cố <strong>{$suCo['ma_su_co']}</strong>. Trạng thái chuyển sang Đang xử lý.");
                chuyen_huong('su-co/chi-tiet.php?id=' . $suCo['id']);
            } else {
                dat_thong_bao('nguy_hiem', $kq['loi']);
            }
        } elseif ($hanhDong === 'hoan_tat') {
            $kq = su_co_hoan_tat_xu_ly((int)$suCo['id'], $_POST, (int)$nguoiDung['id']);
            if ($kq['thanh_cong']) {
                dat_thong_bao('thanh_cong', "Đã hoàn tất xử lý sự cố <strong>{$suCo['ma_su_co']}</strong> thành công! Trạng thái tài sản đã được cập nhật.");
                chuyen_huong('su-co/chi-tiet.php?id=' . $suCo['id']);
            } else {
                dat_thong_bao('nguy_hiem', $kq['loi']);
            }
        }
    }
}

// Tải lại thông tin mới nhất
$suCo = su_co_tim_theo_id($id);
$danhSachPhongHoc = phong_hoc_danh_sach(true);

$tieuDeTrang = 'Chi tiết sự cố: ' . $suCo['ma_su_co'];
require dirname(__DIR__) . '/app/views/su-co/chi-tiet.php';
