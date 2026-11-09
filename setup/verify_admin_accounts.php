<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('Chỉ chạy bằng CLI.');
}

require_once __DIR__ . '/../src/includes/database.php';
require_once __DIR__ . '/../src/admin/accounts-service.php';
require_once __DIR__ . '/../src/includes/account-list.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$key = 'TAD' . strtoupper(bin2hex(random_bytes(4)));

function admin_account_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS $message\n";
}

function admin_account_rejects(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (DomainException) {
        echo "PASS $message\n";
        return;
    }
    throw new RuntimeException($message);
}

$admin = $db->query("SELECT id FROM nhan_vien WHERE vai_tro='QUAN_TRI' AND dang_hoat_dong=1 ORDER BY id LIMIT 1")->fetch_row();
if (!$admin) {
    throw new RuntimeException('Cần ít nhất một tài khoản Quản trị đang hoạt động.');
}
$actor = (int)$admin[0];

$db->begin_transaction();
try {
    $db->execute_query('INSERT INTO phong_truc (ma_phong_truc,ten_phong_truc) VALUES (?,?)', [$key, 'Phòng trực kiểm thử tài khoản']);
    $desk = (int)$db->insert_id;
    $db->execute_query('INSERT INTO phong_truc (ma_phong_truc,ten_phong_truc,dang_hoat_dong) VALUES (?,?,0)', [$key . 'X', 'Phòng trực ngừng hoạt động']);
    $inactiveDesk = (int)$db->insert_id;

    $staffPassword = 'AdminTest#123';
    account_save($db, $actor, 'create', 0, [
        'ma_nhan_vien' => $key . 'NV',
        'ho_ten' => 'Nhân viên kiểm thử quản trị',
        'ten_dang_nhap' => strtolower($key) . '_staff',
        'so_dien_thoai' => '0900000000',
        'vai_tro' => 'NHAN_VIEN',
        'phong_truc_id' => (string)$desk,
        'mat_khau' => $staffPassword,
    ]);
    $staff = (int)$db->insert_id;
    $row = $db->execute_query('SELECT * FROM nhan_vien WHERE id=?', [$staff])->fetch_assoc();
    admin_account_check($row && $row['vai_tro'] === 'NHAN_VIEN' && (int)$row['phong_truc_id'] === $desk, 'Tạo Nhân viên và phân công phòng trực');
    admin_account_check(password_verify($staffPassword, (string)$row['mat_khau_hash']), 'Mật khẩu được băm an toàn');

    admin_account_rejects(fn() => account_save($db, $actor, 'create', 0, [
        'ma_nhan_vien' => $key . 'MISS', 'ho_ten' => 'Thiếu phòng trực',
        'ten_dang_nhap' => strtolower($key) . '_missing', 'so_dien_thoai' => '',
        'vai_tro' => 'NHAN_VIEN', 'phong_truc_id' => '', 'mat_khau' => $staffPassword,
    ]), 'Chặn Nhân viên chưa được phân công phòng trực');

    admin_account_rejects(fn() => account_save($db, $actor, 'create', 0, [
        'ma_nhan_vien' => $key . 'INACTIVE', 'ho_ten' => 'Phòng trực đã ngừng',
        'ten_dang_nhap' => strtolower($key) . '_inactive', 'so_dien_thoai' => '',
        'vai_tro' => 'NHAN_VIEN', 'phong_truc_id' => (string)$inactiveDesk, 'mat_khau' => $staffPassword,
    ]), 'Chặn phân công phòng trực đã ngừng hoạt động');

    admin_account_rejects(fn() => account_save($db, $actor, 'create', 0, [
        'ma_nhan_vien' => $key . 'SHORT', 'ho_ten' => 'Mật khẩu ngắn',
        'ten_dang_nhap' => strtolower($key) . '_short', 'so_dien_thoai' => '',
        'vai_tro' => 'QUAN_LY', 'phong_truc_id' => '', 'mat_khau' => '1234567',
    ]), 'Chặn mật khẩu ngắn');

    account_save($db, $actor, 'create', 0, [
        'ma_nhan_vien' => $key . 'QL',
        'ho_ten' => 'Quản lý kiểm thử',
        'ten_dang_nhap' => strtolower($key) . '_manager',
        'so_dien_thoai' => '',
        'vai_tro' => 'QUAN_LY',
        'phong_truc_id' => '',
        'mat_khau' => 'ManagerTest#123',
    ]);
    $manager = (int)$db->insert_id;
    admin_account_check((int)$db->execute_query('SELECT COUNT(*) FROM nhan_vien WHERE id=? AND vai_tro=\'QUAN_LY\' AND phong_truc_id IS NULL', [$manager])->fetch_row()[0] === 1, 'Tạo Quản lý không bắt buộc phòng trực');

    account_save($db, $actor, 'edit', $manager, [
        'ma_nhan_vien' => $key . 'QL',
        'ho_ten' => 'Quản lý đã cập nhật',
        'ten_dang_nhap' => strtolower($key) . '_manager',
        'so_dien_thoai' => '0911111111',
        'vai_tro' => 'QUAN_LY',
        'phong_truc_id' => '',
    ]);
    admin_account_check($db->execute_query('SELECT ho_ten FROM nhan_vien WHERE id=?', [$manager])->fetch_row()[0] === 'Quản lý đã cập nhật', 'Sửa thông tin tài khoản');

    account_save($db, $actor, 'status', $manager, ['status' => '0']);
    admin_account_check((int)$db->execute_query('SELECT dang_hoat_dong FROM nhan_vien WHERE id=?', [$manager])->fetch_row()[0] === 0, 'Khóa tài khoản');
    account_save($db, $actor, 'status', $manager, ['status' => '1']);
    admin_account_check((int)$db->execute_query('SELECT dang_hoat_dong FROM nhan_vien WHERE id=?', [$manager])->fetch_row()[0] === 1, 'Mở khóa tài khoản');

    $newPassword = 'ChangedTest#456';
    account_save($db, $actor, 'password', $manager, ['mat_khau' => $newPassword]);
    $hash = (string)$db->execute_query('SELECT mat_khau_hash FROM nhan_vien WHERE id=?', [$manager])->fetch_row()[0];
    admin_account_check(password_verify($newPassword, $hash), 'Đặt lại mật khẩu');

    admin_account_rejects(fn() => account_save($db, $actor, 'status', $actor, ['status' => '0']), 'Chặn Quản trị tự khóa tài khoản');
    $actorRow = $db->execute_query('SELECT ma_nhan_vien,ho_ten,ten_dang_nhap,so_dien_thoai,phong_truc_id FROM nhan_vien WHERE id=?', [$actor])->fetch_assoc();
    admin_account_rejects(fn() => account_save($db, $actor, 'edit', $actor, [
        'ma_nhan_vien' => $actorRow['ma_nhan_vien'], 'ho_ten' => $actorRow['ho_ten'],
        'ten_dang_nhap' => $actorRow['ten_dang_nhap'], 'so_dien_thoai' => $actorRow['so_dien_thoai'] ?? '',
        'vai_tro' => 'QUAN_LY', 'phong_truc_id' => $actorRow['phong_truc_id'] === null ? '' : (string)$actorRow['phong_truc_id'],
    ]), 'Chặn Quản trị tự hạ quyền');

    admin_account_rejects(fn() => account_save($db, $manager, 'status', $staff, ['status' => '0']), 'Chặn vai trò khác thực hiện quản trị tài khoản');

    [$result, $total] = account_list($db, $key . 'NV', 'NHAN_VIEN', '1', 1);
    $found = $result->fetch_all(MYSQLI_ASSOC);
    admin_account_check($total === 1 && (int)$found[0]['id'] === $staff, 'Tìm kiếm và lọc đúng tài khoản');
    [, $injectionTotal] = account_list($db, "' OR 1=1 --", '', '', 1);
    admin_account_check($injectionTotal === 0, 'Chuỗi tìm kiếm SQL được xử lý như dữ liệu');
} finally {
    $db->rollback();
    echo "Đã hoàn tác dữ liệu kiểm thử tài khoản quản trị.\n";
}

