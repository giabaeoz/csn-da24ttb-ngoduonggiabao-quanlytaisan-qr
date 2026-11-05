<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit('Chỉ chạy CLI.');
ini_set('session.save_path', sys_get_temp_dir());

require_once __DIR__ . '/../src/includes/session.php';
require_once __DIR__ . '/../src/includes/database.php';
require_once __DIR__ . '/../src/includes/auth.php';
require_once __DIR__ . '/../src/includes/rbac.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$key = strtoupper(bin2hex(random_bytes(5)));
$password = bin2hex(random_bytes(16));
$usernames = [];
$passed = 0;

function check_system(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException($message);
    $passed++;
    echo "PASS $message\n";
}

$db->begin_transaction();
try {
    $db->execute_query('INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc) VALUES (?, ?)',
        ['TA' . $key, 'Phòng trực thử xác thực ' . $key]);
    $deskId = (int)$db->insert_id;
    foreach ([VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN] as $role) {
        $username = 'test_auth_' . strtolower($role) . '_' . strtolower($key);
        $usernames[$role] = $username;
        $db->execute_query('INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id) VALUES (?, ?, ?, ?, ?, ?)',
            ['T' . $role . $key, 'Tài khoản kiểm thử ' . $role, $username, password_hash($password, PASSWORD_DEFAULT), $role,
                $role === VAI_TRO_NHAN_VIEN ? $deskId : null]);
    }
    $lockedUsername = 'test_auth_locked_' . strtolower($key);
    $db->execute_query("INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, ten_dang_nhap, mat_khau_hash, vai_tro, dang_hoat_dong) VALUES (?, 'Tài khoản bị khóa kiểm thử', ?, ?, 'QUAN_TRI', 0)",
        ['TL' . $key, $lockedUsername, password_hash($password, PASSWORD_DEFAULT)]);

    foreach ($usernames as $role => $username) {
        $login = auth_dang_nhap($username, $password);
        check_system($login['thanh_cong'] === true && ($login['vai_tro'] ?? '') === $role, 'Đăng nhập vai trò ' . $role);
    }
    $badPassword = auth_dang_nhap($usernames[VAI_TRO_QUAN_TRI], $password . 'sai');
    check_system($badPassword['thanh_cong'] === false, 'Từ chối mật khẩu sai');
    $unknown = auth_dang_nhap('khong_co_' . $key, $password);
    check_system($unknown['thanh_cong'] === false, 'Từ chối tài khoản không tồn tại');
    $locked = auth_dang_nhap($lockedUsername, $password);
    check_system($locked['thanh_cong'] === false, 'Từ chối tài khoản bị khóa');

    $token = csrf_token();
    check_system(strlen($token) === 64 && csrf_kiem_tra($token), 'Token CSRF hợp lệ');
    check_system(!csrf_kiem_tra('invalid_token') && !csrf_kiem_tra(''), 'Từ chối token CSRF sai');

    $_SESSION['nguoi_dung']['vai_tro'] = VAI_TRO_QUAN_TRI;
    check_system(co_the_quan_tri_tai_khoan() && !co_the_van_hanh_muon_tra() && !co_the_quan_ly_tai_san_danh_muc(), 'Quyền Quản trị');
    $_SESSION['nguoi_dung']['vai_tro'] = VAI_TRO_QUAN_LY;
    check_system(co_the_quan_ly_tai_san_danh_muc() && !co_the_quan_tri_tai_khoan() && !co_the_van_hanh_muon_tra(), 'Quyền Quản lý');
    $_SESSION['nguoi_dung']['vai_tro'] = VAI_TRO_NHAN_VIEN;
    check_system(co_the_van_hanh_muon_tra() && !co_the_quan_tri_tai_khoan() && !co_the_quan_ly_tai_san_danh_muc(), 'Quyền Nhân viên');

    auth_dang_xuat();
    check_system(!auth_da_dang_nhap(), 'Đăng xuất xóa phiên');
    echo "Đạt $passed kiểm tra.\n";
} finally {
    $db->rollback();
    echo "Đã hoàn tác toàn bộ tài khoản thử nghiệm.\n";
}
