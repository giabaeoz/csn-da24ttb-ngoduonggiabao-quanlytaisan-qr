<?php
/**
 * Quản lý phiên làm việc (Session) và thông báo tạm thời (Flash Message).
 * Đảm bảo cookie an toàn (HttpOnly, SameSite=Lax, Secure khi có HTTPS).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * Khởi động phiên làm việc an toàn với các cờ bảo mật cookie.
 */
function phien_khoi_dong(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    if (!headers_sent()) {
        $thoiGianSong = (int)cau_hinh('ung_dung.thoi_gian_song', 28800);
        $laHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

        session_set_cookie_params([
            'lifetime' => $thoiGianSong,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $laHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    } elseif (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
}

/**
 * Đặt thông báo tạm thời (flash message) hiển thị ở lượt truy cập kế tiếp.
 *
 * @param string $loai 'thanh_cong' | 'loi' | 'canh_bao' | 'thong_tin'
 * @param string $noiDung Nội dung thông báo hiển thị cho người dùng
 */
function dat_thong_bao(string $loai, string $noiDung): void {
    phien_khoi_dong();
    $_SESSION['__thong_bao'] = [
        'loai'    => $loai,
        'noi_dung' => $noiDung,
    ];
}

/**
 * Lấy và xóa thông báo tạm thời sau khi hiển thị (Flash message pattern).
 *
 * @return array|null Mảng gồm 'loai' và 'noi_dung' hoặc null nếu không có
 */
function lay_thong_bao(): ?array {
    phien_khoi_dong();
    if (!isset($_SESSION['__thong_bao']) || !is_array($_SESSION['__thong_bao'])) {
        return null;
    }

    $thongBao = $_SESSION['__thong_bao'];
    unset($_SESSION['__thong_bao']);
    return $thongBao;
}
