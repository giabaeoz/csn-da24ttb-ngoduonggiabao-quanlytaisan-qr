<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../src/includes/database.php';
require __DIR__.'/../src/includes/manager-service.php';
$db=db_ket_noi(); mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
function check(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message); echo "PASS: $message\n"; }
function rejects(callable $action,string $message): void {
    try { $action(); } catch (DomainException $ex) { echo "PASS: $message\n"; return; }
    throw new RuntimeException($message);
}
$actor=(int)$db->query("SELECT id FROM nhan_vien WHERE vai_tro='QUAN_LY' AND dang_hoat_dong=1 LIMIT 1")->fetch_row()[0];
$staff=(int)$db->query("SELECT id FROM nhan_vien WHERE vai_tro='NHAN_VIEN' AND dang_hoat_dong=1 LIMIT 1")->fetch_row()[0];
$key='T'.bin2hex(random_bytes(5));
$db->begin_transaction();
try {
    manager_check_actor($db,$actor);
    $type=manager_catalog_save($db,'loai','create',null,['code'=>$key,'name'=>'Test type','extra'=>'']);
    $desk=manager_catalog_save($db,'phong-truc','create',null,['code'=>$key,'name'=>'Test desk','extra'=>'']);
    $room=manager_catalog_save($db,'phong-hoc','create',null,['code'=>$key,'name'=>'Test room','extra'=>'','tang'=>'2']);
    $room2=manager_catalog_save($db,'phong-hoc','create',null,['code'=>$key.'B','name'=>'Test room B','extra'=>'','tang'=>'3']);
    $form=['ma_tai_san'=>$key,'ten_tai_san'=>'Test asset','loai_tai_san_id'=>(string)$type,'phong_truc_id'=>(string)$desk,
        'phong_hoc_hien_tai_id'=>(string)$room,'pham_vi_tuong_thich'=>'THEO_PHONG','rooms'=>[(string)$room,(string)$room2],
        'tinh_trang'=>'TOT','trang_thai'=>'SAN_SANG','ngay_dua_vao_su_dung'=>'2026-11-04','mo_ta'=>'Test note'];
    $asset=manager_asset_save($db,'create',null,$form);
    $saved=$db->execute_query('SELECT * FROM tai_san WHERE id=?',[$asset])->fetch_assoc();
    check($saved['ma_qr']==='TVU:'.$key && json_decode($saved['phong_tuong_thich'],true)===[$key,$key.'B'] && $saved['phong_hoc_phu_hop_id']===null,'Create asset with stable QR and multiroom list');
    rejects(fn()=>manager_asset_save($db,'create',null,array_replace($form,['pham_vi_tuong_thich'=>'LINH_HOAT'])),'Reject flexible scope with fixed rooms');
    rejects(fn()=>manager_asset_save($db,'create',null,array_replace($form,['phong_hoc_hien_tai_id'=>'999999'])),'Reject unknown room');
    rejects(fn()=>manager_asset_save($db,'create',null,array_replace($form,['phong_hoc_hien_tai_id'=>(string)$room2,'rooms'=>[(string)$room]])),'Reject incompatible current room');
    rejects(fn()=>manager_asset_save($db,'create',null,array_replace($form,['tinh_trang'=>'HU_HONG'])),'Reject broken but ready');
    rejects(fn()=>manager_catalog_save($db,'phong-hoc','edit',$room,['code'=>$key.'NEW','name'=>'Renamed','extra'=>'','tang'=>'']),'Prevent room rename when referenced by JSON');
    rejects(fn()=>manager_catalog_save($db,'phong-hoc','delete',$room,[]),'Prevent deletion of compatible room');
    $edit=array_replace($form,['ma_tai_san'=>'CHANGED','ten_tai_san'=>'Renamed asset','rooms'=>[(string)$room],'phong_hoc_hien_tai_id'=>(string)$room]);
    manager_asset_save($db,'edit',$asset,$edit);
    $saved=$db->execute_query('SELECT * FROM tai_san WHERE id=?',[$asset])->fetch_assoc();
    check($saved['ma_tai_san']===$key && $saved['ma_qr']==='TVU:'.$key && (int)$saved['phong_hoc_phu_hop_id']===$room,'Edit keeps code and QR; single room FK updated');
    $db->execute_query('INSERT INTO nguoi_muon (ma_nguoi_muon,ma_so_truong,ho_ten,loai_nguoi_muon) VALUES (?,?,?,?)',[$key,$key,'Test Borrower','SINH_VIEN']);
    $borrower=$db->insert_id;
    $db->execute_query("INSERT INTO phieu_muon (ma_phieu_muon,nguoi_muon_id,phong_hoc_id,phong_truc_id,nhan_vien_lap_id,han_tra) VALUES (?,?,?,?,?,NOW()+INTERVAL 1 DAY)",[$key,$borrower,$room,$desk,$staff]);
    $loan=$db->insert_id;
    $db->execute_query('INSERT INTO chi_tiet_muon (phieu_muon_id,tai_san_id) VALUES (?,?)',[$loan,$asset]);
    rejects(fn()=>manager_asset_save($db,'stop',$asset,[]),'Prevent stopping asset on open loan');
    rejects(fn()=>manager_asset_save($db,'delete',$asset,[]),'Prevent deleting asset with open loan');
    rejects(fn()=>manager_asset_save($db,'edit',$asset,array_replace($edit,['trang_thai'=>'BAO_TRI'])),'Prevent operational edit on open loan');
    manager_asset_save($db,'edit',$asset,array_replace($edit,['ten_tai_san'=>'Name while borrowed']));
    check($db->execute_query('SELECT ten_tai_san FROM tai_san WHERE id=?',[$asset])->fetch_row()[0]==='Name while borrowed','Allow safe name edit while borrowed');
    $db->execute_query('UPDATE chi_tiet_muon SET thoi_gian_tra=NOW(),tinh_trang_khi_tra=\'TOT\' WHERE phieu_muon_id=?',[$loan]);
    manager_asset_save($db,'stop',$asset,[]);
    check($db->execute_query('SELECT trang_thai FROM tai_san WHERE id=?',[$asset])->fetch_row()[0]==='NGUNG_SU_DUNG','Stop keeps asset record');
    rejects(fn()=>manager_asset_save($db,'delete',$asset,[]),'Prevent deleting asset with returned-loan history');
    $unusedCode=$key.'U';
    $unusedForm=array_replace($form,['ma_tai_san'=>$unusedCode]);
    $unused=manager_asset_save($db,'create',null,$unusedForm);
    manager_asset_save($db,'delete',$unused,[]);
    check(!$db->execute_query('SELECT id FROM tai_san WHERE id=?',[$unused])->fetch_row(),'Delete unused asset');
    $type2=manager_catalog_save($db,'loai','create',null,['code'=>$key.'X','name'=>'Unused','extra'=>'']);
    manager_catalog_save($db,'loai','delete',$type2,[]);
    check(!$db->execute_query('SELECT id FROM loai_tai_san WHERE id=?',[$type2])->fetch_row(),'Delete unused catalog entry');
} finally { $db->rollback(); echo "Rolled back all test data.\n"; }
