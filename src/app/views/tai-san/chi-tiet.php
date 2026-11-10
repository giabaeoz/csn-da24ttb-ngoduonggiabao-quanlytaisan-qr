<?php
/**
 * Giao diện Chi tiết Thiết bị, Xem Mã QR và Truy vết Lịch sử - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var array<string, mixed> $taiSan
 * @var list<array<string, mixed>> $lichSuMuon
 */

$tieuDeTrang = 'Chi tiết thiết bị: ' . ($taiSan['ma_tai_san'] ?? '');
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<!-- Tiêu đề trang & Nút tác vụ -->
<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang mb-0">Chi tiết thiết bị: <?= e($taiSan['ma_tai_san']) ?></h1>
        <div class="mo-ta-phu">Thông số kỹ thuật, tem mã QR và lịch sử sử dụng của thiết bị.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('tai-san/in-qr.php?id=' . (int)$taiSan['id']) ?>" class="btn btn-phu" target="_blank">
            <?= icon('printer', 'bieu-tuong-sm') ?>
            <span>In tem QR</span>
        </a>
        <?php if (co_quyen('quan_ly_tai_san')): ?>
        <a href="<?= url('tai-san/sua.php?id=' . (int)$taiSan['id']) ?>" class="btn btn-phu">
            <?= icon('pencil', 'bieu-tuong-sm') ?>
            <span>Sửa thông tin</span>
        </a>
        <?php endif; ?>
        <a href="<?= url('tai-san/index.php') ?>" class="btn btn-phu">
            <?= icon('arrow-left', 'bieu-tuong-sm') ?>
            <span>Quay lại</span>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Cột trái: Thẻ hiển thị Tem mã QR -->
    <div class="col-12 col-md-4">
        <div class="the-noi-dung text-center p-3 h-100">
            <div class="tieu-de-khoi mb-3">Mã QR tài sản</div>
            
            <div class="d-flex justify-content-center mb-3">
                <div id="qrcode" data-qr="<?= e($taiSan['ma_tai_san']) ?>" data-qr-size="160" class="p-2 bg-white border d-inline-block khung-qr-164"></div>
            </div>

            <div class="font-monospace fs-4 fw-bold text-primary mb-2">
                <?= e($taiSan['ma_tai_san']) ?>
            </div>
            <div class="text-muted small mb-3">
                <?= e($taiSan['ten_tai_san']) ?>
            </div>

            <div class="border-top pt-2 small text-secondary">
                <div>Bàn trực: <strong><?= e($taiSan['ten_phong_truc']) ?></strong></div>
                <?php if (!empty($taiSan['ma_phong_phu_hop'])): ?>
                    <div class="text-primary fw-semibold mt-1">
                        Xếp kệ: Phòng <?= e($taiSan['ma_phong_phu_hop']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cột phải: Thông số chi tiết của thiết bị -->
    <div class="col-12 col-md-8">
        <div class="the-noi-dung p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h3 class="tieu-de-khoi mb-0"><?= e($taiSan['ten_tai_san']) ?></h3>
                <div>
                    <?php if ($taiSan['trang_thai'] === 'SAN_SANG'): ?>
                        <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Sẵn sàng</span>
                    <?php elseif ($taiSan['trang_thai'] === 'DANG_MUON'): ?>
                        <span class="huy-hieu-tt huy-hieu-tt-chinh">Đang mượn</span>
                    <?php elseif ($taiSan['trang_thai'] === 'BAO_TRI'): ?>
                        <span class="huy-hieu-tt huy-hieu-tt-canh-bao">Bảo trì</span>
                    <?php else: ?>
                        <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Ngừng sử dụng</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-sm-6">
                    <span class="text-muted small">Chủng loại:</span>
                    <div class="fw-semibold text-dark"><?= e($taiSan['ten_loai']) ?> (<?= e($taiSan['ma_loai']) ?>)</div>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">Tình trạng vật lý:</span>
                    <div>
                        <?php if ($taiSan['tinh_trang'] === 'TOT'): ?>
                            <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Tốt</span>
                        <?php else: ?>
                            <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Hư hỏng</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-sm-6">
                    <span class="text-muted small">Hãng sản xuất:</span>
                    <div class="fw-semibold text-dark"><?= e($taiSan['hang_san_xuat'] ?: 'Chưa cập nhật') ?></div>
                </div>
                <div class="col-sm-6">
                    <span class="text-muted small">Model kỹ thuật:</span>
                    <div class="fw-semibold font-monospace"><?= e($taiSan['model'] ?: 'Chưa cập nhật') ?></div>
                </div>

                <div class="col-sm-6">
                    <span class="text-muted small">Vị trí hiện tại:</span>
                    <div>
                        <?php if (!empty($taiSan['ma_phong_hien_tai'])): ?>
                            <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25">
                                Đang dùng tại phòng <?= e($taiSan['ma_phong_hien_tai']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                Đang ở bàn trực <?= e($taiSan['ma_phong_truc']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-sm-6">
                    <span class="text-muted small">Phạm vi tương thích:</span>
                    <div>
                        <span class="badge bg-light text-secondary border">
                            <?= e(TEN_PHAM_VI_TUONG_THICH[$taiSan['pham_vi_tuong_thich']] ?? $taiSan['pham_vi_tuong_thich']) ?>
                        </span>
                    </div>
                </div>

                <div class="col-sm-6">
                    <span class="text-muted small">Ngày đưa vào sử dụng:</span>
                    <div class="fw-semibold"><?= dinh_dang_ngay($taiSan['ngay_dua_vao_su_dung']) ?></div>
                </div>

                <div class="col-12 border-top pt-2 mt-2">
                    <span class="text-muted small">Mô tả / ghi chú:</span>
                    <div class="text-secondary"><?= e($taiSan['mo_ta'] ?: 'Không có ghi chú.') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lịch sử mượn trả gần nhất -->
<div class="the-noi-dung mb-4">
    <div class="the-noi-dung-dau">
        <h3 class="tieu-de-khoi mb-0">Lịch sử mượn trả gần nhất</h3>
    </div>
    <div class="the-noi-dung-than p-0">
        <div class="table-responsive">
            <table class="bang-du-lieu mb-0">
                <thead>
                    <tr>
                        <th>Mã phiếu</th>
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
                        <td class="font-monospace fw-semibold text-primary">
                            <?= e($muon['ma_phieu']) ?>
                        </td>
                        <td>
                            <div class="fw-medium text-dark"><?= e($muon['ten_nguoi_muon']) ?></div>
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
                                <span class="huy-hieu-tt huy-hieu-tt-canh-bao">Đang mượn</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if ($muon['tinh_trang_khi_tra'] === 'HU_HONG'): ?>
                                <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Hư hỏng</span>
                            <?php elseif ($muon['tinh_trang_khi_tra'] === 'TOT'): ?>
                                <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Tốt</span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Nạp thư viện tạo mã QR offline -->
<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script src="<?= url('assets/vendor/qrcode/qrcode_UTF8.js') ?>"></script>
<script src="<?= url('assets/js/qr-tao.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const maQr = <?= json_encode($taiSan['ma_tai_san']) ?>;
    taoMaQr('qrcode', maQr, { size: 160, level: 'M' });
});
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
