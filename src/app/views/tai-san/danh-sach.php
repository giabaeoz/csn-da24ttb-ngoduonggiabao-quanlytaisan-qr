<?php

/**
 * Giao diện Danh sách Tài sản - Tinh gọn, rõ ràng, mã QR tích hợp trực tiếp theo mã tài sản
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var list<array<string, mixed>> $danhSachTaiSan
 * @var array<string, int> $thongKe
 * @var list<array<string, mixed>> $danhSachLoai
 * @var list<array<string, mixed>> $danhSachPhongTruc
 * @var int $trang
 * @var int $tongSoTrang
 * @var int $tongSo
 * @var string $tuKhoa
 * @var int $loaiId
 * @var int $phongTrucId
 * @var string $trangThai
 */

$tieuDeTrang = 'Tài sản';
require dirname(__DIR__) . '/layout/dau-trang.php';

$tongTs = (int)($thongKe['tong_so'] ?? 0);
$coTheMuon = (int)($thongKe['san_sang'] ?? 0);
$dangMuon = (int)($thongKe['dang_muon'] ?? 0);
$trangThaiKhac = max(0, $tongTs - $coTheMuon - $dangMuon);

$viTriBatDau = ($trang - 1) * 15 + (count($danhSachTaiSan) > 0 ? 1 : 0);
$viTriKetThuc = ($trang - 1) * 15 + count($danhSachTaiSan);
?>

