<?php
/**
 * Giao diện Bảng điều khiển Tổng quan (Dashboard Frame).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Tổng quan hệ thống';
require __DIR__ . '/layout/dau-trang.php';
require __DIR__ . '/layout/menu.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <h1 class="h3 fw-bold text-dark mb-0">Bảng điều khiển Tổng quan</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-primary fs-6 px-3 py-2 shadow-sm">
            <?= date('d/m/Y') ?>
        </span>
    </div>
</div>

<!-- Lời chào cán bộ đang trực -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h4 class="fw-bold text-primary mb-1">
                    Xin chào, <?= e($nguoiDung['ho_ten']) ?>! 👋
                </h4>
                <p class="text-secondary mb-0">
                    Vai trò hiện tại: <strong><?= e(TEN_VAI_TRO[$nguoiDung['vai_tro']] ?? $nguoiDung['vai_tro']) ?></strong>
                    <?php if (!empty($nguoiDung['ten_phong_truc'])): ?>
                        &bull; Phụ trách: <strong><?= e($nguoiDung['ten_phong_truc']) ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <?php if (co_quyen('nghiep_vu_muon_tra')): ?>
                <a href="<?= url('muon-tra/muon.php') ?>" class="btn btn-primary fw-semibold me-2 shadow-sm">
                    📤 Tạo phiếu mượn QR
                </a>
                <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-success fw-semibold shadow-sm">
                    📥 Nhận trả thiết bị
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Khung thống kê nhanh (Placeholder cho G2) -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded-3 p-3 fs-3 me-3">
                        💻
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Tổng tài sản</div>
                        <h4 class="fw-bold mb-0 text-dark">--</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded-3 p-3 fs-3 me-3">
                        ✅
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Đang sẵn sàng</div>
                        <h4 class="fw-bold mb-0 text-dark">--</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-warning bg-opacity-10 text-warning rounded-3 p-3 fs-3 me-3">
                        📤
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Đang được mượn</div>
                        <h4 class="fw-bold mb-0 text-dark">--</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-danger bg-opacity-10 text-danger rounded-3 p-3 fs-3 me-3">
                        ⚠️
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Sự cố / Báo hỏng</div>
                        <h4 class="fw-bold mb-0 text-dark">--</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require __DIR__ . '/layout/chan-trang.php';
?>
