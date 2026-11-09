<?php
/**
 * Bộ kiểm thử tự động Module Quản lý Tài sản & Sinh mã QR (G4).
 * Chạy: C:\xampp\php\php.exe setup/tests/thu_tai_san.php
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/tai-san-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/danh-muc-service.php';
require_once __DIR__ . '/ho_tro.php';

echo "============================================================\n";
echo "  BÀI THỬ 5: QUẢN LÝ TÀI SẢN & ĐỘ TƯƠNG THÍCH MÃ QR (G4)\n";
echo "============================================================\n\n";

// 1. Kiểm thử lấy danh sách tài sản mẫu ban đầu
$resDs = tai_san_danh_sach([], 1, 20);
kiem_thu('Lấy danh sách tài sản mẫu thành công', is_array($resDs['danh_sach']) && $resDs['tong_so'] >= 15, 'Tổng số: ' . $resDs['tong_so']);

// 2. Tra cứu theo ID, Mã tài sản và Mã QR
$ts1 = tai_san_tim_theo_id(1);
kiem_thu('Tìm tài sản theo ID = 1', $ts1 !== null && $ts1['ma_tai_san'] === 'MC-0001');

$tsMa = tai_san_tim_theo_ma('MC-0001');
kiem_thu('Tìm tài sản theo Mã TS = MC-0001', $tsMa !== null && $tsMa['id'] == 1);

$tsQr = tai_san_tim_theo_qr('QR-D6-MC0001');
kiem_thu('Tìm tài sản theo Mã QR = QR-D6-MC0001', $tsQr !== null && $tsQr['id'] == 1);

// 3. Kiểm thử sinh mã QR chuẩn
$qrSinhRa = tai_san_sinh_ma_qr('MIC-9999', 'D6');
kiem_thu('Sinh mã QR chuẩn theo mẫu QR-[PHONG_TRUC]-[MA_TS]', $qrSinhRa === 'QR-D6-MIC9999', "Mã: $qrSinhRa");

// 4. Thêm tài sản mới có Hãng & Model kỹ thuật
$maTsTest = 'TEST_TS_' . rand(1000, 9999);
$resThem = tai_san_luu([
    'ma_tai_san'          => $maTsTest,
    'ten_tai_san'         => 'Remote Máy Lạnh Daikin Inverter Test',
    'loai_tai_san_id'     => 2, // Remote
    'phong_truc_id'       => 1, // D6
    'hang_san_xuat'       => 'Daikin',
    'model'               => 'FTKB25W',
    'phong_hoc_phu_hop_id'=> 1, // B31.101 (gợi ý vị trí tủ)
    'pham_vi_tuong_thich' => TUONG_THICH_THEO_THIET_BI,
    'ngay_dua_vao_su_dung'=> date('Y-m-d'),
    'mo_ta'               => 'Thiết bị tạo tự động để kiểm thử',
]);
kiem_thu('Thêm mới thiết bị kèm Hãng & Model thành công', $resThem['thanh_cong'] === true && $resThem['id'] > 0);
$idTsTest = $resThem['id'];

// 5. Kiểm tra dữ liệu vừa lưu trong CSDL
$tsMoi = tai_san_tim_theo_id($idTsTest);
kiem_thu(
    'Thông tin Hãng sản xuất và Model được lưu chính xác',
    $tsMoi !== null && $tsMoi['hang_san_xuat'] === 'Daikin' && $tsMoi['model'] === 'FTKB25W',
    "Hãng: {$tsMoi['hang_san_xuat']}, Model: {$tsMoi['model']}"
);

// 6. Chặn tạo trùng mã tài sản và mã QR
$resTrungMa = tai_san_luu([
    'ma_tai_san'      => $maTsTest,
    'ten_tai_san'     => 'Trùng Mã',
    'loai_tai_san_id' => 1,
    'phong_truc_id'   => 1,
]);
kiem_thu('Chặn tạo trùng Mã tài sản', $resTrungMa['thanh_cong'] === false);

$resTrungQr = tai_san_luu([
    'ma_tai_san'      => 'KHAC_' . rand(100, 999),
    'ma_qr'           => $tsMoi['ma_qr'],
    'ten_tai_san'     => 'Trùng QR',
    'loai_tai_san_id' => 1,
    'phong_truc_id'   => 1,
]);
kiem_thu('Chặn tạo trùng Mã QR', $resTrungQr['thanh_cong'] === false);

// 7. KIỂM THỬ THUẬT TOÁN TƯƠNG THÍCH PHÒNG HỌC (THEO ĐỀ BÀI)
$phongB31_101 = phong_hoc_tim_theo_id(1); // Có Máy lạnh Daikin FTKB25W
$phongD6_201  = phong_hoc_tim_theo_id(6); // Chỉ có Máy lạnh Panasonic CU-N9

// 7.1. Tương thích theo Hãng & Model: Thiết bị Daikin FTKB25W với phòng B31.101 (CÓ máy lạnh Daikin FTKB25W)
$kqTuongThichHopLe = tai_san_kiem_tra_tuong_thich($tsMoi, $phongB31_101);
kiem_thu('Tương thích Hãng/Model HỢP LỆ (Daikin FTKB25W với phòng B31.101)', $kqTuongThichHopLe['tuong_thich'] === true, $kqTuongThichHopLe['ly_do']);

// 7.2. Tương thích theo Hãng & Model: Thiết bị Daikin FTKB25W với phòng D6.201 (KHÔNG có Daikin)
$kqTuongThichKhongHopLe = tai_san_kiem_tra_tuong_thich($tsMoi, $phongD6_201);
kiem_thu('Từ chối tương thích khi phòng không có thiết bị cùng Hãng (Daikin với phòng D6.201)', $kqTuongThichKhongHopLe['tuong_thich'] === false, $kqTuongThichKhongHopLe['ly_do']);

// 7.3. Tương thích LINH HOẠT: Cáp HDMI hoặc Máy chiếu di động dùng được cho mọi phòng
$tsLinhHoat = tai_san_tim_theo_id(1); // MC-0001 linh hoạt
$kqLinhHoat = tai_san_kiem_tra_tuong_thich($tsLinhHoat, $phongD6_201);
kiem_thu('Thiết bị phạm vi LINH_HOAT tương thích với mọi phòng học', $kqLinhHoat['tuong_thich'] === true);

// 8. Cập nhật thông tin tài sản
$resSua = tai_san_luu([
    'ma_tai_san'          => $maTsTest,
    'ten_tai_san'         => 'Remote Máy Lạnh Daikin Inverter Đã Sửa',
    'loai_tai_san_id'     => 2,
    'phong_truc_id'       => 1,
    'hang_san_xuat'       => 'Daikin',
    'model'               => 'FTKB35W',
    'phong_hoc_phu_hop_id'=> 2, // B31.102
    'pham_vi_tuong_thich' => TUONG_THICH_THEO_THIET_BI,
], $idTsTest);
$tsDaSua = tai_san_tim_theo_id($idTsTest);
kiem_thu(
    'Cập nhật thông tin tài sản thành công',
    $resSua['thanh_cong'] === true && $tsDaSua['ten_tai_san'] === 'Remote Máy Lạnh Daikin Inverter Đã Sửa' && $tsDaSua['model'] === 'FTKB35W'
);

// 9. Thống kê nhanh số lượng tài sản
$thongKe = tai_san_thong_ke_nhanh();
kiem_thu(
    'Thống kê nhanh số lượng tài sản theo trạng thái',
    $thongKe['tong_so'] > 0 && $thongKe['san_sang'] > 0,
    "Tổng: {$thongKe['tong_so']}, Sẵn sàng: {$thongKe['san_sang']}, Mượn: {$thongKe['dang_muon']}"
);

// 10. Xóa an toàn tài sản thử nghiệm khi chưa có giao dịch
$resXoa = tai_san_xoa_hoac_ngung_su_dung($idTsTest);
kiem_thu('Xóa tài sản an toàn khi chưa phát sinh giao dịch mượn/chuyển/kiểm kê', $resXoa['thanh_cong'] === true && $resXoa['da_xoa_cung'] === true);

// Tổng kết
tong_ket_kiem_thu('Quản lý Tài sản (G4)');
