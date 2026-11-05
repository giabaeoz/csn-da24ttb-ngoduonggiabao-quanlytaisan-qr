<?php
declare(strict_types=1);
require_once __DIR__.'/assets.php';

function admin_dashboard(mysqli $db): array
{
    $counts = [];
    foreach (['nhan_vien'=>'accounts','tai_san'=>'assets','phong_truc'=>'desks','phong_hoc'=>'rooms'] as $table=>$key) {
        $activeColumn = $table === 'tai_san' ? "trang_thai <> 'NGUNG_SU_DUNG'" : 'dang_hoat_dong = 1';
        $row = $db->query("SELECT COUNT(*) AS total, COALESCE(SUM($activeColumn),0) AS active FROM $table")->fetch_assoc();
        $counts[$key] = ['total'=>(int)$row['total'],'active'=>(int)$row['active']];
    }
    $conditions = $db->query("SELECT
        COALESCE(SUM(tinh_trang='TOT'),0) AS good,
        COALESCE(SUM(tinh_trang='HU_HONG'),0) AS damaged,
        COALESCE(SUM(tinh_trang='MAT'),0) AS missing
        FROM tai_san")->fetch_assoc();
    $conditions = array_map('intval',$conditions);
    $recent = $db->query('SELECT t.id,t.ma_tai_san,t.ten_tai_san,t.tinh_trang,t.cap_nhat_luc,
        l.ten_loai,p.ten_phong_truc
        FROM tai_san t JOIN loai_tai_san l ON l.id=t.loai_tai_san_id
        JOIN phong_truc p ON p.id=t.phong_truc_id
        ORDER BY t.cap_nhat_luc DESC,t.id DESC LIMIT 8')->fetch_all(MYSQLI_ASSOC);
    return compact('counts','conditions','recent');
}
