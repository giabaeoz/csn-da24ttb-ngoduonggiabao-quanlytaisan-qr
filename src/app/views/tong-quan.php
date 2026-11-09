<?php
/**
 * Giao diện Bảng điều khiển Tổng quan (Dashboard Frame) tích hợp biểu đồ Chart.js (G10).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Tổng quan hệ thống';
require __DIR__ . '/layout/dau-trang.php';
require __DIR__ . '/layout/menu.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <h1 class="h3 fw-bold text-dark mb-0">Bảng điều khiển Tổng quan</h1>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2 align-items-center">
        <?php if (co_quyen('xem_thong_ke')): ?>
            <a href="<?= url('thong-ke/index.php') ?>" class="btn btn-outline-primary btn-sm shadow-sm fw-semibold">
                <i class="bi bi-bar-chart-line me-1"></i> Báo cáo chuyên sâu
            </a>
        <?php endif; ?>
        <span class="badge bg-primary fs-6 px-3 py-2 shadow-sm">
            <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y') ?>
        </span>
    </div>
</div>

<!-- Lời chào cán bộ đang trực -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h4 class="fw-bold text-primary mb-1">
                    Xin chào, <?= e($nguoiDung['ho_ten']) ?>! 👋
                </h4>
                <p class="text-secondary mb-0">
                    Vai trò hiện tại: <strong><?= e(TEN_VAI_TRO[$nguoiDung['vai_tro']] ?? $nguoiDung['vai_tro']) ?></strong>
                    <?php if (!empty($nguoiDung['ten_phong_truc'])): ?>
                        &bull; Phụ trách: <strong><?= e($nguoiDung['ten_phong_truc']) ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <?php if (co_quyen('nghiep_vu_muon_tra')): ?>
                <a href="<?= url('muon-tra/muon.php') ?>" class="btn btn-primary fw-semibold shadow-sm">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Tạo phiếu mượn QR
                </a>
                <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-success fw-semibold shadow-sm">
                    <i class="bi bi-box-arrow-in-left me-1"></i> Nhận trả thiết bị
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Khung thống kê nhanh 4 thẻ số liệu -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= url('tai-san/index.php') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded-3 p-3 fs-3 me-3">
                            💻
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold">Tổng tài sản</div>
                            <h4 class="fw-bold mb-0 text-dark"><?= (int)($thongKe['tong_so'] ?? 0) ?></h4>
                            <small class="text-muted">Khai thác: <?= $kpi['ty_le_su_dung'] ?? 0 ?>%</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= url('tai-san/index.php?trang_thai=SAN_SANG') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded-3 p-3 fs-3 me-3">
                            ✅
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold">Đang sẵn sàng</div>
                            <h4 class="fw-bold mb-0 text-success"><?= (int)($thongKe['san_sang'] ?? 0) ?></h4>
                            <small class="text-muted">Phục vụ giảng dạy</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= url('tai-san/index.php?trang_thai=DANG_MUON') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-warning bg-opacity-10 text-warning rounded-3 p-3 fs-3 me-3">
                            📤
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold">Đang được mượn</div>
                            <h4 class="fw-bold mb-0 text-warning"><?= (int)($thongKe['dang_muon'] ?? 0) ?></h4>
                            <small class="text-muted"><?= (int)($kpi['phieu_dang_muon'] ?? 0) ?> phiếu mượn</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= url('su-co/index.php') ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 bg-danger bg-opacity-10 text-danger rounded-3 p-3 fs-3 me-3">
                            ⚠️
                        </div>
                        <div>
                            <div class="text-muted small fw-semibold">Bảo trì / Sự cố</div>
                            <h4 class="fw-bold mb-0 text-danger"><?= (int)($thongKe['bao_tri_hu_hong'] ?? 0) ?></h4>
                            <small class="text-danger"><?= (int)($kpi['su_co_chua_xu_ly'] ?? 0) ?> sự cố chờ xử lý</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- BIỂU ĐỒ TRỰC QUAN CHART.JS (7 NGÀY GẦN NHẤT & CƠ CẤU TRẠNG THÁI) -->
<div class="row g-4 mb-4">
    <!-- Cột trái: Biểu đồ xu hướng mượn trả 7 ngày -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up text-primary"></i> Xu hướng Mượn & Trả (7 ngày gần nhất)
                </h6>
                <span class="badge bg-light text-secondary border small">Thời gian thực</span>
            </div>
            <div class="card-body p-3">
                <div style="height: 260px; position: relative;">
                    <canvas id="chartDashboardMuonTra"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Cột phải: Biểu đồ trạng thái thiết bị -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart text-success"></i> Tỷ lệ trạng thái thiết bị
                </h6>
            </div>
            <div class="card-body p-3 d-flex flex-column justify-content-center align-items-center">
                <div style="width: 190px; height: 190px; position: relative;">
                    <canvas id="chartDashboardTrangThai"></canvas>
                </div>
                <div class="mt-3 w-100">
                    <div class="row g-1 text-center small">
                        <div class="col-6"><span class="badge bg-success w-100 py-1">Sẵn sàng: <?= (int)$taiSanTrangThai['data'][0] ?></span></div>
                        <div class="col-6"><span class="badge bg-warning text-dark w-100 py-1">Đang mượn: <?= (int)$taiSanTrangThai['data'][1] ?></span></div>
                        <div class="col-6"><span class="badge bg-danger w-100 py-1">Bảo trì: <?= (int)$taiSanTrangThai['data'][2] ?></span></div>
                        <div class="col-6"><span class="badge bg-secondary w-100 py-1">Ngừng SD: <?= (int)$taiSanTrangThai['data'][3] ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HOẠT ĐỘNG MƯỢN TRẢ GẦN ĐÂY -->
<div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-secondary"></i> Phiếu mượn thiết bị gần đây
        </h6>
        <a href="<?= url('muon-tra/index.php') ?>" class="btn btn-sm btn-outline-primary">
            Xem tất cả <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary text-uppercase small">
                <tr>
                    <th style="width: 140px;" class="ps-3">Mã phiếu</th>
                    <th>Người mượn</th>
                    <th style="width: 120px;">Phòng học</th>
                    <th style="width: 110px;">Số thiết bị</th>
                    <th style="width: 150px;">Thời gian mượn</th>
                    <th style="width: 130px;">Trạng thái</th>
                    <th style="width: 80px;" class="text-end pe-3">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($hoatDongGanDay)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Chưa có hoạt động mượn trả nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($hoatDongGanDay as $hd): ?>
                        <tr>
                            <td class="ps-3 font-monospace fw-bold text-primary">
                                <a href="<?= url('muon-tra/chi-tiet.php?id=' . $hd['id']) ?>" class="text-decoration-none">
                                    <?= e($hd['ma_phieu_muon']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($hd['ho_ten_nguoi_muon']) ?></div>
                                <small class="text-muted"><?= e($hd['loai_nguoi_muon'] === 'GIANG_VIEN' ? 'Giảng viên' : 'Sinh viên') ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($hd['ma_phong']) ?></span></td>
                            <td><span class="badge bg-secondary"><?= (int)$hd['so_thiet_bi'] ?> thiết bị</span></td>
                            <td class="small text-muted"><?= dinh_dang_ngay_gio($hd['thoi_gian_muon']) ?></td>
                            <td>
                                <?php if ($hd['trang_thai'] === 'DANG_MUON'): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> Đang mượn</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><i class="bi bi-check2 me-1"></i> Đã trả đủ</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <a href="<?= url('muon-tra/chi-tiet.php?id=' . $hd['id']) ?>" class="btn btn-sm btn-outline-primary" title="Xem chi tiết phiếu">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SCRIPTS KHỞI TẠO BIỂU ĐỒ DASHBOARD TỔNG QUAN -->
<script src="<?= url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Line Chart Dashboard: Mượn & Trả 7 ngày
    const ctxM = document.getElementById('chartDashboardMuonTra').getContext('2d');
    new Chart(ctxM, {
        type: 'line',
        data: {
            labels: <?= json_encode($muonTra7Ngay['labels'], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: 'Lượt mượn',
                    data: <?= json_encode($muonTra7Ngay['so_luot_muon']) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                },
                {
                    label: 'Lượt trả',
                    data: <?= json_encode($muonTra7Ngay['so_luot_tra']) ?>,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
        }
    });

    // 2. Doughnut Chart Dashboard: Trạng thái thiết bị
    const ctxT = document.getElementById('chartDashboardTrangThai').getContext('2d');
    new Chart(ctxT, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($taiSanTrangThai['labels'], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode($taiSanTrangThai['data']) ?>,
                backgroundColor: <?= json_encode($taiSanTrangThai['colors']) ?>,
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '65%'
        }
    });
});
</script>

<?php require __DIR__ . '/layout/chan-trang.php'; ?>
