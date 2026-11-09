<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo 'Phương thức không được hỗ trợ. Vui lòng đăng xuất qua biểu mẫu bảo mật.';
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!csrf_kiem_tra($token)) {
    http_response_code(400);
    echo 'Mã xác thực CSRF không hợp lệ hoặc phiên làm việc đã kết thúc.';
    exit;
}

auth_dang_xuat();

header('Location: ' . app_full_url('login.php?dang_xuat=1'));
exit;
