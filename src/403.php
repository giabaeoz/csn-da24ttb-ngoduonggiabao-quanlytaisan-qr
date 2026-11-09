<?php
/**
 * Bộ điều khiển trang lỗi 403 Forbidden.
 */

declare(strict_types=1);

require_once __DIR__ . '/app/core/khoi-dong.php';

http_response_code(403);
$tieuDeTrang = '403 - Truy cập bị từ chối';
$noiDungLoi = 'Bạn không có đủ quyền hạn để truy cập vào chức năng hoặc tài nguyên này.';

require __DIR__ . '/app/views/403.php';
