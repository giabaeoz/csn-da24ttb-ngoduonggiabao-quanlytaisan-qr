<?php

declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $rows
 * @var array<string, int> $stats
 * @var array<string, mixed> $filters
 * @var array<int, array<string, mixed>> $types
 * @var array<int, array<string, mixed>> $desks
 * @var array<int, array<string, mixed>> $rooms
 * @var int $id
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var callable $link
 * @var bool|null $canEditAsset
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/assets.php';

if (!isset($rows, $stats, $filters, $types, $desks, $rooms, $total, $page, $pages, $link)) {
    http_response_code(404);
    exit;
}
$canEditAsset = !empty($canEditAsset);
?>
<div class="card-title">
    <div><button type="button" id="print-selected" disabled>In QR đã chọn (0)</button>
        <?php if (!empty($canEditAsset)): ?><a class="button primary" href="tai-san.php?new=1">Thêm tài sản</a><?php endif; ?></div>
</div>
<div class="stats">
    <?php foreach (
        [
            'Tổng tài sản' => $stats['total'],
            'Sẵn sàng' => $stats['ready'],
            'Đang mượn' => $stats['borrowed'],
            'Trạng thái khác' => $stats['total'] - $stats['ready'] - $stats['borrowed'],
        ] as $label => $count
    ): ?>
        <div class="stat"><strong><?= (int)$count ?></strong><?= e($label) ?></div>
    <?php endforeach; ?>
