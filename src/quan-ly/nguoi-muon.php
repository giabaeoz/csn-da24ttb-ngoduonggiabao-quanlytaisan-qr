<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/borrowers.php';

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$nguoiDung = kiem_tra_quyen(VAI_TRO_QUAN_LY);
$canEdit = true;
$desk = null;

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$id = filter_var(($isPost ? $_POST : $_GET)['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
if ($id === false) {
    http_response_code(400);
    exit('Mã hồ sơ không hợp lệ.');
}

$error = '';
$action = borrower_text($_POST, 'action');
if ($isPost) {
    if (!csrf_kiem_tra(borrower_text($_POST, 'csrf_token'))) {
        http_response_code(403);
        exit('Biểu mẫu đã hết hạn. Vui lòng tải lại trang.');
    }
    try {
        $db->begin_transaction();
        $manager = $db->execute_query("SELECT id FROM nhan_vien WHERE id = ?
            AND vai_tro = 'QUAN_LY' AND dang_hoat_dong = 1 FOR SHARE", [$nguoiDung['id']])->fetch_row();
        if (!$manager) {
            $db->rollback();
            http_response_code(403);
            exit('Quyền quản lý đã thay đổi.');
        }
        $id = borrower_write($db, $action, $id, $_POST);
        $db->commit();
        set_flash('success', $action === 'delete' ? 'Đã xóa hồ sơ chưa có phiếu mượn.' : 'Đã lưu hồ sơ người mượn.');
        header('Location: nguoi-muon.php?tab=all' . ($id ? '&id=' . $id : ''), true, 303);
        exit;
    } catch (DomainException | mysqli_sql_exception $exception) {
        $db->rollback();
        $error = $exception instanceof DomainException ? $exception->getMessage() : match ($exception->getCode()) {
            1062 => 'Mã người mượn hoặc mã số trường của loại người mượn này đã tồn tại.',
            1451 => 'Người mượn đã có phiếu mượn. Hồ sơ được giữ lại để bảo toàn lịch sử.',
            default => 'Không lưu được thay đổi. Vui lòng thử lại.',
        };
        http_response_code(422);
    }
}

$tabs = ['open' => 'Đang mượn', 'all' => 'Tất cả', 'late' => 'Quá hạn'];
$tab = borrower_text($_GET, 'tab');
$tab = isset($tabs[$tab]) ? $tab : 'open';
$keyword = mb_substr(borrower_text($_GET, 'q'), 0, 100);
$room = mb_substr(borrower_text($_GET, 'room'), 0, 30);
$page = (int) filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$new = $canEdit && !$id && (isset($_GET['new']) || $action === 'create');
try {
    [$rows, $total, $page, $pages] = borrower_list($db, $tab, $keyword, $room, $desk, $page);
    $profile = $id ? $db->execute_query('SELECT * FROM nguoi_muon WHERE id = ?', [$id])->fetch_assoc() : [];
    if ($id && !$profile) {
        http_response_code(404);
        exit('Không tìm thấy hồ sơ người mượn.');
    }
    $history = $id ? borrower_history($db, $id, $desk) : null;
} catch (mysqli_sql_exception $exception) {
    http_response_code(503);
    exit('Chưa tải được thông tin người mượn.');
}
if ($error && in_array($action, ['create', 'edit'], true)) {
    foreach ([...array_keys(BORROWER_FIELDS), 'loai_nguoi_muon'] as $field) {
        $profile[$field] = borrower_text($_POST, $field);
    }
}

$filters = ['tab' => $tab, 'q' => $keyword, 'room' => $room, 'page' => $page];
$link = static fn(array $changes): string => '?' . http_build_query(array_replace($filters, $changes));
$title = 'Người mượn';
require __DIR__ . '/manager-head.php';
require __DIR__ . '/views/borrowers-list.php';
if ($id || $new) {
    require __DIR__ . '/views/borrower-profile.php';
}
require __DIR__ . '/../shared/views/end.php';
