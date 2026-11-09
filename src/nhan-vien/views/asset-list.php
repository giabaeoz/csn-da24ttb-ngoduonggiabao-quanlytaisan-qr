<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $rows
 * @var array<string, int> $stats
 * @var array<string, mixed> $filters
 * @var array<int, array<string, mixed>> $types
 * @var array<int, array<string, mixed>> $rooms
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var callable $link
 * @var array<string, mixed> $desk
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/assets.php';

$fromIndex = $total > 0 ? ($page - 1) * 20 + 1 : 0;
$toIndex = min($page * 20, $total);
?>

<div class="asset-page-header">
    <div>
        <h1 class="asset-title">Tài sản</h1>
    </div>
    <div class="asset-header-actions">
        <button type="button" id="btn-print-selected" class="btn btn-sketch" disabled>
            In QR đã chọn (0)
        </button>
        <a href="tai-san.php?new=1" class="btn btn-sketch-primary">
            Thêm tài sản
        </a>
    </div>
</div>

<div class="row g-3 mb-4 asset-stat-grid">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$stats['total'] ?></div>
            <div class="stat-label">Tổng tài sản</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$stats['ready'] ?></div>
            <div class="stat-label">Có thể mượn</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$stats['borrowed'] ?></div>
            <div class="stat-label">Đang mượn</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-number"><?= sprintf('%02d', (int)$stats['other']) ?></div>
            <div class="stat-label">Trạng thái khác</div>
        </div>
    </div>
</div>