</div>
<section class="panel asset-search-panel">
    <div class="asset-search-layout">
        <div class="asset-qr-search" data-qr-scanner>
            <div>
                <h2>Quét QR để tìm tài sản</h2>
            </div>
            <div class="asset-qr-search-tools">
                <div class="asset-qr-preview" data-qr-preview>Đưa mã QR tài sản vào khung</div>
                <div class="asset-qr-controls">
                    <button type="button" data-qr-start>Bật camera</button>
                    <button type="button" data-qr-stop hidden>Tắt camera</button>
                    <label>Chọn ảnh QR <input type="file" accept="image/png,image/jpeg,image/webp" data-qr-file></label>
                </div>
            </div>
        </div>
        <form method="get" class="asset-filter-form">
            <div class="asset-filter-heading">
                <h2>Tìm kiếm tài sản</h2>
                <small>Tìm nhanh theo mã hoặc thu hẹp kết quả bằng các bộ lọc.</small>
            </div>
            <div class="asset-filters">
                <label class="asset-filter-keyword">Tìm kiếm
                    <input name="q" maxlength="100" value="<?= e($filters['q']) ?>" placeholder="Mã, tên tài sản hoặc mã QR">
                </label>
                <?php foreach (
                    [
                        'type' => ['Nhóm tài sản', $types, 'ten_loai'],
                        'desk' => ['Phòng trực', $desks, 'ten_phong_truc'],
                        'location' => ['Phòng hiện tại', $rooms, 'ma_phong'],
                    ] as $key => [$label, $options, $text]
                ): ?>
                    <label><?= e($label) ?>
                        <select name="<?= $key ?>">
                            <option value="">Tất cả</option>
                            <?php foreach ($options as $option): ?>
                                <option value="<?= (int)$option['id'] ?>" <?= $filters[$key] === (int)$option['id'] ? 'selected' : '' ?>>
                                    <?= e($option[$text]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endforeach; ?>
                <?php foreach (
                    [
                        'state' => ['Trạng thái', ASSET_STATES],
                        'condition' => ['Tình trạng', ASSET_CONDITIONS],
                        'scope' => ['Phạm vi tương thích', ['THEO_PHONG' => 'Theo phòng', 'LINH_HOAT' => 'Linh hoạt']],
                    ] as $key => [$label, $options]
                ): ?>
                    <label><?= e($label) ?>
                        <select name="<?= $key ?>">
                            <option value="">Tất cả</option>
                            <?php foreach ($options as $value => $text): ?>
                                <option value="<?= e($value) ?>" <?= $filters[$key] === $value ? 'selected' : '' ?>><?= e($text) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endforeach; ?>
                <label>Dùng được tại phòng
                    <input name="compatible" maxlength="30" value="<?= e($filters['compatible']) ?>" placeholder="Mã phòng chính xác, ví dụ B21.301">
                </label>
            </div>
            <div class="asset-filter-actions">
                <div><button class="primary">Lọc tài sản</button> <a class="button" href="tai-san.php">Đặt lại</a></div>
            </div>
        </form>
    </div>
</section>
<section class="panel">
    <div class="card-title">
        <div>
            <h2>Danh sách tài sản</h2><small>Mỗi dòng là một tài sản có mã QR riêng.</small>
        </div>
        <span id="selection-count" aria-live="polite">Đã chọn 0 tài sản</span>
    </div>
    <?php if (!empty($canEditAsset)): ?><small class="mobile-scroll-hint">Vuốt bảng sang trái để xem nút Sửa và Xóa.</small><?php endif; ?>
    <div class="scroll">
        <table class="asset-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all" aria-label="Chọn tất cả trên trang"></th>
                    <th>Mã / Tên tài sản</th>
                    <th>Nhóm</th>
                    <th>Thuộc phòng / Vị trí</th>
                    <th>Tình trạng / Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr class="<?= $id === (int)$row['id'] ? 'selected' : '' ?>">
                        <td><input type="checkbox" class="asset-check" value="<?= (int)$row['id'] ?>" aria-label="Chọn <?= e($row['ma_tai_san']) ?>"></td>
                        <td><strong><?= e($row['ma_tai_san']) ?></strong><small><?= e($row['ten_tai_san']) ?></small></td>
                        <td><?= e($row['ten_loai']) ?></td>
                        <td><strong><?= e($row['phong_hien_tai'] ?: ($row['phong_cu'] ?: 'Tại phòng trực')) ?></strong></td>
                        <td>
                            <?= e(ASSET_CONDITIONS[$row['tinh_trang']]) ?>
                            <small><?= e(ASSET_STATES[$row['trang_thai_hien_thi']]) ?></small>
                        </td>
                        <td>
                            <a class="button" href="<?= e($link(['id' => $row['id']])) ?>#asset-profile">Chi tiết</a>
                            <?php if (!empty($canEditAsset)): ?>
                                <a class="button" href="tai-san.php?id=<?= (int)$row['id'] ?>&amp;edit=1">Sửa</a>
                                <form class="asset-inline-form" method="post" action="tai-san.php" onsubmit="return confirm('Xóa vĩnh viễn tài sản chưa có lịch sử nghiệp vụ?')">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                    <button type="submit">Xóa</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="7">Chưa có tài sản phù hợp với bộ lọc.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-title actions">
        <small>Hiển thị <?= count($rows) ?> / <?= $total ?> tài sản</small>
        <div>
            <?php if ($page > 1): ?>
                <a class="button" href="<?= e($link(['page' => $page - 1])) ?>">Trước</a>
            <?php endif; ?>
            <span>Trang <?= $page ?> / <?= $pages ?></span>
            <?php if ($page < $pages): ?>
                <a class="button" href="<?= e($link(['page' => $page + 1])) ?>">Sau</a>
            <?php endif; ?>
        </div>
    </div>
</section>
<div id="qr-print-sheet" aria-hidden="true">
    <?php foreach ($rows as $row): ?>
        <?php $roomTag = $row['phong_hien_tai'] ?: ($row['phong_cu'] ?: 'PHÒNG TRỰC'); ?>
        <div class="qr-label" data-asset-id="<?= (int)$row['id'] ?>">
            <div class="print-room-badge"><?= e($roomTag) ?></div>
            <div class="print-card-body">
                <div class="print-card-left">
                    <div class="print-qr-img" data-qr="<?= e($row['ma_qr']) ?>"></div>
                </div>
                <div class="print-card-right">
                    <div class="print-asset-code"><?= e($row['ma_tai_san']) ?></div>
                    <div class="print-asset-name"><?= e($row['ten_tai_san']) ?></div>
                    <div class="print-desk-name">Phòng trực: <?= e($row['ten_phong_truc']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>