<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('Chỉ chạy CLI.');
require __DIR__ . '/../src/includes/database.php';
require __DIR__ . '/../src/includes/inventory-service.php';
require __DIR__ . '/../src/includes/borrow-service.php';
require __DIR__ . '/../src/includes/manager-service.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$key = strtoupper(bin2hex(random_bytes(5)));
$deskId = 0; $staffId = 0; $borrowerId = 0; $loanId = 0; $roundId = 0; $assets = [];
function inventory_assert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS $message\n";
}
function inventory_reject(callable $action, string $message): void
{
    try { $action(); } catch (DomainException) { inventory_assert(true, $message); return; }
    throw new RuntimeException($message . ' không bị chặn');
}

try {
    $type = $db->query('SELECT id FROM loai_tai_san WHERE dang_hoat_dong = 1 LIMIT 1')->fetch_row();
    $room = $db->query('SELECT id FROM phong_hoc WHERE dang_hoat_dong = 1 LIMIT 1')->fetch_row();
    $otherStaff = $db->query("SELECT id FROM nhan_vien WHERE vai_tro='NHAN_VIEN' AND dang_hoat_dong=1 LIMIT 1")->fetch_row();
    inventory_assert((bool)($type && $room && $otherStaff), 'Có danh mục nền cho kiểm thử');
    $db->execute_query('INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc) VALUES (?, ?)', ['T' . substr($key, 0, 12), 'Phòng trực kiểm thử ' . $key]);
    $deskId = (int)$db->insert_id;
    $db->execute_query("INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id) VALUES (?, 'Nhân viên kiểm thử', ?, ?, 'NHAN_VIEN', ?)",
        ['NV' . $key, 'test_inv_' . strtolower($key), password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT), $deskId]);
    $staffId = (int)$db->insert_id;
    $db->execute_query("INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon) VALUES (?, ?, 'Người mượn kiểm thử', 'SINH_VIEN')", ['NM' . $key, 'MS' . $key]);
    $borrowerId = (int)$db->insert_id;
    foreach (['TOT', 'HU_HONG', 'MISSING', 'WRONG', 'LOAN'] as $kind) {
        $code = 'INV-' . $key . '-' . $kind;
        $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, pham_vi_tuong_thich, phong_tuong_thich) VALUES (?, ?, ?, ?, ?, 'LINH_HOAT', '[]')",
            [$code, 'TVU:' . $code, 'Thiết bị kiểm thử ' . $kind, (int)$type[0], $deskId]);
        $assets[$kind] = (int)$db->insert_id;
    }
    $loanId = loan_create($db, $staffId, [
        'nguoi_muon_id' => $borrowerId, 'phong_hoc_id' => (int)$room[0],
        'han_tra' => date('Y-m-d\TH:i', time() + 86400), 'tai_san_id' => [$assets['LOAN']], 'ghi_chu' => 'Kiểm thử kiểm kê',
    ]);
    $roundId = inventory_start($db, $staffId, ['ten_dot_kiem_ke' => 'Đợt kiểm thử ' . $key, 'loai_kiem_ke' => 'CUOI_BUOI', 'ghi_chu' => 'Tự dọn sau thử']);
    $details = $db->execute_query('SELECT id, tai_san_id, trang_thai_so_sach FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?', [$roundId])->fetch_all(MYSQLI_ASSOC);
    inventory_assert(count($details) === 5, 'Chụp đủ năm tài sản trong phòng trực');
    $byAsset = []; foreach ($details as $detail) $byAsset[(int)$detail['tai_san_id']] = $detail;
    inventory_assert($byAsset[$assets['LOAN']]['trang_thai_so_sach'] === 'DANG_MUON', 'Nhận diện tài sản đang mượn lúc mở đợt');
    inventory_reject(fn() => inventory_start($db, $staffId, ['ten_dot_kiem_ke'=>'Trùng', 'loai_kiem_ke'=>'CUOI_BUOI']), 'Chặn hai đợt mở cùng phòng trực');
    inventory_reject(fn() => loan_create($db, $staffId, ['nguoi_muon_id'=>$borrowerId, 'phong_hoc_id'=>(int)$room[0], 'han_tra'=>date('Y-m-d\TH:i', time()+86400), 'tai_san_id'=>[$assets['TOT']], 'ghi_chu'=>'']), 'Chặn mượn mới khi đang kiểm kê');
    inventory_reject(function () use ($db, $assets) {
        $db->begin_transaction();
        try { manager_asset_save($db, 'stop', $assets['TOT'], []); $db->commit(); }
        catch (Throwable $error) { $db->rollback(); throw $error; }
    }, 'Chặn sửa tài sản khi đang kiểm kê');
    inventory_reject(fn() => inventory_record($db, (int)$otherStaff[0], ['dot_kiem_ke_id'=>$roundId, 'chi_tiet_kiem_ke_id'=>$byAsset[$assets['TOT']]['id'], 'ket_qua'=>'TIM_THAY', 'tinh_trang_thuc_te'=>'TOT', 'ghi_chu'=>'']), 'Chặn nhân viên khác phòng trực');
    inventory_reject(fn() => inventory_record($db, $staffId, ['dot_kiem_ke_id'=>$roundId, 'chi_tiet_kiem_ke_id'=>$byAsset[$assets['LOAN']]['id'], 'ket_qua'=>'TIM_THAY', 'tinh_trang_thuc_te'=>'TOT', 'ghi_chu'=>'']), 'Không kiểm trực tiếp tài sản đang mượn');
    $record = function (string $kind, string $result, ?string $condition, ?int $actualRoom, string $note) use ($db, $staffId, $roundId, $byAsset, $assets): void {
        inventory_record($db, $staffId, [
            'dot_kiem_ke_id'=>$roundId, 'chi_tiet_kiem_ke_id'=>$byAsset[$assets[$kind]]['id'],
            'ket_qua'=>$result, 'tinh_trang_thuc_te'=>$condition, 'phong_hoc_thuc_te_id'=>$actualRoom ?: '', 'ghi_chu'=>$note,
        ]);
    };
    $record('TOT', 'TIM_THAY', 'TOT', null, '');
    inventory_reject(fn() => inventory_finish($db, $staffId, $roundId), 'Chặn chốt khi còn tài sản chưa kiểm');
    $record('HU_HONG', 'TIM_THAY', 'HU_HONG', null, 'Vỡ vỏ thiết bị');
    $record('MISSING', 'CHUA_TIM_THAY', null, null, 'Đã tìm tại phòng trực và tủ số 1');
    $record('WRONG', 'TIM_THAY', 'TOT', (int)$room[0], 'Thấy ở phòng học');
    $outcome = inventory_finish($db, $staffId, $roundId);
    inventory_assert($outcome['incidents'] === 3 && $outcome['assets'] === 5, 'Chốt đợt và lập ba sự cố');
    inventory_assert($db->execute_query('SELECT trang_thai FROM dot_kiem_ke WHERE id = ?', [$roundId])->fetch_row()[0] === 'HOAN_TAT', 'Đợt chuyển sang hoàn tất');
    $states = [];
    foreach ($db->execute_query('SELECT id, tinh_trang, trang_thai FROM tai_san WHERE phong_truc_id = ?', [$deskId])->fetch_all(MYSQLI_ASSOC) as $asset) $states[(int)$asset['id']] = $asset;
    inventory_assert($states[$assets['HU_HONG']]['trang_thai'] === 'BAO_TRI' && $states[$assets['HU_HONG']]['tinh_trang'] === 'HU_HONG', 'Tài sản hư hỏng chuyển bảo trì');
    inventory_assert($states[$assets['MISSING']]['trang_thai'] === 'NGUNG_SU_DUNG' && $states[$assets['WRONG']]['trang_thai'] === 'NGUNG_SU_DUNG', 'Tạm chặn mượn tài sản thiếu hoặc sai vị trí');
    inventory_reject(fn() => inventory_record($db, $staffId, ['dot_kiem_ke_id'=>$roundId, 'chi_tiet_kiem_ke_id'=>$byAsset[$assets['TOT']]['id'], 'ket_qua'=>'TIM_THAY', 'tinh_trang_thuc_te'=>'TOT', 'ghi_chu'=>'']), 'Không sửa kết quả sau khi hoàn tất');
    inventory_reject(fn() => inventory_finish($db, $staffId, $roundId), 'Không chốt lặp đợt kiểm kê');
} finally {
    if ($roundId) {
        $db->execute_query('DELETE FROM su_co_tai_san WHERE chi_tiet_kiem_ke_id IN (SELECT id FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?)', [$roundId]);
        $db->execute_query('DELETE FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?', [$roundId]);
        $db->execute_query('DELETE FROM dot_kiem_ke WHERE id = ?', [$roundId]);
    }
    if ($loanId) {
        $db->execute_query('DELETE FROM chi_tiet_muon WHERE phieu_muon_id = ?', [$loanId]);
        $db->execute_query('DELETE FROM phieu_muon WHERE id = ?', [$loanId]);
    }
    foreach ($assets as $id) $db->execute_query('DELETE FROM tai_san WHERE id = ?', [$id]);
    if ($borrowerId) $db->execute_query('DELETE FROM nguoi_muon WHERE id = ?', [$borrowerId]);
    if ($staffId) $db->execute_query('DELETE FROM nhan_vien WHERE id = ?', [$staffId]);
    if ($deskId) $db->execute_query('DELETE FROM phong_truc WHERE id = ?', [$deskId]);
    echo "Đã dọn dữ liệu kiểm thử.\n";
}
