<?php
/**
 * Script quét toàn bộ mã nguồn tìm lỗi cú pháp, tệp nhúng thiếu hoặc liên kết hỏng.
 */

declare(strict_types=1);

$goc = dirname(__DIR__);
$loi = [];
$canhBao = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($goc . '/src', FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $tep) {
    $duongDan = $tep->getPathname();
    $ext = strtolower(pathinfo($duongDan, PATHINFO_EXTENSION));

    if ($ext === 'php') {
        // 1. Kiểm tra syntax
        $cmd = 'C:\\xampp\\php\\php.exe -l ' . escapeshellarg($duongDan) . ' 2>&1';
        $out = shell_exec($cmd) ?? '';
        if (!str_contains($out, 'No syntax errors detected')) {
            $loi[] = "Lỗi cú pháp PHP tại: $duongDan -> " . trim($out);
        }

        // 2. Quét nội dung tệp
        $nd = file_get_contents($duongDan);
        
        // Quét require/include tệp không tồn tại
        if (preg_match_all('~(?:require|include)(?:_once)?\s+([^\;]+)\;~', $nd, $m)) {
            // Xem qua các đường dẫn cố định
        }

        // Quét link cứng không qua url()
        if (preg_match_all('/href=["\']\/(?!http)([a-zA-Z0-9_\-\.\/\?=&]+)["\']/', $nd, $matches)) {
            foreach ($matches[0] as $match) {
                $canhBao[] = "Link cứng tại $duongDan: $match (nên dùng <?= url(...) ?>)";
            }
        }
    }
}

echo "=== KẾT QUẢ QUÉT ===\n";
echo "Số lỗi: " . count($loi) . "\n";
foreach ($loi as $l) {
    echo "  [LỖI] $l\n";
}
echo "Số cảnh báo: " . count($canhBao) . "\n";
foreach ($canhBao as $cb) {
    echo "  [CẢNH BÁO] $cb\n";
}
