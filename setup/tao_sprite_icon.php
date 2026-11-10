<?php

/**
 * Script CLI tạo tệp SVG Sprite từ bộ biểu tượng Lucide cục bộ.
 * Chạy lệnh: C:\xampp\php\php.exe setup/tao_sprite_icon.php
 *
 * Để thêm biểu tượng mới (tối đa 30 biểu tượng theo quy chuẩn):
 *  1. Sao chép tệp .svg từ bộ Lucide vào src/assets/vendor/lucide/
 *  2. Thêm tên biểu tượng vào mảng $danhSachBieuTuong bên dưới
 *  3. Chạy lại script này để sinh src/assets/icons/sprite.svg
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Script này chỉ chạy trên giao diện dòng lệnh (CLI).\n");
}

// Danh sách các biểu tượng Lucide đang dùng trong hệ thống (tối đa 30 biểu tượng)
$danhSachBieuTuong = [
    'layout-dashboard', // Tổng quan
    'package',          // Tài sản
    'repeat',           // Mượn - trả
    'arrow-left-right', // Điều chuyển
    'clipboard-check',  // Kiểm kê
    'users',            // Người mượn
    'folder-tree',      // Danh mục
    'user-cog',         // Tài khoản
    'wrench',           // Sự cố & bảo trì
    'bar-chart-3',      // Thống kê
    'qr-code',          // Mã QR
    'scan-line',        // Quét mã
    'plus',             // Thêm mới
    'pencil',           // Chỉnh sửa
    'trash-2',          // Xoá
    'eye',              // Xem chi tiết
    'search',           // Tìm kiếm
    'printer',          // In ấn
    'save',             // Lưu
    'log-out',          // Đăng xuất
    'menu',             // Mở menu
    'x',                // Đóng / Huỷ
    'filter',           // Bộ lọc
    'check-circle-2',   // Thành công / Đạt
    'alert-triangle',   // Cảnh báo
    'alert-circle',     // Lỗi / Không đạt
    'info',             // Thông tin
    'rotate-ccw',       // Đặt lại bộ lọc
    'lock',             // Khoá / Bảo mật
    'arrow-left',       // Quay lại
];

if (count($danhSachBieuTuong) > 30) {
    fwrite(STDERR, "[LỖI] Số lượng biểu tượng vượt quá giới hạn 30 biểu tượng cho phép!\n");
    exit(1);
}

$thuMucGoc = dirname(__DIR__);
$thuMucNguon = $thuMucGoc . '/src/assets/vendor/lucide';
$thuMucDich = $thuMucGoc . '/src/assets/icons';
$tepSprite = $thuMucDich . '/sprite.svg';

if (!is_dir($thuMucDich)) {
    mkdir($thuMucDich, 0775, true);
}

$cacSymbol = [];
foreach ($danhSachBieuTuong as $ten) {
    $duongDanSvg = $thuMucNguon . '/' . $ten . '.svg';
    if (!is_file($duongDanSvg)) {
        fwrite(STDERR, "[LỖI] Không tìm thấy tệp biểu tượng: {$duongDanSvg}\n");
        exit(1);
    }

    $noiDung = (string)file_get_contents($duongDanSvg);
    // Trích xuất phần thân bên trong thẻ <svg ...>...</svg>
    if (!preg_match('~<svg[^>]*>(.*?)</svg>~si', $noiDung, $m)) {
        fwrite(STDERR, "[LỖI] Nội dung SVG không hợp lệ trong tệp: {$duongDanSvg}\n");
        exit(1);
    }

    $thanSvg = trim($m[1]);
    $cacSymbol[] = sprintf(
        '  <symbol id="%s" viewBox="0 0 24 24">%s</symbol>',
        htmlspecialchars($ten, ENT_QUOTES, 'UTF-8'),
        "\n    " . preg_replace('/\s*\n\s*/', "\n    ", $thanSvg) . "\n  "
    );
}

$noiDungSprite = "<!-- Tệp tự động sinh bởi setup/tao_sprite_icon.php (Bộ biểu tượng Lucide - Giấy phép ISC) -->\n"
    . '<svg xmlns="http://www.w3.org/2000/svg" style="display:none">' . "\n"
    . implode("\n", $cacSymbol) . "\n"
    . "</svg>\n";

file_put_contents($tepSprite, $noiDungSprite);

echo "[THÀNH CÔNG] Đã đóng gói " . count($cacSymbol) . " biểu tượng vào: src/assets/icons/sprite.svg\n";
