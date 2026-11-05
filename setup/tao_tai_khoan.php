<?php

declare(strict_types=1);

/**
 * Script CLI khởi tạo tài khoản an toàn cho môi trường cục bộ
 * 
 * Cách sử dụng từ dòng lệnh:
 *   php setup/tao_tai_khoan.php
 *   php setup/tao_tai_khoan.php --username=admin --role=QUAN_TRI
 *   php setup/tao_tai_khoan.php --username=quanly01 --role=QUAN_LY
 *   php setup/tao_tai_khoan.php --username=nhanvien01 --role=NHAN_VIEN
 */

// Chỉ cho phép chạy từ giao diện dòng lệnh (CLI) để đảm bảo an ninh
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Lỗi: Script này chỉ được phép thực thi trực tiếp từ giao diện dòng lệnh (CLI).\n";
    exit(1);
}

require_once __DIR__ . '/../src/includes/database.php';
require_once __DIR__ . '/../src/includes/auth.php';

echo "============================================================\n";
echo "   HỆ THỐNG QUẢN LÝ TÀI SẢN BẰNG MÃ QR (QLTS-QR)\n";
echo "       Script khởi tạo tài khoản nhân viên / quản trị\n";
echo "============================================================\n\n";

// Đọc tham số dòng lệnh nếu có (dạng --key=value)
$tuyChon = getopt('', ['username::', 'name::', 'code::', 'role::', 'phone::', 'password::']);

$db = db_ket_noi();

// Hàm đọc input từ người dùng nếu chưa truyền qua CLI options
function nhap_du_lieu(string $loiNhan, string $macDinh = '', bool $anKyTu = false): string {
    $nhan = $macDinh !== '' ? "$loiNhan [$macDinh]: " : "$loiNhan: ";
    echo $nhan;
    
    $dong = trim((string) fgets(STDIN));
    if ($dong === '' && $macDinh !== '') {
        return $macDinh;
    }
    return $dong;
}

// 1. Thu thập thông tin tài khoản
$vaiTro = strtoupper((string) ($tuyChon['role'] ?? ''));
if (!in_array($vaiTro, [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN], true)) {
    echo "Chọn vai trò tài khoản:\n";
    echo "  1. QUAN_TRI  (Quản trị hệ thống, quản lý tài khoản, báo cáo)\n";
    echo "  2. QUAN_LY   (Quản lý danh mục, tài sản, phòng trực)\n";
    echo "  3. NHAN_VIEN (Vận hành phòng trực, quét QR mượn/trả, kiểm kê)\n";
    $chon = nhap_du_lieu("Nhập số [1-3]", "1");
    $vaiTro = match ($chon) {
        '2' => VAI_TRO_QUAN_LY,
        '3' => VAI_TRO_NHAN_VIEN,
        default => VAI_TRO_QUAN_TRI,
    };
}

$tenDangNhapMacDinh = match ($vaiTro) {
    VAI_TRO_QUAN_TRI  => 'admin',
    VAI_TRO_QUAN_LY   => 'quanly01',
    VAI_TRO_NHAN_VIEN => 'nhanvien01',
};
$tenDangNhap = trim((string) ($tuyChon['username'] ?? nhap_du_lieu("Tên đăng nhập", $tenDangNhapMacDinh)));

$hoTenMacDinh = match ($vaiTro) {
    VAI_TRO_QUAN_TRI  => 'Quản Trị Viên Hệ Thống',
    VAI_TRO_QUAN_LY   => 'Cán Bộ Quản Lý Thiết Bị',
    VAI_TRO_NHAN_VIEN => 'Nhân Viên Trực Phòng 01',
};
$hoTen = trim((string) ($tuyChon['name'] ?? nhap_du_lieu("Họ và tên", $hoTenMacDinh)));

$maNhanVienMacDinh = match ($vaiTro) {
    VAI_TRO_QUAN_TRI  => 'NV_ADMIN01',
    VAI_TRO_QUAN_LY   => 'NV_QL01',
    VAI_TRO_NHAN_VIEN => 'NV_TRUC01',
};
$maNhanVien = trim((string) ($tuyChon['code'] ?? nhap_du_lieu("Mã nhân viên", $maNhanVienMacDinh)));

$soDienThoai = trim((string) ($tuyChon['phone'] ?? nhap_du_lieu("Số điện thoại", "0901234567")));

