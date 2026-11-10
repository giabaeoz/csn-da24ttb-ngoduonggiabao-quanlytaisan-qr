<?php
/**
 * Giao diện Lập phiếu Điều chuyển tài sản - Khớp chính xác Tra điều chuyển.pdf
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var bool $dangKiemKe
 * @var list<array<string, mixed>> $danhSachPhongHoc
 * @var array<string, mixed> $duLieu
 * @var string $loi
 * @var list<array<string, mixed>> $dieuChuyenGanDay
 */

$tieuDeTrang = 'Điều chuyển';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<!-- Tiêu đề trang -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang">Điều chuyển tài sản</h1>
    </div>
</div>

<?php if ($dangKiemKe): ?>
<div class="alert alert-danger mb-4" role="alert">
    Bàn trực hiện đang có đợt kiểm kê tài sản đang diễn ra. Hoạt động điều chuyển tạm thời bị khóa.
</div>
<?php endif; ?>

<?php if (!empty($loi)): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <?= e($loi) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
</div>
<?php endif; ?>

<form action="<?= url('dieu-chuyen/them.php') ?>" method="POST" id="formDieuChuyen" class="<?= $dangKiemKe ? 'opacity-50 pe-none' : '' ?>">
    <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
    <div id="vungTaiSanIds"></div>

    <div class="row g-4 mb-4">
        <!-- CỘT TRÁI: QUÉT QR & XEM TRƯỚC PHIẾU ĐIỀU CHUYỂN -->
        <div class="col-12 col-lg-5">
            <!-- Khối: Quét QR thiết bị điều chuyển -->
            <div class="card mb-4">
                <div class="card-body p-3">
                    <h2 class="tieu-de-khoi mb-3">Quét QR thiết bị điều chuyển</h2>
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
                                <input type="text" class="form-control" id="ma_quet_nhap" 
                                       placeholder="Nhập mã khi không quét được" autocomplete="off">
                                <button type="button" class="btn btn-phu" onclick="xuLyThemMaThietBi()">
                                    Thêm
                                </button>
                            </div>

                            <button type="button" class="btn btn-phu btn-sm w-100" id="btnBatTatCamera" onclick="batTatCamera()">
                                Bật camera
                            </button>
                        </div>
                    </div>
                    <div id="thongBaoQuetLoi" class="loi-truong mt-2 d-none"></div>
                </div>
            </div>

            <!-- Khối: Phiếu điều chuyển (Xem trước) -->
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="tieu-de-khoi mb-3">Phiếu điều chuyển</h2>
                    <div class="mb-3">
                        <div class="fw-semibold text-dark" id="pvLoTrinh">Phòng đi: --- → Phòng đến: ---</div>
                        <div class="mo-ta-phu" id="pvLyDo">Lý do: ---</div>
                        <div class="mo-ta-phu" id="pvThoiGian">Thời gian: <?= date('d/m/Y H:i') ?> (minh họa)</div>
                    </div>

                    <div class="table-responsive mb-2">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Thiết bị</th>
                                    <th class="text-end" style="width: 80px;">Số lượng</th>
                                </tr>
                            </thead>
                            <tbody id="pvDanhSachThietBi">
                                <tr>
                                    <td colspan="2" class="text-center text-phu py-3">Chưa có thiết bị nào</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="fw-semibold text-dark text-end" id="pvTongCongThietBi">
                        Tổng cộng: 0 thiết bị
                    </div>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI: THÔNG TIN ĐIỀU CHUYỂN & THIẾT BỊ ĐÃ QUÉT -->
        <div class="col-12 col-lg-7">
            <!-- Khối: Thông tin điều chuyển -->
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h2 class="tieu-de-khoi mb-3">Thông tin điều chuyển</h2>
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-sm-6">
                            <label class="form-label">Phòng đi</label>
                            <select id="phong_hoc_di_id" name="phong_hoc_di_id" class="form-select" onchange="doiPhongDi(this)">
                                <option value="">Chọn phòng đi</option>
                                <?php foreach ($danhSachPhongHoc as $ph): ?>
                                <option value="<?= (int)$ph['id'] ?>" data-ma="<?= e($ph['ma_phong']) ?>" <?= ((int)$duLieu['phong_hoc_di_id'] === (int)$ph['id']) ? 'selected' : '' ?>>
                                    <?= e($ph['ma_phong']) ?> (<?= e($ph['ten_phong']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6">
                            <label class="form-label">Phòng đến</label>
                            <select id="phong_hoc_den_id" name="phong_hoc_den_id" class="form-select" onchange="doiPhongDen(this)">
                                <option value="">Chọn phòng đến</option>
                                <?php foreach ($danhSachPhongHoc as $ph): ?>
                                <option value="<?= (int)$ph['id'] ?>" data-ma="<?= e($ph['ma_phong']) ?>" <?= ((int)$duLieu['phong_hoc_den_id'] === (int)$ph['id']) ? 'selected' : '' ?>>
                                    <?= e($ph['ma_phong']) ?> (<?= e($ph['ten_phong']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Lý do điều chuyển</label>
                        <textarea class="form-control" name="ly_do" id="inputLyDo" rows="2" 
                                  placeholder="Nhập lý do điều chuyển thiết bị..." oninput="capNhatPreview()"><?= e($duLieu['ly_do']) ?></textarea>
                    </div>
                    <div class="mo-ta-phu">Thời gian: tự ghi nhận khi xác nhận</div>
                </div>
            </div>

            <!-- Khối: Thiết bị điều chuyển — đã quét -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="tieu-de-khoi mb-0">Thiết bị điều chuyển — đã quét (<span id="demThietBi">0</span>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mã / tên thiết bị</th>
                                <th style="width: 140px;">Tình trạng</th>
                                <th class="text-end" style="width: 80px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="bangThietBiDieuChuyen">
                            <tr id="hangChuaCoThietBi">
                                <td colspan="3" class="text-center py-4 text-phu">
                                    Chưa có thiết bị nào được quét.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-top bg-white">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="xac_nhan_tuong_thich" id="xac_nhan_tuong_thich" value="1" onchange="capNhatPreview()">
                        <label class="form-check-label text-dark fw-semibold" for="xac_nhan_tuong_thich">
                            Đã kiểm tra thiết bị phù hợp với phòng đến
                        </label>
                    </div>
                </div>
            </div>

            <!-- Nút hành động chân trang -->
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('dieu-chuyen/index.php') ?>" class="btn btn-phu">Hủy</a>
                <button type="submit" class="btn btn-chinh" id="btnXacNhanDieuChuyen" disabled>
                    Xác nhận điều chuyển
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Bảng: Điều chuyển gần đây -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="tieu-de-khoi mb-0">Điều chuyển gần đây</h2>
        </div>
        <a href="<?= url('dieu-chuyen/index.php') ?>" class="btn btn-phu btn-sm">
            Xem tất cả
        </a>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Thời gian</th>
                    <th>Phòng đi</th>
                    <th>Phòng đến</th>
                    <th>Thiết bị</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dieuChuyenGanDay)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-phu">Chưa có phiếu điều chuyển nào gần đây.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($dieuChuyenGanDay as $dc): ?>
                <tr>
                    <td class="fw-semibold text-primary"><?= e($dc['ma_dieu_chuyen']) ?></td>
                    <td><?= dinh_dang_ngay_gio($dc['thoi_gian_tao']) ?></td>
                    <td><?= e($dc['ma_phong_di']) ?></td>
                    <td><?= e($dc['ma_phong_den']) ?></td>
                    <td><?= (int)$dc['tong_so_thiet_bi'] ?></td>
                    <td class="text-end">
                        <a href="<?= url('dieu-chuyen/chi-tiet.php?id=' . (int)$dc['id']) ?>" class="btn btn-phu btn-sm">
                            Xem phiếu
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="<?= url('assets/vendor/jsqr/jsQR.js') ?>"></script>
<script>
const danhSachThietBi = [];

function doiPhongDi(sel) {
    capNhatPreview();
}

function doiPhongDen(sel) {
    capNhatPreview();
}

// Tìm & thêm thiết bị
const inputMaQuet = document.getElementById('ma_quet_nhap');
const thongBaoLoiQuet = document.getElementById('thongBaoQuetLoi');

if (inputMaQuet) {
    inputMaQuet.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); xuLyThemMaThietBi(); }
    });
}

