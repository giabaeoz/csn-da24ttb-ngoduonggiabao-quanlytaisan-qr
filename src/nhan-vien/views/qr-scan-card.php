<?php
declare(strict_types=1);

/**
 * Khu vực quét QR dùng chung cho trang mượn và trả.
 * @var string $scanTitle
 * @var string $scanHint
 * @var string $scanPlaceholder
 * @var string $scanValue
 */
if (!isset($scanTitle)) {
    http_response_code(404);
    exit;
}
$scanHint = $scanHint ?? 'Đưa mã QR thiết bị vào khung';
$scanPlaceholder = $scanPlaceholder ?? 'Mã tài sản hoặc QR';
$scanValue = $scanValue ?? '';
?>
<div class="sketch-card" data-qr-scanner>
    <h2><?= e($scanTitle) ?></h2>
    <div class="camera-grid">
        <div class="camera-box" data-qr-preview>
            <span class="camera-box-corner corner-tl"></span>
            <span class="camera-box-corner corner-tr"></span>
            <span class="camera-box-corner corner-bl"></span>
            <span class="camera-box-corner corner-br"></span>
            <span class="cam-title">Khung camera</span>
            <span><?= e($scanHint) ?></span>
        </div>
        <div class="camera-side-controls">
            <div>
                <label class="btn-sketch btn-sketch-gray w-100 mb-2" for="scan-image">Chọn ảnh QR</label>
                <input type="file" accept="image/*" capture="environment" id="scan-image" data-qr-file hidden>
                <label class="lbl-sub" for="scan-code">Nhập mã khi không quét được</label>
                <div class="d-flex gap-1">
                    <input class="form-control" id="scan-code" placeholder="<?= e($scanPlaceholder) ?>" value="<?= e($scanValue) ?>" autocomplete="off" list="scan-code-datalist">
                    <button class="btn-sketch" type="button" id="scan-add"><?= e($scanButton ?? 'Thêm') ?></button>
                </div>
                <?php if (!empty($availableAssets)): ?>
                    <datalist id="scan-code-datalist">
                        <?php foreach ($availableAssets as $av): ?>
                            <option value="<?= e($av['ma_tai_san']) ?>"><?= e($av['ten_tai_san'] . ' (' . $av['ten_loai'] . ')') ?></option>
                        <?php endforeach; ?>
                    </datalist>
                <?php endif; ?>
            </div>
            <div>
                <button class="btn-sketch btn-sketch-gray w-100" type="button" data-qr-start>Bật camera</button>
                <button class="btn-sketch w-100" type="button" data-qr-stop hidden>Tắt camera</button>
            </div>
        </div>
    </div>
    <small class="scan-status" data-qr-status role="status" aria-live="polite"></small>
</div>
