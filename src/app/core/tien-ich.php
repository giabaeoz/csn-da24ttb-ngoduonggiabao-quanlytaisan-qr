<?php
/**
 * Thư viện các hàm tiện ích dùng chung trong toàn hệ thống.
 * Bao gồm thoát chuỗi HTML chống XSS, định tuyến URL, chuyển hướng an toàn,
 * đọc tham số và sinh mã nghiệp vụ tự động theo định dạng chuẩn.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * Thoát các ký tự đặc biệt trong HTML để ngăn ngừa tấn công XSS.
 *
 * @param mixed $chuoi Giá trị cần hiển thị
 * @return string Chuỗi an toàn
 */
function e(mixed $chuoi): string {
    if ($chuoi === null) {
        return '';
    }
    return htmlspecialchars((string)$chuoi, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Tạo đường dẫn URL tương đối hoặc chuẩn hóa cho toàn hệ thống.
 *
 * @param string $duongDan Đường dẫn tương đối từ thư mục gốc web (ví dụ: 'muon-tra/index.php')
 * @return string Đường dẫn hoàn chỉnh
 */
function url(string $duongDan = ''): string {
    $duongDan = ltrim($duongDan, '/');
    return '/' . $duongDan;
}

/**
 * Chuyển hướng trình duyệt đến một URL khác và dừng thực thi script.
 *
 * @param string $duongDan URL cần chuyển hướng tới
 */
function chuyen_huong(string $duongDan): void {
    if (!preg_match('~^https?://~i', $duongDan) && !str_starts_with($duongDan, '/')) {
        $duongDan = url($duongDan);
    }
    if (!headers_sent()) {
        header('Location: ' . $duongDan);
    } else {
        echo '<script>window.location.href = ' . json_encode($duongDan) . ';</script>';
    }
    exit;
}

/**
 * Đọc và ép kiểu một tham số sang số nguyên an toàn từ mảng dữ liệu.
 *
 * @param string $khoa Tên tham số
 * @param int $macDinh Giá trị mặc định nếu tham số không tồn tại hoặc không hợp lệ
 * @param array|null $nguon Mảng dữ liệu nguồn (mặc định lấy $_GET hoặc $_POST)
 * @return int
 */
function doc_so_nguyen(string $khoa, int $macDinh = 0, ?array $nguon = null): int {
    if ($nguon === null) {
        $nguon = $_REQUEST;
    }
    if (!isset($nguon[$khoa]) || !is_numeric($nguon[$khoa])) {
        return $macDinh;
    }
    return (int)$nguon[$khoa];
}

/**
 * Sinh mã định danh nghiệp vụ chuẩn: TIEN_TO-YYYYMMDD-XXXXXXXX
 * Ví dụ: PM-20261109-1A2B3C4D, SC-20261109-F9E8D7C6
 *
 * @param string $tienTo Tiền tố nghiệp vụ (PM, DC, KK, SC)
 * @param int $doDaiNgauNhien Độ dài chuỗi ngẫu nhiên (mặc định 8 ký tự hex)
 * @return string Mã nghiệp vụ duy nhất
 */
function sinh_ma(string $tienTo, int $doDaiNgauNhien = 8): string {
    $ngayThang = date('Ymd');
    $byteCanLay = (int)ceil($doDaiNgauNhien / 2);
    $ngauNhien = strtoupper(substr(bin2hex(random_bytes($byteCanLay)), 0, $doDaiNgauNhien));
    return sprintf('%s-%s-%s', strtoupper(trim($tienTo)), $ngayThang, $ngauNhien);
}

/**
 * Bí danh tiện lợi cho sinh_ma.
 */
function sinh_ma_nghiep_vu(string $tienTo, int $doDaiNgauNhien = 8): string {
    return sinh_ma($tienTo, $doDaiNgauNhien);
}

/**
 * Định dạng chuỗi ngày giờ từ MySQL sang định dạng hiển thị tiếng Việt.
 *
 * @param string|null $chuoiNgayGio Chuỗi datetime (Y-m-d H:i:s)
 * @param string $dinhDang Định dạng đầu ra (mặc định 'd/m/Y H:i')
 * @return string Chuỗi ngày giờ hiển thị hoặc '-' nếu rỗng
 */
function dinh_dang_ngay_gio(?string $chuoiNgayGio, string $dinhDang = 'd/m/Y H:i'): string {
    if (empty($chuoiNgayGio)) {
        return '-';
    }
    $thoiDiem = strtotime($chuoiNgayGio);
    if ($thoiDiem === false) {
        return '-';
    }
    return date($dinhDang, $thoiDiem);
}

/**
 * Định dạng ngày sang định dạng d/m/Y.
 *
 * @param string|null $chuoiNgay Chuỗi date (Y-m-d)
 * @param string $dinhDang Định dạng đầu ra (mặc định 'd/m/Y')
 * @return string Chuỗi ngày hiển thị hoặc '-' nếu rỗng
 */
function dinh_dang_ngay(?string $chuoiNgay, string $dinhDang = 'd/m/Y'): string {
    return dinh_dang_ngay_gio($chuoiNgay, $dinhDang);
}

/**
 * Bí danh tiện lợi cho dinh_dang_ngay_gio (hỗ trợ view ngắn gọn).
 */
function f_ngay_gio(?string $chuoiNgayGio, string $dinhDang = 'd/m/Y H:i'): string {
    return dinh_dang_ngay_gio($chuoiNgayGio, $dinhDang);
}

/**
 * Bí danh tiện lợi cho dinh_dang_ngay.
 */
function f_ngay(?string $chuoiNgay, string $dinhDang = 'd/m/Y'): string {
    return dinh_dang_ngay($chuoiNgay, $dinhDang);
}


