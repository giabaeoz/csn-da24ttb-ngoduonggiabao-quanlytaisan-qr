<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $round
 * @var array<int, array<string, mixed>> $details
 * @var array<string, int> $counts
 * @var array<int, array<string, mixed>> $visibleDetails
 * @var array<int, array<string, mixed>> $rooms
 * @var array<string, mixed>|null $selectedDetail
 * @var string $keyword
 * @var string $filter
 * @var int $incidentCount
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/inventory-service.php';

if (!isset($round, $details, $counts, $visibleDetails, $rooms)) {
    http_response_code(404);
    exit;
}
$selectedDetail = isset($selectedDetail) ? $selectedDetail : null;
$keyword = isset($keyword) ? (string)$keyword : '';
$filter = isset($filter) ? (string)$filter : 'all';
$incidentCount = isset($incidentCount) ? (int)$incidentCount : 0;
?>
<?php $isOpen = $round['trang_thai'] === 'DANG_KIEM_KE'; ?>
<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <section class="panel h-100" data-qr-scanner>
            <h2>Quét QR để đối chiếu thiết bị</h2>
            <?php if ($isOpen): ?>
                <div class="row g-2">
                    <div class="col-5"><div class="camera-box" data-qr-preview>Đưa mã QR thiết bị vào khung</div></div>
                    <div class="col-7">
                        <button type="button" class="btn btn-outline-secondary w-100" data-qr-start>Bật camera</button>
                        <button type="button" class="btn btn-outline-secondary w-100" data-qr-stop hidden>Tắt camera</button>
                        <label class="form-label section-label mt-2" for="inventory-image">Chọn ảnh QR</label>
                        <input id="inventory-image" type="file" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm" data-qr-file>
                        <form method="get" class="mt-2" id="inventory-scan-form">
                            <input type="hidden" name="id" value="<?= (int)$round['id'] ?>">
                            <label for="inventory-scan" class="form-label section-label">Nhập mã khi không quét được</label>
                            <div class="d-flex gap-1"><input id="inventory-scan" name="scan" maxlength="100" class="form-control" placeholder="Mã tài sản hoặc TVU:..."><button class="btn btn-outline-secondary">Tìm</button></div>
                        </form>
                    </div>
                </div>
                <small class="muted d-block mt-2" data-qr-status role="status">Quét QR sẽ mở biểu mẫu ghi nhận tài sản trong đợt này.</small>
            <?php else: ?>
                <p class="muted mb-0">Đợt đã hoàn tất. Kết quả chỉ có thể xem, không sửa.</p>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="panel h-100">
            <div class="d-flex justify-content-between gap-2"><h2>Thông tin đợt kiểm kê</h2><span class="badge <?= $isOpen ? 'text-bg-secondary' : 'text-bg-success' ?> align-self-start"><?= $isOpen ? 'Đang thực hiện' : 'Đã hoàn tất' ?></span></div>
            <div class="row g-3">
                <div class="col-md-6"><span class="section-label">Mã đợt</span><div><strong><?= e($round['ma_dot_kiem_ke']) ?></strong></div></div>
                <div class="col-md-6"><span class="section-label">Loại kiểm kê</span><div><?= e(INVENTORY_TYPES[$round['loai_kiem_ke']] ?? $round['loai_kiem_ke']) ?></div></div>
                <div class="col-12"><span class="section-label">Tên / phạm vi tài sản</span><div><?= e($round['ten_dot_kiem_ke']) ?> · Tài sản thuộc <?= e($round['ten_phong_truc']) ?></div></div>
                <div class="col-md-6"><span class="section-label">Nơi kiểm tra thực tế</span><div>Phòng trực hoặc phòng học ghi theo từng tài sản</div></div>
                <div class="col-md-6"><span class="section-label">Thời gian</span><div><?= e($round['thoi_gian_bat_dau']) ?><?= $round['thoi_gian_ket_thuc'] ? ' → ' . e($round['thoi_gian_ket_thuc']) : '' ?></div></div>
                <div class="col-12"><span class="section-label">Người lập</span><div><?= e($round['nguoi_lap']) ?></div></div>
                <?php if ($round['ghi_chu']): ?><div class="col-12"><span class="section-label">Ghi chú</span><div><?= nl2br(e($round['ghi_chu'])) ?></div></div><?php endif; ?>
            </div>
        </section>
    </div>
</div>

<div class="row g-3 mb-3 inventory-stats">
    <?php foreach (['Phải kiểm thực tế' => $counts['required'], 'Đã kiểm' => $counts['checked'], 'Chưa kiểm' => $counts['remaining'], 'Đang mượn theo phiếu' => $counts['borrowed']] as $label => $value): ?>
        <div class="col-sm-6 col-xl-3"><section class="panel h-100"><span class="muted"><?= e($label) ?></span><div class="display-6"><?= (int)$value ?></div></section></div>
    <?php endforeach; ?>
