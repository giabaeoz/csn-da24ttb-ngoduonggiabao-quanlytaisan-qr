<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/navigation.php';

if (!isset($nguoiDung, $title)) {
    http_response_code(404);
    exit;
}

$flash = get_flash();
$roleLabels = ['QUAN_TRI' => 'Quản trị viên', 'QUAN_LY' => 'Quản lý', 'NHAN_VIEN' => 'Nhân viên'];
$headerRole = $nguoiDung['vai_tro'];
$menu = app_navigation($headerRole);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:,">
    <title><?= e($title) ?> · Tài sản QR</title>
    <link rel="stylesheet" href="../vendor/bootstrap.min.css">
    <link rel="stylesheet" href="../shared/management.css">
    <?php if ($title === 'Tài sản'): ?>
        <link rel="stylesheet" href="../shared/assets.css">
    <?php endif; ?>
    <?php if (in_array($title, ['Tài sản', 'Phòng học', 'Phòng trực'], true)): ?>
        <link rel="stylesheet" href="../quan-ly/manager.css">
    <?php endif; ?>
    <?php if ($title === 'Tổng quan'): ?><link rel="stylesheet" href="dashboard.css"><?php endif; ?>
</head>
<body>
<aside>
    <div class="brand">
        <b class="mark">QR</b>
        <div><strong>TÀI SẢN QR</strong><small><?= e($roleLabels[$headerRole]) ?></small></div>
    </div>
    <nav aria-label="Chức năng">
        <small>CHỨC NĂNG</small>
        <?php foreach ($menu as $navItem): ?>
            <a href="<?= e($navItem['href']) ?>" <?= $title === $navItem['page'] ? 'aria-current="page"' : '' ?>><?= e($navItem['label']) ?></a>
        <?php endforeach; ?>
    </nav>
    <form method="post" action="<?= e(app_url('logout.php')) ?>">
        <?= csrf_input() ?>
        <button>Đăng xuất</button>
    </form>
</aside>
<div class="workspace">
    <header>
        <span>Trang chủ / <?= e($title) ?></span>
        <div class="identity">
            <b><?= e(['QUAN_TRI'=>'QT','QUAN_LY'=>'QL','NHAN_VIEN'=>'NV'][$headerRole]) ?></b>
            <div><small><?= e($roleLabels[$headerRole]) ?></small><?= e($nguoiDung['ho_ten']) ?></div>
        </div>
    </header>
    <main>
        <h1><?= e($title) ?></h1>
        <?php if ($flash): ?>
            <p class="notice" role="status"><?= e($flash['noiDung']) ?></p>
        <?php endif; ?>
