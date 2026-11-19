<?php

/**
 * Giao diện Bàn làm việc Kiểm kê tài sản - Khớp chính xác Trang kiểm kê.pdf
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var array<string, mixed> $dot
 * @var list<array<string, mixed>> $danhSachChiTiet
 * @var string $thongBaoLoi
 * @var array<string, string> $boLocChiTiet
 * @var list<array<string, mixed>> $dotGanDay
 */

$tieuDeTrang = 'Kiểm kê';
require dirname(__DIR__) . '/layout/dau-trang.php';

$thongKe = $dot['thong_ke'] ?? [];
$dangMuon = (int)($thongKe['so_dang_muon'] ?? $thongKe['so_dang_muon_khi_mo'] ?? 0);
$phaiKiemThucTe = max(0, (int)($thongKe['tong_so_sach'] ?? 0) - $dangMuon);
$daKiem = (int)($thongKe['so_tim_thay'] ?? 0) + (int)($thongKe['so_chua_tim_thay'] ?? 0);
$chuaKiem = (int)($thongKe['so_con_lai_can_kiem'] ?? 0);
?>

<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang">Kiểm kê tài sản</h1>
        <div class="mo-ta-phu">Quét mã QR hoặc chọn thiết bị trong danh sách để đối chiếu thực tế tại phòng trực.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-phu">
            <?= icon('arrow-left', 'bieu-tuong-sm') ?>
            <span>Danh sách đợt</span>
        </a>
        <a href="<?= url('kiem-ke/tao.php') ?>" class="btn btn-phu">
            <?= icon('plus', 'bieu-tuong-sm') ?>
            <span>Tạo đợt kiểm kê</span>
        </a>
    </div>
</div>

