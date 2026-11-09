<?php
/**
 * Cơ chế phòng chống tấn công giả mạo yêu cầu chéo trang (CSRF Protection).
 * Tạo và xác thực mã CSRF token ngẫu nhiên dựa trên CSPRNG (bin2hex + random_bytes)
 * và so sánh an toàn bằng hash_equals() để chống tấn công phân tích thời gian (timing attack).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * Lấy mã CSRF token hiện tại của phiên hoặc sinh mới nếu chưa tồn tại.
 *
 * @return string Mã token bảo mật 64 ký tự hex
 */
function csrf_token(): string {
    phien_khoi_dong();
    if (empty($_SESSION['__csrf_token']) || !is_string($_SESSION['__csrf_token'])) {
        $_SESSION['__csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['__csrf_token'];
}

/**
 * Sinh chuỗi HTML chứa thẻ input ẩn mang CSRF token để nhúng vào form POST.
 *
 * @return string Thẻ HTML input hidden
 */
function csrf_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Kiểm tra tính hợp lệ của CSRF token được gửi lên từ client.
 * Hỗ trợ nhận token từ trường form POST hoặc tiêu đề HTTP (X-CSRF-TOKEN).
 *
 * @return bool True nếu token hợp lệ, ngược lại False
 */
function csrf_kiem_tra(): bool {
    phien_khoi_dong();
    $tokenTrongPhien = $_SESSION['__csrf_token'] ?? '';
    if (!is_string($tokenTrongPhien) || $tokenTrongPhien === '') {
        return false;
    }

    // 1. Kiểm tra từ trường POST
    $tokenGuiLen = $_POST['csrf_token'] ?? null;

    // 2. Nếu không có trong POST, kiểm tra trong Header HTTP (hỗ trợ gọi AJAX / fetch)
    if ($tokenGuiLen === null) {
        $tokenGuiLen = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }

    if (!is_string($tokenGuiLen) || $tokenGuiLen === '') {
        return false;
    }

    return hash_equals($tokenTrongPhien, $tokenGuiLen);
}
