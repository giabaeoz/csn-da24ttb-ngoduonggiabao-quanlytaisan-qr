<?php
/**
 * Giao diện Bảng điều khiển Tổng quan - Khớp chính xác Trang tổng quan - Dashboard.pdf
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var array<string, int> $thongKe
 * @var array<string, int> $kpi
 * @var array<string, mixed> $muonTra7Ngay
 * @var array<string, mixed> $nhomTaiSan
 * @var list<array<string, mixed>> $phieuTheoDoi
 * @var array<string, mixed> $nguoiDung
 */

$tieuDeTrang = 'Tổng quan';
require __DIR__ . '/layout/dau-trang.php';

// Tính toán các tỷ lệ trạng thái
$tongTs = max(1, (int)($thongKe['tong_so'] ?? 0));
$coTheMuon = (int)($thongKe['san_sang'] ?? 0);
$dangMuon = (int)($thongKe['dang_muon'] ?? 0);
$trangThaiKhac = max(0, $tongTs - $coTheMuon - $dangMuon);

$pctCoTheMuon = round(($coTheMuon / $tongTs) * 100, 1);
$pctDangMuon = round(($dangMuon / $tongTs) * 100, 1);
$pctKhac = round(($trangThaiKhac / $tongTs) * 100, 1);

$tongNhomMuon = array_sum($nhomTaiSan['data'] ?? [0]);
?>

<!-- Tiêu đề trang & Lời chào cán bộ -->
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang">Tổng quan</h1>
        <div class="mo-ta-phu">Tình hình tài sản và mượn – trả tại phòng trực.</div>
    </div>
    <div class="d-flex align-items-center gap-3 text-end">
        <div>
            <div class="fw-semibold text-dark">Xin chào "<?= e($nguoiDung['ho_ten'] ?? 'Nhân viên phòng trực') ?>"</div>
            <div class="mo-ta-phu">Hôm nay ngày <?= date('d/m/Y') ?></div>
        </div>
        <button class="btn btn-phu">
            Tuần này
        </button>
    </div>
</div>

<!-- 4 Thẻ số liệu chính -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu"><?= sprintf('%02d', (int)($thongKe['tong_so'] ?? 0)) ?></div>
            <div class="nhan-so-lieu">Tổng tài sản</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu"><?= sprintf('%02d', (int)($thongKe['san_sang'] ?? 0)) ?></div>
            <div class="nhan-so-lieu">Tài sản có thể mượn</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu"><?= sprintf('%02d', (int)($thongKe['dang_muon'] ?? 0)) ?></div>
            <div class="nhan-so-lieu">Tài sản đang mượn</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu text-danger"><?= sprintf('%02d', (int)($kpi['phieu_qua_han'] ?? 0)) ?></div>
            <div class="nhan-so-lieu">Lượt mượn quá hạn</div>
        </div>
    </div>
</div>

<!-- Thao tác nhanh -->
<div class="card mb-4">
    <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-phu fw-semibold me-2">Thao tác nhanh</span>
        <a href="<?= url('muon-tra/muon.php') ?>" class="btn btn-phu">Lập phiếu mượn</a>
        <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-phu">Nhận trả tài sản</a>
        <a href="<?= url('tai-san/index.php') ?>" class="btn btn-phu">Quét mã QR</a>
    </div>
</div>

<!-- Biểu đồ: Xu hướng mượn – trả trong tuần -->
<div class="card mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="tieu-de-khoi">Xu hướng mượn – trả trong tuần</h2>
                <div class="mo-ta-phu">Đơn vị: lượt giao dịch • Dữ liệu thời gian thực</div>
            </div>
            <div class="d-flex align-items-center gap-3 small">
                <span class="d-flex align-items-center gap-1">
                    <span style="display:inline-block;width:16px;height:2px;background-color:#1E4E8C;"></span> Mượn
                </span>
                <span class="d-flex align-items-center gap-1">
                    <span style="display:inline-block;width:16px;height:2px;border-top:2px dashed #6B7280;"></span> Trả
                </span>
            </div>
        </div>
        <div style="height: 220px; position: relative;">
            <canvas id="chartDashboardXuHuong"></canvas>
        </div>
    </div>
</div>

