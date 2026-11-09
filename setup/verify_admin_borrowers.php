<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('Chỉ chạy bằng CLI.');
}

require_once __DIR__ . '/../src/includes/database.php';
require_once __DIR__ . '/../src/includes/borrowers.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$key = 'TAB' . strtoupper(bin2hex(random_bytes(4)));

function admin_borrower_check(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS $message\n";
}

function admin_borrower_rejects(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (DomainException) {
        echo "PASS $message\n";
        return;
    }
    throw new RuntimeException($message);
}

$actors = [];
foreach (['QUAN_TRI','QUAN_LY','NHAN_VIEN'] as $role) {
    $row = $db->execute_query('SELECT id FROM nhan_vien WHERE vai_tro=? AND dang_hoat_dong=1 ORDER BY id LIMIT 1', [$role])->fetch_row();
    if (!$row) throw new RuntimeException("Thiếu tài khoản $role đang hoạt động.");
    $actors[$role] = (int)$row[0];
}

$db->begin_transaction();
try {
    borrower_check_actor($db, $actors['QUAN_TRI'], ['QUAN_TRI']);
    admin_borrower_check(true, 'Cho phép Quản trị quản lý người mượn');
    borrower_check_actor($db, $actors['QUAN_LY']);
    admin_borrower_check(true, 'Giữ quyền quản lý người mượn của Quản lý');
    admin_borrower_rejects(
        fn() => borrower_check_actor($db, $actors['NHAN_VIEN'], ['QUAN_TRI','QUAN_LY']),
        'Chặn Nhân viên dùng CRUD toàn hệ thống'
    );

    $id = borrower_write($db, 'create', 0, [
        'ma_nguoi_muon' => $key,
        'ma_so_truong' => $key,
        'ho_ten' => 'Người mượn kiểm thử Quản trị',
        'lop_don_vi' => 'Lớp kiểm thử',
        'so_dien_thoai' => '0900000000',
        'loai_nguoi_muon' => 'SINH_VIEN',
    ]);
    admin_borrower_check($id > 0, 'Quản trị tạo hồ sơ người mượn');

    borrower_write($db, 'edit', $id, [
        'ma_nguoi_muon' => $key,
        'ma_so_truong' => $key,
        'ho_ten' => 'Người mượn đã cập nhật',
        'lop_don_vi' => 'Đơn vị đã cập nhật',
        'so_dien_thoai' => '0911111111',
        'loai_nguoi_muon' => 'GIANG_VIEN',
    ]);
    $row = $db->execute_query('SELECT ho_ten,loai_nguoi_muon FROM nguoi_muon WHERE id=?', [$id])->fetch_assoc();
    admin_borrower_check($row['ho_ten'] === 'Người mượn đã cập nhật' && $row['loai_nguoi_muon'] === 'GIANG_VIEN', 'Quản trị sửa hồ sơ người mượn');

    [$result, $total] = borrower_list($db, 'all', $key, '', null, 1);
    $found = $result->fetch_all(MYSQLI_ASSOC);
    admin_borrower_check($total === 1 && (int)$found[0]['id'] === $id, 'Tìm kiếm người mượn toàn hệ thống');
    [, $injectionTotal] = borrower_list($db, 'all', "' OR 1=1 --", '', null, 1);
    admin_borrower_check($injectionTotal === 0, 'Chuỗi tìm kiếm SQL được xử lý như dữ liệu');

    $deleted = borrower_write($db, 'delete', $id, []);
    admin_borrower_check($deleted === 0 && !$db->execute_query('SELECT id FROM nguoi_muon WHERE id=?', [$id])->fetch_row(), 'Xóa hồ sơ chưa có lịch sử mượn');
} finally {
    $db->rollback();
    echo "Đã hoàn tác dữ liệu kiểm thử người mượn của Quản trị.\n";
}
