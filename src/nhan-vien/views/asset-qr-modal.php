<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $selectedAsset
 * @var callable $link
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($selectedAsset)) {
    return;
}

$closeUrl = $link(['id' => null]);
?>
<div class="modal fade" id="staffAssetQrModal" tabindex="-1" aria-labelledby="staffAssetQrTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border">
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold" id="staffAssetQrTitle">
                    Mã QR: <?= e($selectedAsset['ma_tai_san']) ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"
                        onclick="window.location.href='<?= e($closeUrl) ?>'"></button>
            </div>

            <div class="modal-body text-center p-4">
                <?php
                $qrRoomTag = ($selectedAsset['phong_hien_tai'] ?? '') ?: (($selectedAsset['phong_cu'] ?? '') ?: 'PHÒNG TRỰC');
                ?>
                <div class="p-3 border rounded mx-auto mb-3" style="max-width: 280px; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
                    <div style="font-size: 20px; font-weight: 900; color: #000; letter-spacing: 0.5px; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 12px; text-transform: uppercase;">
                        <?= e($qrRoomTag) ?>
                    </div>
                    <div class="qr-code-box mx-auto mb-2" style="width: 180px; height: 180px;">
                        <div data-qr="<?= e($selectedAsset['ma_qr']) ?>" style="width: 160px; height: 160px;"></div>
                    </div>
                    <div style="font-size: 14px; font-weight: 700; font-family: monospace; color: #000;"><?= e($selectedAsset['ma_tai_san']) ?></div>
                    <div style="font-size: 13px; font-weight: 600; color: #222; margin-top: 4px;"><?= e($selectedAsset['ten_tai_san']) ?></div>
                    <div style="font-size: 11px; color: #555; margin-top: 4px;">Phòng trực: <?= e($selectedAsset['ten_phong_truc'] ?? ($desk['ten_phong_truc'] ?? '')) ?></div>
                </div>

                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <button type="button" class="btn btn-sketch btn-sm"
                            data-action="download-svg" data-asset-code="<?= e($selectedAsset['ma_tai_san']) ?>">
                        Tải SVG
                    </button>
                    <button type="button" class="btn btn-sketch btn-sm"
                            data-action="download-png" data-asset-code="<?= e($selectedAsset['ma_tai_san']) ?>">
                        Tải PNG
                    </button>
                    <button type="button" class="btn btn-sketch-dark btn-sm"
                            data-action="print-single-qr" data-asset-id="<?= (int)$selectedAsset['id'] ?>">
                        In tem mã QR
                    </button>
                </div>
            </div>

            <div class="modal-footer py-2">
                <a href="<?= e($link(['id' => $selectedAsset['id']])) ?>#asset-detail" class="btn btn-sketch btn-sm">
                    Xem hồ sơ chi tiết
                </a>
                <a href="<?= e($closeUrl) ?>" class="btn btn-sketch-primary btn-sm" data-bs-dismiss="modal">
                    Đóng
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const qrModalEl = document.getElementById('staffAssetQrModal');
    if (qrModalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(qrModalEl);
        modal.show();
        qrModalEl.addEventListener('hidden.bs.modal', () => {
            window.location.href = '<?= e($closeUrl) ?>';
        }, { once: true });
    }
});
</script>

