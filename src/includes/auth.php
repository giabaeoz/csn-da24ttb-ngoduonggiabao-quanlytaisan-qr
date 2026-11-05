<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';

const VAI_TRO_NHAN_VIEN = 'NHAN_VIEN';
const VAI_TRO_QUAN_LY   = 'QUAN_LY';
const VAI_TRO_QUAN_TRI  = 'QUAN_TRI';

/**
 * Kiểm tra xem người dùng đã đăng nhập hay chưa
 */
function auth_da_dang_nhap(): bool
{
    return isset($_SESSION['nguoi_dung']) && is_array($_SESSION['nguoi_dung']) && !empty($_SESSION['nguoi_dung']['id']);
}

/**
 * Lấy thông tin tài khoản người dùng đang đăng nhập
 */
function auth_nguoi_dung_hien_tai(): ?array
{
    return auth_da_dang_nhap() ? $_SESSION['nguoi_dung'] : null;
}

/**
 * Lấy tên tiếng Việt thân thiện của vai trò
 */
function auth_ten_vai_tro(?string $vaiTro): string
{
    return match ($vaiTro) {
        VAI_TRO_QUAN_TRI  => 'Quản trị viên (Hệ thống)',
        VAI_TRO_QUAN_LY   => 'Quản lý (Danh mục & Tài sản)',
        VAI_TRO_NHAN_VIEN => 'Nhân viên (Vận hành phòng trực)',
        default           => 'Không xác định',
    };
}

/**
 * Lấy màu badge Bootstrap tương ứng vai trò
 */
function auth_badge_vai_tro(?string $vaiTro): string
{
    return match ($vaiTro) {
        VAI_TRO_QUAN_TRI  => 'bg-danger',
        VAI_TRO_QUAN_LY   => 'bg-primary',
        VAI_TRO_NHAN_VIEN => 'bg-success',
        default           => 'bg-secondary',
    };
}

/**
 * Lấy đường dẫn URL trang khởi đầu tương ứng với vai trò
 */
function auth_duong_dan_khoi_dau(?string $vaiTro): string
{
    return match ($vaiTro) {
        VAI_TRO_QUAN_TRI  => app_full_url('admin/index.php'),
        VAI_TRO_QUAN_LY   => app_full_url('quan-ly/index.php'),
        VAI_TRO_NHAN_VIEN => app_full_url('nhan-vien/index.php'),
        default           => app_full_url('login.php'),
    };
}

/**
 * Xử lý đăng nhập tài khoản nhân viên
 * 
 * @return array{thanh_cong: bool, thong_bao: string, vai_tro?: string}
 */
function auth_dang_nhap(string $tenDangNhap, string $matKhau): array
{
    $tenDangNhap = trim($tenDangNhap);

    if ($tenDangNhap === '' || $matKhau === '') {
        return [
            'thanh_cong' => false,
            'thong_bao'  => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.',
        ];
    }

    $db = db_ket_noi();

    // Chuẩn bị truy vấn bằng MySQLi Prepared Statement
    $sql = 'SELECT id, ma_nhan_vien, ho_ten, so_dien_thoai, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id, dang_hoat_dong 
            FROM nhan_vien 
            WHERE ten_dang_nhap = ? 
            LIMIT 1';

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        error_log('[AUTH_ERROR] Lỗi chuẩn bị câu lệnh: ' . $db->error);
        return [
            'thanh_cong' => false,
            'thong_bao'  => 'Hệ thống đang bận, vui lòng thử lại sau ít phút.',
        ];
    }

    $stmt->bind_param('s', $tenDangNhap);
    $stmt->execute();
    $ketQua = $stmt->get_result();
    $nhanVien = $ketQua ? $ketQua->fetch_assoc() : null;
    $stmt->close();

    // Kiểm tra tài khoản có tồn tại hay không
    if (!$nhanVien) {
        return [
            'thanh_cong' => false,
            'thong_bao'  => 'Tên đăng nhập hoặc mật khẩu không chính xác.',
        ];
    }

    // Kiểm tra mật khẩu bằng hàm chuẩn an toàn password_verify()
    if (!password_verify($matKhau, (string) $nhanVien['mat_khau_hash'])) {
        return [
            'thanh_cong' => false,
            'thong_bao'  => 'Tên đăng nhập hoặc mật khẩu không chính xác.',
        ];
    }

    // Kiểm tra trạng thái hoạt động: Không cho tài khoản bị khóa (dang_hoat_dong = 0) đăng nhập
    if ((int) $nhanVien['dang_hoat_dong'] !== 1) {
        return [
            'thanh_cong' => false,
            'thong_bao'  => 'Tài khoản của bạn đã bị khóa hoặc ngừng hoạt động. Vui lòng liên hệ Quản trị viên.',
        ];
    }

    // Tạo lại Session ID để chống Session Fixation (nếu headers chưa gửi)
    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    // Lưu thông tin tối thiểu cần thiết vào session (không lưu mật khẩu băm)
    $_SESSION['nguoi_dung'] = [
        'id'            => (int) $nhanVien['id'],
        'ma_nhan_vien'  => (string) $nhanVien['ma_nhan_vien'],
        'ho_ten'        => (string) $nhanVien['ho_ten'],
        'so_dien_thoai' => (string) ($nhanVien['so_dien_thoai'] ?? ''),
        'ten_dang_nhap' => (string) $nhanVien['ten_dang_nhap'],
        'vai_tro'       => (string) $nhanVien['vai_tro'],
        'phong_truc_id' => $nhanVien['phong_truc_id'] !== null ? (int) $nhanVien['phong_truc_id'] : null,
        'dang_nhap_luc' => date('Y-m-d H:i:s'),
    ];

    return [
        'thanh_cong' => true,
        'thong_bao'  => 'Đăng nhập thành công.',
        'vai_tro'    => (string) $nhanVien['vai_tro'],
    ];
}

/**
 * Xử lý đăng xuất an toàn: xóa phiên, hủy cookie
 */
function auth_dang_xuat(): void
{
    $_SESSION = [];

    if (!headers_sent() && ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
