<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

// Nếu đã đăng nhập, tự động điều hướng về phân hệ phù hợp với vai trò
if (auth_da_dang_nhap()) {
    $nguoiDung = auth_nguoi_dung_hien_tai();
    $vaiTro = $nguoiDung['vai_tro'] ?? null;
    header('Location: ' . auth_duong_dan_khoi_dau($vaiTro));
    exit;
}

// Nếu chưa đăng nhập, chuyển hướng sang trang đăng nhập
header('Location: ' . app_full_url('login.php'));
exit;
