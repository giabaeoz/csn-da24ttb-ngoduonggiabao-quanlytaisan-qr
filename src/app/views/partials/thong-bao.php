<?php
/**
 * Thành phần hiển thị thông báo tạm thời (Flash Message Notification).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/icon.php';

$thongBao = lay_thong_bao();
if ($thongBao !== null):
    $laThanhCong = ($thongBao['loai'] === 'thanh_cong');
    $loaiClass = $laThanhCong ? 'alert-success thong-bao-xanh' : 'alert-danger thong-bao-do';
    $bieuTuong = $laThanhCong ? 'check-circle-2' : 'alert-circle';
    $noiDungHienThi = trim(strip_tags((string)($thongBao['noi_dung'] ?? '')));
?>
<div class="alert <?= $loaiClass ?> thong-bao-tha-xuong alert-dismissible fade show shadow mb-3"
     role="alert"
     id="thong-bao-he-thong"
     data-loai="<?= e($laThanhCong ? 'thanh_cong' : 'loi') ?>"
     data-thoi-gian="<?= e((string)($thongBao['thoi_gian'] ?? date('H:i • d/m/Y'))) ?>">
    <div class="d-flex align-items-center gap-2 flex-grow-1">
        <?= icon($bieuTuong, 'bieu-tuong-md text-white flex-shrink-0') ?>
        <div class="fw-semibold text-white"><?= e($noiDungHienThi) ?></div>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Đóng"></button>
</div>
<?php endif; ?>

