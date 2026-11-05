<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $selectedTransfer
 * @var array<int, array<string, mixed>> $selectedTransferAssets
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($selectedTransfer, $selectedTransferAssets)) {
    http_response_code(404);
    exit;
}
?>
<section class="panel transfer-detail" id="transfer-detail">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div><h2>Phiếu <?= e($selectedTransfer['ma_dieu_chuyen']) ?></h2><p class="muted mb-0">Đã lưu, chỉ xem · <?= e($selectedTransfer['thoi_gian_dieu_chuyen']) ?></p></div>
        <a class="btn btn-outline-secondary btn-sm" href="dieu-chuyen.php">Tạo phiếu mới</a>
    </div>
    <div class="row g-3 mt-1">
        <div class="col-md-6"><span class="section-label">Phòng đi</span><div><?= e($selectedTransfer['phong_di'] . ' — ' . $selectedTransfer['ten_phong_di']) ?></div></div>
        <div class="col-md-6"><span class="section-label">Phòng đến</span><div><?= e($selectedTransfer['phong_den'] . ' — ' . $selectedTransfer['ten_phong_den']) ?></div></div>
        <div class="col-md-6"><span class="section-label">Người lập</span><div><?= e($selectedTransfer['nguoi_lap']) ?></div></div>
        <div class="col-md-6"><span class="section-label">Lý do</span><div><?= nl2br(e($selectedTransfer['ly_do'])) ?></div></div>
        <?php if ($selectedTransfer['ghi_chu']): ?><div class="col-12"><span class="section-label">Ghi chú</span><div><?= nl2br(e($selectedTransfer['ghi_chu'])) ?></div></div><?php endif; ?>
    </div>
    <div class="table-responsive mt-3"><table class="table table-sm align-middle mb-0"><thead class="table-light"><tr><th>Mã tài sản</th><th>Tên tài sản</th><th>Vị trí sau điều chuyển</th></tr></thead><tbody>
        <?php foreach ($selectedTransferAssets as $asset): ?><tr><td><strong><?= e($asset['ma_tai_san']) ?></strong></td><td><?= e($asset['ten_tai_san']) ?></td><td><?= e($selectedTransfer['phong_den']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
