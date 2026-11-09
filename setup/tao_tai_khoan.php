<?php
/**
 * Script CLI tạo tài khoản cán bộ: QUAN_TRI, QUAN_LY, NHAN_VIEN
 * 
 * Cách sử dụng từ dòng lệnh:
 *   C:\xampp\php\php.exe setup/tao_tai_khoan.php
 *   C:\xampp\php\php.exe setup/tao_tai_khoan.php --username=admin --role=QUAN_TRI --password=admin123
 *   C:\xampp\php\php.exe setup/tao_tai_khoan.php --username=quanly01 --role=QUAN_LY --password=quanly123
 *   C:\xampp\php\php.exe setup/tao_tai_khoan.php --username=nhanvien01 --role=NHAN_VIEN --desk=1 --password=nhanvien123
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Tập tin này chỉ được thực thi trên giao diện dòng lệnh (CLI).\n");
    exit(1);
}

define('APP_KHOI_DONG', true);

const VAI_TRO_QUAN_TRI  = 'QUAN_TRI';
const VAI_TRO_QUAN_LY   = 'QUAN_LY';
const VAI_TRO_NHAN_VIEN = 'NHAN_VIEN';

echo "============================================================\n";
echo "   HỆ THỐNG QUẢN LÝ TÀI SẢN BẰNG MÃ QR (QLTS-QR)\n";
echo "       Script khởi tạo tài khoản cán bộ hệ thống\n";
echo "============================================================\n\n";

// Đọc cấu hình kết nối DB
$tepConfigLocal = dirname(__DIR__) . '/src/app/config/config.local.php';
$tepConfigExample = dirname(__DIR__) . '/src/app/config/config.example.php';

$cauHinhDb = [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'user'     => 'root',
    'password' => '',
    'database' => 'dacsn_qlts_qr',
    'charset'  => 'utf8mb4',
];

if (is_file($tepConfigLocal)) {
    $cucBo = require $tepConfigLocal;
    if (isset($cucBo['csdl']) && is_array($cucBo['csdl'])) {
        $cauHinhDb = array_merge($cauHinhDb, $cucBo['csdl']);
    } elseif (is_array($cucBo)) {
        $cauHinhDb = array_merge($cauHinhDb, $cucBo);
    }
} elseif (is_file($tepConfigExample)) {
    $mau = require $tepConfigExample;
    if (isset($mau['csdl']) && is_array($mau['csdl'])) {
        $cauHinhDb = array_merge($cauHinhDb, $mau['csdl']);
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = new mysqli(
        $cauHinhDb['host'],
        $cauHinhDb['user'],
        $cauHinhDb['password'],
        $cauHinhDb['database'],
        (int)$cauHinhDb['port']
    );
    $db->set_charset('utf8mb4');
    $db->query("SET time_zone = '+07:00'");
} catch (Throwable $e) {
    fwrite(STDERR, "Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage() . "\n");
    exit(1);
}

// Đọc tham số CLI (dạng --key=value)
$tuyChon = getopt('', ['username::', 'name::', 'code::', 'role::', 'phone::', 'password::', 'desk::']);
$coThamSoCli = !empty($tuyChon);

function nhap_du_lieu(string $loiNhan, string $macDinh = '', bool $tuDong = false): string {
    if ($tuDong) {
        return $macDinh;
    }
    $nhan = $macDinh !== '' ? "$loiNhan [$macDinh]: " : "$loiNhan: ";
    echo $nhan;
    $dong = trim((string) fgets(STDIN));
    return $dong === '' ? $macDinh : $dong;
}

// 1. Xác định vai trò
$vaiTro = strtoupper((string) ($tuyChon['role'] ?? ''));
if (!in_array($vaiTro, [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN], true)) {
    if ($coThamSoCli) {
        fwrite(STDERR, "Vai trò không hợp lệ. Chọn một trong: QUAN_TRI, QUAN_LY, NHAN_VIEN\n");
        exit(1);
    }
    echo "Chọn vai trò tài khoản:\n";
    echo "  1. QUAN_TRI  (Quản trị hệ thống, tài khoản cán bộ)\n";
    echo "  2. QUAN_LY   (Quản lý danh mục, tài sản, sự cố, thống kê)\n";
    echo "  3. NHAN_VIEN (Trực phòng, quét QR mượn - trả, kiểm kê)\n";
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
$tenDangNhap = trim((string) ($tuyChon['username'] ?? nhap_du_lieu("Tên đăng nhập", $tenDangNhapMacDinh, $coThamSoCli)));

$hoTenMacDinh = match ($vaiTro) {
    VAI_TRO_QUAN_TRI  => 'Quản Trị Viên',
    VAI_TRO_QUAN_LY   => 'Cán Bộ Quản Lý',
    VAI_TRO_NHAN_VIEN => 'Nhân Viên Trực Phòng D6',
};
$hoTen = trim((string) ($tuyChon['name'] ?? nhap_du_lieu("Họ và tên", $hoTenMacDinh, $coThamSoCli)));

$maNhanVienMacDinh = match ($vaiTro) {
    VAI_TRO_QUAN_TRI  => 'NV-ADMIN01',
    VAI_TRO_QUAN_LY   => 'NV-QL01',
    VAI_TRO_NHAN_VIEN => 'NV-TRUC01',
};
$maNhanVien = trim((string) ($tuyChon['code'] ?? nhap_du_lieu("Mã nhân viên", $maNhanVienMacDinh, $coThamSoCli)));
$soDienThoai = trim((string) ($tuyChon['phone'] ?? nhap_du_lieu("Số điện thoại", "0901234567", $coThamSoCli)));

// 2. Kiểm tra trùng lặp
$stmt = $db->prepare('SELECT id, ten_dang_nhap, ma_nhan_vien, vai_tro FROM nhan_vien WHERE ten_dang_nhap = ? OR ma_nhan_vien = ? LIMIT 1');
$stmt->bind_param('ss', $tenDangNhap, $maNhanVien);
$stmt->execute();
$tonTai = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($tonTai) {
    echo "\n[THÔNG BÁO] Tài khoản với tên đăng nhập hoặc mã nhân viên này ĐÃ TỒN TẠI!\n";
    echo "  - ID: {$tonTai['id']}\n";
    echo "  - Tên đăng nhập: {$tonTai['ten_dang_nhap']}\n";
    echo "  - Mã nhân viên: {$tonTai['ma_nhan_vien']}\n";
    echo "  - Vai trò: {$tonTai['vai_tro']}\n";
    echo "Đã dừng thao tác để bảo vệ dữ liệu hiện có.\n";
    exit(0);
}

// 3. Mật khẩu
$matKhau = (string) ($tuyChon['password'] ?? '');
if ($matKhau === '' && $coThamSoCli) {
    $matKhau = match ($vaiTro) {
        VAI_TRO_QUAN_TRI  => 'admin123',
        VAI_TRO_QUAN_LY   => 'quanly123',
        VAI_TRO_NHAN_VIEN => 'nhanvien123',
    };
}
while ($matKhau === '') {
    $matKhau = nhap_du_lieu("Nhập mật khẩu (tối thiểu 6 ký tự)");
    if (strlen($matKhau) < 6) {
        echo "Mật khẩu quá ngắn, vui lòng nhập ít nhất 6 ký tự!\n";
        $matKhau = '';
    }
}
$matKhauHash = password_hash($matKhau, PASSWORD_DEFAULT);

// 4. Xử lý phòng trực cho NHAN_VIEN
$phongTrucId = null;
if ($vaiTro === VAI_TRO_NHAN_VIEN) {
    $deskInput = $tuyChon['desk'] ?? null;
    if ($deskInput !== null && is_numeric($deskInput)) {
        $phongTrucId = (int)$deskInput;
    } else {
        $dsPhong = $db->query("SELECT id, ma_phong_truc, ten_phong_truc FROM phong_truc WHERE dang_hoat_dong = 1 ORDER BY id")->fetch_all(MYSQLI_ASSOC);
        if (!$dsPhong) {
            $db->query("INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc, vi_tri) VALUES ('D6', 'Phòng trực D6', 'Tầng trệt D6')");
            $phongTrucId = (int)$db->insert_id;
            echo "-> Đã tạo mới phòng trực mặc định D6 (ID: $phongTrucId)\n";
        } elseif ($coThamSoCli) {
            $phongTrucId = (int)$dsPhong[0]['id'];
            echo "-> Tự động gán vào phòng trực: {$dsPhong[0]['ten_phong_truc']} (ID: $phongTrucId)\n";
        } else {
            echo "Chọn phòng trực phân công cho nhân viên:\n";
            foreach ($dsPhong as $p) {
                echo "  {$p['id']}. {$p['ten_phong_truc']} ({$p['ma_phong_truc']})\n";
            }
            $chonPhong = nhap_du_lieu("Nhập ID phòng trực", (string)$dsPhong[0]['id']);
            $phongTrucId = (int)$chonPhong;
        }
    }
}

// 5. Thêm tài khoản vào CSDL
$stmt = $db->prepare('INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, so_dien_thoai, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id, dang_hoat_dong) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
$stmt->bind_param('ssssssi', $maNhanVien, $hoTen, $soDienThoai, $tenDangNhap, $matKhauHash, $vaiTro, $phongTrucId);
$stmt->execute();
$newId = (int)$db->insert_id;
$stmt->close();

echo "\n============================================================\n";
echo "[THÀNH CÔNG] Đã khởi tạo tài khoản {$vaiTro} thành công!\n";
echo "============================================================\n";
echo "  - ID nhân viên  : {$newId}\n";
echo "  - Mã nhân viên  : {$maNhanVien}\n";
echo "  - Họ và tên     : {$hoTen}\n";
echo "  - Tên đăng nhập : {$tenDangNhap}\n";
echo "  - Mật khẩu      : {$matKhau}\n";
echo "  - Vai trò       : {$vaiTro}\n";
if ($phongTrucId) {
    echo "  - Phòng trực ID : {$phongTrucId}\n";
}
echo "  - Trạng thái    : Đang hoạt động (dang_hoat_dong = 1)\n";
echo "============================================================\n";
echo "Sử dụng tài khoản này để đăng nhập vào hệ thống.\n\n";
exit(0);