<!-- Hàng 2 Biểu đồ: Nhóm tài sản & Tình trạng tài sản -->
<div class="row g-4 mb-4">
    <!-- Cột trái: Lượt mượn theo nhóm tài sản -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4 d-flex flex-column">
                <h2 class="tieu-de-khoi">Lượt mượn theo nhóm tài sản</h2>
                <div class="mo-ta-phu mb-3">Cùng thời gian • Đơn vị: lượt</div>
                <div style="height: 200px; position: relative;" class="flex-grow-1">
                    <canvas id="chartDashboardNhomTaiSan"></canvas>
                </div>
                <div class="mo-ta-phu text-end mt-2">Tổng: <?= (int)$tongNhomMuon ?> lượt mượn</div>
            </div>
        </div>
    </div>

    <!-- Cột phải: Tình trạng tài sản -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <h2 class="tieu-de-khoi">Tình trạng tài sản</h2>
                <div class="mo-ta-phu mb-3">Cơ cấu <?= (int)($thongKe['tong_so'] ?? 0) ?> tài sản</div>
                <div class="row align-items-center">
                    <div class="col-sm-6 text-center">
                        <div style="width: 170px; height: 170px; margin: 0 auto; position: relative;">
                            <canvas id="chartDashboardTinhTrang"></canvas>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; pointer-events: none;">
                                <div class="fw-bold fs-5 text-dark"><?= (int)($thongKe['tong_so'] ?? 0) ?></div>
                                <div class="mo-ta-phu" style="font-size: 11px;">tài sản</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 mt-3 mt-sm-0">
                        <div class="d-flex flex-column gap-2 small">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><span style="display:inline-block;width:10px;height:10px;background-color:#1E7A46;border-radius:2px;margin-right:6px;"></span>Có thể mượn</span>
                                <span class="fw-semibold"><?= $coTheMuon ?> • <?= $pctCoTheMuon ?>%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span><span style="display:inline-block;width:10px;height:10px;background-color:#1E4E8C;border-radius:2px;margin-right:6px;"></span>Đang mượn</span>
                                <span class="fw-semibold"><?= $dangMuon ?> • <?= $pctDangMuon ?>%</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span><span style="display:inline-block;width:10px;height:10px;background-color:#6B7280;border-radius:2px;margin-right:6px;"></span>Trạng thái khác</span>
                                <span class="fw-semibold"><?= $trangThaiKhac ?> • <?= $pctKhac ?>%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bảng: Lượt mượn cần theo dõi -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="tieu-de-khoi mb-0">Lượt mượn cần theo dõi</h2>
        </div>
        <a href="<?= url('muon-tra/index.php') ?>" class="btn btn-phu btn-sm">
            Xem tất cả
        </a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Người mượn</th>
                    <th>Tài sản</th>
                    <th>Hạn trả</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($phieuTheoDoi)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-phu">Không có lượt mượn nào cần theo dõi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($phieuTheoDoi as $p): 
                        $laQuaHan = (strtotime($p['han_tra']) < time());
                        $laHomNay = (date('Y-m-d', strtotime($p['han_tra'])) === date('Y-m-d'));
                    ?>
                        <tr>
                            <td class="fw-semibold text-primary">
                                <?= e($p['ma_phieu_muon']) ?>
                            </td>
                            <td><?= e($p['ho_ten_nguoi_muon']) ?></td>
                            <td><?= e($p['ten_cac_thiet_bi'] ?? ($p['so_thiet_bi'] . ' thiết bị')) ?></td>
                            <td>
                                <?= ($laHomNay ? 'Hôm nay, ' : '') . date('H:i', strtotime($p['han_tra'])) ?>
                                <?php if (!$laHomNay): ?>
                                    <span class="text-phu">(<?= date('d/m', strtotime($p['han_tra'])) ?>)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($laQuaHan): ?>
                                    <?php $ma = 'QUA_HAN'; $nhan = 'Quá hạn'; require __DIR__ . '/partials/huy-hieu-trang-thai.php'; ?>
                                <?php elseif ($laHomNay): ?>
                                    <?php $ma = 'DEN_HAN_HOM_NAY'; $nhan = 'Đến hạn hôm nay'; require __DIR__ . '/partials/huy-hieu-trang-thai.php'; ?>
                                <?php else: ?>
                                    <?php $ma = 'DANG_MUON'; $nhan = 'Đang mượn'; require __DIR__ . '/partials/huy-hieu-trang-thai.php'; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php if ($laQuaHan || $laHomNay): ?>
                                    <a href="<?= url('muon-tra/tra.php?ma_phieu=' . urlencode($p['ma_phieu_muon'])) ?>" class="btn btn-phu btn-sm">
                                        Nhận trả
                                    </a>
                                <?php else: ?>
                                    <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$p['id']) ?>" class="btn btn-phu btn-sm">
                                        Chi tiết
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SCRIPTS KHỞI TẠO 3 BIỂU ĐỒ CHART.JS -->
<script src="<?= url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Line Chart: Xu hướng mượn - trả trong tuần
    const ctxXh = document.getElementById('chartDashboardXuHuong').getContext('2d');
    new Chart(ctxXh, {
        type: 'line',
        data: {
            labels: <?= json_encode($muonTra7Ngay['labels'], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: 'Mượn',
                    data: <?= json_encode($muonTra7Ngay['so_luot_muon']) ?>,
                    borderColor: '#1E4E8C',
                    backgroundColor: 'rgba(30, 78, 140, 0.05)',
                    borderWidth: 2,
                    pointBackgroundColor: '#1E4E8C',
                    pointRadius: 3,
                    fill: false,
                    tension: 0.2
                },
                {
                    label: 'Trả',
                    data: <?= json_encode($muonTra7Ngay['so_luot_tra']) ?>,
                    borderColor: '#6B7280',
                    borderDash: [5, 5],
                    borderWidth: 2,
                    pointBackgroundColor: '#6B7280',
                    pointRadius: 3,
                    fill: false,
                    tension: 0.2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#E5E7EB' }, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Bar Chart: Lượt mượn theo nhóm tài sản
    const ctxNhom = document.getElementById('chartDashboardNhomTaiSan').getContext('2d');
    new Chart(ctxNhom, {
        type: 'bar',
        data: {
            labels: <?= json_encode($nhomTaiSan['labels'], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode($nhomTaiSan['data']) ?>,
                backgroundColor: '#6B7280',
                borderRadius: 4,
                maxBarThickness: 36
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#E5E7EB' }, ticks: { precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });

    // 3. Doughnut Chart: Tình trạng tài sản
    const ctxTt = document.getElementById('chartDashboardTinhTrang').getContext('2d');
    new Chart(ctxTt, {
        type: 'doughnut',
        data: {
            labels: ['Có thể mượn', 'Đang mượn', 'Trạng thái khác'],
            datasets: [{
                data: [<?= $coTheMuon ?>, <?= $dangMuon ?>, <?= $trangThaiKhac ?>],
                backgroundColor: ['#1E7A46', '#1E4E8C', '#6B7280'],
                borderWidth: 2,
                borderColor: '#FFFFFF'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '72%'
        }
    });
});
</script>

<?php require __DIR__ . '/layout/chan-trang.php'; ?>
