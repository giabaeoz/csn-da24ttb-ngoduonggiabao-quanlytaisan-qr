<?php

/**
 * Giao diện Tiếp nhận Trả tài sản - Khớp chính xác Trang trả tài sản.pdf
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var bool $dangBiKhoaKiemKe
 * @var array<string, mixed>|null $phieuHienTai
 * @var list<array<string, mixed>> $danhSachChoTra
 * @var string $thongBaoLoi
 * @var string $thongBaoLoiTimKiem
 */

$tieuDeTrang = 'Mượn - trả';
require dirname(__DIR__) . '/layout/dau-trang.php';

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

<!-- Tiêu đề trang & Tabs chuyển đổi -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang">Mượn - trả tài sản</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('muon-tra/muon.php') ?>" class="btn btn-phu">Mượn</a>
        <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-chinh">Trả</a>
    </div>
</div>

<?php if ($dangBiKhoaKiemKe): ?>
    <div class="alert alert-danger mb-4" role="alert">
        Bàn trực hiện đang diễn ra đợt kiểm kê tài sản. Hoạt động nhận trả tạm thời bị khóa.
    </div>
<?php endif; ?>

<?php if ($thongBaoLoi !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <?= e($thongBaoLoi) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
<?php endif; ?>

<form action="<?= url('muon-tra/tra.php') ?>" method="POST" id="formNhanTra" class="<?= $dangBiKhoaKiemKe ? 'opacity-50 pe-none' : '' ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="phieu_muon_id" value="<?= (int)($phieuHienTai['id'] ?? 0) ?>">

    <div class="row g-4">
        <!-- CỘT TRÁI: QUÉT QR TRẢ & PHIẾU TRẢ TÀI SẢN -->
        <div class="col-12 col-lg-5">
            <!-- Khối: Quét QR thiết bị trả -->
            <div class="card mb-4">
                <div class="card-body p-3">
                    <h2 class="tieu-de-khoi mb-3">Quét QR thiết bị trả</h2>
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-sm-6">
                            <div class="khung-quet-camera" id="khungVideoContainer">
                                <video id="videoQuetCamera" playsinline style="display:none;"></video>
                                <canvas id="canvasQuetCamera" style="display:none;"></canvas>
                                <div class="overlay-ngam" id="overlayCamera">
                                    <div class="fw-semibold text-dark mb-1">Khung camera</div>
                                    <div style="font-size: 11px;">Đưa mã QR thiết bị vào khung</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 d-flex flex-column gap-2">
                            <select id="chonCamera" class="form-select form-select-sm">
                                <option value="">Chọn camera</option>
                            </select>

                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="ma_tra_cuu_nhap"
                                    placeholder="Mã thiết bị / mã phiếu / mã số"
                                    value="<?= e($_GET['tu_khoa'] ?? $_GET['ma_phieu'] ?? '') ?>" autocomplete="off">
                                <button type="button" class="btn btn-phu" onclick="thucHienTraCuu()">
                                    Tìm
                                </button>
                            </div>

                            <button type="button" class="btn btn-phu btn-sm w-100" id="btnBatTatCamera" onclick="batTatCamera()">
                                Bật camera
                            </button>
                        </div>
                    </div>
                    <?php if ($thongBaoLoiTimKiem !== ''): ?>
                        <div class="loi-truong mt-2"><?= e($thongBaoLoiTimKiem) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Khối: Phiếu trả tài sản -->
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="tieu-de-khoi mb-3">Phiếu trả tài sản</h2>
                    <?php if ($phieuHienTai): ?>
                        <div class="mb-3">
                            <div class="fw-semibold text-dark">
                                <?= e($phieuHienTai['ho_ten_nguoi_muon']) ?> - <?= e($phieuHienTai['ma_so_nguoi_muon']) ?> - <?= e($phieuHienTai['lop_don_vi'] ?? '---') ?>
                            </div>
                            <div class="mo-ta-phu">
                                Phiếu mượn: <?= e($phieuHienTai['ma_phieu_muon']) ?> • Phòng: <?= e($phieuHienTai['ma_phong']) ?>
                            </div>
                            <div class="mo-ta-phu">
                                Trả: <?= date('d/m/Y H:i') ?> •
                                <span class="<?= $quaHan ? 'text-danger fw-semibold' : 'text-success' ?>">
                                    <?= $quaHan ? 'Quá hạn' : 'Trong hạn' ?>
                                </span>
                            </div>
                        </div>

                        <div class="table-responsive mb-2">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Thiết bị nhận trả</th>
                                        <th class="text-end" style="width: 110px;">Tình trạng</th>
                                    </tr>
                                </thead>
                                <tbody id="pvDanhSachNhanTra">
                                    <!-- Cập nhật qua JS khi tick chọn -->
                                </tbody>
                            </table>
                        </div>

                        <div class="fw-semibold text-dark text-end" id="pvTongNhan">
                            Tổng nhận: 0 thiết bị
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-phu">
                            Chưa chọn phiếu mượn cần trả. Hãy quét QR hoặc tra cứu ở khung bên trên.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI: THÔNG TIN PHIẾU MƯỢN & THIẾT BỊ NHẬN TRẢ -->
        <div class="col-12 col-lg-7">
            <!-- Khối: Thông tin phiếu mượn -->
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="tieu-de-khoi mb-0">Thông tin phiếu mượn</h2>
                        <?php if ($phieuHienTai): ?>
                            <span class="font-monospace fw-semibold text-dark"><?= e($phieuHienTai['ma_phieu_muon']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($phieuHienTai): ?>
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-sm-4">
                                <label class="form-label">Mã số</label>
                                <input type="text" class="form-control" value="<?= e($phieuHienTai['ma_so_nguoi_muon']) ?>" readonly>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="form-label">Họ tên</label>
                                <input type="text" class="form-control" value="<?= e($phieuHienTai['ho_ten_nguoi_muon']) ?>" readonly>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="form-label">Số điện thoại</label>
                                <input type="text" class="form-control" value="<?= e($phieuHienTai['so_dien_thoai'] ?? '---') ?>" readonly>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-12 col-sm-4">
                                <label class="form-label">Lớp / đơn vị</label>
                                <input type="text" class="form-control" value="<?= e($phieuHienTai['lop_don_vi'] ?? '---') ?>" readonly>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="form-label">Phòng đã mượn</label>
                                <input type="text" class="form-control" value="<?= e($phieuHienTai['ma_phong']) ?>" readonly>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="form-label">Hạn trả</label>
                                <input type="text" class="form-control <?= $quaHan ? 'is-invalid text-danger' : '' ?>"
                                    value="<?= date('d/m/Y - H:i', strtotime($phieuHienTai['han_tra'])) ?>" readonly>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-phu">
                            Vui lòng quét tem thiết bị hoặc nhập mã phiếu để nạp thông tin.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Khối: Thiết bị nhận trả - đã quét -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="tieu-de-khoi mb-0">Thiết bị nhận trả - đã quét (<span id="demNhanTra"><?= $soChuaTra ?></span>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Nhận</th>
                                <th>Mã / tên thiết bị</th>
                                <th style="width: 170px;">Tình trạng khi nhận</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dsChiTiet)): ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-phu">
                                        Chưa có thiết bị nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dsChiTiet as $it):
                                    $daTra = !empty($it['thoi_gian_tra']);
                                ?>
                                    <tr class="<?= $daTra ? 'opacity-50 bg-light' : '' ?>">
                                        <td>
                                            <?php if (!$daTra): ?>
                                                <input type="checkbox" class="form-check-input check-nhan-tra"
                                                    name="chi_tiet_id[]" value="<?= (int)$it['id'] ?>"
                                                    data-ma="<?= e($it['ma_tai_san']) ?>"
                                                    data-ten="<?= e($it['ten_tai_san']) ?>"
                                                    onchange="capNhatPreviewTra()" checked>
                                            <?php else: ?>
                                                <span class="text-success fw-bold">✓</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($it['ma_tai_san']) ?> - <?= e($it['ten_tai_san']) ?></div>
                                            <div class="mo-ta-phu"><?= e($phieuHienTai['ma_phong']) ?></div>
                                        </td>
                                        <td>
                                            <?php if (!$daTra): ?>
                                                <select name="tinh_trang[<?= (int)$it['id'] ?>]" class="form-select form-select-sm sel-tinh-trang"
                                                    data-id="<?= (int)$it['id'] ?>" onchange="capNhatPreviewTra()">
                                                    <option value="TOT" selected>Bình thường</option>
                                                    <option value="HU_HONG">Hư hỏng</option>
                                                </select>
                                            <?php else: ?>
                                                <span class="text-phu small">Đã trả (<?= dinh_dang_ngay_gio($it['thoi_gian_tra']) ?>)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top bg-white">
                    <label class="form-label">Ghi chú hư hỏng / thiếu phụ kiện (nếu có)</label>
                    <input type="text" class="form-control" name="ghi_chu_chung" id="ghi_chu_chung"
                        placeholder="Nhập ghi chú" autocomplete="off">
                </div>
            </div>

            <!-- Nút hành động chân trang -->
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-phu">Hủy</a>
                <button type="submit" class="btn btn-chinh" id="btnXacNhanTra" <?= (!$phieuHienTai || $soChuaTra === 0) ? 'disabled' : '' ?>>
                    Xác nhận trả & lưu phiếu
                </button>
            </div>
        </div>
    </div>
