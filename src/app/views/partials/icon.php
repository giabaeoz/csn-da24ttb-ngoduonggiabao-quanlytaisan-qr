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

if (!function_exists('minh_hoa_hop_trong')) {
    /**
     * Hiển thị hình minh họa chiếc hộp mở nắp phát sáng kèm dòng thông báo khi bảng/khối trống.
     */
    function minh_hoa_hop_trong(string $thongDiep = 'Không có dữ liệu.'): string {
        return '<div class="empty-state-hop">'
            . '<svg viewBox="0 0 80 68" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
            . '<ellipse cx="40" cy="60" rx="26" ry="5" fill="#E0EDFD"/>'
            . '<path d="M40 16L62 27V49L40 60L18 49V27L40 16Z" fill="#DBEAFE" stroke="#60A5FA" stroke-width="1.6" stroke-linejoin="round"/>'
            . '<path d="M40 38V60" stroke="#60A5FA" stroke-width="1.6"/>'
            . '<path d="M18 27L40 38L62 27" stroke="#60A5FA" stroke-width="1.6" stroke-linejoin="round"/>'
            . '<path d="M18 27L10 19L32 9L40 16" fill="#EFF6FF" stroke="#60A5FA" stroke-width="1.5" stroke-linejoin="round"/>'
            . '<path d="M62 27L70 19L48 9L40 16" fill="#EFF6FF" stroke="#60A5FA" stroke-width="1.5" stroke-linejoin="round"/>'
            . '<circle cx="40" cy="24" r="3" fill="#93C5FD"/>'
            . '<path d="M40 5V11M29 9L32 13M51 9L48 13" stroke="#93C5FD" stroke-width="1.5" stroke-linecap="round"/>'
            . '</svg>'
            . '<div>' . e($thongDiep) . '</div>'
            . '</div>';
    }
}


