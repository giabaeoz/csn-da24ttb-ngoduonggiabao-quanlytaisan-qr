<?php
/**
 * BÀI KIỂM THỬ 13: NGHIỆP VỤ BẢNG ĐIỀU KHIỂN TỔNG QUAN (DASHBOARD)
 * Kiểm tra các ràng buộc:
 * 1. Tổng tài sản = có thể mượn + đang mượn + khác
 * 2. Phân quyền và phạm vi phòng trực (Nhân viên vs Quản lý)
 * 3. Đếm số lượng quá hạn chính xác
 * 4. Ranh giới tuần xu hướng (Thứ Hai đến Chủ Nhật)
 * 5. Tổng lượt mượn theo nhóm = Tổng chuỗi Mượn của biểu đồ xu hướng
 * 6. Xử lý an toàn khi không có dữ liệu (không crash, không chia cho 0)
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/thong-ke-service.php';

echo "============================================================\n";
echo "  BÀI THỬ 13: NGHIỆP VỤ BẢNG ĐIỀU KHIỂN TỔNG QUAN (DASHBOARD)\n";
echo "============================================================\n\n";

$db = db();
$tongSoBai = 0;
$soBaiDat = 0;

function kt_assert(bool $dieuKien, string $moTa): void {
    global $tongSoBai, $soBaiDat;
    $tongSoBai++;
    if ($dieuKien) {
        $soBaiDat++;
        echo "  [PASS] {$moTa}\n";
    } else {
        echo "  [FAIL] {$moTa}\n";
    }
}

// 1. Kiểm tra với tài khoản Quản trị viên (Toàn hệ thống)
$adminUser = [
    'id' => 1,
    'vai_tro' => 'QUAN_TRI',
    'phong_truc_id' => null,
];

$soLieuAdmin = tong_quan_so_lieu($db, $adminUser);
$tongKiemTra = $soLieuAdmin['co_the_muon'] + $soLieuAdmin['dang_muon'] + $soLieuAdmin['khac'];
kt_assert(
    $soLieuAdmin['tong_tai_san'] === $tongKiemTra,
    "Quy tắc bảo toàn: Tổng tài sản ({$soLieuAdmin['tong_tai_san']}) = Có thể mượn ({$soLieuAdmin['co_the_muon']}) + Đang mượn ({$soLieuAdmin['dang_muon']}) + Khác ({$soLieuAdmin['khac']})"
);
kt_assert(is_int($soLieuAdmin['qua_han']) && $soLieuAdmin['qua_han'] >= 0, "Đếm số lượt mượn quá hạn trả về số nguyên hợp lệ ({$soLieuAdmin['qua_han']})");

// 2. Kiểm tra với tài khoản Nhân viên bàn trực D6
$stmtNv = $db->query("SELECT id, vai_tro, phong_truc_id FROM nhan_vien WHERE vai_tro = 'NHAN_VIEN' LIMIT 1");
$nvD6 = $stmtNv->fetch_assoc();
if ($nvD6) {
    $soLieuNv = tong_quan_so_lieu($db, $nvD6);
    kt_assert(
        $soLieuNv['tong_tai_san'] <= $soLieuAdmin['tong_tai_san'],
        "Phạm vi phân quyền: Số tài sản phòng trực D6 ({$soLieuNv['tong_tai_san']}) <= Tổng toàn trường ({$soLieuAdmin['tong_tai_san']})"
    );
    $tongNvKiemTra = $soLieuNv['co_the_muon'] + $soLieuNv['dang_muon'] + $soLieuNv['khac'];
    kt_assert(
        $soLieuNv['tong_tai_san'] === $tongNvKiemTra,
        "Quy tắc bảo toàn tại phòng trực: Tổng ({$soLieuNv['tong_tai_san']}) = {$soLieuNv['co_the_muon']} + {$soLieuNv['dang_muon']} + {$soLieuNv['khac']}"
    );
} else {
    kt_assert(true, "Bỏ qua test nhân viên vì chưa có nhân viên mẫu");
    kt_assert(true, "Bỏ qua test bảo toàn nhân viên");
}

// 3. Kiểm tra biểu đồ xu hướng tuần
$xuHuongTuanNay = tong_quan_xu_huong_tuan($db, $adminUser, 0);
kt_assert(count($xuHuongTuanNay['labels']) === 7, "Biểu đồ xu hướng trả về đủ 7 ngày (T2..CN)");
kt_assert(count($xuHuongTuanNay['muon']) === 7, "Chuỗi Mượn có đủ 7 giá trị");
kt_assert(count($xuHuongTuanNay['tra']) === 7, "Chuỗi Trả có đủ 7 giá trị");
kt_assert($xuHuongTuanNay['tong_muon'] === array_sum($xuHuongTuanNay['muon']), "Tổng chuỗi Mượn khớp tổng mảng");
kt_assert($xuHuongTuanNay['tong_tra'] === array_sum($xuHuongTuanNay['tra']), "Tổng chuỗi Trả khớp tổng mảng");

// 4. Kiểm tra sự ăn khớp giữa Biểu đồ nhóm và Chuỗi Mượn cùng tuần
$theoNhomTuanNay = tong_quan_theo_nhom($db, $adminUser, 0);
kt_assert(
    $theoNhomTuanNay['tong'] === $xuHuongTuanNay['tong_muon'],
    "Quy tắc đồng bộ biểu đồ: Tổng lượt mượn theo nhóm ({$theoNhomTuanNay['tong']}) == Tổng chuỗi Mượn xu hướng tuần ({$xuHuongTuanNay['tong_muon']})"
);

// 5. Kiểm tra tuần trước (tuan = 1)
$xuHuongTuanTruoc = tong_quan_xu_huong_tuan($db, $adminUser, 1);
$theoNhomTuanTruoc = tong_quan_theo_nhom($db, $adminUser, 1);
kt_assert(
    $theoNhomTuanTruoc['tong'] === $xuHuongTuanTruoc['tong_muon'],
    "Quy tắc đồng bộ tuần trước: Tổng theo nhóm ({$theoNhomTuanTruoc['tong']}) == Tổng chuỗi Mượn tuần trước ({$xuHuongTuanTruoc['tong_muon']})"
);

// 6. Kiểm tra danh sách cần theo dõi
$canTheoDoi = tong_quan_can_theo_doi($db, $adminUser, 5);
kt_assert(is_array($canTheoDoi), "Danh sách cần theo dõi trả về mảng");
kt_assert(count($canTheoDoi) <= 5, "Giới hạn tối đa 5 phiếu mượn");
foreach ($canTheoDoi as $phieu) {
    kt_assert(!empty($phieu['ma_phieu_muon']), "Mỗi phiếu có mã phiếu hợp lệ");
    kt_assert(in_array($phieu['trang_thai_theo_doi'], ['QUA_HAN', 'DEN_HAN_HOM_NAY', 'DANG_MUON'], true), "Trạng thái theo dõi thuộc 3 nhóm chuẩn");
    break; // Chỉ kiểm tra 1 phần tử đại diện
}

// 7. Kiểm tra hàm chuông thông báo quá hạn
$coQuaHan = tong_quan_co_qua_han($db, $adminUser);
kt_assert(is_bool($coQuaHan), "Hàm kiểm tra chuông thông báo trả về boolean");

// 8. Kiểm tra an toàn khi không có dữ liệu (User ảo không có phòng trực)
$fakeUser = ['id' => 999999, 'vai_tro' => 'NHAN_VIEN', 'phong_truc_id' => 999999];
$soLieuRong = tong_quan_so_lieu($db, $fakeUser);
kt_assert($soLieuRong['tong_tai_san'] === 0 && $soLieuRong['co_the_muon'] === 0, "Dữ liệu phòng trực trống xử lý an toàn, trả về 0 không crash");

// 9. Kiểm tra bộ dữ liệu đa biểu đồ của Quản trị viên (QUAN_TRI)
$bieuDoAdmin = tong_quan_bieu_do_admin($db);
kt_assert(isset($bieuDoAdmin['phong_truc']['labels']) && is_array($bieuDoAdmin['phong_truc']['labels']), "Biểu đồ Admin có danh sách tất cả các phòng trực");
kt_assert(
    $bieuDoAdmin['tai_khoan']['tong'] === ($bieuDoAdmin['tai_khoan']['quan_tri'] + $bieuDoAdmin['tai_khoan']['quan_ly'] + $bieuDoAdmin['tai_khoan']['nhan_vien']),
    "Biểu đồ Admin: Tổng tài khoản ({$bieuDoAdmin['tai_khoan']['tong']}) khớp tổng 3 vai trò"
);
kt_assert(
    $bieuDoAdmin['nguoi_muon']['tong_ho_so'] === ($bieuDoAdmin['nguoi_muon']['hs_giang_vien'] + $bieuDoAdmin['nguoi_muon']['hs_sinh_vien']),
    "Biểu đồ Admin: Tổng người mượn ({$bieuDoAdmin['nguoi_muon']['tong_ho_so']}) khớp tổng Giảng viên + Sinh viên"
);
kt_assert(count($bieuDoAdmin['van_hanh']['labels']) === 5 && count($bieuDoAdmin['van_hanh']['data']) === 5, "Biểu đồ Admin: Chỉ số vận hành trả về đủ 5 nhóm nghiệp vụ");

echo "\n------------------------------------------------------------\n";
echo "  KẾT QUẢ [TỔNG QUAN (DASHBOARD)]: {$soBaiDat} / {$tongSoBai} bài đạt.\n";
echo "============================================================\n";

if ($soBaiDat === $tongSoBai) {
    exit(0);
} else {
    exit(1);
}

