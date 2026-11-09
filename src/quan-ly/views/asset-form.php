<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $formData
 * @var array<int, array<string, mixed>> $types
 * @var array<int, array<string, mixed>> $desks
 * @var array<int, array<string, mixed>> $rooms
 * @var int|null $id
 * @var array<string, mixed>|null $asset
 * @var bool|null $canEditTypes
 * @var bool|null $canEditRooms
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/assets.php';

if (!isset($formData, $types, $desks, $rooms)) {
    http_response_code(404);
    exit;
}
$assetId = isset($id) ? (int)$id : 0;
?>
<div class="modal fade" id="assetModal" tabindex="-1" aria-labelledby="assetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assetModalLabel"><?= $assetId ? 'Sửa' : 'Thêm' ?> tài sản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng" onclick="window.location.href='tai-san.php'"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="asset-form" action="tai-san.php<?= $assetId ? '?id=' . $assetId : '' ?>" class="manager-form">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id" value="<?= $assetId ?: '' ?>">
                    <input type="hidden" name="action" value="<?= $assetId ? 'edit' : 'create' ?>">

                    <label>Mã tài sản
                        <input name="ma_tai_san" required maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9._-]*"
                               value="<?= e(is_string($formData['ma_tai_san'] ?? null) ? $formData['ma_tai_san'] : '') ?>"
                               <?= $assetId ? 'readonly' : '' ?> placeholder="Ví dụ MIC-003">
                        <?php if ($assetId): ?>
                            <small>Mã và nội dung QR được giữ cố định để nhãn đã in vẫn dùng được.</small>
                        <?php endif; ?>
                    </label>

                    <div id="asset-qr-preview" class="wide" data-existing-qr="<?= e($assetId ? ($asset['ma_qr'] ?? '') : '') ?>">
                        <strong>QR của tài sản</strong>
                        <small data-qr-text>Nhập mã tài sản để xem QR sẽ được tạo.</small>
                        <div data-qr-live role="img" aria-label="Xem trước mã QR"></div>
                        <small>Hệ thống tạo QR tự động khi lưu; nhãn có thể in từ danh sách tài sản.</small>
                    </div>

                    <label>Tên tài sản
                        <input name="ten_tai_san" required maxlength="150"
                               value="<?= e(is_string($formData['ten_tai_san'] ?? null) ? $formData['ten_tai_san'] : '') ?>">
                    </label>

                    <div>
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <label for="form_loai_tai_san_id" style="margin:0; font-weight:600;">Loại tài sản</label>
                            <button type="button" id="btn-toggle-new-type" style="background:none; border:none; color:#0d6efd; font-size:12px; cursor:pointer; padding:0; text-decoration:underline;">+ Thêm phân loại</button>
                        </div>
                        <select name="loai_tai_san_id" id="form_loai_tai_san_id" required>
                            <option value="">Chọn loại</option>
                            <option value="__NEW__">+ Thêm phân loại mới...</option>
                            <?php foreach ($types as $type): ?>
                                <option value="<?= (int)$type['id'] ?>" <?= (string)($formData['loai_tai_san_id'] ?? '') === (string)$type['id'] ? 'selected' : '' ?>>
                                    <?= e($type['ten_loai']) ?><?= $type['dang_hoat_dong'] ? '' : ' (ngừng hoạt động)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="new-type-fields" style="display:none; margin-top:8px; padding:10px; background:#f8f9fa; border:1px solid #ddd; border-radius:4px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                                <strong style="font-size:12px;">Thêm phân loại mới:</strong>
                                <button type="button" id="btn-cancel-new-type" style="background:none; border:none; cursor:pointer; font-size:12px;" title="Hủy">✕</button>
                            </div>
                            <input type="text" name="ten_loai_moi" id="form_ten_loai_moi" placeholder="Tên phân loại (ví dụ: Loa Bluetooth)" style="width:100%; margin-bottom:6px; padding:6px; font-size:13px; border:1px solid #ccc; border-radius:3px;">
                            <input type="text" name="ma_loai_moi" id="form_ma_loai_moi" placeholder="Mã phân loại (tùy chọn, tự sinh nếu để trống)" style="width:100%; padding:6px; font-size:13px; border:1px solid #ccc; border-radius:3px;">
                        </div>
                    </div>

                    <label>Phòng trực phụ trách
                        <select name="phong_truc_id" required>
                            <option value="">Chọn phòng trực</option>
                            <?php foreach ($desks as $desk): ?>
                                <option value="<?= (int)$desk['id'] ?>" <?= (string)($formData['phong_truc_id'] ?? '') === (string)$desk['id'] ? 'selected' : '' ?>>
                                    <?= e($desk['ten_phong_truc']) ?><?= $desk['dang_hoat_dong'] ? '' : ' (ngừng hoạt động)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>Thuộc phòng học / Vị trí phòng
                        <select name="phong_hoc_hien_tai_id">
                            <option value="">Tại phòng trực (thiết bị lưu động, cấp phát)</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= (int)$room['id'] ?>" <?= (string)($formData['phong_hoc_hien_tai_id'] ?? ($formData['phong_hoc_phu_hop_id'] ?? '')) === (string)$room['id'] ? 'selected' : '' ?>>
                                    <?= e($room['ma_phong']) ?><?= !empty($room['ten_phong']) ? (' - ' . e($room['ten_phong'])) : '' ?><?= !empty($room['dang_hoat_dong']) ? '' : ' (ngừng hoạt động)' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <input type="hidden" name="pham_vi_tuong_thich" value="LINH_HOAT">

                    <label>Tình trạng
                        <select name="tinh_trang">
                            <?php foreach (ASSET_CONDITIONS as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($formData['tinh_trang'] ?? 'TOT') === $value ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>Trạng thái
                        <select name="trang_thai">
                            <?php foreach (ASSET_STATES as $value => $label): ?>
                                <?php if ($value === 'DANG_MUON' && ($formData['trang_thai'] ?? '') !== 'DANG_MUON') continue; ?>
                                <option value="<?= $value ?>" <?= ($formData['trang_thai'] ?? 'SAN_SANG') === $value ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>Ngày đưa vào sử dụng
                        <input name="ngay_dua_vao_su_dung" type="date"
                               value="<?= e(is_string($formData['ngay_dua_vao_su_dung'] ?? null) ? $formData['ngay_dua_vao_su_dung'] : '') ?>">
                    </label>

                    <label class="wide">Mô tả
                        <textarea name="mo_ta" maxlength="5000"><?= e(is_string($formData['mo_ta'] ?? null) ? $formData['mo_ta'] : '') ?></textarea>
                    </label>
                </form>

                <?php if ($assetId): ?>
                    <hr class="my-3">
                    <div class="manager-actions">
                        <?php if ($asset['trang_thai_hien_thi'] !== 'NGUNG_SU_DUNG'): ?>
                            <form method="post" action="tai-san.php?id=<?= $assetId ?>" onsubmit="return confirm('Ngừng sử dụng tài sản này? Lịch sử và mã QR vẫn được giữ lại.')">
                                <?= csrf_input() ?>
                                <input type="hidden" name="id" value="<?= $assetId ?>">
                                <input type="hidden" name="action" value="stop">
                                <button type="submit">Ngừng sử dụng</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="tai-san.php?id=<?= $assetId ?>" onsubmit="return confirm('Xóa vĩnh viễn tài sản chưa có lịch sử nghiệp vụ?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= $assetId ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit">Xóa tài sản</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <a class="button" href="tai-san.php" data-bs-dismiss="modal">Hủy</a>
                <button type="submit" form="asset-form" class="primary">Lưu tài sản</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('assetModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        var myModal = new bootstrap.Modal(modalEl);
        myModal.show();
        modalEl.addEventListener('hidden.bs.modal', function () {
            if (window.location.search.includes('new=1') || window.location.search.includes('edit=1')) {
                window.location.href = 'tai-san.php';
            }
        });
    }

    var sel = document.getElementById('form_loai_tai_san_id');
    var box = document.getElementById('new-type-fields');
    var btnToggle = document.getElementById('btn-toggle-new-type');
    var btnCancel = document.getElementById('btn-cancel-new-type');
    var inputName = document.getElementById('form_ten_loai_moi');
    var inputCode = document.getElementById('form_ma_loai_moi');

    function showNewType() {
        if (!box) return;
        box.style.display = 'block';
        if (sel) sel.value = '__NEW__';
        if (inputName) inputName.focus();
    }
    function hideNewType() {
        if (!box) return;
        box.style.display = 'none';
        if (inputName) inputName.value = '';
        if (inputCode) inputCode.value = '';
        if (sel && sel.value === '__NEW__') sel.value = '';
    }

    if (btnToggle) btnToggle.addEventListener('click', showNewType);
    if (btnCancel) btnCancel.addEventListener('click', hideNewType);
    if (sel) {
        sel.addEventListener('change', function() {
            if (this.value === '__NEW__') {
                showNewType();
            } else {
                hideNewType();
            }
        });
    }
});
</script>
