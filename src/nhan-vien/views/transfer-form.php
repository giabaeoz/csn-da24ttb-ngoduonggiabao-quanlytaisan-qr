<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $rooms
 * @var array<int, array<string, mixed>> $assets
 * @var array<int, string> $oldAssets
 * @var array<string, mixed> $desk
 * @var string $oldOrigin
 * @var string $oldDestination
 * @var string $oldReason
 * @var string $oldNote
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($rooms, $assets, $oldAssets, $desk)) {
    http_response_code(404);
    exit;
}

$oldOrigin = isset($oldOrigin) ? (string)$oldOrigin : '';
$oldDestination = isset($oldDestination) ? (string)$oldDestination : '';
$oldReason = isset($oldReason) ? (string)$oldReason : '';
$oldNote = isset($oldNote) ? (string)$oldNote : '';
?>
<form method="post" id="transfer-form">
    <?= csrf_input() ?>
    <input type="hidden" name="action" value="create">

    <div class="transfer-layout">
        <div class="transfer-col-left">
            <div class="sketch-card" data-qr-scanner>
                <h2>Quét QR thiết bị điều chuyển</h2>
                <div class="camera-grid">
                    <div class="camera-box" data-qr-preview>
                        <span class="camera-box-corner corner-tl"></span>
                        <span class="camera-box-corner corner-tr"></span>
                        <span class="camera-box-corner corner-bl"></span>
                        <span class="camera-box-corner corner-br"></span>
                        <span class="cam-title">Khung camera</span>
                        <span>Đưa mã QR thiết bị vào khung</span>
                    </div>
                    <div class="camera-side-controls">
                        <div>
                            <button class="btn-sketch w-100 mb-2" type="button" style="background:#e4e4e4;">Chọn camera</button>
                            <span class="lbl-sub">Nhập mã khi không quét được</span>
                            <div class="d-flex gap-1 mb-2">
                                <input id="transfer-code" class="form-control" maxlength="100" placeholder="Mã tài sản hoặc QR">
                                <button id="transfer-add" class="btn-sketch" type="button">Thêm</button>
                            </div>
                            <input id="transfer-image" type="file" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm" data-qr-file style="display:none;">
                        </div>
                        <div>
                            <button class="btn-sketch w-100" type="button" data-qr-start style="background:#e4e4e4;">Bật camera</button>
                            <button class="btn-sketch w-100" type="button" data-qr-stop hidden>Tắt camera</button>
                        </div>
                    </div>
                </div>
                <small class="text-muted d-block mt-2" data-qr-status role="status"></small>
            </div>

            <div class="sketch-card receipt-card">
                <h2>Phiếu điều chuyển</h2>
                <div class="receipt-meta">
                    <div>Phòng đi: <strong data-transfer-origin-label>C71.102</strong> → Phòng đến: <strong data-transfer-destination-label>C71.103</strong></div>
                    <div>Lý do: <span data-transfer-reason-label>thiết bị phòng đến gặp sự cố.</span></div>
                    <div>Thời gian: <?= date('d/m/Y H:i') ?> (minh họa)</div>
                </div>
                <div class="receipt-items-header">
                    <span>Thiết bị</span>
                    <span>Số lượng</span>
                </div>
                <div data-transfer-preview-assets class="receipt-items-rows">
                    <div class="receipt-item-row">
                        <span>TS006 — Ổ cắm điện 01</span>
                        <span>1</span>
                    </div>
                    <div class="receipt-item-row">
                        <span>TS007 — Cáp chuyển đổi 01</span>
                        <span>1</span>
                    </div>
                </div>
                <div class="receipt-total">Tổng cộng: <strong data-transfer-count>2</strong> thiết bị</div>
            </div>
        </div>

        <div class="transfer-col-right">
            <div class="sketch-card">
                <h2>Thông tin điều chuyển</h2>
                <div class="form-grid-2">
                    <label>Phòng đi
                        <select id="transfer-origin" name="phong_hoc_di_id" class="form-select" required>
                            <option value="">Chọn phòng học</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= (int)$room['id'] ?>"
                                        data-code="<?= e($room['ma_phong']) ?>"
                                        <?= $oldOrigin === (string)$room['id'] ? 'selected' : '' ?>>
                                    <?= e($room['ma_phong']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Phòng đến
                        <select id="transfer-destination" name="phong_hoc_den_id" class="form-select" required>
                            <option value="">Chọn phòng học</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= (int)$room['id'] ?>"
                                        data-code="<?= e($room['ma_phong']) ?>"
                                        <?= $oldDestination === (string)$room['id'] ? 'selected' : '' ?>>
                                    <?= e($room['ma_phong']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <div class="transfer-reason-wrap">
                    <label for="transfer-reason">Lý do điều chuyển</label>
                    <textarea id="transfer-reason" name="ly_do" class="form-control" rows="2" maxlength="2000" required placeholder="Nêu lý do điều chuyển"><?= e($oldReason ?: 'Thiết bị tại phòng đến gặp sự cố. Chuyển ổ cắm và cáp để tiếp tục buổi học.') ?></textarea>
                </div>

                <div class="transfer-time-hint">Thời gian: tự ghi nhận khi xác nhận</div>
            </div>

            <div class="sketch-card">
                <h2>Thiết bị điều chuyển — đã quét (<span data-transfer-count>2</span>)</h2>
                
                <table class="scanned-table">
                    <thead>
                        <tr>
                            <th>Mã / tên thiết bị</th>
                            <th>Tình trạng</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="transfer-assets">
                        <?php foreach ($assets as $asset): ?>
                            <tr class="transfer-asset-row"
                                data-code="<?= e(mb_strtolower($asset['ma_tai_san'], 'UTF-8')) ?>"
                                data-qr="<?= e(mb_strtolower($asset['ma_qr'], 'UTF-8')) ?>"
                                data-label="<?= e($asset['ma_tai_san'] . ' — ' . $asset['ten_tai_san']) ?>"
                                data-origin="<?= (int)$asset['phong_hoc_hien_tai_id'] ?>"
                                data-origin-code="<?= e($asset['phong_hien_tai']) ?>"
                                data-compat="<?= e($asset['compatibility_tokens']) ?>"
                                hidden>
                                <td>
                                    <input type="checkbox" name="tai_san_id[]" value="<?= (int)$asset['id'] ?>" <?= in_array((string)$asset['id'], $oldAssets, true) ? 'checked' : '' ?> style="display:none;">
                                    <strong><?= e($asset['ma_tai_san']) ?></strong> — <?= e($asset['ten_tai_san']) ?>
                                    <small class="text-muted d-block">Phòng hiện tại: <?= e($asset['phong_hien_tai']) ?></small>
                                </td>
                                <td>Bình thường</td>
                                <td style="text-align:right;">
                                    <button class="btn-remove" type="button" data-transfer-remove>Bỏ</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$assets): ?>
                            <tr>
                                <td>
                                    <strong>TS006</strong> — Ổ cắm điện 01
                                    <small class="text-muted d-block">Phòng hiện tại: C71.102</small>
                                </td>
                                <td>Bình thường</td>
                                <td style="text-align:right;"><button class="btn-remove" type="button">Bỏ</button></td>
                            </tr>
                            <tr>
                                <td>
                                    <strong>TS007</strong> — Cáp chuyển đổi 01
                                    <small class="text-muted d-block">Phòng hiện tại: C71.102</small>
                                </td>
                                <td>Bình thường</td>
                                <td style="text-align:right;"><button class="btn-remove" type="button">Bỏ</button></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="form-check mt-3">
                    <input id="transfer-compatible" class="form-check-input" type="checkbox" name="xac_nhan_tuong_thich" value="1" required checked>
                    <label class="form-check-label" for="transfer-compatible" style="font-size:13px;color:#333;">Đã kiểm tra thiết bị phù hợp với phòng đến</label>
                </div>
            </div>

            <div class="transfer-actions-bottom">
                <a class="btn-sketch-white" href="dieu-chuyen.php">Hủy</a>
                <button class="btn-sketch-confirm" type="submit">Xác nhận điều chuyển</button>
            </div>
        </div>
    </div>
</form>
