<?php

/**
 * Giao diện: Chi tiết phiếu điều chuyển và in biên bản bàn giao - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var array<string, mixed> $phieu
 */

$tieuDeTrang = 'Phiếu điều chuyển: ' . ($phieu['ma_dieu_chuyen'] ?? '');
require dirname(__DIR__) . '/layout/dau-trang.php';
$dsChiTiet = $phieu['chi_tiet'] ?? [];
$tongThietBi = count($dsChiTiet);
$idsTaiSan = array_column($dsChiTiet, 'tai_san_id');
?>

<!-- Tiêu đề trang & Nút tác vụ -->
<div class="thanh-cong-cu-trang no-print">
    <div>
        <h1 class="tieu-de-trang mb-0">Phiếu điều chuyển: <?= e($phieu['ma_dieu_chuyen']) ?></h1>
        <div class="mo-ta-phu">Biên bản bàn giao và điều chuyển thiết bị giữa các phòng học.</div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= url('dieu-chuyen/index.php') ?>" class="btn btn-phu">
            <?= icon('arrow-left', 'bieu-tuong-sm') ?>
            <span>Quay lại</span>
        </a>
        <?php if (!empty($idsTaiSan)): ?>
            <a href="<?= url('tai-san/in-qr.php?ids=' . implode(',', $idsTaiSan)) ?>" class="btn btn-phu" target="_blank">
                <?= icon('qr-code', 'bieu-tuong-sm') ?>
                <span>In tem QR</span>
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-phu" onclick="window.print();">
            <?= icon('printer', 'bieu-tuong-sm') ?>
            <span>In biên bản</span>
        </button>
        <?php if (co_quyen('tao_dieu_chuyen')): ?>
            <a href="<?= url('dieu-chuyen/them.php') ?>" class="btn btn-chinh">
                <?= icon('plus', 'bieu-tuong-sm') ?>
                <span>Lập phiếu mới</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- KHỐI IN BIÊN BẢN CHÍNH THỨC (HIỂN THỊ KHI IN) -->
<div class="print-only mb-4">
    <div class="row align-items-center border-bottom pb-3 mb-3">
        <div class="col-7 text-center">
            <div class="fw-bold small text-uppercase">TRƯỜNG ĐẠI HỌC CÔNG NGHỆ</div>
            <div class="fw-bold fs-6 text-uppercase">PHÒNG QUẢN TRỊ THIẾT BỊ & CƠ SỞ VẬT CHẤT</div>
            <div class="small fst-italic">Số: ......./BB-ĐCTB</div>
        </div>
        <div class="col-5 text-center">
            <div class="fw-bold small text-uppercase">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
            <div class="fw-bold small">Độc lập - Tự do - Hạnh phúc</div>
            <div class="small fst-italic">Ngày <?= date('d') ?> tháng <?= date('m') ?> năm <?= date('Y') ?></div>
        </div>
    </div>
    <div class="text-center my-3">
        <h4 class="fw-bold text-uppercase mb-1">BIÊN BẢN BÀN GIAO & ĐIỀU CHUYỂN THIẾT BỊ</h4>
        <div class="small text-muted font-monospace">Mã phiếu: <strong><?= e($phieu['ma_dieu_chuyen']) ?></strong></div>
    </div>
</div>

