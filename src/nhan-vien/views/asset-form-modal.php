<?php
declare(strict_types=1);

/**
 * @var bool $isNew
 * @var bool $isEdit
 * @var array<string, mixed> $formData
 * @var array<int, array<string, mixed>> $types
 * @var array<int, array<string, mixed>> $rooms
 * @var array<string, mixed> $desk
 * @var array<string, mixed>|null $assetToEdit
 * @var string $error
 * @var callable $link
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/assets.php';

$editId = $isEdit && $assetToEdit ? (int)$assetToEdit['id'] : 0;
$closeUrl = $link(['new' => null, 'edit' => null, 'id' => null]);
$isBorrowed = $assetToEdit && ($assetToEdit['trang_thai_hien_thi'] ?? '') === 'DANG_MUON';
$selectedRooms = is_array($formData['rooms'] ?? null)
    ? array_map('strval', $formData['rooms'])
    : ($assetToEdit && !empty($assetToEdit['phong_tuong_thich'])
        ? (array)json_decode((string)$assetToEdit['phong_tuong_thich'], true)
        : []);
?>
<div class="modal fade" id="staffAssetFormModal" tabindex="-1" aria-labelledby="staffAssetFormTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="badge bg-dark mb-1">Phòng trực <?= e($desk['ten_phong_truc']) ?></span>
                    <h5 class="modal-title fw-bold" id="staffAssetFormTitle">
                        <?= $isNew ? 'Thêm tài sản mới vào phòng trực' : 'Cập nhật tài sản: ' . e($assetToEdit['ma_tai_san'] ?? '') ?>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"
                        onclick="window.location.href='<?= e($closeUrl) ?>'"></button>
            </div>

            <div class="modal-body p-4">
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger mb-3" role="alert"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($isBorrowed): ?>
                    <div class="alert alert-warning mb-3">
                        <strong>Lưu ý:</strong> Tài sản đang có lượt mượn chưa trả. Chỉ có thể cập nhật Tên tài sản và Mô tả/Ghi chú.
                    </div>
                <?php endif; ?>

                <form method="post" id="staffAssetForm" action="tai-san.php<?= $editId ? '?id=' . $editId . '&edit=1' : '?new=1' ?>">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="<?= $isNew ? 'create' : 'edit' ?>">
                    <input type="hidden" name="id" value="<?= $editId ?>">

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="form_ma_tai_san" class="form-label fw-semibold">
                                Mã tài sản <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="form_ma_tai_san" name="ma_tai_san" class="form-control"
                                   maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" required
                                   value="<?= e($formData['ma_tai_san'] ?? '') ?>"
                                   <?= $isEdit ? 'readonly' : 'placeholder="Ví dụ: MIC-005, RML-004"' ?>>
                            <small class="text-muted d-block mt-1">
                                <?= $isEdit ? 'Mã tài sản và mã QR cố định sau khi tạo để nhãn đã in luôn khớp.' : 'Mã chỉ gồm chữ cái, số, dấu gạch ngang, gạch dưới hoặc chấm.' ?>
                            </small>
                        </div>
                        <div class="col-md-4 text-center">
                            <label class="form-label fw-semibold d-block">Xem trước mã QR</label>
                            <div class="live-qr-preview" id="live-qr-preview-box">
                                <span class="text-muted small">Xem trước QR</span>
                            </div>
                            <small class="text-muted d-block mt-1" id="live-qr-preview-text">
                                <?= !empty($formData['ma_tai_san']) ? 'TVU:' . e($formData['ma_tai_san']) : 'TVU:...' ?>
                            </small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="form_ten_tai_san" class="form-label fw-semibold">
                            Tên tài sản / Thiết bị <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="form_ten_tai_san" name="ten_tai_san" class="form-control"
                               maxlength="150" required value="<?= e($formData['ten_tai_san'] ?? '') ?>"
                               placeholder="Ví dụ: Microphone không dây Shure SM58">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="form_loai_tai_san_id" class="form-label fw-semibold mb-0">
                                    Loại tài sản <span class="text-danger">*</span>
                                </label>
                                <?php if (!$isBorrowed): ?>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="btn-toggle-new-type" style="font-size: 12px;">+ Thêm phân loại</button>
                                <?php endif; ?>
                            </div>
                            <select id="form_loai_tai_san_id" name="loai_tai_san_id" class="form-select" required <?= $isBorrowed ? 'disabled' : '' ?>>
                                <option value="">-- Chọn loại tài sản --</option>
                                <option value="__NEW__">+ Thêm phân loại mới...</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= (int)$type['id'] ?>" <?= (string)($formData['loai_tai_san_id'] ?? '') === (string)$type['id'] ? 'selected' : '' ?>>
                                        <?= e($type['ten_loai']) ?><?= $type['dang_hoat_dong'] ? '' : ' (ngừng hoạt động)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($isBorrowed): ?>
                                <input type="hidden" name="loai_tai_san_id" value="<?= (int)($formData['loai_tai_san_id'] ?? 0) ?>">
                            <?php endif; ?>

                            <div id="new-type-fields" class="mt-2 p-2 border rounded bg-light" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-bold text-dark">Thêm phân loại mới:</span>
                                    <button type="button" class="btn-close btn-sm" id="btn-cancel-new-type" style="font-size: 9px;" aria-label="Hủy"></button>
                                </div>
                                <div class="mb-2">
                                    <input type="text" id="form_ten_loai_moi" name="ten_loai_moi" class="form-control form-control-sm" placeholder="Tên phân loại (ví dụ: Loa Bluetooth)">
                                </div>
                                <div>
                                    <input type="text" id="form_ma_loai_moi" name="ma_loai_moi" class="form-control form-control-sm" placeholder="Mã phân loại (tùy chọn, tự sinh nếu để trống)">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phòng trực phụ trách</label>
                            <input type="text" class="form-control bg-light" readonly
                                   value="<?= e($desk['ten_phong_truc']) ?> (<?= e($desk['ma_phong_truc']) ?>)">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="form_phong_hoc_hien_tai_id" class="form-label fw-semibold">
                                Thuộc phòng học / Vị trí phòng
                            </label>
                            <select id="form_phong_hoc_hien_tai_id" name="phong_hoc_hien_tai_id" class="form-select" <?= $isBorrowed ? 'disabled' : '' ?>>
                                <option value="">Tại phòng trực (thiết bị lưu động, cấp phát)</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= (int)$room['id'] ?>" <?= (string)($formData['phong_hoc_hien_tai_id'] ?? ($formData['phong_hoc_phu_hop_id'] ?? '')) === (string)$room['id'] ? 'selected' : '' ?>>
                                        <?= e($room['ma_phong']) ?><?= !empty($room['ten_phong']) ? (' - ' . e($room['ten_phong'])) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="pham_vi_tuong_thich" value="LINH_HOAT">
                            <?php if ($isBorrowed): ?>
                                <input type="hidden" name="phong_hoc_hien_tai_id" value="<?= (int)($formData['phong_hoc_hien_tai_id'] ?? 0) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="form_ngay_dua_vao_su_dung" class="form-label fw-semibold">Ngày đưa vào sử dụng</label>
                            <input type="date" id="form_ngay_dua_vao_su_dung" name="ngay_dua_vao_su_dung"
                                   class="form-control" value="<?= e($formData['ngay_dua_vao_su_dung'] ?? '') ?>"
                                   <?= $isBorrowed ? 'readonly' : '' ?>>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="form_tinh_trang" class="form-label fw-semibold">Tình trạng vật lý</label>
                            <select id="form_tinh_trang" name="tinh_trang" class="form-select" <?= $isBorrowed ? 'disabled' : '' ?>>
                                <?php foreach (ASSET_CONDITIONS as $cKey => $cLabel): ?>
                                    <option value="<?= e($cKey) ?>" <?= ($formData['tinh_trang'] ?? 'TOT') === $cKey ? 'selected' : '' ?>>
                                        <?= e($cLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($isBorrowed): ?>
                                <input type="hidden" name="tinh_trang" value="<?= e($formData['tinh_trang'] ?? 'TOT') ?>">
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="form_trang_thai" class="form-label fw-semibold">Trạng thái vận hành</label>
                            <select id="form_trang_thai" name="trang_thai" class="form-select" <?= $isBorrowed ? 'disabled' : '' ?>>
                                <?php foreach (ASSET_STATES as $sKey => $sLabel): ?>
                                    <?php if ($sKey === 'DANG_MUON' && !$isBorrowed) continue; ?>
                                    <option value="<?= e($sKey) ?>" <?= ($formData['trang_thai'] ?? 'SAN_SANG') === $sKey ? 'selected' : '' ?>>
                                        <?= e($sLabel) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($isBorrowed): ?>
                                <input type="hidden" name="trang_thai" value="<?= e($formData['trang_thai'] ?? 'DANG_MUON') ?>">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="form_mo_ta" class="form-label fw-semibold">Mô tả chi tiết / Ghi chú</label>
                        <textarea id="form_mo_ta" name="mo_ta" class="form-control" rows="3" maxlength="5000"
                                  placeholder="Nhập thông tin phụ kiện kèm theo, thông số kỹ thuật hoặc ghi chú..."><?= e($formData['mo_ta'] ?? '') ?></textarea>
                    </div>
                </form>

                <?php if ($isEdit && !$isBorrowed && ($assetToEdit['trang_thai_hien_thi'] ?? '') !== 'NGUNG_SU_DUNG'): ?>
                    <hr class="my-4">
                    <div class="d-flex justify-content-between align-items-center bg-light p-3 rounded">
                        <div>
                            <strong class="d-block text-danger">Ngừng sử dụng thiết bị này?</strong>
                            <small class="text-muted">Lịch sử mượn trả và mã QR vẫn được lưu giữ an toàn.</small>
                        </div>
                        <form method="post" action="tai-san.php?id=<?= $editId ?>"
                              onsubmit="return confirm('Bạn có chắc chắn muốn ngừng sử dụng tài sản này?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="action" value="stop">
                            <input type="hidden" name="id" value="<?= $editId ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                Ngừng sử dụng
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div class="modal-footer">
                <a href="<?= e($closeUrl) ?>" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    Hủy bỏ
                </a>
                <button type="submit" form="staffAssetForm" class="btn btn-dark">
                    <?= $isNew ? 'Thêm tài sản mới' : 'Lưu thay đổi' ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('staffAssetFormModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
        modalEl.addEventListener('hidden.bs.modal', () => {
            window.location.href = '<?= e($closeUrl) ?>';
        }, { once: true });
    }

    const sel = document.getElementById('form_loai_tai_san_id');
    const box = document.getElementById('new-type-fields');
    const btnToggle = document.getElementById('btn-toggle-new-type');
    const btnCancel = document.getElementById('btn-cancel-new-type');
    const inputName = document.getElementById('form_ten_loai_moi');
    const inputCode = document.getElementById('form_ma_loai_moi');

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

