<?php
declare(strict_types=1);
require __DIR__ . '/../src/includes/database.php';
require __DIR__ . '/../src/includes/borrow-service.php';
if (PHP_SAPI !== 'cli') exit('Chỉ chạy CLI.');
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$key = strtoupper(bin2hex(random_bytes(5)));
$deskId = 0; $staffId = 0; $borrowerId = 0; $loanId = 0; $assets = []; $details = [];
function check_borrow(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); echo "PASS $message\n"; }
try {
    $room = $db->query('SELECT id, ma_phong FROM phong_hoc WHERE dang_hoat_dong = 1 ORDER BY id LIMIT 1')->fetch_assoc();
    $otherRoom = $room ? $db->execute_query('SELECT id FROM phong_hoc WHERE dang_hoat_dong = 1 AND id <> ? ORDER BY id LIMIT 1', [(int)$room['id']])->fetch_assoc() : null;
    $type = $db->query('SELECT id FROM loai_tai_san WHERE dang_hoat_dong = 1 ORDER BY id LIMIT 1')->fetch_assoc();
    check_borrow((bool)($room && $otherRoom && $type), 'Có danh mục nền cho thử nghiệm');
    $db->execute_query('INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc) VALUES (?, ?)', ['BT' . $key, 'Phòng trực thử mượn trả ' . $key]);
    $deskId = (int)$db->insert_id;
    $db->execute_query("INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id) VALUES (?, 'Nhân viên thử mượn trả', ?, ?, 'NHAN_VIEN', ?)",
        ['NVB' . $key, 'test_borrow_' . strtolower($key), password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT), $deskId]);
    $staffId = (int)$db->insert_id;
    $staff = ['id' => $staffId, 'phong_truc_id' => $deskId];
    $db->execute_query("INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon) VALUES (?, ?, 'Người mượn kiểm thử', 'SINH_VIEN')", ["TEST-$key", "TEST-$key"]);
    $borrowerId = (int)$db->insert_id;
    foreach (['LINH_HOAT', 'THEO_PHONG'] as $i => $scope) {
        $code = "TEST-$key-$i";
        $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, pham_vi_tuong_thich, phong_tuong_thich) VALUES (?, ?, 'Thiết bị thử nghiệm', ?, ?, ?, ?)",
            [$code, "TVU:$code", (int)$type['id'], $deskId, $scope, $scope === 'THEO_PHONG' ? json_encode([$room['ma_phong']]) : '[]']);
        $assets[] = (int)$db->insert_id;
    }
    $payload = ['nguoi_muon_id' => $borrowerId, 'phong_hoc_id' => (int)$room['id'], 'han_tra' => date('Y-m-d\TH:i', time()+86400), 'tai_san_id' => $assets, 'ghi_chu' => 'Kiểm thử'];
    try { loan_create($db, (int)$staff['id'], [...$payload, 'phong_hoc_id' => (int)$otherRoom['id']]); throw new RuntimeException('Đã cho mượn sai phòng'); }
    catch (DomainException) { check_borrow(true, 'Chặn tài sản không phù hợp phòng học'); }
    $loanId = loan_create($db, (int)$staff['id'], $payload);
    $otherStaff = $db->execute_query("SELECT id FROM nhan_vien WHERE vai_tro = 'NHAN_VIEN' AND dang_hoat_dong = 1 AND phong_truc_id <> ? LIMIT 1", [(int)$staff['phong_truc_id']])->fetch_assoc();
    if ($otherStaff) {
        try { loan_create($db, (int)$otherStaff['id'], $payload); throw new RuntimeException('Nhân viên khác phòng đã mượn được'); }
        catch (DomainException) { check_borrow(true, 'Chặn nhân viên khác phòng trực lập phiếu'); }
    }
    $details = $db->execute_query('SELECT id, tai_san_id FROM chi_tiet_muon WHERE phieu_muon_id = ? ORDER BY id', [$loanId])->fetch_all(MYSQLI_ASSOC);
    check_borrow(count($details) === 2, 'Lập phiếu hai thiết bị');
    check_borrow((int)$db->execute_query("SELECT COUNT(*) FROM tai_san WHERE id IN (?, ?) AND trang_thai = 'DANG_MUON' AND phong_hoc_hien_tai_id = ?", [$assets[0], $assets[1], (int)$room['id']])->fetch_row()[0] === 2, 'Cập nhật trạng thái và phòng sử dụng');
    try { loan_create($db, (int)$staff['id'], $payload); throw new RuntimeException('Đã cho mượn trùng'); }
    catch (DomainException) { check_borrow(true, 'Chặn mượn trùng tài sản'); }
    $first = (int)$details[0]['id']; $second = (int)$details[1]['id'];
    if ($otherStaff) {
        try { loan_return($db, (int)$otherStaff['id'], ['phieu_muon_id' => $loanId, 'chi_tiet_id' => [$first], 'tinh_trang' => [$first => 'TOT'], 'ghi_chu' => []]); throw new RuntimeException('Nhân viên khác phòng đã nhận trả được'); }
        catch (DomainException) { check_borrow(true, 'Chặn nhân viên khác phòng trực nhận trả'); }
    }
    $left = loan_return($db, (int)$staff['id'], ['phieu_muon_id' => $loanId, 'chi_tiet_id' => [$first], 'tinh_trang' => [$first => 'TOT'], 'ghi_chu' => []]);
    check_borrow($left === 1, 'Trả một phần giữ phiếu đang mượn');
    check_borrow($db->execute_query('SELECT trang_thai FROM phieu_muon WHERE id = ?', [$loanId])->fetch_row()[0] === 'DANG_MUON', 'Phiếu vẫn mở');
    try { loan_return($db, (int)$staff['id'], ['phieu_muon_id' => $loanId, 'chi_tiet_id' => [$second], 'tinh_trang' => [$second => 'HU_HONG'], 'ghi_chu' => []]); throw new RuntimeException('Thiếu mô tả hư hỏng'); }
    catch (DomainException) { check_borrow(true, 'Yêu cầu mô tả hư hỏng'); }
    $left = loan_return($db, (int)$staff['id'], ['phieu_muon_id' => $loanId, 'chi_tiet_id' => [$second], 'tinh_trang' => [$second => 'HU_HONG'], 'ghi_chu' => [$second => 'Nứt vỏ thiết bị thử nghiệm']]);
    check_borrow($left === 0, 'Trả đầy đủ đóng phiếu');
    check_borrow($db->execute_query('SELECT trang_thai FROM phieu_muon WHERE id = ?', [$loanId])->fetch_row()[0] === 'DA_TRA', 'Trạng thái phiếu đã trả');
    check_borrow((int)$db->execute_query("SELECT COUNT(*) FROM su_co_tai_san WHERE chi_tiet_muon_id = ? AND loai_su_co = 'HU_HONG'", [$second])->fetch_row()[0] === 1, 'Tự lập sự cố hư hỏng');
    try { loan_return($db, (int)$staff['id'], ['phieu_muon_id' => $loanId, 'chi_tiet_id' => [$second], 'tinh_trang' => [$second => 'TOT'], 'ghi_chu' => []]); throw new RuntimeException('Đã trả hai lần'); }
    catch (DomainException) { check_borrow(true, 'Chặn trả lặp'); }
} finally {
    if ($loanId) {
        $db->execute_query('DELETE FROM su_co_tai_san WHERE chi_tiet_muon_id IN (SELECT id FROM chi_tiet_muon WHERE phieu_muon_id = ?)', [$loanId]);
        $db->execute_query('DELETE FROM chi_tiet_muon WHERE phieu_muon_id = ?', [$loanId]);
        $db->execute_query('DELETE FROM phieu_muon WHERE id = ?', [$loanId]);
    }
    foreach ($assets as $id) $db->execute_query('DELETE FROM tai_san WHERE id = ?', [$id]);
    if ($borrowerId) $db->execute_query('DELETE FROM nguoi_muon WHERE id = ?', [$borrowerId]);
    if ($staffId) $db->execute_query('DELETE FROM nhan_vien WHERE id = ?', [$staffId]);
    if ($deskId) $db->execute_query('DELETE FROM phong_truc WHERE id = ?', [$deskId]);
    echo "Đã dọn dữ liệu thử nghiệm.\n";
}
