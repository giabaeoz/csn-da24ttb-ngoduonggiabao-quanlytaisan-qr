<?php
/**
 * Giao diện: Chi tiết phiếu điều chuyển và in biên bản bàn giao (G7).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';

$dsChiTiet = $phieu['chi_tiet'] ?? [];
$tongThietBi = count($dsChiTiet);
$idsTaiSan = array_column($dsChiTiet, 'tai_san_id');
?>

<style>
@media print {
    /* Ẩn toàn bộ giao diện web khi in biên bản */
    #sidebarMenu, .navbar, .btn-toolbar, .no-print, nav[aria-label="breadcrumb"], footer {
        display: none !important;
    }
    main {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .print-only {
        display: block !important;
    }
    body {
        background-color: #fff !important;
        color: #000 !important;
        font-size: 13px !important;
    }
    .table-bordered th, .table-bordered td {
        border: 1px solid #000 !important;
    }
}
.print-only {
    display: none;
}
</style>

<div class="container-fluid py-3">
    <!-- Breadcrumb & Tiêu đề thanh công cụ -->
    <div class="row align-items-center mb-3 no-print">
        <div class="col-md-7">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('tong-quan.php') ?>" class="text-decoration-none">Tổng quan</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('dieu-chuyen/index.php') ?>" class="text-decoration-none">Điều chuyển</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($phieu['ma_dieu_chuyen']) ?></li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold d-flex align-items-center gap-2">
                <span>Phiếu điều chuyển: <strong class="text-primary font-monospace"><?= e($phieu['ma_dieu_chuyen']) ?></strong></span>
                <span class="badge bg-success fs-6"><i class="bi bi-check2-circle"></i> Đã hoàn tất chuyển</span>
            </h1>
        </div>
        <div class="col-md-5 text-md-end mt-2 mt-md-0 d-flex justify-content-md-end gap-2 flex-wrap">
            <a href="<?= url('dieu-chuyen/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
            <?php if (!empty($idsTaiSan)): ?>
                <a href="<?= url('tai-san/in-qr.php?ids=' . implode(',', $idsTaiSan)) ?>" class="btn btn-outline-dark" target="_blank">
                    <i class="bi bi-qr-code"></i> In tem QR thiết bị
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-primary" onclick="window.print();">
                <i class="bi bi-printer"></i> In biên bản điều chuyển
            </button>
            <?php if (co_quyen('tao_dieu_chuyen')): ?>
                <a href="<?= url('dieu-chuyen/them.php') ?>" class="btn btn-outline-primary">
                    <i class="bi bi-plus-lg"></i> Lập phiếu mới
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KHỐI IN BIÊN BẢN CHÍNH THỨC (HIỂN THỊ KHI IN) -->
    <div class="print-only mb-4">
        <div class="row align-items-center border-bottom pb-3 mb-3">
            <div class="col-7 text-center">
                <div class="fw-bold small text-uppercase">TRƯỜNG ĐẠI HỌC TRÀ VINH</div>
                <div class="fw-bold fs-6 text-uppercase">PHÒNG QUẢN TRỊ THIẾT BỊ & CƠ SỞ VẬT CHẤT</div>
                <div class="small fst-italic">Số: ......./BB-ĐCTB</div>
            </div>
            <div class="col-5 text-center">
                <div class="fw-bold small text-uppercase">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                <div class="fw-bold small">Độc lập - Tự do - Hạnh phúc</div>
                <div class="small fst-italic">Trà Vinh, ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></div>
            </div>
        </div>
        <div class="text-center my-3">
            <h4 class="fw-bold text-uppercase mb-1">BIÊN BẢN BÀN GIAO & ĐIỀU CHUYỂN THIẾT BỊ</h4>
            <div class="small text-muted font-monospace">Mã phiếu: <strong><?= e($phieu['ma_dieu_chuyen']) ?></strong></div>
        </div>
    </div>

    <div class="row g-4">
        <!-- CỘT TRÁI: THÔNG TIN CHI TIẾT PHIẾU -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-primary"></i> Thông tin phiếu điều chuyển
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="text-muted small">Thời gian điều chuyển:</label>
                            <div class="fw-semibold text-dark">
                                <i class="bi bi-calendar3 me-1 text-primary"></i>
                                <?= e(f_ngay_gio($phieu['thoi_gian_dieu_chuyen'])) ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="text-muted small">Cán bộ lập phiếu:</label>
                            <div class="fw-semibold text-dark">
                                <i class="bi bi-person me-1 text-primary"></i>
                                <?= e($phieu['ho_ten_nguoi_lap']) ?> 
                                <span class="text-muted font-monospace">(<?= e($phieu['ma_nhan_vien_lap']) ?>)</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light rounded border">
                                <div class="row align-items-center text-center">
                                    <div class="col-md-5">
                                        <div class="small text-muted text-uppercase fw-bold">Phòng học nguồn (Phòng đi)</div>
                                        <div class="fs-5 fw-bold text-secondary mt-1">
                                            <?= e($phieu['ma_phong_di']) ?>
                                        </div>
                                        <div class="small text-muted"><?= e($phieu['ten_phong_di']) ?></div>
                                    </div>
                                    <div class="col-md-2 my-2 my-md-0">
                                        <div class="badge bg-primary rounded-pill p-2 fs-6">
                                            <i class="bi bi-arrow-right"></i>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="small text-muted text-uppercase fw-bold">Phòng học đích (Phòng đến)</div>
                                        <div class="fs-5 fw-bold text-success mt-1">
                                            <?= e($phieu['ma_phong_den']) ?>
                                        </div>
                                        <div class="small text-muted"><?= e($phieu['ten_phong_den']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="text-muted small">Lý do điều chuyển:</label>
                            <div class="p-2 bg-light rounded text-dark fst-italic">
                                "<?= e($phieu['ly_do']) ?>"
                            </div>
                        </div>

                        <?php if (!empty($phieu['ghi_chu'])): ?>
                            <div class="col-12">
                                <label class="text-muted small">Ghi chú kèm theo:</label>
                                <div class="p-2 bg-light rounded text-dark">
                                    <?= e($phieu['ghi_chu']) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- BẢNG DANH SÁCH THIẾT BỊ ĐIỀU CHUYỂN -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        Danh sách thiết bị chuyển giao (<span class="text-primary fw-bold"><?= $tongThietBi ?></span> thiết bị)
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light text-secondary text-uppercase small">
                            <tr>
                                <th class="text-center" style="width: 40px;">STT</th>
                                <th style="width: 140px;">Mã tài sản</th>
                                <th>Tên thiết bị</th>
                                <th style="width: 140px;">Hãng / Model</th>
                                <th style="width: 120px;" class="text-center">Tình trạng</th>
                                <th style="width: 130px;" class="text-center">Vị trí hiện tại</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dsChiTiet)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Không có thiết bị trong phiếu.</td>
                                </tr>
                            <?php else: ?>
                                <?php $stt = 1; foreach ($dsChiTiet as $item): ?>
                                    <tr>
                                        <td class="text-center text-muted small"><?= $stt++ ?></td>
                                        <td>
                                            <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$item['tai_san_id']) ?>" 
                                               class="font-monospace fw-bold text-primary text-decoration-none">
                                                <?= e($item['ma_tai_san']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark small"><?= e($item['ten_tai_san']) ?></div>
                                            <div class="small text-muted"><?= e($item['ten_loai_tai_san']) ?></div>
                                        </td>
                                        <td class="small text-muted">
                                            <?= e((string)($item['hang_san_xuat'] ?? '') . ($item['model'] ? ' / ' . $item['model'] : '')) ?: '—' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                Tốt
                                            </span>
                                        </td>
                                        <td class="text-center font-monospace small fw-bold text-success">
                                            <?= e($phieu['ma_phong_den']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CHỮ KÝ XÁC NHẬN KHI IN (PRINT ONLY) -->
            <div class="print-only mt-4 pt-3">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fw-bold small text-uppercase">NGƯỜI LẬP PHIẾU</div>
                        <div class="small text-muted fst-italic">(Ký và ghi rõ họ tên)</div>
                        <div style="height: 70px;"></div>
                        <div class="fw-bold"><?= e($phieu['ho_ten_nguoi_lap']) ?></div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold small text-uppercase">ĐẠI DIỆN TIẾP NHẬN</div>
                        <div class="small text-muted fst-italic">(Ký và ghi rõ họ tên)</div>
                        <div style="height: 70px;"></div>
                        <div class="fw-bold">........................................</div>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold small text-uppercase">PHÒNG QUẢN TRỊ THIẾT BỊ</div>
                        <div class="small text-muted fst-italic">(Ký, đóng dấu xác nhận)</div>
                        <div style="height: 70px;"></div>
                        <div class="fw-bold">........................................</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI: MÃ QR PHIẾU & THÔNG TIN TIỆN ÍCH -->
        <div class="col-lg-4 no-print">
            <div class="card border-0 shadow-sm text-center p-3 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-3">Mã QR phiếu điều chuyển</h6>
                    <div class="d-flex justify-content-center mb-3">
                        <div id="transfer_qr" data-qr="<?= e($phieu['ma_dieu_chuyen']) ?>" data-qr-size="160" 
                             class="p-2 bg-white border rounded shadow-sm" style="min-width: 164px; min-height: 164px;"></div>
                    </div>
                    <div class="font-monospace fw-bold text-primary fs-6 mb-1"><?= e($phieu['ma_dieu_chuyen']) ?></div>
                    <p class="small text-muted mb-0">Quét mã QR này tại bất kỳ máy quét nào để tra cứu nhanh thông tin phiếu.</p>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-3">
                <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-check text-success me-1"></i> Kiểm soát an toàn</h6>
                <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-2">
                        <i class="bi bi-check2 text-success me-1"></i>
                        Vị trí của <strong><?= $tongThietBi ?></strong> thiết bị đã được tự động cập nhật sang <strong>Phòng <?= e($phieu['ma_phong_den']) ?></strong>.
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-check2 text-success me-1"></i>
                        Phòng trực quản lý tài sản vẫn được bảo toàn nguyên vẹn.
                    </li>
                    <li>
                        <i class="bi bi-check2 text-success me-1"></i>
                        Biên bản bàn giao đã sẵn sàng để in lưu trữ hồ sơ cơ sở vật chất.
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Nạp thư viện tạo mã QR offline -->
<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script src="<?= url('assets/vendor/qrcode/qrcode_UTF8.js') ?>"></script>
<script src="<?= url('assets/js/qr-tao.js') ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('transfer_qr');
    if (el && typeof taoMaQr === 'function') {
        taoMaQr(el, el.getAttribute('data-qr') || '', 160);
    }
});
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>

