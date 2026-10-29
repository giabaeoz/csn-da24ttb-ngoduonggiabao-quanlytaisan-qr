<?php

declare(strict_types=1);

// 1. Cấu hình hiển thị lỗi khi kiểm tra môi trường
ini_set('display_errors', '1');
error_reporting(E_ALL);

// 2. Cấu hình kết nối cơ sở dữ liệu mặc định
$cauHinhDb = [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'user'     => 'root',
    'password' => '',
    'database' => 'dacsn_qlts_qr',
];

// Nạp cấu hình cục bộ nếu có (config.local.php)
$tepCauHinhCucBo = __DIR__ . '/config.local.php';
if (is_file($tepCauHinhCucBo)) {
    $cauHinhCucBo = require $tepCauHinhCucBo;
    if (is_array($cauHinhCucBo)) {
        $cauHinhDb = array_replace($cauHinhDb, $cauHinhCucBo);
    }
}

// 3. Thực hiện kiểm tra các dịch vụ
$coMysqli   = extension_loaded('mysqli');
$ketNoi     = null;
$loiKetNoi  = '';
$dsDb       = [];

if ($coMysqli) {
    mysqli_report(MYSQLI_REPORT_OFF);

    $ketNoi = @new mysqli(
        $cauHinhDb['host'],
        $cauHinhDb['user'],
        $cauHinhDb['password'],
        $cauHinhDb['database'],
        (int) $cauHinhDb['port']
    );

    if ($ketNoi->connect_errno) {
        $loiKetNoi = $ketNoi->connect_error;
        $ketNoi    = null;
    } else {
        $ketQuaDb = $ketNoi->query('SHOW DATABASES');
        if ($ketQuaDb) {
            while ($dong = $ketQuaDb->fetch_assoc()) {
                $dsDb[] = $dong['Database'];
            }
        }
    }
}

$coDbMucTieu = in_array($cauHinhDb['database'], $dsDb, true);

// 4. Tổng hợp danh sách tiêu chí kiểm tra
$danhSachKiemTra = [
    [
        'ten'      => 'Môi trường PHP',
        'hopLe'    => true,
        'chiTiet'  => 'Phiên bản PHP ' . PHP_VERSION,
        'huongDan' => 'Yêu cầu PHP 8.2 trở lên.'
    ],
    [
        'ten'      => 'Thư viện MySQLi',
        'hopLe'    => $coMysqli,
        'chiTiet'  => $coMysqli ? 'Đã được kích hoạt' : 'Chưa bật extension mysqli',
        'huongDan' => $coMysqli ? 'Sẵn sàng kết nối cơ sở dữ liệu.' : 'Mở file php.ini và bỏ dấu chấm phẩy (;) trước extension=mysqli.'
    ],
    [
        'ten'      => 'Kết nối máy chủ MySQL',
        'hopLe'    => (bool) $ketNoi,
        'chiTiet'  => $ketNoi
            ? 'Kết nối thành công (Phiên bản: ' . $ketNoi->server_info . ')'
            : 'Không thể kết nối' . ($loiKetNoi ? ': ' . $loiKetNoi : ''),
        'huongDan' => $ketNoi
            ? "Máy chủ: {$cauHinhDb['user']}@{$cauHinhDb['host']}:{$cauHinhDb['port']}"
            : 'Kiểm tra lại tài khoản, mật khẩu trong config.local.php hoặc bật dịch vụ MySQL.'
    ],
    [
        'ten'      => 'Cơ sở dữ liệu mục tiêu',
        'hopLe'    => $coDbMucTieu,
        'chiTiet'  => $coDbMucTieu
            ? "Đã tìm thấy CSDL `{$cauHinhDb['database']}`"
            : "Chưa có CSDL `{$cauHinhDb['database']}`",
        'huongDan' => $coDbMucTieu
            ? 'Cơ sở dữ liệu đã sẵn sàng để sử dụng.'
            : 'Vui lòng import file setup/database_dacsn_qlts_qr.sql vào phpMyAdmin.'
    ],
];

