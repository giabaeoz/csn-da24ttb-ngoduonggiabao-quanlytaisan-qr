<?php
/**
 * Giao diện: Báo cáo Thống kê & Biểu đồ Chart.js (G10).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';
?>

<style>
@media print {
    #sidebarMenu, .navbar, .btn-toolbar, .no-print, nav[aria-label="breadcrumb"], footer, .d-print-none {
        display: none !important;
    }
    main {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: 1px solid #dee2e6 !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
    .print-only {
        display: block !important;
    }
}
.print-only {
    display: none;
}
</style>

<!-- Tiêu ngữ in báo cáo chính thức -->
<div class="print-only mb-4">
    <div class="row align-items-center text-center pb-3 border-bottom mb-3">
        <div class="col-6">
            <div class="text-uppercase fw-bold text-secondary small">TRƯỜNG ĐẠI HỌC CÔNG NGHỆ</div>
            <div class="fw-bold fs-6 text-uppercase text-dark">PHÒNG QUẢN LÝ THIẾT BỊ VÀ TÀI SẢN</div>
        </div>
        <div class="col-6">
            <div class="text-uppercase fw-bold text-dark fs-6">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
            <div class="fw-bold small text-dark">Độc lập - Tự do - Hạnh phúc</div>
        </div>
    </div>
    <div class="text-center my-3">
        <h3 class="fw-bold text-uppercase mb-1">BÁO CÁO THỐNG KÊ QUẢN LÝ THIẾT BỊ & MƯỢN TRẢ</h3>
        <p class="text-muted small">Thời điểm xuất báo cáo: <?= date('d/m/Y H:i') ?></p>
    </div>
</div>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom no-print">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">
            <span class="text-primary">📈</span> Báo cáo Thống kê & Phân tích Dữ liệu
        </h1>
        <p class="text-muted small mb-0">Giám sát tổng thể hoạt động mượn trả, cơ cấu tài sản, tần suất sử dụng và kiểm soát sự cố bảo trì.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <!-- Bộ lọc bàn trực -->
        <form action="<?= url('thong-ke/index.php') ?>" method="GET" class="d-flex gap-2">
            <input type="hidden" name="khoang" value="<?= e($khoangThoiGian) ?>">
            <?php if ($nguoiDung['vai_tro'] !== VAI_TRO_NHAN_VIEN): ?>
                <select name="phong_truc_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Toàn bộ các bàn trực --</option>
                    <?php foreach ($danhSachPhongTruc as $pt): ?>
                        <option value="<?= (int)$pt['id'] ?>" <?= ((int)$phongTrucFilter === (int)$pt['id']) ? 'selected' : '' ?>>
                            <?= e($pt['ten_phong_truc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </form>

        <button type="button" class="btn btn-outline-secondary btn-sm shadow-sm fw-semibold" onclick="window.print()">
            <i class="bi bi-printer me-1"></i> In báo cáo A4
        </button>
    </div>
</div>

<!-- THẺ CHỈ SỐ KPI CHÍNH (4 CARDS) -->
<div class="row g-3 mb-4">
    <!-- KPI 1: Tổng tài sản & Tỷ lệ sử dụng -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small text-uppercase fw-bold">Tổng tài sản</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold">Khai thác: <?= $kpi['ty_le_su_dung'] ?>%</span>
                </div>
                <div class="h3 mb-0 fw-bold text-primary mt-2"><?= (int)$kpi['tong_tai_san'] ?></div>
                <div class="small text-muted mt-1">
                    <span class="text-success fw-semibold"><?= (int)$kpi['tai_san_san_sang'] ?> sẵn sàng</span> &bull; 
                    <span class="text-warning-emphasis fw-semibold"><?= (int)$kpi['tai_san_dang_muon'] ?> đang mượn</span>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI 2: Hoạt động mượn trả -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-success border-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small text-uppercase fw-bold">Lượt mượn 30 ngày</span>
                    <span class="badge bg-success bg-opacity-10 text-success fw-bold">Tổng: <?= (int)$kpi['tong_luot_muon'] ?></span>
                </div>
                <div class="h3 mb-0 fw-bold text-success mt-2"><?= (int)$kpi['luot_muon_30_ngay'] ?></div>
                <div class="small text-muted mt-1">
                    <span class="text-dark fw-semibold"><?= (int)$kpi['phieu_dang_muon'] ?> phiếu đang mượn</span>
                    <?php if ($kpi['phieu_qua_han'] > 0): ?>
                        &bull; <span class="text-danger fw-bold"><?= (int)$kpi['phieu_qua_han'] ?> quá hạn!</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI 3: Sự cố & Bảo trì -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-danger border-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small text-uppercase fw-bold">Sự cố thiết bị</span>
                    <?php if ($kpi['su_co_chua_xu_ly'] > 0): ?>
                        <span class="badge bg-danger">Chờ xử lý: <?= (int)$kpi['su_co_chua_xu_ly'] ?></span>
                    <?php else: ?>
                        <span class="badge bg-success bg-opacity-10 text-success">Ổn định</span>
                    <?php endif; ?>
                </div>
                <div class="h3 mb-0 fw-bold text-danger mt-2"><?= (int)$kpi['tong_su_co'] ?></div>
                <div class="small text-muted mt-1">
                    <span class="text-warning-emphasis"><?= (int)$kpi['su_co_dang_xu_ly'] ?> đang bảo trì</span> &bull; 
                    <span class="text-success"><?= (int)$kpi['su_co_da_xu_ly'] ?> đã xử lý</span>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI 4: Hiệu suất kiểm kê -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm border-start border-info border-4 h-100 bg-white">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small text-uppercase fw-bold">Đợt kiểm kê</span>
                    <span class="badge bg-info bg-opacity-10 text-info fw-bold">Hoàn tất: <?= (int)$kpi['dot_hoan_tat'] ?></span>
                </div>
                <div class="h3 mb-0 fw-bold text-info mt-2"><?= (int)$kpi['tong_dot_kiem_ke'] ?></div>
                <div class="small text-muted mt-1">
                    <?php if ($kpi['dot_dang_mo'] > 0): ?>
                        <span class="text-warning fw-bold"><i class="bi bi-clock-history"></i> <?= (int)$kpi['dot_dang_mo'] ?> đợt đang mở</span>
                    <?php else: ?>
                        <span class="text-muted">Không có đợt đang mở</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HÀNG 1: BIỂU ĐỒ XU HƯỚNG MƯỢN TRẢ & CƠ CẤU TRẠNG THÁI -->
<div class="row g-4 mb-4">
    <!-- Cột 1: Biểu đồ xu hướng mượn - trả (Line/Bar Chart) -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-graph-up text-primary"></i> Xu hướng Mượn & Trả thiết bị
                </h6>
                <div class="btn-group btn-group-sm no-print" role="group">
                    <?php
                    $taoUrlKhoang = function(string $k) use ($phongTrucFilter) {
                        return url('thong-ke/index.php?khoang=' . $k . ($phongTrucFilter ? '&phong_truc_id=' . $phongTrucFilter : ''));
                    };
                    ?>
                    <a href="<?= $taoUrlKhoang('7_ngay') ?>" class="btn <?= ($khoangThoiGian === '7_ngay') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        7 ngày
                    </a>
                    <a href="<?= $taoUrlKhoang('30_ngay') ?>" class="btn <?= ($khoangThoiGian === '30_ngay') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        30 ngày
                    </a>
                    <a href="<?= $taoUrlKhoang('12_thang') ?>" class="btn <?= ($khoangThoiGian === '12_thang') ? 'btn-primary' : 'btn-outline-secondary' ?>">
                        12 tháng
                    </a>
                </div>
            </div>
            <div class="card-body p-3">
                <div style="height: 300px; position: relative;">
                    <canvas id="chartMuonTra"></canvas>
                </div>
            </div>
            <div class="card-footer bg-light py-2 text-center small text-muted">
                Tổng lượt mượn trong kỳ: <strong class="text-primary"><?= (int)$muonTra['tong_muon'] ?></strong> &bull; 
                Tổng lượt trả thiết bị: <strong class="text-success"><?= (int)$muonTra['tong_tra'] ?></strong>
            </div>
        </div>
    </div>

    <!-- Cột 2: Biểu đồ trạng thái tài sản (Doughnut Chart) -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-pie-chart text-success"></i> Tỷ lệ trạng thái tài sản
                </h6>
            </div>
            <div class="card-body p-3 d-flex flex-column justify-content-center align-items-center">
                <div style="width: 230px; height: 230px; position: relative;">
                    <canvas id="chartTrangThai"></canvas>
                </div>
                <div class="mt-3 w-100">
                    <div class="row g-2 text-center small">
                        <div class="col-6">
                            <span class="badge bg-success w-100 py-1">Sẵn sàng: <?= (int)$taiSanTrangThai['data'][0] ?></span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-warning text-dark w-100 py-1">Đang mượn: <?= (int)$taiSanTrangThai['data'][1] ?></span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-danger w-100 py-1">Bảo trì: <?= (int)$taiSanTrangThai['data'][2] ?></span>
                        </div>
                        <div class="col-6">
                            <span class="badge bg-secondary w-100 py-1">Ngừng SD: <?= (int)$taiSanTrangThai['data'][3] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HÀNG 2: CƠ CẤU CHỦNG LOẠI & ĐỐI TƯỢNG MƯỢN & SỰ CỐ -->
<div class="row g-4 mb-4">
    <!-- Cột 1: Cơ cấu tài sản theo loại (Bar Chart ngang hoặc đứng) -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-bar-chart-fill text-info"></i> Cơ cấu thiết bị theo chủng loại
                </h6>
            </div>
            <div class="card-body p-3">
                <div style="height: 250px; position: relative;">
                    <canvas id="chartLoaiTaiSan"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Cột 2: Đối tượng mượn: Giảng viên vs Sinh viên -->
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-people-fill text-primary"></i> Đối tượng mượn
                </h6>
            </div>
            <div class="card-body p-3 d-flex flex-column justify-content-center align-items-center">
                <div style="width: 180px; height: 180px; position: relative;">
                    <canvas id="chartDoiTuong"></canvas>
                </div>
                <div class="mt-3 w-100 text-center small">
                    <span class="badge bg-primary me-2">Giảng viên: <?= (int)$doiTuong['data'][0] ?></span>
                    <span class="badge bg-info text-dark">Sinh viên: <?= (int)$doiTuong['data'][1] ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Cột 3: Phân tích sự cố -->
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-octagon-fill text-danger"></i> Phân loại sự cố
                </h6>
            </div>
            <div class="card-body p-3 d-flex flex-column justify-content-center align-items-center">
                <div style="width: 180px; height: 180px; position: relative;">
                    <canvas id="chartSuCo"></canvas>
                </div>
                <div class="mt-3 w-100 text-center small">
                    <span class="badge bg-danger me-1">Hỏng: <?= (int)$suCo['theo_loai']['data'][0] ?></span>
                    <span class="badge bg-dark me-1">Mất: <?= (int)$suCo['theo_loai']['data'][1] ?></span>
                    <span class="badge bg-warning text-dark">Sai phòng: <?= (int)$suCo['theo_loai']['data'][2] ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HÀNG 3: BẢNG XẾP HẠNG & TỔNG HỢP CHI TIẾT -->
<div class="row g-4 mb-4">
    <!-- Top 5 Phòng học mượn nhiều nhất -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-trophy text-warning"></i> Top 5 phòng học sử dụng thiết bị nhiều nhất
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th style="width: 40px;" class="ps-3">#</th>
                            <th>Mã phòng</th>
                            <th>Tên phòng học</th>
                            <th style="width: 100px;">Tòa nhà</th>
                            <th style="width: 130px;" class="text-end pe-3">Lượt mượn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topPhongHoc['danh_sach'])): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Chưa có dữ liệu mượn phòng.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topPhongHoc['danh_sach'] as $i => $ph): ?>
                                <tr>
                                    <td class="ps-3 fw-bold <?= ($i === 0) ? 'text-warning' : 'text-muted' ?>">
                                        <?= $i + 1 ?>
                                    </td>
                                    <td class="fw-bold font-monospace text-primary"><?= e($ph['ma_phong']) ?></td>
                                    <td><?= e($ph['ten_phong']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($ph['toa_nha'] ?? '--') ?></span></td>
                                    <td class="text-end pe-3">
                                        <span class="badge bg-primary rounded-pill px-3 py-2"><?= (int)$ph['so_luot_muon'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top 5 Thiết bị được mượn nhiều nhất -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100 bg-white">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-star-fill text-warning"></i> Top 5 thiết bị có tần suất mượn cao nhất
                </h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase small">
                        <tr>
                            <th style="width: 40px;" class="ps-3">#</th>
                            <th style="width: 120px;">Mã tài sản</th>
                            <th>Tên thiết bị</th>
                            <th>Loại</th>
                            <th style="width: 130px;" class="text-end pe-3">Lượt mượn</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topTaiSan['danh_sach'])): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Chưa có dữ liệu mượn thiết bị.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($topTaiSan['danh_sach'] as $i => $ts): ?>
                                <tr>
                                    <td class="ps-3 fw-bold <?= ($i === 0) ? 'text-warning' : 'text-muted' ?>">
                                        <?= $i + 1 ?>
                                    </td>
                                    <td class="fw-bold font-monospace text-primary"><?= e($ts['ma_tai_san']) ?></td>
                                    <td class="fw-semibold text-dark"><?= e($ts['ten_tai_san']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($ts['ten_loai'] ?? 'Thiết bị') ?></span></td>
                                    <td class="text-end pe-3">
                                        <span class="badge bg-success rounded-pill px-3 py-2"><?= (int)$ts['so_luot_muon'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS KHỞI TẠO BIỂU ĐỒ CHART.JS (OFFLINE LOCAL VENDOR) -->
<script src="<?= url('assets/vendor/chartjs/chart.umd.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. BIỂU ĐỒ XU HƯỚNG MƯỢN & TRẢ (Chart Line/Bar)
    const ctxMuonTra = document.getElementById('chartMuonTra').getContext('2d');
    new Chart(ctxMuonTra, {
        type: 'line',
        data: {
            labels: <?= json_encode($muonTra['labels'], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: 'Lượt mượn thiết bị',
                    data: <?= json_encode($muonTra['so_luot_muon']) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#0d6efd',
                },
                {
                    label: 'Lượt trả thiết bị',
                    data: <?= json_encode($muonTra['so_luot_tra']) ?>,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#198754',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    // 2. BIỂU ĐỒ TRẠNG THÁI TÀI SẢN (Doughnut)
    const ctxTrangThai = document.getElementById('chartTrangThai').getContext('2d');
    new Chart(ctxTrangThai, {
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
            plugins: {
                legend: { display: false }
            },
            cutout: '65%'
        }
    });

    // 3. BIỂU ĐỒ CƠ CẤU CHỦNG LOẠI (Bar Chart)
    const ctxLoai = document.getElementById('chartLoaiTaiSan').getContext('2d');
    new Chart(ctxLoai, {
        type: 'bar',
        data: {
            labels: <?= json_encode($taiSanLoai['labels'], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Số lượng thiết bị',
                data: <?= json_encode($taiSanLoai['so_luong']) ?>,
                backgroundColor: 'rgba(13, 202, 240, 0.7)',
                borderColor: '#0dcaf0',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    // 4. BIỂU ĐỒ ĐỐI TƯỢNG MƯỢN (Doughnut)
    const ctxDoiTuong = document.getElementById('chartDoiTuong').getContext('2d');
    new Chart(ctxDoiTuong, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($doiTuong['labels'], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode($doiTuong['data']) ?>,
                backgroundColor: <?= json_encode($doiTuong['colors']) ?>,
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '60%'
        }
    });

    // 5. BIỂU ĐỒ PHÂN LOẠI SỰ CỐ (Doughnut)
    const ctxSuCo = document.getElementById('chartSuCo').getContext('2d');
    new Chart(ctxSuCo, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($suCo['theo_loai']['labels'], JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                data: <?= json_encode($suCo['theo_loai']['data']) ?>,
                backgroundColor: <?= json_encode($suCo['theo_loai']['colors']) ?>,
                borderWidth: 1,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '60%'
        }
    });
});
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
