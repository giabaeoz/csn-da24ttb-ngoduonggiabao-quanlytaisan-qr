<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/manager-service.php';

$catalogRole = defined('CATALOG_ROLE') ? CATALOG_ROLE : VAI_TRO_QUAN_LY;
$nguoiDung = kiem_tra_quyen($catalogRole);

$kind = is_string($_GET['kind'] ?? null) ? $_GET['kind'] : '';
$spec = MANAGER_CATALOGS[$kind] ?? null;
if (!$spec) {
    http_response_code(404);
    exit('Không tìm thấy danh mục.');
}

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = '';
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$source = $post ? $_POST : $_GET;

try {
    $id = manager_id($source['id'] ?? null, true);
} catch (DomainException $ex) {
    http_response_code(400);
    exit(e($ex->getMessage()));
}

$action = $post && is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

if ($post) {
    if (!csrf_kiem_tra(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        exit('Phiên biểu mẫu đã hết hạn.');
    }
    try {
        $db->begin_transaction();
        manager_check_actor($db, (int)$nguoiDung['id'], [$catalogRole]);
        $saved = manager_catalog_save($db, $kind, $action, $id, $_POST);
        $db->commit();
        set_flash('success', match ($action) {
            'create' => 'Đã thêm dữ liệu thành công.',
            'edit' => 'Đã cập nhật dữ liệu thành công.',
            'status' => 'Đã cập nhật trạng thái thành công.',
            'delete' => 'Đã xóa bản ghi không còn được sử dụng.',
            default => 'Đã lưu dữ liệu thành công.',
        });
        header('Location: danh-muc.php?' . http_build_query(['kind' => $kind]), true, 303);
        exit;
    } catch (DomainException | mysqli_sql_exception $ex) {
        $db->rollback();
        $error = $ex instanceof DomainException ? $ex->getMessage() : match ((int)$ex->getCode()) {
            1062 => 'Mã này đã tồn tại.',
            1451 => 'Bản ghi đã được dùng trong hệ thống, không thể xóa.',
            default => 'Chưa lưu được thay đổi. Vui lòng kiểm tra dữ liệu.',
        };
        http_response_code(422);
    }
}

$table = $spec['table'];
$codeCol = $spec['code'];
$nameCol = $spec['name'];
$extraCol = $spec['extra'];

$q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$where = '';
$params = [];

if ($q !== '') {
    $where = " WHERE $codeCol LIKE ? ESCAPE '!' OR $nameCol LIKE ? ESCAPE '!'";
    $pattern = '%' . strtr($q, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    $params = [$pattern, $pattern];
}

$total = (int)$db->execute_query("SELECT COUNT(*) FROM $table$where", $params)->fetch_row()[0];
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$pages = max(1, (int)ceil($total / 20));
$page = min($page === false ? 1 : (int)$page, $pages);

$rows = $db->execute_query("SELECT * FROM $table$where ORDER BY $codeCol, id LIMIT 20 OFFSET ?", [...$params, ($page - 1) * 20])->fetch_all(MYSQLI_ASSOC);
$item = $id ? $db->execute_query("SELECT * FROM $table WHERE id = ?", [$id])->fetch_assoc() : null;

if ($id && !$item) {
    http_response_code(404);
    exit('Không tìm thấy bản ghi.');
}

$showForm = $id || isset($_GET['new']) || ($post && in_array($action, ['create', 'edit'], true));
$data = $post && in_array($action, ['create', 'edit'], true) ? $_POST : [
    'code' => $item[$codeCol] ?? '',
    'name' => $item[$nameCol] ?? '',
    'extra' => $item[$extraCol] ?? '',
    'tang' => $item['tang'] ?? '',
];

$title = $spec['title'];
require $catalogRole === VAI_TRO_QUAN_TRI ? __DIR__ . '/../admin/admin-head.php' : __DIR__ . '/manager-head.php';
?>

<?php if ($error): ?>
    <p class="manager-error" role="alert"><?= e($error) ?></p>
<?php endif; ?>

<section class="panel">
    <form method="get" class="row-tools">
        <input type="hidden" name="kind" value="<?= e($kind) ?>">
        <label>Tìm kiếm
            <input name="q" value="<?= e($q) ?>" maxlength="100" placeholder="Mã hoặc tên">
        </label>
        <button class="primary" type="submit">Tìm</button>
        <a class="button" href="danh-muc.php?kind=<?= e($kind) ?>">Đặt lại</a>
        <a class="button primary" href="danh-muc.php?kind=<?= e($kind) ?>&amp;new=1">Thêm <?= e(mb_strtolower($title)) ?></a>
    </form>
</section>

<section class="panel">
    <div class="card-title">
        <h2>Danh sách <?= e(mb_strtolower($title)) ?></h2>
        <small><?= $total ?> bản ghi</small>
    </div>
    <div class="scroll">
        <table class="manager-table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên</th>
                    <th><?= e($spec['extra_label']) ?></th>
                    <?php if ($kind === 'phong-hoc'): ?>
                        <th>Tầng</th>
                    <?php endif; ?>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr class="<?= $id === (int)$row['id'] ? 'selected' : '' ?>">
                        <td><?= e($row[$codeCol]) ?></td>
                        <td><?= e($row[$nameCol]) ?></td>
                        <td><?= e($row[$extraCol] ?? '—') ?></td>
                        <?php if ($kind === 'phong-hoc'): ?>
                            <td><?= e($row['tang'] ?? '—') ?></td>
                        <?php endif; ?>
                        <td><?= (int)$row['dang_hoat_dong'] ? 'Hoạt động' : 'Ngừng hoạt động' ?></td>
                        <td>
                            <a class="button" href="?<?= e(http_build_query(['kind' => $kind, 'q' => $q, 'page' => $page, 'id' => $row['id']])) ?>">Sửa</a>
                            <?php if ($kind === 'phong-hoc'): ?>
                                <button type="button" class="button" onclick="showRoomQr('<?= e('TVU:PHONG:' . $row[$codeCol]) ?>', '<?= e($row[$codeCol]) ?>', '<?= e($row[$nameCol]) ?>')">QR</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="<?= $kind === 'phong-hoc' ? 6 : 5 ?>">Chưa có bản ghi phù hợp.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-title actions">
        <small>Trang <?= $page ?> / <?= $pages ?></small>
        <div>
            <?php if ($page > 1): ?>
                <a class="button" href="?<?= e(http_build_query(['kind' => $kind, 'q' => $q, 'page' => $page - 1])) ?>">Trước</a>
            <?php endif; ?>
            <?php if ($page < $pages): ?>
                <a class="button" href="?<?= e(http_build_query(['kind' => $kind, 'q' => $q, 'page' => $page + 1])) ?>">Sau</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($showForm): ?>
<div class="modal fade" id="catalogModal" tabindex="-1" aria-labelledby="catalogModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="catalogModalLabel"><?= $id ? 'Sửa' : 'Thêm' ?> <?= e(mb_strtolower($title)) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng" onclick="window.location.href='danh-muc.php?kind=<?= e($kind) ?>'"></button>
            </div>
            <div class="modal-body">
                <form method="post" id="catalogForm" action="danh-muc.php?kind=<?= e($kind) ?><?= $id ? '&amp;id=' . $id : '' ?>" class="manager-form">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id" value="<?= $id ?? '' ?>">
                    <input type="hidden" name="action" value="<?= $id ? 'edit' : 'create' ?>">
                    <label class="wide">Mã
                        <input name="code" required maxlength="<?= $spec['code_max'] ?>" value="<?= e(is_string($data['code'] ?? null) ? $data['code'] : '') ?>">
                    </label>
                    <label class="wide">Tên
                        <input name="name" required maxlength="<?= $spec['name_max'] ?>" value="<?= e(is_string($data['name'] ?? null) ? $data['name'] : '') ?>">
                    </label>
                    <label class="wide"><?= e($spec['extra_label']) ?>
                        <input name="extra" maxlength="<?= $spec['extra_max'] ?>" value="<?= e(is_string($data['extra'] ?? null) ? $data['extra'] : '') ?>">
                    </label>
                    <?php if ($kind === 'phong-hoc'): ?>
                        <label class="wide">Tầng
                            <input name="tang" maxlength="20" value="<?= e(is_string($data['tang'] ?? null) ? $data['tang'] : '') ?>">
                        </label>
                    <?php endif; ?>
                </form>

                <?php if ($id): ?>
                    <hr class="my-3">
                    <div class="manager-actions">
                        <form method="post" action="danh-muc.php?kind=<?= e($kind) ?>&amp;id=<?= $id ?>">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="action" value="status">
                            <button type="submit"><?= (int)$item['dang_hoat_dong'] ? 'Ngừng hoạt động' : 'Kích hoạt lại' ?></button>
                        </form>
                        <form method="post" action="danh-muc.php?kind=<?= e($kind) ?>&amp;id=<?= $id ?>" onsubmit="return confirm('Xóa vĩnh viễn bản ghi chưa được sử dụng?')">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit">Xóa nếu chưa sử dụng</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <a class="button" href="danh-muc.php?kind=<?= e($kind) ?>" data-bs-dismiss="modal">Hủy</a>
                <button type="submit" form="catalogForm" class="primary">Lưu thông tin</button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('catalogModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        var myModal = new bootstrap.Modal(modalEl);
        myModal.show();
        modalEl.addEventListener('hidden.bs.modal', function () {
            if (window.location.search.includes('new=1') || window.location.search.includes('id=')) {
                window.location.href = 'danh-muc.php?kind=<?= e($kind) ?>';
            }
        });
    }
});
</script>
<?php endif; ?>
<?php if ($kind === 'phong-hoc'): ?>
<div class="modal fade" id="roomQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 380px;">
        <div class="modal-content border">
            <div class="modal-header py-3">
                <h5 class="modal-title fw-bold" id="roomQrModalTitle">Mã QR phòng học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div class="p-3 border rounded mx-auto mb-3" style="max-width: 260px; background: #fff;" id="roomQrPrintArea">
                    <div id="roomQrCodeDisplay" style="font-size: 18px; font-weight: 800; border-bottom: 2px solid #000; padding-bottom: 4px; margin-bottom: 10px;"></div>
                    <div id="roomQrBox" class="mx-auto mb-2" style="width: 160px; height: 160px; display: flex; align-items: center; justify-content: center;"></div>
                    <div id="roomQrNameDisplay" style="font-size: 13px; font-weight: 600; color: #333;"></div>
                    <div style="font-size: 11px; color: #666; margin-top: 4px;">Dán tại cửa phòng để quét mượn / kiểm kê</div>
                </div>
                <button type="button" class="btn btn-sketch btn-sm" onclick="printRoomQr()">In mã QR phòng</button>
            </div>
        </div>
    </div>
