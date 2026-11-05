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
<div class="row g-3 borrower-detail" id="borrower-profile">
    <div class="col-lg-6">
        <section class="panel h-100">
            <h2>Thông tin người mượn đang chọn</h2>
            <?php if (!$profile): ?>
                <p class="muted mb-0">Chọn <strong>Xem</strong> trong danh sách để mở hồ sơ.</p>
            <?php elseif ($canEditProfile): ?>
                <p class="muted">Cập nhật thông tin cơ bản cho người đã có phiếu tại <?= e($desk['ten_phong_truc']) ?>.</p>
                <form method="post" action="nguoi-muon.php?id=<?= (int)$profile['id'] ?>#borrower-profile" class="borrower-profile-form">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$profile['id'] ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="borrower-code" class="form-label">Mã số trường</label>
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
                    <div class="borrower-profile-actions">
                        <a class="btn btn-outline-secondary" href="<?= e($link(['id' => null])) ?>">Hủy</a>
                        <button class="btn btn-dark" type="submit">Lưu thay đổi</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="muted">Hồ sơ này chưa có phiếu tại phòng trực của bạn. Có thể xem để lập phiếu mượn; Quản lý cập nhật hồ sơ chung.</p>
                <dl class="borrower-readonly">
                    <div><dt>Mã số trường</dt><dd><?= e($profile['ma_so_truong']) ?></dd></div>
                    <div><dt>Họ tên</dt><dd><?= e($profile['ho_ten']) ?></dd></div>
                    <div><dt>Lớp / đơn vị</dt><dd><?= e($profile['lop_don_vi'] ?? '—') ?></dd></div>
                    <div><dt>Số điện thoại</dt><dd><?= e($profile['so_dien_thoai'] ?? '—') ?></dd></div>
                </dl>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="panel h-100">
            <h2>Lịch sử mượn của người đang chọn</h2>
            <?php if (!$profile): ?>
                <p class="muted mb-0">Chọn người mượn để xem các phiếu thuộc phòng trực của bạn.</p>
            <?php else: ?>
                <p><?= e($profile['ho_ten']) ?> <small class="muted">· <?= e($desk['ten_phong_truc']) ?></small></p>
                <?php if (!$loans): ?><p class="muted">Chưa có phiếu mượn tại phòng trực này.</p><?php endif; ?>
                <?php foreach ($loans as $loan): ?>
                    <?php $isOpen = (int)$loan['open_count'] > 0; ?>
                    <article class="borrower-loan">
                        <div class="d-flex justify-content-between gap-2">
                            <strong><?= e($loan['ma_phieu_muon']) ?> · <?= e($loan['thoi_gian_muon']) ?></strong>
                            <span><?= $isOpen ? ($loan['overdue'] ? 'Quá hạn' : 'Đang mượn') : 'Đã trả đủ' ?></span>
                        </div>
                        <div class="muted"><?= e($loan['ma_phong']) ?> · <?= (int)$loan['asset_count'] ?> thiết bị<?= $isOpen ? ' · ' . (int)$loan['open_count'] . ' chưa trả' : '' ?></div>
                        <small class="muted">Hạn trả: <?= e($loan['han_tra']) ?></small>
                        <a class="d-block" href="muon-tra.php?tab=tra&amp;id=<?= (int)$loan['id'] ?>">Xem phiếu</a>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</div>
