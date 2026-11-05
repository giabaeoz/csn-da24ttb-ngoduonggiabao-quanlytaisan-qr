<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/../src/includes/database.php';
require_once __DIR__.'/../src/includes/admin-dashboard.php';
$db=db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
function dashboard_check(bool $ok,string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
    echo "PASS: $message\n";
}
$before=admin_dashboard($db);
dashboard_check(array_keys($before['counts'])===['accounts','assets','desks','rooms'],'Dashboard contains only four administration domains');
$key='DB'.bin2hex(random_bytes(5));
$type=$db->query('SELECT id FROM loai_tai_san LIMIT 1')->fetch_row()[0];
$db->begin_transaction();
try {
    $db->execute_query('INSERT INTO phong_truc (ma_phong_truc,ten_phong_truc) VALUES (?,?)',[$key,'Phòng trực kiểm thử']);
    $desk=$db->insert_id;
    $db->execute_query('INSERT INTO phong_hoc (ma_phong,ten_phong) VALUES (?,?)',[$key,'Phòng học kiểm thử']);
    $db->execute_query('INSERT INTO tai_san (ma_tai_san,ma_qr,ten_tai_san,loai_tai_san_id,phong_truc_id,pham_vi_tuong_thich,phong_tuong_thich) VALUES (?,?,?,?,?,\'LINH_HOAT\',\'[]\')',[$key,'TVU:'.$key,'Tài sản kiểm thử',$type,$desk]);
    $during=admin_dashboard($db);
    foreach (['assets','desks','rooms'] as $domain) {
        dashboard_check($during['counts'][$domain]['total']===$before['counts'][$domain]['total']+1,"Count updates for $domain");
    }
    dashboard_check($during['conditions']['good']===$before['conditions']['good']+1,'Asset condition updates');
    dashboard_check($during['recent'][0]['ma_tai_san']===$key,'Recently updated asset appears');
} finally {
    $db->rollback();
    echo "Rolled back dashboard test records.\n";
}
