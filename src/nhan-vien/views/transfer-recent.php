<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $recent
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($recent)) {
    http_response_code(404);
    exit;
}
?>
<section class="panel">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-2"><h2 class="mb-0">Điều chuyển gần đây</h2><small class="muted">Trong phạm vi tài sản của phòng trực</small></div>
    <div class="table-responsive"><table class="table align-middle mb-0 transfer-recent"><thead class="table-light"><tr><th>Mã phiếu</th><th>Thời gian</th><th>Phòng đi</th><th>Phòng đến</th><th>Thiết bị</th><th>Thao tác</th></tr></thead><tbody>
        <?php foreach ($recent as $item): ?><tr><td><strong><?= e($item['ma_dieu_chuyen']) ?></strong></td><td><?= e($item['thoi_gian_dieu_chuyen']) ?></td><td><?= e($item['phong_di']) ?></td><td><?= e($item['phong_den']) ?></td><td><?= (int)$item['asset_count'] ?></td><td><a class="btn btn-outline-secondary btn-sm" href="dieu-chuyen.php?id=<?= (int)$item['id'] ?>#transfer-detail">Xem phiếu</a></td></tr><?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="6" class="text-center muted py-4">Chưa có phiếu điều chuyển nào.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