// Kiểm tra toàn bộ hệ thống đã đạt chuẩn chưa
$tatCaHopLe = $coMysqli && (bool) $ketNoi && $coDbMucTieu;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm tra môi trường - Quản lý tài sản bằng mã QR</title>
    <style>
        :root {
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
            --danger-color: #ef4444;
            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --primary-color: #3b82f6;
            --primary-bg: #eff6ff;
            --radius-md: 12px;
            --radius-sm: 8px;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            line-height: 1.6;
            padding: 40px 20px;
        }

        .container {
            max-width: 860px;
            margin: 0 auto;
        }

        /* Tiêu đề ứng dụng */
        .header {
            margin-bottom: 28px;
            text-align: center;
        }

        .header .badge {
            display: inline-block;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 9999px;
            background: var(--primary-bg);
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .header h1 {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .header p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* Banner thông báo trạng thái chung */
        .status-banner {
            border-radius: var(--radius-md);
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 14px;
            border: 1px solid;
            box-shadow: var(--shadow-sm);
        }

        .status-banner.success {
            background-color: var(--success-bg);
            border-color: var(--success-border);
            color: #065f46;
        }

        .status-banner.danger {
            background-color: var(--danger-bg);
            border-color: var(--danger-border);
            color: #991b1b;
        }

        .status-banner .icon {
            font-size: 1.5rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .status-banner .text strong {
            display: block;
            font-size: 1rem;
            margin-bottom: 2px;
        }

        .status-banner .text p {
            font-size: 0.88rem;
            opacity: 0.95;
        }

        /* Thẻ nội dung chính */
        .card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
            background-color: #fafbfc;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-main);
        }

        /* Bảng kiểm tra */
        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            padding: 12px 20px;
            border-bottom: 1px solid var(--border-color);
            background: #fafbfc;
        }

        td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.92rem;
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #fcfdfe;
        }

        .item-name {
            font-weight: 600;
            color: var(--text-main);
        }

        .item-guide {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* Nhãn trạng thái (Pill) */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .pill-success {
            background-color: var(--success-bg);
            color: #047857;
            border: 1px solid var(--success-border);
        }

        .pill-danger {
            background-color: var(--danger-bg);
            color: #b91c1c;
            border: 1px solid var(--danger-border);
        }

        /* Danh sách cơ sở dữ liệu hiện có */
        .db-box {
            padding: 18px 20px;
        }

        .db-box-title {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.82rem;
            padding: 4px 10px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-sm);
            color: #334155;
        }

        .tag.target {
            background: var(--primary-bg);
            border-color: #bfdbfe;
            color: #1d4ed8;
            font-weight: 600;
        }

        /* Chân trang */
        .footer {
            text-align: center;
            font-size: 0.84rem;
            color: var(--text-muted);
            padding-top: 12px;
        }

        .footer span {
            font-weight: 600;
            color: var(--text-main);
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Phần tiêu đề -->
        <header class="header">
            <span class="badge">Đồ Án Cơ Sở Ngành</span>
            <h1>Hệ Thống Quản Lý Tài Sản Bằng Mã QR</h1>
            <p>Kiểm tra cấu hình môi trường máy chủ và kết nối cơ sở dữ liệu</p>
        </header>

        <!-- Banner thông báo tổng quan -->
        <?php if ($tatCaHopLe): ?>
            <div class="status-banner success">
                <div class="icon">&#10004;</div>
                <div class="text">
                    <strong>Môi trường hoàn toàn sẵn sàng!</strong>
                    <p>Tất cả các dịch vụ PHP, MySQLi và cơ sở dữ liệu <code><?= htmlspecialchars($cauHinhDb['database']) ?></code> đều hoạt động bình thường.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="status-banner danger">
                <div class="icon">&#9888;</div>
                <div class="text">
                    <strong>Cần cấu hình thêm môi trường!</strong>
                    <p>Một hoặc nhiều mục kiểm tra bên dưới chưa đạt yêu cầu. Vui lòng xem hướng dẫn chi tiết.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Bảng danh sách kiểm tra -->
        <div class="card">
            <div class="card-header">
                <h2>Kết Quả Kiểm Tra Hệ Thống</h2>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 28%;">Thành phần</th>
                            <th style="width: 18%;">Trạng thái</th>
                            <th style="width: 54%;">Chi tiết &amp; Hướng dẫn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($danhSachKiemTra as $muc): ?>
                            <tr>
                                <td>
                                    <div class="item-name"><?= htmlspecialchars($muc['ten']) ?></div>
                                </td>
                                <td>
                                    <?php if ($muc['hopLe']): ?>
                                        <span class="pill pill-success">&#10003; Đạt</span>
                                    <?php else: ?>
                                        <span class="pill pill-danger">&#10007; Lỗi</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($muc['chiTiet']) ?></div>
                                    <div class="item-guide"><?= htmlspecialchars($muc['huongDan']) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Khối hiển thị các Database hiện có trên MySQL -->
        <div class="card">
            <div class="card-header">
                <h2>Thông Tin Cơ Sở Dữ Liệu Máy Chủ</h2>
            </div>
            <div class="db-box">
                <div class="db-box-title">Danh sách các cơ sở dữ liệu hiện có trên máy chủ MySQL:</div>
                <?php if (!empty($dsDb)): ?>
                    <div class="tag-list">
                        <?php foreach ($dsDb as $db): ?>
                            <?php $laMucTieu = ($db === $cauHinhDb['database']); ?>
                            <span class="tag <?= $laMucTieu ? 'target' : '' ?>">
                                <?= htmlspecialchars($db) ?> <?= $laMucTieu ? '(mục tiêu)' : '' ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted); font-size: 0.88rem; font-style: italic;">
                        (Không thể đọc danh sách cơ sở dữ liệu - vui lòng kiểm tra kết nối MySQL)
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chân trang -->
        <footer class="footer">
            <p>Sinh viên thực hiện: <span>Ngô Dương Gia Bảo</span> &bull; MSSV: <span>110124008</span> &bull; Lớp: <span>DA24TTB</span></p>
            <p style="margin-top: 4px; font-size: 0.78rem;">Đồ án Quản lý tài sản bằng mã QR &bull; Giảng viên hướng dẫn: Th.S Nguyễn Ngọc Đan Thanh</p>
        </footer>
    </div>
</body>

</html>