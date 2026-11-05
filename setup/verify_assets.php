<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../src/includes/database.php';
require_once __DIR__ . '/../src/includes/assets.php';
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
function check(bool $value, string $message): void {
    if (!$value) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}
$filters = ['q'=>'','type'=>null,'desk'=>null,'location'=>null,'state'=>'','condition'=>'','scope'=>'','compatible'=>''];
$samples = require __DIR__ . '/assets_word_samples.php';
foreach ($samples as [$code, $name, $type, $scope, $rooms]) {
    $asset = $db->execute_query(ASSET_SELECT . ' WHERE t.ma_tai_san = ?', [$code])->fetch_assoc();
    check($asset && $asset['ten_tai_san'] === $name && $asset['pham_vi_tuong_thich'] === $scope
        && json_decode($asset['phong_tuong_thich'], true) === $rooms, "Word mapping $code");
    check($asset['ten_phong_truc'] === 'Phòng trực thiết bị khu 1 (khu B)', "Desk $code");
}
$filtered = asset_list($db, array_replace($filters, ['compatible'=>'B21.301']), 1)[0];
$codes = array_column($filtered, 'ma_tai_san');
foreach (['RML-002','RML-003','RTV-003','MIC-002'] as $code) { check(in_array($code, $codes, true), "Compatible $code"); }
check(!in_array('RML-001', $codes, true), 'Exclude incompatible RML-001');
check(asset_list($db, array_replace($filters, ['q'=>"' OR 1=1 --"]), 1)[1] === 0, 'SQL injection stays literal');
check(asset_list($db, array_replace($filters, ['q'=>'%_']), 1)[1] === 0, 'LIKE wildcards stay literal');

$db->begin_transaction();
try {
    $asset = $db->query(ASSET_SELECT . " WHERE t.ma_tai_san = 'RML-001'")->fetch_assoc();
    $id = (int)$asset['id'];
    $staff = $db->query("SELECT id FROM nhan_vien WHERE vai_tro = 'NHAN_VIEN' LIMIT 1")->fetch_row()[0];
    $room = (int)$asset['phong_hoc_phu_hop_id'];
    $otherRoom = $db->execute_query('SELECT id FROM phong_hoc WHERE id <> ? LIMIT 1', [$room])->fetch_row()[0];
    $key = 'TEST' . bin2hex(random_bytes(6));
    foreach ([['THEO_PHONG','[]'], ['LINH_HOAT','["B21.101"]'], ['THEO_PHONG','["B21.101","B21.101"]'], ['THEO_PHONG',null]] as [$scope,$json]) {
        $rejected = false;
        try { $db->execute_query('UPDATE tai_san SET pham_vi_tuong_thich = ?, phong_tuong_thich = ? WHERE id = ?', [$scope,$json,$id]); }
        catch (mysqli_sql_exception $e) { if ($e->getCode() !== 3819) { throw $e; } $rejected = true; }
        check($rejected, 'Reject invalid compatibility ' . ($json ?? 'NULL'));
    }
    $db->execute_query('INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon) VALUES (?, ?, ?, ?)', [$key,$key,'Test history','SINH_VIEN']);
    $borrower = $db->insert_id;
    $db->execute_query("INSERT INTO phieu_muon (ma_phieu_muon,nguoi_muon_id,phong_hoc_id,phong_truc_id,nhan_vien_lap_id,thoi_gian_muon,han_tra) VALUES (?,?,?,?,?,NOW() - INTERVAL 2 DAY,NOW() - INTERVAL 1 DAY)", [$key,$borrower,$room,$asset['phong_truc_id'],$staff]);
    $loan = $db->insert_id;
    $db->execute_query('INSERT INTO chi_tiet_muon (phieu_muon_id,tai_san_id) VALUES (?,?)', [$loan,$id]);
    $loanDetail = $db->insert_id;
    $db->execute_query("INSERT INTO dot_kiem_ke (ma_dot_kiem_ke,ten_dot_kiem_ke,phong_truc_id,nhan_vien_lap_id,loai_kiem_ke) VALUES (?,?,?,?,'DOT_XUAT')", [$key,'Test inventory',$asset['phong_truc_id'],$staff]);
    $inventory = $db->insert_id;
    $db->execute_query("INSERT INTO chi_tiet_kiem_ke (dot_kiem_ke_id,tai_san_id,tinh_trang_so_sach,trang_thai_so_sach) VALUES (?,?,'TOT','DANG_MUON')", [$inventory,$id]);
    $db->execute_query('INSERT INTO dieu_chuyen (ma_dieu_chuyen,phong_hoc_di_id,phong_hoc_den_id,nhan_vien_lap_id,ly_do) VALUES (?,?,?,?,?)', [$key,$room,$otherRoom,$staff,'Test transfer']);
    $transfer = $db->insert_id;
    $db->execute_query('INSERT INTO chi_tiet_dieu_chuyen (dieu_chuyen_id,tai_san_id) VALUES (?,?)', [$transfer,$id]);
    $db->execute_query("INSERT INTO su_co_tai_san (ma_su_co,tai_san_id,nhan_vien_lap_id,loai_su_co,mo_ta) VALUES (?,?,?,'SAI_VI_TRI',?)", [$key,$id,$staff,'Test incident']);
    $history = asset_history($db, $id);
    foreach (['loans','inventory','transfers','incidents'] as $kind) { check(count($history[$kind]) >= 1, "History $kind"); }
    check((int)$history['loans'][0]['qua_han'] === 1, 'Overdue open loan');
    check(asset_list($db,array_replace($filters,['q'=>'RML-001','state'=>'DANG_MUON']),1)[1] === 1, 'Open loan reflected in asset state');
    $db->execute_query("UPDATE chi_tiet_muon SET thoi_gian_tra = NOW(), tinh_trang_khi_tra = 'TOT' WHERE id = ?", [$loanDetail]);
    check(asset_list($db,array_replace($filters,['q'=>'RML-001','state'=>'SAN_SANG']),1)[1] === 1, 'Returned asset ready');
    $db->execute_query('UPDATE tai_san SET pham_vi_tuong_thich = NULL, phong_tuong_thich = NULL WHERE id = ?', [$id]);
    $legacy = $db->execute_query(ASSET_SELECT . ' WHERE t.id = ?', [$id])->fetch_assoc();
    check(asset_compatibility($legacy) === 'B21.101', 'Legacy room fallback');
    for ($i = 0; $i < 21; $i++) {
        $code = $key . '-' . $i;
        $db->execute_query('INSERT INTO tai_san (ma_tai_san,ma_qr,ten_tai_san,loai_tai_san_id,phong_truc_id) VALUES (?,?,?,?,?)', [$code,$code,'Test pagination',$asset['loai_tai_san_id'],$asset['phong_truc_id']]);
    }
    [$pageRows,$total,$page,$pages] = asset_list($db,array_replace($filters,['q'=>$key]),999);
    check($total === 21 && $page === 2 && $pages === 2 && count($pageRows) === 1, 'Pagination clamps to valid last page');
} finally {
    $db->rollback();
    echo "Rolled back all test data.\n";
}
