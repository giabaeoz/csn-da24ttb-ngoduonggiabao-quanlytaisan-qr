<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/rbac.php';
require_once __DIR__.'/../includes/admin-dashboard.php';
$nguoiDung=kiem_tra_quyen(VAI_TRO_QUAN_TRI);
if ($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405); header('Allow: GET'); exit; }
$db=db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $dashboard=admin_dashboard($db);
} catch (mysqli_sql_exception $ex) {
    error_log('[ADMIN DASHBOARD] '.$ex->getMessage());
    http_response_code(503);
    exit('Chưa tải được số liệu tổng quan.');
}
extract($dashboard,EXTR_SKIP);
$title='Tổng quan';
require __DIR__.'/admin-head.php';
require __DIR__.'/views/dashboard.php';
require __DIR__.'/../shared/views/end.php';
