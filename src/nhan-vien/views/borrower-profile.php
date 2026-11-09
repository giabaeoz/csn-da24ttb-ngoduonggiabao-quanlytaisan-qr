<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $loans
 * @var bool $canEditProfile
 * @var array<string, mixed>|null $profile
 * @var array<string, mixed> $desk
 * @var callable $link
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($loans, $canEditProfile)) {
    http_response_code(404);
    exit;
}
$profile = isset($profile) ? $profile : null;
?>
<div class="borrower-bottom-grid" id="borrower-profile">
    <div class="borrower-profile-card">
        <h2>Thông tin người mượn đang chọn</h2>
        <?php if (!$profile): ?>
            <p class="text-muted py-4 text-center m-0">Chọn một người mượn ở danh sách phía trên để xem và cập nhật thông tin.</p>
        <?php elseif ($canEditProfile): ?>
            <form method="post" action="nguoi-muon.php?id=<?= (int)$profile['id'] ?>#borrower-profile" class="borrower-profile-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$profile['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="borrower-code" class="form-label">Mã số</label>
                        <input id="borrower-code" name="ma_so_truong" class="form-control" maxlength="30" required value="<?= e($profile['ma_so_truong']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="borrower-phone" class="form-label">Số điện thoại</label>
                        <input id="borrower-phone" name="so_dien_thoai" class="form-control" maxlength="20" value="<?= e($profile['so_dien_thoai'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label for="borrower-name" class="form-label">Họ tên</label>
                        <input id="borrower-name" name="ho_ten" class="form-control" maxlength="100" required value="<?= e($profile['ho_ten']) ?>">
                    </div>
                    <div class="col-12">
                        <label for="borrower-unit" class="form-label">Lớp / đơn vị</label>
                        <input id="borrower-unit" name="lop_don_vi" class="form-control" maxlength="100" value="<?= e($profile['lop_don_vi'] ?? '') ?>">
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn-sketch" href="<?= e($link(['id' => null])) ?>">Hủy</a>
                    <button class="btn-sketch-dark" type="submit">Lưu thay đổi</button>
                </div>
            </form>
        <?php else: ?>
            <form class="borrower-profile-form">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Mã số</label>
                        <input class="form-control" value="<?= e($profile['ma_so_truong']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại</label>
                        <input class="form-control" value="<?= e($profile['so_dien_thoai'] ?? '—') ?>" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Họ tên</label>
                        <input class="form-control" value="<?= e($profile['ho_ten']) ?>" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Lớp / đơn vị</label>
                        <input class="form-control" value="<?= e($profile['lop_don_vi'] ?? '—') ?>" readonly>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn-sketch" href="<?= e($link(['id' => null])) ?>">Đóng</a>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <div class="borrower-profile-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="m-0">Lịch sử mượn của người đang chọn</h2>
            <a class="btn-sketch" style="padding:3px 12px;font-size:12px;" href="muon-tra.php?tab=tra">Xem tất cả</a>
        </div>
        <?php if ($profile): ?>
            <div style="font-size:14px;color:#222;font-weight:600;margin-bottom:12px;">
                <?= e($profile['ho_ten']) ?> (<?= e($profile['ma_so_truong']) ?>)
            </div>
            <?php if ($loans): ?>
                <?php foreach ($loans as $loan): ?>
                    <?php $isOpen = (int)$loan['open_count'] > 0; ?>
                    <div class="borrower-loan-item">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><?= e($loan['ma_phieu_muon']) ?> • <?= date('d/m/Y', strtotime((string)$loan['thoi_gian_muon'])) ?></strong>
                            <span class="text-muted"><?= $isOpen ? ($loan['overdue'] ? 'Quá hạn' : 'Đang mượn') : 'Đã trả đủ' ?></span>
                        </div>
                        <div class="text-muted" style="margin:2px 0;"><?= e($loan['ma_phong']) ?> • <?= (int)$loan['asset_count'] ?> thiết bị<?= $isOpen ? ' • ' . (int)$loan['open_count'] . ' chưa trả' : '' ?></div>
                        <div class="text-muted" style="font-size:12px;">Hạn trả: <?= date('d/m/Y H:i', strtotime((string)$loan['han_tra'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted text-center py-4 m-0">Người mượn chưa có phiếu mượn nào tại phòng trực này.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="text-muted text-center py-4 m-0">Chọn một người mượn ở danh sách phía trên để xem lịch sử mượn.</p>
        <?php endif; ?>
    </div>
</div>
</div>
