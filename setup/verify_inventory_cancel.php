<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("Chỉ chạy CLI.\n");
require __DIR__ . '/../src/includes/database.php';
require __DIR__ . '/../src/includes/inventory-service.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$key = strtoupper(bin2hex(random_bytes(5)));
$deskId = 0;
$staffId = 0;
$assetId = 0;
$roundIds = [];

function cancel_assert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS $message\n";
}

function cancel_reject(callable $action, string $message): void
{
    try {
        $action();
    } catch (DomainException) {
        cancel_assert(true, $message);
        return;
    }
    throw new RuntimeException($message . ' không bị chặn');
}

try {
    $type = $db->query('SELECT id FROM loai_tai_san WHERE dang_hoat_dong = 1 ORDER BY id LIMIT 1')->fetch_row();
    cancel_assert((bool)$type, 'Có loại tài sản nền');
    $db->execute_query('INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc) VALUES (?, ?)',
        ['KHC' . substr($key, 0, 12), 'Phòng trực thử hủy kiểm kê ' . $key]);
    $deskId = (int)$db->insert_id;
    $db->execute_query("INSERT INTO nhan_vien
        (ma_nhan_vien, ho_ten, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id)
        VALUES (?, 'Nhân viên thử hủy kiểm kê', ?, ?, 'NHAN_VIEN', ?)",
        ['NVC' . $key, 'cancel_inventory_' . strtolower($key), password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT), $deskId]);
    $staffId = (int)$db->insert_id;
    $code = 'CAN-' . $key;
    $db->execute_query("INSERT INTO tai_san
        (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, pham_vi_tuong_thich, phong_tuong_thich)
        VALUES (?, ?, 'Tài sản thử hủy kiểm kê', ?, ?, 'LINH_HOAT', '[]')",
        [$code, 'TVU:' . $code, (int)$type[0], $deskId]);
    $assetId = (int)$db->insert_id;

    $roundId = inventory_start($db, $staffId, [
        'ten_dot_kiem_ke' => 'Đợt sẽ hủy ' . $key,
        'loai_kiem_ke' => 'DOT_XUAT',
        'ghi_chu' => 'Kiểm thử chức năng hủy',
    ]);
    $roundIds[] = $roundId;
    $detailId = (int)$db->execute_query(
        'SELECT id FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ? AND tai_san_id = ?',
        [$roundId, $assetId]
    )->fetch_row()[0];
    inventory_record($db, $staffId, [
        'dot_kiem_ke_id' => $roundId,
        'chi_tiet_kiem_ke_id' => $detailId,
        'ket_qua' => 'TIM_THAY',
        'tinh_trang_thuc_te' => 'TOT',
        'phong_hoc_thuc_te_id' => '',
        'ghi_chu' => 'Kết quả tạm trước khi hủy',
    ]);
    inventory_cancel($db, $staffId, $roundId, 'Hủy để lập lại phạm vi kiểm kê');

    $cancelled = $db->execute_query(
        'SELECT trang_thai, thoi_gian_ket_thuc, nhan_vien_huy_id, ly_do_huy FROM dot_kiem_ke WHERE id = ?',
        [$roundId]
    )->fetch_assoc();
    cancel_assert($cancelled['trang_thai'] === 'DA_HUY', 'Chuyển đợt sang trạng thái Đã hủy');
    cancel_assert($cancelled['thoi_gian_ket_thuc'] !== null
        && (int)$cancelled['nhan_vien_huy_id'] === $staffId
        && $cancelled['ly_do_huy'] === 'Hủy để lập lại phạm vi kiểm kê', 'Lưu thời gian, người hủy và lý do hủy');
    cancel_assert((int)$db->execute_query(
        'SELECT COUNT(*) FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?', [$roundId]
    )->fetch_row()[0] === 1, 'Giữ chi tiết kiểm kê để truy vết');
    $assetState = $db->execute_query(
        'SELECT tinh_trang, trang_thai FROM tai_san WHERE id = ?', [$assetId]
    )->fetch_assoc();
    cancel_assert($assetState['tinh_trang'] === 'TOT' && $assetState['trang_thai'] === 'SAN_SANG',
        'Hủy không làm thay đổi hồ sơ tài sản');
    cancel_reject(fn() => inventory_cancel($db, $staffId, $roundId, 'Hủy lần nữa'), 'Chặn hủy lặp');
    cancel_reject(fn() => inventory_finish($db, $staffId, $roundId), 'Chặn hoàn tất đợt đã hủy');
    cancel_reject(fn() => inventory_record($db, $staffId, [
        'dot_kiem_ke_id' => $roundId,
        'chi_tiet_kiem_ke_id' => $detailId,
        'ket_qua' => 'TIM_THAY',
        'tinh_trang_thuc_te' => 'TOT',
        'ghi_chu' => '',
    ]), 'Chặn sửa kết quả của đợt đã hủy');

    $newRoundId = inventory_start($db, $staffId, [
        'ten_dot_kiem_ke' => 'Đợt mở lại ' . $key,
        'loai_kiem_ke' => 'DOT_XUAT',
        'ghi_chu' => '',
    ]);
    $roundIds[] = $newRoundId;
    cancel_assert($newRoundId > 0, 'Cho phép mở đợt mới sau khi hủy');
    cancel_reject(fn() => inventory_cancel($db, $staffId, $newRoundId, '   '), 'Bắt buộc nhập lý do hủy');
    inventory_cancel($db, $staffId, $newRoundId, 'Dọn dữ liệu kiểm thử');
} finally {
    foreach (array_reverse($roundIds) as $roundId) {
        $db->execute_query('DELETE FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?', [$roundId]);
        $db->execute_query('DELETE FROM dot_kiem_ke WHERE id = ?', [$roundId]);
    }
    if ($assetId) $db->execute_query('DELETE FROM tai_san WHERE id = ?', [$assetId]);
    if ($staffId) $db->execute_query('DELETE FROM nhan_vien WHERE id = ?', [$staffId]);
    if ($deskId) $db->execute_query('DELETE FROM phong_truc WHERE id = ?', [$deskId]);
    echo "Đã dọn dữ liệu kiểm thử hủy kiểm kê.\n";
}
