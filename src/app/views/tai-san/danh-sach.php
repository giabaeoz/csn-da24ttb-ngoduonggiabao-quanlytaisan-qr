<?php
/**
 * Giao diện Danh sách Tài sản - Khớp chính xác Trang tài sản.pdf
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Tài sản';
require dirname(__DIR__) . '/layout/dau-trang.php';

$tongTs = (int)($thongKe['tong_so'] ?? 0);
$coTheMuon = (int)($thongKe['san_sang'] ?? 0);
$dangMuon = (int)($thongKe['dang_muon'] ?? 0);
$trangThaiKhac = max(0, $tongTs - $coTheMuon - $dangMuon);

$viTriBatDau = ($trang - 1) * 15 + (count($danhSachTaiSan) > 0 ? 1 : 0);
$viTriKetThuc = ($trang - 1) * 15 + count($danhSachTaiSan);
?>

<!-- Tiêu đề trang & Các nút hành động chính -->
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang">Tài sản</h1>
        <div class="mo-ta-phu">Quản lý thông tin và mã QR cho từng tài sản.</div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-phu" id="btnInDaChon" onclick="inCacTemDaChon()">
            In QR đã chọn (<span id="demInChon">0</span>)
        </button>
        <?php if (co_quyen('quan_ly_tai_san')): ?>
        <a href="<?= url('tai-san/them.php') ?>" class="btn btn-phu">
            Thêm tài sản
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- 4 Thẻ số liệu thống kê -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu"><?= sprintf('%02d', $tongTs) ?></div>
            <div class="nhan-so-lieu">Tổng tài sản</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu"><?= sprintf('%02d', $coTheMuon) ?></div>
            <div class="nhan-so-lieu">Có thể mượn</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu"><?= sprintf('%02d', $dangMuon) ?></div>
            <div class="nhan-so-lieu">Đang mượn</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu"><?= sprintf('%02d', $trangThaiKhac) ?></div>
            <div class="nhan-so-lieu">Trạng thái khác</div>
        </div>
    </div>
</div>

<!-- Khối Bộ lọc tìm kiếm -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="<?= url('tai-san/index.php') ?>" method="GET">
            <div class="row g-2 align-items-end mb-2">
                <div class="col-12 col-md-3">
                    <label class="form-label">Tìm kiếm</label>
                    <input type="text" name="q" class="form-control" 
                           placeholder="Tìm mã hoặc tên tài sản" 
                           value="<?= e($tuKhoa ?? '') ?>">
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label">Nhóm tài sản</label>
                    <select name="loai_id" class="form-select">
                        <option value="">Tất cả nhóm</option>
                        <?php foreach ($danhSachLoai as $loai): ?>
                        <option value="<?= (int)$loai['id'] ?>" <?= ((int)$loaiId === (int)$loai['id']) ? 'selected' : '' ?>>
                            <?= e($loai['ten_loai']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label">Trạng thái</label>
                    <select name="trang_thai" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="SAN_SANG" <?= ($trangThai === 'SAN_SANG') ? 'selected' : '' ?>>Có thể mượn</option>
                        <option value="DANG_MUON" <?= ($trangThai === 'DANG_MUON') ? 'selected' : '' ?>>Đang mượn</option>
                        <option value="BAO_TRI" <?= ($trangThai === 'BAO_TRI') ? 'selected' : '' ?>>Hỏng / Bảo trì</option>
                        <option value="NGUNG_SU_DUNG" <?= ($trangThai === 'NGUNG_SU_DUNG') ? 'selected' : '' ?>>Ngừng sử dụng</option>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">Vị trí</label>
                    <select name="phong_truc_id" class="form-select">
                        <option value="">Tất cả vị trí</option>
                        <?php foreach ($danhSachPhongTruc as $pt): ?>
                        <option value="<?= (int)$pt['id'] ?>" <?= ((int)$phongTrucId === (int)$pt['id']) ? 'selected' : '' ?>>
                            Phòng trực: <?= e($pt['ten_phong_truc']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-phu">Lọc tài sản</button>
                    <a href="<?= url('tai-san/index.php') ?>" class="btn btn-phu">Đặt lại</a>
                </div>
                <div class="mo-ta-phu">
                    Hiển thị <?= count($danhSachTaiSan) ?> / <?= $tongSo ?> tài sản
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Bảng Danh sách tài sản -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="tieu-de-khoi mb-0">Danh sách tài sản</h2>
            <div class="mo-ta-phu">Mỗi dòng là một tài sản có mã QR riêng.</div>
        </div>
        <div class="mo-ta-phu">
            Đã chọn <span id="soLuongChon">0</span> tài sản
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 36px;">
                        <input type="checkbox" class="form-check-input" id="checkAll" onclick="chonTatCa(this)">
                    </th>
                    <th>Mã tài sản</th>
                    <th>Tên tài sản</th>
                    <th>Nhóm</th>
                    <th>Vị trí</th>
                    <th>Trạng thái</th>
                    <th>Mã QR</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachTaiSan)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-phu">
                        Không có dữ liệu.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($danhSachTaiSan as $ts): ?>
                <tr>
                    <td>
                        <input type="checkbox" class="form-check-input check-item" value="<?= (int)$ts['id'] ?>" onchange="capNhatDemChon()">
                    </td>
                    <td class="fw-semibold">
                        <?= e($ts['ma_tai_san']) ?>
                    </td>
                    <td>
                        <?= e($ts['ten_tai_san']) ?>
                    </td>
                    <td>
                        <?= e($ts['ten_loai']) ?>
                    </td>
                    <td>
                        <?php if (!empty($ts['ma_phong_hien_tai'])): ?>
                            P. <?= e($ts['ma_phong_hien_tai']) ?>
                        <?php else: ?>
                            Phòng trực
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($ts['trang_thai'] === 'SAN_SANG'): ?>
                            <?php $ma = 'SAN_SANG'; $nhan = 'Có thể mượn'; require dirname(__DIR__) . '/partials/huy-hieu-trang-thai.php'; ?>
                        <?php elseif ($ts['trang_thai'] === 'DANG_MUON'): ?>
                            <?php $ma = 'DANG_MUON'; $nhan = 'Đang mượn'; require dirname(__DIR__) . '/partials/huy-hieu-trang-thai.php'; ?>
                        <?php elseif ($ts['tinh_trang'] === 'HU_HONG' || $ts['trang_thai'] === 'BAO_TRI'): ?>
                            <?php $ma = 'HU_HONG'; $nhan = 'Hỏng'; require dirname(__DIR__) . '/partials/huy-hieu-trang-thai.php'; ?>
                        <?php else: ?>
                            <?php $ma = 'NGUNG_SU_DUNG'; $nhan = 'Ngừng sử dụng'; require dirname(__DIR__) . '/partials/huy-hieu-trang-thai.php'; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button type="button" class="btn btn-phu btn-sm py-1 px-2" 
                                onclick="xemQrModal('<?= e($ts['ma_qr']) ?>', '<?= e($ts['ma_tai_san']) ?>', '<?= e($ts['ten_tai_san']) ?>', '<?= url('tai-san/in-qr.php?id=' . (int)$ts['id']) ?>')">
                            Xem QR
                        </button>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$ts['id']) ?>" class="btn btn-phu btn-sm py-1 px-2">
                                Chi tiết
                            </a>
                            <?php if (co_quyen('quan_ly_tai_san')): ?>
                            <a href="<?= url('tai-san/sua.php?id=' . (int)$ts['id']) ?>" class="btn btn-phu btn-sm py-1 px-2">
                                Sửa
                            </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang dưới bảng -->
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
        <div class="mo-ta-phu">
            <?= $viTriBatDau ?>–<?= $viTriKetThuc ?> trong <?= $tongSo ?> tài sản
        </div>
        <div>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= ($trang <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= url('tai-san/index.php?' . http_build_query(array_merge($_GET, ['trang' => max(1, $trang - 1)]))) ?>">Trước</a>
                </li>
                <?php for ($i = 1; $i <= $tongSoTrang; $i++): ?>
                    <?php if ($i == 1 || $i == $tongSoTrang || abs($i - $trang) <= 1): ?>
                        <li class="page-item <?= ($i === $trang) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('tai-san/index.php?' . http_build_query(array_merge($_GET, ['trang' => $i]))) ?>"><?= $i ?></a>
                        </li>
                    <?php elseif ($i == 2 || $i == $tongSoTrang - 1): ?>
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    <?php endif; ?>
                <?php endfor; ?>
                <li class="page-item <?= ($trang >= $tongSoTrang) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= url('tai-san/index.php?' . http_build_query(array_merge($_GET, ['trang' => min($tongSoTrang, $trang + 1)]))) ?>">Sau</a>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Modal Xem mã QR nhanh -->
<div class="modal fade" id="modalXemQr" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fs-6 fw-semibold">Mã QR tài sản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body text-center p-4">
                <div id="vungVeQr" class="d-flex justify-content-center mb-3"></div>
                <div class="fw-bold text-dark fs-6" id="qrMaTaiSan"></div>
                <div class="text-phu small" id="qrTenTaiSan"></div>
                <div class="font-monospace text-muted mt-1" style="font-size: 11px;" id="qrChuoiMa"></div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                <button type="button" class="btn btn-phu btn-sm" data-bs-dismiss="modal">Đóng</button>
                <a id="btnInQrModal" href="#" target="_blank" class="btn btn-chinh btn-sm">In tem QR</a>
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

function xemQrModal(maQr, maTaiSan, tenTaiSan, inUrl) {
    document.getElementById('qrMaTaiSan').textContent = maTaiSan;
    document.getElementById('qrTenTaiSan').textContent = tenTaiSan;
    document.getElementById('qrChuoiMa').textContent = maQr;
    document.getElementById('btnInQrModal').href = inUrl;

    const vungQr = document.getElementById('vungVeQr');
    vungQr.innerHTML = '';
    new QRCode(vungQr, {
        text: maQr,
        width: 140,
        height: 140,
        correctLevel: QRCode.CorrectLevel.M
    });

    const modal = new bootstrap.Modal(document.getElementById('modalXemQr'));
    modal.show();
}
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
