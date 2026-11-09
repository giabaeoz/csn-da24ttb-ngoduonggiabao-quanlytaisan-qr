<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $rows
 * @var array<string, int> $summary
 * @var array<string, string> $tabs
 * @var string $tab
 * @var string $keyword
 * @var string $room
 * @var int $total
 * @var int $id
 * @var int $page
 * @var int $pages
 * @var callable $link
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($rows, $summary, $tabs, $link)) {
    http_response_code(404);
    exit;
}
$tab = isset($tab) ? (string)$tab : 'open';
$keyword = isset($keyword) ? (string)$keyword : '';
$room = isset($room) ? (string)$room : '';
$total = isset($total) ? (int)$total : 0;
$id = isset($id) ? (int)$id : 0;
$page = isset($page) ? (int)$page : 1;
$pages = isset($pages) ? (int)$pages : 1;
?>
<nav class="borrower-tabs" aria-label="Lọc người mượn">
    <?php foreach ($tabs as $key => $label): ?>
        <?php $count = $key === 'open' ? (int)$summary['open_count'] : ($key === 'late' ? (int)$summary['late_count'] : null); ?>
        <a href="<?= e($link(['tab' => $key, 'page' => 1, 'id' => null])) ?>"
           class="tab-item <?= $tab === $key ? 'active' : '' ?>"
           <?= $tab === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?><?= $count === null ? '' : ' (' . $count . ')' ?></a>
    <?php endforeach; ?>
</nav>

<section class="borrower-filter-card">
    <form method="get" class="borrower-filter-row">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <label for="borrower-search">Tìm người mượn
            <input id="borrower-search" name="q" class="form-control" maxlength="100"
                value="<?= e($keyword) ?>" placeholder="Mã số / họ tên / số điện thoại">
        </label>
        <label for="borrower-room">Phòng học đang sử dụng
            <input id="borrower-room" name="room" class="form-control" maxlength="30"
                value="<?= e($room) ?>" placeholder="Nhập mã phòng, ví dụ C71.102">
        </label>
        <button class="btn-sketch-gray" type="submit">Tìm</button>
        <a class="btn-sketch" href="nguoi-muon.php?tab=<?= e($tab) ?>">Xóa lọc</a>
    </form>
</section>

<section class="borrower-list-card">
    <div class="head">
        <h2><?= e(['open' => 'Người đang mượn tài sản', 'all' => 'Danh sách người mượn', 'late' => 'Người mượn quá hạn'][$tab]) ?></h2>
        <div class="meta"><?= (int)$total ?> người<?= $tab === 'open' ? ' • ' . (int)$summary['asset_count'] . ' thiết bị chưa trả' : '' ?></div>
    </div>
    <div class="table-responsive">
        <table class="borrower-table">
            <thead>
                <tr>
                    <th>Mã số / Họ tên</th>
                    <th>Lớp / đơn vị</th>
                    <th>Phòng học</th>
                    <th>Điện thoại</th>
                    <th>Chưa trả</th>
                    <th>Hạn trả</th>
                    <th style="text-align:right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr <?= $id === (int)$row['id'] ? 'class="table-active"' : '' ?>>
                        <td>
                            <div style="font-size:12px;color:#666;"><?= e($row['ma_so_truong']) ?></div>
                            <strong><?= e($row['ho_ten']) ?></strong>
                        </td>
                        <td><?= e($row['lop_don_vi'] ?? '—') ?></td>
                        <td><?= e($row['rooms'] ?? '—') ?></td>
                        <td><?= e($row['so_dien_thoai'] ?? '—') ?></td>
                        <td><?= (int)$row['open_count'] ?></td>
                        <td>
                            <?= e($row['due'] ?? '—') ?>
                            <?php if ($row['due']): ?>
                                <small class="d-block text-muted"><?= $row['overdue'] ? 'Quá hạn' : 'Trong hạn' ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <a class="btn-sketch" style="padding:4px 14px;" href="<?= e($link(['id' => $row['id']])) ?>#borrower-profile">Xem</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($total === 0): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Không có người mượn phù hợp.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div class="borrower-pagination" style="display:flex;justify-content:flex-end;align-items:center;gap:10px;margin-top:16px;">
            <?php if ($page > 1): ?>
                <a class="btn-sketch" style="padding:4px 12px;" href="<?= e($link(['page' => $page - 1])) ?>">Trang trước</a>
            <?php endif; ?>
            <span style="font-size:13px;color:#666;">Trang <?= (int)$page ?> / <?= (int)$pages ?></span>
            <?php if ($page < $pages): ?>
                <a class="btn-sketch" style="padding:4px 12px;" href="<?= e($link(['page' => $page + 1])) ?>">Trang sau</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
