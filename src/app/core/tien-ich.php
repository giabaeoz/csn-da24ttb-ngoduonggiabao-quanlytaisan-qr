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
 * Lấy tiền tố đường dẫn cơ sở (Base URL path) của ứng dụng web.
 * Tự động nhận diện khi chạy dưới thư mục con của Apache (ví dụ: /quanlytaisan-qr hoặc /quanlytaisan-qr/src)
 * hoặc khi chạy tại thư mục gốc / máy chủ nội bộ PHP (ví dụ: '').
 *
 * @return string Tiền tố đường dẫn không có dấu gạch chéo ở cuối (ví dụ: '/quanlytaisan-qr' hoặc '')
 */
function duong_dan_goc(): string {
    static $basePath = null;
    if ($basePath !== null) {
        return $basePath;
    }

    if (PHP_SAPI === 'cli' || empty($_SERVER['SCRIPT_NAME'])) {
        $basePath = '';
        return $basePath;
    }

    $cauHinhBase = cau_hinh('ung_dung.duong_dan_goc', null);
    if ($cauHinhBase !== null) {
        $basePath = rtrim((string)$cauHinhBase, '/');
        return $basePath;
    }

    $srcDir = str_replace('\\', '/', realpath(dirname(__DIR__, 2)));
    $scriptFile = !empty($_SERVER['SCRIPT_FILENAME']) ? str_replace('\\', '/', (string)realpath($_SERVER['SCRIPT_FILENAME'])) : '';

    if ($srcDir !== '' && $scriptFile !== '' && str_starts_with($scriptFile, $srcDir)) {
        $rel = ltrim(substr($scriptFile, strlen($srcDir)), '/');
        $scriptName = str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME']);
        if ($rel !== '' && str_ends_with($scriptName, $rel)) {
            $prefix = substr($scriptName, 0, -strlen($rel));
            $basePath = rtrim($prefix, '/');
            return $basePath;
        }
    }

    if (!empty($_SERVER['CONTEXT_PREFIX'])) {
        $basePath = rtrim((string)$_SERVER['CONTEXT_PREFIX'], '/');
        return $basePath;
    }

    $basePath = '';
    return $basePath;
}

/**
 * Tạo đường dẫn URL tương đối hoặc chuẩn hóa cho toàn hệ thống.
 *
 * @param string $duongDan Đường dẫn tương đối từ thư mục gốc web (ví dụ: 'muon-tra/index.php')
 * @return string Đường dẫn hoàn chỉnh
 */
function url(string $duongDan = ''): string {
    $duongDan = ltrim($duongDan, '/');
    $goc = duong_dan_goc();
    if ($duongDan === '') {
        return $goc !== '' ? $goc . '/' : '/';
    }
    return ($goc !== '' ? $goc : '') . '/' . $duongDan;
}

/**
 * Chuyển hướng trình duyệt đến một URL khác và dừng thực thi script.
 *
 * @param string $duongDan URL cần chuyển hướng tới
 */
function chuyen_huong(string $duongDan): void {
    if (!preg_match('~^https?://~i', $duongDan)) {
        $goc = duong_dan_goc();
        if ($goc !== '' && str_starts_with($duongDan, $goc . '/')) {
            // Đã có tiền tố gốc
        } elseif (str_starts_with($duongDan, '/')) {
            $duongDan = ($goc !== '' ? $goc : '') . $duongDan;
        } else {
            $duongDan = url($duongDan);
        }
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

/**
 * Kiểm tra xem yêu cầu hiện tại có phải là HTTP POST hay không.
 */
function la_post(): bool {
    return (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST');
}

/**
 * Trả về nhãn tiếng Việt cho loại kiểm kê tài sản.
 */
function nhan_loai_kiem_ke(?string $loai): string {
    return match ($loai) {
        'CUOI_BUOI' => 'Cuối buổi trực',
        'DINH_KY'   => 'Định kỳ',
        'DOT_XUAT'  => 'Đột xuất',
        default     => $loai ?? 'Khác',
    };
}

/**
 * Trả về nhãn tiếng Việt cho trạng thái đợt kiểm kê tài sản.
 */
function nhan_trang_thai_kiem_ke(?string $trangThai): string {
    return match ($trangThai) {
        'DANG_KIEM_KE' => 'Đang kiểm kê',
        'HOAN_TAT'     => 'Hoàn tất',
        'DA_HUY'       => 'Đã hủy',
        default        => $trangThai ?? 'Không xác định',
    };
}

/**
 * Trả về nhãn tiếng Việt cho kết quả đối chiếu tài sản trong đợt kiểm kê.
 */
function nhan_ket_qua_kiem_ke(?string $ketQua, ?string $tinhTrang = null): string {
    return match ($ketQua) {
        'TIM_THAY'      => ($tinhTrang === 'HU_HONG' ? 'Có mặt (Hư hỏng)' : 'Có mặt (Tốt)'),
        'CHUA_TIM_THAY' => 'Chưa tìm thấy',
        'CHUA_KIEM_TRA' => 'Chưa kiểm tra',
        default         => $ketQua ?? 'Chưa rõ',
    };
}

/**
 * Trả về nhãn tiếng Việt cho loại sự cố tài sản.
 */
function nhan_loai_su_co(?string $loai): string {
    return match ($loai) {
        'HU_HONG'    => 'Hư hỏng',
        'MAT'        => 'Mất / Thất lạc',
        'SAI_VI_TRI' => 'Sai vị trí',
        default      => $loai ?? 'Khác',
    };
}

/**
 * Trả về nhãn tiếng Việt cho trạng thái xử lý sự cố tài sản.
 */
function nhan_trang_thai_su_co(?string $trangThai): string {
    return match ($trangThai) {
        'CHUA_XU_LY' => 'Chưa xử lý',
        'DANG_XU_LY' => 'Đang xử lý',
        'DA_XU_LY'   => 'Đã xử lý xong',
        default      => $trangThai ?? 'Không xác định',
    };
}

/**
 * Trả về nhãn tiếng Việt cho trạng thái tài sản.
 */
function nhan_trang_thai_tai_san(?string $trangThai): string {
    return match ($trangThai) {
        'SAN_SANG'      => 'Sẵn sàng',
        'DANG_MUON'     => 'Đang cho mượn',
        'BAO_TRI'       => 'Đang bảo trì',
        'NGUNG_SU_DUNG' => 'Ngừng sử dụng',
        default         => $trangThai ?? 'Không xác định',
    };
}

/**
 * Trả về nhãn tiếng Việt cho tình trạng tài sản.
 */
function nhan_tinh_trang_tai_san(?string $tinhTrang): string {
    return match ($tinhTrang) {
        'TOT'     => 'Tốt',
        'HU_HONG' => 'Hư hỏng',
        default   => $tinhTrang ?? 'Không rõ',
    };
}


