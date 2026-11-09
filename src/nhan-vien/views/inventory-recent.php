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
<div class="sketch-card mb-4">
    <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px;">
        <h2 style="font-size: 16px; font-weight: 700; margin: 0; color: #111;">Đợt kiểm kê gần đây</h2>
        <a href="kiem-ke.php" style="font-size: 13px; color: #444; text-decoration: none;">Xem tất cả</a>
    </div>
    <div class="sketch-table-wrap">
        <table class="table" style="margin: 0;">
            <tbody>
                <?php if ($rounds): ?>
                    <?php foreach (array_slice($rounds, 0, 5) as $item): ?>
                        <tr>
                            <td style="padding: 12px 14px; font-size: 13px;">
                                <strong><?= e($item['ma_dot_kiem_ke']) ?></strong> • <?= e(INVENTORY_TYPES[$item['loai_kiem_ke']] ?? $item['loai_kiem_ke']) ?> • <?= date('d/m/Y', strtotime((string)$item['thoi_gian_bat_dau'])) ?>
                            </td>
                            <td style="padding: 12px 14px; font-size: 13px; color: #444;"><?= e($item['ten_phong_truc']) ?></td>
                            <td style="padding: 12px 14px; font-size: 13px; color: #444;">
                                <?= match ($item['trang_thai']) {
                                    'HOAN_TAT' => 'Đã hoàn tất',
                                    'DA_HUY' => 'Đã hủy',
                                    default => 'Đang thực hiện',
                                } ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: right;">
                                <a href="kiem-ke.php?id=<?= (int)$item['id'] ?>" class="btn-sketch" style="padding: 4px 14px; font-size: 12px; text-decoration: none;">Xem</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td style="padding: 12px 14px; font-size: 13px;">KK0020 • Cuối buổi • 28/10/2026</td>
                        <td style="padding: 12px 14px; font-size: 13px; color: #444;">Phòng C71.102</td>
                        <td style="padding: 12px 14px; font-size: 13px; color: #444;">Đã hoàn tất</td>
                        <td style="padding: 12px 14px; text-align: right;"><a href="#" class="btn-sketch" style="padding: 4px 14px; font-size: 12px;">Xem</a></td>
                    </tr>
                    <tr>
                        <td style="padding: 12px 14px; font-size: 13px;">KK0019 • Định kỳ • 20/10/2026</td>
                        <td style="padding: 12px 14px; font-size: 13px; color: #444;">Phòng C71.201</td>
                        <td style="padding: 12px 14px; font-size: 13px; color: #444;">Đã hoàn tất</td>
                        <td style="padding: 12px 14px; text-align: right;"><a href="#" class="btn-sketch" style="padding: 4px 14px; font-size: 12px;">Xem</a></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
