<?php
/**
 * Tệp khởi động trung tâm của toàn bộ hệ thống (Central Bootstrap).
 * Mọi tệp bộ điều khiển (controller) hoặc API tại thư mục gốc /src đều phải
 * nạp tệp này đầu tiên để nạp các thư viện cốt lõi theo đúng thứ tự phụ thuộc.
 */

declare(strict_types=1);

// Đánh dấu hệ thống đã khởi động hợp lệ
define('APP_KHOI_DONG', true);

// Thiết lập múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Đường dẫn gốc thư mục mã nguồn lõi
$thuMucCore = __DIR__;

// Nạp các module cốt lõi theo đúng thứ tự phụ thuộc
require_once $thuMucCore . '/cau-hinh.php';
require_once $thuMucCore . '/csdl.php';
require_once $thuMucCore . '/phien.php';
require_once $thuMucCore . '/csrf.php';
require_once $thuMucCore . '/tien-ich.php';
require_once $thuMucCore . '/phan-hoi.php';
require_once $thuMucCore . '/xac-thuc.php';
require_once $thuMucCore . '/phan-quyen.php';

// Tự động khởi động phiên làm việc bảo mật
phien_khoi_dong();

