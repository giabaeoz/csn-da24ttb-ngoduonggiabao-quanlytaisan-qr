<?php
/**
 * Thành phần hiển thị thông báo tạm thời (Flash Message Notification).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$thongBao = lay_thong_bao();
if ($thongBao !== null):
    $loaiClass = match ($thongBao['loai']) {
        'thanh_cong' => 'alert-success',
        'loi'        => 'alert-danger',
        'canh_bao'   => 'alert-warning',
        default      => 'alert-primary',
    };
    $bieuTuong = match ($thongBao['loai']) {
        'thanh_cong' => 'bi-check-circle-fill',
        'loi'        => 'bi-exclamation-triangle-fill',
        'canh_bao'   => 'bi-exclamation-circle-fill',
        default      => 'bi-info-circle-fill',
    };
?>
<div class="alert <?= $loaiClass ?> alert-dismissible fade show shadow-sm mb-4" role="alert" id="thong-bao-he-thong">
    <div class="d-flex align-items-center">
        <div><?= e($thongBao['noi_dung']) ?></div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
</div>
<?php endif; ?>
