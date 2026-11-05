<?php

declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $loans
 * @var array<int, array<string, mixed>> $details
 * @var array<string, mixed>|null $selectedLoan
 * @var string $q
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($loans, $details)) {
    http_response_code(404);
    exit;
}
$q = isset($q) ? (string)$q : '';
$selectedLoan = isset($selectedLoan) ? $selectedLoan : null;
?>
<div class="row g-3">
    <div class="col-lg-5">
        <section class="panel" data-qr-scanner>
            <h2>Quét QR thiết bị trả</h2>
            <div class="row g-2">
                <div class="col-5">
                    <div class="camera-box" data-qr-preview>Đưa mã QR thiết bị vào khung</div>
                </div>
                <div class="col-7"><button class="btn btn-outline-secondary w-100" type="button" data-qr-start>Bật camera</button><button class="btn btn-outline-secondary w-100" type="button" data-qr-stop hidden>Tắt camera</button><label class="form-label section-label mt-2" for="return-image">Chọn ảnh mã QR</label><input class="form-control form-control-sm" type="file" accept="image/png,image/jpeg,image/webp" id="return-image" data-qr-file>
                    <form method="get" class="mt-2"><input type="hidden" name="tab" value="tra"><label class="form-label section-label" for="return-search">Mã thiết bị / mã phiếu / mã số</label><input class="form-control" id="return-search" name="q" value="<?= e($q) ?>"><button class="btn btn-outline-secondary mt-2 w-100">Tìm</button></form>
                </div>
            </div><small class="muted d-block mt-2" data-qr-status role="status">Quét mã trên thiết bị để mở phiếu chưa trả trong phòng trực.</small>
        </section>
        <section class="panel">
            <h2>Phiếu trả tài sản</h2><?php if ($selectedLoan): ?><p><?= e($selectedLoan['ho_ten'] . '  ' . $selectedLoan['ma_so_truong']) ?><br>Phiếu: <strong><?= e($selectedLoan['ma_phieu_muon']) ?></strong> · Phòng: <?= e($selectedLoan['ma_phong']) ?><br>Hạn trả: <?= e($selectedLoan['han_tra']) ?></p>
                <p>Đã trả: <?= count(array_filter($details, fn($d) => $d['thoi_gian_tra'] !== null)) ?> / <?= count($details) ?> thiết bị</p><?php else: ?><p class="muted">Chọn phiếu mượn để nhận thiết bị.</p><?php endif; ?>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="panel">
            <h2>Phiếu đang mượn</h2>
            <div class="result-list"><?php foreach ($loans as $loan): ?><a class="item-row d-block text-decoration-none text-dark" href="muon-tra.php?tab=tra&id=<?= (int)$loan['id'] ?>"><b><?= e($loan['ma_phieu_muon']) ?></b> · <?= e($loan['ho_ten']) ?> (<?= e($loan['ma_so_truong']) ?>)<small class="muted d-block"><?= e($loan['ma_phong']) ?> · <?= (int)$loan['so_chua_tra'] ?> thiết bị chưa trả · Hạn <?= e($loan['han_tra']) ?></small></a><?php endforeach; ?><?php if (!$loans): ?><p class="muted">Không có phiếu đang mượn phù hợp.</p><?php endif; ?></div>
        </section>
        <?php if ($selectedLoan): ?><section class="panel">
                <h2>Thông tin phiếu mượn <small class="muted fs-6"><?= e($selectedLoan['ma_phieu_muon']) ?></small></h2>
                <div class="row g-2 mb-3">
                    <div class="col-md-6"><span class="section-label">Người mượn</span>
                        <div><?= e($selectedLoan['ho_ten'] . '  ' . $selectedLoan['ma_so_truong']) ?></div>
                    </div>
                    <div class="col-md-6"><span class="section-label">Phòng đã mượn / Hạn trả</span>
                        <div><?= e($selectedLoan['ma_phong'] . ' / ' . $selectedLoan['han_tra']) ?></div>
                    </div>
                </div>
                <h2>Thiết bị nhận trả</h2>
                <form method="post" action="muon-tra.php?tab=tra&id=<?= (int)$selectedLoan['id'] ?>"><?= csrf_input() ?><input type="hidden" name="phieu_muon_id" value="<?= (int)$selectedLoan['id'] ?>">
                    <?php foreach ($details as $detail): ?><div class="item-row row g-2 align-items-center">
                            <div class="col-md-6"><label class="d-flex gap-2 align-items-start"><input type="checkbox" class="form-check-input mt-1" name="chi_tiet_id[]" value="<?= (int)$detail['id'] ?>" <?= $detail['thoi_gian_tra'] ? 'disabled' : '' ?> <?= !$detail['thoi_gian_tra'] && $q !== '' && in_array(mb_strtolower($q, 'UTF-8'), [mb_strtolower((string)$detail['ma_qr'], 'UTF-8'), mb_strtolower((string)$detail['ma_tai_san'], 'UTF-8')], true) ? 'checked' : '' ?>><span><b><?= e($detail['ma_tai_san']) ?></b> <?= e($detail['ten_tai_san']) ?><small class="muted d-block"><?= $detail['thoi_gian_tra'] ? 'Đã trả: ' . e($detail['thoi_gian_tra']) : 'Chưa trả' ?></small></span></label></div>
                            <div class="col-md-6"><select class="form-select" name="tinh_trang[<?= (int)$detail['id'] ?>]" <?= $detail['thoi_gian_tra'] ? 'disabled' : '' ?>>
                                    <option value="TOT">Bình thường</option>
                                    <option value="HU_HONG">Hư hỏng</option>
                                </select><input class="form-control mt-1" name="ghi_chu[<?= (int)$detail['id'] ?>]" maxlength="2000" placeholder="Mô tả hư hỏng nếu có" <?= $detail['thoi_gian_tra'] ? 'disabled' : '' ?>></div>
                        </div><?php endforeach; ?>
                    <?php if ($selectedLoan['trang_thai'] === 'DANG_MUON'): ?><div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="muon-tra.php?tab=tra">Hủy</a><button class="btn btn-dark" type="submit">Xác nhận trả &amp; lưu phiếu</button></div><?php else: ?><p class="alert alert-success mt-3 mb-0">Phiếu đã trả đầy đủ.</p><?php endif; ?></form>
            </section><?php elseif (isset($_GET['id'])): ?><div class="alert alert-warning">Không tìm thấy phiếu trong phòng trực của bạn.</div><?php endif; ?>
    </div>
</div>