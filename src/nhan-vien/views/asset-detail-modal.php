<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $selectedAsset
 * @var array<string, mixed> $assetHistory
 * @var callable $link
 * @var string $tab
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/assets.php';

if (!isset($selectedAsset, $assetHistory)) {
    return;
}

$closeUrl = $link(['id' => null]);
?>
<div class="modal fade" id="staffAssetDetailModal" tabindex="-1" aria-labelledby="staffAssetDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="badge bg-secondary mb-1">Chi tiết tài sản phòng trực</span>
                    <h5 class="modal-title fw-bold" id="staffAssetDetailTitle">
                        <?= e($selectedAsset['ma_tai_san']) ?> · <?= e($selectedAsset['ten_tai_san']) ?>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"
                        onclick="window.location.href='<?= e($closeUrl) ?>'"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-lg-7 col-md-7">
                        <h6 class="fw-bold mb-3 border-bottom pb-2">Thông tin kỹ thuật & quản lý</h6>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted">Mã tài sản:</dt>
                            <dd class="col-sm-8 fw-semibold"><?= e($selectedAsset['ma_tai_san']) ?></dd>

                            <dt class="col-sm-4 text-muted">Tên tài sản:</dt>
                            <dd class="col-sm-8"><?= e($selectedAsset['ten_tai_san']) ?></dd>

                            <dt class="col-sm-4 text-muted">Loại tài sản:</dt>
                            <dd class="col-sm-8"><?= e($selectedAsset['ten_loai']) ?></dd>

                            <dt class="col-sm-4 text-muted">Phòng trực quản lý:</dt>
                            <dd class="col-sm-8"><?= e($selectedAsset['ten_phong_truc']) ?></dd>

                            <dt class="col-sm-4 text-muted">Thuộc phòng / Vị trí:</dt>
                            <dd class="col-sm-8">
                                <?php if (!empty($selectedAsset['phong_hien_tai'])): ?>
                                    <span class="badge bg-info text-dark">Phòng <?= e($selectedAsset['phong_hien_tai']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Tại phòng trực thiết bị</span>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4 text-muted">Tình trạng:</dt>
                            <dd class="col-sm-8">
                                <span class="badge bg-light text-dark border">
                                    <?= e(ASSET_CONDITIONS[$selectedAsset['tinh_trang']] ?? $selectedAsset['tinh_trang']) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-4 text-muted">Trạng thái:</dt>
                            <dd class="col-sm-8">
                                <span class="badge <?= match($selectedAsset['trang_thai_hien_thi']) {
                                    'SAN_SANG' => 'bg-success',
                                    'DANG_MUON' => 'bg-primary',
                                    'BAO_TRI' => 'bg-warning text-dark',
                                    'NGUNG_SU_DUNG' => 'bg-secondary',
                                    default => 'bg-secondary'
                                } ?>">
                                    <?= e(ASSET_STATES[$selectedAsset['trang_thai_hien_thi']] ?? $selectedAsset['trang_thai_hien_thi']) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-4 text-muted">Ngày đưa vào sử dụng:</dt>
                            <dd class="col-sm-8"><?= e($selectedAsset['ngay_dua_vao_su_dung'] ?? 'Chưa ghi nhận') ?></dd>

                            <?php if (!empty($selectedAsset['mo_ta'])): ?>
                                <dt class="col-sm-4 text-muted">Mô tả / Ghi chú:</dt>
                                <dd class="col-sm-8 text-break"><?= nl2br(e($selectedAsset['mo_ta'])) ?></dd>
                            <?php endif; ?>
                        </dl>
                    </div>

                    <div class="col-lg-5 col-md-5">
                        <div class="small text-muted fw-semibold mb-2">Tem mã QR (Khổ mini nhỏ gọn):</div>
                        <?php
                        $qrRoomTag = ($selectedAsset['phong_hien_tai'] ?? '') ?: (($selectedAsset['phong_cu'] ?? '') ?: 'PHÒNG TRỰC');
                        ?>
                        <div class="p-2 border rounded mb-2" style="max-width: 250px; background: #fff; border: 1.2px solid #000 !important; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                            <div style="font-size: 12px; font-weight: 900; color: #000; letter-spacing: 0.3px; border-bottom: 1.2px solid #000; padding-bottom: 2px; margin-bottom: 4px; text-transform: uppercase; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?= e($qrRoomTag) ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="qr-code-box flex-shrink-0 p-1" id="detail-modal-qr" style="width: 66px; height: 66px;">
                                    <div data-qr="<?= e($selectedAsset['ma_qr']) ?>" style="width: 58px; height: 58px;"></div>
                                </div>
                                <div class="flex-grow-1 overflow-hidden" style="min-width: 0;">
                                    <div style="font-size: 10.5px; font-weight: 700; font-family: monospace; color: #000;"><?= e($selectedAsset['ma_tai_san']) ?></div>
                                    <div style="font-size: 10px; font-weight: 600; color: #222; margin-top: 1px; line-height: 1.2; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;"><?= e($selectedAsset['ten_tai_san']) ?></div>
                                    <div style="font-size: 8.5px; color: #666; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Phòng trực: <?= e($selectedAsset['ten_phong_truc'] ?? ($desk['ten_phong_truc'] ?? '')) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-1" style="max-width: 250px;">
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sketch btn-sm py-1 flex-fill" style="font-size: 11px;"
                                        data-action="download-svg" data-asset-code="<?= e($selectedAsset['ma_tai_san']) ?>">
                                    Tải SVG
                                </button>
                                <button type="button" class="btn btn-sketch btn-sm py-1 flex-fill" style="font-size: 11px;"
                                        data-action="download-png" data-asset-code="<?= e($selectedAsset['ma_tai_san']) ?>">
                                    Tải PNG
                                </button>
                            </div>
                            <button type="button" class="btn btn-sketch-dark btn-sm py-1" style="font-size: 11px;"
                                    data-action="print-single-qr" data-asset-id="<?= (int)$selectedAsset['id'] ?>">
                                In tem mã QR này
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <h6 class="fw-bold mb-3">Lịch sử nghiệp vụ thiết bị</h6>
                    <div class="accordion asset-history-accordion" id="assetHistoryAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingLoans">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLoans" aria-expanded="true" aria-controls="collapseLoans">
                                    Lịch sử mượn – trả (<?= count($assetHistory['loans']) ?> lượt)
                                </button>
                            </h2>
                            <div id="collapseLoans" class="accordion-collapse collapse show" aria-labelledby="headingLoans" data-bs-parent="#assetHistoryAccordion">
                                <div class="accordion-body p-0">
                                    <?php if ($assetHistory['loans']): ?>
                                        <?php foreach ($assetHistory['loans'] as $loan): ?>
                                            <div class="history-item-card">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong><?= e($loan['ma_phieu_muon']) ?></strong>
                                                    <span class="badge <?= $loan['thoi_gian_tra'] ? 'bg-success' : ($loan['qua_han'] ? 'bg-danger' : 'bg-primary') ?>">
                                                        <?= $loan['thoi_gian_tra'] ? 'Đã trả' : ($loan['qua_han'] ? 'Quá hạn' : 'Đang mượn') ?>
                                                    </span>
                                                </div>
                                                <div class="text-muted small">
                                                    Người mượn: <strong><?= e($loan['ho_ten']) ?></strong> (<?= e($loan['ma_so_truong']) ?>)
                                                </div>
                                                <div class="text-muted small">
                                                    Mượn: <?= e($loan['thoi_gian_muon']) ?> · Hạn trả: <?= e($loan['han_tra']) ?>
                                                    <?= $loan['thoi_gian_tra'] ? ' · Trả lúc: ' . e($loan['thoi_gian_tra']) : '' ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted p-3 mb-0 small">Chưa có lịch sử mượn – trả cho tài sản này.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingInventory">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInventory" aria-expanded="false" aria-controls="collapseInventory">
                                    Lịch sử kiểm kê (<?= count($assetHistory['inventory']) ?> đợt)
                                </button>
                            </h2>
                            <div id="collapseInventory" class="accordion-collapse collapse" aria-labelledby="headingInventory" data-bs-parent="#assetHistoryAccordion">
                                <div class="accordion-body p-0">
                                    <?php if ($assetHistory['inventory']): ?>
                                        <?php foreach ($assetHistory['inventory'] as $inv): ?>
                                            <div class="history-item-card">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong><?= e($inv['ma_dot_kiem_ke']) ?> - <?= e($inv['ten_dot_kiem_ke']) ?></strong>
                                                    <span class="badge bg-secondary"><?= e($inv['ket_qua']) ?></span>
                                                </div>
                                                <div class="text-muted small">
                                                    Bắt đầu đợt: <?= e($inv['thoi_gian_bat_dau']) ?>
                                                    <?= !empty($inv['thoi_gian_kiem_tra']) ? ' · Kiểm tra: ' . e($inv['thoi_gian_kiem_tra']) : '' ?>
                                                    <?= !empty($inv['ma_phong']) ? ' · Phòng: ' . e($inv['ma_phong']) : '' ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted p-3 mb-0 small">Chưa có dữ liệu kiểm kê cho tài sản này.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTransfers">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTransfers" aria-expanded="false" aria-controls="collapseTransfers">
                                    Lịch sử điều chuyển phòng học (<?= count($assetHistory['transfers']) ?> lần)
                                </button>
                            </h2>
                            <div id="collapseTransfers" class="accordion-collapse collapse" aria-labelledby="headingTransfers" data-bs-parent="#assetHistoryAccordion">
                                <div class="accordion-body p-0">
                                    <?php if ($assetHistory['transfers']): ?>
                                        <?php foreach ($assetHistory['transfers'] as $trans): ?>
                                            <div class="history-item-card">
                                                <strong><?= e($trans['ma_dieu_chuyen']) ?></strong>:
                                                Phòng <?= e($trans['phong_di']) ?> &rarr; Phòng <?= e($trans['phong_den']) ?>
                                                <div class="text-muted small">
                                                    Thời gian: <?= e($trans['thoi_gian_dieu_chuyen']) ?> · Lý do: <?= e($trans['ly_do']) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted p-3 mb-0 small">Chưa có lượt điều chuyển nào.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingIncidents">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIncidents" aria-expanded="false" aria-controls="collapseIncidents">
                                    Ghi nhận sự cố & bảo trì (<?= count($assetHistory['incidents']) ?> sự cố)
                                </button>
                            </h2>
                            <div id="collapseIncidents" class="accordion-collapse collapse" aria-labelledby="headingIncidents" data-bs-parent="#assetHistoryAccordion">
                                <div class="accordion-body p-0">
                                    <?php if ($assetHistory['incidents']): ?>
                                        <?php foreach ($assetHistory['incidents'] as $inc): ?>
                                            <div class="history-item-card">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <strong><?= e($inc['ma_su_co']) ?> (<?= e($inc['loai_su_co']) ?>)</strong>
                                                    <span class="badge bg-warning text-dark"><?= e($inc['trang_thai']) ?></span>
                                                </div>
                                                <div class="small mt-1"><?= e($inc['mo_ta']) ?></div>
                                                <div class="text-muted small">Phát hiện: <?= e($inc['thoi_gian_phat_hien']) ?></div>
                                                <?php if (!empty($inc['ket_qua_xu_ly'])): ?>
                                                    <div class="text-success small mt-1">Xử lý: <?= e($inc['ket_qua_xu_ly']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted p-3 mb-0 small">Không có sự cố nào được ghi nhận cho tài sản này.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <a href="<?= e($link(['id' => $selectedAsset['id'], 'edit' => 1])) ?>" class="btn btn-dark">
                    Chỉnh sửa tài sản
                </a>
                <a href="<?= e($closeUrl) ?>" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Đóng
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('staffAssetDetailModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        modalEl.addEventListener('hidden.bs.modal', () => {
            window.location.href = '<?= e($closeUrl) ?>';
        }, { once: true });
    }
});
</script>

