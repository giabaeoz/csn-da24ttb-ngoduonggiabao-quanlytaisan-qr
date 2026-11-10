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
$placeholderNhaptay = $placeholderNhaptay ?? 'Quét tem QR hoặc nhập mã thiết bị (VD: MC-01, RMC-01, MIC-01)...';
$idInputTay = $idInputTay ?? 'ma_quet_nhap';
$idBtnThem = $idBtnThem ?? 'btnThemThietBi';
$tenHamXuLy = $tenHamXuLy ?? 'xuLyMaQuet';
?>

<div class="card mb-3">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <?= e($tieuDeOQuet) ?>
        </h6>
        <button type="button" class="btn btn-sm btn-phu" id="btnToggleCamera" onclick="chuyenDoiCamera()">
            <?= icon('scan-line', 'bieu-tuong-sm') ?>
            <span>Bật Camera quét QR</span>
        </button>
    </div>
    <div class="card-body p-3">
        <div id="khuVucCamera" class="d-none text-center mb-3">
            <div class="khung-quet-camera">
                <video id="videoQuet" playsinline></video>
                <canvas id="canvasQuet" class="d-none"></canvas>
                <div class="overlay-ngam"></div>
            </div>
            <div class="mo-ta-phu mt-2">
                Hướng camera về phía mã QR để hệ thống nhận diện tự động...
            </div>
        </div>

        <div class="input-group">
            <input type="text" class="form-control font-monospace fs-6" id="<?= e($idInputTay) ?>" 
                   placeholder="<?= e($placeholderNhaptay) ?>" autocomplete="off">
            <button type="button" class="btn btn-chinh px-4" id="<?= e($idBtnThem) ?>" onclick="<?= e($tenHamXuLy) ?>()">
                <?= icon('plus', 'bieu-tuong-sm') ?>
                <span>Nhập mã</span>
            </button>
        </div>
        <div id="thongBaoQuetLoi" class="ket-qua-quet ket-qua-quet-loi mt-2 d-none"></div>
    </div>
</div>

