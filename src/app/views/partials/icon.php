<?php
/**
 * Thành phần hiển thị biểu tượng SVG dùng chung từ sprite (src/assets/icons/sprite.svg).
 * Mọi biểu tượng trên giao diện chỉ được chèn qua hàm icon().
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

if (!function_exists('icon')) {
    /**
     * Sinh thẻ SVG tham chiếu đến biểu tượng trong sprite.svg.
     *
     * @param string $ten Tên định danh biểu tượng (ví dụ: 'layout-dashboard', 'plus', 'search')
     * @param string $lop Lớp CSS bổ sung (ví dụ: 'bieu-tuong-sm', 'bieu-tuong-lg')
     * @return string Chuỗi HTML thẻ <svg>
     */
    function icon(string $ten, string $lop = ''): string {
        $tenAnToan = preg_replace('/[^a-z0-9\-]/i', '', $ten) ?? '';
        $lopDayDu = trim('bieu-tuong ' . $lop);
        return sprintf(
            '<svg class="%s" aria-hidden="true" focusable="false"><use href="%s#%s"/></svg>',
            e($lopDayDu),
            e(url('assets/icons/sprite.svg')),
            e($tenAnToan)
        );
    }
}

