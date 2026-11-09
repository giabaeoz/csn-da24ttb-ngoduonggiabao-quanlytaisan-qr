<?php
/**
 * Bộ kiểm thử tự động Module Quản lý Danh mục (Desk, Classroom, Asset Category).
 * Chạy: C:\xampp\php\php.exe setup/tests/thu_danh_muc.php
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/danh-muc-service.php';
require_once __DIR__ . '/ho_tro.php';

echo "============================================================\n";
echo "  BÀI THỬ 3: QUẢN LÝ DANH MỤC PHÒNG TRỰC, PHÒNG HỌC, LOẠI TS\n";
echo "============================================================\n\n";

// -------------------------------------------------------------
// 1. KIỂM THỬ PHÒNG TRỰC
// -------------------------------------------------------------
$dsPt = phong_truc_danh_sach();
kiem_thu('Lấy danh sách phòng trực thành công', is_array($dsPt) && count($dsPt) >= 3, 'Số phòng: ' . count($dsPt));

// Thêm phòng trực mới thử nghiệm
$maTestPt = 'TEST_PT_' . rand(100, 999);
$resThemPt = phong_truc_luu([
    'ma_phong_truc'  => $maTestPt,
    'ten_phong_truc' => 'Phòng trực kiểm thử tự động',
    'vi_tri'         => 'Tầng 1 Nhà Test',
    'dang_hoat_dong' => 1,
]);
kiem_thu('Thêm mới phòng trực thành công', $resThemPt['thanh_cong'] === true && $resThemPt['id'] > 0);
$idPtTest = $resThemPt['id'];

// Kiểm tra chặn trùng mã phòng trực
$resTrungPt = phong_truc_luu([
    'ma_phong_truc'  => $maTestPt,
    'ten_phong_truc' => 'Phòng trực trùng lặp',
]);
kiem_thu('Chặn tạo trùng mã phòng trực thành công', $resTrungPt['thanh_cong'] === false);

// Đổi trạng thái phòng trực
$okDoiPt = phong_truc_doi_trang_thai($idPtTest);
$ptCapNhat = phong_truc_tim_theo_id($idPtTest);
kiem_thu('Đổi trạng thái phòng trực hoạt động/tắt', $okDoiPt && (int)$ptCapNhat['dang_hoat_dong'] === 0);

// Xóa phòng trực vừa tạo (chưa có tài sản hay nhân viên liên kết)
$resXoaPt = phong_truc_xoa($idPtTest);
kiem_thu('Xóa phòng trực hợp lệ khi không có dữ liệu liên kết', $resXoaPt['thanh_cong'] === true);

// Kiểm tra chặn xóa phòng trực đang có nhân viên / tài sản (Phòng 1: D6)
$resXoaD6 = phong_truc_xoa(1);
kiem_thu('Chặn xóa phòng trực D6 do đang có nhân viên / tài sản', $resXoaD6['thanh_cong'] === false, $resXoaD6['loi']);

// -------------------------------------------------------------
// 2. KIỂM THỬ PHÒNG HỌC & CẤU HÌNH THIẾT BỊ HÃNG/MODEL
// -------------------------------------------------------------
$dsPh = phong_hoc_danh_sach();
kiem_thu('Lấy danh sách phòng học thành công', is_array($dsPh) && count($dsPh) >= 6, 'Số phòng: ' . count($dsPh));

// Thêm phòng học có cấu hình thiết bị cố định (JSON hãng/model)
$maTestPh = 'TEST_PH_' . rand(100, 999);
$resThemPh = phong_hoc_luu([
    'ma_phong'       => $maTestPh,
    'ten_phong'      => 'Phòng thí nghiệm IoT Test',
    'toa_nha'        => 'B99',
    'tang'           => '3',
    'thiet_bi_phong' => [
        ['ten' => 'Máy lạnh', 'hang' => 'Daikin', 'model' => 'FTKB35W'],
        ['ten' => 'Máy chiếu', 'hang' => 'Sony', 'model' => 'VPL-EX575'],
        ['ten' => 'Dàn âm thanh', 'hang' => 'Soundking', 'model' => 'SK-12'],
    ],
    'dang_hoat_dong' => 1,
]);
kiem_thu('Thêm phòng học kèm cấu hình thiết bị hãng/model thành công', $resThemPh['thanh_cong'] === true && $resThemPh['id'] > 0);
$idPhTest = $resThemPh['id'];

// Kiểm tra cấu hình JSON được lưu và giải mã chính xác
$phMoi = phong_hoc_tim_theo_id($idPhTest);
$tbPhong = json_decode($phMoi['thiet_bi_phong'] ?? '[]', true);
kiem_thu(
    'Cấu hình thiết bị cố định được lưu chuẩn JSON trong CSDL',
    is_array($tbPhong) && count($tbPhong) === 3 && $tbPhong[0]['hang'] === 'Daikin' && $tbPhong[1]['hang'] === 'Sony',
    'Hãng đã lưu: ' . implode(', ', array_column($tbPhong, 'hang'))
);

// Kiểm tra chặn trùng mã phòng học
$resTrungPh = phong_hoc_luu([
    'ma_phong'  => $maTestPh,
    'ten_phong' => 'Phòng học trùng mã',
]);
kiem_thu('Chặn tạo trùng mã phòng học', $resTrungPh['thanh_cong'] === false);

// Xóa phòng học test
$resXoaPh = phong_hoc_xoa($idPhTest);
kiem_thu('Xóa phòng học test khi chưa có tài sản liên kết', $resXoaPh['thanh_cong'] === true);

// Kiểm tra chặn xóa phòng học B31.101 (đang có thiết bị gán nhãn)
$resXoaB31 = phong_hoc_xoa(1);
kiem_thu('Chặn xóa phòng học B31.101 do đang liên kết tài sản', $resXoaB31['thanh_cong'] === false, $resXoaB31['loi']);

// -------------------------------------------------------------
// 3. KIỂM THỬ CHỦNG LOẠI TÀI SẢN
// -------------------------------------------------------------
$dsLoai = loai_tai_san_danh_sach();
kiem_thu('Lấy danh sách loại tài sản thành công', is_array($dsLoai) && count($dsLoai) >= 6, 'Số loại: ' . count($dsLoai));

// Thêm loại tài sản mới
$maTestLoai = 'TEST_' . rand(10, 99);
$resThemLoai = loai_tai_san_luu([
    'ma_loai'        => $maTestLoai,
    'ten_loai'       => 'Thiết bị thí nghiệm VR/AR',
    'mo_ta'          => 'Kính thực tế ảo Meta Quest 3',
    'dang_hoat_dong' => 1,
]);
kiem_thu('Thêm mới loại tài sản thành công', $resThemLoai['thanh_cong'] === true && $resThemLoai['id'] > 0);
$idLoaiTest = $resThemLoai['id'];

// Chặn tạo trùng mã loại
$resTrungLoai = loai_tai_san_luu([
    'ma_loai'  => $maTestLoai,
    'ten_loai' => 'Loại tài sản trùng',
]);
kiem_thu('Chặn tạo trùng mã loại tài sản', $resTrungLoai['thanh_cong'] === false);

// Xóa loại tài sản vừa tạo
$resXoaLoai = loai_tai_san_xoa($idLoaiTest);
kiem_thu('Xóa loại tài sản an toàn khi không có thiết bị', $resXoaLoai['thanh_cong'] === true);

// Chặn xóa loại tài sản 1 (Máy chiếu di động đang có tài sản)
$resXoaLoaiMC = loai_tai_san_xoa(1);
kiem_thu('Chặn xóa loại Máy chiếu do đang có tài sản liên kết', $resXoaLoaiMC['thanh_cong'] === false, $resXoaLoaiMC['loi']);

// Tổng kết
tong_ket_kiem_thu('Quản lý Danh mục (G3)');
