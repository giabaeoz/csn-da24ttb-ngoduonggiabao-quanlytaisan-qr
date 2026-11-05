<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $desk
 * @var array<int, array<string, mixed>> $rounds
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/inventory-service.php';

if (!isset($desk, $rounds)) {
    http_response_code(404);
    exit;
}
$activeRound = null;
foreach ($rounds as $item) {
    if ($item['trang_thai'] === 'DANG_KIEM_KE') { $activeRound = $item; break; }
}
?>
<section class="panel">
    <h2>+ Tạo đợt kiểm kê</h2>
    <?php if ($activeRound): ?>
        <p class="alert alert-info mb-0">Phòng trực đã có đợt <?= e($activeRound['ma_dot_kiem_ke']) ?> đang thực hiện.
            <a href="kiem-ke.php?id=<?= (int)$activeRound['id'] ?>">Tiếp tục đối chiếu</a>.</p>
    <?php else: ?>
        <form method="post" class="row g-3">
            <?= csrf_input() ?><input type="hidden" name="action" value="create">
            <div class="col-md-7">
                <label for="round-name" class="form-label">Tên đợt kiểm kê</label>
                <input id="round-name" name="ten_dot_kiem_ke" maxlength="150" required class="form-control"
                    value="<?= e(is_string($_POST['ten_dot_kiem_ke'] ?? null) ? $_POST['ten_dot_kiem_ke'] : '') ?>"
                    placeholder="Ví dụ: Kiểm kê cuối buổi khu B">
            </div>
            <div class="col-md-5">
                <label for="round-type" class="form-label">Loại kiểm kê</label>
                <select id="round-type" name="loai_kiem_ke" class="form-select" required>
                    <?php foreach (INVENTORY_TYPES as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($_POST['loai_kiem_ke'] ?? 'CUOI_BUOI') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6"><span class="section-label">Phạm vi tài sản</span><div>Thiết bị đang sử dụng thuộc <?= e($desk['ten_phong_truc']) ?></div></div>
            <div class="col-md-6"><span class="section-label">Nơi kiểm tra thực tế</span><div>Phòng trực hoặc phòng học được ghi riêng cho từng tài sản</div></div>
            <div class="col-12">
                <label for="round-note" class="form-label">Ghi chú (nếu có)</label>
                <textarea id="round-note" name="ghi_chu" maxlength="2000" rows="2" class="form-control"><?= e(is_string($_POST['ghi_chu'] ?? null) ? $_POST['ghi_chu'] : '') ?></textarea>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a class="btn btn-outline-secondary" href="kiem-ke.php">Hủy</a>
                <button class="btn btn-dark" type="submit">Tạo đợt và chụp danh sách tài sản</button>
            </div>
        </form>
    <?php endif; ?>
</section>
