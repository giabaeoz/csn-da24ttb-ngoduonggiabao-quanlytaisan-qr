<?php
/**
 * Quản lý kết nối cơ sở dữ liệu MySQLi an toàn.
 * Sử dụng mô hình Singleton để tái sử dụng một kết nối duy nhất trong suốt
 * vòng đời một lượt request HTTP, tránh lãng phí tài nguyên máy chủ.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * Trả về đối tượng kết nối cơ sở dữ liệu MySQLi duy nhất (Singleton).
 * Tự động thiết lập charset utf8mb4 và múi giờ Việt Nam (+07:00).
 *
 * @return mysqli Đối tượng kết nối MySQLi đã sẵn sàng truy vấn
 */
function db(): mysqli {
    static $ketNoi = null;

    if ($ketNoi instanceof mysqli) {
        return $ketNoi;
    }

    $c = cau_hinh('csdl', []);
    $host     = (string)($c['host'] ?? '127.0.0.1');
    $port     = (int)($c['port'] ?? 3306);
    $user     = (string)($c['user'] ?? 'root');
    $password = (string)($c['password'] ?? '');
    $database = (string)($c['database'] ?? 'dacsn_qlts_qr');
    $charset  = (string)($c['charset'] ?? 'utf8mb4');

    // Bật chế độ ném ngoại lệ khi gặp lỗi SQL để xử lý bằng try/catch
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $ketNoi = new mysqli($host, $user, $password, $database, $port);
        $ketNoi->set_charset($charset);
        $ketNoi->query("SET time_zone = '+07:00'");
    } catch (mysqli_sql_exception $e) {
        error_log('[CSDL ERROR] Không thể kết nối cơ sở dữ liệu: ' . $e->getMessage());
        if (cau_hinh('ung_dung.moi_truong') === 'phat_trien') {
            die('Lỗi kết nối cơ sở dữ liệu: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
        die('Lỗi kết nối cơ sở dữ liệu. Vui lòng liên hệ quản trị viên.');
    }

    return $ketNoi;
}

