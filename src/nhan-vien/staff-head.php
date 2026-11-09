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
    <link rel="icon" href="data:,">
    <title><?= e($title) ?> · Tài sản QR</title>
    <link rel="stylesheet" href="vendor/bootstrap.min.css">
    <link rel="stylesheet" href="staff.css">
    <?php if (!empty($staffPageCss)): ?><link rel="stylesheet" href="<?= e($staffPageCss) ?>"><?php endif; ?>
</head>
<body>
<aside class="staff-sidebar">
    <div class="brand"><b class="mark">LOGO</b><div><strong>TÀI SẢN QR</strong><small>Phòng trực thiết bị</small></div></div>
    <nav aria-label="Chức năng"><small>CHỨC NĂNG</small>
        <?php foreach ($menu as $navItem): ?>
            <a href="<?= e($navItem['href']) ?>" <?= $title === $navItem['page'] ? 'aria-current="page"' : '' ?>><?= e($navItem['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="post" action="<?= e(app_url('logout.php')) ?>"><?= csrf_input() ?><button type="submit" class="btn-logout">Đăng xuất</button></form>
</aside>
<div class="staff-workspace">
    <header class="staff-top">
        <span class="breadcrumb-title">Trang chủ / <?= e($title) ?></span>
        <div class="identity">
            <span class="avatar">NV</span>
            <div>
                <small>Tài khoản</small>
                <strong>Nhân viên phòng trực</strong>
            </div>
        </div>
    </header>
    <main class="staff-main">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['loai'] ?? 'info') ?>" role="status"><?= e($flash['noiDung'] ?? '') ?></div><?php endif; ?>
