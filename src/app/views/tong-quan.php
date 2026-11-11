<?php
/**
 * Giao diện Bảng điều khiển Tổng quan (Dashboard) - Khớp 100% Mockup thiết kế
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var array{tong_tai_san: int, co_the_muon: int, dang_muon: int, khac: int, qua_han: int} $soLieu
 * @var array{labels: list<string>, muon: list<int>, tra: list<int>, tong_muon: int, tong_tra: int, tu_ngay: string, den_ngay: string} $xuHuong
 * @var array{labels: list<string>, data: list<int>, tong: int} $theoNhom
 * @var list<array<string, mixed>> $canTheoDoi
 * @var list<array{gia_tri: int, nhan: string, dang_chon: bool}> $danhSachTuan
 * @var array<string, mixed> $nguoiDung
 * @var bool $coQuyenThaoTacNhanh
 * @var float $tyLeCoTheMuon
 * @var float $tyLeDangMuon
 * @var float $tyLeKhac
 */

$tieuDeTrang = 'Tổng quan';
require __DIR__ . '/layout/dau-trang.php';

$tenCanBo = (string)($nguoiDung['ho_ten'] ?? 'Nhân viên phòng trực');
$ngayHienTaiStr = date('d/m/Y');
?>

<div class="thanh-cong-cu-trang align-items-start mb-4">
    <div>
        <h1 class="tieu-de-trang mb-1">Tổng quan</h1>
        <div class="mo-ta-phu">Tình hình tài sản và mượn – trả tại phòng trực.</div>
    </div>

    <div class="d-flex align-items-center flex-wrap gap-4 mt-2 mt-md-0">
        <div class="text-md-end">
            <div class="fw-semibold text-dark">Xin chào “<?= e($tenCanBo) ?>”</div>
            <div class="mo-ta-phu">Hôm nay ngày <?= e($ngayHienTaiStr) ?></div>
        </div>

        <form method="GET" action="<?= url('tong-quan.php') ?>" class="m-0">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0 text-primary">
                    <?= icon('calendar', 'bieu-tuong-sm') ?>
                </span>
                <select name="tuan" class="form-select border-start-0 ps-1 fw-semibold text-dark" onchange="this.form.submit()" aria-label="Chọn tuần thống kê">
                    <?php foreach ($danhSachTuan as $opt): ?>
                        <option value="<?= $opt['gia_tri'] ?>" <?= $opt['dang_chon'] ? 'selected' : '' ?>>
                            <?= e($opt['nhan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="banner-tong-quan mb-4">
    <img src="<?= url('assets/images/QR%20Asset%20Management%20Dashboard%20Banner.png') ?>" alt="Quản lý tài sản bằng mã QR" class="img-fluid w-100 rounded-3">
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-xanh">
            <div class="bieu-tuong-chinh">
                <?= icon('package') ?>
            </div>
            <div>
                <div class="so-lieu"><?= sprintf('%02d', $soLieu['tong_tai_san']) ?></div>
                <div class="nhan-so-lieu">Tổng tài sản</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('package') ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-la">
            <div class="bieu-tuong-chinh">
                <?= icon('check-circle-2') ?>
            </div>
            <div>
                <div class="so-lieu"><?= sprintf('%02d', $soLieu['co_the_muon']) ?></div>
                <div class="nhan-so-lieu">Tài sản có thể mượn</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('check-circle-2') ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-cam">
            <div class="bieu-tuong-chinh">
                <?= icon('clock') ?>
            </div>
            <div>
                <div class="so-lieu"><?= sprintf('%02d', $soLieu['dang_muon']) ?></div>
                <div class="nhan-so-lieu">Tài sản đang mượn</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('package') ?>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-do">
            <div class="bieu-tuong-chinh">
                <?= icon('alert-triangle') ?>
            </div>
            <div>
                <div class="so-lieu"><?= sprintf('%02d', $soLieu['qua_han']) ?></div>
                <div class="nhan-so-lieu">Lượt mượn quá hạn</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('package') ?>
            </div>
        </div>
    </div>
</div>

<?php if ($coQuyenThaoTacNhanh): ?>
    <div class="card p-3 mb-4">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="fw-bold text-dark fs-6 me-2">Thao tác nhanh</span>
            <div class="d-flex align-items-center gap-3 flex-grow-1 flex-wrap">
                <a href="<?= url('muon-tra/muon.php') ?>" class="btn-nhanh btn-nhanh-xanh">
                    <?= icon('file-plus') ?>
                    <span>Lập phiếu mượn</span>
                </a>
                <a href="<?= url('muon-tra/tra.php') ?>" class="btn-nhanh btn-nhanh-la">
                    <?= icon('repeat') ?>
                    <span>Nhận trả tài sản</span>
                </a>
                <a href="<?= url('tai-san/index.php') ?>" class="btn-nhanh btn-nhanh-tim">
                    <?= icon('qr-code') ?>
                    <span>Quét mã QR</span>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-12 col-xl-5">
        <div class="card h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <?= icon('bar-chart-3', 'bieu-tuong text-primary') ?>
                            <h2 class="tieu-de-khoi">Xu hướng mượn – trả trong tuần</h2>
                        </div>
                        <div class="mo-ta-phu mt-1">Đơn vị: lượt giao dịch</div>
                    </div>
                    <div class="d-flex align-items-center gap-3 small">
                        <span class="d-flex align-items-center gap-1 fw-medium" style="color: #1768D8;">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#1768D8;"></span> Mượn
                        </span>
                        <span class="d-flex align-items-center gap-1 fw-medium" style="color: #2CD3CF;">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#2CD3CF;"></span> Trả
                        </span>
                    </div>
                </div>
                <div class="khung-bieu-do-220 flex-grow-1">
                    <canvas id="chartDashboardXuHuong"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <?= icon('bar-chart-3', 'bieu-tuong text-primary') ?>
                    <h2 class="tieu-de-khoi">Lượt mượn theo nhóm tài sản</h2>
                </div>
                <div class="mo-ta-phu mb-2">Đơn vị: lượt</div>

                <div class="khung-bieu-do-200 flex-grow-1">
                    <canvas id="chartDashboardNhomTaiSan"></canvas>
                </div>
                <div class="mo-ta-phu text-end fw-semibold mt-2">
                    Tổng: <?= (int)$theoNhom['tong'] ?> lượt mượn
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <?= icon('pie-chart', 'bieu-tuong text-primary') ?>
                    <h2 class="tieu-de-khoi">Tình trạng tài sản</h2>
                </div>
                <div class="mo-ta-phu mb-3">Cơ cấu <?= (int)$soLieu['tong_tai_san'] ?> tài sản</div>

                <div class="khung-bieu-do-tron my-auto">
                    <canvas id="chartDashboardTinhTrang"></canvas>
                    <div class="tam-bieu-do-tron">
                        <div class="fw-bold fs-4 text-dark"><?= (int)$soLieu['tong_tai_san'] ?></div>
                        <div class="mo-ta-phu">tài sản</div>
                    </div>
                </div>

                <div class="d-flex flex-column gap-2 small mt-3 pt-2 border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="d-flex align-items-center gap-2">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#1768D8;"></span>
                            <span>Có thể mượn</span>
                        </span>
                        <span class="fw-semibold text-dark"><?= $soLieu['co_the_muon'] ?> • <?= dinh_dang_phan_tram($tyLeCoTheMuon) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="d-flex align-items-center gap-2">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#3FDAD3;"></span>
                            <span>Đang mượn</span>
                        </span>
                        <span class="fw-semibold text-dark"><?= $soLieu['dang_muon'] ?> • <?= dinh_dang_phan_tram($tyLeDangMuon) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="d-flex align-items-center gap-2">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#B4BBC6;"></span>
                            <span>Trạng thái khác</span>
                        </span>
                        <span class="fw-semibold text-dark"><?= $soLieu['khac'] ?> • <?= dinh_dang_phan_tram($tyLeKhac) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <?= icon('list', 'bieu-tuong text-primary') ?>
            <h2 class="tieu-de-khoi mb-0">Lượt mượn cần theo dõi</h2>
        </div>
        <a href="<?= url('muon-tra/index.php') ?>" class="btn-vien-thuoc">
            <span>Xem tất cả →</span>
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
                <?php if (empty($canTheoDoi)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-phu">Không có lượt mượn nào cần theo dõi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($canTheoDoi as $p): ?>
                        <tr>
                            <td class="fw-semibold text-primary font-monospace">
                                <?= e($p['ma_phieu_muon']) ?>
                            </td>
                            <td class="fw-medium text-dark"><?= e($p['ho_ten_nguoi_muon']) ?></td>
                            <td><?= e($p['tai_san_hien_thi']) ?></td>
                            <td class="<?= $p['trang_thai_theo_doi'] === 'QUA_HAN' ? 'text-danger fw-semibold' : '' ?>">
                                <?= e($p['han_tra_dinh_dang']) ?>
                            </td>
                            <td>
                                <span class="huy-hieu-tt <?= e($p['lop_huy_hieu']) ?>">
                                    <?= e($p['nhan_trang_thai']) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($coQuyenThaoTacNhanh && ($p['trang_thai_theo_doi'] === 'QUA_HAN' || $p['trang_thai_theo_doi'] === 'DEN_HAN_HOM_NAY')): ?>
                                    <a href="<?= url('muon-tra/tra.php?tu_khoa=' . urlencode($p['ma_thiet_bi_dau'] ?: $p['ma_phieu_muon'])) ?>" class="btn-vien-thuoc">
                                        <span>Nhận trả</span>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$p['id']) ?>" class="btn-vien-thuoc">
                                        <span>Chi tiết</span>
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

<script src="<?= url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const giamChuyenDong = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const thoiGianHoatAnh = giamChuyenDong ? 0 : 350;

    // Plugin hiển thị số liệu trên đầu cột cho biểu đồ Bar
    const pluginSoDauCot = {
        id: 'soDauCot',
        afterDatasetsDraw(chart) {
            const { ctx, data } = chart;
            ctx.save();
            ctx.font = '600 12px system-ui, sans-serif';
            ctx.fillStyle = '#0F2747';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'bottom';

            chart.getDatasetMeta(0).data.forEach((bar, index) => {
                const val = data.datasets[0].data[index];
                if (val !== undefined && val !== null && val > 0) {
                    ctx.fillText(val, bar.x, bar.y - 4);
                }
            });
            ctx.restore();
        }
    };

    // 1. Biểu đồ Line: Xu hướng mượn – trả trong tuần
    const ctxXh = document.getElementById('chartDashboardXuHuong').getContext('2d');
    
    // Gradient chuyển sắc nhẹ dưới đường Mượn
    const gradientMuon = ctxXh.createLinearGradient(0, 0, 0, 220);
    gradientMuon.addColorStop(0, 'rgba(23, 104, 216, 0.25)');
    gradientMuon.addColorStop(1, 'rgba(23, 104, 216, 0.0)');

    new Chart(ctxXh, {
        type: 'line',
        data: {
            labels: <?= json_encode($xuHuong['labels'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            datasets: [
                {
                    label: 'Mượn',
                    data: <?= json_encode($xuHuong['muon'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    borderColor: '#1768D8',
                    backgroundColor: gradientMuon,
                    borderWidth: 2.5,
                    pointBackgroundColor: '#1768D8',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: true,
                    tension: 0.35
                },
                {
                    label: 'Trả',
                    data: <?= json_encode($xuHuong['tra'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    borderColor: '#2CD3CF',
                    backgroundColor: 'transparent',
                    borderWidth: 2.5,
                    pointBackgroundColor: '#2CD3CF',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    fill: false,
                    tension: 0.35
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: thoiGianHoatAnh },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0F2747',
                    padding: 8,
                    cornerRadius: 6
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#EEF2F8' },
                    ticks: { precision: 0, color: '#5B6676' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#5B6676' }
                }
            }
        }
    });

    // 2. Biểu đồ Bar: Lượt mượn theo nhóm tài sản
    const ctxNhom = document.getElementById('chartDashboardNhomTaiSan').getContext('2d');
    
    // Gradient chuyển sắc dọc cột: --mau-chinh ở đáy -> --bd-cot-tren ở đỉnh
    const gradientCot = ctxNhom.createLinearGradient(0, 200, 0, 0);
    gradientCot.addColorStop(0, '#1768D8');
    gradientCot.addColorStop(1, '#5DB2FF');

    const duLieuNhomData = <?= json_encode($theoNhom['data'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const maxVal = Math.max(...duLieuNhomData, 10);

    new Chart(ctxNhom, {
        type: 'bar',
        data: {
            labels: <?= json_encode($theoNhom['labels'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
            datasets: [{
                data: duLieuNhomData,
                backgroundColor: gradientCot,
                borderRadius: 4,
                maxBarThickness: 42
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: thoiGianHoatAnh },
            layout: {
                padding: { top: 16 }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0F2747',
                    padding: 8,
                    cornerRadius: 6
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: maxVal + 5,
                    grid: { color: '#EEF2F8' },
                    ticks: { precision: 0, color: '#5B6676' }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#5B6676' }
                }
            }
        },
        plugins: [pluginSoDauCot]
    });

    // 3. Biểu đồ Doughnut: Tình trạng tài sản
    const ctxTt = document.getElementById('chartDashboardTinhTrang').getContext('2d');
    new Chart(ctxTt, {
        type: 'doughnut',
        data: {
            labels: ['Có thể mượn', 'Đang mượn', 'Trạng thái khác'],
            datasets: [{
                data: [
                    <?= (int)$soLieu['co_the_muon'] ?>,
                    <?= (int)$soLieu['dang_muon'] ?>,
                    <?= (int)$soLieu['khac'] ?>
                ],
                backgroundColor: ['#1768D8', '#3FDAD3', '#B4BBC6'],
                borderWidth: 2,
                borderColor: '#FFFFFF'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: thoiGianHoatAnh },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0F2747',
                    padding: 8,
                    cornerRadius: 6
                }
            },
            cutout: '70%'
        }
    });
});
</script>

<?php require __DIR__ . '/layout/chan-trang.php'; ?>
