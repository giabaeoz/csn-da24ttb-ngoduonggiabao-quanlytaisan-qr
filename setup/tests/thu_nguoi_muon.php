<?php
/**
 * Bộ kiểm thử tự động Module Quản lý Người mượn (Borrower Tests).
 * Chạy: C:\xampp\php\php.exe setup/tests/thu_nguoi_muon.php
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/nguoi-muon-service.php';
require_once __DIR__ . '/ho_tro.php';

echo "============================================================\n";
echo "  BÀI THỬ 4: QUẢN LÝ HỒ SƠ NGƯỜI MƯỢN & TRA CỨU NHANH (G3)\n";
echo "============================================================\n\n";

// 1. Kiểm thử lấy danh sách và phân trang
$resDs = nguoi_muon_danh_sach([], 1, 10);
kiem_thu('Lấy danh sách người mượn thành công', is_array($resDs['danh_sach']) && $resDs['tong_so'] >= 5, 'Tổng số: ' . $resDs['tong_so']);

// 2. Kiểm thử tìm theo mã số trường chính xác
$sv1 = nguoi_muon_tim_theo_ma_so_truong('110124001');
kiem_thu(
    'Tìm chính xác theo mã số trường (MSSV 110124001)',
    $sv1 !== null && $sv1['ho_ten'] === 'Nguyễn Văn An',
    'Tên tìm thấy: ' . ($sv1['ho_ten'] ?? 'Không tìm thấy')
);

// 3. Kiểm thử tìm kiếm nhanh theo từ khóa (API AJAX gợi ý)
// 3.1. Tìm theo số điện thoại
$timSdt = nguoi_muon_tim_kiem_nhanh('0912345671');
kiem_thu('Tìm kiếm nhanh theo số điện thoại', count($timSdt) >= 1 && $timSdt[0]['ma_so_truong'] === '110124001');

// 3.2. Tìm theo tên giảng viên
$timGv = nguoi_muon_tim_kiem_nhanh('Đan Thanh');
kiem_thu('Tìm kiếm nhanh theo họ tên giảng viên', count($timGv) >= 1 && $timGv[0]['ma_so_truong'] === 'GV001');

// 4. Thêm mới người mượn
$mssvTest = 'TEST_SV_' . rand(10000, 99999);
$sdtTest = '098' . rand(1000000, 9999999);
$resThem = nguoi_muon_luu([
    'ma_so_truong'    => $mssvTest,
    'ho_ten'          => 'Sinh Viên Kiểm Thử Tự Động',
    'loai_nguoi_muon' => 'SINH_VIEN',
    'lop_don_vi'      => 'DA21CNTT',
    'so_dien_thoai'   => $sdtTest,
]);
kiem_thu('Thêm mới hồ sơ người mượn thành công', $resThem['thanh_cong'] === true && $resThem['id'] > 0);
$idNmTest = $resThem['id'];

// 5. Kiểm tra tự sinh mã người mượn chuẩn
$nmMoi = nguoi_muon_tim_theo_id($idNmTest);
kiem_thu(
    'Mã người mượn tự động sinh theo mẫu chuẩn NM-[MSSV]',
    $nmMoi !== null && $nmMoi['ma_nguoi_muon'] === 'NM-' . $mssvTest,
    'Mã sinh ra: ' . ($nmMoi['ma_nguoi_muon'] ?? '')
);

// 6. Chặn trùng mã số trường
$resTrungMst = nguoi_muon_luu([
    'ma_so_truong'    => $mssvTest,
    'ho_ten'          => 'Trùng MSSV',
    'loai_nguoi_muon' => 'SINH_VIEN',
]);
kiem_thu('Chặn tạo trùng mã số trường (MSSV)', $resTrungMst['thanh_cong'] === false);

// 7. Chặn trùng số điện thoại
$resTrungSdt = nguoi_muon_luu([
    'ma_so_truong'    => 'KHAC_' . rand(100, 999),
    'ho_ten'          => 'Trùng Số Điện Thoại',
    'loai_nguoi_muon' => 'SINH_VIEN',
    'so_dien_thoai'   => $sdtTest,
]);
kiem_thu('Chặn tạo trùng số điện thoại', $resTrungSdt['thanh_cong'] === false);

// 8. Chỉnh sửa thông tin người mượn
$resSua = nguoi_muon_luu([
    'ma_so_truong'    => $mssvTest,
    'ho_ten'          => 'Sinh Viên Đã Sửa Tên',
    'loai_nguoi_muon' => 'SINH_VIEN',
    'lop_don_vi'      => 'DA21CNTT-VIP',
    'so_dien_thoai'   => $sdtTest,
], $idNmTest);
$nmDaSua = nguoi_muon_tim_theo_id($idNmTest);
kiem_thu(
    'Cập nhật thông tin người mượn thành công',
    $resSua['thanh_cong'] === true && $nmDaSua['ho_ten'] === 'Sinh Viên Đã Sửa Tên' && $nmDaSua['lop_don_vi'] === 'DA21CNTT-VIP'
);

// 9. Lấy lịch sử mượn trả
$lichSu = nguoi_muon_lich_su_muon($idNmTest);
kiem_thu('Truy vấn lịch sử mượn trả (người mới tạo có 0 lượt)', is_array($lichSu) && count($lichSu) === 0);

// 10. Xóa người mượn test
$resXoa = nguoi_muon_xoa($idNmTest);
kiem_thu('Xóa người mượn an toàn khi chưa phát sinh giao dịch', $resXoa['thanh_cong'] === true);

// Tổng kết
tong_ket_kiem_thu('Quản lý Người mượn (G3)');

