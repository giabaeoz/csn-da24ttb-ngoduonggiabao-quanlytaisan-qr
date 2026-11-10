<?php
/**
 * Giao diện xem chi tiết Phiếu mượn - trả (Loan Detail View).
 *
 * @var array  $phieu Dữ liệu phiếu mượn và danh sách chi tiết thiết bị
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

<div class="container-fluid py-3">
    <!-- Breadcrumb & Tiêu đề -->
    <div class="row align-items-center mb-3">
        <div class="col-md-7">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('tong-quan.php') ?>" class="text-decoration-none">Tổng quan</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('muon-tra/index.php') ?>" class="text-decoration-none">Mượn - Trả tài sản</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($phieu['ma_phieu'] ?? '') ?></li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 d-flex align-items-center gap-2">
                <span>Phiếu mượn: <strong><?= e($phieu['ma_phieu'] ?? '') ?></strong></span>
                <?php if ($laDangMuon): ?>
                    <span class="badge bg-warning text-dark fs-6"><i class="bi bi-clock-history"></i> Đang mượn</span>
                    <?php if ($quaHan): ?>
                        <span class="badge bg-danger fs-6"><i class="bi bi-exclamation-octagon-fill"></i> Quá hạn</span>
                    <?php endif; ?>
                <?php else: ?>
                    <span class="badge bg-success fs-6"><i class="bi bi-check-circle-fill"></i> Đã trả đủ</span>
                <?php endif; ?>
            </h1>
        </div>
        <div class="col-md-5 text-md-end mt-2 mt-md-0 d-flex justify-content-md-end gap-2 flex-wrap">
            <a href="<?= url('muon-tra/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
            <?php if (!empty($dsChiTiet)): ?>
                <a href="<?= url('tai-san/in-qr.php?ids=' . implode(',', array_column($dsChiTiet, 'tai_san_id'))) ?>" class="btn btn-outline-dark" target="_blank">
                    <i class="bi bi-qr-code"></i> In tem QR thiết bị
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-dark" onclick="window.print();">
                <i class="bi bi-printer"></i> In phiếu mượn
            </button>
            <?php if ($chuaTraCount > 0 && co_quyen('nghiep_vu_muon_tra')): ?>
                <a href="<?= url('muon-tra/tra.php?ma_phieu=' . urlencode((string)$phieu['ma_phieu'])) ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-return-left"></i> Nhận trả thiết bị (<?= $chuaTraCount ?>)
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Thông báo nếu quá hạn -->
    <?php if ($quaHan): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
            <div>
                <strong>Cảnh báo quá hạn:</strong> Phiếu mượn này có hạn trả là <strong><?= dinh_dang_ngay_gio($phieu['han_tra']) ?></strong> nhưng hiện tại vẫn còn <strong><?= $chuaTraCount ?></strong> thiết bị chưa được hoàn trả về bàn trực. Vui lòng liên hệ người mượn để thu hồi thiết bị.
            </div>
        </div>
    <?php endif; ?>

    <!-- Khối thông tin chung -->
    <div class="row g-3 mb-4">
        <!-- Thông tin người mượn -->
        <div class="col-lg-5">
            <div class="card shadow-sm h-100 border-0 border-top border-primary border-3">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-primary">
                        <i class="bi bi-person-badge me-2"></i>Thông tin Người mượn
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted" style="width: 35%;">Họ và tên:</th>
                                <td class="fw-bold fs-6 text-dark"><?= e($phieu['ten_nguoi_muon'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Mã số trường:</th>
                                <td>
                                    <span class="badge bg-secondary font-monospace fs-6">
                                        <?= e($phieu['ma_so_truong'] ?? 'Chưa có') ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Đối tượng:</th>
                                <td>
                                    <?php
                                    $loaiNm = $phieu['loai_nguoi_muon'] ?? '';
                                    if ($loaiNm === 'SINH_VIEN') {
                                        echo '<span class="badge bg-info text-dark">Sinh viên</span>';
                                    } elseif ($loaiNm === 'GIANG_VIEN') {
                                        echo '<span class="badge bg-primary">Giảng viên</span>';
                                    } elseif ($loaiNm === 'CAN_BO') {
                                        echo '<span class="badge bg-secondary">Cán bộ / NV</span>';
                                    } else {
                                        echo '<span class="badge bg-light text-dark">Khác</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Lớp / Khoa / Đơn vị:</th>
                                <td><?= e($phieu['lop_don_vi'] ?? '---') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Số điện thoại:</th>
                                <td>
                                    <?php if (!empty($phieu['so_dien_thoai'])): ?>
                                        <a href="tel:<?= e($phieu['so_dien_thoai']) ?>" class="text-decoration-none">
                                            <i class="bi bi-telephone me-1"></i><?= e($phieu['so_dien_thoai']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Chưa cập nhật</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Thông tin phiên mượn -->
        <div class="col-lg-4">
            <div class="card shadow-sm h-100 border-0 border-top border-info border-3">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 text-info">
                        <i class="bi bi-info-circle me-2"></i>Thông tin Phiên mượn
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted" style="width: 38%;">Phòng học đích:</th>
                                <td>
                                    <span class="badge bg-dark fs-6 font-monospace me-1">
                                        <?= e($phieu['ma_phong'] ?? '') ?>
                                    </span>
                                    <span class="fw-semibold"><?= e($phieu['ten_phong'] ?? '') ?></span>
                                    <?php if (!empty($phieu['toa_nha'])): ?>
                                        <small class="text-muted d-block">(Tòa <?= e($phieu['toa_nha']) ?><?= !empty($phieu['tang']) ? ' - Tầng ' . e((string)$phieu['tang']) : '' ?>)</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Bàn trực cấp:</th>
                                <td>
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                    <strong><?= e($phieu['ten_phong_truc'] ?? '') ?></strong>
                                    <small class="text-muted font-monospace">(<?= e($phieu['ma_phong_truc'] ?? '') ?>)</small>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Thời gian mượn:</th>
                                <td><i class="bi bi-calendar3 me-1 text-primary"></i> <?= dinh_dang_ngay_gio($phieu['thoi_gian_muon'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Hạn trả dự kiến:</th>
                                <td>
                                    <i class="bi bi-alarm me-1 text-warning"></i>
                                    <span class="<?= $quaHan ? 'text-danger fw-bold' : '' ?>">
                                        <?= dinh_dang_ngay_gio($phieu['han_tra'] ?? '') ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Cán bộ lập:</th>
                                <td><i class="bi bi-person-check me-1"></i> <?= e($phieu['ten_nhan_vien_lap'] ?? '') ?></td>
                            </tr>
                            <?php if (!empty($phieu['ghi_chu'])): ?>
                                <tr>
                                    <th class="text-muted">Ghi chú:</th>
                                    <td class="fst-italic text-secondary small"><?= nl2br(e($phieu['ghi_chu'])) ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Thẻ Mã QR Phiếu mượn -->
        <div class="col-lg-3">
            <div class="card shadow-sm h-100 border-0 border-top border-dark border-3 text-center p-3 bg-white">
                <div class="card-header bg-white py-2 border-0">
                    <h6 class="card-title mb-0 fw-bold text-dark">
                        MÃ QR PHIẾU MƯỢN
                    </h6>
                </div>
                <div class="card-body p-2 d-flex flex-column align-items-center justify-content-center">
                    <div id="ticket_qr" data-qr="<?= e($phieu['ma_phieu']) ?>" data-qr-size="130" class="p-2 bg-white border rounded shadow-sm mb-2" style="min-width: 134px; min-height: 134px;"></div>
                    <div class="font-monospace fw-bold text-primary small"><?= e($phieu['ma_phieu']) ?></div>
                    <small class="text-muted d-block mt-1" style="font-size: 11px;">
                        Quét mã này tại bàn trực để nhận trả nhanh thiết bị
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Danh sách thiết bị trong phiếu mượn -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="bi bi-cpu me-2"></i>Danh sách thiết bị mượn (<?= $tongThietBi ?>)
            </h5>
            <div>
                <span class="badge bg-light text-dark border">
                    Đã trả: <strong class="text-success"><?= $tongThietBi - $chuaTraCount ?></strong> / <?= $tongThietBi ?>
                </span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;" class="text-center">STT</th>
                        <th>Mã & Tên tài sản</th>
                        <th>Loại tài sản</th>
                        <th>Hãng / Model</th>
                        <th>Mã QR</th>
                        <th>Tình trạng lúc mượn</th>
                        <th>Trạng thái trả</th>
                        <th>Cán bộ nhận trả</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dsChiTiet)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Không có thiết bị nào trong phiếu mượn này.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dsChiTiet as $idx => $item): ?>
                            <?php $daTra = !empty($item['thoi_gian_tra']); ?>
                            <tr class="<?= $daTra ? 'table-light text-muted' : '' ?>">
                                <td class="text-center font-monospace"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold">
                                        <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$item['tai_san_id']) ?>" class="text-decoration-none <?= $daTra ? 'text-secondary' : 'text-primary' ?>">
                                            <?= e($item['ten_tai_san'] ?? '') ?>
                                        </a>
                                    </div>
                                    <span class="badge bg-secondary font-monospace"><?= e($item['ma_tai_san'] ?? '') ?></span>
                                </td>
                                <td><?= e($item['ten_loai'] ?? '') ?></td>
                                <td>
                                    <?php if (!empty($item['hang_san_xuat']) || !empty($item['model'])): ?>
                                        <span class="badge bg-light text-dark border font-monospace">
                                            <?= e($item['hang_san_xuat'] ?? '') ?> <?= !empty($item['model']) ? '/ ' . e($item['model']) : '' ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">---</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code class="text-dark bg-light px-2 py-1 rounded small"><?= e($item['ma_qr'] ?? '') ?></code>
                                </td>
                                <td>
                                    <?php
                                    $ttMuon = $item['tinh_trang_muon'] ?? 'BINH_THUONG';
                                    if ($ttMuon === 'BINH_THUONG') {
                                        echo '<span class="badge bg-success-subtle text-success border border-success-subtle">Bình thường</span>';
                                    } elseif ($ttMuon === 'XUOC_VO') {
                                        echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Trầy xước nhẹ</span>';
                                    } elseif ($ttMuon === 'THIEU_PHU_KIEN') {
                                        echo '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">Thiếu phụ kiện</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">' . e($ttMuon) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($daTra): ?>
                                        <span class="badge bg-success"><i class="bi bi-check2-circle"></i> Đã trả</span>
                                        <div class="small text-muted mt-1">
                                            <i class="bi bi-clock"></i> <?= dinh_dang_ngay_gio($item['thoi_gian_tra']) ?>
                                        </div>
                                        <?php if (!empty($item['tinh_trang_tra'])): ?>
                                            <div class="small mt-1">
                                                Tình trạng: <strong><?= e($item['tinh_trang_tra']) ?></strong>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($item['ghi_chu_tra'])): ?>
                                            <div class="small text-secondary fst-italic">
                                                Ghi chú: <?= e($item['ghi_chu_tra']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Chưa trả</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($daTra): ?>
                                        <?= e($item['ten_nhan_vien_nhan'] ?? '---') ?>
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
    <!-- Phần chữ ký dành riêng cho bản in thực tế -->
    <div class="d-none d-print-block mt-5 pt-3">
        <div class="row text-center">
            <div class="col-6">
                <div class="fw-bold">NGƯỜI MƯỢN THIẾT BỊ</div>
                <div class="small text-muted fst-italic mb-5">(Ký và ghi rõ họ tên)</div>
                <div class="fw-bold mt-5"><?= e($phieu['ten_nguoi_muon'] ?? '') ?></div>
            </div>
            <div class="col-6">
                <div class="fw-bold">CÁN BỘ BÀN TRỰC GIAO NHẬN</div>
                <div class="small text-muted fst-italic mb-5">(Ký và ghi rõ họ tên)</div>
                <div class="fw-bold mt-5"><?= e($phieu['ten_nhan_vien_lap'] ?? '') ?></div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    header, nav, #sidebarMenu, .breadcrumb, .btn, .alert, footer {
        display: none !important;
    }
    body {
        background-color: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
        color: #000000 !important;
    }
    .container-fluid {
        width: 100% !important;
        max-width: 100% !important;
        padding: 10px !important;
    }
    .card {
        border: 1px solid #333333 !important;
        box-shadow: none !important;
        margin-bottom: 15px !important;
    }
    .card-header {
        background-color: #f8f9fa !important;
        border-bottom: 1px solid #333333 !important;
    }
    .table {
        border-color: #333333 !important;
    }
    .d-print-block {
        display: block !important;
    }
}
</style>

<!-- Nạp thư viện tạo mã QR offline -->
<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script src="<?= url('assets/vendor/qrcode/qrcode_UTF8.js') ?>"></script>
<script src="<?= url('assets/js/qr-tao.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const maPhieu = <?= json_encode($phieu['ma_phieu'] ?? '') ?>;
    taoMaQr('ticket_qr', maPhieu, { size: 130, margin: 1 });
});
</script>

<?php
require dirname(__DIR__) . '/layout/chan-trang.php';
