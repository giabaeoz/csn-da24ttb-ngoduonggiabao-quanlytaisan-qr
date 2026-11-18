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
        <h1 class="tieu-de-trang">Tổng quan</h1>
        <div class="mo-ta-phu">Tình hình tài sản và mượn - trả tại phòng trực.</div>
    </div>

    <div class="d-flex align-items-center flex-wrap gap-3 mt-2 mt-md-0">
        <div class="text-md-end">
            <div class="fw-semibold text-dark small">Xin chào “<?= e($tenCanBo) ?>”</div>
            <div class="mo-ta-phu chu-nho-11">Hôm nay ngày <?= e($ngayHienTaiStr) ?></div>
        </div>

        <form method="GET" action="<?= url('tong-quan.php') ?>" class="m-0">
            <div class="input-group">
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
    <img src="<?= url('assets/images/banner-chinh.jpg?v=' . @filemtime(dirname(__DIR__, 2) . '/assets/images/banner-chinh.jpg')) ?>" alt="Quản lý thiết bị theo QR" class="img-fluid w-100 rounded-3">
</div>

<?php
$laAdmin = (($nguoiDung['vai_tro'] ?? '') === 'QUAN_TRI');
$laNhanVien = (($nguoiDung['vai_tro'] ?? '') === 'NHAN_VIEN');
?>
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

    <?php if ($laAdmin): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="the-kpi-pha-mau the-kpi-la">
                <div class="bieu-tuong-chinh">
                    <?= icon('user-cog') ?>
                </div>
                <div>
                    <div class="so-lieu"><?= sprintf('%02d', $soLieu['tong_tai_khoan'] ?? 0) ?></div>
                    <div class="nhan-so-lieu">Tổng tài khoản</div>
                </div>
                <div class="bieu-tuong-mo-bg" aria-hidden="true">
                    <?= icon('user-cog') ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="the-kpi-pha-mau the-kpi-cam">
                <div class="bieu-tuong-chinh">
                    <?= icon('home') ?>
                </div>
                <div>
                    <div class="so-lieu"><?= sprintf('%02d', $soLieu['tong_phong_truc_hoat_dong'] ?? 0) ?></div>
                    <div class="nhan-so-lieu">Phòng trực đang hoạt động</div>
                </div>
                <div class="bieu-tuong-mo-bg" aria-hidden="true">
                    <?= icon('home') ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="the-kpi-pha-mau the-kpi-do">
                <div class="bieu-tuong-chinh">
                    <?= icon('users') ?>
                </div>
                <div>
                    <div class="so-lieu"><?= sprintf('%02d', $soLieu['tong_nguoi_muon'] ?? 0) ?></div>
                    <div class="nhan-so-lieu">Tổng người mượn</div>
                </div>
                <div class="bieu-tuong-mo-bg" aria-hidden="true">
                    <?= icon('users') ?>
                </div>
            </div>
        </div>
    <?php elseif ($laNhanVien): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="the-kpi-pha-mau the-kpi-la">
                <div class="bieu-tuong-chinh">
                    <?= icon('users') ?>
                </div>
                <div>
                    <div class="so-lieu"><?= sprintf('%02d', $soLieu['nguoi_dang_muon'] ?? 0) ?></div>
                    <div class="nhan-so-lieu">Tổng người đang mượn tài sản</div>
                </div>
                <div class="bieu-tuong-mo-bg" aria-hidden="true">
                    <?= icon('users') ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="the-kpi-pha-mau the-kpi-cam">
                <div class="bieu-tuong-chinh">
                    <?= icon('wrench') ?>
                </div>
                <div>
                    <div class="so-lieu"><?= sprintf('%02d', $soLieu['tai_san_su_co'] ?? 0) ?></div>
                    <div class="nhan-so-lieu">Tài sản có sự cố</div>
                </div>
                <div class="bieu-tuong-mo-bg" aria-hidden="true">
                    <?= icon('wrench') ?>
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
                    <?= icon('alert-triangle') ?>
                </div>
            </div>
        </div>
    <?php else: ?>
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
                    <?= icon('clock') ?>
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
                    <?= icon('alert-triangle') ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($coQuyenThaoTacNhanh): ?>
    <div class="card p-3 mb-4">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <span class="fw-bold text-dark small me-1">Thao tác nhanh</span>
            <div class="d-flex align-items-center gap-3 flex-grow-1 flex-wrap">
                <a href="<?= url('muon-tra/muon.php') ?>" class="btn-nhanh btn-nhanh-xanh">
                    <?= icon('file-plus', 'bieu-tuong-sm') ?>
                    <span>Lập phiếu mượn</span>
                </a>
                <a href="<?= url('muon-tra/tra.php') ?>" class="btn-nhanh btn-nhanh-la">
                    <?= icon('repeat', 'bieu-tuong-sm') ?>
                    <span>Nhận trả tài sản</span>
                </a>
                <a href="<?= url('tai-san/index.php') ?>" class="btn-nhanh btn-nhanh-tim">
                    <?= icon('qr-code', 'bieu-tuong-sm') ?>
                    <span>Quét mã QR</span>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($laAdmin && !empty($bieuDoAdmin)): ?>
    <?php
    $bdPt = $bieuDoAdmin['phong_truc'];
    $bdTk = $bieuDoAdmin['tai_khoan'];
    $bdNm = $bieuDoAdmin['nguoi_muon'];
    $bdVh = $bieuDoAdmin['van_hanh'];
    $bd14 = $bieuDoAdmin['chuoi_14_ngay'];
    ?>
    <!-- HÀNG BIỂU ĐỒ 1: MƯỢN TRẢ CÁC PHÒNG TRỰC, XU HƯỚNG TUẦN & CƠ CẤU TÀI KHOẢN -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <?= icon('home', 'bieu-tuong-sm text-primary') ?>
                                <h2 class="tieu-de-khoi">Tình trạng mượn - trả các phòng trực</h2>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 small flex-wrap">
                            <span class="d-flex align-items-center gap-1 fw-medium" style="color: #1768D8;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#1768D8;"></span> Đã trả (<?= (int)$bdPt['tong_da_tra'] ?>)
                            </span>
                            <span class="d-flex align-items-center gap-1 fw-medium" style="color: #2CD3CF;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#2CD3CF;"></span> Đang mượn (<?= (int)$bdPt['tong_dang_muon'] ?>)
                            </span>
                            <span class="d-flex align-items-center gap-1 fw-medium" style="color: #F04438;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#F04438;"></span> Quá hạn (<?= (int)$bdPt['tong_qua_han'] ?>)
                            </span>
                        </div>
                    </div>
                    <div class="khung-bieu-do-220 flex-grow-1">
                        <canvas id="chartAdminMuonTraPhongTruc"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <?= icon('bar-chart-3', 'bieu-tuong-sm text-primary') ?>
                                <h2 class="tieu-de-khoi">Xu hướng mượn - trả toàn trường</h2>
                            </div>
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
                    <div class="khung-bieu-do-200 flex-grow-1">
                        <canvas id="chartDashboardXuHuong"></canvas>
                    </div>
                    <div class="mo-ta-phu text-end fw-semibold chu-nho-11 mt-2">
                        Tuần chọn: <?= (int)$xuHuong['tong_muon'] ?> lượt mượn • <?= (int)$xuHuong['tong_tra'] ?> lượt trả
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2">
                        <?= icon('user-cog', 'bieu-tuong-sm text-primary') ?>
                        <h2 class="tieu-de-khoi">Tài khoản cán bộ</h2>
                    </div>
                    <div class="mo-ta-phu chu-nho-11 mb-3">
                        Hoạt động: <?= (int)$bdTk['dang_hoat_dong'] ?>/<?= (int)$bdTk['tong'] ?> tài khoản
                    </div>

                    <div class="khung-bieu-do-tron my-auto">
                        <canvas id="chartAdminTaiKhoan"></canvas>
                        <div class="tam-bieu-do-tron">
                            <div class="fw-bold fs-4 text-dark"><?= (int)$bdTk['tong'] ?></div>
                            <div class="mo-ta-phu">tài khoản</div>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2 small mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="d-flex align-items-center gap-2">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#1768D8;"></span>
                                <span>Quản trị viên</span>
                            </span>
                            <span class="fw-semibold text-dark"><?= (int)$bdTk['quan_tri'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="d-flex align-items-center gap-2">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#3FDAD3;"></span>
                                <span>Cán bộ quản lý</span>
                            </span>
                            <span class="fw-semibold text-dark"><?= (int)$bdTk['quan_ly'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="d-flex align-items-center gap-2">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#F79009;"></span>
                                <span>Nhân viên trực phòng</span>
                            </span>
                            <span class="fw-semibold text-dark"><?= (int)$bdTk['nhan_vien'] ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HÀNG BIỂU ĐỒ 2: PHÂN BỔ NGUỒN LỰC PHÒNG TRỰC, CHỈ SỐ VẬN HÀNH & NGƯỜI MƯỢN -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <?= icon('package', 'bieu-tuong-sm text-primary') ?>
                                <h2 class="tieu-de-khoi">Tổng hợp tài sản và nhân sự theo phòng trực</h2>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 small flex-wrap">
                            <span class="d-flex align-items-center gap-1 fw-medium" style="color: #12B76A;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#12B76A;"></span> Sẵn sàng
                            </span>
                            <span class="d-flex align-items-center gap-1 fw-medium" style="color: #1768D8;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#1768D8;"></span> Đang mượn
                            </span>
                            <span class="d-flex align-items-center gap-1 fw-medium" style="color: #F79009;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#F79009;"></span> Bảo trì/Khác
                            </span>
                            <span class="d-flex align-items-center gap-1 fw-medium" style="color: #7A5AF8;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#7A5AF8;"></span> Nhân sự
                            </span>
                        </div>
                    </div>
                    <div class="khung-bieu-do-220 flex-grow-1">
                        <canvas id="chartAdminNguonLucPhongTruc"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2">
                        <?= icon('clipboard-check', 'bieu-tuong-sm text-primary') ?>
                        <h2 class="tieu-de-khoi">Vận hành: Sự cố, Kiểm kê & Điều chuyển</h2>
                    </div>

                    <div class="khung-bieu-do-200 flex-grow-1">
                        <canvas id="chartAdminVanHanh"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2">
                        <?= icon('users', 'bieu-tuong-sm text-primary') ?>
                        <h2 class="tieu-de-khoi">Đối tượng người mượn</h2>
                    </div>
                    <div class="khung-bieu-do-tron my-auto">
                        <canvas id="chartAdminNguoiMuon"></canvas>
                        <div class="tam-bieu-do-tron">
                            <div class="fw-bold fs-4 text-dark"><?= (int)$bdNm['tong_ho_so'] ?></div>
                            <div class="mo-ta-phu">người mượn</div>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2 small mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="d-flex align-items-center gap-2">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#1768D8;"></span>
                                <span>Giảng viên</span>
                            </span>
                            <span class="fw-semibold text-dark"><?= (int)$bdNm['hs_giang_vien'] ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="d-flex align-items-center gap-2">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#2CD3CF;"></span>
                                <span>Sinh viên</span>
                            </span>
                            <span class="fw-semibold text-dark"><?= (int)$bdNm['hs_sinh_vien'] ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="d-flex align-items-center gap-2">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:#B4BBC6;"></span>
                                <span>Lượt mượn quá hạn</span>
                            </span>
                            <span class="fw-semibold text-danger"><?= (int)$soLieu['qua_han'] ?> </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- HÀNG BIỂU ĐỒ 3: BIỂU ĐỒ ĐƯỜNG TRẢI DÀI SO SÁNH BIẾN ĐỘNG CÁC PHÒNG TRỰC & TOÀN TRƯỜNG (14 NGÀY GẦN NHẤT) -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <?= icon('bar-chart-3', 'bieu-tuong-sm text-primary') ?>
                                <h2 class="tieu-de-khoi">Biến động lưu lượng mượn - trả giữa các phòng trực & toàn hệ thống (14 ngày gần nhất)</h2>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3 small flex-wrap">
                            <span class="d-flex align-items-center gap-1 fw-semibold" style="color: #1768D8;">
                                <span style="display:inline-block; width:18px; height:3px; border-radius:2px; background-color:#1768D8;"></span> Tổng mượn (<?= array_sum($bd14['tong_muon']) ?>)
                            </span>
                            <span class="d-flex align-items-center gap-1 fw-semibold" style="color: #2CD3CF;">
                                <span style="display:inline-block; width:18px; height:3px; border-radius:2px; background-color:#2CD3CF;"></span> Tổng trả (<?= array_sum($bd14['tong_tra']) ?>)
                            </span>
                            <?php foreach ($bd14['theo_phong_truc'] as $ptLine): ?>
                                <span class="d-flex align-items-center gap-1 fw-medium" style="color: <?= e($ptLine['mau']) ?>;" title="<?= e($ptLine['ten_phong_truc']) ?>">
                                    <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background-color:<?= e($ptLine['mau']) ?>;"></span> <?= e($ptLine['ma_phong_truc']) ?> (<?= (int)$ptLine['tong'] ?>)
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="height: 250px; position: relative;">
                        <canvas id="chartAdminChuoi14Ngay"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-5">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <?= icon('bar-chart-3', 'bieu-tuong-sm text-primary') ?>
                                <h2 class="tieu-de-khoi">Xu hướng mượn - trả trong tuần</h2>
                            </div>
                            <div class="mo-ta-phu chu-nho-11">Đơn vị: lượt giao dịch</div>
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
                    <div class="d-flex align-items-center gap-2">
                        <?= icon('bar-chart-3', 'bieu-tuong-sm text-primary') ?>
                        <h2 class="tieu-de-khoi">Lượt mượn theo nhóm tài sản</h2>
                    </div>
                    <div class="mo-ta-phu chu-nho-11 mb-2">Đơn vị: lượt</div>

                    <div class="khung-bieu-do-200 flex-grow-1">
                        <canvas id="chartDashboardNhomTaiSan"></canvas>
                    </div>
                    <div class="mo-ta-phu text-end fw-semibold chu-nho-11 mt-2">
                        Tổng: <?= (int)$theoNhom['tong'] ?> lượt mượn
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center gap-2">
                        <?= icon('pie-chart', 'bieu-tuong-sm text-primary') ?>
                        <h2 class="tieu-de-khoi">Tình trạng tài sản</h2>
                    </div>
                    <div class="mo-ta-phu chu-nho-11 mb-3">Cơ cấu <?= (int)$soLieu['tong_tai_san'] ?> tài sản</div>

                    <div class="khung-bieu-do-tron my-auto">
                        <canvas id="chartDashboardTinhTrang"></canvas>
                        <div class="tam-bieu-do-tron">
                            <div class="fw-bold fs-4 text-dark"><?= (int)$soLieu['tong_tai_san'] ?></div>
                            <div class="mo-ta-phu">tài sản</div>
                        </div>
                    </div>

                    <div class="d-flex flex-column gap-2 small mt-3 pt-3 border-top">
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
<?php endif; ?>

<div class="card mb-2">
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
                    <?php if ($laAdmin): ?>
                        <th>Phòng trực</th>
                    <?php endif; ?>
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
                        <td colspan="<?= $laAdmin ? 7 : 6 ?>" class="text-center py-4 text-phu">Không có lượt mượn nào cần theo dõi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($canTheoDoi as $p): ?>
                        <tr>
                            <td class="fw-semibold text-primary font-monospace text-nowrap">
                                <?= e($p['ma_phieu_muon']) ?>
                            </td>
                            <?php if ($laAdmin): ?>
                                <td class="text-nowrap">
                                    <span class="huy-hieu-tt huy-hieu-tt-xam"><?= e($p['ma_phong_truc'] ?? '—') ?></span>
                                </td>
                            <?php endif; ?>
                            <td class="fw-medium text-dark text-nowrap"><?= e($p['ho_ten_nguoi_muon']) ?></td>
                            <td><?= e($p['tai_san_hien_thi']) ?></td>
                            <td class="text-nowrap <?= $p['trang_thai_theo_doi'] === 'QUA_HAN' ? 'text-danger fw-semibold' : '' ?>">
                                <?= e($p['han_tra_dinh_dang']) ?>
                            </td>
                            <td class="text-nowrap">
                                <span class="huy-hieu-tt <?= e($p['lop_huy_hieu']) ?>">
                                    <?= e($p['nhan_trang_thai']) ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
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

        // Plugin hiển thị số liệu trên đầu cột cho biểu đồ Bar đơn
        const pluginSoDauCot = {
            id: 'soDauCot',
            afterDatasetsDraw(chart) {
                const {
                    ctx,
                    data
                } = chart;
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

        // 1. Biểu đồ Line: Xu hướng mượn – trả trong tuần (dùng chung cho cả Admin và Nhân viên/Quản lý)
        const elXh = document.getElementById('chartDashboardXuHuong');
        if (elXh) {
            const ctxXh = elXh.getContext('2d');
            const gradientMuon = ctxXh.createLinearGradient(0, 0, 0, 220);
            gradientMuon.addColorStop(0, 'rgba(23, 104, 216, 0.25)');
            gradientMuon.addColorStop(1, 'rgba(23, 104, 216, 0.0)');

            new Chart(ctxXh, {
                type: 'line',
                data: {
                    labels: <?= json_encode($xuHuong['labels'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    datasets: [{
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
                    animation: {
                        duration: thoiGianHoatAnh
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#0F2747',
                            padding: 8,
                            cornerRadius: 6
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#EEF2F8'
                            },
                            ticks: {
                                precision: 0,
                                color: '#5B6676'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#5B6676'
                            }
                        }
                    }
                }
            });
        }

        <?php if ($laAdmin && !empty($bieuDoAdmin)): ?>
            const tenDayDuPt = <?= json_encode($bdPt['ten_day_du'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

            // A1. Biểu đồ cột nhóm: Tình trạng mượn – trả tại tất cả các phòng trực
            const elMtPt = document.getElementById('chartAdminMuonTraPhongTruc');
            if (elMtPt) {
                new Chart(elMtPt.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($bdPt['labels'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        datasets: [{
                                label: 'Đã trả',
                                data: <?= json_encode($bdPt['da_tra'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                                backgroundColor: '#1768D8',
                                borderRadius: 4,
                                maxBarThickness: 24
                            },
                            {
                                label: 'Đang mượn',
                                data: <?= json_encode($bdPt['dang_muon'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                                backgroundColor: '#2CD3CF',
                                borderRadius: 4,
                                maxBarThickness: 24
                            },
                            {
                                label: 'Quá hạn',
                                data: <?= json_encode($bdPt['qua_han'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                                backgroundColor: '#F04438',
                                borderRadius: 4,
                                maxBarThickness: 24
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: thoiGianHoatAnh
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0F2747',
                                padding: 8,
                                cornerRadius: 6,
                                callbacks: {
                                    title(items) {
                                        const idx = items[0]?.dataIndex ?? 0;
                                        return tenDayDuPt[idx] ? (items[0].label + ' - ' + tenDayDuPt[idx]) : items[0].label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#EEF2F8'
                                },
                                ticks: {
                                    precision: 0,
                                    color: '#5B6676'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#5B6676'
                                }
                            }
                        }
                    }
                });
            }

            // A2. Biểu đồ Doughnut: Cơ cấu tài khoản cán bộ
            const elTk = document.getElementById('chartAdminTaiKhoan');
            if (elTk) {
                new Chart(elTk.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Quản trị viên', 'Cán bộ quản lý', 'Nhân viên trực phòng'],
                        datasets: [{
                            data: [
                                <?= (int)$bdTk['quan_tri'] ?>,
                                <?= (int)$bdTk['quan_ly'] ?>,
                                <?= (int)$bdTk['nhan_vien'] ?>
                            ],
                            backgroundColor: ['#1768D8', '#3FDAD3', '#F79009'],
                            borderWidth: 2,
                            borderColor: '#FFFFFF'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: thoiGianHoatAnh
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0F2747',
                                padding: 8,
                                cornerRadius: 6
                            }
                        },
                        cutout: '70%'
                    }
                });
            }

            // A3. Biểu đồ cột nhóm: Phân bổ tài sản & nhân sự theo phòng trực
            const elNlPt = document.getElementById('chartAdminNguonLucPhongTruc');
            if (elNlPt) {
                new Chart(elNlPt.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($bdPt['labels'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        datasets: [{
                                label: 'Tài sản sẵn sàng',
                                data: <?= json_encode($bdPt['ts_san_sang'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                                backgroundColor: '#12B76A',
                                borderRadius: 4,
                                maxBarThickness: 20
                            },
                            {
                                label: 'Tài sản đang mượn',
                                data: <?= json_encode($bdPt['ts_dang_muon'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                                backgroundColor: '#1768D8',
                                borderRadius: 4,
                                maxBarThickness: 20
                            },
                            {
                                label: 'Bảo trì / Khác',
                                data: <?= json_encode($bdPt['ts_khac'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                                backgroundColor: '#F79009',
                                borderRadius: 4,
                                maxBarThickness: 20
                            },
                            {
                                label: 'Nhân sự trực',
                                data: <?= json_encode($bdPt['nhan_vien'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                                backgroundColor: '#7A5AF8',
                                borderRadius: 4,
                                maxBarThickness: 20
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: thoiGianHoatAnh
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0F2747',
                                padding: 8,
                                cornerRadius: 6,
                                callbacks: {
                                    title(items) {
                                        const idx = items[0]?.dataIndex ?? 0;
                                        return tenDayDuPt[idx] ? (items[0].label + ' - ' + tenDayDuPt[idx]) : items[0].label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#EEF2F8'
                                },
                                ticks: {
                                    precision: 0,
                                    color: '#5B6676'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#5B6676'
                                }
                            }
                        }
                    }
                });
            }

            // A4. Biểu đồ Bar: Vận hành Sự cố, Kiểm kê & Điều chuyển
            const elVh = document.getElementById('chartAdminVanHanh');
            if (elVh) {
                const duLieuVh = <?= json_encode($bdVh['data'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                const maxVh = Math.max(...duLieuVh, 5);
                new Chart(elVh.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($bdVh['labels'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        datasets: [{
                            data: duLieuVh,
                            backgroundColor: ['#F04438', '#12B76A', '#F79009', '#1768D8', '#7A5AF8'],
                            borderRadius: 4,
                            maxBarThickness: 38
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: thoiGianHoatAnh
                        },
                        layout: {
                            padding: {
                                top: 16
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0F2747',
                                padding: 8,
                                cornerRadius: 6
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                suggestedMax: maxVh + 3,
                                grid: {
                                    color: '#EEF2F8'
                                },
                                ticks: {
                                    precision: 0,
                                    color: '#5B6676'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#5B6676',
                                    font: {
                                        size: 11
                                    }
                                }
                            }
                        }
                    },
                    plugins: [pluginSoDauCot]
                });
            }

            // A5. Biểu đồ Doughnut: Cơ cấu Người mượn (Giảng viên & Sinh viên)
            const elNm = document.getElementById('chartAdminNguoiMuon');
            if (elNm) {
                new Chart(elNm.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Giảng viên', 'Sinh viên'],
                        datasets: [{
                            data: [
                                <?= (int)$bdNm['hs_giang_vien'] ?>,
                                <?= (int)$bdNm['hs_sinh_vien'] ?>
                            ],
                            backgroundColor: ['#1768D8', '#2CD3CF'],
                            borderWidth: 2,
                            borderColor: '#FFFFFF'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                            duration: thoiGianHoatAnh
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0F2747',
                                padding: 8,
                                cornerRadius: 6
                            }
                        },
                        cutout: '70%'
                    }
                });
            }

            // A6. Biểu đồ đường trải dài (Full-width Line Chart): Biến động 14 ngày gần nhất giữa các phòng trực & toàn trường
            const el14 = document.getElementById('chartAdminChuoi14Ngay');
            if (el14) {
                const ctx14 = el14.getContext('2d');
                const gradTongMuon14 = ctx14.createLinearGradient(0, 0, 0, 250);
                gradTongMuon14.addColorStop(0, 'rgba(23, 104, 216, 0.20)');
                gradTongMuon14.addColorStop(1, 'rgba(23, 104, 216, 0.0)');

                const dsChuoiPt = <?= json_encode($bd14['theo_phong_truc'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
                const datasets14 = [{
                        label: 'Tổng lượt mượn toàn trường',
                        data: <?= json_encode($bd14['tong_muon'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        borderColor: '#1768D8',
                        backgroundColor: gradTongMuon14,
                        borderWidth: 3,
                        pointBackgroundColor: '#1768D8',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.35
                    },
                    {
                        label: 'Tổng lượt trả toàn trường',
                        data: <?= json_encode($bd14['tong_tra'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        borderColor: '#2CD3CF',
                        backgroundColor: 'transparent',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#2CD3CF',
                        pointRadius: 3.5,
                        pointHoverRadius: 5.5,
                        fill: false,
                        tension: 0.35
                    }
                ];

                dsChuoiPt.forEach(function(ptItem) {
                    datasets14.push({
                        label: 'Mượn tại ' + ptItem.ma_phong_truc + ' (' + ptItem.ten_phong_truc + ')',
                        data: ptItem.data,
                        borderColor: ptItem.mau,
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 4],
                        pointBackgroundColor: ptItem.mau,
                        pointRadius: 3,
                        pointHoverRadius: 5,
                        fill: false,
                        tension: 0.35
                    });
                });

                new Chart(ctx14, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode($bd14['labels'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                        datasets: datasets14
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        animation: {
                            duration: thoiGianHoatAnh
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0F2747',
                                padding: 10,
                                cornerRadius: 6
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: '#EEF2F8'
                                },
                                ticks: {
                                    precision: 0,
                                    color: '#5B6676'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#5B6676'
                                }
                            }
                        }
                    }
                });
            }
        <?php else: ?>
            // 2. Biểu đồ Bar: Lượt mượn theo nhóm tài sản (cho Nhân viên & Quản lý)
            const elNhom = document.getElementById('chartDashboardNhomTaiSan');
            if (elNhom) {
                const ctxNhom = elNhom.getContext('2d');
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
                        animation: {
                            duration: thoiGianHoatAnh
                        },
                        layout: {
                            padding: {
                                top: 16
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
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
                                grid: {
                                    color: '#EEF2F8'
                                },
                                ticks: {
                                    precision: 0,
                                    color: '#5B6676'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: '#5B6676'
                                }
                            }
                        }
                    },
                    plugins: [pluginSoDauCot]
                });
            }

            // 3. Biểu đồ Doughnut: Tình trạng tài sản (cho Nhân viên & Quản lý)
            const elTt = document.getElementById('chartDashboardTinhTrang');
            if (elTt) {
                new Chart(elTt.getContext('2d'), {
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
                        animation: {
                            duration: thoiGianHoatAnh
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                backgroundColor: '#0F2747',
                                padding: 8,
                                cornerRadius: 6
                            }
                        },
                        cutout: '70%'
                    }
                });
            }
        <?php endif; ?>
    });
</script>

<?php require __DIR__ . '/layout/chan-trang.php'; ?>