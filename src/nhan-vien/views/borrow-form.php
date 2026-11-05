<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $assets
 * @var array<int, array<string, mixed>> $rooms
 * @var array<int, array<string, mixed>> $borrowers
 * @var string $bq
 * @var string $oldBorrower
 * @var string $oldRoom
 * @var string $oldDue
 * @var string $oldNote
 * @var array<int, string> $oldAssets
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($assets, $rooms, $borrowers)) {
    http_response_code(404);
    exit;
}
$bq = isset($bq) ? (string)$bq : '';
$oldBorrower = isset($oldBorrower) ? (string)$oldBorrower : '';
$oldRoom = isset($oldRoom) ? (string)$oldRoom : '';
$oldDue = isset($oldDue) ? (string)$oldDue : '';
$oldNote = isset($oldNote) ? (string)$oldNote : '';
$oldAssets = isset($oldAssets) && is_array($oldAssets) ? $oldAssets : [];
?>
<div class="row g-3">
  <div class="col-lg-5">
    <section class="panel" data-qr-scanner><h2>Quét QR thiết bị</h2><div class="row g-2"><div class="col-5"><div class="camera-box" data-qr-preview>Đưa mã QR thiết bị vào khung</div></div><div class="col-7"><button class="btn btn-outline-secondary w-100" type="button" data-qr-start>Bật camera</button><button class="btn btn-outline-secondary w-100" type="button" data-qr-stop hidden>Tắt camera</button><label class="form-label section-label mt-2" for="scan-image">Chọn ảnh mã QR</label><input class="form-control form-control-sm" type="file" accept="image/png,image/jpeg,image/webp" id="scan-image" data-qr-file><label class="form-label section-label mt-2" for="scan-code">Nhập mã khi không quét được</label><input class="form-control" id="scan-code" placeholder="Mã tài sản hoặc mã QR"><button class="btn btn-outline-secondary mt-2 w-100" type="button" id="scan-add">Thêm thiết bị</button></div></div><small class="muted d-block mt-2" data-qr-status role="status">Camera cần HTTPS hoặc localhost; ảnh QR có thể chọn từ thiết bị.</small></section>
    <section class="panel"><h2>Phiếu mượn</h2><div id="borrow-preview" class="muted">Chọn người mượn, phòng học và thiết bị để lập phiếu.</div></section>
  </div>
  <div class="col-lg-7">
    <section class="panel"><h2>Thông tin mượn</h2>
      <form method="get" class="d-flex gap-2 mb-3"><input type="hidden" name="tab" value="muon"><input class="form-control" name="nguoi_muon" value="<?= e($bq) ?>" placeholder="Tìm người mượn: mã số / họ tên / SĐT"><button class="btn btn-outline-secondary" type="submit">Tìm</button></form>
      <form method="post" id="borrow-form"><?= csrf_input() ?>
        <label class="form-label" for="borrower">Người mượn</label><select class="form-select mb-3" id="borrower" name="nguoi_muon_id" required><option value="">Chọn hồ sơ</option><?php foreach ($borrowers as $person): ?><option value="<?= (int)$person['id'] ?>" data-name="<?= e($person['ho_ten']) ?>" data-code="<?= e($person['ma_so_truong']) ?>" <?= $oldBorrower === (string)$person['id'] ? 'selected' : '' ?>><?= e($person['ma_so_truong'] . ' — ' . $person['ho_ten'] . ' · ' . ($person['lop_don_vi'] ?? '') . ' · ' . ($person['so_dien_thoai'] ?? '')) ?></option><?php endforeach; ?></select>
        <?php if (!$borrowers): ?><div class="alert alert-info">Chưa có hồ sơ phù hợp. Quản lý cần thêm người mượn trước khi lập phiếu.</div><?php endif; ?>
        <div class="row g-2 mb-3"><div class="col-md-6"><label class="form-label" for="room">Phòng cần mượn</label><select class="form-select" id="room" name="phong_hoc_id" required><option value="">Chọn phòng học</option><?php foreach ($rooms as $room): ?><option value="<?= (int)$room['id'] ?>" data-code="<?= e($room['ma_phong']) ?>" <?= $oldRoom === (string)$room['id'] ? 'selected' : '' ?>><?= e($room['ma_phong'] . ' — ' . $room['ten_phong']) ?></option><?php endforeach; ?></select></div><div class="col-md-6"><label class="form-label" for="due">Hạn trả</label><input type="datetime-local" class="form-control" id="due" name="han_tra" value="<?= e($oldDue) ?>" required></div></div>
        <label class="form-label" for="borrow-note">Ghi chú phiếu</label><textarea class="form-control mb-3" id="borrow-note" name="ghi_chu" maxlength="2000" rows="2"><?= e($oldNote) ?></textarea>
        <h2>Thiết bị cần mượn <small class="muted fs-6">(chọn nhiều thiết bị)</small></h2><input class="form-control mb-2" id="asset-filter" placeholder="Lọc theo mã / tên / QR"><div class="result-list" id="asset-list">
        <?php foreach ($assets as $asset): $codes = json_decode((string)$asset['phong_tuong_thich'], true); $compat = $asset['pham_vi_tuong_thich'] === 'LINH_HOAT' ? '*' : ($asset['pham_vi_tuong_thich'] === 'THEO_PHONG' && is_array($codes) ? implode('|', $codes) : (string)$asset['phong_hoc_phu_hop_id']); ?><label class="item-row d-flex align-items-center gap-2 asset-item" data-code="<?= e(mb_strtolower($asset['ma_tai_san'] . ' ' . $asset['ma_qr'] . ' ' . $asset['ten_tai_san'])) ?>" data-asset-code="<?= e($asset['ma_tai_san']) ?>" data-asset-qr="<?= e($asset['ma_qr']) ?>" data-compat="<?= e($compat) ?>"><input type="checkbox" name="tai_san_id[]" value="<?= (int)$asset['id'] ?>" data-name="<?= e($asset['ma_tai_san'] . ' — ' . $asset['ten_tai_san']) ?>" <?= in_array((string)$asset['id'], array_map('strval', $oldAssets), true) ? 'checked' : '' ?>><span><b><?= e($asset['ma_tai_san']) ?></b> — <?= e($asset['ten_tai_san']) ?><small class="muted d-block">QR: <?= e($asset['ma_qr']) ?> · <?= $compat === '*' ? 'Dùng linh hoạt' : 'Phòng phù hợp: ' . e($compat) ?></small></span></label><?php endforeach; ?>
        </div><p class="muted mt-2" id="asset-empty" hidden>Chọn phòng học để xem thiết bị phù hợp.</p><div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="muon-tra.php?tab=muon">Hủy</a><button class="btn btn-dark" type="submit">Xác nhận mượn &amp; lưu phiếu</button></div>
      </form>
    </section>
  </div>
</div>
