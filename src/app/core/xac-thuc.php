<?php
/**
 * Quản lý xác thực người dùng và bảo mật phiên đăng nhập.
 * Hỗ trợ đăng nhập, đăng xuất, kiểm tra phiên cán bộ, kiểm tra trạng thái hoạt động.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * Lấy thông tin tài khoản cán bộ đang đăng nhập từ phiên làm việc.
 *
 * @return array|null Mảng thông tin nhân viên hoặc null nếu chưa đăng nhập
 */
function nguoi_dung_hien_tai(): ?array {
    phien_khoi_dong();
    if (empty($_SESSION['nguoi_dung']) || !is_array($_SESSION['nguoi_dung'])) {
        return null;
    }
    return $_SESSION['nguoi_dung'];
}

/**
 * Xác thực thông tin đăng nhập từ cơ sở dữ liệu.
 * Kiểm tra mật khẩu mã hóa bằng password_verify(), kiểm tra trạng thái hoạt động (dang_hoat_dong = 1).
 * Khi đăng nhập thành công:
 *  - Tái tạo ID phiên (session_regenerate_id) để chống Session Fixation
 *  - Lưu thông tin an toàn vào $_SESSION['nguoi_dung']
 *
 * @param string $tenDangNhap Tên đăng nhập
 * @param string $matKhau Mật khẩu thô do người dùng nhập
 * @return bool True nếu đăng nhập thành công, False nếu sai tài khoản/mật khẩu hoặc bị khóa
 */
function dang_nhap(string $tenDangNhap, string $matKhau): bool {
    phien_khoi_dong();

    $tenDangNhap = trim($tenDangNhap);
    if ($tenDangNhap === '' || $matKhau === '') {
        return false;
    }

    $ketNoi = db();

    $sql = "SELECT nv.id, nv.ma_nhan_vien, nv.ten_dang_nhap, nv.mat_khau_hash, 
                   nv.ho_ten, nv.so_dien_thoai, nv.vai_tro, 
                   nv.phong_truc_id, nv.dang_hoat_dong,
                   pt.ten_phong_truc, pt.ma_phong_truc
            FROM nhan_vien nv
            LEFT JOIN phong_truc pt ON nv.phong_truc_id = pt.id
            WHERE nv.ten_dang_nhap = ?
            LIMIT 1";

    $stmt = $ketNoi->prepare($sql);
    $stmt->bind_param('s', $tenDangNhap);
    $stmt->execute();
    $ketQua = $stmt->get_result();
    $nhanVien = $ketQua->fetch_assoc();
    $stmt->close();

    if (!$nhanVien) {
        return false;
    }

    // Kiểm tra tài khoản có đang hoạt động không
    if ((int)$nhanVien['dang_hoat_dong'] !== 1) {
        return false;
    }

    // Xác thực băm mật khẩu
    if (!password_verify($matKhau, $nhanVien['mat_khau_hash'])) {
        return false;
    }

    // Tái tạo Session ID để chống Session Fixation
    if (!headers_sent()) {
        session_regenerate_id(true);
    }

    // Xóa trường mật khẩu hash trước khi lưu vào session
    unset($nhanVien['mat_khau_hash']);

    $_SESSION['nguoi_dung'] = [
        'id'             => (int)$nhanVien['id'],
        'ma_nhan_vien'   => (string)$nhanVien['ma_nhan_vien'],
        'ten_dang_nhap' => (string)$nhanVien['ten_dang_nhap'],
        'ho_ten'         => (string)$nhanVien['ho_ten'],
        'so_dien_thoai'  => (string)($nhanVien['so_dien_thoai'] ?? ''),
        'vai_tro'        => (string)$nhanVien['vai_tro'],
        'phong_truc_id'  => $nhanVien['phong_truc_id'] !== null ? (int)$nhanVien['phong_truc_id'] : null,
        'ten_phong_truc' => (string)($nhanVien['ten_phong_truc'] ?? ''),
        'ma_phong_truc'  => (string)($nhanVien['ma_phong_truc'] ?? ''),
    ];

    // Tạo token CSRF mới cho phiên đăng nhập mới
    $_SESSION['__csrf_token'] = bin2hex(random_bytes(32));

    return true;
}

/**
 * Đăng xuất và hủy toàn bộ phiên làm việc.
 */
function dang_xuat(): void {
    phien_khoi_dong();
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

/**
 * Yêu cầu người dùng phải đăng nhập trước khi tiếp tục.
 * Chuyển hướng về trang đăng nhập nếu chưa có phiên hợp lệ.
 *
 * @return array Thông tin người dùng hiện tại
 */
function yeu_cau_dang_nhap(): array {
    $nguoiDung = nguoi_dung_hien_tai();
    if ($nguoiDung === null) {
        chuyen_huong('login.php');
    }
    return $nguoiDung;
}
