<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/rbac.php';
require_once __DIR__.'/accounts-service.php';
require_once __DIR__.'/../includes/account-list.php';
$nguoiDung = kiem_tra_quyen(VAI_TRO_QUAN_TRI);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$post = $_SERVER['REQUEST_METHOD'] === 'POST';
$source = $post ? $_POST : $_GET;
$id = filter_var($source['id'] ?? 0, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
if ($id === false) { http_response_code(400); exit('Mã tài khoản không hợp lệ.'); }
$action = $post && is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
$error = '';
if ($post) {
    if (!csrf_kiem_tra(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403); exit('Phiên biểu mẫu đã hết hạn. Vui lòng tải lại trang.');
    }
    try {
        $db->begin_transaction();
        if ($action === 'create' && $id !== 0) { throw new DomainException('Mã tạo mới không hợp lệ.'); }
        account_save($db, (int)$nguoiDung['id'], $action, $id, $_POST);
        if ($action === 'create') { $id = (int)$db->insert_id; }
        $db->commit();
        set_flash('success', match($action) {
            'create'=>'Đã tạo tài khoản.', 'password'=>'Đã đặt lại mật khẩu.',
            'status'=>'Đã cập nhật trạng thái tài khoản.', default=>'Đã cập nhật tài khoản thành công.'
        });
        header('Location: tai-khoan.php', true, 303);
        exit;
    } catch (DomainException | mysqli_sql_exception $ex) {
        $db->rollback();
        $error = $ex instanceof DomainException ? $ex->getMessage() : ($ex->getCode() === 1062 ?
            'Mã nhân viên hoặc tên đăng nhập đã được sử dụng.' : 'Chưa lưu được thay đổi. Vui lòng thử lại.');
        http_response_code(422);
    }
}
$keyword = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']),0,100) : '';
$role = is_string($_GET['role'] ?? null) && isset(ACCOUNT_ROLES[$_GET['role']]) ? $_GET['role'] : '';
$status = in_array($_GET['status'] ?? '', ['0','1'],true) ? $_GET['status'] : '';
try {
    [$list, $total, $page, $pages] = account_list($db, $keyword, $role, $status, $_GET['page'] ?? 1);
    $rooms = $db->query('SELECT id,ten_phong_truc FROM phong_truc WHERE dang_hoat_dong=1 ORDER BY ten_phong_truc')->fetch_all(MYSQLI_ASSOC);
    $item = $id ? $db->execute_query('SELECT id,ma_nhan_vien,ho_ten,ten_dang_nhap,so_dien_thoai,vai_tro,phong_truc_id,dang_hoat_dong FROM nhan_vien WHERE id=?',[$id])->fetch_assoc() : [];
} catch (mysqli_sql_exception $ex) { http_response_code(503); exit('Tạm thời chưa tải được danh sách tài khoản.'); }
if ($id && !$item) { http_response_code(404); exit('Không tìm thấy tài khoản.'); }
$showForm = $id || isset($_GET['new']) || $action === 'create';
$data = $item;
if ($post && in_array($action,['create','edit'],true)) {
    foreach (['ma_nhan_vien','ho_ten','ten_dang_nhap','so_dien_thoai','vai_tro','phong_truc_id'] as $key) {
        $data[$key] = is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
    }
}
$filters = ['q'=>$keyword,'role'=>$role,'status'=>$status,'page'=>$page];
$link = static fn(array $changes): string => '?'.http_build_query(array_replace($filters,$changes));
$title = 'Tài khoản';
require __DIR__.'/admin-head.php';
require __DIR__.'/views/accounts.php';
require __DIR__.'/../shared/views/end.php';
