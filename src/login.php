<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';

if (auth_da_dang_nhap()) {
    $vaiTro = auth_nguoi_dung_hien_tai()['vai_tro'] ?? null;
    header('Location: ' . auth_duong_dan_khoi_dau($vaiTro));
    exit;
}

$loiDangNhap = '';
$tenDangNhapNhap = '';
$tiepTuc = $_GET['tiep_tuc'] ?? ($_POST['tiep_tuc'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null;
    if (!csrf_kiem_tra($token)) {
        $loiDangNhap = 'Phiên làm việc đã hết hạn hoặc yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.';
    } else {
        $tenDangNhapNhap = is_string($_POST['ten_dang_nhap'] ?? null) ? trim($_POST['ten_dang_nhap']) : '';
        $matKhau = is_string($_POST['mat_khau'] ?? null) ? $_POST['mat_khau'] : '';

        $ketQua = auth_dang_nhap($tenDangNhapNhap, $matKhau);

        if ($ketQua['thanh_cong']) {
            set_flash('success', 'Chào mừng bạn quay trở lại hệ thống!');

            if (is_string($tiepTuc) && $tiepTuc !== '' && str_starts_with($tiepTuc, '/') && !str_starts_with($tiepTuc, '//')) {
                header('Location: ' . $tiepTuc);
                exit;
            }

            header('Location: ' . auth_duong_dan_khoi_dau($ketQua['vai_tro'] ?? null));
            exit;
        } else {
            $loiDangNhap = $ketQua['thong_bao'];
        }
    }
}

require __DIR__ . '/views/login-page.php';
