<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $rows
 * @var array<string, string> $tabs
 * @var string $tab
 * @var string $keyword
 * @var string $room
 * @var int $total
 * @var bool $canEdit
 * @var int $id
 * @var int $page
 * @var int $pages
 * @var string $error
 * @var callable $link
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($rows, $tabs, $tab, $keyword, $room, $total, $link)) {
    http_response_code(404);
    exit;
}

$canEdit = !empty($canEdit);
$id = isset($id) ? (int)$id : 0;
$page = isset($page) ? (int)$page : 1;
$pages = isset($pages) ? (int)$pages : 1;
$error = isset($error) ? (string)$error : '';
?>
<div class="borrower-tabs" aria-label="Lọc người mượn">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="button <?= $tab === $key ? 'primary' : '' ?>"
           href="<?= e($link(['tab' => $key, 'page' => 1])) ?>">
            <?= e($label) ?>
        </a>
    <?php endforeach; ?>
</div>

<section class="panel">
    <form method="get" class="row-tools">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <label>Tìm người mượn
            <input name="q" maxlength="100" placeholder="Mã số / họ tên / số điện thoại" value="<?= e($keyword) ?>">
        </label>
        <label>Phòng học đang sử dụng
            <input name="room" maxlength="30" placeholder="Ví dụ C71.102" value="<?= e($room) ?>">
        </label>
        <button type="submit">Tìm</button>
        <a class="button" href="?tab=<?= e($tab) ?>">Xóa lọc</a>
    </form>
</section>

<section class="panel">
    <div class="card-title">
        <h2><?= e(['open' => 'Người đang mượn tài sản', 'late' => 'Người mượn quá hạn', 'all' => 'Danh sách người mượn'][$tab]) ?></h2>
        <span><?= $total ?> người</span>
        <?php if ($canEdit): ?>
            <a class="button primary" href="?tab=all&amp;new=1">Thêm người mượn</a>
        <?php endif; ?>
    </div>
    <div class="scroll">
        <table>
            <thead>
                <tr>
                    <th>Mã số / Họ tên</th>
                    <th>Lớp / đơn vị</th>
                    <th>Phòng học</th>
                    <th>Điện thoại</th>
                    <th>Chưa trả</th>
                    <th>Hạn trả gần nhất</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr class="<?= (int)$row['id'] === $id ? 'selected' : '' ?>">
                        <td>
                            <small><?= e($row['ma_so_truong']) ?></small>
                            <?= e($row['ho_ten']) ?>
                        </td>
                        <td><?= e($row['lop_don_vi'] ?? '—') ?></td>
                        <td><?= e($row['rooms'] ?? '—') ?></td>
                        <td><?= e($row['so_dien_thoai'] ?? '—') ?></td>
                        <td><?= (int)$row['open_count'] ?></td>
                        <td>
                            <?= e($row['due'] ?? '—') ?>
                            <?php if ($row['due']): ?>
                                <small><?= $row['overdue'] ? 'Quá hạn' : 'Trong hạn' ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="button" href="<?= e($link(['id' => $row['id']])) ?>">Xem</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$total): ?>
                    <tr>
                        <td colspan="7">Không có người mượn phù hợp.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div class="actions">
            <?php if ($page > 1): ?>
                <a href="<?= e($link(['page' => $page - 1])) ?>">Trang trước</a>
            <?php endif; ?>
            Trang <?= $page ?> / <?= $pages ?>
            <?php if ($page < $pages): ?>
                <a href="<?= e($link(['page' => $page + 1])) ?>">Trang sau</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($error !== ''): ?>
    <p role="alert" class="error"><?= e($error) ?></p>
<?php endif; ?>
