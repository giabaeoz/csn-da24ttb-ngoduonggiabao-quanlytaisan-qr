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
<div class="sketch-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="m-0">Điều chuyển gần đây</h2>
        <a class="btn-sketch" href="dieu-chuyen.php">Xem tất cả</a>
    </div>
    <div class="table-responsive">
        <table class="sketch-table">
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Thời gian</th>
                    <th>Phòng đi</th>
                    <th>Phòng đến</th>
                    <th>Thiết bị</th>
                    <th style="text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent): ?>
                    <?php foreach ($recent as $item): ?>
                        <tr>
                            <td><strong><?= e($item['ma_dieu_chuyen']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime((string)$item['thoi_gian_dieu_chuyen'])) ?></td>
                            <td><?= e($item['phong_di']) ?></td>
                            <td><?= e($item['phong_den']) ?></td>
                            <td><?= (int)$item['asset_count'] ?></td>
                            <td style="text-align:right;">
                                <a class="btn-sketch" style="padding:4px 14px;" href="dieu-chuyen.php?id=<?= (int)$item['id'] ?>#transfer-detail">Xem phiếu</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td><strong>DC0012</strong></td>
                        <td>29/10/2026 09:00</td>
                        <td>C71.201</td>
                        <td>C71.202</td>
                        <td>1</td>
                        <td style="text-align:right;"><a class="btn-sketch" style="padding:4px 14px;" href="#">Xem phiếu</a></td>
                    </tr>
                    <tr>
                        <td><strong>DC0011</strong></td>
                        <td>28/10/2026 14:15</td>
                        <td>C71.102</td>
                        <td>C71.103</td>
                        <td>2</td>
                        <td style="text-align:right;"><a class="btn-sketch" style="padding:4px 14px;" href="#">Xem phiếu</a></td>
                    </tr>
                    <tr>
                        <td><strong>DC0010</strong></td>
                        <td>28/10/2026 08:30</td>
                        <td>C71.301</td>
                        <td>C71.302</td>
                        <td>1</td>
                        <td style="text-align:right;"><a class="btn-sketch" style="padding:4px 14px;" href="#">Xem phiếu</a></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
