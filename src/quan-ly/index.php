<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/rbac.php';

$nguoiDung = kiem_tra_quyen(VAI_TRO_QUAN_LY);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$counts = [
    'Tài sản' => (int)$db->query('SELECT COUNT(*) FROM tai_san')->fetch_row()[0],
    'Loại tài sản' => (int)$db->query('SELECT COUNT(*) FROM loai_tai_san')->fetch_row()[0],
    'Phòng học' => (int)$db->query('SELECT COUNT(*) FROM phong_hoc')->fetch_row()[0],
    'Phòng trực' => (int)$db->query('SELECT COUNT(*) FROM phong_truc')->fetch_row()[0],
];

$title = 'Tổng quan';
require __DIR__ . '/manager-head.php';
?>

<div class="stats">
    <?php foreach ($counts as $label => $count): ?>
        <div class="stat">
            <strong><?= $count ?></strong>
            <?= e($label) ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="manager-cards">
    <?php foreach ([
        ['tai-san.php', 'Tài sản'],
        ['danh-muc.php?kind=loai', 'Loại tài sản'],
        ['danh-muc.php?kind=phong-hoc', 'Phòng học'],
        ['danh-muc.php?kind=phong-truc', 'Phòng trực'],
    ] as [$url, $label]): ?>
        <section class="panel">
            <h2><?= e($label) ?></h2>
            <a class="button" href="<?= e($url) ?>">Mở <?= e($label) ?></a>
        </section>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../shared/views/end.php'; ?>
