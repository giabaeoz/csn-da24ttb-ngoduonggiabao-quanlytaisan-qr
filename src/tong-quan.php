<?php
/**
 * Bộ điều khiển Bảng điều khiển Tổng quan (Dashboard Controller) - Bám sát 100% Mockup thiết kế
 */

declare(strict_types=1);

require_once __DIR__ . '/app/core/khoi-dong.php';
require_once __DIR__ . '/app/services/thong-ke-service.php';
require_once __DIR__ . '/app/services/muon-tra-service.php';

// 1. Kiểm tra phiên đăng nhập & phân quyền xem tổng quan
$nguoiDung = yeu_cau_dang_nhap();
yeu_cau_quyen('xem_tong_quan');

$db = db();

// 2. Đọc tham số tuần (0 = tuần này, 1 = tuần trước, ..., tối đa 5)
$tuanRaw = (int)($_GET['tuan'] ?? 0);
$tuan = max(0, min(5, $tuanRaw));

// 3. Chuẩn bị danh sách các tuần cho hộp chọn tuần (Dropdown)
$tz = new DateTimeZone('Asia/Ho_Chi_Minh');
$now = new DateTime('now', $tz);
$thuHienTai = (int)$now->format('N');

$danhSachTuan = [];
for ($i = 0; $i <= 5; $i++) {
    $thuHaiTuan = clone $now;
    $thuHaiTuan->modify('-' . ($thuHienTai - 1) . ' days')->setTime(0, 0, 0);
    if ($i > 0) {
        $thuHaiTuan->modify('-' . ($i * 7) . ' days');
    }
    $chuNhatTuan = clone $thuHaiTuan;
    $chuNhatTuan->modify('+6 days');

    $nhanTuan = match ($i) {
        0 => 'Tuần này',
        1 => 'Tuần trước',
        default => $thuHaiTuan->format('d/m') . ' – ' . $chuNhatTuan->format('d/m'),
    };

    $danhSachTuan[] = [
        'gia_tri' => $i,
        'nhan'    => $nhanTuan,
        'dang_chon' => ($i === $tuan),
    ];
}

// 4. Lấy dữ liệu nghiệp vụ chuẩn từ service
$soLieu = tong_quan_so_lieu($db, $nguoiDung);
$xuHuong = tong_quan_xu_huong_tuan($db, $nguoiDung, $tuan);
$theoNhom = tong_quan_theo_nhom($db, $nguoiDung, $tuan);
$canTheoDoi = tong_quan_can_theo_doi($db, $nguoiDung, 5);
$bieuDoAdmin = (($nguoiDung['vai_tro'] ?? '') === 'QUAN_TRI') ? tong_quan_bieu_do_admin($db) : null;

// 5. Kiểm tra quyền thực hiện thao tác nhanh (chỉ hiện cho cán bộ trực bàn)
$coQuyenThaoTacNhanh = co_quyen('nghiep_vu_muon_tra') && (($nguoiDung['vai_tro'] ?? '') === 'NHAN_VIEN');

// 6. Tính toán tỷ lệ phần trăm cho biểu đồ tình trạng tài sản
$tongTs = max(1, $soLieu['tong_tai_san']);
$tyLeCoTheMuon = round(($soLieu['co_the_muon'] / $tongTs) * 100, 1);
$tyLeDangMuon = round(($soLieu['dang_muon'] / $tongTs) * 100, 1);
$tyLeKhac = round(max(0, 100 - $tyLeCoTheMuon - $tyLeDangMuon), 1);

$tieuDeTrang = 'Tổng quan';
require __DIR__ . '/app/views/tong-quan.php';
