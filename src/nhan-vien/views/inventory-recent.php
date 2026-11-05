<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $rounds
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/inventory-service.php';

if (!isset($rounds)) {
    http_response_code(404);
    exit;
}
?>
<section class="panel">
    <h2>Đợt kiểm kê gần đây</h2>
    <?php if (!$rounds): ?><p class="muted mb-0">Chưa có đợt kiểm kê nào trong phòng trực.</p><?php endif; ?>
    <div class="inventory-recent">
        <?php foreach ($rounds as $item): ?>
            <div class="inventory-recent-item">
                <span><strong><?= e($item['ma_dot_kiem_ke']) ?></strong> · <?= e(INVENTORY_TYPES[$item['loai_kiem_ke']] ?? $item['loai_kiem_ke']) ?>
                    <small class="muted d-block"><?= e($item['ten_dot_kiem_ke']) ?> · <?= e($item['thoi_gian_bat_dau']) ?></small></span>
                <span><?= e($item['ten_phong_truc']) ?></span>
                <span><?= $item['trang_thai'] === 'HOAN_TAT' ? 'Đã hoàn tất' : 'Đang thực hiện' ?></span>
                <a class="btn btn-outline-secondary btn-sm" href="kiem-ke.php?id=<?= (int)$item['id'] ?>">Xem</a>
            </div>
        <?php endforeach; ?>
    </div>
</section>
