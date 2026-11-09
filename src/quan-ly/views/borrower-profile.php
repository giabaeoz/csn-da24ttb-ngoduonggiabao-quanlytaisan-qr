<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $profile
 * @var bool $new
 * @var bool $canEdit
 * @var int $id
 * @var string $tab
 * @var mysqli_result|null $history
 * @var callable $link
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/borrowers.php';

if (!isset($profile)) {
    http_response_code(404);
    exit;
}
$new = !empty($new);
$canEdit = !empty($canEdit);
$id = isset($id) ? (int)$id : 0;
$tab = isset($tab) ? (string)$tab : 'all';
$history = isset($history) ? $history : null;
?>
<div class="modal fade" id="borrowerModal" tabindex="-1" aria-labelledby="borrowerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="borrowerModalLabel"><?= $new ? 'Thêm người mượn' : 'Thông tin người mượn' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng" onclick="window.location.href='nguoi-muon.php?tab=<?= e($tab ?? 'all') ?>'"></button>
            </div>
            <div class="modal-body">
                <?php if ($canEdit): ?>
                    <form method="post" id="borrowerForm">
                        <?= csrf_input() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="action" value="<?= $new ? 'create' : 'edit' ?>">
                        <div class="fields">
                            <?php foreach (BORROWER_FIELDS as $key => [$label, $max, $required]): ?>
                                <label><?= e($label) ?>
                                    <input name="<?= e($key) ?>" maxlength="<?= $max ?>" <?= $required ? 'required' : '' ?>
                                           value="<?= e($profile[$key] ?? '') ?>">
                                </label>
                            <?php endforeach; ?>
                            <label>Loại người mượn
                                <select name="loai_nguoi_muon">
                                    <?php foreach (BORROWER_TYPES as $key => $label): ?>
                                        <option value="<?= e($key) ?>" <?= ($profile['loai_nguoi_muon'] ?? '') === $key ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                    </form>
                    <?php if ($id): ?>
                        <hr class="my-3">
                        <form method="post" class="actions" onsubmit="return confirm('Xóa hồ sơ người mượn đang chọn?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit">Xóa hồ sơ</button>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <dl>
                        <?php foreach (BORROWER_FIELDS as $key => [$label]): ?>
                            <dt><?= e($label) ?></dt>
                            <dd><?= e($profile[$key] ?? '—') ?></dd>
                        <?php endforeach; ?>
                        <dt>Loại người mượn</dt>
                        <dd><?= e(BORROWER_TYPES[$profile['loai_nguoi_muon']]) ?></dd>
                    </dl>
                <?php endif; ?>

                <?php if ($history): ?>
                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">Lịch sử mượn của <?= e($profile['ho_ten'] ?? '') ?></h6>
                    <?php if (!$history->num_rows): ?>
                        <p class="text-muted">Chưa có tài sản mượn trong phạm vi tra cứu.</p>
                    <?php endif; ?>
                    <?php foreach ($history as $loan): ?>
                        <article class="loan-detail">
                            <strong><?= e($loan['ma_phieu_muon']) ?> · <?= e($loan['ma_tai_san']) ?></strong>
                            <div><?= e($loan['ten_tai_san']) ?> · Phòng <?= e($loan['ma_phong']) ?></div>
                            <small>Mượn <?= e($loan['thoi_gian_muon']) ?> · Hạn trả <?= e($loan['han_tra']) ?></small>
                            <span><?= $loan['thoi_gian_tra'] ? 'Đã trả ' . e($loan['thoi_gian_tra']) : ($loan['overdue'] ? 'Quá hạn' : 'Đang mượn') ?></span>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <a class="button" href="<?= e($link([])) ?>" data-bs-dismiss="modal">Đóng</a>
                <?php if ($canEdit): ?>
                    <button type="submit" form="borrowerForm" class="primary"><?= $new ? 'Tạo hồ sơ' : 'Lưu thay đổi' ?></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('borrowerModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        var myModal = new bootstrap.Modal(modalEl);
        myModal.show();
        modalEl.addEventListener('hidden.bs.modal', function () {
            if (window.location.search.includes('new=1') || window.location.search.includes('id=')) {
                window.location.href = 'nguoi-muon.php?tab=<?= e($tab ?? 'all') ?>';
            }
        });
    }
});
</script>
