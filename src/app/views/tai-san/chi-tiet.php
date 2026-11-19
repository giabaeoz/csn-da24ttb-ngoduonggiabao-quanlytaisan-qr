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

<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang mb-0">Chi tiết thiết bị: <?= e($taiSan['ma_tai_san']) ?></h1>

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

<?php $urlAnh = !empty($taiSan['hinh_anh']) ? tai_san_lay_url_anh($taiSan['hinh_anh']) : null; ?>

<div class="row g-3 mb-4 align-items-stretch">
    <!-- Cột Trái: Thẻ Nhận diện & Tem QR -->
    <div class="col-12 col-lg-4">
        <div class="the-noi-dung p-0 overflow-hidden h-100 d-flex flex-column justify-content-between">
            <!-- 1. Ảnh nhận diện thiết bị -->
            <div>
                <div class="position-relative bg-light border-bottom p-3 d-flex align-items-center justify-content-center" style="min-height: 220px; max-height: 240px; background-color: #f8fafc;">
                    <?php if ($urlAnh): ?>
                        <a href="<?= e($urlAnh) ?>" target="_blank" title="Bấm để xem ảnh phóng to" class="d-flex align-items-center justify-content-center w-100 h-100">
                            <img src="<?= e($urlAnh) ?>" alt="<?= e($taiSan['ten_tai_san']) ?>" class="img-fluid rounded shadow-sm" style="max-height: 200px; max-width: 100%; object-fit: contain;">
                        </a>
                        <a href="<?= e($urlAnh) ?>" target="_blank" class="position-absolute bottom-0 end-0 m-2 badge bg-dark bg-opacity-75 text-white text-decoration-none small d-flex align-items-center gap-1">
                            <?= icon('maximize-2', 'bieu-tuong-sm') ?>
                            <span>Phóng to</span>
                        </a>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <div class="text-secondary mb-2"><?= icon('camera', 'bieu-tuong-lg') ?></div>
                            <div class="small fw-medium text-secondary">Chưa có ảnh nhận diện</div>
                            <?php if (co_quyen('quan_ly_tai_san')): ?>
                                <a href="<?= url('tai-san/sua.php?id=' . (int)$taiSan['id']) ?>" class="btn btn-sm btn-link p-0 mt-1 small text-decoration-none">
                                    + Tải ảnh lên
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 2. Khối Mã QR & Tem dán tài sản -->
                <div class="p-3 text-center">
                    <div class="text-secondary small fw-semibold text-uppercase chu-nho-11 mb-2">Tem QR quản lý tài sản</div>
                    <div class="d-flex justify-content-center mb-2">
                        <div id="qrcode" data-qr="<?= e($taiSan['ma_tai_san']) ?>" data-qr-size="130" class="p-2 bg-white border rounded d-inline-block shadow-sm"></div>
                    </div>
                    <div class="font-monospace fs-4 fw-bold text-primary mb-1">
                        <?= e($taiSan['ma_tai_san']) ?>
                    </div>
                    <div class="small text-secondary mb-0">
                        Phòng trực: <strong><?= e($taiSan['ten_phong_truc']) ?></strong>
                        <?php if (!empty($taiSan['ma_phong_phu_hop'])): ?>
                            <br><span class="text-primary fw-semibold">Xếp kệ tủ: Phòng <?= e($taiSan['ma_phong_phu_hop']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Nút in tem QR -->
            <div class="p-3 pt-0 border-top mt-2">
                <a href="<?= url('tai-san/in-qr.php?id=' . (int)$taiSan['id']) ?>" class="btn btn-phu w-100" target="_blank">
                    <?= icon('printer', 'bieu-tuong-sm') ?>
                    <span>In tem QR thiết bị</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Cột Phải: Bảng thông tin chi tiết thiết bị -->
    <div class="col-12 col-lg-8">
        <div class="the-noi-dung p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <!-- Tiêu đề thiết bị & Huy hiệu trạng thái -->
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3 border-bottom pb-3">
                    <div>
                        <span class="badge bg-light text-secondary border font-monospace px-2 py-1 mb-1">
                            <?= e($taiSan['ma_tai_san']) ?>
                        </span>
                        <h2 class="tieu-de-khoi fs-4 mb-0 text-dark fw-bold">
                            <?= e($taiSan['ten_tai_san']) ?>
                        </h2>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <?php if ($taiSan['trang_thai'] === 'SAN_SANG'): ?>
                            <span class="huy-hieu-tt huy-hieu-tt-thanh-cong"><?= icon('check-circle-2', 'bieu-tuong-sm') ?> Sẵn sàng</span>
                        <?php elseif ($taiSan['trang_thai'] === 'DANG_MUON'): ?>
                            <span class="huy-hieu-tt huy-hieu-tt-chinh"><?= icon('clock', 'bieu-tuong-sm') ?> Đang mượn</span>
                        <?php elseif ($taiSan['trang_thai'] === 'BAO_TRI'): ?>
                            <span class="huy-hieu-tt huy-hieu-tt-canh-bao"><?= icon('wrench', 'bieu-tuong-sm') ?> Bảo trì</span>
                        <?php else: ?>
                            <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem"><?= icon('alert-circle', 'bieu-tuong-sm') ?> Ngừng sử dụng</span>
                        <?php endif; ?>

                        <?php if ($taiSan['tinh_trang'] === 'TOT'): ?>
                            <span class="huy-hieu huy-hieu-thanh-cong">
                                Tình trạng: Tốt
                            </span>
                        <?php else: ?>
                            <span class="huy-hieu huy-hieu-nguy-hiem">
                                Tình trạng: Hư hỏng
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Khung Vị trí & Vận hành nổi bật -->
                <div class="p-3 rounded mb-3 border" style="background-color: var(--nen-tieu-de-bang);">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-sm-6">
                            <div class="text-secondary small chu-nho-11 fw-semibold text-uppercase">Vị trí hiện tại:</div>
                            <div class="fw-semibold text-dark mt-1 fs-6">
                                <?php $vtChiTiet = tai_san_thong_tin_vi_tri($taiSan); ?>
                                <?php if ($vtChiTiet['la_dang_muon']): ?>
                                    <span class="text-warning-emphasis">
                                        <?= icon('map-pin', 'bieu-tuong-sm text-warning') ?> Đang mượn tại <strong><?= e($vtChiTiet['ten_hien_thi']) ?></strong>
                                    </span>
                                <?php elseif ($vtChiTiet['la_co_dinh']): ?>
                                    <span class="text-primary-emphasis">
                                        <?= icon('home', 'bieu-tuong-sm text-primary') ?> Cố định tại <strong><?= e($vtChiTiet['ten_hien_thi']) ?></strong>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success">
                                        <?= icon('check-circle-2', 'bieu-tuong-sm text-success') ?> Đang lưu tại <strong><?= e($vtChiTiet['ten_hien_thi']) ?></strong>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="text-secondary small chu-nho-11 fw-semibold text-uppercase">Phạm vi tương thích:</div>
                            <div class="fw-semibold text-dark mt-1 fs-6">
                                <?= icon('layers', 'bieu-tuong-sm text-primary') ?>
                                <?= e(TEN_PHAM_VI_TUONG_THICH[$taiSan['pham_vi_tuong_thich']] ?? $taiSan['pham_vi_tuong_thich']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danh sách thông số kỹ thuật -->
                <div class="row g-3">
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small">Nhóm / Chủng loại:</div>
                        <div class="fw-semibold text-dark mt-1"><?= e($taiSan['ten_loai']) ?> (<?= e($taiSan['ma_loai']) ?>)</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small">Phòng trực quản lý:</div>
                        <div class="fw-semibold text-dark mt-1"><?= e($taiSan['ten_phong_truc']) ?> (<?= e($taiSan['ma_phong_truc']) ?>)</div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small">Hãng sản xuất:</div>
                        <div class="fw-semibold text-dark mt-1"><?= e($taiSan['hang_san_xuat'] ?: 'Chưa cập nhật') ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small">Model kỹ thuật:</div>
                        <div class="fw-semibold font-monospace text-primary mt-1"><?= e($taiSan['model'] ?: 'Chưa cập nhật') ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small">Ngày đưa vào sử dụng:</div>
                        <div class="fw-semibold text-dark mt-1"><?= dinh_dang_ngay($taiSan['ngay_dua_vao_su_dung']) ?></div>
                    </div>
                    <div class="col-12 col-sm-6">
                        <div class="text-muted small">Nhãn kệ tủ xếp đặt:</div>
                        <div class="fw-semibold mt-1">
                            <?php if (!empty($taiSan['ma_phong_phu_hop'])): ?>
                                <span class="huy-hieu huy-hieu-chinh">
                                    Phòng <?= e($taiSan['ma_phong_phu_hop']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-secondary">Linh hoạt theo phân loại</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 border-top pt-3 mt-3">
                        <div class="text-muted small">Mô tả đặc điểm / phụ kiện đi kèm:</div>
                        <div class="text-dark mt-1"><?= nl2br(e($taiSan['mo_ta'] ?: 'Không có ghi chú thêm.')) ?></div>
                    </div>
                </div>
            </div>

            <?php if (co_quyen('quan_ly_tai_san')): ?>
                <div class="border-top pt-3 mt-3 d-flex justify-content-end gap-2">
                    <a href="<?= url('tai-san/sua.php?id=' . (int)$taiSan['id']) ?>" class="btn btn-chinh">
                        <?= icon('pencil', 'bieu-tuong-sm') ?>
                        <span>Chỉnh sửa thông tin thiết bị</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

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
</div>

<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script src="<?= url('assets/vendor/qrcode/qrcode_UTF8.js') ?>"></script>
<script src="<?= url('assets/js/qr-tao.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const maQr = <?= json_encode($taiSan['ma_tai_san']) ?>;
        taoMaQr('qrcode', maQr, {
            size: 130,
            level: 'M'
        });
    });
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>