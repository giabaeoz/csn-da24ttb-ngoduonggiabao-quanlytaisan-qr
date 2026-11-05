<?php

declare(strict_types=1);

/**
 * Quản lý Session an toàn, chống tấn công CSRF và các tiện ích hỗ trợ
 */

function khoi_dong_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $laHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $laHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }
}

// Luôn đảm bảo session được kích hoạt khi nạp tệp này
khoi_dong_session();

/**
 * Tạo URL chuẩn xác dựa trên môi trường chạy (Apache Alias hoặc PHP built-in server)
 */
function app_url(string $duongDan = ''): string
{
    static $goc = null;
    if ($goc === null) {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (preg_match('#^(.*?)/(admin|quan-ly|nhan-vien)(/|$)#i', $scriptName, $m)) {
            $goc = rtrim($m[1], '/');
        } else {
            $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
            $goc = ($dir === '' || $dir === '/' || $dir === '.') ? '' : $dir;
        }
    }

    $duongDan = ltrim($duongDan, '/');
    if ($duongDan === '') {
        return $goc === '' ? '/' : $goc;
    }

    return ($goc === '' ? '' : $goc) . '/' . $duongDan;
}

/**
 * Tạo URL đầy đủ bao gồm giao thức, tên miền/cổng và đường dẫn
 */
function app_full_url(string $duongDan = ''): string
{
    $laHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
    $giaoThuc = $laHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $duongDanTuongDoi = app_url($duongDan);
    
    return $giaoThuc . '://' . $host . $duongDanTuongDoi;
}

/**
 * Tạo hoặc lấy mã chống CSRF hiện tại trong phiên
 */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Sinh thẻ HTML input ẩn chứa mã CSRF
 */
function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Kiểm tra tính hợp lệ của mã CSRF gửi lên
 */
function csrf_kiem_tra(?string $token): bool
{
    $tokenHienTai = $_SESSION['_csrf_token'] ?? '';
    if (!is_string($tokenHienTai) || $tokenHienTai === '') {
        return false;
    }

    if (!is_string($token) || $token === '') {
        return false;
    }

    return hash_equals($tokenHienTai, $token);
}

/**
 * Gán thông báo nhanh (flash message)
 */
function set_flash(string $loai, string $noiDung): void
{
    $_SESSION['_flash_message'] = [
        'loai'    => $loai,   // 'success', 'danger', 'warning', 'info'
        'noiDung' => $noiDung,
    ];
}

/**
 * Lấy và xóa thông báo flash
 */
function get_flash(): ?array
{
    if (isset($_SESSION['_flash_message']) && is_array($_SESSION['_flash_message'])) {
        $thongBao = $_SESSION['_flash_message'];
        unset($_SESSION['_flash_message']);
        return $thongBao;
    }

    return null;
}

/**
 * Escape chuỗi HTML an toàn chống XSS
 */
function e(?string $giaTri): string
{
    return htmlspecialchars((string) ($giaTri ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
