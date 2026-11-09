<?php
/**
 * Giao diện Tiếp nhận Trả tài sản & Báo cáo sự cố (Asset Return View).
 *
 * @var array|null $phieuHienTai Dữ liệu phiếu mượn đang được chọn xử lý
 * @var array      $danhSachChoTra Danh sách các phiếu mượn đang chờ trả tại bàn trực
 * @var array      $phongTrucHienTai Bàn trực của cán bộ đang đăng nhập
 * @var bool       $dangBiKhoaKiemKe Trạng thái khóa kiểm kê của bàn trực
 * @var string     $thongBaoLoi Thông báo lỗi thao tác
 * @var string     $thongBaoLoiTimKiem Thông báo lỗi tra cứu
 * @var string     $tieuDeTrang Tiêu đề trang
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';

$laDangMuon = ($phieuHienTai && ($phieuHienTai['trang_thai'] ?? '') === PHIEU_MUON_DANG_MUON);
$quaHan = false;
if ($laDangMuon && !empty($phieuHienTai['han_tra'])) {
    $quaHan = strtotime((string)$phieuHienTai['han_tra']) < time();
}
$dsChiTiet = $phieuHienTai['chi_tiet'] ?? [];
$soChuaTra = 0;
foreach ($dsChiTiet as $it) {
    if (empty($it['thoi_gian_tra'])) {
        $soChuaTra++;
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">📥 Tiếp nhận Trả tài sản & Báo cáo sự cố</h1>
        <p class="text-secondary small mb-0">
            Quét mã QR phiếu hoặc tem thiết bị để tra cứu nhanh, kiểm tra tình trạng và ghi nhận nhận trả về bàn trực: 
            <strong class="text-primary"><?= e($phongTrucHienTai['ten_phong_truc'] ?? 'D6') ?></strong>
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('muon-tra/muon.php') ?>" class="btn btn-outline-primary fw-semibold shadow-sm">
            📤 Lập phiếu mượn mới
        </a>
        <a href="<?= url('muon-tra/index.php') ?>" class="btn btn-outline-secondary fw-semibold shadow-sm">
            📋 Lịch sử mượn trả
        </a>
    </div>
</div>

<!-- Cảnh báo khóa kiểm kê -->
<?php if ($dangBiKhoaKiemKe): ?>
<div class="alert alert-danger d-flex align-items-center rounded-3 shadow-sm mb-4" role="alert">
    <div class="fs-4 me-3">🔒</div>
    <div>
        <h6 class="fw-bold mb-1">Bàn trực đang diễn ra đợt kiểm kê tài sản!</h6>
        <div class="small">
            Mọi thao tác tiếp nhận trả thiết bị tạm thời bị khóa cho đến khi đợt kiểm kê được chốt hoặc hủy.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Thông báo lỗi thao tác -->
<?php if ($thongBaoLoi !== ''): ?>
<div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
    <strong>Lỗi tiếp nhận trả:</strong> <?= e($thongBaoLoi) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
</div>
<?php endif; ?>

<!-- KHUNG TRA CỨU & QUÉT QR -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-body p-4">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-lg-8">
                <form action="<?= url('muon-tra/tra.php') ?>" method="GET" class="d-flex gap-2" id="formTraCuu">
                    <div class="input-group input-group-lg flex-grow-1">
                        <span class="input-group-text bg-light text-muted border-end-0">
                            🔍
                        </span>
                        <input type="text" class="form-control border-start-0 font-monospace fs-6" name="tu_khoa" id="tu_khoa"
                               placeholder="Quét mã QR tem thiết bị / Quét mã phiếu / Nhập MSSV hoặc Mã TS..." 
                               value="<?= e($_GET['tu_khoa'] ?? $_GET['ma_phieu'] ?? '') ?>" autocomplete="off" autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">
                        Tra cứu
                    </button>
                    <button type="button" class="btn btn-outline-success fw-semibold px-3" id="btnToggleCamera" onclick="toggleCamera()">
                        📹 Quét QR
                    </button>
                </form>
                <?php if ($thongBaoLoiTimKiem !== ''): ?>
                    <div class="text-danger small fw-semibold mt-2">
                        ⚠️ <?= e($thongBaoLoiTimKiem) ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-12 col-lg-4 text-lg-end">
                <span class="badge bg-light text-dark border p-2 text-start">
                    💡 Hỗ trợ: Mã QR phiếu, mã QR tem thiết bị, mã TS (MC-0001), hoặc MSSV người mượn.
                </span>
            </div>
        </div>

        <!-- KHUNG CAMERA QUÉT QR (Dùng thư viện jsQR offline) -->
        <div id="khuVucCamera" class="d-none text-center mt-3 p-3 bg-dark rounded-4">
            <div class="position-relative d-inline-block">
                <video id="videoQuet" playsinline style="max-width: 100%; max-height: 250px; border-radius: 8px;"></video>
                <canvas id="canvasQuet" class="d-none"></canvas>
                <div class="position-absolute top-50 start-50 translate-middle border border-2 border-success rounded-3" 
                     style="width: 180px; height: 180px; pointer-events: none; box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.4);">
                </div>
            </div>
            <div class="text-white small mt-2">
                Hướng camera về phía mã QR trên tem thiết bị hoặc trên phiếu mượn để hệ thống tự động tìm kiếm...
            </div>
        </div>
    </div>
</div>

<?php if ($phieuHienTai !== null): ?>
<!-- KHỐI XỬ LÝ NHẬN TRẢ CHO PHIẾU ĐANG CHỌN -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4 border-top border-primary border-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold mb-0 text-dark">
                Phiếu mượn: <span class="font-monospace text-primary"><?= e($phieuHienTai['ma_phieu']) ?></span>
            </h5>
            <?php if ($laDangMuon): ?>
                <span class="badge bg-warning text-dark"><i class="bi bi-clock-history"></i> Đang mượn</span>
                <?php if ($quaHan): ?>
                    <span class="badge bg-danger"><i class="bi bi-exclamation-octagon-fill"></i> Quá hạn</span>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Đã trả đủ</span>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$phieuHienTai['id']) ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                🔍 Xem chi tiết phiếu
            </a>
            <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-sm btn-outline-dark">
                ✖ Đóng / Chọn phiếu khác
            </a>
        </div>
    </div>

    <div class="card-body p-4">
        <!-- Thông tin tóm tắt phiếu mượn -->
        <div class="row g-3 p-3 bg-light rounded-3 mb-4">
            <div class="col-md-3">
                <span class="text-muted small d-block">Người mượn:</span>
                <strong class="text-dark fs-6"><?= e($phieuHienTai['ten_nguoi_muon']) ?></strong>
                <div class="small text-muted font-monospace">MSSV: <?= e($phieuHienTai['ma_so_truong']) ?></div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">Phòng học phục vụ:</span>
                <span class="badge bg-dark font-monospace"><?= e($phieuHienTai['ma_phong']) ?></span>
                <span class="small text-muted ms-1"><?= e($phieuHienTai['ten_phong']) ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">Thời gian mượn:</span>
                <span class="small fw-semibold"><?= dinh_dang_ngay_gio($phieuHienTai['thoi_gian_muon']) ?></span>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">Hạn trả dự kiến:</span>
                <span class="small fw-bold <?= $quaHan ? 'text-danger' : 'text-dark' ?>">
                    <?= dinh_dang_ngay_gio($phieuHienTai['han_tra']) ?>
                    <?= $quaHan ? '<span class="badge bg-danger ms-1">Quá hạn</span>' : '' ?>
                </span>
            </div>
        </div>

        <?php if ($soChuaTra === 0): ?>
            <div class="alert alert-success d-flex align-items-center mb-0 rounded-3">
                <i class="bi bi-check-circle-fill fs-3 me-3 text-success"></i>
                <div>
                    <h6 class="fw-bold mb-1">Toàn bộ thiết bị trong phiếu mượn này đã được hoàn trả!</h6>
                    <div class="small text-muted">Không còn thiết bị nào cần tiếp nhận trả cho phiếu này.</div>
                </div>
            </div>
        <?php else: ?>
            <!-- BIỂU MẪU TIẾP NHẬN TRẢ -->
            <form action="<?= url('muon-tra/tra.php') ?>" method="POST" id="formTiepNhanTra">
                <?= csrf_field() ?>
                <input type="hidden" name="phieu_muon_id" value="<?= (int)$phieuHienTai['id'] ?>">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0">
                        Danh sách thiết bị cần hoàn trả (Còn lại <?= $soChuaTra ?> / <?= count($dsChiTiet) ?>)
                    </h6>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" onclick="chonTatCa(true)">Chọn tất cả</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="chonTatCa(false)">Bỏ chọn</button>
                    </div>
                </div>

                <div class="table-responsive border rounded-3 mb-4">
                    <table class="table table-hover align-middle mb-0" id="bangThietBiTra">
                        <thead class="table-light small">
                            <tr>
                                <th style="width: 45px;" class="text-center">
                                    <input type="checkbox" class="form-check-input" id="checkAll" onchange="chonTatCa(this.checked)" checked>
                                </th>
                                <th style="width: 50px;" class="text-center">STT</th>
                                <th>Thiết bị / Tem QR</th>
                                <th>Loại & Hãng/Model</th>
                                <th>Tình trạng mượn</th>
                                <th style="width: 220px;">Tình trạng khi trả</th>
                                <th>Ghi chú / Mô tả hư hỏng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dsChiTiet as $idx => $ctm): ?>
                                <?php 
                                    $daTra = !empty($ctm['thoi_gian_tra']);
                                    $ctmId = (int)$ctm['id'];
                                ?>
                                <tr class="<?= $daTra ? 'table-light text-muted opacity-75' : 'hang-cho-tra' ?>" id="hang_<?= $ctmId ?>">
                                    <td class="text-center">
                                        <?php if ($daTra): ?>
                                            <span class="text-success fs-5">✓</span>
                                        <?php else: ?>
                                            <input type="checkbox" class="form-check-input check-thiet-bi" 
                                                   name="chi_tiet_id[]" value="<?= $ctmId ?>" 
                                                   id="cb_<?= $ctmId ?>" onchange="capNhatDong(<?= $ctmId ?>)" checked>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace small"><?= $idx + 1 ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($ctm['ten_tai_san']) ?></div>
                                        <div class="small">
                                            <span class="badge bg-secondary font-monospace"><?= e($ctm['ma_tai_san']) ?></span>
                                            <code class="text-dark bg-light px-2 py-0 rounded"><?= e($ctm['ma_qr']) ?></code>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small text-muted"><?= e($ctm['ten_loai']) ?></div>
                                        <?php if (!empty($ctm['hang_san_xuat']) || !empty($ctm['model'])): ?>
                                            <span class="badge bg-light text-dark border font-monospace small">
                                                <?= e($ctm['hang_san_xuat'] ?? '') ?> <?= !empty($ctm['model']) ? '/ ' . e($ctm['model']) : '' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <?= ($ctm['tinh_trang_khi_muon'] ?? 'TOT') === 'TOT' ? 'Tốt' : 'Hư hỏng' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($daTra): ?>
                                            <span class="badge bg-success"><i class="bi bi-check2"></i> Đã trả</span>
                                            <div class="small text-muted mt-1"><?= dinh_dang_ngay_gio($ctm['thoi_gian_tra']) ?></div>
                                        <?php else: ?>
                                            <select class="form-select form-select-sm" name="tinh_trang[<?= $ctmId ?>]" 
                                                    id="tinh_trang_<?= $ctmId ?>" onchange="doiTinhTrang(<?= $ctmId ?>)">
                                                <option value="TOT" selected>✅ Tốt / Nguyên vẹn</option>
                                                <option value="HU_HONG">⚠️ Hư hỏng / Sự cố</option>
                                            </select>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($daTra): ?>
                                            <div class="small text-muted"><?= e($ctm['ghi_chu_tra'] ?? '---') ?></div>
                                            <div class="small text-secondary">Cán bộ nhận: <?= e($ctm['ten_nhan_vien_nhan'] ?? '---') ?></div>
                                        <?php else: ?>
                                            <input type="text" class="form-control form-control-sm" 
                                                   name="ghi_chu[<?= $ctmId ?>]" id="ghi_chu_<?= $ctmId ?>" 
                                                   placeholder="Ghi chú thêm...">
                                            <div class="text-danger small fw-semibold mt-1 d-none" id="loi_ghi_chu_<?= $ctmId ?>">
                                                * Bắt buộc ghi rõ mô tả hư hỏng
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        Đang chọn nhận trả: <strong class="text-primary" id="dem_so_chon"><?= $soChuaTra ?></strong> / <?= $soChuaTra ?> thiết bị chưa trả.
                    </span>
                    <button type="submit" class="btn btn-primary btn-lg fw-semibold px-4 shadow-sm" id="btnXacNhanTra">
                        📥 Xác nhận tiếp nhận trả (<span id="dem_nut_tra"><?= $soChuaTra ?></span>)
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- DANH SÁCH CÁC PHIẾU ĐANG CHỜ TRẢ TẠI BÀN TRỰC -->
<div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">
            📋 Các phiếu mượn đang chờ trả tại bàn trực: <strong class="text-primary"><?= e($phongTrucHienTai['ten_phong_truc'] ?? 'D6') ?></strong>
        </h5>
        <span class="badge bg-secondary rounded-pill"><?= count($danhSachChoTra) ?> phiếu</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th class="ps-3" style="width: 140px;">Mã phiếu</th>
                    <th>Người mượn</th>
                    <th>Phòng học</th>
                    <th>Thời gian mượn</th>
                    <th>Hạn trả</th>
                    <th class="text-center">Chưa trả / Tổng</th>
                    <th class="text-end pe-3" style="width: 140px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachChoTra)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            🎉 Hiện tại không có phiếu mượn nào đang chờ trả tại bàn trực này.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danhSachChoTra as $cho): ?>
                        <tr class="<?= $cho['la_qua_han'] ? 'table-warning' : '' ?>">
                            <td class="ps-3">
                                <a href="<?= url('muon-tra/tra.php?id=' . (int)$cho['id']) ?>" class="font-monospace fw-bold text-decoration-none text-primary">
                                    <?= e($cho['ma_phieu']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($cho['ten_nguoi_muon']) ?></div>
                                <div class="small text-muted font-monospace">MSSV: <?= e($cho['ma_so_truong']) ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($cho['ma_phong']) ?></span>
                                <div class="small text-muted text-truncate" style="max-width: 130px;"><?= e($cho['ten_phong']) ?></div>
                            </td>
                            <td><?= dinh_dang_ngay_gio($cho['thoi_gian_muon']) ?></td>
                            <td>
                                <span class="<?= $cho['la_qua_han'] ? 'text-danger fw-bold' : '' ?>">
                                    <?= dinh_dang_ngay_gio($cho['han_tra']) ?>
                                    <?php if ($cho['la_qua_han']): ?>
                                        <span class="badge bg-danger small ms-1">Quá hạn</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-warning text-dark fs-6">
                                    <?= $cho['so_chua_tra'] ?> / <?= $cho['tong_so_thiet_bi'] ?>
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="<?= url('muon-tra/tra.php?id=' . (int)$cho['id']) ?>" class="btn btn-sm btn-primary fw-semibold shadow-sm">
                                    📥 Nhận trả
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Nạp thư viện jsQR cục bộ để quét mã QR bằng camera -->
<script src="<?= url('assets/vendor/jsqr/jsQR.js') ?>"></script>

<script>
let videoStream = null;
let cameraRunning = false;

// 1. Quét mã QR bằng Camera qua jsQR
function toggleCamera() {
    const khuVuc = document.getElementById('khuVucCamera');
    const btn = document.getElementById('btnToggleCamera');
    const video = document.getElementById('videoQuet');
    const canvas = document.getElementById('canvasQuet');
    const ctx = canvas.getContext('2d');

    if (!cameraRunning) {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Trình duyệt của bạn không hỗ trợ truy cập camera.');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                videoStream = stream;
                video.srcObject = stream;
                video.setAttribute('playsinline', true);
                video.play();
                cameraRunning = true;
                khuVuc.classList.remove('d-none');
                btn.innerHTML = '⏹ Tắt Camera';
                btn.classList.replace('btn-outline-success', 'btn-outline-danger');
                requestAnimationFrame(tickQuetQr);
            })
            .catch(function(err) {
                alert('Không thể mở camera: ' + err.message);
            });
    } else {
        tatCamera();
    }
}

function tatCamera() {
    const khuVuc = document.getElementById('khuVucCamera');
    const btn = document.getElementById('btnToggleCamera');
    const video = document.getElementById('videoQuet');

    if (videoStream) {
        videoStream.getTracks().forEach(track => track.stop());
        videoStream = null;
    }
    video.srcObject = null;
    cameraRunning = false;
    khuVuc.classList.add('d-none');
    btn.innerHTML = '📹 Quét QR';
    btn.classList.replace('btn-outline-danger', 'btn-outline-success');
}

function tickQuetQr() {
    if (!cameraRunning) return;

    const video = document.getElementById('videoQuet');
    const canvas = document.getElementById('canvasQuet');
    const ctx = canvas.getContext('2d');

    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.height = video.videoHeight;
        canvas.width = video.videoWidth;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert',
        });

        if (code && code.data) {
            const qrText = code.data.trim();
            tatCamera();
            document.getElementById('tu_khoa').value = qrText;
            document.getElementById('formTraCuu').submit();
            return;
        }
    }
    requestAnimationFrame(tickQuetQr);
}

// 2. Xử lý đổi tình trạng thiết bị khi nhận trả (Tốt vs Hư hỏng)
function doiTinhTrang(id) {
    const select = document.getElementById('tinh_trang_' + id);
    const inputGhiChu = document.getElementById('ghi_chu_' + id);
    const loiGhiChu = document.getElementById('loi_ghi_chu_' + id);

    if (select.value === 'HU_HONG') {
        inputGhiChu.classList.add('border-danger', 'bg-warning-subtle');
        inputGhiChu.placeholder = 'Ghi rõ chi tiết hư hỏng / sự cố thiết bị...';
        inputGhiChu.focus();
        loiGhiChu.classList.remove('d-none');
    } else {
        inputGhiChu.classList.remove('border-danger', 'bg-warning-subtle');
        inputGhiChu.placeholder = 'Ghi chú thêm...';
        loiGhiChu.classList.add('d-none');
    }
}

// 3. Chọn tất cả hoặc bỏ chọn các thiết bị cần trả
function chonTatCa(chon) {
    const checkboxes = document.querySelectorAll('.check-thiet-bi');
    checkboxes.forEach(cb => {
        cb.checked = chon;
        capNhatDong(cb.value);
    });
    capNhatDem();
}

function capNhatDong(id) {
    const cb = document.getElementById('cb_' + id);
    const tr = document.getElementById('hang_' + id);
    const select = document.getElementById('tinh_trang_' + id);
    const inputGhiChu = document.getElementById('ghi_chu_' + id);

    if (cb && tr) {
        if (cb.checked) {
            tr.classList.remove('opacity-50');
            select.disabled = false;
            inputGhiChu.disabled = false;
        } else {
            tr.classList.add('opacity-50');
            select.disabled = true;
            inputGhiChu.disabled = true;
        }
    }
    capNhatDem();
}

function capNhatDem() {
    const count = document.querySelectorAll('.check-thiet-bi:checked').length;
    const demSoChon = document.getElementById('dem_so_chon');
    const demNutTra = document.getElementById('dem_nut_tra');
    const btn = document.getElementById('btnXacNhanTra');

    if (demSoChon) demSoChon.textContent = count;
    if (demNutTra) demNutTra.textContent = count;
    if (btn) btn.disabled = (count === 0);
}

// 4. Kiểm tra trước khi submit form
const formTra = document.getElementById('formTiepNhanTra');
if (formTra) {
    formTra.addEventListener('submit', function(e) {
        const checked = document.querySelectorAll('.check-thiet-bi:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert('Vui lòng tích chọn ít nhất một thiết bị cần tiếp nhận trả.');
            return;
        }

        let coLoi = false;
        checked.forEach(cb => {
            const id = cb.value;
            const select = document.getElementById('tinh_trang_' + id);
            const inputGhiChu = document.getElementById('ghi_chu_' + id);
            if (select && select.value === 'HU_HONG') {
                if (!inputGhiChu || inputGhiChu.value.trim() === '') {
                    coLoi = true;
                    inputGhiChu.focus();
                    inputGhiChu.classList.add('is-invalid');
                }
            }
        });

        if (coLoi) {
            e.preventDefault();
            alert('Thiết bị đánh dấu Hư hỏng bắt buộc phải có mô tả chi tiết hư hỏng!');
            return;
        }
    });
}
</script>

<?php
require dirname(__DIR__) . '/layout/chan-trang.php';