</div>
<script src="../vendor/qrcode.js"></script>
<script src="../vendor/qrcode_UTF8.js"></script>
<script>
function showRoomQr(payload, code, name) {
    document.getElementById('roomQrModalTitle').textContent = 'Mã QR: ' + code;
    document.getElementById('roomQrCodeDisplay').textContent = code;
    document.getElementById('roomQrNameDisplay').textContent = name;
    var box = document.getElementById('roomQrBox');
    box.innerHTML = '';
    if (typeof qrcode === 'function') {
        var qr = qrcode(0, 'M');
        qr.addData(payload);
        qr.make();
        box.innerHTML = qr.createSvgTag({cellSize: 4, margin: 0});
    }
    var modalEl = document.getElementById('roomQrModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        new bootstrap.Modal(modalEl).show();
    }
}
function printRoomQr() {
    var content = document.getElementById('roomQrPrintArea').innerHTML;
    var win = window.open('', '_blank', 'width=500,height=500');
    win.document.write('<!doctype html><html><head><title>In QR Phòng</title><style>body{text-align:center;padding:20mm;font-family:sans-serif;}@page{size:A6;margin:10mm;}</style></head><body>' + content + '</body></html>');
    win.document.close();
    win.focus();
    setTimeout(function() { win.print(); win.close(); }, 300);
}
</script>
<?php endif; ?>
<?php require __DIR__ . '/../shared/views/end.php'; ?>
