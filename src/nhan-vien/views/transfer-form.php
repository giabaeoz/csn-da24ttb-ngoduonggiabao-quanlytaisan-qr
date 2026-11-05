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
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <section class="panel h-100" data-qr-scanner>
                <h2>Quét QR thiết bị điều chuyển</h2>
                <div class="row g-2">
                    <div class="col-sm-5"><div class="camera-box" data-qr-preview>Đưa mã QR thiết bị vào khung</div></div>
                    <div class="col-sm-7">
                        <button class="btn btn-outline-secondary w-100" type="button" data-qr-start>Bật camera</button>
                        <button class="btn btn-outline-secondary w-100" type="button" data-qr-stop hidden>Tắt camera</button>
                        <label for="transfer-image" class="form-label section-label mt-2">Chọn ảnh QR</label>
                        <input id="transfer-image" type="file" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm" data-qr-file>
                        <label for="transfer-code" class="form-label section-label mt-2">Nhập mã khi không quét được</label>
                        <div class="input-group"><input id="transfer-code" class="form-control" maxlength="100" placeholder="Mã tài sản hoặc TVU:..."><button id="transfer-add" class="btn btn-outline-secondary" type="button">Thêm</button></div>
                    </div>
                </div>
                <small class="muted d-block mt-2" data-qr-status role="status">Chọn phòng đi và phòng đến, sau đó quét thiết bị.</small>
            </section>
        </div>
        <div class="col-lg-7">
            <section class="panel h-100">
                <h2>Thông tin điều chuyển</h2>
                <p class="muted">Phòng trực <strong><?= e($desk['ten_phong_truc']) ?></strong> quản lý tài sản; phiếu chỉ thay đổi vị trí giữa hai phòng học.</p>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="transfer-origin" class="form-label">Phòng đi</label>
                        <select id="transfer-origin" name="phong_hoc_di_id" class="form-select" required>
                            <option value="">Chọn phòng học nguồn</option>
                            <?php foreach ($rooms as $room): ?><option value="<?= (int)$room['id'] ?>" data-code="<?= e($room['ma_phong']) ?>" <?= $oldOrigin === (string)$room['id'] ? 'selected' : '' ?>><?= e($room['ma_phong'] . ' — ' . $room['ten_phong']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="transfer-destination" class="form-label">Phòng đến</label>
                        <select id="transfer-destination" name="phong_hoc_den_id" class="form-select" required>
                            <option value="">Chọn phòng học đích</option>
                            <?php foreach ($rooms as $room): ?><option value="<?= (int)$room['id'] ?>" data-code="<?= e($room['ma_phong']) ?>" <?= $oldDestination === (string)$room['id'] ? 'selected' : '' ?>><?= e($room['ma_phong'] . ' — ' . $room['ten_phong']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="transfer-reason" class="form-label">Lý do điều chuyển</label>
                        <textarea id="transfer-reason" name="ly_do" class="form-control" rows="3" maxlength="2000" required placeholder="Nêu lý do cần chuyển thiết bị giữa hai phòng học"><?= e($oldReason) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label for="transfer-note" class="form-label">Ghi chú (nếu có)</label>
                        <textarea id="transfer-note" name="ghi_chu" class="form-control" rows="2" maxlength="2000"><?= e($oldNote) ?></textarea>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <section class="panel h-100 transfer-preview">
                <h2>Phiếu điều chuyển</h2>
                <p>Phòng đi: <strong data-transfer-origin-label>—</strong> → Phòng đến: <strong data-transfer-destination-label>—</strong></p>
                <p>Lý do: <span data-transfer-reason-label>Chưa nhập</span></p>
                <p>Thời gian: tự ghi nhận khi xác nhận</p>
                <hr>
                <div data-transfer-preview-assets class="muted">Chưa quét thiết bị.</div>
                <hr>
                <p class="mb-0">Tổng cộng: <strong data-transfer-count>0</strong> thiết bị</p>
            </section>
        </div>
        <div class="col-lg-7">
            <section class="panel h-100">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2"><h2 class="mb-0">Thiết bị điều chuyển — đã quét (<span data-transfer-count>0</span>)</h2><small class="muted">Tối đa 30 thiết bị</small></div>
                <div class="transfer-assets" id="transfer-assets">
                    <?php foreach ($assets as $asset): ?>
                        <label class="transfer-asset" data-code="<?= e(mb_strtolower($asset['ma_tai_san'], 'UTF-8')) ?>"
                            data-qr="<?= e(mb_strtolower($asset['ma_qr'], 'UTF-8')) ?>"
                            data-label="<?= e($asset['ma_tai_san'] . ' — ' . $asset['ten_tai_san']) ?>"
                            data-origin="<?= (int)$asset['phong_hoc_hien_tai_id'] ?>"
                            data-origin-code="<?= e($asset['phong_hien_tai']) ?>"
                            data-compat="<?= e($asset['compatibility_tokens']) ?>" hidden>
                            <input type="checkbox" name="tai_san_id[]" value="<?= (int)$asset['id'] ?>" <?= in_array((string)$asset['id'], $oldAssets, true) ? 'checked' : '' ?>>
                            <span><strong><?= e($asset['ma_tai_san']) ?></strong> — <?= e($asset['ten_tai_san']) ?><small class="muted d-block">Phòng hiện tại: <?= e($asset['phong_hien_tai']) ?> · Tình trạng: Tốt</small></span>
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-transfer-remove>Bỏ</button>
                        </label>
                    <?php endforeach; ?>
                    <?php if (!$assets): ?><p class="muted">Không có tài sản tốt, sẵn sàng và đang đặt tại phòng học để điều chuyển.</p><?php endif; ?>
                </div>
                <div class="form-check mt-3">
                    <input id="transfer-compatible" class="form-check-input" type="checkbox" name="xac_nhan_tuong_thich" value="1" required>
                    <label class="form-check-label" for="transfer-compatible">Đã kiểm tra thiết bị phù hợp với phòng đến</label>
                </div>
            </section>
        </div>
    </div>
    <div class="d-flex justify-content-end gap-3 mb-4">
        <a class="btn btn-outline-secondary transfer-cancel" href="dieu-chuyen.php">Hủy</a>
        <button class="btn btn-dark transfer-submit" type="submit">Xác nhận điều chuyển</button>
    </div>
</form>