<?php if (!empty($thongBaoLoi)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <?= icon('alert-circle', 'bieu-tuong-md') ?>
        <div class="flex-grow-1"><?= e($thongBaoLoi) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4 luong-quet-qr">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="tieu-de-khoi mb-0">Quét QR để đối chiếu thiết bị</h2>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="chkQuetLienTuc" checked>
                        <label class="form-check-label small fw-semibold text-success" for="chkQuetLienTuc">Quét liên tục</label>
                    </div>
                </div>
                <div class="row g-3 align-items-center">
                    <div class="col-12 col-sm-6">
                        <div class="khung-quet-camera" id="khungVideoContainer">
                            <video id="videoQuetCamera" playsinline class="d-none"></video>
                            <canvas id="canvasQuetCamera" class="d-none"></canvas>
                            <div class="overlay-ngam" id="overlayCamera">
                                <div class="fw-semibold text-dark mb-1">Khung camera</div>
                                <div class="chu-nho-11">Đưa mã QR thiết bị vào khung</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 d-flex flex-column gap-3">
                        <select id="chonCamera" class="form-select form-select-sm">
                            <option value="">Chọn camera</option>
                        </select>

                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control font-monospace" id="ma_quet_nhap"
                                placeholder="Nhập mã TS (VD: MC-01)..." autocomplete="off">
                            <button type="button" class="btn btn-chinh" onclick="xuLyThemMaThietBi()">
                                <?= icon('search', 'bieu-tuong-sm') ?>
                                <span>Đối chiếu</span>
                            </button>
                        </div>

                        <button type="button" class="btn btn-phu btn-sm w-100" id="btnBatTatCamera" onclick="batTatCamera()">
                            <?= icon('scan-line', 'bieu-tuong-sm') ?>
                            <span id="nhanNutCamera">Bật camera</span>
                        </button>
                    </div>
                </div>
                <div id="thongBaoQuetLoi" class="ket-qua-quet ket-qua-quet-loi mt-3 d-none"></div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="tieu-de-khoi mb-0">Thông tin đợt kiểm kê</h2>
                    <span class="huy-hieu huy-hieu-chinh">Đang thực hiện</span>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label">Mã đợt</label>
                        <input type="text" class="form-control font-monospace" value="<?= e($dot['ma_dot_kiem_ke'] ?? '') ?>" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Loại kiểm kê</label>
                        <input type="text" class="form-control" value="<?= e(nhan_loai_kiem_ke($dot['loai_kiem_ke'] ?? '')) ?>" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phạm vi tài sản</label>
                    <input type="text" class="form-control" value="Thiết bị thuộc phòng <?= e($dot['ten_phong_truc'] ?? '') ?>" readonly>
                </div>

                <div class="mb-0">
                    <label class="form-label">Nơi kiểm tra thực tế</label>
                    <input type="text" class="form-control" value="Phòng trực thiết bị" readonly>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-xanh">
            <div class="bieu-tuong-chinh">
                <?= icon('clipboard-check') ?>
            </div>
            <div>
                <div class="so-lieu" id="kpiPhaiKiem"><?= sprintf('%02d', $phaiKiemThucTe) ?></div>
                <div class="nhan-so-lieu">Phải kiểm thực tế</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('clipboard-check') ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-la">
            <div class="bieu-tuong-chinh">
                <?= icon('check-circle-2') ?>
            </div>
            <div>
                <div class="so-lieu" id="kpiDaKiem"><?= sprintf('%02d', $daKiem) ?></div>
                <div class="nhan-so-lieu">Đã kiểm</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('check-circle-2') ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-cam">
            <div class="bieu-tuong-chinh">
                <?= icon('alert-triangle') ?>
            </div>
            <div>
                <div class="so-lieu" id="kpiChuaKiem"><?= sprintf('%02d', $chuaKiem) ?></div>
                <div class="nhan-so-lieu">Chưa kiểm</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('alert-triangle') ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-do">
            <div class="bieu-tuong-chinh">
                <?= icon('repeat') ?>
            </div>
            <div>
                <div class="so-lieu" id="kpiDangMuon"><?= sprintf('%02d', $dangMuon) ?></div>
                <div class="nhan-so-lieu">Đang mượn theo phiếu</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('repeat') ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-3">
        <form action="<?= url('kiem-ke/thuc-hien.php') ?>" method="GET" class="row g-3 align-items-end">
            <input type="hidden" name="id" value="<?= (int)$dot['id'] ?>">
            <div class="col-12 col-md-5">
                <label class="form-label">Tìm thiết bị</label>
                <div class="o-tim-kiem">
                    <?= icon('search', 'bieu-tuong-sm') ?>
                    <input type="text" name="tu_khoa" class="form-control" placeholder="Mã / tên thiết bị (VD: MC-01, RMC-01...)"
                        value="<?= e($boLocChiTiet['tu_khoa'] ?? '') ?>">
                </div>
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label">Kết quả đối chiếu</label>
                <select name="trang_thai_loc" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="TIM_THAY" <?= (($boLocChiTiet['trang_thai_loc'] ?? '') === 'TIM_THAY') ? 'selected' : '' ?>>Có mặt</option>
                    <option value="CHUA_TIM_THAY" <?= (($boLocChiTiet['trang_thai_loc'] ?? '') === 'CHUA_TIM_THAY') ? 'selected' : '' ?>>Chưa tìm thấy</option>
                    <option value="DANG_MUON" <?= (($boLocChiTiet['trang_thai_loc'] ?? '') === 'DANG_MUON') ? 'selected' : '' ?>>Đang mượn</option>
                    <option value="CHUA_KIEM" <?= (($boLocChiTiet['trang_thai_loc'] ?? '') === 'CHUA_KIEM') ? 'selected' : '' ?>>Chưa kiểm</option>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-phu flex-grow-1">
                    <?= icon('search', 'bieu-tuong-sm') ?>
                    <span>Lọc</span>
                </button>
                <a href="<?= url('kiem-ke/thuc-hien.php?id=' . (int)$dot['id']) ?>" class="btn btn-phu" title="Đặt lại" aria-label="Đặt lại">
                    <?= icon('rotate-ccw', 'bieu-tuong-sm') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="tieu-de-khoi mb-0">Danh sách đối chiếu</h2>
        </div>
        <div class="mo-ta-phu">
            <?= count($danhSachChiTiet) ?> tài sản trong phạm vi
        </div>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Mã / tên thiết bị</th>
                    <th>Đối chiếu</th>
                    <th>Tình trạng thực tế</th>
                    <th>Ghi chú</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachChiTiet)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-phu">Không tìm thấy thiết bị nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danhSachChiTiet as $ct):
                        $laDangMuon = (($ct['trang_thai_so_sach'] ?? '') === 'DANG_MUON');
                        $ketQuaCt = (string)($ct['ket_qua'] ?? $ct['ket_qua_kiem_ke'] ?? 'CHUA_KIEM_TRA');
                        $daKiemDoi = in_array($ketQuaCt, ['TIM_THAY', 'CHUA_TIM_THAY'], true);
                    ?>
                        <tr id="hang_chi_tiet_<?= (int)$ct['id'] ?>" data-chi-tiet-id="<?= (int)$ct['id'] ?>" data-tai-san-id="<?= (int)$ct['tai_san_id'] ?>" data-ma-tai-san="<?= e($ct['ma_tai_san'] ?? '') ?>">
                            <td>
                                <div class="fw-semibold text-dark">
                                    <span class="font-monospace text-primary"><?= e($ct['ma_tai_san'] ?? '') ?></span> - <?= e($ct['ten_tai_san'] ?? '') ?>
                                </div>
                                <div class="mo-ta-phu">Phòng quản lý: <?= e($dot['ten_phong_truc'] ?? '') ?></div>
                            </td>
                            <td class="cot-doi-chieu">
                                <?php if ($laDangMuon): ?>
                                    <span class="huy-hieu huy-hieu-chinh">Đang mượn</span>
                                <?php elseif ($ketQuaCt === 'TIM_THAY'): ?>
                                    <span class="huy-hieu huy-hieu-thanh-cong">Có mặt</span>
                                <?php elseif ($ketQuaCt === 'CHUA_TIM_THAY'): ?>
                                    <span class="huy-hieu huy-hieu-nguy-hiem">Chưa tìm thấy</span>
                                <?php else: ?>
                                    <span class="huy-hieu huy-hieu-xam">Chưa kiểm</span>
                                <?php endif; ?>
                            </td>
                            <td class="cot-tinh-trang">
                                <?php if ($laDangMuon): ?>
                                    <span class="text-phu">Theo phiếu mượn</span>
                                <?php elseif (!$daKiemDoi): ?>
                                    <span class="text-phu">Chưa ghi nhận</span>
                                <?php elseif ($ketQuaCt === 'CHUA_TIM_THAY'): ?>
                                    <span class="text-danger">Không tìm thấy</span>
                                <?php elseif (($ct['tinh_trang_thuc_te'] ?? '') === 'HU_HONG'): ?>
                                    <span class="text-danger fw-semibold">Hỏng</span>
                                <?php else: ?>
                                    <span class="text-success fw-medium">Bình thường</span>
                                <?php endif; ?>
                            </td>
                            <td class="cot-ghi-chu">
                                <?php if (!empty($ct['ghi_chu'])): ?>
                                    <?= e($ct['ghi_chu']) ?>
                                <?php elseif ($laDangMuon && !empty($ct['ma_phieu_muon_hien_tai'])): ?>
                                    <span class="font-monospace text-primary"><?= e($ct['ma_phieu_muon_hien_tai']) ?></span>
                                <?php else: ?>
                                    <span class="text-phu">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end cot-thao-tac">
                                <?php if ($laDangMuon): ?>
                                    <?php if (!empty($ct['phieu_muon_id'])): ?>
                                        <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$ct['phieu_muon_id']) ?>" class="btn-vien-thuoc btn-vien-thuoc-phu">
                                            Xem phiếu
                                        </a>
                                    <?php else: ?>
                                        <span class="text-phu small">Đang mượn</span>
                                    <?php endif; ?>
                                <?php elseif ($daKiemDoi): ?>
                                    <button type="button" class="btn-vien-thuoc btn-vien-thuoc-phu text-success" onclick="moModalGhiNhan(<?= htmlspecialchars(json_encode($ct), ENT_QUOTES, 'UTF-8') ?>)">
                                        Đã lưu (Sửa)
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn-vien-thuoc btn-vien-thuoc-chinh" onclick="moModalGhiNhan(<?= htmlspecialchars(json_encode($ct), ENT_QUOTES, 'UTF-8') ?>)">
                                        Ghi nhận
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-4">
        <h2 class="tieu-de-khoi mb-2">Kết quả tạm thời</h2>
        <div class="mo-ta-phu mb-2">
            Bình thường: <?= (int)($thongKe['so_tim_thay_tot'] ?? 0) ?> •
            Hỏng: <?= (int)($thongKe['so_tim_thay_hong'] ?? 0) ?> •
            Không tìm thấy: <?= (int)($thongKe['so_chua_tim_thay'] ?? 0) ?> •
            Đang mượn: <?= $dangMuon ?> •
            Chưa kiểm: <?= $chuaKiem ?>
        </div>
        <div class="small text-dark mb-3">
            <?php if ($chuaKiem > 0): ?>
                Đối chiếu <?= $chuaKiem ?> thiết bị còn lại trước khi hoàn tất đợt kiểm kê.
            <?php else: ?>
                Đã đối chiếu đầy đủ mọi thiết bị trong phạm vi đợt kiểm kê.
            <?php endif; ?>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-phu">
                <?= icon('save', 'bieu-tuong-sm') ?>
                <span>Lưu nháp</span>
            </a>

            <form action="<?= url('kiem-ke/thuc-hien.php?id=' . (int)$dot['id']) ?>" method="POST" class="d-inline" data-xac-nhan="Bạn có chắc muốn chốt sổ và hoàn tất đợt kiểm kê này? Các sự cố phát sinh (nếu có) sẽ được tự động ghi nhận.">
                <?= csrf_field() ?>
                <input type="hidden" name="hanh_dong" value="hoan_tat">
                <button type="submit" class="btn btn-chinh">
                    <?= icon('check-circle-2', 'bieu-tuong-sm') ?>
                    <span>Hoàn tất kiểm kê</span>
                </button>
            </form>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="tieu-de-khoi mb-0">Đợt kiểm kê gần đây</h2>
        </div>
        <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-phu btn-sm">
            Xem tất cả
        </a>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Mã đợt</th>
                    <th>Phạm vi</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dotGanDay)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4 text-phu">Chưa có đợt kiểm kê nào gần đây.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dotGanDay as $d): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-primary font-monospace"><?= e($d['ma_dot_kiem_ke'] ?? '') ?></div>
                                <div class="mo-ta-phu"><?= e(nhan_loai_kiem_ke($d['loai_kiem_ke'] ?? '')) ?> • <?= !empty($d['thoi_gian_bat_dau']) ? date('d/m/Y', strtotime((string)$d['thoi_gian_bat_dau'])) : '' ?></div>
                            </td>
                            <td>Phòng <?= e($d['ten_phong_truc'] ?? '') ?></td>
                            <td>
                                <?php if (($d['trang_thai'] ?? '') === 'HOAN_TAT'): ?>
                                    <span class="huy-hieu huy-hieu-thanh-cong">Đã hoàn tất</span>
                                <?php elseif (($d['trang_thai'] ?? '') === 'DANG_KIEM_KE'): ?>
                                    <span class="huy-hieu huy-hieu-chinh">Đang thực hiện</span>
                                <?php else: ?>
                                    <span class="huy-hieu huy-hieu-xam">Đã hủy</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end cot-thao-tac">
                                <a href="<?= url('kiem-ke/chi-tiet.php?id=' . (int)$d['id']) ?>" class="btn-vien-thuoc btn-vien-thuoc-phu">Chi tiết</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modalGhiNhan" tabindex="-1" aria-labelledby="modalGhiNhanTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= url('kiem-ke/thuc-hien.php?id=' . (int)($dot['id'] ?? 0)) ?>" method="POST" onsubmit="chuanBiLuuGhiNhan()">
                <?= csrf_field() ?>
                <input type="hidden" name="hanh_dong" value="ghi_nhan">
                <input type="hidden" name="dot_kiem_ke_id" value="<?= (int)($dot['id'] ?? 0) ?>">
                <input type="hidden" name="chi_tiet_id" id="gn_chi_tiet_id">
                <input type="hidden" name="tai_san_id" id="gn_tai_san_id">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fs-6 fw-semibold" id="modalGhiNhanTitle">Đối chiếu thiết bị</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <div class="fw-bold fs-6 text-dark" id="gn_ten_thiet_bi"></div>
                        <div class="font-monospace text-primary small" id="gn_ma_tai_san"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kết quả đối chiếu</label>
                        <select name="ket_qua" id="gn_ket_qua" class="form-select" onchange="doiKetQuaGhiNhan(this.value)">
                            <option value="TIM_THAY">Có mặt (Tìm thấy)</option>
                            <option value="CHUA_TIM_THAY">Chưa tìm thấy (Mất)</option>
                        </select>
                    </div>

                    <div class="mb-3" id="gn_box_tinh_trang">
                        <label class="form-label">Tình trạng thực tế</label>
                        <select name="tinh_trang_thuc_te" id="gn_tinh_trang_thuc_te" class="form-select">
                            <option value="TOT">Bình thường (Tốt)</option>
                            <option value="HU_HONG">Hỏng (Hư hỏng)</option>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label">Ghi chú</label>
                        <input type="text" name="ghi_chu" id="gn_ghi_chu" class="form-control" placeholder="Ghi chú sự cố hoặc linh kiện (nếu có)">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-phu" data-bs-dismiss="modal">
                        <?= icon('x', 'bieu-tuong-sm') ?>
                        <span>Đóng</span>
                    </button>
                    <button type="submit" class="btn btn-chinh">
                        <?= icon('save', 'bieu-tuong-sm') ?>
                        <span>Lưu kết quả</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= url('assets/vendor/jsqr/jsQR.js') ?>"></script>