// 2. Kiểm tra trước để tránh tạo trùng hoặc ghi đè
$stmtCheck = $db->prepare('SELECT id, ten_dang_nhap, ma_nhan_vien, vai_tro FROM nhan_vien WHERE ten_dang_nhap = ? OR ma_nhan_vien = ? LIMIT 1');
$stmtCheck->bind_param('ss', $tenDangNhap, $maNhanVien);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();
$tonTai = $resCheck ? $resCheck->fetch_assoc() : null;
$stmtCheck->close();

if ($tonTai) {
    echo "\n[THÔNG BÁO] Tài khoản với tên đăng nhập hoặc mã nhân viên này ĐÃ TỒN TẠI!\n";
    echo "  - ID: {$tonTai['id']}\n";
    echo "  - Tên đăng nhập: {$tonTai['ten_dang_nhap']}\n";
    echo "  - Mã nhân viên: {$tonTai['ma_nhan_vien']}\n";
    echo "  - Vai trò: {$tonTai['vai_tro']}\n";
    echo "Thao tác đã dừng lại để tránh ghi đè dữ liệu có sẵn.\n";
    exit(0);
}

// 3. Nhập mật khẩu an toàn
$matKhau = (string) ($tuyChon['password'] ?? '');
while ($matKhau === '') {
    $matKhau = nhap_du_lieu("Nhập mật khẩu (tối thiểu 6 ký tự)");
    if (strlen($matKhau) < 6) {
        echo "Mật khẩu quá ngắn, vui lòng nhập ít nhất 6 ký tự!\n";
        $matKhau = '';
    }
}

// 4. Băm mật khẩu bằng hàm tiêu chuẩn an toàn
$matKhauHash = password_hash($matKhau, PASSWORD_DEFAULT);

// 5. Xử lý đặc thù cho vai trò NHAN_VIEN: Ràng buộc CSDL yêu cầu phong_truc_id không được rỗng
$phongTrucId = null;
if ($vaiTro === VAI_TRO_NHAN_VIEN) {
    // Kiểm tra xem đã có phòng trực nào trong CSDL chưa
    $resPhong = $db->query('SELECT id, ma_phong_truc, ten_phong_truc FROM phong_truc LIMIT 1');
    if ($resPhong && $resPhong->num_rows > 0) {
        $phong = $resPhong->fetch_assoc();
        $phongTrucId = (int) $phong['id'];
        echo "-> Tự động gán vào điểm trực hiện có: {$phong['ten_phong_truc']} ({$phong['ma_phong_truc']})\n";
    } else {
        // Tạo một phòng trực mẫu nếu chưa có
        $db->query("INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc, vi_tri) VALUES ('PT01', 'Phòng Trực Thiết Bị A', 'Tầng 1 - Khu Nhà A')");
        $phongTrucId = (int) $db->insert_id;
        echo "-> Đã tự động khởi tạo điểm trực ban đầu: Phòng Trực Thiết Bị A (PT01)\n";
    }
}

// 6. Lưu vào cơ sở dữ liệu bằng Prepared Statement
$sqlInsert = 'INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, so_dien_thoai, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id, dang_hoat_dong) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 1)';
$stmtInsert = $db->prepare($sqlInsert);
if (!$stmtInsert) {
    echo "\n[LỖI] Chuẩn bị câu lệnh SQL thất bại: " . $db->error . "\n";
    exit(1);
}

$stmtInsert->bind_param('ssssssi', $maNhanVien, $hoTen, $soDienThoai, $tenDangNhap, $matKhauHash, $vaiTro, $phongTrucId);
$kq = $stmtInsert->execute();

if ($kq) {
    $nhanVienId = $db->insert_id;
    echo "\n============================================================\n";
    echo "[THÀNH CÔNG] Đã tạo tài khoản {$vaiTro} thành công!\n";
    echo "============================================================\n";
    echo "  - ID nhân viên  : {$nhanVienId}\n";
    echo "  - Mã nhân viên  : {$maNhanVien}\n";
    echo "  - Họ và tên     : {$hoTen}\n";
    echo "  - Tên đăng nhập : {$tenDangNhap}\n";
    echo "  - Vai trò       : {$vaiTro}\n";
    echo "  - Trạng thái    : Đang hoạt động (dang_hoat_dong = 1)\n";
    echo "  - Mật khẩu      : [Đã băm an toàn bằng password_hash]\n";
    echo "============================================================\n";
    echo "Bây giờ bạn có thể mở trình duyệt và đăng nhập tại:\n";
    echo "  http://localhost:8000/login.php (hoặc http://localhost/quanlytaisan-qr/login.php)\n\n";
} else {
    echo "\n[LỖI] Tạo tài khoản thất bại: " . $stmtInsert->error . "\n";
    exit(1);
}

$stmtInsert->close();

