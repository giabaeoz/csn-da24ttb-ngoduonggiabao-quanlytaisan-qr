<?php
/**
 * Giao diện Chi tiết Thiết bị, Xem Mã QR và Truy vết Lịch sử.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">🔍 Chi tiết Thiết bị & Mã QR</h1>
        <p class="text-secondary small mb-0">Xem toàn bộ thông số kỹ thuật, tem mã QR và lịch sử sử dụng của thiết bị.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('tai-san/in-qr.php?id=' . (int)$taiSan['id']) ?>" class="btn btn-dark fw-semibold shadow-sm" target="_blank">
            🖨️ In tem mã QR
        </a>
        <?php if (co_quyen('quan_ly_tai_san')): ?>
        <a href="<?= url('tai-san/sua.php?id=' . (int)$taiSan['id']) ?>" class="btn btn-outline-primary fw-semibold">
            ✏️ Sửa thông tin
        </a>
        <?php endif; ?>
        <a href="<?= url('tai-san/index.php') ?>" class="btn btn-outline-secondary fw-semibold">
            ← Quay lại
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Cột trái: Thẻ hiển thị Tem mã QR -->
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 text-center p-4 bg-white">
            <h5 class="fw-bold text-primary mb-3">TEM ĐỊNH DANH QR</h5>
            
            <div class="d-flex justify-content-center mb-3">
                <div id="qrcode" class="p-3 bg-white border rounded-3 shadow-sm" style="display: inline-block;"></div>
            </div>

            <div class="font-monospace fs-5 fw-bold text-dark mb-1">
                <?= e($taiSan['ma_tai_san']) ?>
            </div>
            <div class="font-monospace text-muted small mb-3">
                <?= e($taiSan['ma_qr']) ?>
            </div>

            <div class="border-top pt-3 small text-secondary">
                <div>Bàn trực: <strong><?= e($taiSan['ten_phong_truc']) ?></strong></div>
                <?php if (!empty($taiSan['ma_phong_phu_hop'])): ?>
                    <div class="text-primary fw-semibold mt-1">
                        🏷️ Gợi ý vị trí tủ: Phòng <?= e($taiSan['ma_phong_phu_hop']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cột phải: Thông số chi tiết của thiết bị -->
    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h4 class="fw-bold text-dark mb-0"><?= e($taiSan['ten_tai_san']) ?></h4>
                <div>
                    <?php
                        $badgeClass = match ($taiSan['trang_thai']) {
                            'SAN_SANG'      => 'bg-success',
                            'DANG_MUON'     => 'bg-warning text-dark',
                            'BAO_TRI'       => 'bg-danger',
                            'NGUNG_SU_DUNG' => 'bg-secondary',
                            default         => 'bg-secondary',
                        };
                    ?>
                    <span class="badge <?= $badgeClass ?> fs-6 px-3 py-2">
                        <?= e(TEN_TRANG_THAI[$taiSan['trang_thai']] ?? $taiSan['trang_thai']) ?>
                    </span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-sm-6">
                    <span class="text-muted small">Chủng loại:</span>
                    <div class="fw-bold text-dark"><?= e($taiSan['ten_loai']) ?> (<?= e($taiSan['ma_loai']) ?>)</div>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">Tình trạng vật lý:</span>
                    <div>
                        <span class="badge bg-light text-dark border">
                            <?= e(TEN_TINH_TRANG[$taiSan['tinh_trang']] ?? $taiSan['tinh_trang']) ?>
                        </span>
                    </div>
                </div>

                <div class="col-sm-6">
                    <span class="text-muted small">Hãng sản xuất:</span>
                    <div class="fw-bold text-primary"><?= e($taiSan['hang_san_xuat'] ?: 'Chưa cập nhật') ?></div>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">Model kỹ thuật:</span>
                    <div class="fw-bold font-monospace"><?= e($taiSan['model'] ?: 'Chưa cập nhật') ?></div>
                </div>

                <div class="col-sm-6">
                    <span class="text-muted small">Vị trí hiện tại:</span>
                    <div>
                        <?php if (!empty($taiSan['ma_phong_hien_tai'])): ?>
                            <span class="badge bg-warning text-dark fw-bold">
                                🏫 Đang dùng tại phòng <?= e($taiSan['ma_phong_hien_tai']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-success border">
                                🏠 Đang ở bàn trực <?= e($taiSan['ma_phong_truc']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-sm-6">
                    <span class="text-muted small">Phạm vi tương thích:</span>
                    <div>
                        <span class="badge bg-info bg-opacity-10 text-info fw-semibold">
                            <?= e(TEN_PHAM_VI_TUONG_THICH[$taiSan['pham_vi_tuong_thich']] ?? $taiSan['pham_vi_tuong_thich']) ?>
                        </span>
                    </div>
                </div>

                <div class="col-sm-6">
                    <span class="text-muted small">Ngày đưa vào sử dụng:</span>
                    <div class="fw-semibold"><?= dinh_dang_ngay($taiSan['ngay_dua_vao_su_dung']) ?></div>
                </div>

                <div class="col-12 border-top pt-2 mt-2">
                    <span class="text-muted small">Mô tả quy cách / ghi chú:</span>
                    <div class="text-secondary"><?= e($taiSan['mo_ta'] ?: 'Không có ghi chú.') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LỊCH SỬ MƯỢN TRẢ & SỰ CỐ -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="fw-bold mb-0 text-dark">📋 Lịch sử mượn trả gần nhất</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3">Mã phiếu</th>
                    <th>Người mượn</th>
                    <th>Phòng học</th>
                    <th>Thời gian mượn</th>
                    <th>Hạn trả</th>
                    <th>Thời gian trả</th>
                    <th class="text-center">Tình trạng trả</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lichSuMuon)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        Chưa có lượt mượn nào được ghi nhận cho thiết bị này.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($lichSuMuon as $muon): ?>
                <tr>
                    <td class="ps-3 font-monospace fw-bold text-primary">
                        <?= e($muon['ma_phieu']) ?>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= e($muon['ten_nguoi_muon']) ?></div>
                        <div class="text-muted small"><?= e($muon['ma_so_truong']) ?></div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= e($muon['ma_phong_hoc']) ?>
                        </span>
                    </td>
                    <td><?= dinh_dang_ngay_gio($muon['thoi_gian_muon']) ?></td>
                    <td><?= dinh_dang_ngay_gio($muon['han_tra']) ?></td>
                    <td>
                        <?php if ($muon['thoi_gian_tra'] !== null): ?>
                            <span class="text-success fw-semibold"><?= dinh_dang_ngay_gio($muon['thoi_gian_tra']) ?></span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Đang mượn</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($muon['tinh_trang_khi_tra'] === 'HU_HONG'): ?>
                            <span class="badge bg-danger">Hư hỏng</span>
                        <?php elseif ($muon['tinh_trang_khi_tra'] === 'TOT'): ?>
                            <span class="badge bg-success">Tốt</span>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Nạp thư viện tạo mã QR offline -->
<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Sinh mã QR cục bộ offline
    const maQr = <?= json_encode($taiSan['ma_qr']) ?>;
    new QRCode(document.getElementById('qrcode'), {
        text: maQr,
        width: 160,
        height: 160,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>