<script>
    const DS_CHI_TIET_KIEM_KE = <?= json_encode($danhSachChiTiet, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

    function phatAmThanhBip(thanhCong = true) {
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const audioCtx = new AudioCtx();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.type = 'sine';
            if (thanhCong) {
                osc.frequency.setValueAtTime(880, audioCtx.currentTime); // 880Hz (A5)
                gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.12);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.12);
            } else {
                osc.frequency.setValueAtTime(300, audioCtx.currentTime); // 300Hz (trầm cảnh báo)
                gain.gain.setValueAtTime(0.18, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.2);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.2);
            }
        } catch (e) {}
    }

    function moModalGhiNhan(ct) {
        document.getElementById('gn_chi_tiet_id').value = ct.id;
        document.getElementById('gn_tai_san_id').value = ct.tai_san_id;
        document.getElementById('gn_ten_thiet_bi').textContent = ct.ten_tai_san;
        document.getElementById('gn_ma_tai_san').textContent = ct.ma_tai_san;

        const kq = (ct.ket_qua === 'TIM_THAY' || ct.ket_qua === 'CHUA_TIM_THAY') ? ct.ket_qua : 'TIM_THAY';
        document.getElementById('gn_ket_qua').value = kq;
        document.getElementById('gn_tinh_trang_thuc_te').value = ct.tinh_trang_thuc_te || 'TOT';
        document.getElementById('gn_ghi_chu').value = ct.ghi_chu || '';
        doiKetQuaGhiNhan(kq);

        const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalGhiNhan'));
        m.show();
    }

    function doiKetQuaGhiNhan(val) {
        const box = document.getElementById('gn_box_tinh_trang');
        if (val === 'CHUA_TIM_THAY') {
            box.classList.add('d-none');
        } else {
            box.classList.remove('d-none');
        }
    }

    function chuanBiLuuGhiNhan() {
        const kq = document.getElementById('gn_ket_qua').value;
        const tt = document.getElementById('gn_tinh_trang_thuc_te').value;
        const gcInput = document.getElementById('gn_ghi_chu');
        if (gcInput && gcInput.value.trim() === '') {
            if (kq === 'CHUA_TIM_THAY') {
                gcInput.value = 'Chưa tìm thấy thiết bị tại phòng trực khi kiểm kê.';
            } else if (kq === 'TIM_THAY' && tt === 'HU_HONG') {
                gcInput.value = 'Phát hiện thiết bị hư hỏng trong quá trình kiểm kê.';
            }
        }
    }

    // Cập nhật số liệu KPI đợt kiểm kê theo thời gian thực
    function capNhatKpi(tk) {
        if (!tk) return;
        const dm = parseInt(tk.so_dang_muon || 0);
        const pkt = Math.max(0, parseInt(tk.tong_so_sach || 0) - dm);
        const dk = parseInt(tk.so_tim_thay || 0) + parseInt(tk.so_chua_tim_thay || 0);
        const ck = parseInt(tk.so_con_lai_can_kiem || 0);

        const elPhaiKiem = document.getElementById('kpiPhaiKiem');
        const elDaKiem = document.getElementById('kpiDaKiem');
        const elChuaKiem = document.getElementById('kpiChuaKiem');
        const elDangMuon = document.getElementById('kpiDangMuon');

        if (elPhaiKiem) elPhaiKiem.textContent = String(pkt).padStart(2, '0');
        if (elDaKiem) elDaKiem.textContent = String(dk).padStart(2, '0');
        if (elChuaKiem) elChuaKiem.textContent = String(ck).padStart(2, '0');
        if (elDangMuon) elDangMuon.textContent = String(dm).padStart(2, '0');
    }

    // Cập nhật hiển thị dòng thiết bị trong bảng đối chiếu mà không cần tải lại trang
    function capNhatHangSauGhiNhan(chiTietId, ketQua, tinhTrang, ghiChu, ctObj = null) {
        const hang = document.getElementById('hang_chi_tiet_' + chiTietId) || document.querySelector(`tr[data-chi-tiet-id="${chiTietId}"]`);
        if (!hang) return;

        const cotDoiChieu = hang.querySelector('.cot-doi-chieu');
        const cotTinhTrang = hang.querySelector('.cot-tinh-trang');
        const cotGhiChu = hang.querySelector('.cot-ghi-chu');
        const cotThaoTac = hang.querySelector('.cot-thao-tac');

        if (cotDoiChieu) {
            if (ketQua === 'TIM_THAY') {
                cotDoiChieu.innerHTML = '<span class="huy-hieu huy-hieu-thanh-cong">Có mặt</span>';
            } else if (ketQua === 'CHUA_TIM_THAY') {
                cotDoiChieu.innerHTML = '<span class="huy-hieu huy-hieu-nguy-hiem">Chưa tìm thấy</span>';
            } else {
                cotDoiChieu.innerHTML = '<span class="huy-hieu huy-hieu-xam">Chưa kiểm</span>';
            }
        }

        if (cotTinhTrang) {
            if (ketQua === 'CHUA_TIM_THAY') {
                cotTinhTrang.innerHTML = '<span class="text-danger">Không tìm thấy</span>';
            } else if (tinhTrang === 'HU_HONG') {
                cotTinhTrang.innerHTML = '<span class="text-danger fw-semibold">Hỏng</span>';
            } else {
                cotTinhTrang.innerHTML = '<span class="text-success fw-medium">Bình thường</span>';
            }
        }

        if (cotGhiChu) {
            cotGhiChu.textContent = ghiChu ? ghiChu : '-';
        }

        if (cotThaoTac) {
            const btn = cotThaoTac.querySelector('button');
            if (btn) {
                btn.className = 'btn-vien-thuoc btn-vien-thuoc-phu text-success';
                btn.textContent = 'Đã lưu (Sửa)';
                if (ctObj) {
                    btn.onclick = function() { moModalGhiNhan(ctObj); };
                }
            }
        }

        hang.classList.add('table-success');
        setTimeout(() => {
            hang.classList.remove('table-success');
        }, 1800);
        hang.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Xử lý gửi biểu mẫu ghi nhận trong modal bằng AJAX để không làm dừng camera hoặc tải lại trang
    document.getElementById('modalGhiNhan')?.querySelector('form')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        chuanBiLuuGhiNhan();

        const form = this;
        const btnSubmit = form.querySelector('button[type="submit"]');
        if (btnSubmit) btnSubmit.disabled = true;

        const formData = new FormData(form);
        try {
            const res = await fetch('<?= url("api/kiem-ke.php?hanh_dong=ghi_nhan") ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const json = await res.json();
            if (json.thanh_cong) {
                const ctId = document.getElementById('gn_chi_tiet_id').value;
                const kq = document.getElementById('gn_ket_qua').value;
                const tt = document.getElementById('gn_tinh_trang_thuc_te').value;
                const gc = document.getElementById('gn_ghi_chu').value;
                const tenTs = document.getElementById('gn_ten_thiet_bi').textContent;
                const maTs = document.getElementById('gn_ma_tai_san').textContent;

                capNhatHangSauGhiNhan(ctId, kq, tt, gc);
                if (json.thong_ke) {
                    capNhatKpi(json.thong_ke);
                }

                const modalInst = bootstrap.Modal.getInstance(document.getElementById('modalGhiNhan'));
                if (modalInst) modalInst.hide();

                if (typeof window.hienPopupQuetThietBi === 'function') {
                    window.hienPopupQuetThietBi({
                        loai: kq === 'TIM_THAY' ? (tt === 'HU_HONG' ? 'canh_bao' : 'thanh_cong') : 'loi',
                        tieuDe: kq === 'TIM_THAY' ? (tt === 'HU_HONG' ? 'Ghi nhận: Hư hỏng' : 'Đã ghi nhận: Có mặt') : 'Ghi nhận: Chưa tìm thấy',
                        maTaiSan: maTs,
                        tenTaiSan: tenTs
                    });
                }
                phatAmThanhBip(true);
            } else {
                alert(json.loi || 'Lỗi khi lưu kết quả kiểm kê.');
            }
        } catch (err) {
            form.submit();
        } finally {
            if (btnSubmit) btnSubmit.disabled = false;
        }
    });

    // Quét hoặc nhập mã thiết bị: tra cứu qua API kiểm kê, tự động ghi nhận khi bật quét liên tục
    async function xuLyThemMaThietBi(maQuetTrucTiep, laQuetCamera = false) {
        const inputEl = document.getElementById('ma_quet_nhap');
        const errEl = document.getElementById('thongBaoQuetLoi');
        if (errEl) errEl.classList.add('d-none');

        const ma = (maQuetTrucTiep || (inputEl ? inputEl.value : '')).trim();
        if (!ma) return;

        try {
            const urlTraCuu = '<?= url("api/kiem-ke.php") ?>?hanh_dong=tra_cuu&dot_kiem_ke_id=<?= (int)$dot["id"] ?>&ma_qr=' + encodeURIComponent(ma);
            const res = await fetch(urlTraCuu, { credentials: 'same-origin' });
            const json = await res.json();

            if (!json.thanh_cong || !json.chi_tiet) {
                const msgLoi = json.thong_diep || json.loi || ('Không tìm thấy thiết bị khớp với mã "' + ma + '".');
                if (errEl) {
                    errEl.textContent = msgLoi;
                    errEl.classList.remove('d-none');
                }
                if (typeof window.hienPopupQuetThietBi === 'function') {
                    window.hienPopupQuetThietBi({
                        loai: 'loi',
                        tieuDe: 'Không tìm thấy',
                        maTaiSan: ma.toUpperCase(),
                        tenTaiSan: msgLoi
                    });
                }
                phatAmThanhBip(false);
                return;
            }

            const ct = json.chi_tiet;
            if (inputEl) inputEl.value = '';

            // Nếu thiết bị đang trong trạng thái Đang mượn theo sổ sách
            if (ct.trang_thai_so_sach === 'DANG_MUON') {
                const msgDm = 'Thiết bị ' + (ct.ma_tai_san || ma) + ' đang được mượn theo phiếu, không cần kiểm thực tế.';
                if (errEl) {
                    errEl.textContent = msgDm;
                    errEl.classList.remove('d-none');
                }
                if (typeof window.hienPopupQuetThietBi === 'function') {
                    window.hienPopupQuetThietBi({
                        loai: 'canh_bao',
                        tieuDe: 'Đang mượn theo phiếu',
                        maTaiSan: ct.ma_tai_san,
                        tenTaiSan: ct.ten_tai_san
                    });
                }
                phatAmThanhBip(false);
                return;
            }

            const cheDoQuetLienTuc = document.getElementById('chkQuetLienTuc')?.checked ?? true;

            // Chế độ quét liên tục qua camera: Tự động ghi nhận "Có mặt - Tốt" ngay lập tức không ngắt quãng
            if (laQuetCamera && cheDoQuetLienTuc) {
                // Nếu thiết bị đã được đối chiếu Có mặt trước đó
                if (ct.ket_qua === 'TIM_THAY') {
                    if (typeof window.hienPopupQuetThietBi === 'function') {
                        window.hienPopupQuetThietBi({
                            loai: 'canh_bao',
                            tieuDe: 'Đã đối chiếu trước đó',
                            maTaiSan: ct.ma_tai_san,
                            tenTaiSan: ct.ten_tai_san
                        });
                    }
                    phatAmThanhBip(true);
                    capNhatHangSauGhiNhan(ct.id, 'TIM_THAY', ct.tinh_trang_thuc_te || 'TOT', ct.ghi_chu || '', ct);
                    return;
                }

                // Gửi API ghi nhận Có mặt trực tiếp
                const formData = new FormData();
                formData.append('csrf_token', '<?= csrf_token() ?>');
                formData.append('dot_kiem_ke_id', '<?= (int)$dot["id"] ?>');
                formData.append('chi_tiet_id', ct.id);
                formData.append('ket_qua', 'TIM_THAY');
                formData.append('tinh_trang_thuc_te', 'TOT');
                formData.append('ghi_chu', '');

                const resGhiNhan = await fetch('<?= url("api/kiem-ke.php?hanh_dong=ghi_nhan") ?>', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const jsonGn = await resGhiNhan.json();

                if (jsonGn.thanh_cong) {
                    phatAmThanhBip(true);
                    ct.ket_qua = 'TIM_THAY';
                    ct.tinh_trang_thuc_te = 'TOT';
                    if (typeof window.hienPopupQuetThietBi === 'function') {
                        window.hienPopupQuetThietBi({
                            loai: 'thanh_cong',
                            tieuDe: 'Đã ghi nhận: Có mặt',
                            maTaiSan: ct.ma_tai_san,
                            tenTaiSan: ct.ten_tai_san
                        });
                    }
                    capNhatHangSauGhiNhan(ct.id, 'TIM_THAY', 'TOT', '', ct);
                    if (jsonGn.thong_ke) {
                        capNhatKpi(jsonGn.thong_ke);
                    }
                } else {
                    phatAmThanhBip(false);
                    const errGn = jsonGn.loi || 'Không thể lưu ghi nhận.';
                    if (errEl) {
                        errEl.textContent = errGn;
                        errEl.classList.remove('d-none');
                    }
                }
                return;
            }

            // Nếu nhập thủ công hoặc tắt chế độ quét liên tục: Mở modal để chọn tình trạng / ghi chú
            moModalGhiNhan(ct);
        } catch (err) {
            if (errEl) {
                errEl.textContent = 'Lỗi kết nối máy chủ khi tra cứu mã QR.';
                errEl.classList.remove('d-none');
            }
        }
    }

    document.getElementById('ma_quet_nhap')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            xuLyThemMaThietBi('', false);
        }
    });

    // Camera
    let streamCamera = null;
    let dangQuet = false;
    let maVuaQuet = '';
    let thoiGianVuaQuet = 0;

    const videoEl = document.getElementById('videoQuetCamera');
    const canvasEl = document.getElementById('canvasQuetCamera');
    const overlayEl = document.getElementById('overlayCamera');
    const btnCamera = document.getElementById('btnBatTatCamera');
    const selCamera = document.getElementById('chonCamera');

    async function khoiTaoCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices || !selCamera) return;
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(d => d.kind === 'videoinput');
            selCamera.innerHTML = '<option value="">Chọn camera</option>';
            videoDevices.forEach((d, idx) => {
                const opt = document.createElement('option');
                opt.value = d.deviceId;
                opt.textContent = d.label || `Camera ${idx + 1}`;
                selCamera.appendChild(opt);
            });
        } catch (e) {}
        // Tự động mở camera khi vào trang kiểm kê
        await batCamera(true);
    }
    khoiTaoCamera();

    if (selCamera) {
        selCamera.addEventListener('change', async function() {
            if (dangQuet) {
                dungCamera();
            }
            await batCamera(false);
        });
    }

    async function batTatCamera() {
        if (dangQuet) {
            dungCamera();
        } else {
            await batCamera(false);
        }
    }

    async function batCamera(tuDong = false) {
        const devId = selCamera ? selCamera.value : '';
        const constraints = {
            video: devId ? {
                deviceId: {
                    exact: devId
                }
            } : {
                facingMode: 'environment'
            }
        };
        try {
            streamCamera = await navigator.mediaDevices.getUserMedia(constraints);
            videoEl.srcObject = streamCamera;
            videoEl.classList.remove('d-none');
            overlayEl.classList.add('d-none');
            await videoEl.play();
            dangQuet = true;
            const nhanNut = document.getElementById('nhanNutCamera');
            if (nhanNut) nhanNut.textContent = 'Tắt camera';
            btnCamera.classList.replace('btn-phu', 'btn-danger');
            requestAnimationFrame(quetVongLap);
        } catch (e) {
            if (!tuDong) {
                alert('Không thể mở camera. Vui lòng cấp quyền hoặc nhập mã thủ công.');
            }
        }
    }

    function dungCamera() {
        if (streamCamera) {
            streamCamera.getTracks().forEach(t => t.stop());
            streamCamera = null;
        }
        dangQuet = false;
        if (videoEl) videoEl.classList.add('d-none');
        if (overlayEl) overlayEl.classList.remove('d-none');
        const nhanNut = document.getElementById('nhanNutCamera');
        if (nhanNut) nhanNut.textContent = 'Bật camera';
        if (btnCamera) btnCamera.classList.replace('btn-danger', 'btn-phu');
    }
    window.dungCamera = dungCamera;

    // Vòng lặp quét QR liên tục không ngắt quãng
    function quetVongLap() {
        if (!dangQuet) return;
        if (videoEl.readyState === videoEl.HAVE_ENOUGH_DATA) {
            canvasEl.width = videoEl.videoWidth;
            canvasEl.height = videoEl.videoHeight;
            const ctx = canvasEl.getContext('2d');
            ctx.drawImage(videoEl, 0, 0, canvasEl.width, canvasEl.height);
            const imgData = ctx.getImageData(0, 0, canvasEl.width, canvasEl.height);
            const code = jsQR(imgData.data, imgData.width, imgData.height, {
                inversionAttempts: 'dontInvert'
            });

            if (code && code.data) {
                const bayGio = Date.now();
                // Chống quét lặp cùng 1 mã trong 2 giây khi giữ camera trước tem
                if (code.data === maVuaQuet && (bayGio - thoiGianVuaQuet) < 2000) {
                    requestAnimationFrame(quetVongLap);
                    return;
                }

                maVuaQuet = code.data;
                thoiGianVuaQuet = bayGio;

                xuLyThemMaThietBi(code.data, true);

                // Quét liên tục: Tiếp tục quét thiết bị tiếp theo sau 1.2s mà KHÔNG tắt camera
                setTimeout(() => {
                    if (dangQuet) requestAnimationFrame(quetVongLap);
                }, 1200);
                return;
            }
        }
        requestAnimationFrame(quetVongLap);
    }
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
