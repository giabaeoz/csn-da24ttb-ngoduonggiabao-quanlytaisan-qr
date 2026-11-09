<?php
/**
 * Bộ điều khiển trang lỗi 404 Not Found.
 */

declare(strict_types=1);

require_once __DIR__ . '/app/core/khoi-dong.php';

http_response_code(404);
$tieuDeTrang = '404 - Không tìm thấy trang';
$noiDungLoi = 'Trang bạn yêu cầu không tồn tại hoặc đã được di chuyển.';

require __DIR__ . '/app/views/404.php';