async function xuLyThemMaThietBi() {
    const ma = inputMaQuet.value.trim();
    if (!ma) return;
    await traCuuVaThem(ma);
}

async function traCuuVaThem(ma) {
    thongBaoLoiQuet.classList.add('d-none');
    try {
        const res = await fetch('<?= url("api/tra-cuu-qr.php") ?>?ma=' + encodeURIComponent(ma) + '&hanh_dong=DIEU_CHUYEN');
        const json = await res.json();

        if (!json.thanh_cong) {
            hienLoiQuet(json.thong_diep || 'Không tìm thấy thiết bị hợp lệ.');
            return;
        }

        const ts = json.du_lieu;
        if (danhSachThietBi.some(item => item.id === ts.id)) {
            hienLoiQuet(`Thiết bị [${ts.ma_tai_san}] đã có trong danh sách.`);
            return;
        }

        danhSachThietBi.push(ts);
        inputMaQuet.value = '';
        capNhatBangThietBi();
        capNhatPreview();
    } catch(e) {
        hienLoiQuet('Lỗi kết nối máy chủ.');
    }
}

function hienLoiQuet(msg) {
    thongBaoLoiQuet.textContent = msg;
    thongBaoLoiQuet.classList.remove('d-none');
}

function boThietBi(idx) {
    danhSachThietBi.splice(idx, 1);
    capNhatBangThietBi();
    capNhatPreview();
}