</div>

<?php if (isset($_GET['scan']) && is_string($_GET['scan']) && trim($_GET['scan']) !== '' && !$selectedDetail): ?>
    <div class="alert alert-warning">Mã vừa quét không thuộc danh sách tài sản của đợt kiểm kê này.</div>
<?php endif; ?>
<?php if ($selectedDetail && $isOpen): ?>
    <?php if ($selectedDetail['trang_thai_so_sach'] === 'DANG_MUON'): ?>
        <div class="alert alert-info">Tài sản <?= e($selectedDetail['ma_tai_san']) ?> đang mượn lúc mở đợt; không phải kiểm thực tế trong đợt này.</div>
    <?php else: ?>
        <section class="panel" id="inventory-record">
            <div class="d-flex justify-content-between align-items-center gap-2"><h2>Ghi nhận <?= e($selectedDetail['ma_tai_san'] . ' — ' . $selectedDetail['ten_tai_san']) ?></h2><a class="btn btn-outline-secondary btn-sm" href="kiem-ke.php?id=<?= (int)$round['id'] ?>">Đóng</a></div>
            <p class="muted">Sổ sách: <?= e($selectedDetail['tinh_trang_so_sach']) ?> · <?= e($selectedDetail['phong_so_sach'] ?? 'Tại phòng trực') ?>. Ghi đúng vị trí và tình trạng quan sát được.</p>
            <form method="post" class="row g-3" id="inventory-record-form">
                <?= csrf_input() ?><input type="hidden" name="action" value="record">
                <input type="hidden" name="dot_kiem_ke_id" value="<?= (int)$round['id'] ?>">
                <input type="hidden" name="chi_tiet_kiem_ke_id" value="<?= (int)$selectedDetail['id'] ?>">
                <div class="col-md-4"><label class="form-label" for="inventory-result">Kết quả đối chiếu</label>
                    <select class="form-select" name="ket_qua" id="inventory-result">
                        <option value="TIM_THAY" <?= $selectedDetail['ket_qua'] !== 'CHUA_TIM_THAY' ? 'selected' : '' ?>>Có mặt</option>
                        <option value="CHUA_TIM_THAY" <?= $selectedDetail['ket_qua'] === 'CHUA_TIM_THAY' ? 'selected' : '' ?>>Chưa tìm thấy</option>
                    </select></div>
                <div class="col-md-4 inventory-found-field"><label class="form-label" for="inventory-condition">Tình trạng thực tế</label>
                    <select class="form-select" name="tinh_trang_thuc_te" id="inventory-condition">
                        <option value="TOT" <?= $selectedDetail['tinh_trang_thuc_te'] !== 'HU_HONG' ? 'selected' : '' ?>>Bình thường</option>
                        <option value="HU_HONG" <?= $selectedDetail['tinh_trang_thuc_te'] === 'HU_HONG' ? 'selected' : '' ?>>Hư hỏng</option>
                    </select></div>
                <div class="col-md-4 inventory-found-field"><label class="form-label" for="inventory-room">Vị trí thực tế</label>
                    <select class="form-select" name="phong_hoc_thuc_te_id" id="inventory-room">
                        <option value="">Tại phòng trực</option>
                        <?php foreach ($rooms as $room): ?><option value="<?= (int)$room['id'] ?>" <?= (int)$selectedDetail['phong_hoc_thuc_te_id'] === (int)$room['id'] ? 'selected' : '' ?>><?= e($room['ma_phong'] . ' — ' . $room['ten_phong']) ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-12"><label class="form-label" for="inventory-note">Ghi chú <span class="muted">(bắt buộc khi hư hỏng hoặc chưa tìm thấy)</span></label>
                    <textarea class="form-control" name="ghi_chu" id="inventory-note" rows="2" maxlength="2000" placeholder="Mô tả hư hỏng hoặc nơi đã tìm kiếm"><?= e($selectedDetail['ghi_chu'] ?? '') ?></textarea></div>
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-dark" type="submit">Lưu kết quả đối chiếu</button></div>
            </form>
        </section>
    <?php endif; ?>
<?php endif; ?>

