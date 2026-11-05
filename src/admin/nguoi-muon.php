<?php
declare(strict_types=1);

// Chuyển các URL cũ sang trang đúng vai trò; không đặt nghiệp vụ người mượn trong Admin.
require_once __DIR__ . '/../includes/rbac.php';
$actor = yeu_cau_dang_nhap();
$path = match ($actor['vai_tro']) {
    VAI_TRO_NHAN_VIEN => 'nhan-vien/nguoi-muon.php',
    VAI_TRO_QUAN_LY => 'quan-ly/nguoi-muon.php',
    default => null,
};
if ($path === null) {
    http_response_code(403);
    exit('Vai trò này không quản lý người mượn.');
}
$query = http_build_query($_GET);
header('Location: ' . app_url($path) . ($query === '' ? '' : '?' . $query), true,
    $_SERVER['REQUEST_METHOD'] === 'POST' ? 307 : 302);
exit;
