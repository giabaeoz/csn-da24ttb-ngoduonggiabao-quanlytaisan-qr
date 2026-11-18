<?php

/**
 * Giao diện xem chi tiết Phiếu mượn - trả (Loan Detail View) - Chuẩn Design System
 *
 * @var array<string, mixed> $phieu Dữ liệu phiếu mượn và danh sách chi tiết thiết bị
 * @var string $tieuDeTrang Tiêu đề trang
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require dirname(__DIR__) . '/layout/dau-trang.php';

$laDangMuon = ($phieu['trang_thai'] ?? '') === 'DANG_MUON';
$quaHan = false;
if ($laDangMuon && !empty($phieu['han_tra'])) {
    $quaHan = strtotime((string)$phieu['han_tra']) < time();
}

$dsChiTiet = $phieu['chi_tiet'] ?? [];
$tongThietBi = count($dsChiTiet);
$chuaTraCount = 0;
foreach ($dsChiTiet as $item) {
    if (empty($item['thoi_gian_tra'])) {
        $chuaTraCount++;
    }
}
?>

<!-- Thanh công cụ đầu trang -->
<div class="thanh-cong-cu-trang no-print">
    <div>
        <h1 class="tieu-de-trang mb-0">Phiếu mượn: <?= e($phieu['ma_phieu'] ?? '') ?></h1>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <?php if (!empty($dsChiTiet)): ?>
            <a href="<?= url('tai-san/in-qr.php?ids=' . implode(',', array_column($dsChiTiet, 'tai_san_id'))) ?>" class="btn btn-phu" target="_blank">
                <?= icon('qr-code', 'bieu-tuong-sm') ?>
                <span>In tem QR thiết bị</span>
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-phu" onclick="window.print();">
            <?= icon('printer', 'bieu-tuong-sm') ?>
            <span>In phiếu mượn</span>
        </button>
        <?php if ($chuaTraCount > 0 && co_quyen('nghiep_vu_muon_tra')): ?>
            <a href="<?= url('muon-tra/tra.php?ma_phieu=' . urlencode((string)$phieu['ma_phieu'])) ?>" class="btn btn-chinh">
                <?= icon('rotate-ccw', 'bieu-tuong-sm') ?>
                <span>Nhận trả thiết bị (<?= $chuaTraCount ?>)</span>
            </a>
        <?php endif; ?>
        <a href="<?= url('muon-tra/index.php') ?>" class="btn btn-phu">
            <?= icon('arrow-left', 'bieu-tuong-sm') ?>
            <span>Quay lại</span>
        </a>
    </div>
</div>

<!-- Cảnh báo nếu phiếu mượn quá hạn -->
<?php if ($quaHan): ?>
    <div class="alert alert-danger d-flex align-items-center gap-3 mb-3 no-print" role="alert">
        <?= icon('alert-triangle', 'bieu-tuong-md') ?>
        <div>
            <strong>Cảnh báo quá hạn trả:</strong> Hạn trả dự kiến là <strong><?= dinh_dang_ngay_gio($phieu['han_tra']) ?></strong> nhưng hiện còn <strong><?= $chuaTraCount ?></strong> thiết bị chưa hoàn trả về phòng trực.
        </div>
    </div>
<?php endif; ?>

<!-- Khối tiêu đề biên bản (chỉ xuất hiện khi in ra giấy) -->
<div class="d-none d-print-block mb-4">
    <div class="row align-items-center border-bottom pb-3 mb-3">
        <div class="col-7 text-center">
            <div class="fw-bold small text-uppercase">TRƯỜNG ĐẠI HỌC CÔNG NGHỆ</div>
            <div class="fw-bold fs-6 text-uppercase">PHÒNG QUẢN TRỊ THIẾT BỊ & CƠ SỞ VẬT CHẤT</div>
            <div class="small fst-italic">Phòng trực: <?= e($phieu['ten_phong_truc'] ?? '') ?></div>
        </div>
        <div class="col-5 text-center">
            <div class="fw-bold small text-uppercase">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
            <div class="fw-bold small">Độc lập - Tự do - Hạnh phúc</div>
            <div class="small fst-italic">Ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></div>
        </div>
    </div>
    <div class="text-center my-3">
        <h4 class="fw-bold text-uppercase mb-1">PHIẾU MƯỢN TRẢ THIẾT BỊ DẠY HỌC</h4>
        <div class="small text-muted font-monospace">Mã phiếu: <strong><?= e($phieu['ma_phieu']) ?></strong></div>
    </div>
</div>

<!-- Bố cục 2 cột chuẩn: Thông tin phiếu mượn (Trái - 8 cột) & Mã QR Phiếu (Phải - 4 cột) -->
<div class="row g-4 mb-4 align-items-stretch">
    <!-- Cột Trái: Thông tin tổng hợp Người mượn & Phiên mượn -->
    <div class="col-12 col-lg-8">
        <div class="the-noi-dung h-100 mb-0 d-flex flex-column">
            <div class="the-noi-dung-dau d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="hop-icon-dau">
                        <?= icon('clipboard-check', 'bieu-tuong-sm') ?>
                    </div>
                    <h3 class="tieu-de-khoi mb-0">Thông tin phiếu mượn</h3>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <?php if ($laDangMuon): ?>
                        <span class="huy-hieu-tt huy-hieu-tt-canh-bao">
                            <?= icon('clock', 'bieu-tuong-sm') ?>
                            <span>Đang mượn (còn <?= $chuaTraCount ?> máy)</span>
                        </span>
                        <?php if ($quaHan): ?>
                            <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">
                                <?= icon('alert-triangle', 'bieu-tuong-sm') ?>
                                <span>Quá hạn</span>
                            </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">
                            <?= icon('check-circle-2', 'bieu-tuong-sm') ?>
                            <span>Đã trả đủ</span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="the-noi-dung-than p-4 flex-grow-1 d-flex flex-column justify-content-between">
                <div>
                    <!-- Khối nổi bật: Người mượn & Phòng học sử dụng -->
                    <div class="p-3 rounded-3 border mb-4" style="background-color: var(--nen-tieu-de-bang);">
                        <div class="row g-3 align-items-center">
                            <!-- Người mượn -->
                            <div class="col-12 col-md-6 border-end-md pe-md-4">
                                <div class="text-secondary chu-nho-11 fw-semibold text-uppercase mb-1">Người mượn thiết bị</div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="fw-bold fs-5 text-dark"><?= e($phieu['ten_nguoi_muon'] ?? '') ?></span>
                                    <?php
                                    $loaiNm = $phieu['loai_nguoi_muon'] ?? '';
                                    if ($loaiNm === 'SINH_VIEN') {
                                        echo '<span class="huy-hieu huy-hieu-chinh">Sinh viên</span>';
                                    } elseif ($loaiNm === 'GIANG_VIEN') {
                                        echo '<span class="huy-hieu huy-hieu-chinh">Giảng viên</span>';
                                    } else {
                                        echo '<span class="huy-hieu huy-hieu-xam">Cán bộ / NV</span>';
                                    }
                                    ?>
                                </div>
                                <div class="text-secondary small mt-1">
                                    Mã số: <strong class="font-monospace text-dark"><?= e($phieu['ma_so_truong'] ?? '---') ?></strong>
                                    &bull; Đơn vị: <strong><?= e($phieu['lop_don_vi'] ?? '---') ?></strong>
                                </div>
                            </div>

                            <!-- Phòng học đích -->
                            <div class="col-12 col-md-6 ps-md-4">
                                <div class="text-secondary chu-nho-11 fw-semibold text-uppercase mb-1">Phòng học sử dụng</div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="huy-hieu huy-hieu-chinh font-monospace fs-6 px-3 py-1">
                                        <?= e($phieu['ma_phong'] ?? '') ?>
                                    </span>
                                    <span class="fw-bold text-dark fs-6"><?= e($phieu['ten_phong'] ?? '') ?></span>
                                </div>
                                <div class="text-secondary small mt-1">
                                    <?php if (!empty($phieu['toa_nha'])): ?>
                                        Tòa <?= e($phieu['toa_nha']) ?><?= !empty($phieu['tang']) ? ' &bull; Tầng ' . e((string)$phieu['tang']) : '' ?>
                                    <?php else: ?>
                                        Phòng học giảng đường
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lưới thông tin chi tiết 3 cột thoáng đãng -->
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="p-3 rounded border bg-white h-100">
                                <div class="text-muted small d-flex align-items-center gap-1">
                                    <?= icon('clock', 'bieu-tuong-sm text-primary') ?>
                                    <span>Thời gian mượn</span>
                                </div>
                                <div class="fw-semibold text-dark mt-1">
                                    <?= dinh_dang_ngay_gio($phieu['thoi_gian_muon'] ?? '') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="p-3 rounded border bg-white h-100">
                                <div class="text-muted small d-flex align-items-center gap-1">
                                    <?= icon('calendar', 'bieu-tuong-sm text-primary') ?>
                                    <span>Hạn trả dự kiến</span>
                                </div>
                                <div class="fw-semibold mt-1 <?= $quaHan ? 'text-danger fw-bold' : 'text-dark' ?>">
                                    <?= dinh_dang_ngay_gio($phieu['han_tra'] ?? '') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="p-3 rounded border bg-white h-100">
                                <div class="text-muted small d-flex align-items-center gap-1">
                                    <?= icon('phone', 'bieu-tuong-sm text-primary') ?>
                                    <span>Số điện thoại liên hệ</span>
                                </div>
                                <div class="fw-semibold mt-1">
                                    <?php if (!empty($phieu['so_dien_thoai'])): ?>
                                        <a href="tel:<?= e($phieu['so_dien_thoai']) ?>" class="text-primary text-decoration-none">
                                            <?= e($phieu['so_dien_thoai']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa cập nhật</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="p-3 rounded border bg-white h-100">
                                <div class="text-muted small d-flex align-items-center gap-1">
                                    <?= icon('home', 'bieu-tuong-sm text-primary') ?>
                                    <span>Phòng trực cấp thiết bị</span>
                                </div>
                                <div class="fw-semibold text-dark mt-1">
                                    <?= e($phieu['ten_phong_truc'] ?? '') ?> (<?= e($phieu['ma_phong_truc'] ?? '') ?>)
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="p-3 rounded border bg-white h-100">
                                <div class="text-muted small d-flex align-items-center gap-1">
                                    <?= icon('user-check', 'bieu-tuong-sm text-primary') ?>
                                    <span>Cán bộ lập phiếu</span>
                                </div>
                                <div class="fw-semibold text-dark mt-1">
                                    <?= e($phieu['ten_nhan_vien_lap'] ?? '---') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-4">
                            <div class="p-3 rounded border bg-white h-100">
                                <div class="text-muted small d-flex align-items-center gap-1">
                                    <?= icon('info', 'bieu-tuong-sm text-primary') ?>
                                    <span>Ghi chú phiếu mượn</span>
                                </div>
                                <div class="fw-semibold text-dark mt-1">
                                    <?= !empty($phieu['ghi_chu']) ? e($phieu['ghi_chu']) : '<span class="text-muted fw-normal">Không có ghi chú</span>' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cột Phải: Mã QR Phiếu Mượn -->
    <div class="col-12 col-lg-4">
        <div class="the-noi-dung h-100 mb-0 d-flex flex-column text-center">
            <div class="the-noi-dung-dau d-flex align-items-center justify-content-center gap-2">
                <div class="hop-icon-dau">
                    <?= icon('qr-code', 'bieu-tuong-sm') ?>
                </div>
                <h3 class="tieu-de-khoi mb-0">Mã QR phiếu mượn</h3>
            </div>
            <div class="the-noi-dung-than p-4 flex-grow-1 d-flex flex-column align-items-center justify-content-center">
                <div class="p-3 rounded-3 border mb-3" style="background-color: var(--nen-tieu-de-bang);">
                    <div id="ticket_qr" data-qr="<?= e($phieu['ma_phieu']) ?>" data-qr-size="140" class="p-2 bg-white border rounded shadow-sm d-inline-flex align-items-center justify-content-center"></div>
                </div>
                <div class="font-monospace fw-bold text-primary fs-5 mb-1"><?= e($phieu['ma_phieu']) ?></div>
                <div class="mo-ta-phu">
                    Quét mã này tại bàn tiếp đón phòng trực để đối chiếu hoặc nhận trả nhanh thiết bị.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bảng Danh sách thiết bị mượn -->
<div class="the-noi-dung mb-4">
    <div class="the-noi-dung-dau d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="hop-icon-dau">
                <?= icon('package', 'bieu-tuong-sm') ?>
            </div>
            <h3 class="tieu-de-khoi mb-0">Danh sách thiết bị mượn (<?= $tongThietBi ?>)</h3>
        </div>
        <div>
            <?php if ($chuaTraCount === 0): ?>
                <span class="huy-hieu huy-hieu-thanh-cong">
                    <?= icon('check-circle-2', 'bieu-tuong-sm') ?>
                    <span>Đã trả: <?= $tongThietBi ?> / <?= $tongThietBi ?> thiết bị</span>
                </span>
            <?php else: ?>
                <span class="huy-hieu huy-hieu-canh-bao">
                    <?= icon('clock', 'bieu-tuong-sm') ?>
                    <span>Đã trả: <?= $tongThietBi - $chuaTraCount ?> / <?= $tongThietBi ?> thiết bị</span>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="the-noi-dung-than p-0">
        <div class="table-responsive">
            <table class="bang-du-lieu mb-0">
                <thead>
                    <tr>
                        <th class="text-center cot-50">STT</th>
                        <th class="cot-110">Mã TS</th>
                        <th>Tên thiết bị</th>
                        <th class="text-center cot-130">Lúc mượn</th>
                        <th>Trạng thái trả</th>
                        <th>Cán bộ nhận trả</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dsChiTiet)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Chưa có thiết bị nào trong phiếu mượn này.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dsChiTiet as $idx => $item): ?>
                            <?php
                            $daTra = !empty($item['thoi_gian_tra']);
                            $urlAnh = !empty($item['hinh_anh']) ? tai_san_lay_url_anh($item['hinh_anh']) : null;

                            $ttMuon = (string)($item['tinh_trang_muon'] ?? 'TOT');
                            $labelTtMuon = ($ttMuon === 'HU_HONG') ? 'Hư hỏng' : 'Tốt';
                            $classTtMuon = ($ttMuon === 'HU_HONG') ? 'huy-hieu-nguy-hiem' : 'huy-hieu-thanh-cong';

                            $ttTra = (string)($item['tinh_trang_tra'] ?? '');
                            $labelTtTra = ($ttTra === 'TOT' || $ttTra === 'BINH_THUONG') ? 'Tốt' : (($ttTra === 'HU_HONG') ? 'Hư hỏng' : $ttTra);
                            ?>
                            <tr>
                                <td class="text-center text-muted font-monospace"><?= $idx + 1 ?></td>
                                <td>
                                    <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$item['tai_san_id']) ?>" class="font-monospace fw-bold text-primary text-decoration-none">
                                        <?= e($item['ma_tai_san'] ?? '') ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if ($urlAnh): ?>
                                            <img src="<?= e($urlAnh) ?>" alt="<?= e($item['ten_tai_san'] ?? '') ?>" class="rounded border flex-shrink-0" style="width: 38px; height: 38px; object-fit: cover; background-color: #f8fafc;">
                                        <?php else: ?>
                                            <div class="rounded border d-flex align-items-center justify-content-center text-secondary flex-shrink-0" style="width: 38px; height: 38px; background-color: #f8fafc;">
                                                <?= icon('package', 'bieu-tuong-sm') ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$item['tai_san_id']) ?>" class="fw-semibold text-dark text-decoration-none">
                                                <?= e($item['ten_tai_san'] ?? '') ?>
                                            </a>
                                            <div class="mo-ta-phu">
                                                <?= e($item['ten_loai'] ?? 'Thiết bị') ?>
                                                <?php if (!empty($item['hang_san_xuat']) || !empty($item['model'])): ?>
                                                    &bull; <?= e(trim(($item['hang_san_xuat'] ?? '') . (!empty($item['model']) ? ' ' . $item['model'] : ''))) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="huy-hieu <?= $classTtMuon ?>"><?= e($labelTtMuon) ?></span>
                                </td>
                                <td>
                                    <?php if ($daTra): ?>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <span class="huy-hieu huy-hieu-thanh-cong">
                                                <?= icon('check-circle-2', 'bieu-tuong-sm') ?>
                                                <span>Đã trả</span>
                                            </span>
                                            <span class="small text-dark fw-medium"><?= dinh_dang_ngay_gio($item['thoi_gian_tra']) ?></span>
                                            <?php if (!empty($labelTtTra)): ?>
                                                <span class="mo-ta-phu">(Tình trạng: <strong class="text-dark"><?= e($labelTtTra) ?></strong>)</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="huy-hieu huy-hieu-canh-bao">
                                            <?= icon('clock', 'bieu-tuong-sm') ?>
                                            <span>Đang mượn</span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($daTra): ?>
                                        <span class="fw-medium text-dark"><?= e($item['ten_nhan_vien_nhan'] ?? '---') ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">---</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chữ ký biên bản (chỉ xuất hiện khi in ra giấy) -->
<div class="d-none d-print-block mt-5 pt-3">
    <div class="row text-center">
        <div class="col-6">
            <div class="fw-bold">NGƯỜI MƯỢN THIẾT BỊ</div>
            <div class="small text-muted fst-italic mb-5">(Ký và ghi rõ họ tên)</div>
            <div class="fw-bold mt-5"><?= e($phieu['ten_nguoi_muon'] ?? '') ?></div>
        </div>
        <div class="col-6">
            <div class="fw-bold">CÁN BỘ PHÒNG TRỰC GIAO NHẬN</div>
            <div class="small text-muted fst-italic mb-5">(Ký và ghi rõ họ tên)</div>
            <div class="fw-bold mt-5"><?= e($phieu['ten_nhan_vien_lap'] ?? '') ?></div>
        </div>
    </div>
</div>

<!-- Thư viện sinh QR code và khởi tạo -->
<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script src="<?= url('assets/vendor/qrcode/qrcode_UTF8.js') ?>"></script>
<script src="<?= url('assets/js/qr-tao.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const maPhieu = <?= json_encode($phieu['ma_phieu'] ?? '') ?>;
        taoMaQr('ticket_qr', maPhieu, {
            size: 140,
            margin: 1
        });
    });
</script>

<?php
require dirname(__DIR__) . '/layout/chan-trang.php';
