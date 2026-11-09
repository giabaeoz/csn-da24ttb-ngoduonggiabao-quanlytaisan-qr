<?php
/**
 * Script CLI kiểm tra chẩn đoán môi trường hệ thống.
 * Chạy: C:\xampp\php\php.exe setup/kiem_tra_moi_truong.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Tập tin này chỉ được thực thi trên giao diện dòng lệnh (CLI).\n");
    exit(1);
}

define('APP_KHOI_DONG', true);

echo "======================================================================\n";
echo "  KIỂM TRA MÔI TRƯỜNG HỆ THỐNG QUẢN LÝ TÀI SẢN BẰNG MÃ QR (AGY)\n";
echo "======================================================================\n\n";

$soLoi = 0;
$soThanhCong = 0;

function kiem_tra(string $mucTieu, bool $dieuKien, string $thongTinThem = '', string $huongDan = ''): void {
    global $soLoi, $soThanhCong;
    if ($dieuKien) {
        $soThanhCong++;
        echo "  [PASS] $mucTieu";
        if ($thongTinThem !== '') {
            echo " ($thongTinThem)";
        }
        echo "\n";
    } else {
        $soLoi++;
        echo "  [FAIL] $mucTieu\n";
        if ($thongTinThem !== '') {
            echo "         Chi tiết: $thongTinThem\n";
        }
        if ($huongDan !== '') {
            echo "         Khắc phục: $huongDan\n";
        }
    }
}

// 1. Môi trường PHP
kiem_tra(
    'Phiên bản PHP >= 8.2',
    version_compare(PHP_VERSION, '8.2.0', '>='),
    PHP_VERSION,
    'Vui lòng nâng cấp PHP lên 8.2 hoặc mới hơn.'
);

kiem_tra(
    'Extension mysqli',
    extension_loaded('mysqli'),
    extension_loaded('mysqli') ? 'Đã bật' : 'Chưa bật',
    'Mở php.ini và bỏ dấu ; ở dòng extension=mysqli.'
);

kiem_tra(
    'Extension mbstring',
    extension_loaded('mbstring'),
    extension_loaded('mbstring') ? 'Đã bật' : 'Chưa bật'
);

kiem_tra(
    'Extension json',
    extension_loaded('json'),
    extension_loaded('json') ? 'Đã bật' : 'Chưa bật'
);

// 2. Đọc cấu hình
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
    kiem_tra('Tệp cấu hình cục bộ config.local.php', true, 'src/app/config/config.local.php');
} elseif (is_file($tepConfigExample)) {
    kiem_tra('Tệp cấu hình cục bộ config.local.php', false, 'Chưa tìm thấy config.local.php', 'Sao chép config.example.php thành config.local.php và cập nhật mật khẩu.');
}

// 3. Kết nối MySQL
$db = null;
if (extension_loaded('mysqli')) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $db = @new mysqli(
        $cauHinhDb['host'],
        $cauHinhDb['user'],
        $cauHinhDb['password'],
        '',
        (int)$cauHinhDb['port']
    );

    $ketNoiOk = $db && !$db->connect_errno;
    kiem_tra(
        'Kết nối máy chủ MySQL',
        $ketNoiOk,
        $ketNoiOk ? ("MySQL " . $db->server_info . " tại " . $cauHinhDb['host'] . ":" . $cauHinhDb['port']) : ($db ? $db->connect_error : 'Không thể kết nối'),
        'Kiểm tra dịch vụ MySQL trong XAMPP hoặc thông tin đăng nhập trong config.local.php.'
    );

    if ($ketNoiOk) {
        // Kiểm tra database
        $dbName = $cauHinhDb['database'];
        $db->select_db($dbName);
        $coDb = !$db->errno;
        kiem_tra(
            "Cơ sở dữ liệu `$dbName`",
            $coDb,
            $coDb ? 'Tồn tại' : 'Chưa tồn tại',
            "Chạy import file setup/database_dacsn_qlts_qr.sql vào MySQL."
        );

        if ($coDb) {
            $db->set_charset('utf8mb4');
            $db->query("SET time_zone = '+07:00'");

            // 13 bảng bắt buộc
            $bangYeuCau = [
                'phong_truc',
                'phong_hoc',
                'loai_tai_san',
                'nhan_vien',
                'nguoi_muon',
                'tai_san',
                'phieu_muon',
                'chi_tiet_muon',
                'dieu_chuyen',
                'chi_tiet_dieu_chuyen',
                'dot_kiem_ke',
                'chi_tiet_kiem_ke',
                'su_co_tai_san',
            ];

            $bangHienCo = [];
            $res = $db->query("SHOW TABLES");
            if ($res) {
                while ($row = $res->fetch_row()) {
                    $bangHienCo[] = $row[0];
                }
            }

            $thieuBang = array_diff($bangYeuCau, $bangHienCo);
            kiem_tra(
                'Đầy đủ 13 bảng CSDL cốt lõi',
                count($thieuBang) === 0,
                count($thieuBang) === 0 ? 'Đủ 13/13 bảng' : ('Thiếu: ' . implode(', ', $thieuBang)),
                'Import lại setup/database_dacsn_qlts_qr.sql để cập nhật đủ cấu trúc bảng.'
            );

            // Kiểm tra các cột nghiệp vụ đặc thù
            if (in_array('tai_san', $bangHienCo, true)) {
                $colsTaiSan = [];
                $res = $db->query("SHOW COLUMNS FROM tai_san");
                while ($r = $res->fetch_assoc()) {
                    $colsTaiSan[] = $r['Field'];
                }
                $coTuongThich = in_array('pham_vi_tuong_thich', $colsTaiSan, true) && in_array('phong_tuong_thich', $colsTaiSan, true);
                kiem_tra(
                    'Bảng `tai_san` có thuộc tính tương thích phòng',
                    $coTuongThich,
                    $coTuongThich ? 'pham_vi_tuong_thich, phong_tuong_thich' : 'Thiếu cột tương thích'
                );
            }

            if (in_array('chi_tiet_muon', $bangHienCo, true)) {
                $colsCTM = [];
                $res = $db->query("SHOW COLUMNS FROM chi_tiet_muon");
                while ($r = $res->fetch_assoc()) {
                    $colsCTM[] = $r['Field'];
                }
                $coNhanVienNhan = in_array('nhan_vien_nhan_id', $colsCTM, true);
                kiem_tra(
                    'Bảng `chi_tiet_muon` có cột nhân viên nhận trả',
                    $coNhanVienNhan,
                    $coNhanVienNhan ? 'nhan_vien_nhan_id' : 'Thiếu nhan_vien_nhan_id'
                );
                $coTaiSanChuaTra = in_array('tai_san_chua_tra_id', $colsCTM, true);
                kiem_tra(
                    'Bảng `chi_tiet_muon` có cột sinh chặn mượn trùng',
                    $coTaiSanChuaTra,
                    $coTaiSanChuaTra ? 'tai_san_chua_tra_id (UNIQUE)' : 'Thiếu tai_san_chua_tra_id'
                );
            }

            if (in_array('dot_kiem_ke', $bangHienCo, true)) {
                $colsDKK = [];
                $res = $db->query("SHOW COLUMNS FROM dot_kiem_ke");
                while ($r = $res->fetch_assoc()) {
                    $colsDKK[] = $r['Field'];
                }
                $coHuyDot = in_array('nhan_vien_huy_id', $colsDKK, true) && in_array('ly_do_huy', $colsDKK, true);
                kiem_tra(
                    'Bảng `dot_kiem_ke` hỗ trợ hủy đợt kiểm kê',
                    $coHuyDot,
                    $coHuyDot ? 'nhan_vien_huy_id, ly_do_huy' : 'Thiếu cột hủy đợt'
                );
            }
        }
    }
}

echo "\n----------------------------------------------------------------------\n";
echo "  KẾT QUẢ: $soThanhCong tiêu chí đạt, $soLoi tiêu chí chưa đạt.\n";
echo "======================================================================\n";

if ($soLoi > 0) {
    exit(1);
}

exit(0);
