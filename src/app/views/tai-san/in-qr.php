<?php
/**
 * Giao diện In tem Mã QR Thiết bị (Print Template).
 * Hỗ trợ in 1 tem hoặc nhiều tem định dạng chuẩn trên khổ A4 / Decal.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tieuDeTrang) ?> - QLTS QR</title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <style>
        body {
            background-color: #F3F4F6;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .khung-tem {
            background: #FFFFFF;
            border: 2px dashed #9CA3AF;
            border-radius: 8px;
            padding: 12px;
            width: 320px;
            box-sizing: border-box;
            page-break-inside: avoid;
            margin-bottom: 20px;
        }

        .tieu-de-truong {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4B5563;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 4px;
            margin-bottom: 8px;
            text-align: center;
        }

        .ten-thiet-bi {
            font-size: 0.95rem;
            font-weight: 700;
            color: #111827;
            line-height: 1.25;
            margin-bottom: 4px;
        }

        .ma-thiet-bi {
            font-family: monospace;
            font-size: 1.15rem;
            font-weight: 700;
            color: #1E4E8C;
        }

        .thong-tin-phu {
            font-size: 0.75rem;
            color: #4B5563;
            line-height: 1.35;
        }

        /* Tối ưu hóa khi in ra giấy máy in */
        @media print {
            body {
                background: #FFFFFF;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .khung-tem {
                border: 1.5px solid #000000;
                margin: 6px;
                box-shadow: none;
                float: left;
            }
            .trang-in {
                padding: 0;
            }
        }
    </style>
</head>
<body class="py-4">

<!-- Thanh điều khiển in (ẩn khi in thực tế) -->
<div class="container mb-4 no-print">
    <div class="card border-0 shadow-sm p-3 bg-white d-flex flex-row justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-1">In tem mã QR thiết bị</h5>
            <div class="text-muted small">
                Đang chuẩn bị in <strong><?= count($danhSachIn) ?></strong> tem thiết bị.
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary fw-semibold px-4" onclick="window.print()">
                In ngay
            </button>
            <button type="button" class="btn btn-outline-secondary fw-semibold" onclick="window.close()">
                Đóng
            </button>
        </div>
    </div>
</div>

<div class="container trang-in">
    <div class="d-flex flex-wrap justify-content-start gap-3">
        <?php foreach ($danhSachIn as $idx => $ts): ?>
        <div class="khung-tem">
            <div class="tieu-de-truong">HỆ THỐNG QUẢN LÝ THIẾT BỊ QR</div>

            <div class="d-flex align-items-center gap-3">
                <!-- Mã QR Render -->
                <div id="qr_box_<?= $idx ?>" data-qr="<?= e($ts['ma_qr']) ?>" data-qr-size="95" class="flex-shrink-0" style="min-width: 95px; min-height: 95px;"></div>

                <!-- Thông tin thiết bị -->
                <div class="flex-grow-1 overflow-hidden">
                    <div class="ma-thiet-bi"><?= e($ts['ma_tai_san']) ?></div>
                    <div class="ten-thiet-bi text-truncate"><?= e($ts['ten_tai_san']) ?></div>
                    
                    <div class="thong-tin-phu mt-1">
                        <?php if (!empty($ts['hang_san_xuat'])): ?>
                            <div>Hãng: <strong><?= e($ts['hang_san_xuat']) ?></strong> <?= !empty($ts['model']) ? '(' . e($ts['model']) . ')' : '' ?></div>
                        <?php endif; ?>
                        
                        <?php if (!empty($ts['ma_phong_phu_hop'])): ?>
                            <div class="fw-semibold text-dark">Phòng: <?= e($ts['ma_phong_phu_hop']) ?></div>
                        <?php else: ?>
                            <div>Phạm vi: Linh hoạt</div>
                        <?php endif; ?>

                        <div>Bàn trực: <?= e($ts['ma_phong_truc']) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Thư viện QRCode.js offline -->
<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script src="<?= url('assets/vendor/qrcode/qrcode_UTF8.js') ?>"></script>
<script src="<?= url('assets/js/qr-tao.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const danhSach = <?= json_encode($danhSachIn, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    danhSach.forEach((ts, idx) => {
        taoMaQr('qr_box_' + idx, ts.ma_qr, { size: 95, margin: 1 });
    });
});
</script>

</body>
</html>

