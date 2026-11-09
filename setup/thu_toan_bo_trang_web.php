<?php
declare(strict_types=1);

$cookieFile = sys_get_temp_dir() . '/cookie_admin.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

// 1. Đăng nhập admin
$ch = curl_init('http://127.0.0.1:8000/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', (string)$html, $m);
$csrf = $m[1] ?? '';

$ch = curl_init('http://127.0.0.1:8000/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'ten_dang_nhap' => 'admin',
    'mat_khau'      => 'admin123',
    'csrf_token'    => $csrf,
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_exec($ch);
curl_close($ch);

// 2. Lấy 1 ID tài sản và 1 ID người mượn thực tế
require_once dirname(__DIR__) . '/src/app/core/khoi-dong.php';
$db = db();
$tsId = (int)($db->query("SELECT id FROM tai_san LIMIT 1")->fetch_row()[0] ?? 1);
$nmId = (int)($db->query("SELECT id FROM nguoi_muon LIMIT 1")->fetch_row()[0] ?? 1);
$pmId = (int)($db->query("SELECT id FROM phieu_muon LIMIT 1")->fetch_row()[0] ?? 1);

$trangCanKiemTra = [
    '/tong-quan.php',
    '/muon-tra/muon.php',
    '/muon-tra/tra.php',
    '/muon-tra/index.php',
    '/muon-tra/chi-tiet.php?id=' . $pmId,
    '/tai-san/index.php',
    '/tai-san/them.php',
    '/tai-san/sua.php?id=' . $tsId,
    '/tai-san/chi-tiet.php?id=' . $tsId,
    '/tai-san/in-qr.php?id=' . $tsId,
    '/nguoi-muon/index.php',
    '/nguoi-muon/them.php',
    '/nguoi-muon/sua.php?id=' . $nmId,
    '/nguoi-muon/chi-tiet.php?id=' . $nmId,
    '/danh-muc/phong-truc.php',
    '/danh-muc/phong-hoc.php',
    '/danh-muc/loai-tai-san.php',
    '/dieu-chuyen/index.php',
    '/dieu-chuyen/them.php',
];

$loi = 0;
echo "============================================================\n";
echo "  KIỂM TRA HTTP TOÀN BỘ GIAO DIỆN TRANG WEB\n";
echo "============================================================\n";

foreach ($trangCanKiemTra as $uri) {
    $ch = curl_init('http://127.0.0.1:8000' . $uri);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200) {
        // Kiểm tra xem trong body có chứa Fatal error hoặc Exception không
        if (str_contains((string)$body, 'Fatal error') || str_contains((string)$body, 'Uncaught')) {
            echo "  [FAIL] $uri -> Trả về 200 nhưng có lỗi PHP Fatal error trong HTML!\n";
            $loi++;
        } else {
            echo "  [PASS] $uri -> HTTP 200 OK\n";
        }
    } else {
        echo "  [FAIL] $uri -> HTTP $code!\n";
        $loi++;
    }
}

echo "============================================================\n";
if ($loi === 0) {
    echo "TẤT CẢ " . count($trangCanKiemTra) . " TRANG ĐỀU MỞ THÀNH CÔNG (HTTP 200 OK, KHÔNG CÓ LỖI)!\n";
} else {
    echo "CÓ $loi TRANG BỊ LỖI!\n";
}
echo "============================================================\n";