</form>

<script src="<?= url('assets/vendor/jsqr/jsQR.js') ?>"></script>
<script>
    function thucHienTraCuu() {
        const val = document.getElementById('ma_tra_cuu_nhap').value.trim();
        if (!val) return;
        window.location.href = '<?= url("muon-tra/tra.php") ?>?tu_khoa=' + encodeURIComponent(val);
    }

    document.getElementById('ma_tra_cuu_nhap')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            thucHienTraCuu();
        }
    });

    function capNhatPreviewTra() {
        const checkedItems = document.querySelectorAll('.check-nhan-tra:checked');
        const dem = checkedItems.length;
        document.getElementById('demNhanTra').textContent = dem;

        const pvTbody = document.getElementById('pvDanhSachNhanTra');
        if (!pvTbody) return;

        if (dem === 0) {
            pvTbody.innerHTML = '<tr><td colspan="2" class="text-center text-phu py-2">Chưa chọn thiết bị nào</td></tr>';
            document.getElementById('pvTongNhan').textContent = 'Tổng nhận: 0 thiết bị';
            document.getElementById('btnXacNhanTra').disabled = true;
            return;
        }

        pvTbody.innerHTML = '';
        checkedItems.forEach(cb => {
            const id = cb.value;
            const ma = cb.getAttribute('data-ma');
            const ten = cb.getAttribute('data-ten');
            const sel = document.querySelector(`.sel-tinh-trang[data-id="${id}"]`);
            const ttText = (sel && sel.value === 'HU_HONG') ? 'Hỏng' : 'Bình thường';

            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${escapeHtml(ma)} - ${escapeHtml(ten)}</td><td class="text-end text-phu">${ttText}</td>`;
            pvTbody.appendChild(tr);
        });

        const tongChuaTra = <?= (int)$soChuaTra ?>;
        const trangThaiDu = (dem === tongChuaTra && tongChuaTra > 0) ? ' • Đã trả đủ' : '';
        document.getElementById('pvTongNhan').textContent = `Tổng nhận: ${dem} thiết bị${trangThaiDu}`;
        document.getElementById('btnXacNhanTra').disabled = false;
    }

    // Camera scanner
    let streamCamera = null;
    let dangQuet = false;
    const videoEl = document.getElementById('videoQuetCamera');
    const canvasEl = document.getElementById('canvasQuetCamera');
    const overlayEl = document.getElementById('overlayCamera');
    const btnCamera = document.getElementById('btnBatTatCamera');
    const selCamera = document.getElementById('chonCamera');

    async function khoiTaoCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;
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
    }
    khoiTaoCamera();

    async function batTatCamera() {
        if (dangQuet) {
            dungCamera();
        } else {
            await batCamera();
        }
    }

    async function batCamera() {
        const devId = selCamera.value;
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
            videoEl.style.display = 'block';
            overlayEl.style.display = 'none';
            await videoEl.play();
            dangQuet = true;
            btnCamera.textContent = 'Tắt camera';
            btnCamera.classList.replace('btn-phu', 'btn-danger');
            requestAnimationFrame(quetVongLap);
        } catch (e) {
            alert('Không thể mở camera. Vui lòng cấp quyền hoặc nhập mã thủ công.');
        }
    }

    function dungCamera() {
        if (streamCamera) {
            streamCamera.getTracks().forEach(t => t.stop());
            streamCamera = null;
        }
        dangQuet = false;
        videoEl.style.display = 'none';
        overlayEl.style.display = 'flex';
        btnCamera.textContent = 'Bật camera';
        btnCamera.classList.replace('btn-danger', 'btn-phu');
    }

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
                dungCamera();
                window.location.href = '<?= url("muon-tra/tra.php") ?>?tu_khoa=' + encodeURIComponent(code.data);
                return;
            }
        }
        requestAnimationFrame(quetVongLap);
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(m) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            } [m];
        });
    }

    // Khởi chạy preview ban đầu
    capNhatPreviewTra();
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>