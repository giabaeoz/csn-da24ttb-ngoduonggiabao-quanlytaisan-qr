<?php
/**
 * Cấu hình hệ thống mẫu (môi trường phát triển và máy chủ)
 * Sao chép tập tin này thành config.local.php để sử dụng.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

return [
    'csdl' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'user'     => 'root',
        'password' => '',
        'database' => 'dacsn_qlts_qr',
        'charset'  => 'utf8mb4',
    ],
    'ung_dung' => [
        'ten'            => 'Quản lý tài sản - QR',
        'moi_truong'     => 'phat_trien', // 'phat_trien' hoặc 'san_xuat'
        'thoi_gian_song' => 28800,        // 8 giờ
    ],
];