<div class="row g-3">
    <!-- CỘT TRÁI: THÔNG TIN CHI TIẾT PHIẾU -->
    <div class="col-12 col-lg-8">
        <div class="the-noi-dung mb-3">
            <div class="the-noi-dung-dau d-flex justify-content-between align-items-center">
                <h3 class="tieu-de-khoi mb-0">Thông tin phiếu điều chuyển</h3>
                <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Đã hoàn tất</span>
            </div>
            <div class="the-noi-dung-than p-3">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="text-muted small">Thời gian điều chuyển:</label>
                        <div class="fw-semibold text-dark">
                            <?= e(f_ngay_gio($phieu['thoi_gian_dieu_chuyen'])) ?>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <label class="text-muted small">Cán bộ lập phiếu:</label>
                        <div class="fw-semibold text-dark">
                            <?= e($phieu['ho_ten_nguoi_lap']) ?>
                            <span class="text-muted font-monospace">(<?= e($phieu['ma_nhan_vien_lap']) ?>)</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="p-3 bg-light rounded border text-center">
                            <div class="row align-items-center">
                                <div class="col-5">
                                    <div class="small text-muted text-uppercase fw-semibold">Phòng nguồn (Đi)</div>
                                    <div class="fs-5 fw-bold text-dark mt-1">
                                        <?= e($phieu['ma_phong_di']) ?>
                                    </div>
                                    <div class="small text-muted"><?= e($phieu['ten_phong_di']) ?></div>
                                </div>
                                <div class="col-2">
                                    <span class="fs-4 text-primary">&rarr;</span>
                                </div>
                                <div class="col-5">
                                    <div class="small text-muted text-uppercase fw-semibold">Phòng đích (Đến)</div>
                                    <div class="fs-5 fw-bold text-primary mt-1">
                                        <?= e($phieu['ma_phong_den']) ?>
                                    </div>
                                    <div class="small text-muted"><?= e($phieu['ten_phong_den']) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="text-muted small">Lý do điều chuyển:</label>
                        <div class="p-2 bg-light rounded text-dark">
                            <?= e($phieu['ly_do']) ?>
                        </div>
                    </div>

                    <?php if (!empty($phieu['ghi_chu'])): ?>
                        <div class="col-12">
                            <label class="text-muted small">Ghi chú kèm theo:</label>
                            <div class="p-2 bg-light rounded text-dark">
                                <?= e($phieu['ghi_chu']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- BẢNG DANH SÁCH THIẾT BỊ ĐIỀU CHUYỂN -->
        <div class="the-noi-dung mb-4">
            <div class="the-noi-dung-dau d-flex justify-content-between align-items-center">
                <h3 class="tieu-de-khoi mb-0">
                    Danh sách thiết bị chuyển giao
                </h3>
                <span class="badge bg-light text-secondary border"><?= $tongThietBi ?> thiết bị</span>
            </div>
            <div class="the-noi-dung-than p-0">
                <div class="table-responsive">
                    <table class="bang-du-lieu mb-0">
                        <thead>
                            <tr>
                                <th class="text-center cot-40">STT</th>
                                <th class="cot-140">Mã tài sản</th>
                                <th>Tên thiết bị</th>
                                <th class="cot-160">Hãng / Model</th>
                                <th class="text-center cot-120">Tình trạng</th>
                                <th class="text-center cot-130">Vị trí hiện tại</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dsChiTiet)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Không có thiết bị trong phiếu.</td>
                                </tr>
                            <?php else: ?>
                                <?php $stt = 1;
                                foreach ($dsChiTiet as $item): ?>
                                    <tr>
                                        <td class="text-center text-muted small"><?= $stt++ ?></td>
                                        <td>
                                            <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$item['tai_san_id']) ?>"
                                                class="font-monospace fw-semibold text-primary text-decoration-none">
                                                <?= e($item['ma_tai_san']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="fw-medium text-dark"><?= e($item['ten_tai_san']) ?></div>
                                            <div class="small text-muted"><?= e($item['ten_loai_tai_san']) ?></div>
                                        </td>
                                        <td class="small text-muted">
                                            <?= e((string)($item['hang_san_xuat'] ?? '') . ($item['model'] ? ' / ' . $item['model'] : '')) ?: '-' ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">
                                                Tốt
                                            </span>
                                        </td>
                                        <td class="text-center font-monospace fw-semibold text-success">
                                            <?= e($phieu['ma_phong_den']) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CHỮ KÝ XÁC NHẬN KHI IN (PRINT ONLY) -->
        <div class="print-only mt-4 pt-3">
            <div class="row text-center">
                <div class="col-4">
                    <div class="fw-bold small text-uppercase">NGƯỜI LẬP PHIẾU</div>
                    <div class="small text-muted fst-italic">(Ký và ghi rõ họ tên)</div>
                    <div class="khoang-ky-ten-in"></div>
                    <div class="fw-bold"><?= e($phieu['ho_ten_nguoi_lap']) ?></div>
                </div>
                <div class="col-4">
                    <div class="fw-bold small text-uppercase">ĐẠI DIỆN TIẾP NHẬN</div>
                    <div class="small text-muted fst-italic">(Ký và ghi rõ họ tên)</div>
                    <div class="khoang-ky-ten-in"></div>
                    <div class="fw-bold">........................................</div>
                </div>
                <div class="col-4">
                    <div class="fw-bold small text-uppercase">PHÒNG QUẢN TRỊ THIẾT BỊ</div>
                    <div class="small text-muted fst-italic">(Ký, đóng dấu xác nhận)</div>
                    <div class="khoang-ky-ten-in"></div>
                    <div class="fw-bold">........................................</div>
                </div>
            </div>
        </div>
    </div>

    <!-- CỘT PHẢI: MÃ QR PHIẾU & THÔNG TIN TIỆN ÍCH -->
    <div class="col-12 col-lg-4 no-print">
        <div class="the-noi-dung text-center p-3 mb-3">
            <h3 class="tieu-de-khoi mb-3">Mã QR phiếu điều chuyển</h3>
            <div class="d-flex justify-content-center mb-3">
                <div id="transfer_qr" data-qr="<?= e($phieu['ma_dieu_chuyen']) ?>" data-qr-size="160"
                    class="p-2 bg-white border khung-qr-164"></div>
            </div>
            <div class="font-monospace fw-semibold text-primary fs-6 mb-1"><?= e($phieu['ma_dieu_chuyen']) ?></div>
            <div class="mo-ta-phu mb-0">Quét mã QR này để tra cứu nhanh thông tin phiếu.</div>
        </div>

        <div class="the-noi-dung p-3">
            <h4 class="tieu-de-khoi mb-2">Kiểm soát an toàn</h4>
            <ul class="list-unstyled mo-ta-phu mb-0">
                <li class="mb-2">
                    &bull; Vị trí của <strong><?= $tongThietBi ?></strong> thiết bị đã cập nhật sang <strong>Phòng <?= e($phieu['ma_phong_den']) ?></strong>.
                </li>
                <li class="mb-2">
                    &bull; Bàn trực quản lý tài sản được bảo toàn nguyên vẹn.
                </li>
                <li>
                    &bull; Biên bản bàn giao sẵn sàng để in lưu trữ hồ sơ cơ sở vật chất.
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Nạp thư viện tạo mã QR offline -->
<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script src="<?= url('assets/vendor/qrcode/qrcode_UTF8.js') ?>"></script>
<script src="<?= url('assets/js/qr-tao.js') ?>"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const el = document.getElementById('transfer_qr');
        if (el && typeof taoMaQr === 'function') {
            taoMaQr(el, el.getAttribute('data-qr') || '', {
                size: 160
            });
        }
    });
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>