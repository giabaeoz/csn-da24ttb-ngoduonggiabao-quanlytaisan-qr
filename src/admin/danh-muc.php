<?php
declare(strict_types=1);
$kind = $_GET['kind'] ?? 'phong-truc';
if (!in_array($kind, ['phong-truc', 'phong-hoc'], true)) {
    http_response_code(403);
    exit('Quản trị viên chỉ quản lý phòng trực và phòng học.');
}
$_GET['kind'] = $kind;
define('CATALOG_ROLE', 'QUAN_TRI');
require __DIR__.'/../quan-ly/danh-muc.php';
