<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

/**
 * Menu chung cho ba giao diện. URL gốc được tính bởi app_url() để chuyển
 * giữa các thư mục vai trò mà không mất các mục trong thanh điều hướng.
 * Quyền truy cập vẫn được kiểm tra tại từng trang bằng kiem_tra_quyen().
 */
function app_navigation(string $role): array
{
    $items = match ($role) {
        VAI_TRO_NHAN_VIEN => [
            ['nhan-vien/index.php', 'Tổng quan', 'Tổng quan'],
            ['nhan-vien/tai-san.php', 'Tài sản', 'Tài sản'],
            ['nhan-vien/nguoi-muon.php', 'Người mượn', 'Người mượn'],
            ['nhan-vien/muon-tra.php?tab=muon', 'Mượn – trả', 'Mượn – trả tài sản'],
            ['nhan-vien/dieu-chuyen.php', 'Điều chuyển', 'Điều chuyển tài sản'],
            ['nhan-vien/kiem-ke.php', 'Kiểm kê', 'Kiểm kê tài sản'],
            ['nhan-vien/tra-cuu.php', 'Tra cứu, thống kê', 'Tra cứu, thống kê'],
        ],
        VAI_TRO_QUAN_LY => [
            ['quan-ly/index.php', 'Tổng quan', 'Tổng quan'],
            ['quan-ly/tai-san.php', 'Tài sản', 'Tài sản'],
            ['quan-ly/danh-muc.php?kind=loai', 'Loại tài sản', 'Loại tài sản'],
            ['quan-ly/danh-muc.php?kind=phong-hoc', 'Phòng học', 'Phòng học'],
            ['quan-ly/danh-muc.php?kind=phong-truc', 'Phòng trực', 'Phòng trực'],
            ['quan-ly/nguoi-muon.php', 'Người mượn', 'Người mượn'],
        ],
        VAI_TRO_QUAN_TRI => [
            ['admin/index.php', 'Tổng quan', 'Tổng quan'],
            ['admin/tai-khoan.php', 'Tài khoản', 'Tài khoản'],
            ['admin/tai-san.php', 'Tài sản', 'Tài sản'],
            ['admin/nguoi-muon.php', 'Người mượn', 'Người mượn'],
            ['admin/danh-muc.php?kind=phong-truc', 'Phòng trực', 'Phòng trực'],
            ['admin/danh-muc.php?kind=phong-hoc', 'Phòng học', 'Phòng học'],
        ],
        default => [],
    };

    return array_map(static fn(array $item): array => [
        'href' => app_url($item[0]),
        'label' => $item[1],
        'page' => $item[2],
    ], $items);
}
