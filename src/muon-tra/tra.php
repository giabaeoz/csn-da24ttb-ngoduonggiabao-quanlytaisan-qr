<?php
/**
 * Bộ điều khiển Tiếp nhận Trả tài sản & Báo cáo sự cố (Asset Return Controller).
 * Hỗ trợ quét mã QR phiếu/thiết bị, tra cứu mã tay, trả một phần hoặc toàn bộ.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/muon-tra-service.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

// Yêu cầu quyền thực hiện nghiệp vụ mượn - trả
yeu_cau_quyen('nghiep_vu_muon_tra');

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);
if ($phongTrucId <= 0 && ($nguoiDung['vai_tro'] ?? '') === VAI_TRO_QUAN_TRI) {
    $phongTrucId = doc_so_nguyen('phong_truc_id', 1, $_REQUEST);
}

if ($phongTrucId <= 0) {
    dat_thong_bao('loi', 'Tài khoản của bạn chưa được phân công phòng trực cụ thể.');
    chuyen_huong('tong-quan.php');
}

$phongTrucHienTai = phong_truc_tim_theo_id($phongTrucId);

// Kiểm tra đợt kiểm kê đang diễn ra
$dangBiKhoaKiemKe = ($phongTrucId > 0) ? muon_kiem_tra_khoa_kiem_ke($phongTrucId) : false;

$thongBaoLoi = '';
$thongBaoLoiTimKiem = '';
$phieuHienTai = null;

// Xử lý gửi biểu mẫu tiếp nhận trả (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_kiem_tra()) {
        $thongBaoLoi = 'Phiên làm việc bảo mật đã hết hạn. Vui lòng tải lại trang và thử lại.';
    } elseif ($dangBiKhoaKiemKe) {
        $thongBaoLoi = 'Phòng trực hiện đang diễn ra đợt kiểm kê tài sản. Hoạt động nhận trả tạm thời bị khóa.';
    } else {
        $ketQua = tra_thuc_hien($_POST, (int)$nguoiDung['id'], $phongTrucId);

        if ($ketQua['thanh_cong']) {
            $maPhieu = $ketQua['ma_phieu'];
            $soDaTra = $ketQua['so_da_tra'];
            $conLai = $ketQua['con_lai'];

            if ($ketQua['hoan_tat']) {
                dat_thong_bao('thanh_cong', "Tiếp nhận trả thành công <strong>$soDaTra</strong> thiết bị. Phiếu mượn <strong>$maPhieu</strong> đã hoàn tất trả đủ!");
                chuyen_huong('muon-tra/chi-tiet.php?id=' . (int)($_POST['phieu_muon_id'] ?? 0));
            } else {
                dat_thong_bao('thanh_cong', "Đã tiếp nhận trả <strong>$soDaTra</strong> thiết bị. Phiếu mượn <strong>$maPhieu</strong> hiện còn <strong>$conLai</strong> thiết bị chưa trả.");
                chuyen_huong('muon-tra/tra.php?id=' . (int)($_POST['phieu_muon_id'] ?? 0));
            }
        } else {
            $thongBaoLoi = $ketQua['loi'];
            // Tải lại phiếu để người dùng tiếp tục thao tác
            $phieuHienTai = muon_tim_theo_id((int)($_POST['phieu_muon_id'] ?? 0));
        }
    }
}

// Xử lý tra cứu phiếu mượn (GET)
$idCanTim = doc_so_nguyen('id', 0, $_GET);
$maPhieuCanTim = trim((string)($_GET['ma_phieu'] ?? ''));
$tuKhoaCanTim = trim((string)($_GET['tu_khoa'] ?? $_GET['ma_qr'] ?? ''));

if ($idCanTim > 0 && $phieuHienTai === null) {
    $phieuHienTai = muon_tim_theo_id($idCanTim);
    if ($phieuHienTai === null) {
        $thongBaoLoiTimKiem = "Không tìm thấy phiếu mượn có ID #$idCanTim trong hệ thống.";
    } elseif ($phieuHienTai['trang_thai'] !== PHIEU_MUON_DANG_MUON) {
        dat_thong_bao('thong_tin', "Phiếu mượn <strong>{$phieuHienTai['ma_phieu']}</strong> đã được trả đủ từ trước.");
    }
} elseif ($maPhieuCanTim !== '' && $phieuHienTai === null) {
    $phieuHienTai = tra_tim_phieu_theo_tu_khoa($maPhieuCanTim, $phongTrucId > 0 ? $phongTrucId : null);
    if ($phieuHienTai === null) {
        $thongBaoLoiTimKiem = "Không tìm thấy phiếu mượn '$maPhieuCanTim' hoặc phiếu này đã trả đủ.";
    }
} elseif ($tuKhoaCanTim !== '' && $phieuHienTai === null) {
    $phieuHienTai = tra_tim_phieu_theo_tu_khoa($tuKhoaCanTim, $phongTrucId > 0 ? $phongTrucId : null);
    if ($phieuHienTai === null) {
        $thongBaoLoiTimKiem = "Không tìm thấy phiếu mượn đang hoạt động nào khớp với từ khóa '$tuKhoaCanTim'.";
    }
}

// Lấy danh sách phiếu mượn đang chờ trả tại phòng trực để chọn nhanh
$danhSachChoTra = tra_danh_sach_cho_xu_ly($phongTrucId > 0 ? $phongTrucId : null, 10);

$tieuDeTrang = 'Tiếp nhận Trả tài sản & Báo cáo sự cố';
require dirname(__DIR__) . '/app/views/muon-tra/tra.php';