<div class="khung-trang-tai-san-co-dinh">
<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang">Tài sản</h1>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-phu" id="btnInDaChon" onclick="inCacTemDaChon()">
            <?= icon('printer', 'bieu-tuong-sm') ?>
            <span>In QR đã chọn (<span id="demInChon">0</span>)</span>
        </button>
        <?php if (co_quyen('quan_ly_tai_san')): ?>
            <a href="<?= url('tai-san/them.php') ?>" class="btn btn-chinh">
                <?= icon('plus', 'bieu-tuong-sm') ?>
                <span>Thêm tài sản</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-xanh">
            <div class="bieu-tuong-chinh">
                <?= icon('package') ?>
            </div>
            <div>
                <div class="so-lieu"><?= sprintf('%02d', $tongTs) ?></div>
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
                <div class="so-lieu"><?= sprintf('%02d', $coTheMuon) ?></div>
                <div class="nhan-so-lieu">Có thể mượn</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('check-circle-2') ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-cam">
            <div class="bieu-tuong-chinh">
                <?= icon('repeat') ?>
            </div>
            <div>
                <div class="so-lieu"><?= sprintf('%02d', $dangMuon) ?></div>
                <div class="nhan-so-lieu">Đang mượn</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('repeat') ?>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-kpi-pha-mau the-kpi-do">
            <div class="bieu-tuong-chinh">
                <?= icon('wrench') ?>
            </div>
            <div>
                <div class="so-lieu"><?= sprintf('%02d', $trangThaiKhac) ?></div>
                <div class="nhan-so-lieu">Bảo trì / Khác</div>
            </div>
            <div class="bieu-tuong-mo-bg" aria-hidden="true">
                <?= icon('wrench') ?>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-3">
        <form action="<?= url('tai-san/index.php') ?>" method="GET" class="row g-3 align-items-center">
            <div class="col-12 col-lg-4">
                <div class="o-tim-kiem">
                    <?= icon('search', 'bieu-tuong-sm') ?>
                    <input type="text" name="q" class="form-control"
                        placeholder="Tìm nhanh mã (MC-01, RMC-01, MIC-01...) hoặc tên..."
                        value="<?= e($tuKhoa ?? '') ?>">
                </div>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <select name="loai_id" class="form-select">
                    <option value="">Tất cả nhóm</option>
                    <?php foreach ($danhSachLoai as $loai): ?>
                        <option value="<?= (int)$loai['id'] ?>" <?= ((int)$loaiId === (int)$loai['id']) ? 'selected' : '' ?>>
                            <?= e($loai['ten_loai']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-4 col-lg-2">
                <select name="trang_thai" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="SAN_SANG" <?= ($trangThai === 'SAN_SANG') ? 'selected' : '' ?>>Có thể mượn</option>
                    <option value="DANG_MUON" <?= ($trangThai === 'DANG_MUON') ? 'selected' : '' ?>>Đang mượn</option>
                    <option value="BAO_TRI" <?= ($trangThai === 'BAO_TRI') ? 'selected' : '' ?>>Hỏng / Bảo trì</option>
                    <option value="NGUNG_SU_DUNG" <?= ($trangThai === 'NGUNG_SU_DUNG') ? 'selected' : '' ?>>Ngừng sử dụng</option>
                </select>
            </div>

            <div class="col-12 col-md-4 col-lg-2">
                <select name="phong_truc_id" class="form-select" <?= ($nguoiDung['vai_tro'] !== VAI_TRO_QUAN_TRI && !empty($nguoiDung['phong_truc_id'])) ? 'disabled' : '' ?>>
                    <?php if ($nguoiDung['vai_tro'] === VAI_TRO_QUAN_TRI): ?>
                        <option value="">Tất cả phòng trực</option>
                    <?php endif; ?>
                    <?php foreach ($danhSachPhongTruc as $pt): ?>
                        <?php if ($nguoiDung['vai_tro'] === VAI_TRO_QUAN_TRI || (int)$pt['id'] === (int)$nguoiDung['phong_truc_id']): ?>
                            <option value="<?= (int)$pt['id'] ?>" <?= ((int)$phongTrucId === (int)$pt['id']) ? 'selected' : '' ?>>
                                <?= e($pt['ten_phong_truc']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <?php if ($nguoiDung['vai_tro'] !== VAI_TRO_QUAN_TRI && !empty($nguoiDung['phong_truc_id'])): ?>
                    <input type="hidden" name="phong_truc_id" value="<?= (int)$nguoiDung['phong_truc_id'] ?>">
                <?php endif; ?>
            </div>

            <div class="col-12 col-lg-2 d-flex gap-2 justify-content-end">
                <button type="submit" class="btn btn-chinh flex-grow-1">
                    <?= icon('filter', 'bieu-tuong-sm') ?>
                    <span>Lọc</span>
                </button>
                <a href="<?= url('tai-san/index.php') ?>" class="btn btn-phu" title="Đặt lại bộ lọc" aria-label="Đặt lại bộ lọc">
                    <?= icon('rotate-ccw', 'bieu-tuong-sm') ?>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card the-danh-sach-tai-san-cuon mb-0">
    <div class="card-header d-flex justify-content-between align-items-center py-3">
        <div class="d-flex align-items-center gap-2">
            <h2 class="tieu-de-khoi mb-0">Danh sách tài sản</h2>
            <span class="huy-hieu huy-hieu-chinh"><?= $tongSo ?> thiết bị</span>
        </div>
        <div class="mo-ta-phu">
            Đã chọn <strong id="soLuongChon">0</strong> thiết bị để in QR
        </div>
    </div>
    <div class="table-responsive vung-cuon-bang-tai-san">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th class="cot-40 text-center">
                        <input type="checkbox" class="form-check-input o-chon-tai-san" id="checkAll" onclick="chonTatCa(this)" title="Chọn tất cả tài sản">
                    </th>
                    <th>Mã tài sản (QR)</th>
                    <th>Tên tài sản</th>
                    <th>Nhóm</th>
                    <th>Vị trí</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachTaiSan)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-phu">
                            Không tìm thấy tài sản nào phù hợp.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danhSachTaiSan as $ts): ?>
                        <tr>
                            <td class="text-center text-nowrap">
                                <input type="checkbox" class="form-check-input o-chon-tai-san check-item" value="<?= (int)$ts['id'] ?>" onchange="capNhatDemChon()" title="Chọn tài sản <?= e($ts['ma_tai_san']) ?>">
                            </td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-sm btn-phu font-monospace fw-bold py-1 px-2 text-nowrap"
                                    title="Bấm để xem mã QR của <?= e($ts['ma_tai_san']) ?>"
                                    onclick="xemQrModal('<?= e($ts['ma_tai_san']) ?>', '<?= e($ts['ten_tai_san']) ?>', '<?= url('tai-san/in-qr.php?id=' . (int)$ts['id']) ?>')">
                                    <?= icon('qr-code', 'bieu-tuong-sm') ?>
                                    <span><?= e($ts['ma_tai_san']) ?></span>
                                </button>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <?php $urlAnhRow = !empty($ts['hinh_anh']) ? tai_san_lay_url_anh($ts['hinh_anh']) : null; ?>
                                    <?php if ($urlAnhRow): ?>
                                        <a href="<?= e($urlAnhRow) ?>" target="_blank" title="Bấm xem ảnh phóng to" class="flex-shrink-0 d-block border rounded overflow-hidden shadow-sm" style="width: 42px; height: 42px; background-color: #f8fafc;">
                                            <img src="<?= e($urlAnhRow) ?>" alt="<?= e($ts['ten_tai_san']) ?>" class="w-100 h-100 object-fit-cover">
                                        </a>
                                    <?php else: ?>
                                        <div class="flex-shrink-0 d-flex align-items-center justify-content-center border rounded bg-light text-muted" style="width: 42px; height: 42px; background-color: #f8fafc;" title="Chưa có ảnh nhận diện">
                                            <?= icon('package', 'bieu-tuong-sm text-secondary') ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$ts['id']) ?>" class="fw-semibold text-dark text-decoration-none">
                                            <?= e($ts['ten_tai_san']) ?>
                                        </a>
                                        <?php if (!empty($ts['hang_san_xuat']) || !empty($ts['model'])): ?>
                                            <div class="mo-ta-phu text-nowrap">
                                                <?= e(trim(($ts['hang_san_xuat'] ?? '') . (!empty($ts['model']) ? ' · ' . $ts['model'] : ''))) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">
                                <span class="text-secondary"><?= e($ts['ten_loai']) ?></span>
                            </td>
                            <td class="text-nowrap">
                                <?php $vt = tai_san_thong_tin_vi_tri($ts); ?>
                                <?php if ($vt['la_dang_muon']): ?>
                                    <span class="huy-hieu huy-hieu-canh-bao" title="Đang có giao dịch mượn tại <?= e($vt['ten_hien_thi']) ?>">
                                        <?= icon('map-pin', 'bieu-tuong-sm') ?>
                                        <span><?= e($vt['ten_hien_thi']) ?></span>
                                    </span>
                                <?php elseif ($vt['la_co_dinh']): ?>
                                    <span class="huy-hieu huy-hieu-chinh" title="Tài sản cố định tại <?= e($vt['ten_hien_thi']) ?>">
                                        <?= icon('home', 'bieu-tuong-sm') ?>
                                        <span><?= e($vt['ten_hien_thi']) ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-secondary small d-inline-flex align-items-center gap-1" title="Linh hoạt lưu tại phòng trực">
                                        <?= icon('archive', 'bieu-tuong-sm text-muted') ?>
                                        <span><?= e($vt['ten_hien_thi']) ?></span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <?php if ($ts['trang_thai'] === 'SAN_SANG'): ?>
                                    <?php $ma = 'SAN_SANG';
                                    $nhan = 'Có thể mượn';
                                    require dirname(__DIR__) . '/partials/huy-hieu-trang-thai.php'; ?>
                                <?php elseif ($ts['trang_thai'] === 'DANG_MUON'): ?>
                                    <?php $ma = 'DANG_MUON';
                                    $nhan = 'Đang mượn';
                                    require dirname(__DIR__) . '/partials/huy-hieu-trang-thai.php'; ?>
                                <?php elseif ($ts['tinh_trang'] === 'HU_HONG' || $ts['trang_thai'] === 'BAO_TRI'): ?>
                                    <?php $ma = 'HU_HONG';
                                    $nhan = 'Hỏng / Bảo trì';
                                    require dirname(__DIR__) . '/partials/huy-hieu-trang-thai.php'; ?>
                                <?php else: ?>
                                    <?php $ma = 'NGUNG_SU_DUNG';
                                    $nhan = 'Ngừng sử dụng';
                                    require dirname(__DIR__) . '/partials/huy-hieu-trang-thai.php'; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-2 align-items-center">
                                    <button type="button" class="btn-bieu-tuong" title="Xem mã QR" aria-label="Xem mã QR"
                                        onclick="xemQrModal('<?= e($ts['ma_tai_san']) ?>', '<?= e($ts['ten_tai_san']) ?>', '<?= url('tai-san/in-qr.php?id=' . (int)$ts['id']) ?>')">
                                        <?= icon('qr-code', 'bieu-tuong-sm') ?>
                                    </button>
                                    <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$ts['id']) ?>" class="btn-bieu-tuong" title="Chi tiết thiết bị" aria-label="Chi tiết thiết bị">
                                        <?= icon('eye', 'bieu-tuong-sm') ?>
                                    </a>
                                    <?php if (co_quyen('quan_ly_tai_san')): ?>
                                        <a href="<?= url('tai-san/sua.php?id=' . (int)$ts['id']) ?>" class="btn-bieu-tuong" title="Sửa thiết bị" aria-label="Sửa thiết bị">
                                            <?= icon('pencil', 'bieu-tuong-sm') ?>
                                        </a>
                                        <?php if ($ts['trang_thai'] !== 'DANG_MUON'): ?>
                                            <form action="<?= url('tai-san/index.php') ?>" method="POST" class="d-inline" data-xac-nhan="Xác nhận xóa hoặc ngừng sử dụng tài sản <?= e($ts['ma_tai_san']) ?>?">
                                                <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                                                <input type="hidden" name="hanh_dong" value="xoa">
                                                <input type="hidden" name="id" value="<?= (int)$ts['id'] ?>">
                                                <button type="submit" class="btn-bieu-tuong btn-bieu-tuong-xoa text-danger" title="Xóa tài sản" aria-label="Xóa tài sản">
                                                    <?= icon('trash-2', 'bieu-tuong-sm') ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<div class="modal fade" id="modalXemQr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fs-6 fw-semibold">Mã QR tài sản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div id="vungVeQr" class="d-flex justify-content-center mb-3"></div>
                <div class="font-monospace fw-bold text-primary fs-5" id="qrMaTaiSan"></div>
                <div class="text-phu small mt-1" id="qrTenTaiSan"></div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                <button type="button" class="btn btn-phu btn-sm" data-bs-dismiss="modal">Đóng</button>
                <a id="btnInQrModal" href="#" target="_blank" class="btn btn-chinh btn-sm">
                    <?= icon('printer', 'bieu-tuong-sm') ?>
                    <span>In tem QR</span>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script>
    function chonTatCa(master) {
        const items = document.querySelectorAll('.check-item');
        items.forEach(el => el.checked = master.checked);
        capNhatDemChon();
    }

    function capNhatDemChon() {
        const checked = document.querySelectorAll('.check-item:checked');
        const dem = checked.length;
        document.getElementById('soLuongChon').textContent = dem;
        document.getElementById('demInChon').textContent = dem;
    }

    function inCacTemDaChon() {
        const checked = Array.from(document.querySelectorAll('.check-item:checked')).map(el => el.value);
        if (checked.length === 0) {
            alert('Vui lòng tích chọn ít nhất một tài sản để in tem.');
            return;
        }
        const url = '<?= url("tai-san/in-qr.php") ?>?ids=' + checked.join(',');
        window.open(url, '_blank');
    }

    function xemQrModal(maTaiSan, tenTaiSan, inUrl) {
        document.getElementById('qrMaTaiSan').textContent = maTaiSan;
        document.getElementById('qrTenTaiSan').textContent = tenTaiSan;
        document.getElementById('btnInQrModal').href = inUrl;

        const vungQr = document.getElementById('vungVeQr');
        vungQr.innerHTML = '';
        new QRCode(vungQr, {
            text: maTaiSan,
            width: 150,
            height: 150,
            correctLevel: QRCode.CorrectLevel.M
        });

        const modal = new bootstrap.Modal(document.getElementById('modalXemQr'));
        modal.show();
    }
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>