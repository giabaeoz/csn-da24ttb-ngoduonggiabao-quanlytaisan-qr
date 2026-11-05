<?php
declare(strict_types=1);

/**
 * @var array<string, array<string, int>> $counts
 * @var array<string, int> $conditions
 * @var array<int, array<string, mixed>> $recent
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/assets.php';

if (!isset($counts, $conditions, $recent)) {
    http_response_code(404);
    exit;
}
$assetsTotal=$counts['assets']['total'];
$goodEnd=$assetsTotal ? 100*$conditions['good']/$assetsTotal : 0;
$damagedEnd=$assetsTotal ? 100*($conditions['good']+$conditions['damaged'])/$assetsTotal : 0;
$donut=sprintf('conic-gradient(#4a4a4a 0 %.2f%%, #999 %.2f%% %.2f%%, #ddd %.2f%% 100%%)',
    $goodEnd,$goodEnd,$damagedEnd,$damagedEnd);
?>
<div class="dashboard-intro">
    <p>Quản lý tài khoản, tài sản, phòng trực và phòng học.</p>
    <span>Dữ liệu cập nhật khi tải trang</span>
</div>
<div class="stats dashboard-stats">
    <?php foreach ([
        ['Tài khoản',$counts['accounts']['total'],'tai-khoan.php'],
        ['Tài sản',$counts['assets']['total'],'tai-san.php'],
        ['Phòng trực',$counts['desks']['total'],'danh-muc.php?kind=phong-truc'],
        ['Phòng học',$counts['rooms']['total'],'danh-muc.php?kind=phong-hoc'],
    ] as [$label,$number,$url]): ?>
        <a class="stat dashboard-stat" href="<?= e($url) ?>"><strong><?= (int)$number ?></strong><span><?= e($label) ?></span></a>
    <?php endforeach; ?>
</div>
<section class="panel dashboard-shortcuts">
    <h2>Thao tác quản trị</h2>
    <div class="actions">
        <a class="button primary" href="tai-khoan.php?new=1">Thêm tài khoản</a>
        <a class="button" href="tai-san.php?new=1">Thêm tài sản</a>
        <a class="button" href="danh-muc.php?kind=phong-truc&amp;new=1">Thêm phòng trực</a>
        <a class="button" href="danh-muc.php?kind=phong-hoc&amp;new=1">Thêm phòng học</a>
    </div>
</section>
<div class="dashboard-columns">
    <section class="panel">
        <h2>Thông tin tài sản</h2><small>Tình trạng của tài sản trong hệ thống</small>
        <div class="dashboard-donut" role="img" aria-label="<?= $conditions['good'] ?> tốt, <?= $conditions['damaged'] ?> hư hỏng, <?= $conditions['missing'] ?> mất" style="background:<?= e($donut) ?>">
            <div><strong><?= $assetsTotal ?></strong><small>tài sản</small></div>
        </div>
        <div class="dashboard-breakdown">
            <span>Tốt</span><b><?= $conditions['good'] ?></b>
            <span>Hư hỏng</span><b><?= $conditions['damaged'] ?></b>
            <span>Mất</span><b><?= $conditions['missing'] ?></b>
        </div>
    </section>
    <section class="panel">
        <h2>Danh mục đang hoạt động</h2><small>Số bản ghi có thể dùng khi thêm tài sản và phân công</small>
        <?php foreach ([
            ['Tài khoản',$counts['accounts']['active'],$counts['accounts']['total'],'tai-khoan.php'],
            ['Tài sản',$counts['assets']['active'],$counts['assets']['total'],'tai-san.php'],
            ['Phòng trực',$counts['desks']['active'],$counts['desks']['total'],'danh-muc.php?kind=phong-truc'],
            ['Phòng học',$counts['rooms']['active'],$counts['rooms']['total'],'danh-muc.php?kind=phong-hoc'],
        ] as [$label,$active,$total,$url]): ?>
            <a class="dashboard-active-row" href="<?= e($url) ?>"><span><?= e($label) ?></span><strong><?= (int)$active ?> / <?= (int)$total ?></strong></a>
        <?php endforeach; ?>
    </section>
</div>
<section class="panel">
    <div class="card-title"><div><h2>Tài sản cập nhật gần đây</h2><small>Thông tin quản lý của tối đa 8 tài sản</small></div><a class="button" href="tai-san.php">Xem tất cả tài sản</a></div>
    <div class="scroll"><table class="dashboard-recent-table"><thead><tr><th>Mã / Tên tài sản</th><th>Loại</th><th>Phòng trực</th><th>Tình trạng</th><th>Cập nhật</th><th>Thao tác</th></tr></thead><tbody>
        <?php foreach ($recent as $item): ?><tr>
            <td><strong><?= e($item['ma_tai_san']) ?></strong><small><?= e($item['ten_tai_san']) ?></small></td>
            <td><?= e($item['ten_loai']) ?></td><td><?= e($item['ten_phong_truc']) ?></td>
            <td><?= e(ASSET_CONDITIONS[$item['tinh_trang']]) ?></td>
            <td><?= e($item['cap_nhat_luc']) ?></td>
            <td><a class="button" href="tai-san.php?id=<?= (int)$item['id'] ?>&amp;edit=1">Sửa</a></td>
        </tr><?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="6">Chưa có tài sản.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
