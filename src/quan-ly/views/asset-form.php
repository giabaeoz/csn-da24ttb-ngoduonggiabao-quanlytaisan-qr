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
$id = isset($id) ? (int)$id : 0;
?>
<div class="modal fade" id="assetModal" tabindex="-1" aria-labelledby="assetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assetModalLabel"><?= $id ? 'Sửa' : 'Thêm' ?> tài sản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng" onclick="window.location.href='tai-san.php'"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="asset-form" action="tai-san.php<?= $id ? '?id=' . $id : '' ?>" class="manager-form">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id" value="<?= $id ?? '' ?>">
                    <input type="hidden" name="action" value="<?= $id ? 'edit' : 'create' ?>">
                    <label>Mã tài sản
                        <input name="ma_tai_san" required maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" value="<?= e(is_string($formData['ma_tai_san'] ?? null) ? $formData['ma_tai_san'] : '') ?>" <?= $id ? 'readonly' : '' ?> placeholder="Ví dụ MIC-003">
                        <?php if ($id): ?><small>Mã và nội dung QR được giữ cố định để nhãn đã in vẫn dùng được.</small><?php endif; ?>
                    </label>
                    <div id="asset-qr-preview" class="wide" data-existing-qr="<?= e($id ? ($asset['ma_qr'] ?? '') : '') ?>">
                        <strong>QR của tài sản</strong>
                        <small data-qr-text>Nhập mã tài sản để xem QR sẽ được tạo.</small>
                        <div data-qr-live role="img" aria-label="Xem trước mã QR"></div>
                        <small>Hệ thống tạo QR tự động khi lưu; nhãn có thể in từ danh sách tài sản.</small>
                    </div>
                    <label>Tên tài sản <input name="ten_tai_san" required maxlength="150" value="<?= e(is_string($formData['ten_tai_san'] ?? null) ? $formData['ten_tai_san'] : '') ?>"></label>
                    <label>Loại tài sản <select name="loai_tai_san_id" required><option value="">Chọn loại</option>
                        <?php foreach ($types as $type): ?><option value="<?= (int)$type['id'] ?>" <?= (string)($formData['loai_tai_san_id'] ?? '') === (string)$type['id'] ? 'selected' : '' ?>><?= e($type['ten_loai']) ?><?= $type['dang_hoat_dong'] ? '' : ' (ngừng hoạt động)' ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Phòng trực phụ trách <select name="phong_truc_id" required><option value="">Chọn phòng trực</option>
                        <?php foreach ($desks as $desk): ?><option value="<?= (int)$desk['id'] ?>" <?= (string)($formData['phong_truc_id'] ?? '') === (string)$desk['id'] ? 'selected' : '' ?>><?= e($desk['ten_phong_truc']) ?><?= $desk['dang_hoat_dong'] ? '' : ' (ngừng hoạt động)' ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Phạm vi tương thích <select name="pham_vi_tuong_thich" required>
                        <option value="THEO_PHONG" <?= ($formData['pham_vi_tuong_thich'] ?? 'THEO_PHONG') === 'THEO_PHONG' ? 'selected' : '' ?>>Theo phòng</option>
                        <option value="LINH_HOAT" <?= ($formData['pham_vi_tuong_thich'] ?? '') === 'LINH_HOAT' ? 'selected' : '' ?>>Linh hoạt, không giới hạn phòng cố định</option>
                    </select></label>
                    <label>Phòng hiện tại <select name="phong_hoc_hien_tai_id"><option value="">Chưa xác định</option>
                        <?php foreach ($rooms as $room): ?><option value="<?= (int)$room['id'] ?>" <?= (string)($formData['phong_hoc_hien_tai_id'] ?? '') === (string)$room['id'] ? 'selected' : '' ?>><?= e($room['ma_phong']) ?><?= $room['dang_hoat_dong'] ? '' : ' (ngừng hoạt động)' ?></option><?php endforeach; ?>
                    </select></label>
                    <fieldset id="compatible-rooms" class="wide"><legend>Phòng tương thích</legend><p class="manager-muted">Chọn một hoặc nhiều phòng nếu tài sản theo phòng. Tài sản linh hoạt không chọn phòng cố định.</p>
                        <div class="room-options">
                            <?php foreach ($rooms as $room): ?><label><input type="checkbox" name="rooms[]" value="<?= (int)$room['id'] ?>" <?= in_array((string)$room['id'], array_map('strval', is_array($formData['rooms'] ?? null) ? $formData['rooms'] : []), true) ? 'checked' : '' ?>><?= e($room['ma_phong']) ?></label><?php endforeach; ?>
                        </div>
                    </fieldset>
                    <label>Tình trạng <select name="tinh_trang">
                        <?php foreach (ASSET_CONDITIONS as $value => $label): ?><option value="<?= $value ?>" <?= ($formData['tinh_trang'] ?? 'TOT') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Trạng thái <select name="trang_thai">
                        <?php foreach (ASSET_STATES as $value => $label): if ($value === 'DANG_MUON' && ($formData['trang_thai'] ?? '') !== 'DANG_MUON') continue; ?><option value="<?= $value ?>" <?= ($formData['trang_thai'] ?? 'SAN_SANG') === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
                    </select></label>
                    <label>Ngày đưa vào sử dụng <input name="ngay_dua_vao_su_dung" type="date" value="<?= e(is_string($formData['ngay_dua_vao_su_dung'] ?? null) ? $formData['ngay_dua_vao_su_dung'] : '') ?>"></label>
                    <label class="wide">Mô tả <textarea name="mo_ta" maxlength="5000"><?= e(is_string($formData['mo_ta'] ?? null) ? $formData['mo_ta'] : '') ?></textarea></label>
                </form>

                <?php if ($id): ?>
                    <hr class="my-3">
                    <div class="manager-actions">
                        <?php if ($asset['trang_thai_hien_thi'] !== 'NGUNG_SU_DUNG'): ?>
                            <form method="post" action="tai-san.php?id=<?= $id ?>" onsubmit="return confirm('Ngừng sử dụng tài sản này? Lịch sử và mã QR vẫn được giữ lại.')">
                                <?= csrf_input() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="stop"><button type="submit">Ngừng sử dụng</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" action="tai-san.php?id=<?= $id ?>" onsubmit="return confirm('Xóa vĩnh viễn tài sản chưa có lịch sử nghiệp vụ?')">
                            <?= csrf_input() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="action" value="delete"><button type="submit">Xóa tài sản</button>
                        </form>
                    </div>
                    <p class="manager-muted mt-2 mb-0">Tài sản đã có mượn–trả, kiểm kê, điều chuyển hoặc sự cố chỉ có thể ngừng sử dụng.</p>
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
});
</script>
