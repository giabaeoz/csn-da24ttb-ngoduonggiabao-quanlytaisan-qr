<?php
declare(strict_types=1);

// 1. Đăng nhập để lấy cookie session
$cookieFile = sys_get_temp_dir() . '/cookie_test.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

// Lấy CSRF token từ trang login
$ch = curl_init('http://127.0.0.1:8000/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
$html = curl_exec($ch);
curl_close($ch);

preg_match('/name="csrf_token"\s+value="([^"]+)"/', (string)$html, $m);
$csrf = $m[1] ?? '';

// Gửi POST đăng nhập tài khoản nhanvien01
$ch = curl_init('http://127.0.0.1:8000/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'ten_dang_nhap' => 'nhanvien01',
    'mat_khau'      => 'nhanvien123',
    'csrf_token'    => $csrf,
]));
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_exec($ch);
curl_close($ch);

// 2. Truy cập /dieu-chuyen/index.php
$ch = curl_init('http://127.0.0.1:8000/dieu-chuyen/index.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code /dieu-chuyen/index.php: " . $httpCode . "\n";
if ($httpCode === 200 && str_contains($response, 'Điều chuyển thiết bị giữa các phòng học')) {
    echo ">>> TRANG ĐIỀU CHUYỂN HOẠT ĐỘNG HOÀN TOÀN TỐT TRÊN TRÌNH DUYỆT! <<<\n";
} else {
    echo "Phản hồi: " . substr($response, 0, 500) . "\n";
}

// 3. Truy cập /dieu-chuyen/them.php
$ch = curl_init('http://127.0.0.1:8000/dieu-chuyen/them.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code /dieu-chuyen/them.php: " . $httpCode2 . "\n";
if ($httpCode2 === 200 && str_contains($response2, 'Lập phiếu điều chuyển thiết bị')) {
    echo ">>> TRANG TẠO PHIẾU ĐIỀU CHUYỂN HOẠT ĐỘNG HOÀN TOÀN TỐT TRÊN TRÌNH DUYỆT! <<<\n";
} else {
    echo "Phản hồi: " . substr($response2, 0, 500) . "\n";
}
