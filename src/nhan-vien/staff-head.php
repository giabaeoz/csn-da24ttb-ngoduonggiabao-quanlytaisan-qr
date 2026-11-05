<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/navigation.php';
if (!isset($nguoiDung, $title)) { http_response_code(404); exit; }
$flash = get_flash();
$tab = $tab ?? '';
$menu = app_navigation($nguoiDung['vai_tro']);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Tài sản QR</title>
    <link rel="stylesheet" href="vendor/bootstrap.min.css">
    <link rel="stylesheet" href="staff.css">
    <?php if (!empty($staffPageCss)): ?><link rel="stylesheet" href="<?= e($staffPageCss) ?>"><?php endif; ?>
</head>
<body>
<aside class="staff-sidebar">
    <div class="brand"><b class="mark">QR</b><div><strong>TÀI SẢN QR</strong><small>Phòng trực thiết bị</small></div></div>
    <nav aria-label="Chức năng"><small>CHỨC NĂNG</small>
        <?php foreach ($menu as $item): ?>
            <a href="<?= e($item['href']) ?>" <?= $title === $item['page'] ? 'aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="post" action="<?= e(app_url('logout.php')) ?>"><?= csrf_input() ?><button type="submit" class="btn btn-outline-secondary w-100">Đăng xuất</button></form>
</aside>
<div class="staff-workspace"><header class="staff-top"><span>Trang chủ / <?= e($title) ?></span><div class="identity"><b>NV</b><div><small>Nhân viên phòng trực</small><?= e($nguoiDung['ho_ten']) ?></div></div></header>
<main class="staff-main">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['loai'] ?? 'info') ?>" role="status"><?= e($flash['noiDung'] ?? '') ?></div><?php endif; ?>
