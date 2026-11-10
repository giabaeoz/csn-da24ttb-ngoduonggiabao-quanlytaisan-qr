<?php
/**
 * Controller: Bàn làm việc thực hiện đối chiếu kiểm kê tài sản trực tiếp (G8).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/kiem-ke-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_dang_nhap();
yeu_cau_mot_trong_cac_quyen(['thuc_hien_kiem_ke', 'mo_dot_kiem_ke', 'chot_kiem_ke']);

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

$id = doc_so_nguyen('id', 0, $_GET ?: $_POST);
$dot = kiem_ke_tim_theo_id($id, $phongTrucId > 0 ? $phongTrucId : null);

if ($dot === null) {
    dat_thong_bao('nguy_hiem', 'Không tìm thấy đợt kiểm kê hoặc bạn không có quyền truy cập.');
    chuyen_huong('kiem-ke/index.php');
}

// Nếu đợt đã hoàn tất hoặc đã hủy thì chuyển sang trang xem chi tiết
if ($dot['trang_thai'] !== KIEM_KE_DANG_KIEM_KE) {
    chuyen_huong('kiem-ke/chi-tiet.php?id=' . $dot['id']);
}

$thongBaoLoi = '';

// Xử lý các thao tác POST (Ghi nhận / Hoàn tất / Hủy)
if (la_post()) {
    if (!csrf_kiem_tra()) {
        $thongBaoLoi = 'Phiên làm việc bảo mật đã hết hạn. Vui lòng tải lại trang.';
        dat_thong_bao('nguy_hiem', $thongBaoLoi);
    } else {
        $hanhDong = trim((string)($_POST['hanh_dong'] ?? ''));

        if ($hanhDong === 'hoan_tat') {
            yeu_cau_quyen('chot_kiem_ke');
            $kq = kiem_ke_hoan_tat((int)$dot['id'], (int)$nguoiDung['id'], $phongTrucId);
            if ($kq['thanh_cong']) {
                dat_thong_bao('thanh_cong', "Chốt sổ đợt kiểm kê <strong>{$dot['ma_dot_kiem_ke']}</strong> thành công! Tổng số thiết bị: {$kq['tong_tai_san']}, số sự cố phát hiện và ghi nhận: {$kq['so_su_co']}.");
                chuyen_huong('kiem-ke/chi-tiet.php?id=' . $dot['id']);
            } else {
                $thongBaoLoi = $kq['loi'];
                dat_thong_bao('nguy_hiem', $thongBaoLoi);
            }
        } elseif ($hanhDong === 'huy') {
            $lyDoHuy = trim((string)($_POST['ly_do_huy'] ?? ''));
            $kq = kiem_ke_huy((int)$dot['id'], $lyDoHuy, (int)$nguoiDung['id'], $phongTrucId);
            if ($kq['thanh_cong']) {
                dat_thong_bao('canh_bao', "Đã hủy đợt kiểm kê <strong>{$dot['ma_dot_kiem_ke']}</strong> thành công.");
                chuyen_huong('kiem-ke/chi-tiet.php?id=' . $dot['id']);
            } else {
                $thongBaoLoi = $kq['loi'];
                dat_thong_bao('nguy_hiem', $thongBaoLoi);
            }
        } elseif ($hanhDong === 'ghi_nhan') {
            $duLieuGhiNhan = $_POST;
            $duLieuGhiNhan['dot_kiem_ke_id'] = (int)$dot['id'];
            $kq = kiem_ke_ghi_nhan($duLieuGhiNhan, (int)$nguoiDung['id'], $phongTrucId);
            if ($kq['thanh_cong']) {
                dat_thong_bao('thanh_cong', 'Đã lưu kết quả đối chiếu thiết bị.');
                chuyen_huong('kiem-ke/thuc-hien.php?id=' . $dot['id']);
            } else {
                $thongBaoLoi = $kq['loi'];
                dat_thong_bao('nguy_hiem', $thongBaoLoi);
            }
        }
    }
}

// Tải lại thông tin mới nhất và danh sách thiết bị
$dot = kiem_ke_tim_theo_id($id, $phongTrucId > 0 ? $phongTrucId : null);
$boLocChiTiet = [
    'tu_khoa'        => trim((string)($_GET['tu_khoa'] ?? '')),
    'trang_thai_loc' => trim((string)($_GET['trang_thai_loc'] ?? '')),
];
$danhSachChiTiet = kiem_ke_lay_chi_tiet_dot((int)$dot['id'], $boLocChiTiet);
$danhSachPhongHoc = phong_hoc_danh_sach(true);

$ketQuaGanDay = kiem_ke_danh_sach(['phong_truc_id' => $phongTrucId > 0 ? $phongTrucId : null], 1, 3);
$dotGanDay = $ketQuaGanDay['danh_sach'] ?? [];

$tieuDeTrang = 'Thực hiện kiểm kê: ' . $dot['ten_dot_kiem_ke'];
require dirname(__DIR__) . '/app/views/kiem-ke/thuc-hien.php';
