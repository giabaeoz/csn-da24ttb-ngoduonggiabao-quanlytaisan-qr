<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/staff-borrowers.php';

$nguoiDung = kiem_tra_quyen(VAI_TRO_NHAN_VIEN);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$deskId = (int)($nguoiDung['phong_truc_id'] ?? 0);
$desk = $deskId ? $db->execute_query('SELECT ten_phong_truc, dang_hoat_dong FROM phong_truc WHERE id = ?', [$deskId])->fetch_assoc() : null;
$canOperate = $desk && (int)$desk['dang_hoat_dong'] === 1;
$error = '';
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$id = filter_var(($isPost ? $_POST : $_GET)['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false && isset(($isPost ? $_POST : $_GET)['id'])) {
    http_response_code(400);
    exit('Mã người mượn không hợp lệ.');
}
$id = $id ? (int)$id : 0;

if ($isPost) {
    if (!csrf_kiem_tra(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        exit('Phiên biểu mẫu không hợp lệ. Hãy tải lại trang.');
    }
    try {
        if (($_POST['action'] ?? '') !== 'update') throw new DomainException('Thao tác không hợp lệ.');
        staff_borrower_update($db, (int)$nguoiDung['id'], $id, $_POST);
        set_flash('success', 'Đã cập nhật thông tin người mượn.');
        header('Location: nguoi-muon.php?tab=all&id=' . $id . '#borrower-profile', true, 303);
        exit;
    } catch (DomainException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    } catch (mysqli_sql_exception $exception) {
        error_log('[STAFF_BORROWER] ' . $exception->getMessage());
        http_response_code(422);
        $error = (int)$exception->getCode() === 1062
            ? 'Mã số trường đã được dùng cho người mượn cùng loại.'
            : 'Chưa lưu được hồ sơ. Hãy tải lại trang và thử lại.';
    }
}

$tabs = ['open' => 'Đang mượn', 'all' => 'Tất cả', 'late' => 'Quá hạn'];
$tab = is_string($_GET['tab'] ?? null) && isset($tabs[$_GET['tab']]) ? $_GET['tab'] : 'open';
$keyword = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$room = is_string($_GET['room'] ?? null) ? mb_substr(trim($_GET['room']), 0, 30) : '';
$requestedPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$page = $requestedPage ? (int)$requestedPage : 1;
$rows = [];
$total = 0;
$pages = 1;
$summary = ['open_count' => 0, 'late_count' => 0, 'asset_count' => 0];
$profile = null;
$loans = [];
$canEditProfile = false;

if ($canOperate) {
    [$rows, $total, $page, $pages] = borrower_list($db, $tab, $keyword, $room, $deskId, $page);
    $summary = staff_borrower_summary($db, $deskId);
    if ($id) {
        $profile = $db->execute_query('SELECT * FROM nguoi_muon WHERE id = ?', [$id])->fetch_assoc();
        if (!$profile) {
            http_response_code(404);
            exit('Không tìm thấy người mượn.');
        }
        $loans = staff_borrower_loans($db, $id, $deskId);
        $canEditProfile = staff_borrower_can_edit($db, $id, $deskId);
        if ($error !== '') {
            foreach (array_keys(STAFF_BORROWER_FIELDS) as $field) {
                if (is_string($_POST[$field] ?? null)) $profile[$field] = $_POST[$field];
            }
        }
    }
}

$filters = ['tab' => $tab, 'q' => $keyword, 'room' => $room, 'page' => $page];
$link = static fn(array $changes): string => 'nguoi-muon.php?' . http_build_query(array_replace($filters, $changes));
$title = 'Người mượn';
$staffPageCss = 'borrowers.css';
require __DIR__ . '/staff-head.php';
?>
<h1 class="mb-2">Người mượn</h1>
<?php if (!$canOperate): ?>
    <div class="alert alert-warning">Tài khoản cần được gán một phòng trực đang hoạt động để tra cứu người mượn.</div>
<?php else: ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
    <div class="borrower-page">
        <?php require __DIR__ . '/views/borrowers.php'; ?>
        <?php require __DIR__ . '/views/borrower-profile.php'; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/staff-foot.php'; ?>
