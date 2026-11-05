<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('Chỉ chạy CLI.');

require_once __DIR__ . '/../src/includes/database.php';
require_once __DIR__ . '/../src/includes/borrow-service.php';
require_once __DIR__ . '/../src/includes/staff-borrowers.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$key = strtoupper(bin2hex(random_bytes(5)));
$desks = []; $staff = []; $borrowers = []; $assetId = 0; $loanId = 0;

function verify_staff_borrower(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS $message\n";
}
function verify_staff_borrower_reject(callable $action, string $message): void
{
    try { $action(); } catch (DomainException $error) { verify_staff_borrower(true, $message); return; }
    throw new RuntimeException($message . ' không bị chặn.');
}

try {
    $type = $db->query('SELECT id FROM loai_tai_san WHERE dang_hoat_dong = 1 LIMIT 1')->fetch_row();
    $room = $db->query('SELECT id FROM phong_hoc WHERE dang_hoat_dong = 1 LIMIT 1')->fetch_row();
    verify_staff_borrower((bool)($type && $room), 'Có danh mục nền');
    for ($i = 1; $i <= 2; $i++) {
        $db->execute_query('INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc) VALUES (?, ?)',
            ['SB' . $key . $i, 'Phòng trực thử người mượn ' . $i]);
        $desks[$i] = (int)$db->insert_id;
        $db->execute_query("INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id) VALUES (?, 'Nhân viên kiểm thử', ?, ?, 'NHAN_VIEN', ?)",
            ['NSB' . $key . $i, 'test_staff_borrower_' . strtolower($key) . $i, password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT), $desks[$i]]);
        $staff[$i] = (int)$db->insert_id;
        $db->execute_query("INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon) VALUES (?, ?, 'Người mượn kiểm thử', 'SINH_VIEN')",
            ['BSB' . $key . $i, 'MSB' . $key . $i]);
        $borrowers[$i] = (int)$db->insert_id;
    }
    $code = 'ASB' . $key;
    $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, pham_vi_tuong_thich, phong_tuong_thich) VALUES (?, ?, 'Tài sản kiểm thử', ?, ?, 'LINH_HOAT', '[]')",
        [$code, 'TVU:' . $code, (int)$type[0], $desks[1]]);
    $assetId = (int)$db->insert_id;
    $loanId = loan_create($db, $staff[1], ['nguoi_muon_id' => $borrowers[1], 'phong_hoc_id' => (int)$room[0],
        'han_tra' => date('Y-m-d\TH:i', time() + 86400), 'tai_san_id' => [$assetId], 'ghi_chu' => '']);

    verify_staff_borrower(staff_borrower_can_edit($db, $borrowers[1], $desks[1]), 'Cho sửa hồ sơ có phiếu tại phòng trực');
    verify_staff_borrower(!staff_borrower_can_edit($db, $borrowers[2], $desks[1]), 'Chỉ xem hồ sơ chưa liên quan phòng trực');
    staff_borrower_update($db, $staff[1], $borrowers[1], [
        'ma_so_truong' => 'NEW' . $key, 'ho_ten' => 'Tên đã cập nhật',
        'lop_don_vi' => 'Lớp A', 'so_dien_thoai' => '0901234567',
        'ma_nguoi_muon' => 'KHONG_DUOC_DOI', 'loai_nguoi_muon' => 'GIANG_VIEN',
    ]);
    $record = $db->execute_query('SELECT * FROM nguoi_muon WHERE id = ?', [$borrowers[1]])->fetch_assoc();
    verify_staff_borrower($record['ho_ten'] === 'Tên đã cập nhật' && $record['so_dien_thoai'] === '0901234567', 'Lưu thông tin cơ bản');
    verify_staff_borrower($record['ma_nguoi_muon'] === 'BSB' . $key . '1' && $record['loai_nguoi_muon'] === 'SINH_VIEN', 'Giữ nguyên mã hồ sơ và loại người mượn');
    $input = ['ma_so_truong' => 'X' . $key, 'ho_ten' => 'Tên thử', 'lop_don_vi' => '', 'so_dien_thoai' => ''];
    verify_staff_borrower_reject(fn() => staff_borrower_update($db, $staff[2], $borrowers[1], $input), 'Chặn Nhân viên khác phòng trực');
    verify_staff_borrower_reject(fn() => staff_borrower_update($db, $staff[1], $borrowers[2], $input), 'Chặn sửa người chưa có phiếu tại phòng trực');
    verify_staff_borrower_reject(fn() => staff_borrower_update($db, $staff[1], $borrowers[1], [...$input, 'ho_ten' => '']), 'Chặn tên rỗng');
    verify_staff_borrower(count(staff_borrower_loans($db, $borrowers[1], $desks[1])) === 1, 'Lịch sử phiếu đúng phòng trực');
    verify_staff_borrower(count(staff_borrower_loans($db, $borrowers[1], $desks[2])) === 0, 'Không lộ lịch sử phòng trực khác');
} finally {
    if ($loanId) {
        $db->execute_query('DELETE FROM chi_tiet_muon WHERE phieu_muon_id = ?', [$loanId]);
        $db->execute_query('DELETE FROM phieu_muon WHERE id = ?', [$loanId]);
    }
    if ($assetId) $db->execute_query('DELETE FROM tai_san WHERE id = ?', [$assetId]);
    foreach ($borrowers as $id) $db->execute_query('DELETE FROM nguoi_muon WHERE id = ?', [$id]);
    foreach ($staff as $id) $db->execute_query('DELETE FROM nhan_vien WHERE id = ?', [$id]);
    foreach ($desks as $id) $db->execute_query('DELETE FROM phong_truc WHERE id = ?', [$id]);
    echo "Đã dọn dữ liệu thử nghiệm.\n";
}
