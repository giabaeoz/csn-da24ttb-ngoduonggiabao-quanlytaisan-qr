<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $asset
 * @var array<string, mixed> $history
 * @var callable $link
 * @var bool|null $canViewBorrower
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/assets.php';

if (!isset($asset, $history)) {
    http_response_code(404);
    exit;
}
$canViewBorrower = !empty($canViewBorrower);
?>
<section id="asset-profile" class="panel">
    <div class="card-title">
        <h2><?= e($asset['ma_tai_san']) ?> · <?= e($asset['ten_tai_san']) ?></h2>
        <a class="button" href="<?= e($link([])) ?>">Đóng chi tiết</a>
    </div>
    <div class="asset-detail-grid">
        <dl class="asset-properties">
            <?php foreach ([
                'Loại tài sản' => $asset['ten_loai'],
                'Phòng trực quản lý' => $asset['ten_phong_truc'],
                'Phạm vi tương thích' => match ($asset['pham_vi_tuong_thich']) {
                    'THEO_PHONG' => 'Theo phòng', 'LINH_HOAT' => 'Linh hoạt', default => 'Chưa xác định',
                },
                'Phòng tương thích' => asset_compatibility($asset),
                'Phòng hiện tại' => $asset['phong_hien_tai'] ?? 'Chưa xác định',
                'Tình trạng' => ASSET_CONDITIONS[$asset['tinh_trang']],
                'Trạng thái' => ASSET_STATES[$asset['trang_thai_hien_thi']],
                'Ngày đưa vào sử dụng' => $asset['ngay_dua_vao_su_dung'] ?? 'Chưa xác định',
            ] as $label => $value): ?>
                <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <div id="asset-qr">
            <div data-qr="<?= e($asset['ma_qr']) ?>" role="img" aria-label="Mã QR <?= e($asset['ma_tai_san']) ?>"></div>
            <small><?= e($asset['ma_qr']) ?></small>
            <button type="button" data-download-qr data-asset-code="<?= e($asset['ma_tai_san']) ?>">Tải mã QR SVG</button>
            <button type="button" data-download-qr-png data-asset-code="<?= e($asset['ma_tai_san']) ?>">Tải mã QR PNG</button>
        </div>
    </div>
    <?php if ($asset['mo_ta']): ?><p class="asset-note"><?= nl2br(e($asset['mo_ta'])) ?></p><?php endif; ?>
</section>
<section class="panel">
    <h2>Lịch sử mượn – trả</h2>
    <?php if (!$history['loans']): ?><p>Chưa có lịch sử mượn – trả.</p><?php endif; ?>
    <?php foreach ($history['loans'] as $loan): ?>
        <article class="loan-detail">
            <strong><?= e($loan['ma_phieu_muon']) ?></strong> ·
            <?php if (!empty($canViewBorrower)): ?><a href="<?= e(app_url('quan-ly/nguoi-muon.php')) ?>?tab=all&amp;id=<?= (int)$loan['nguoi_muon_id'] ?>"><?php endif; ?>
                <?= e($loan['ho_ten']) ?> (<?= e($loan['ma_so_truong']) ?>)
            <?php if (!empty($canViewBorrower)): ?></a><?php endif; ?>
            <small>Mượn <?= e($loan['thoi_gian_muon']) ?> · Hạn trả <?= e($loan['han_tra']) ?></small>
            <?= $loan['thoi_gian_tra'] ? 'Đã trả ' . e($loan['thoi_gian_tra']) : ($loan['qua_han'] ? 'Đang giữ · Quá hạn' : 'Đang giữ tài sản') ?>
        </article>
    <?php endforeach; ?>
</section>
<div class="asset-history-grid">
    <section class="panel">
        <h2>Kiểm kê</h2>
        <?php if (!$history['inventory']): ?><p>Chưa có lịch sử kiểm kê.</p><?php endif; ?>
        <?php foreach ($history['inventory'] as $item): ?>
            <article class="loan-detail">
                <strong><?= e($item['ma_dot_kiem_ke']) ?> · <?= e($item['ten_dot_kiem_ke']) ?></strong>
                <small>Mở đợt <?= e($item['thoi_gian_bat_dau']) ?></small>
                <div><?= e(['CHUA_KIEM_TRA'=>'Chưa kiểm tra','TIM_THAY'=>'Tìm thấy','CHUA_TIM_THAY'=>'Chưa tìm thấy'][$item['ket_qua']]) ?></div>
                <small><?= e($item['thoi_gian_kiem_tra'] ?? 'Chưa ghi nhận thời điểm kiểm tra') ?> · <?= e($item['ma_phong'] ?? 'Chưa ghi nhận vị trí thực tế') ?></small>
                <span><?= e(ASSET_CONDITIONS[$item['tinh_trang_thuc_te']] ?? 'Chưa ghi nhận tình trạng thực tế') ?></span>
            </article>
        <?php endforeach; ?>
    </section>
    <section class="panel">
        <h2>Điều chuyển</h2>
        <?php if (!$history['transfers']): ?><p>Chưa có lịch sử điều chuyển.</p><?php endif; ?>
        <?php foreach ($history['transfers'] as $item): ?>
            <article class="loan-detail">
                <strong><?= e($item['ma_dieu_chuyen']) ?></strong>
                <div><?= e($item['phong_di']) ?> → <?= e($item['phong_den']) ?></div>
                <small><?= e($item['thoi_gian_dieu_chuyen']) ?></small>
                <span><?= e($item['ly_do']) ?></span>
            </article>
        <?php endforeach; ?>
    </section>
</div>
<section class="panel">
    <h2>Sự cố tài sản</h2>
    <?php if (!$history['incidents']): ?><p>Chưa ghi nhận sự cố.</p><?php endif; ?>
    <?php foreach ($history['incidents'] as $item): ?>
        <article class="loan-detail">
            <strong><?= e($item['ma_su_co']) ?> · <?= e(['HU_HONG'=>'Hư hỏng','MAT'=>'Mất','SAI_VI_TRI'=>'Sai vị trí'][$item['loai_su_co']]) ?></strong>
            <small><?= e($item['thoi_gian_phat_hien']) ?> · <?= e(['CHUA_XU_LY'=>'Chưa xử lý','DANG_XU_LY'=>'Đang xử lý','DA_XU_LY'=>'Đã xử lý'][$item['trang_thai']]) ?></small>
            <p><?= e($item['mo_ta']) ?></p>
            <?php if ($item['ket_qua_xu_ly']): ?><p><?= e($item['ket_qua_xu_ly']) ?></p><?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
