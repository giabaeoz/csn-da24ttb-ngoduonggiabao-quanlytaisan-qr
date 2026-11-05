<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/navigation.php';
if (!isset($nguoiDung,$title) || $nguoiDung['vai_tro'] !== VAI_TRO_QUAN_LY) { http_response_code(404); exit; }
$flash = get_flash();
$menu = app_navigation($nguoiDung['vai_tro']);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Tài sản QR</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../shared/management.css">
    <?php if ($title === 'Tài sản'): ?><link rel="stylesheet" href="../shared/assets.css"><?php endif; ?>
    <link rel="stylesheet" href="manager.css">
</head>
<body>
<aside>
    <div class="brand"><b class="mark">QR</b><div><strong>TÀI SẢN QR</strong><small>Quản lý</small></div></div>
    <nav aria-label="Chức năng"><small>CHỨC NĂNG</small>
        <?php foreach ($menu as $item): ?>
            <a href="<?= e($item['href']) ?>" <?= $title === $item['page'] ? 'aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="post" action="<?= e(app_url('logout.php')) ?>"><?= csrf_input() ?><button>Đăng xuất</button></form>
</aside>
<div class="workspace">
    <header><span>Trang chủ / <?= e($title) ?></span><div class="identity"><b>QL</b><div><small>Quản lý</small><?= e($nguoiDung['ho_ten']) ?></div></div></header>
    <main>
        <h1><?= e($title) ?></h1>
        <?php if ($flash): ?><p class="notice" role="status"><?= e($flash['noiDung']) ?></p><?php endif; ?>