<section class="panel">
    <form class="inventory-filters" method="get">
        <input type="hidden" name="id" value="<?= (int)$round['id'] ?>">
        <label> Tìm thiết bị
            <input class="form-control" name="q" maxlength="100" value="<?= e($keyword) ?>" placeholder="Mã / tên thiết bị / QR">
        </label>
        <label>Kết quả đối chiếu
            <select class="form-select" name="result">
                <?php foreach (['all'=>'Tất cả', 'found'=>'Có mặt', 'missing'=>'Chưa tìm thấy', 'unchecked'=>'Chưa kiểm', 'borrowed'=>'Đang mượn'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filter === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn btn-outline-secondary align-self-end">Tìm</button>
    </form>
</section>

<section class="panel">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-2"><h2 class="mb-0">Danh sách đối chiếu</h2><small class="muted"><?= count($visibleDetails) ?> / <?= count($details) ?> tài sản trong phạm vi</small></div>
    <div class="table-responsive">
        <table class="table table-sm align-middle inventory-table">
            <thead class="table-light"><tr><th>Mã / tên thiết bị</th><th>Đối chiếu</th><th>Tình trạng thực tế</th><th>Vị trí thực tế / ghi chú</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($visibleDetails as $item): ?>
                <tr <?= $selectedDetail && (int)$selectedDetail['id'] === (int)$item['id'] ? 'class="table-active"' : '' ?>>
                    <td><strong><?= e($item['ma_tai_san']) ?></strong> — <?= e($item['ten_tai_san']) ?><small class="muted d-block">Sổ sách: <?= e($item['phong_so_sach'] ?? 'Phòng trực') ?></small></td>
                    <td><?= $item['trang_thai_so_sach'] === 'DANG_MUON' ? 'Đang mượn' : e(INVENTORY_RESULTS[$item['ket_qua']] ?? $item['ket_qua']) ?></td>
                    <td><?= $item['ket_qua'] === 'TIM_THAY' ? ($item['tinh_trang_thuc_te'] === 'HU_HONG' ? 'Hư hỏng' : 'Bình thường') : ($item['trang_thai_so_sach'] === 'DANG_MUON' ? 'Theo phiếu mượn' : '—') ?></td>
                    <td><?= $item['ket_qua'] === 'TIM_THAY' ? e($item['phong_thuc_te'] ?? 'Phòng trực') : '—' ?><?= $item['ghi_chu'] ? '<small class="muted d-block">' . e($item['ghi_chu']) . '</small>' : '' ?></td>
                    <td>
                        <?php if ($item['trang_thai_so_sach'] === 'DANG_MUON'): ?>
                            <?php if ($item['phieu_muon_id']): ?><a class="btn btn-outline-secondary btn-sm" href="muon-tra.php?tab=tra&amp;id=<?= (int)$item['phieu_muon_id'] ?>">Xem phiếu</a><?php else: ?><small class="muted">Mượn lúc mở đợt</small><?php endif; ?>
                        <?php elseif ($isOpen): ?>
                            <a class="btn btn-outline-secondary btn-sm" href="kiem-ke.php?id=<?= (int)$round['id'] ?>&amp;detail=<?= (int)$item['id'] ?>#inventory-record"><?= $item['ket_qua'] === 'CHUA_KIEM_TRA' ? 'Ghi nhận' : 'Sửa' ?></a>
                        <?php else: ?><small class="muted">Đã lưu</small><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$visibleDetails): ?><tr><td colspan="5" class="text-center muted py-3">Không có tài sản phù hợp bộ lọc.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel">
    <h2>Kết quả <?= $isOpen ? 'tạm thời' : 'đã hoàn tất' ?></h2>
    <p class="mb-1">Bình thường: <strong><?= $counts['normal'] ?></strong> · Hỏng: <strong><?= $counts['damaged'] ?></strong> · Chưa tìm thấy: <strong><?= $counts['missing'] ?></strong> · Đang mượn: <strong><?= $counts['borrowed'] ?></strong> · Chưa kiểm: <strong><?= $counts['remaining'] ?></strong></p>
    <?php if ($isOpen): ?><small class="muted">Mỗi lần ghi nhận được lưu ngay. Cần đối chiếu <?= $counts['remaining'] ?> tài sản còn lại trước khi hoàn tất.</small>
    <?php else: ?><small class="muted"><?= $incidentCount ?> sự cố đã được lập từ kết quả kiểm kê. Tài sản hư hỏng hoặc chưa tìm thấy được tạm chặn cho mượn để chờ xử lý.</small><?php endif; ?>
</section>
<?php if ($isOpen): ?>
    <div class="d-flex justify-content-end gap-2 mb-4">
        <a class="btn btn-outline-secondary" href="index.php">Lưu nháp &amp; quay lại</a>
        <form method="post" onsubmit="return confirm('Hoàn tất đợt kiểm kê? Sau khi chốt sẽ không thể sửa kết quả.');">
            <?= csrf_input() ?><input type="hidden" name="action" value="finish">
            <input type="hidden" name="dot_kiem_ke_id" value="<?= (int)$round['id'] ?>">
            <button class="btn btn-dark" type="submit" <?= $counts['remaining'] ? 'disabled' : '' ?>>Hoàn tất kiểm kê</button>
        </form>
    </div>
<?php endif; ?>
