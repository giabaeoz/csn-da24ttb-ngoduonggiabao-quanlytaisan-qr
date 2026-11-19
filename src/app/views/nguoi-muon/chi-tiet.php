<?php

/**
 * Giao diện Hồ sơ chi tiết Người mượn & Toàn bộ Lịch sử Mượn trả - Chuẩn Design System hài hòa
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var array<string, mixed> $nguoiMuon
 * @var list<array<string, mixed>> $lichSuMuon
 */

$tieuDeTrang = 'Hồ sơ người mượn: ' . ($nguoiMuon['ho_ten'] ?? '');
require dirname(__DIR__) . '/layout/dau-trang.php';

$soDangMuon = (int)($nguoiMuon['so_thiet_bi_dang_muon'] ?? 0);
$chuVietTat = function_exists('chu_cai_dau_ho_ten') ? chu_cai_dau_ho_ten((string)($nguoiMuon['ho_ten'] ?? 'NM')) : 'NM';
$laGiangVien = ($nguoiMuon['loai_nguoi_muon'] ?? '') === 'GIANG_VIEN';
?>

<div class="thanh-cong-cu-trang mb-3">
    <div>
        <nav aria-label="breadcrumb" class="d-none d-md-block mb-1">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="<?= url('nguoi-muon/index.php') ?>" class="text-decoration-none text-muted">Người mượn</a></li>
                <li class="breadcrumb-item active text-dark fw-semibold" aria-current="page">Hồ sơ chi tiết</li>
            </ol>
        </nav>
        <h1 class="tieu-de-trang mb-0">Hồ sơ người mượn: <?= e($nguoiMuon['ho_ten']) ?></h1>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('nguoi-muon/sua.php?id=' . (int)$nguoiMuon['id']) ?>" class="btn btn-phu">
            <?= icon('pencil', 'bieu-tuong-sm') ?>
            <span>Chỉnh sửa hồ sơ</span>
        </a>
        <a href="<?= url('nguoi-muon/index.php') ?>" class="btn btn-phu">
            <?= icon('arrow-left', 'bieu-tuong-sm') ?>
            <span>Quay lại</span>
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white overflow-hidden">
    <div class="card-body p-4">
        <!-- Hàng đầu: Thông tin định danh & Trạng thái -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 pb-3 border-bottom">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-nguoi-muon rounded-3 text-white fw-bold d-flex align-items-center justify-content-center shadow-sm"
                     style="width: 56px; height: 56px; font-size: 20px; background: linear-gradient(135deg, #1768D8 0%, #0F4FB5 100%); flex-shrink: 0;">
                    <?= e($chuVietTat) ?>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h2 class="h4 fw-bold text-dark mb-0"><?= e($nguoiMuon['ho_ten']) ?></h2>
                        <?php if ($laGiangVien): ?>
                            <span class="badge rounded-pill px-2.5 py-1 text-white" style="background-color: #4B49C8; font-size: 12px;">
                                <?= icon('user-check', 'bieu-tuong-sm me-1 text-white') ?> Giảng viên
                            </span>
                        <?php else: ?>
                            <span class="badge rounded-pill px-2.5 py-1 text-white" style="background-color: #1768D8; font-size: 12px;">
                                <?= icon('user', 'bieu-tuong-sm me-1 text-white') ?> Sinh viên
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-muted small flex-wrap">
                        <span>Mã trường: <strong class="text-dark font-monospace bg-light px-2 py-0.5 rounded border"><?= e($nguoiMuon['ma_so_truong']) ?></strong></span>
                        <span class="text-secondary-subtle">&bull;</span>
                        <span>Mã hệ thống: <span class="font-monospace"><?= e($nguoiMuon['ma_nguoi_muon']) ?></span></span>
                    </div>
                </div>
            </div>

            <!-- Cụm trạng thái mượn và nút thao tác -->
            <div class="d-flex flex-wrap align-items-center gap-2 ms-md-auto">
                <?php if ($soDangMuon > 0): ?>
                    <span class="huy-hieu-tt huy-hieu-tt-canh-bao px-3 py-2 fw-semibold">
                        <?= icon('clock', 'bieu-tuong-sm') ?>
                        <span>Đang giữ <?= $soDangMuon ?> thiết bị</span>
                    </span>
                <?php else: ?>
                    <span class="huy-hieu-tt huy-hieu-tt-thanh-cong px-3 py-2 fw-semibold">
                        <?= icon('check-circle-2', 'bieu-tuong-sm') ?>
                        <span>Không giữ thiết bị nào</span>
                    </span>
                <?php endif; ?>

                <?php if (co_quyen('nghiep_vu_muon_tra')): ?>
                    <a href="<?= url('muon-tra/muon.php?ma_so=' . urlencode((string)$nguoiMuon['ma_so_truong'])) ?>" class="btn btn-chinh">
                        <?= icon('plus', 'bieu-tuong-sm') ?>
                        <span>Lập phiếu mượn</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Hàng 3 khối thông tin chi tiết -->
        <div class="row g-3 pt-3">
            <div class="col-12 col-sm-4">
                <div class="p-3 rounded-3 bg-light border border-light-subtle d-flex align-items-center gap-3 h-100">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-primary" 
                         style="width: 42px; height: 42px; background-color: var(--the-xanh); flex-shrink: 0;">
                        <?= icon('building', 'bieu-tuong-md') ?>
                    </div>
                    <div>
                        <div class="text-muted small">Lớp / Đơn vị</div>
                        <div class="fw-semibold text-dark fs-6"><?= e($nguoiMuon['lop_don_vi'] ?: '---') ?></div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-4">
                <div class="p-3 rounded-3 bg-light border border-light-subtle d-flex align-items-center gap-3 h-100">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-success" 
                         style="width: 42px; height: 42px; background-color: var(--the-la); flex-shrink: 0;">
                        <?= icon('phone', 'bieu-tuong-md') ?>
                    </div>
                    <div>
                        <div class="text-muted small">Số điện thoại</div>
                        <div class="fw-semibold text-dark fs-6">
                            <?php if (!empty($nguoiMuon['so_dien_thoai'])): ?>
                                <a href="tel:<?= e($nguoiMuon['so_dien_thoai']) ?>" class="text-dark text-decoration-none hover-primary">
                                    <?= e($nguoiMuon['so_dien_thoai']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">---</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-sm-4">
                <div class="p-3 rounded-3 bg-light border border-light-subtle d-flex align-items-center gap-3 h-100">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 42px; height: 42px; background-color: var(--the-tim); color: var(--bieu-tuong-tim); flex-shrink: 0;">
                        <?= icon('repeat', 'bieu-tuong-md') ?>
                    </div>
                    <div>
                        <div class="text-muted small">Lịch sử mượn trả</div>
                        <div class="fw-semibold text-dark fs-6">
                            <span class="text-primary fw-bold"><?= count($lichSuMuon) ?></span> lượt mượn thiết bị
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white overflow-hidden">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h3 class="tieu-de-khoi mb-0 d-flex align-items-center gap-2">
            <span class="text-primary"><?= icon('clock', 'bieu-tuong-md') ?></span>
            <span>Lịch sử mượn trả tài sản</span>
        </h3>
        <span class="huy-hieu huy-hieu-chinh">
            Tổng cộng <?= count($lichSuMuon) ?> lượt mượn
        </span>
    </div>

    <div class="card-body p-0">
        <?php if (empty($lichSuMuon)): ?>
            <div class="text-center py-5">
                <?= minh_hoa_hop_trong('Người mượn này chưa từng phát sinh giao dịch mượn thiết bị nào trên hệ thống.') ?>
            </div>
        <?php else: ?>
            <div class="accordion accordion-flush" id="accordionLichSu">
                <?php foreach ($lichSuMuon as $idx => $pm): ?>
                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header" id="heading_<?= $pm['id'] ?>">
                            <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?> py-3 px-4" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#collapse_<?= $pm['id'] ?>" 
                                    aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                                <div class="d-flex flex-wrap align-items-center justify-content-between w-100 pe-3 gap-3">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="badge bg-light text-primary border font-monospace px-2.5 py-1">
                                            <?= e($pm['ma_phieu_muon'] ?? $pm['ma_phieu'] ?? '') ?>
                                        </span>
                                        <span class="fw-bold text-dark d-inline-flex align-items-center gap-1">
                                            <?= icon('home', 'bieu-tuong-sm text-secondary') ?>
                                            <span><?= e($pm['ten_phong'] ?? '') ?></span>
                                            <span class="badge bg-secondary-subtle text-secondary fw-normal font-monospace ms-1"><?= e($pm['ma_phong'] ?? '') ?></span>
                                        </span>
                                        <span class="text-muted small ms-2 d-none d-md-inline">
                                            &bull; <?= icon('map-pin', 'bieu-tuong-sm text-muted me-1') ?><?= e($pm['ten_phong_truc'] ?? '') ?>
                                        </span>
                                    </div>
                                    <div class="small d-flex align-items-center gap-3">
                                        <span class="text-muted d-inline-flex align-items-center gap-1">
                                            <?= icon('calendar', 'bieu-tuong-sm text-muted') ?>
                                            <span>Mượn lúc: <strong class="text-dark"><?= dinh_dang_ngay_gio($pm['thoi_gian_muon']) ?></strong></span>
                                        </span>
                                        <?php if ($pm['trang_thai'] === 'DA_TRA'): ?>
                                            <span class="huy-hieu-tt huy-hieu-tt-thanh-cong"><?= icon('check-circle-2') ?> <span>Đã trả đủ</span></span>
                                        <?php else: ?>
                                            <span class="huy-hieu-tt huy-hieu-tt-canh-bao"><?= icon('repeat') ?> <span>Đang mượn</span></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse_<?= $pm['id'] ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" data-bs-parent="#accordionLichSu">
                            <div class="accordion-body bg-light p-3 p-md-4">
                                <div class="p-3 bg-white rounded-3 mb-3 border">
                                    <div class="row g-2 small text-muted">
                                        <div class="col-12 col-md-4 d-flex align-items-center gap-1">
                                            <?= icon('clock', 'bieu-tuong-sm text-danger') ?>
                                            <span>Hạn trả dự kiến:</span>
                                            <strong class="text-danger"><?= dinh_dang_ngay_gio($pm['han_tra']) ?></strong>
                                        </div>
                                        <div class="col-12 col-md-4 d-flex align-items-center gap-1">
                                            <?= icon('user', 'bieu-tuong-sm text-primary') ?>
                                            <span>Cán bộ lập phiếu:</span>
                                            <strong class="text-dark"><?= e($pm['ten_nhan_vien_lap'] ?? '---') ?></strong>
                                        </div>
                                        <div class="col-12 col-md-4 d-flex align-items-center gap-1">
                                            <?= icon('clipboard-check', 'bieu-tuong-sm text-secondary') ?>
                                            <span>Mục đích:</span>
                                            <strong class="text-dark"><?= e(($pm['muc_dich_su_dung'] ?? $pm['ghi_chu'] ?? '') ?: 'Giảng dạy / học tập') ?></strong>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive bg-white rounded-3 border">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr class="text-secondary small">
                                                <th class="ps-3 py-2.5">Mã tài sản (QR)</th>
                                                <th class="py-2.5">Tên tài sản</th>
                                                <th class="py-2.5">Tình trạng lúc mượn</th>
                                                <th class="py-2.5">Thời gian trả</th>
                                                <th class="py-2.5">Tình trạng khi trả</th>
                                                <th class="pe-3 py-2.5">Cán bộ nhận</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pm['chi_tiet'] as $ct): ?>
                                                <tr>
                                                    <td class="ps-3 text-nowrap">
                                                        <span class="badge bg-light text-primary border font-monospace px-2 py-1">
                                                            <?= e($ct['ma_tai_san']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold text-dark"><?= e($ct['ten_tai_san']) ?></span>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">
                                                            <?= icon('check-circle-2') ?>
                                                            <span><?= e(TEN_TINH_TRANG[$ct['tinh_trang_khi_muon']] ?? $ct['tinh_trang_khi_muon']) ?></span>
                                                        </span>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <?php if ($ct['thoi_gian_tra'] !== null): ?>
                                                            <span class="text-success small fw-semibold d-inline-flex align-items-center gap-1">
                                                                <?= icon('check', 'bieu-tuong-sm text-success') ?>
                                                                <?= dinh_dang_ngay_gio($ct['thoi_gian_tra']) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="huy-hieu-tt huy-hieu-tt-canh-bao">
                                                                <?= icon('clock') ?>
                                                                <span>Chưa trả</span>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-nowrap">
                                                        <?php if ($ct['thoi_gian_tra'] !== null): ?>
                                                            <?php if ($ct['tinh_trang_khi_tra'] === 'HU_HONG'): ?>
                                                                <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">
                                                                    <?= icon('alert-triangle') ?>
                                                                    <span>Hư hỏng</span>
                                                                </span>
                                                                <?php if (!empty($ct['ghi_chu_tra']) || !empty($ct['ghi_chu'])): ?>
                                                                    <div class="text-danger small fst-italic mt-0.5"><?= e($ct['ghi_chu_tra'] ?? $ct['ghi_chu'] ?? '') ?></div>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">
                                                                    <?= icon('check-circle-2') ?>
                                                                    <span>Tốt</span>
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="pe-3 text-nowrap text-muted small">
                                                        <?= e($ct['ten_nhan_vien_nhan'] ?: '-') ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