function capNhatBangThietBi() {
    const tbody = document.getElementById('bangThietBiDieuChuyen');
    const demEl = document.getElementById('demThietBi');
    const hiddenContainer = document.getElementById('vungTaiSanIds');
    demEl.textContent = danhSachThietBi.length;

    hiddenContainer.innerHTML = '';
    if (danhSachThietBi.length === 0) {
        tbody.innerHTML = '<tr id="hangChuaCoThietBi"><td colspan="3" class="text-center py-4 text-phu">Chưa có thiết bị nào được quét.</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    danhSachThietBi.forEach((ts, idx) => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'tai_san_ids[]';
        inp.value = ts.id;
        hiddenContainer.appendChild(inp);

        const phongHt = ts.phong_hoc_hien_tai_ma ? `Phòng hiện tại: ${ts.phong_hoc_hien_tai_ma}` : 'Phòng trực';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="fw-semibold text-dark">${escapeHtml(ts.ma_tai_san)} — ${escapeHtml(ts.ten_tai_san)}</div>
                <div class="mo-ta-phu">${escapeHtml(phongHt)}</div>
            </td>
            <td>Bình thường</td>
            <td class="text-end">
                <button type="button" class="btn btn-phu btn-sm py-1 px-2" onclick="boThietBi(${idx})">
                    Bỏ
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function capNhatPreview() {
    const selDi = document.getElementById('phong_hoc_di_id');
    const selDen = document.getElementById('phong_hoc_den_id');
    const maDi = selDi.options[selDi.selectedIndex]?.getAttribute('data-ma') || '---';
    const maDen = selDen.options[selDen.selectedIndex]?.getAttribute('data-ma') || '---';
    const lyDo = document.getElementById('inputLyDo').value.trim() || '---';
    const daCamKet = document.getElementById('xac_nhan_tuong_thich').checked;

    document.getElementById('pvLoTrinh').textContent = `Phòng đi: ${maDi} → Phòng đến: ${maDen}`;
    document.getElementById('pvLyDo').textContent = `Lý do: ${lyDo}`;

    const pvTbody = document.getElementById('pvDanhSachThietBi');
    if (danhSachThietBi.length === 0) {
        pvTbody.innerHTML = '<tr><td colspan="2" class="text-center text-phu py-3">Chưa có thiết bị nào</td></tr>';
    } else {
        pvTbody.innerHTML = '';
        danhSachThietBi.forEach(ts => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${escapeHtml(ts.ma_tai_san)} — ${escapeHtml(ts.ten_tai_san)}</td><td class="text-end">1</td>`;
            pvTbody.appendChild(tr);
        });
    }
    document.getElementById('pvTongCongThietBi').textContent = `Tổng cộng: ${danhSachThietBi.length} thiết bị`;

    const btnSubmit = document.getElementById('btnXacNhanDieuChuyen');
    const hopLe = (maDi !== '---') && (maDen !== '---') && (maDi !== maDen) && (lyDo !== '---') && daCamKet && (danhSachThietBi.length > 0);
    btnSubmit.disabled = !hopLe;
}

// Camera Scanner
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
    } catch(e) {}
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
        video: devId ? { deviceId: { exact: devId } } : { facingMode: 'environment' }
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
    } catch(e) {
        hienLoiQuet('Không thể mở camera. Vui lòng cấp quyền hoặc nhập mã thủ công.');
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
        const code = jsQR(imgData.data, imgData.width, imgData.height, { inversionAttempts: 'dontInvert' });

        if (code && code.data) {
            traCuuVaThem(code.data);
            setTimeout(() => { if (dangQuet) requestAnimationFrame(quetVongLap); }, 1200);
            return;
        }
    }
    requestAnimationFrame(quetVongLap);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}

capNhatPreview();
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
