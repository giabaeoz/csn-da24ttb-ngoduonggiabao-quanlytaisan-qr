<?php

declare(strict_types=1);

/**
 * Quản lý kết nối Cơ sở dữ liệu MySQLi an toàn
 */

function db_ket_noi(): mysqli
{
    static $ketNoi = null;

    if ($ketNoi instanceof mysqli) {
        return $ketNoi;
    }

    $cauHinhDb = [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'user'     => 'root',
        'password' => '',
        'database' => 'dacsn_qlts_qr',
    ];

    // Nạp cấu hình ghi đè cục bộ nếu có
    $tepCauHinhCucBo = __DIR__ . '/../config.local.php';
    if (is_file($tepCauHinhCucBo)) {
        $cauHinhCucBo = require $tepCauHinhCucBo;
        if (is_array($cauHinhCucBo)) {
            $cauHinhDb = array_replace($cauHinhDb, $cauHinhCucBo);
        }
    }

    // Tắt báo cáo lỗi mặc định để xử lý an toàn
    mysqli_report(MYSQLI_REPORT_OFF);

    $ketNoi = @new mysqli(
        (string) $cauHinhDb['host'],
        (string) $cauHinhDb['user'],
        (string) $cauHinhDb['password'],
        (string) $cauHinhDb['database'],
        (int) $cauHinhDb['port']
    );

    if ($ketNoi->connect_errno) {
        // Ghi log chi tiết vào log máy chủ, không hiển thị mật khẩu/chi tiết cho người dùng cuối
        error_log(sprintf(
            '[DB_ERROR] Kết nối CSDL thất bại (Mã: %d): %s',
            $ketNoi->connect_errno,
            $ketNoi->connect_error
        ));

        // Trả về trang thông báo thân thiện, an toàn
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>Lỗi kết nối</title>'
           . '<style>body{font-family:sans-serif;padding:40px;background:#f8fafc;color:#1e293b;text-align:center;}'
           . '.box{max-width:500px;margin:50px auto;background:#fff;padding:30px;border-radius:10px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);}'
           . 'h1{color:#ef4444;font-size:22px;}p{font-size:15px;color:#64748b;line-height:1.6;}</style></head>'
           . '<body><div class="box"><h1>Không thể kết nối máy chủ dữ liệu</h1>'
           . '<p>Hệ thống tạm thời không thể kết nối tới cơ sở dữ liệu. Vui lòng kiểm tra lại dịch vụ MySQL hoặc liên hệ quản trị viên.</p>'
           . '</div></body></html>';
        exit;
    }

    // Thiết lập bảng mã UTF-8 đầy đủ
    $ketNoi->set_charset('utf8mb4');

    return $ketNoi;
}

