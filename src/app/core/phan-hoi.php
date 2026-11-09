<?php
/**
 * Chuẩn hóa các phản hồi HTTP (JSON API, trang lỗi 403, 404).
 * Đảm bảo các luồng phản hồi luôn gửi mã HTTP code chính xác và dừng script đúng lúc.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * Trả về phản hồi dạng JSON với mã trạng thái HTTP tương ứng.
 * Thường dùng cho các API AJAX mượn trả, quét QR, tìm kiếm nhanh.
 *
 * @param array $duLieu Dữ liệu mảng cần mã hóa JSON
 * @param int $maTrangThai Mã trạng thái HTTP (200, 400, 403, 404, 500...)
 */
function phan_hoi_json(array $duLieu, int $maTrangThai = 200): void {
    if (!headers_sent()) {
        http_response_code($maTrangThai);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($duLieu, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Hiển thị trang lỗi 403 Forbidden hoặc trả JSON nếu là AJAX request.
 *
 * @param string $thongDiep Thông báo lỗi chi tiết
 */
function phan_hoi_loi_403(string $thongDiep = 'Bạn không có quyền truy cập chức năng này.'): void {
    http_response_code(403);
    
    // Nếu là request AJAX thì trả về JSON
    $laAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if ($laAjax) {
        phan_hoi_json([
            'thanh_cong' => false,
            'loi'        => $thongDiep,
        ], 403);
    }

    $tieuDeTrang = '403 - Truy cập bị từ chối';
    $noiDungLoi = $thongDiep;
    $tepGiaoDien = dirname(__DIR__) . '/views/403.php';

    if (is_file($tepGiaoDien)) {
        require $tepGiaoDien;
    } else {
        echo '<h1>403 Forbidden</h1><p>' . htmlspecialchars($thongDiep, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    exit;
}

/**
 * Hiển thị trang lỗi 404 Not Found hoặc trả JSON nếu là AJAX request.
 *
 * @param string $thongDiep Thông báo lỗi chi tiết
 */
function phan_hoi_loi_404(string $thongDiep = 'Trang hoặc tài nguyên không tồn tại.'): void {
    http_response_code(404);

    $laAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if ($laAjax) {
        phan_hoi_json([
            'thanh_cong' => false,
            'loi'        => $thongDiep,
        ], 404);
    }

    $tieuDeTrang = '404 - Không tìm thấy trang';
    $noiDungLoi = $thongDiep;
    $tepGiaoDien = dirname(__DIR__) . '/views/404.php';

    if (is_file($tepGiaoDien)) {
        require $tepGiaoDien;
    } else {
        echo '<h1>404 Not Found</h1><p>' . htmlspecialchars($thongDiep, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    exit;
}
