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
    $noiDungSach = trim(strip_tags($noiDung));
    $banGhi = [
        'loai'      => $loai,
        'noi_dung'  => $noiDungSach,
        'thoi_gian' => date('H:i • d/m/Y'),
    ];
    $_SESSION['__thong_bao'] = $banGhi;

    if (!isset($_SESSION['__lich_su_thong_bao']) || !is_array($_SESSION['__lich_su_thong_bao'])) {
        $_SESSION['__lich_su_thong_bao'] = [];
    }
    array_unshift($_SESSION['__lich_su_thong_bao'], $banGhi);
    if (count($_SESSION['__lich_su_thong_bao']) > 15) {
        $_SESSION['__lich_su_thong_bao'] = array_slice($_SESSION['__lich_su_thong_bao'], 0, 15);
    }
}

/**
 * Lấy danh sách các thông báo gần đây trong phiên làm việc.
 *
 * @return array Danh sách thông báo gần đây
 */
function lay_lich_su_thong_bao(): array {
    phien_khoi_dong();
    if (!isset($_SESSION['__lich_su_thong_bao']) || !is_array($_SESSION['__lich_su_thong_bao'])) {
        return [];
    }
    return $_SESSION['__lich_su_thong_bao'];
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