<section class="asset-filter-card mb-4">
    <form method="get" id="asset-filter-form" action="tai-san.php">
        <div class="row g-3 align-items-end mb-3">
            <div class="col-12 col-md-4">
                <label for="filter-q" class="form-label-sketch">Tìm kiếm</label>
                <div class="input-group">
                    <input type="text" id="filter-q" name="q" class="form-control form-control-sketch"
                           value="<?= e($filters['q']) ?>" placeholder="Tìm mã hoặc tên tài sản">
                    <button type="button" class="btn btn-sketch" data-bs-toggle="collapse"
                            data-bs-target="#qrScannerCollapse" aria-expanded="false" title="Quét camera QR">
                        📷 QR
                    </button>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <label for="filter-type" class="form-label-sketch">Nhóm tài sản</label>
                <select id="filter-type" name="type" class="form-select form-select-sketch">
                    <option value="">Tất cả nhóm</option>
                    <?php foreach ($types as $type): ?>
                        <option value="<?= (int)$type['id'] ?>" <?= $filters['type'] === (int)$type['id'] ? 'selected' : '' ?>>
                            <?= e($type['ten_loai']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3">
                <label for="filter-status" class="form-label-sketch">Trạng thái</label>
                <select id="filter-status" name="status" class="form-select form-select-sketch">
                    <option value="">Tất cả trạng thái</option>
                    <option value="CO_THE_MUON" <?= $filters['status'] === 'CO_THE_MUON' ? 'selected' : '' ?>>Có thể mượn</option>
                    <option value="DANG_MUON" <?= $filters['status'] === 'DANG_MUON' ? 'selected' : '' ?>>Đang mượn</option>
                    <option value="BAO_TRI" <?= $filters['status'] === 'BAO_TRI' ? 'selected' : '' ?>>Bảo trì</option>
                    <option value="HONG" <?= $filters['status'] === 'HONG' ? 'selected' : '' ?>>Hỏng</option>
                    <option value="NGUNG_SU_DUNG" <?= $filters['status'] === 'NGUNG_SU_DUNG' ? 'selected' : '' ?>>Ngừng sử dụng</option>
                </select>
            </div>

            <div class="col-12 col-md-2">
                <label for="filter-location" class="form-label-sketch">Vị trí</label>
                <select id="filter-location" name="location" class="form-select form-select-sketch">
                    <option value="">Tất cả vị trí</option>
                    <option value="PHONG_TRUC" <?= $filters['location'] === 'PHONG_TRUC' ? 'selected' : '' ?>>Phòng trực</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int)$room['id'] ?>" <?= (string)$filters['location'] === (string)$room['id'] ? 'selected' : '' ?>>
                            <?= e($room['ma_phong']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="collapse mb-3" id="qrScannerCollapse" data-qr-scanner>
            <div class="card p-3 bg-light border">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <div class="asset-camera-box" data-qr-preview>
                            <span>Đưa mã QR tài sản vào khung</span>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h6 class="fw-bold mb-1">Quét mã QR bằng Camera hoặc tệp ảnh</h6>
                        <small class="text-muted d-block mb-3">Hệ thống sẽ tự nhận diện mã thiết bị và tìm kiếm ngay.</small>
                        <div class="d-flex gap-2 mb-2">
                            <button type="button" class="btn btn-sketch-primary btn-sm" data-qr-start>Bật Camera</button>
                            <button type="button" class="btn btn-sketch btn-sm" data-qr-stop hidden>Tắt Camera</button>
                            <label class="btn btn-sketch btn-sm mb-0">
                                Chọn ảnh QR
                                <input type="file" accept="image/png,image/jpeg,image/webp" data-qr-file hidden>
                            </label>
                        </div>
                        <small class="text-muted" data-qr-status>Camera sẵn sàng</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-sketch">Lọc tài sản</button>
                <a href="tai-san.php" class="btn btn-sketch">Đặt lại</a>
            </div>
            <div class="text-muted small">
                Hiển thị <?= count($rows) ?> / <?= $total ?> tài sản
            </div>
        </div>
    </form>
</section>

<section class="asset-table-card">
    <div class="asset-card-heading">
        <div>
            <h2 class="asset-card-title">Danh sách tài sản</h2>
            <small class="text-muted">Mỗi dòng là một tài sản có mã QR riêng.</small>
        </div>
        <div>
            <span class="text-muted small" id="selection-count">Đã chọn 0 tài sản</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table sketch-table align-middle">
            <thead>
                <tr>
                    <th style="width: 38px;" class="text-center">
                        <input type="checkbox" id="select-all-assets" aria-label="Chọn tất cả">
                    </th>
                    <th>Mã tài sản</th>
                    <th>Tên tài sản</th>
                    <th>Nhóm</th>
                    <th>Vị trí</th>
                    <th>Trạng thái</th>
                    <th class="text-end" style="width: 100px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $statusLabel = match (true) {
                        $row['trang_thai_hien_thi'] === 'DANG_MUON' => 'Đang mượn',
                        $row['tinh_trang'] === 'HU_HONG' || $row['tinh_trang'] === 'MAT' => 'Hỏng',
                        $row['trang_thai_hien_thi'] === 'BAO_TRI' => 'Bảo trì',
                        $row['trang_thai_hien_thi'] === 'NGUNG_SU_DUNG' => 'Ngừng sử dụng',
                        default => 'Có thể mượn',
                    };

                    $locationLabel = !empty($row['phong_hien_tai'])
                        ? 'P. ' . $row['phong_hien_tai']
                        : 'Phòng trực';
                    ?>
                    <tr class="asset-row" data-id="<?= (int)$row['id'] ?>">
                        <td class="text-center">
                            <input type="checkbox" class="asset-check-item" value="<?= (int)$row['id'] ?>"
                                   aria-label="Chọn <?= e($row['ma_tai_san']) ?>">
                        </td>
                        <td>
                            <strong class="font-monospace text-dark"><?= e($row['ma_tai_san']) ?></strong>
                        </td>
                        <td>
                            <?= e($row['ten_tai_san']) ?>
                        </td>
                        <td>
                            <?= e($row['ten_loai']) ?>
                        </td>
                        <td>
                            <?= e($locationLabel) ?>
                        </td>
                        <td>
                            <span class="sketch-status-text <?= $statusLabel === 'Đang mượn' ? 'text-primary' : ($statusLabel === 'Hỏng' ? 'text-danger' : ($statusLabel === 'Có thể mượn' ? 'text-dark' : 'text-muted')) ?>">
                                <?= e($statusLabel) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="<?= e($link(['id' => $row['id']])) ?>" class="btn btn-sketch btn-sm">
                                Chi tiết
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            Không tìm thấy tài sản nào phù hợp.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="asset-card-footer">
        <div class="text-muted small">
            <?= $fromIndex ?>–<?= $toIndex ?> trong <?= $total ?> tài sản
        </div>
        <div class="d-flex gap-1 align-items-center">
            <?php if ($page > 1): ?>
                <a href="<?= e($link(['page' => $page - 1])) ?>" class="btn btn-sketch btn-sm">Trước</a>
            <?php else: ?>
                <button type="button" class="btn btn-sketch btn-sm" disabled>Trước</button>
            <?php endif; ?>

            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($pages, $page + 2);
            for ($i = $startPage; $i <= $endPage; $i++):
            ?>
                <a href="<?= e($link(['page' => $i])) ?>"
                   class="btn btn-sketch btn-sm <?= $i === $page ? 'active-page' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($endPage < $pages): ?>
                <span class="px-1 text-muted">…</span>
                <a href="<?= e($link(['page' => $pages])) ?>" class="btn btn-sketch btn-sm"><?= $pages ?></a>
            <?php endif; ?>

            <?php if ($page < $pages): ?>
                <a href="<?= e($link(['page' => $page + 1])) ?>" class="btn btn-sketch btn-sm">Sau</a>
            <?php else: ?>
                <button type="button" class="btn btn-sketch btn-sm" disabled>Sau</button>
            <?php endif; ?>
        </div>
    </div>
</section>

<div id="staff-print-sheet" aria-hidden="true">
    <?php foreach ($rows as $row): ?>
        <?php $roomTag = $row['phong_hien_tai'] ?: ($row['phong_cu'] ?: 'PHÒNG TRỰC'); ?>
        <div class="staff-qr-print-card" data-asset-id="<?= (int)$row['id'] ?>">
            <div class="print-room-badge"><?= e($roomTag) ?></div>
            <div class="print-card-body">
                <div class="print-card-left">
                    <div class="print-qr-img" data-qr="<?= e($row['ma_qr']) ?>"></div>
                </div>
                <div class="print-card-right">
                    <div class="print-asset-code"><?= e($row['ma_tai_san']) ?></div>
                    <div class="print-asset-name"><?= e($row['ten_tai_san']) ?></div>
                    <div class="print-desk-name">Phòng trực: <?= e($desk['ten_phong_truc']) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
