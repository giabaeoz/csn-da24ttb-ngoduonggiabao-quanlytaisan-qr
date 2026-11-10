<?php
/**
 * Giao diện In tem Mã QR Thiết bị (Print Template).
 * Tối ưu hóa kích thước tem nhỏ gọn:
 * - Cỡ Nhỏ Gọn (Mặc định): 47mm x 26mm (Lưới 4 cột x 9 hàng = 36 tem/trang A4)
 * - Cỡ Siêu Nhỏ: 47mm x 22mm (Lưới 4 cột x 11 hàng = 44 tem/trang A4)
 * - Cỡ Vừa: 60mm x 31mm (Lưới 3 cột x 8 hàng = 24 tem/trang A4)
 * Chữ đậm đen nhất có thể, vị trí phòng làm to nổi bật, không dùng dấu ba chấm '...'.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var list<array<string, mixed>> $danhSachIn
 * @var string $tieuDeTrang
 */

require_once dirname(__DIR__) . '/partials/icon.php';

// Mặc định khởi tạo: Cỡ Nhỏ Gọn (36 tem/trang A4)
$soTemMoiTrangMacDinh = 36;
$cacTrangA4MacDinh = array_chunk($danhSachIn, $soTemMoiTrangMacDinh);
$tongSoTem = count($danhSachIn);
$tongSoTrangMacDinh = count($cacTrangA4MacDinh);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tieuDeTrang) ?> - QLTS QR</title>
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css') ?>">
    <style>
        /* ==========================================================================
           1. CẤU HÌNH GIAO DIỆN CHUNG & XEM TRƯỚC (PREVIEW)
           ========================================================================== */
        body {
            background-color: #E5E7EB;
            color: #000000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .thanh-cong-cu-in {
            position: sticky;
            top: 0;
            z-index: 1050;
            background: #FFFFFF;
            border-bottom: 2px solid #111827;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .khu-vuc-in {
            padding: 20px 0 40px 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
        }

        /* Mô phỏng tờ giấy A4 trên màn hình (210mm x 297mm) */
        .to-giay-a4 {
            background: #FFFFFF;
            width: 210mm;
            min-height: 297mm;
            padding: 8mm 6mm;
            box-sizing: border-box;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
            border-radius: 3px;
            position: relative;
        }

        .nhan-so-trang {
            position: absolute;
            top: 2.5mm;
            right: 6mm;
            font-size: 0.72rem;
            font-weight: 700;
            color: #6B7280;
            letter-spacing: 0.05em;
        }

        /* Khung tem chung */
        .khung-tem {
            box-sizing: border-box;
            background: #FFFFFF;
            border: 1px dashed #4B5563;
            border-radius: 2px;
            display: flex;
            align-items: stretch;
            page-break-inside: avoid;
            break-inside: avoid;
            position: relative;
            overflow: hidden;
            color: #000000 !important;
        }

        .khung-tem.khong-vien-cat {
            border: 1px solid #E5E7EB;
        }

        /* ==========================================================================
           2. CẤU HÌNH CỠ NHỎ GỌN (MẶC ĐỊNH: 47mm x 26mm - 36 TEM / A4)
           ========================================================================== */
        .co-nho-gon .luoi-tem-a4 {
            display: grid;
            grid-template-columns: repeat(4, 47mm);
            grid-auto-rows: 26mm;
            gap: 3mm 2.8mm;
            justify-content: center;
        }

        .co-nho-gon .khung-tem {
            width: 47mm;
            height: 26mm;
            max-height: 26mm;
            padding: 1.6mm 1.8mm;
            gap: 1.8mm;
        }

        /* Cột QR trái */
        .co-nho-gon .tem-cot-qr {
            width: 19.5mm;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .co-nho-gon .khung-qr-hien-thi {
            width: 19mm;
            height: 19mm;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #FFFFFF;
        }

        .co-nho-gon .tem-ma-duoi-qr {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 6.8pt;
            font-weight: 900;
            color: #000000;
            line-height: 1;
            margin-top: 0.5mm;
            text-align: center;
            white-space: nowrap;
        }

        /* Cột thông tin phải */
        .co-nho-gon .tem-cot-thong-tin {
            flex-grow: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        /* VỊ TRÍ PHÒNG TO ĐẬM ĐEN NHẤT */
        .co-nho-gon .tem-dong-phong {
            display: flex;
            align-items: baseline;
            gap: 1mm;
            border-bottom: 1.2px solid #000000;
            padding-bottom: 0.4mm;
            margin-bottom: 0.4mm;
            line-height: 1;
        }

        .co-nho-gon .tem-nhan-phong {
            font-size: 5.4pt;
            font-weight: 900;
            color: #000000;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .co-nho-gon .tem-so-phong {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, sans-serif;
            font-size: 9.6pt;
            font-weight: 900;
            color: #000000;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .co-nho-gon .tem-ten-thiet-bi {
            font-size: 6.5pt;
            font-weight: 800;
            color: #000000;
            line-height: 1.15;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .co-nho-gon .tem-dong-phu {
            font-size: 5.6pt;
            font-weight: 800;
            color: #000000;
            line-height: 1.1;
            display: flex;
            align-items: center;
            gap: 0.8mm;
            white-space: nowrap;
            overflow: hidden;
        }

        /* ==========================================================================
           3. CẤU HÌNH CỠ SIÊU NHỎ (47mm x 22mm - 44 TEM / A4)
           ========================================================================== */
        .co-sieu-nho .luoi-tem-a4 {
            display: grid;
            grid-template-columns: repeat(4, 47mm);
            grid-auto-rows: 22mm;
            gap: 2.5mm 2.8mm;
            justify-content: center;
        }

        .co-sieu-nho .khung-tem {
            width: 47mm;
            height: 22mm;
            max-height: 22mm;
            padding: 1.2mm 1.5mm;
            gap: 1.5mm;
        }

        .co-sieu-nho .tem-cot-qr {
            width: 17mm;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .co-sieu-nho .khung-qr-hien-thi {
            width: 16.5mm;
            height: 16.5mm;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #FFFFFF;
        }

        .co-sieu-nho .tem-ma-duoi-qr {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 6.2pt;
            font-weight: 900;
            color: #000000;
            line-height: 1;
            margin-top: 0.3mm;
            white-space: nowrap;
        }

        .co-sieu-nho .tem-cot-thong-tin {
            flex-grow: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .co-sieu-nho .tem-dong-phong {
            display: flex;
            align-items: baseline;
            gap: 0.8mm;
            border-bottom: 1px solid #000000;
            padding-bottom: 0.3mm;
            margin-bottom: 0.3mm;
            line-height: 1;
        }

        .co-sieu-nho .tem-nhan-phong {
            font-size: 5pt;
            font-weight: 900;
            color: #000000;
            text-transform: uppercase;
        }

        .co-sieu-nho .tem-so-phong {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, sans-serif;
            font-size: 8.8pt;
            font-weight: 900;
            color: #000000;
            white-space: nowrap;
        }

        .co-sieu-nho .tem-ten-thiet-bi {
            font-size: 6.0pt;
            font-weight: 800;
            color: #000000;
            line-height: 1.1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .co-sieu-nho .tem-dong-phu {
            font-size: 5.2pt;
            font-weight: 800;
            color: #000000;
            line-height: 1;
            display: flex;
            align-items: center;
            gap: 0.6mm;
            white-space: nowrap;
            overflow: hidden;
        }

        /* ==========================================================================
           4. CẤU HÌNH CỠ VỪA (60mm x 31mm - 24 TEM / A4)
           ========================================================================== */
        .co-vua .luoi-tem-a4 {
            display: grid;
            grid-template-columns: repeat(3, 60mm);
            grid-auto-rows: 31mm;
            gap: 3.5mm 4mm;
            justify-content: center;
        }

        .co-vua .khung-tem {
            width: 60mm;
            height: 31mm;
            max-height: 31mm;
            padding: 1.8mm 2.2mm;
            gap: 2.2mm;
        }

        .co-vua .tem-cot-qr {
            width: 22mm;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .co-vua .khung-qr-hien-thi {
            width: 21mm;
            height: 21mm;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #FFFFFF;
        }

        .co-vua .tem-ma-duoi-qr {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 7.5pt;
            font-weight: 900;
            color: #000000;
            line-height: 1;
            margin-top: 0.6mm;
            white-space: nowrap;
        }

        .co-vua .tem-cot-thong-tin {
            flex-grow: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .co-vua .tem-dong-phong {
            display: flex;
            align-items: baseline;
            gap: 1.2mm;
            border-bottom: 1.5px solid #000000;
            padding-bottom: 0.5mm;
            margin-bottom: 0.5mm;
            line-height: 1;
        }

        .co-vua .tem-nhan-phong {
            font-size: 6pt;
            font-weight: 900;
            color: #000000;
            text-transform: uppercase;
        }

        .co-vua .tem-so-phong {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, sans-serif;
            font-size: 11pt;
            font-weight: 900;
            color: #000000;
            white-space: nowrap;
        }

        .co-vua .tem-ten-thiet-bi {
            font-size: 7.2pt;
            font-weight: 800;
            color: #000000;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }

        .co-vua .tem-dong-phu {
            font-size: 6.2pt;
            font-weight: 800;
            color: #000000;
            line-height: 1.15;
            display: flex;
            align-items: center;
            gap: 1mm;
            white-space: nowrap;
            overflow: hidden;
        }

        /* SVG QR chung */
        .khung-qr-hien-thi svg {
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 100% !important;
            display: block;
            shape-rendering: crispEdges;
        }

        /* ==========================================================================
           5. TỐI ƯU HÓA KHI IN RA MÁY IN (PRINT STYLESHEET)
           ========================================================================== */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 6mm;
            }

            *, *::before, *::after {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                box-shadow: none !important;
                text-shadow: none !important;
                color: #000000 !important;
            }

            html, body {
                background: #FFFFFF !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                height: auto !important;
            }

            .no-print {
                display: none !important;
            }

            .khu-vuc-in {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                gap: 0 !important;
            }

            .to-giay-a4 {
                width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
                margin: 0 !important;
                background: #FFFFFF !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                page-break-after: always !important;
                break-after: page !important;
            }

            .to-giay-a4:last-child {
                page-break-after: avoid !important;
                break-after: avoid !important;
            }

            .nhan-so-trang {
                display: none !important;
            }

            .khung-tem {
                border: 1px dashed #000000 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .khung-tem.khong-vien-cat {
                border: 1px solid transparent !important;
            }

            .tem-dong-phong {
                border-bottom-color: #000000 !important;
            }

            .tem-so-phong {
                font-weight: 900 !important;
                color: #000000 !important;
            }

            .tem-ten-thiet-bi {
                font-weight: 800 !important;
                color: #000000 !important;
            }

            .tem-ma-duoi-qr {
                font-weight: 900 !important;
                color: #000000 !important;
            }

            .tem-dong-phu {
                font-weight: 800 !important;
                color: #000000 !important;
            }
        }
    </style>
</head>
<body>

<!-- Thanh công cụ điều khiển in (ẩn khi in) -->
<header class="thanh-cong-cu-in no-print py-2 px-3">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="javascript:window.close()" class="btn btn-sm btn-outline-dark d-flex align-items-center gap-1" title="Đóng cửa sổ">
                <?= icon('arrow-left', 'bieu-tuong-sm') ?>
                <span>Quay lại</span>
            </a>
            <div>
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <?= icon('printer', 'bieu-tuong-sm text-dark') ?>
                    <span>In tem mã QR tài sản</span>
                </h6>
                <div class="small text-muted" id="thong_ke_in">
                    Tổng cộng: <strong class="text-dark"><?= $tongSoTem ?></strong> tem &bull; 
                    Khổ A4: <strong class="text-dark" id="txt_so_trang"><?= $tongSoTrangMacDinh ?></strong> trang
                </div>
            </div>
        </div>

        <!-- Các tùy chọn kích cỡ, hiển thị và nút in -->
        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Chọn kích cỡ tem -->
            <div class="d-flex align-items-center gap-1 bg-white border border-dark rounded px-2 py-1">
                <span class="small fw-bold text-dark me-1">Cỡ tem:</span>
                <div class="btn-group btn-group-sm" role="group" aria-label="Kích thước tem">
                    <button type="button" class="btn btn-sm btn-dark active" id="btn_co_nho_gon" onclick="chonCoTem('nho-gon')" title="Kích thước 4.7cm x 2.6cm (36 tem/A4) - Nhỏ gọn, vừa vặn remote, micro, phụ kiện">
                        <span>Nhỏ gọn (36/A4)</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark" id="btn_co_sieu_nho" onclick="chonCoTem('sieu-nho')" title="Kích thước 4.7cm x 2.2cm (44 tem/A4) - Siêu nhỏ dán nắp pin, sạc cáp">
                        <span>Siêu nhỏ (44/A4)</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-dark" id="btn_co_vua" onclick="chonCoTem('vua')" title="Kích thước 6.0cm x 3.1cm (24 tem/A4) - Vừa vặn dán thân máy">
                        <span>Cỡ vừa (24/A4)</span>
                    </button>
                </div>
            </div>

            <div class="form-check form-switch m-0 d-flex align-items-center gap-1 small text-dark ps-0 pe-1">
                <input class="form-check-input ms-0 me-1" type="checkbox" role="switch" id="chk_vien_cat" checked onchange="toggleVienCat(this.checked)">
                <label class="form-check-label user-select-none fw-semibold" for="chk_vien_cat">Viền cắt</label>
            </div>

            <button type="button" class="btn btn-sm btn-dark px-3 fw-bold d-flex align-items-center gap-1 shadow-sm" onclick="window.print()">
                <?= icon('printer', 'bieu-tuong-sm') ?>
                <span>In ngay (Ctrl+P)</span>
            </button>
        </div>
    </div>

    <!-- Hướng dẫn in tối ưu -->
    <div class="container-fluid mt-2 pt-2 border-top">
        <div class="d-flex align-items-center justify-content-between small">
            <div class="d-flex align-items-center gap-1 text-dark">
                <?= icon('info', 'bieu-tuong-sm text-dark flex-shrink-0') ?>
                <span><strong>Lưu ý in:</strong> Chọn Khổ <strong>A4</strong> &bull; Hướng <strong>Dọc (Portrait)</strong> &bull; Tỷ lệ <strong>100% (Mặc định)</strong> &bull; Lề <strong>Mặc định / Tối thiểu</strong>.</span>
            </div>
            <span class="d-none d-lg-inline fw-bold text-dark" id="txt_mo_ta_co_tem">
                Cỡ Nhỏ gọn: 47mm × 26mm &bull; Vị trí phòng to đậm đen &bull; Quét cực nhanh
            </span>
        </div>
    </div>
</header>

<!-- Khu vực chứa các trang in A4 -->
<main class="khu-vuc-in co-nho-gon" id="khu_vuc_in">
    <!-- Nội dung các trang A4 sẽ được render động theo kích cỡ được chọn -->
</main>

<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script src="<?= url('assets/vendor/qrcode/qrcode_UTF8.js') ?>"></script>
<script src="<?= url('assets/js/qr-tao.js') ?>"></script>
<script>
// Dữ liệu danh sách tài sản
const danhSachTaiSan = <?= json_encode($danhSachIn, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

// Cấu hình các kích cỡ tem nhỏ gọn
const CAU_HINH_CO_TEM = {
    'nho-gon': {
        soTemMoiTrang: 36,
        qrSize: 76,
        moTa: 'Cỡ Nhỏ gọn: 47mm × 26mm &bull; Lưới 4x9 (36 tem/A4) &bull; Dán vừa vặn remote, micro, chuột, phụ kiện &bull; Phòng to đậm'
    },
    'sieu-nho': {
        soTemMoiTrang: 44,
        qrSize: 66,
        moTa: 'Cỡ Siêu nhỏ: 47mm × 22mm &bull; Lưới 4x11 (44 tem/A4) &bull; Dán nắp pin, cáp sạc, phụ kiện mini'
    },
    'vua': {
        soTemMoiTrang: 24,
        qrSize: 88,
        moTa: 'Cỡ Vừa: 60mm × 31mm &bull; Lưới 3x8 (24 tem/A4) &bull; Dán màn hình, máy chiếu, thân máy tính'
    }
};

let coTemHienTai = 'nho-gon';

// Escape HTML helper
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Render toàn bộ các trang in theo kích cỡ được chọn
function renderTrangIn(coTem) {
    const khuVuc = document.getElementById('khu_vuc_in');
    const cfg = CAU_HINH_CO_TEM[coTem] || CAU_HINH_CO_TEM['nho-gon'];
    
    // Cập nhật class kích cỡ cho khu vực in
    khuVuc.className = 'khu-vuc-in co-' + coTem;

    // Chia mảng thành các trang A4
    const soTemMoiTrang = cfg.soTemMoiTrang;
    const cacTrang = [];
    for (let i = 0; i < danhSachTaiSan.length; i += soTemMoiTrang) {
        cacTrang.push(danhSachTaiSan.slice(i, i + soTemMoiTrang));
    }
    const tongSoTrang = cacTrang.length;

    // Cập nhật thông tin trên header
    const txtSoTrang = document.getElementById('txt_so_trang');
    if (txtSoTrang) {
        txtSoTrang.textContent = tongSoTrang;
    }
    const txtMoTa = document.getElementById('txt_mo_ta_co_tem');
    if (txtMoTa) {
        txtMoTa.innerHTML = cfg.moTa;
    }

    // Kiểm tra trạng thái viền cắt
    const chkVien = document.getElementById('chk_vien_cat');
    const classVien = (chkVien && !chkVien.checked) ? ' khong-vien-cat' : '';

    let html = '';
    let chiSo = 0;

    cacTrang.forEach((danhSachTemTrang, soTrang) => {
        html += '<section class="to-giay-a4">';
        html += '  <span class="nhan-so-trang">Trang ' + (soTrang + 1) + ' / ' + tongSoTrang + '</span>';
        html += '  <div class="luoi-tem-a4">';

        danhSachTemTrang.forEach(ts => {
            chiSo++;
            const maHienThi = ts.ma_tai_san ? String(ts.ma_tai_san) : ('TS-' + ts.id);
            const maQuetQr = ts.ma_qr ? String(ts.ma_qr) : maHienThi;

            // Thông tin phòng học / vị trí (in to đậm đen nhất)
            let viTriPhong = ts.ma_phong_phu_hop ? String(ts.ma_phong_phu_hop).trim() : '';
            if (!viTriPhong) {
                viTriPhong = 'LINH HOẠT';
            }

            // Hãng / model
            let hangModel = ((ts.hang_san_xuat || '') + ' ' + (ts.model || '')).trim();
            if (!hangModel && ts.ten_loai) {
                hangModel = ts.ten_loai;
            }

            // Bàn trực
            const phongTruc = ts.ma_phong_truc ? String(ts.ma_phong_truc).trim() : 'QLTS';

            html += '    <article class="khung-tem' + classVien + '">';
            
            // Cột trái: Mã QR Code & Mã thiết bị
            html += '      <div class="tem-cot-qr">';
            html += '        <div id="qr_box_' + chiSo + '" class="khung-qr-hien-thi" data-qr="' + escapeHtml(maQuetQr) + '" data-qr-size="' + cfg.qrSize + '" title="Mã quét: ' + escapeHtml(maQuetQr) + '"></div>';
            html += '        <div class="tem-ma-duoi-qr">' + escapeHtml(maHienThi) + '</div>';
            html += '      </div>';

            // Cột phải: VỊ TRÍ PHÒNG TO ĐẬM ĐEN NHẤT & Tên thiết bị (không bị '...')
            html += '      <div class="tem-cot-thong-tin">';
            
            // Dòng vị trí phòng: nổi bật nhất tem
            html += '        <div class="tem-dong-phong">';
            html += '          <span class="tem-nhan-phong">PHÒNG</span>';
            html += '          <span class="tem-so-phong">' + escapeHtml(viTriPhong) + '</span>';
            html += '        </div>';

            // Tên thiết bị hiển thị đầy đủ, không cắt dấu ba chấm
            html += '        <div class="tem-ten-thiet-bi" title="' + escapeHtml(ts.ten_tai_san || '') + '">' + escapeHtml(ts.ten_tai_san || '') + '</div>';
            
            // Dòng phụ trợ: Bàn trực & Model
            html += '        <div class="tem-dong-phu">';
            html += '          <span class="fw-bold">' + escapeHtml(phongTruc) + '</span>';
            if (hangModel) {
                html += '          <span>&bull;</span>';
                html += '          <span>' + escapeHtml(hangModel) + '</span>';
            }
            html += '        </div>';

            html += '      </div>'; // end tem-cot-thong-tin
            html += '    </article>';
        });

        html += '  </div>'; // end luoi-tem-a4
        html += '</section>';
    });

    khuVuc.innerHTML = html;

    // Sinh mã QR vector SVG độ tương phản cao, module rõ nét
    document.querySelectorAll('.khung-qr-hien-thi[data-qr]').forEach(function (el) {
        const text = el.getAttribute('data-qr') || '';
        const size = parseInt(el.getAttribute('data-qr-size') || String(cfg.qrSize), 10);
        if (typeof taoMaQr === 'function' && text.trim() !== '') {
            // margin: 1 module để tối đa diện tích module QR, level: 'M' bắt góc quét siêu nhạy
            taoMaQr(el, text.trim(), { size: size, level: 'M', margin: 1 });
        }
    });
}

// Bấm chọn kích cỡ tem
function chonCoTem(coTem) {
    if (!CAU_HINH_CO_TEM[coTem]) return;
    coTemHienTai = coTem;

    // Cập nhật trạng thái nút
    ['nho_gon', 'sieu_nho', 'vua'].forEach(k => {
        const btn = document.getElementById('btn_co_' + k);
        if (btn) {
            btn.classList.remove('btn-dark', 'active');
            btn.classList.add('btn-outline-dark');
        }
    });
    const btnActive = document.getElementById('btn_co_' + coTem.replace('-', '_'));
    if (btnActive) {
        btnActive.classList.remove('btn-outline-dark');
        btnActive.classList.add('btn-dark', 'active');
    }

    // Lưu vào localStorage
    try {
        localStorage.setItem('qlts_co_tem_qr_mini', coTem);
    } catch (e) {}

    renderTrangIn(coTem);
}

// Bật / tắt đường viền nét đứt cắt tem
function toggleVienCat(batVien) {
    const cacTem = document.querySelectorAll('.khung-tem');
    cacTem.forEach(function (tem) {
        if (batVien) {
            tem.classList.remove('khong-vien-cat');
        } else {
            tem.classList.add('khong-vien-cat');
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    // Khôi phục kích cỡ tem người dùng đã chọn trước đó (mặc định 'nho-gon')
    let coTemDaLuu = 'nho-gon';
    try {
        const saved = localStorage.getItem('qlts_co_tem_qr_mini');
        if (saved && CAU_HINH_CO_TEM[saved]) {
            coTemDaLuu = saved;
        }
    } catch (e) {}

    chonCoTem(coTemDaLuu);

    // Bắt phím tắt Ctrl+P / Cmd+P
    window.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
            e.preventDefault();
            window.print();
        }
    });
});
</script>

</body>
</html>
