<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/rbac.php';
$nguoiDung = kiem_tra_quyen(VAI_TRO_QUAN_LY);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$counts = [
    'Tài sản' => (int)$db->query('SELECT COUNT(*) FROM tai_san')->fetch_row()[0],
    'Loại tài sản' => (int)$db->query('SELECT COUNT(*) FROM loai_tai_san')->fetch_row()[0],
    'Phòng học' => (int)$db->query('SELECT COUNT(*) FROM phong_hoc')->fetch_row()[0],
    'Phòng trực' => (int)$db->query('SELECT COUNT(*) FROM phong_truc')->fetch_row()[0],
];
$title='Tổng quan';
require __DIR__.'/manager-head.php';
?>
<p class="manager-intro">Quản lý danh mục, vị trí và thông tin từng tài sản có mã QR.</p>
<div class="stats">
    <?php foreach ($counts as $label=>$count): ?><div class="stat"><strong><?= $count ?></strong><?= e($label) ?></div><?php endforeach; ?>
</div>
<div class="manager-cards">
    <?php foreach ([
        ['tai-san.php','Tài sản','Thêm, sửa, theo dõi tình trạng và in mã QR.'],
        ['danh-muc.php?kind=loai','Loại tài sản','Quản lý các nhóm thiết bị.'],
        ['danh-muc.php?kind=phong-hoc','Phòng học','Quản lý phòng tương thích và vị trí hiện tại.'],
        ['danh-muc.php?kind=phong-truc','Phòng trực','Quản lý nơi cấp phát và phụ trách thiết bị.'],
    ] as [$url,$label,$description]): ?>
        <section class="panel"><h2><?= e($label) ?></h2><p><?= e($description) ?></p><a class="button" href="<?= e($url) ?>">Mở <?= e($label) ?></a></section>
    <?php endforeach; ?>
</div>
<?php require __DIR__.'/../shared/views/end.php';
