<?php
/**
 * Thành phần giao diện Ô quét mã QR dùng chung (Camera Scanner & Manual Input).
 * Sử dụng trong: Mượn, Trả, Điều chuyển, Kiểm kê.
 *
 * @var string $tieuDeOQuet Tiêu đề khung quét (mặc định: 'Quét mã QR')
 * @var string $placeholderNhaptay Placeholder ô nhập tay
 * @var string $idInputTay ID ô nhập tay (mặc định: 'ma_quet_nhap')
 * @var string $idBtnThem ID nút thêm (mặc định: 'btnThemThietBi')
 * @var string $tenHamXuLy Tên hàm JS xử lý khi có mã (mặc định: 'xuLyMaQuet')
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var string|null $tieuDeOQuet
 * @var string|null $placeholderNhaptay
 * @var string|null $idInputTay
 * @var string|null $idBtnThem
 * @var string|null $tenHamXuLy
 */

$tieuDeOQuet = $tieuDeOQuet ?? 'Quét mã QR bằng Camera hoặc Nhập tay';
$placeholderNhaptay = $placeholderNhaptay ?? 'Quét tem QR hoặc nhập mã thiết bị (MC-0001, QR-D6-MC0001)...';
$idInputTay = $idInputTay ?? 'ma_quet_nhap';
$idBtnThem = $idBtnThem ?? 'btnThemThietBi';
$tenHamXuLy = $tenHamXuLy ?? 'xuLyMaQuet';
?>

<div class="card border-0 shadow-sm rounded-4 bg-white mb-3">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            📷 <?= e($tieuDeOQuet) ?>
        </h6>
        <button type="button" class="btn btn-sm btn-outline-success fw-semibold" id="btnToggleCamera" onclick="chuyenDoiCamera()">
            📹 Bật Camera quét QR
        </button>
    </div>
    <div class="card-body p-3">
        <!-- Khung Camera quét QR -->
        <div id="khuVucCamera" class="d-none text-center mb-3 p-3 bg-dark rounded-4">
            <div class="position-relative d-inline-block">
                <video id="videoQuet" playsinline style="max-width: 100%; max-height: 250px; border-radius: 8px;"></video>
                <canvas id="canvasQuet" class="d-none"></canvas>
                <div class="position-absolute top-50 start-50 translate-middle border border-2 border-success rounded-3" 
                     style="width: 180px; height: 180px; pointer-events: none; box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.45);">
                </div>
            </div>
            <div class="text-white small mt-2">
                Hướng camera về phía mã QR để hệ thống nhận diện tự động...
            </div>
        </div>

        <!-- Ô nhập tay / hỗ trợ máy quét Barcode USB -->
        <div class="input-group">
            <input type="text" class="form-control font-monospace fs-6" id="<?= e($idInputTay) ?>" 
                   placeholder="<?= e($placeholderNhaptay) ?>" autocomplete="off">
            <button type="button" class="btn btn-primary fw-semibold px-4" id="<?= e($idBtnThem) ?>" onclick="<?= e($tenHamXuLy) ?>()">
                + Nhập mã
            </button>
        </div>
        <div id="thongBaoQuetLoi" class="text-danger small fw-semibold mt-1 d-none"></div>
    </div>
</div>

