<?php

/**
 * Giao diện Hồ sơ chi tiết Người mượn & Toàn bộ Lịch sử Mượn trả - Chuẩn Design System
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
?>

<div class="thanh-cong-cu-trang">
    <div>
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

<div class="the-noi-dung p-4 mb-4">
    <div class="row align-items-center">
        <div class="col-12 col-md-8">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="hop-icon-trang rounded-circle">
                    <?= icon('users') ?>
                </div>
                <div>
                    <h3 class="tieu-de-khoi mb-1"><?= e($nguoiMuon['ho_ten']) ?></h3>
                    <div class="text-secondary small">
                        Mã số trường: <strong><?= e($nguoiMuon['ma_so_truong']) ?></strong> &bull; Mã hệ thống: <?= e($nguoiMuon['ma_nguoi_muon']) ?>
                    </div>
                </div>
            </div>

            <div class="row g-2 mt-2 pt-2 border-top small">
                <div class="col-sm-4 d-flex align-items-center gap-1">
                    <span class="text-primary"><?= icon('users', 'bieu-tuong-sm') ?></span>
                    <span class="text-muted">Đối tượng:</span>
                    <strong class="text-primary ms-1">
                        <?= $nguoiMuon['loai_nguoi_muon'] === 'GIANG_VIEN' ? 'Giảng viên' : 'Sinh viên' ?>
                    </strong>
                </div>
                <div class="col-sm-4 d-flex align-items-center gap-1">
                    <span class="text-primary"><?= icon('home', 'bieu-tuong-sm') ?></span>
                    <span class="text-muted">Lớp / Khoa:</span>
                    <strong class="text-dark ms-1"><?= e($nguoiMuon['lop_don_vi'] ?: '-') ?></strong>
                </div>
                <div class="col-sm-4 d-flex align-items-center gap-1">
                    <span class="text-primary"><?= icon('info', 'bieu-tuong-sm') ?></span>
                    <span class="text-muted">Số điện thoại:</span>
                    <strong class="text-dark ms-1"><?= e($nguoiMuon['so_dien_thoai'] ?: '-') ?></strong>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4 mt-3 mt-md-0 text-md-end border-start-md ps-md-4">
            <div class="text-secondary small mb-1">Trạng thái:</div>
            <?php if ((int)$nguoiMuon['so_thiet_bi_dang_muon'] > 0): ?>
                <div class="huy-hieu-tt huy-hieu-tt-canh-bao px-3 py-2 mb-2 d-inline-flex">
                    <?= icon('clock') ?>
                    <span>Đang giữ <?= (int)$nguoiMuon['so_thiet_bi_dang_muon'] ?> thiết bị</span>
                </div>
            <?php else: ?>
                <div class="huy-hieu-tt huy-hieu-tt-thanh-cong px-3 py-2 mb-2 d-inline-flex">
                    <?= icon('check-circle-2') ?>
                    <span>Không giữ thiết bị nào</span>
                </div>
            <?php endif; ?>

            <?php if (co_quyen('nghiep_vu_muon_tra')): ?>
                <div>
                    <a href="<?= url('muon-tra/muon.php?ma_so=' . urlencode($nguoiMuon['ma_so_truong'])) ?>" class="btn btn-sm btn-chinh">
                        <?= icon('plus', 'bieu-tuong-sm') ?>
                        <span>Lập phiếu mượn</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="the-noi-dung mb-4">
    <div class="the-noi-dung-dau d-flex justify-content-between align-items-center">
        <h3 class="tieu-de-khoi mb-0 d-flex align-items-center gap-2">
            <span class="text-primary"><?= icon('clock', 'bieu-tuong-md') ?></span>
            <span>Lịch sử mượn trả tài sản</span>
        </h3>
        <span class="huy-hieu huy-hieu-chinh">
            Tổng cộng <?= count($lichSuMuon) ?> lượt mượn
        </span>
    </div>

    <div class="the-noi-dung-than p-0">
        <?php if (empty($lichSuMuon)): ?>
            <div class="text-center py-4 text-muted">
                Người mượn này chưa từng phát sinh giao dịch mượn thiết bị nào trên hệ thống.
            </div>
        <?php else: ?>
            <div class="accordion accordion-flush" id="accordionLichSu">
                <?php foreach ($lichSuMuon as $idx => $pm): ?>
                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header" id="heading_<?= $pm['id'] ?>">
                            <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $pm['id'] ?>" aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                                <div class="d-flex flex-wrap align-items-center justify-content-between w-100 pe-3 gap-2">
                                    <div>
                                        <span class="badge bg-light text-primary border font-monospace me-2">
                                            <?= e($pm['ma_phieu_muon'] ?? $pm['ma_phieu'] ?? '') ?>
                                        </span>
                                        <span class="fw-semibold text-dark">
                                            <?= e($pm['ten_phong'] ?? '') ?> (<?= e($pm['ma_phong'] ?? '') ?>)
                                        </span>
                                        <span class="text-muted small ms-2">
                                            &bull; Bàn trực: <?= e($pm['ten_phong_truc'] ?? '') ?>
                                        </span>
                                    </div>
                                    <div class="small d-flex align-items-center gap-2">
                                        <span class="text-muted me-2">
                                            Mượn lúc: <strong><?= dinh_dang_ngay_gio($pm['thoi_gian_muon']) ?></strong>
                                        </span>
                                        <?php if ($pm['trang_thai'] === 'DA_TRA'): ?>
                                            <span class="huy-hieu-tt huy-hieu-tt-thanh-cong"><?= icon('check-circle-2') ?> <span>Đã trả đủ</span></span>
                                        <?php else: ?>
                                            <span class="huy-hieu-tt huy-hieu-tt-chinh"><?= icon('repeat') ?> <span>Đang mượn</span></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse_<?= $pm['id'] ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" data-bs-parent="#accordionLichSu">
                            <div class="accordion-body bg-light p-3">
                                <div class="row g-2 mb-2 small text-muted">
                                    <div class="col-sm-4">
                                        Hạn trả dự kiến: <strong class="text-danger"><?= dinh_dang_ngay_gio($pm['han_tra']) ?></strong>
                                    </div>
                                    <div class="col-sm-4">
                                        Cán bộ lập phiếu: <strong class="text-dark"><?= e($pm['ten_nhan_vien_lap'] ?? '') ?></strong>
                                    </div>
                                    <div class="col-sm-4">
                                        Mục đích: <strong class="text-dark"><?= e(($pm['muc_dich_su_dung'] ?? $pm['ghi_chu'] ?? '') ?: 'Giảng dạy / học tập') ?></strong>
                                    </div>
                                </div>

                                <div class="table-responsive bg-white rounded border">
                                    <table class="bang-du-lieu mb-0">
                                        <thead>
                                            <tr>
                                                <th>Mã tài sản (QR)</th>
                                                <th>Tên tài sản</th>
                                                <th>Tình trạng khi mượn</th>
                                                <th>Thời gian trả</th>
                                                <th>Tình trạng khi trả</th>
                                                <th>Cán bộ nhận</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pm['chi_tiet'] as $ct): ?>
                                                <tr>
                                                    <td class="font-monospace fw-semibold text-primary">
                                                        <?= e($ct['ma_tai_san']) ?>
                                                    </td>
                                                    <td class="fw-medium">
                                                        <?= e($ct['ten_tai_san']) ?>
                                                    </td>
                                                    <td>
                                                        <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">
                                                            <?= icon('check-circle-2') ?>
                                                            <span><?= e(TEN_TINH_TRANG[$ct['tinh_trang_khi_muon']] ?? $ct['tinh_trang_khi_muon']) ?></span>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($ct['thoi_gian_tra'] !== null): ?>
                                                            <span class="text-success fw-semibold"><?= dinh_dang_ngay_gio($ct['thoi_gian_tra']) ?></span>
                                                        <?php else: ?>
                                                            <span class="huy-hieu-tt huy-hieu-tt-canh-bao"><?= icon('clock') ?> <span>Chưa trả</span></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($ct['tinh_trang_khi_tra'] !== null): ?>
                                                            <?php if ($ct['tinh_trang_khi_tra'] === 'HU_HONG'): ?>
                                                                <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem"><?= icon('alert-triangle') ?> <span>Hư hỏng</span></span>
                                                                <?php if (!empty($ct['ghi_chu_tra']) || !empty($ct['ghi_chu'])): ?>
                                                                    <div class="text-danger small fst-italic"><?= e($ct['ghi_chu_tra'] ?? $ct['ghi_chu'] ?? '') ?></div>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="huy-hieu-tt huy-hieu-tt-thanh-cong"><?= icon('check-circle-2') ?> <span>Tốt</span></span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
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