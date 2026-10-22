<?php
// Trang kiểm tra: PHP có chạy không, có mysqli không, nối được MySQL không.
ini_set('display_errors', 1);
error_reporting(E_ALL);

$coMysqli = extension_loaded('mysqli');
$ketNoi = null;
$loi = '';
$dsDb = [];

$cauHinhDb = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'user' => 'root',
    'password' => '',
    'database' => 'dacsn_qlts_qr',
];

$tepCauHinhCucBo = __DIR__ . '/config.local.php';
if (is_file($tepCauHinhCucBo)) {
    $cauHinhCucBo = require $tepCauHinhCucBo;
    if (is_array($cauHinhCucBo)) {
        $cauHinhDb = array_replace($cauHinhDb, $cauHinhCucBo);
    }
}

if ($coMysqli) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $ketNoi = @new mysqli(
        $cauHinhDb['host'],
        $cauHinhDb['user'],
        $cauHinhDb['password'],
        $cauHinhDb['database'],
        (int)$cauHinhDb['port']
    );
    if ($ketNoi->connect_errno) {
        $loi = $ketNoi->connect_error;
        $ketNoi = null;
    } else {
        foreach ($ketNoi->query('SHOW DATABASES') as $r) {
            $dsDb[] = $r['Database'];
        }
    }
}

function dong($ten, $ok, $chiTiet = '')
{
    echo '<tr><td>' . htmlspecialchars($ten) . '</td><td style="color:' . ($ok ? '#0a7d33' : '#c62828') . ';font-weight:600">'
        . ($ok ? 'OK' : 'LỖI') . '</td><td>' . htmlspecialchars($chiTiet) . '</td></tr>';
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <title>Kiểm tra môi trường</title>
    <style>
        body {
            font-family: system-ui, Segoe UI, sans-serif;
            max-width: 720px;
            margin: 40px auto;
            padding: 0 16px;
            color: #222
        }

        table {
            border-collapse: collapse;
            width: 100%
        }

        td {
            border: 1px solid #ddd;
            padding: 8px 12px
        }
    </style>
</head>

<body>
    <h2>quanlytaisan-qr: kiểm tra môi trường</h2>
    <table>
        <?php
        dong('PHP chạy được', true, 'Phiên bản ' . PHP_VERSION);
        dong('Thư viện mysqli', $coMysqli, $coMysqli ? 'Đã bật' : 'Chưa bật (mở php.ini, bỏ dấu ; trước extension=mysqli)');
        dong(
            'Kết nối MySQL (' . $cauHinhDb['user'] . '@' . $cauHinhDb['host'] . ':' . $cauHinhDb['port'] . ')',
            (bool)$ketNoi,
            $ketNoi ? 'Phiên bản ' . $ketNoi->server_info : $loi
        );
        dong(
            'CSDL ' . $cauHinhDb['database'],
            in_array($cauHinhDb['database'], $dsDb, true),
            in_array($cauHinhDb['database'], $dsDb, true) ? 'Đã có' : 'Chưa tìm thấy'
        );
        ?>
    </table>
    <p>Các CSDL hiện có: <?= $dsDb ? htmlspecialchars(implode(', ', $dsDb)) : '(không đọc được)' ?></p>
    <p style="color:#666">Đây chỉ là file thử. Khi bắt đầu dự án thật, sẽ thay bằng trang chủ.</p>
</body>

</html>