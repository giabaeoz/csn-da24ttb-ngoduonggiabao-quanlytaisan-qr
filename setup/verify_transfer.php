<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('Chỉ chạy CLI.');

require_once __DIR__ . '/../src/includes/database.php';
require_once __DIR__ . '/../src/includes/transfer-service.php';
require_once __DIR__ . '/../src/includes/borrow-service.php';
require_once __DIR__ . '/../src/includes/inventory-service.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$key = strtoupper(bin2hex(random_bytes(5)));
$deskIds = []; $staffIds = []; $assets = []; $borrowerId = 0; $loanId = 0; $transferId = 0; $roundId = 0;

function transfer_assert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS $message\n";
}
function transfer_reject(callable $action, string $message): void
{
    try { $action(); } catch (DomainException) { transfer_assert(true, $message); return; }
    throw new RuntimeException($message . ' không bị chặn');
}

try {
    $type = $db->query('SELECT id FROM loai_tai_san WHERE dang_hoat_dong = 1 LIMIT 1')->fetch_row();
    $rooms = $db->query('SELECT id, ma_phong FROM phong_hoc WHERE dang_hoat_dong = 1 ORDER BY id LIMIT 3')->fetch_all(MYSQLI_ASSOC);
    transfer_assert((bool)$type && count($rooms) >= 3, 'Có danh mục và ba phòng học nền');
    [$origin, $destination, $otherRoom] = $rooms;
    for ($i = 1; $i <= 2; $i++) {
        $db->execute_query('INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc) VALUES (?, ?)', ['TDC' . $key . $i, 'Phòng trực thử điều chuyển ' . $i]);
        $deskIds[$i] = (int)$db->insert_id;
        $db->execute_query("INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id) VALUES (?, 'Nhân viên thử điều chuyển', ?, ?, 'NHAN_VIEN', ?)",
            ['NVDC' . $key . $i, 'test_transfer_' . strtolower($key) . $i, password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT), $deskIds[$i]]);
        $staffIds[$i] = (int)$db->insert_id;
    }
    $db->execute_query("INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon) VALUES (?, ?, 'Người mượn thử điều chuyển', 'SINH_VIEN')", ['NMDC' . $key, 'MSDC' . $key]);
    $borrowerId = (int)$db->insert_id;
    $createAsset = function (string $kind, int $desk, ?int $room, string $scope = 'LINH_HOAT', array $compatible = []) use ($db, $key, $type, &$assets): int {
        $code = 'DC' . $key . $kind;
        $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, pham_vi_tuong_thich, phong_tuong_thich) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$code, 'TVU:' . $code, 'Tài sản thử ' . $kind, (int)$type[0], $desk, $room, $scope, json_encode($compatible)]);
        return $assets[$kind] = (int)$db->insert_id;
    };
    $createAsset('OK', $deskIds[1], (int)$origin['id']);
    $createAsset('FIXED', $deskIds[1], (int)$origin['id'], 'THEO_PHONG', [$destination['ma_phong']]);
    $createAsset('INCOMP', $deskIds[1], (int)$origin['id'], 'THEO_PHONG', [$otherRoom['ma_phong']]);
    $createAsset('WRONGROOM', $deskIds[1], (int)$otherRoom['id']);
    $createAsset('OTHERDESK', $deskIds[2], (int)$origin['id']);
    $createAsset('BORROWED', $deskIds[1], null);
    $loanId = loan_create($db, $staffIds[1], ['nguoi_muon_id' => $borrowerId, 'phong_hoc_id' => (int)$origin['id'],
        'han_tra' => date('Y-m-d\TH:i', time() + 86400), 'tai_san_id' => [$assets['BORROWED']], 'ghi_chu' => '']);
    $base = ['phong_hoc_di_id' => (int)$origin['id'], 'phong_hoc_den_id' => (int)$destination['id'],
        'ly_do' => 'Điều chuyển phục vụ kiểm thử', 'ghi_chu' => '', 'xac_nhan_tuong_thich' => '1'];
    transfer_reject(fn() => transfer_create($db, $staffIds[1], [...$base, 'phong_hoc_den_id' => (int)$origin['id'], 'tai_san_id' => [$assets['OK']]]), 'Chặn hai phòng học trùng nhau');
    transfer_reject(fn() => transfer_create($db, $staffIds[1], [...$base, 'xac_nhan_tuong_thich' => '', 'tai_san_id' => [$assets['OK']]]), 'Yêu cầu xác nhận tương thích');
    transfer_reject(fn() => transfer_create($db, $staffIds[1], [...$base, 'tai_san_id' => [$assets['INCOMP']]]), 'Chặn tài sản không tương thích phòng đến');
    transfer_reject(fn() => transfer_create($db, $staffIds[1], [...$base, 'tai_san_id' => [$assets['WRONGROOM']]]), 'Chặn tài sản không ở phòng học nguồn');
    transfer_reject(fn() => transfer_create($db, $staffIds[1], [...$base, 'tai_san_id' => [$assets['OTHERDESK']]]), 'Chặn tài sản thuộc phòng trực khác');
    transfer_reject(fn() => transfer_create($db, $staffIds[1], [...$base, 'tai_san_id' => [$assets['BORROWED']]]), 'Chặn tài sản đang mượn');
    transfer_reject(fn() => transfer_create($db, $staffIds[2], [...$base, 'tai_san_id' => [$assets['OK']]]), 'Chặn Nhân viên phòng trực khác');
    $transferId = transfer_create($db, $staffIds[1], [...$base, 'tai_san_id' => [$assets['OK'], $assets['FIXED']]]);
    transfer_assert((int)$db->execute_query('SELECT COUNT(*) FROM chi_tiet_dieu_chuyen WHERE dieu_chuyen_id = ?', [$transferId])->fetch_row()[0] === 2, 'Lưu phiếu có hai tài sản');
    transfer_assert((int)$db->execute_query('SELECT COUNT(*) FROM tai_san WHERE id IN (?, ?) AND phong_hoc_hien_tai_id = ? AND phong_truc_id = ?',
        [$assets['OK'], $assets['FIXED'], (int)$destination['id'], $deskIds[1]])->fetch_row()[0] === 2, 'Cập nhật phòng học và giữ nguyên phòng trực');
    $roundId = inventory_start($db, $staffIds[1], ['ten_dot_kiem_ke' => 'Kiểm kê chặn điều chuyển ' . $key, 'loai_kiem_ke' => 'DOT_XUAT', 'ghi_chu' => '']);
    transfer_reject(fn() => transfer_create($db, $staffIds[1], [...$base, 'phong_hoc_di_id' => (int)$otherRoom['id'], 'tai_san_id' => [$assets['WRONGROOM']]]), 'Chặn điều chuyển khi phòng trực đang kiểm kê');
} finally {
    if ($roundId) {
        $db->execute_query('DELETE FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?', [$roundId]);
        $db->execute_query('DELETE FROM dot_kiem_ke WHERE id = ?', [$roundId]);
    }
    if ($transferId) {
        $db->execute_query('DELETE FROM chi_tiet_dieu_chuyen WHERE dieu_chuyen_id = ?', [$transferId]);
        $db->execute_query('DELETE FROM dieu_chuyen WHERE id = ?', [$transferId]);
    }
    if ($loanId) {
        $db->execute_query('DELETE FROM chi_tiet_muon WHERE phieu_muon_id = ?', [$loanId]);
        $db->execute_query('DELETE FROM phieu_muon WHERE id = ?', [$loanId]);
    }
    foreach ($assets as $id) $db->execute_query('DELETE FROM tai_san WHERE id = ?', [$id]);
    if ($borrowerId) $db->execute_query('DELETE FROM nguoi_muon WHERE id = ?', [$borrowerId]);
    foreach ($staffIds as $id) $db->execute_query('DELETE FROM nhan_vien WHERE id = ?', [$id]);
    foreach ($deskIds as $id) $db->execute_query('DELETE FROM phong_truc WHERE id = ?', [$id]);
    echo "Đã dọn dữ liệu thử nghiệm.\n";
}
