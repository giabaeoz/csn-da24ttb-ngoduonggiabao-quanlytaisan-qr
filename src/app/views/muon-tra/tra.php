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

// Thiết bị vừa được quét để trả
$thietBiVuaQuet = strtoupper(trim((string)($_GET['tu_khoa'] ?? $_GET['ma_qr'] ?? '')));
$coThietBiKhop = false;
if ($thietBiVuaQuet !== '') {
    foreach ($dsChiTiet as $it) {
        if (empty($it['thoi_gian_tra'])) {
            $m1 = strtoupper(trim((string)($it['ma_tai_san'] ?? '')));
            $m2 = strtoupper(trim((string)($it['ma_qr'] ?? '')));
            if ($m1 === $thietBiVuaQuet || $m2 === $thietBiVuaQuet) {
                $coThietBiKhop = true;
                break;
            }
        }
    }
}
?>

<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang">Mượn - trả tài sản</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('muon-tra/muon.php') ?>" class="btn btn-phu bg-white">
            <?= icon('repeat', 'bieu-tuong-sm') ?>
            <span>Mượn</span>
        </a>
        <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-chinh">
            <?= icon('corner-down-left', 'bieu-tuong-sm') ?>
            <span>Trả</span>
        </a>
    </div>
</div>

<?php if ($dangBiKhoaKiemKe): ?>
    <div class="alert alert-danger mb-4" role="alert">
        <?= icon('alert-circle', 'bieu-tuong-md') ?>
        <div class="flex-grow-1">Bàn trực hiện đang diễn ra đợt kiểm kê tài sản. Hoạt động nhận trả tạm thời bị khóa.</div>
    </div>
<?php endif; ?>

<?php if ($thongBaoLoi !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <?= icon('alert-circle', 'bieu-tuong-md') ?>
        <div class="flex-grow-1"><?= e($thongBaoLoi) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
<?php endif; ?>

<form action="<?= url('muon-tra/tra.php') ?>" method="POST" id="formNhanTra" class="luong-quet-qr <?= $dangBiKhoaKiemKe ? 'opacity-50 pe-none' : '' ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="phieu_muon_id" value="<?= (int)($phieuHienTai['id'] ?? 0) ?>">

    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="card mb-4">
                <div class="card-body p-3">
                    <h2 class="tieu-de-khoi mb-3 d-flex align-items-center gap-2">
                        <span class="text-primary"><?= icon('qr-code', 'bieu-tuong-md') ?></span>
                        <span>Quét QR thiết bị trả</span>
                    </h2>
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-sm-6">
                            <div class="khung-quet-camera" id="khungVideoContainer">
                                <video id="videoQuetCamera" playsinline class="d-none"></video>
                                <canvas id="canvasQuetCamera" class="d-none"></canvas>
                                <div class="overlay-ngam" id="overlayCamera">
                                    <div class="text-primary mb-1"><?= icon('scan-line', 'bieu-tuong-lg') ?></div>
                                    <div class="fw-semibold text-dark mb-1">Khung quét QR</div>
                                    <div class="chu-nho-11">Đưa mã QR thiết bị vào khung</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 d-flex flex-column gap-2">
                            <select id="chonCamera" class="form-select form-select-sm">
                                <option value="">Chọn camera</option>
                            </select>

                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="ma_tra_cuu_nhap"
                                    placeholder="Nhập mã thiết bị (VD: OCD-02, CAP-01)..."
                                    value="<?= e($thietBiVuaQuet) ?>" autocomplete="off">
                                <button type="button" class="btn btn-phu" onclick="thucHienTraCuu()">
                                    <?= icon('search', 'bieu-tuong-sm') ?>
                                    <span>Tìm</span>
                                </button>
                            </div>

                            <button type="button" class="btn btn-phu btn-sm w-100" id="btnBatTatCamera" onclick="batTatCamera()">
                                <?= icon('scan-line', 'bieu-tuong-sm') ?>
                                <span id="nhanNutCamera">Bật camera</span>
                            </button>
                        </div>
                    </div>
                    <div id="thongBaoQuetKetQua" class="ket-qua-quet mt-2 <?= $thongBaoLoiTimKiem !== '' ? 'ket-qua-quet-loi' : 'd-none' ?>">
                        <?= icon('alert-circle', 'bieu-tuong-sm') ?>
                        <span id="noiDungThongBaoQuet"><?= e($thongBaoLoiTimKiem) ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <h2 class="tieu-de-khoi mb-3 d-flex align-items-center gap-2">
                        <span class="text-primary"><?= icon('file-plus', 'bieu-tuong-md') ?></span>
                        <span>Phiếu trả tài sản</span>
                    </h2>
                    <?php if ($phieuHienTai): ?>
                        <div class="mb-3">
                            <div class="fw-semibold text-dark">
                                <?= e($phieuHienTai['ho_ten_nguoi_muon'] ?? $phieuHienTai['ten_nguoi_muon'] ?? $phieuHienTai['ho_ten'] ?? '') ?> - <?= e($phieuHienTai['ma_so_nguoi_muon'] ?? $phieuHienTai['ma_so_truong'] ?? '') ?> - <?= e($phieuHienTai['lop_don_vi'] ?? '---') ?>
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
                                        <th class="text-end cot-110">Tình trạng</th>
                                    </tr>
                                </thead>
                                <tbody id="pvDanhSachNhanTra">
                                </tbody>
                            </table>
                        </div>

                        <div class="fw-semibold text-dark text-end" id="pvTongNhan">
                            Tổng nhận: 0 thiết bị
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3 text-phu">
                            <?= minh_hoa_hop_trong('Chưa quét thiết bị cần trả. Hãy đưa mã QR trên thiết bị vào khung camera hoặc nhập mã bên trên.') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="tieu-de-khoi mb-0 d-flex align-items-center gap-2">
                            <span class="text-primary"><?= icon('info', 'bieu-tuong-md') ?></span>
                            <span>Thông tin phiếu mượn</span>
                        </h2>
                        <?php if ($phieuHienTai): ?>
                            <span class="font-monospace fw-semibold text-dark"><?= e($phieuHienTai['ma_phieu_muon']) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($phieuHienTai): ?>
                        <div class="row g-2 mb-2">
                            <div class="col-12 col-sm-4">
                                <label class="form-label">Mã số</label>
                                <input type="text" class="form-control" value="<?= e($phieuHienTai['ma_so_nguoi_muon'] ?? $phieuHienTai['ma_so_truong'] ?? '') ?>" readonly>
                            </div>
                            <div class="col-12 col-sm-4">
                                <label class="form-label">Họ tên</label>
                                <input type="text" class="form-control" value="<?= e($phieuHienTai['ho_ten_nguoi_muon'] ?? $phieuHienTai['ten_nguoi_muon'] ?? $phieuHienTai['ho_ten'] ?? '') ?>" readonly>
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
                        <div class="p-3 rounded-3 d-flex align-items-center justify-content-center gap-2 text-phu" style="background: #EFF8FF; border: 1px solid #BFDBFE;">
                            <span class="text-primary"><?= icon('qr-code', 'bieu-tuong-md') ?></span>
                            <span>Vui lòng quét mã QR trên thiết bị để nạp thông tin phiếu mượn.</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h2 class="tieu-de-khoi mb-0 d-flex align-items-center gap-2">
                        <span class="text-primary"><?= icon('list', 'bieu-tuong-md') ?></span>
                        <span>Danh sách thiết bị trong phiếu (<span id="demNhanTra">0</span>/<?= $soChuaTra ?> nhận trả)</span>
                    </h2>
                    <?php if (!empty($dsChiTiet) && $soChuaTra > 0): ?>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-phu btn-sm" onclick="chonTatCaThietBi(true)">
                                <span>Trả tất cả</span>
                            </button>
                            <?php if ($coThietBiKhop): ?>
                                <button type="button" class="btn btn-phu btn-sm" onclick="chonChiThietBiQuet()">
                                    <span>Chỉ trả thiết bị vừa quét</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="cot-50 text-center">Trả</th>
                                <th>Mã / tên thiết bị</th>
                                <th class="cot-180">Trạng thái trong phiếu</th>
                                <th class="cot-160">Tình trạng khi nhận</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($dsChiTiet)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-phu">
                                        <?= minh_hoa_hop_trong('Chưa có thiết bị nào.') ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($dsChiTiet as $it):
                                    $daTra = !empty($it['thoi_gian_tra']);
                                    $m1 = strtoupper(trim((string)($it['ma_tai_san'] ?? '')));
                                    $m2 = strtoupper(trim((string)($it['ma_qr'] ?? '')));
                                    $laThietBiVuaQuet = ($thietBiVuaQuet !== '' && ($m1 === $thietBiVuaQuet || $m2 === $thietBiVuaQuet));
                                    // Mặc định chọn: nếu có thiết bị vừa quét cụ thể thì chỉ tick thiết bị đó; nếu tra cứu chung thì tick tất cả chưa trả
                                    $macDinhChon = !$daTra && ($coThietBiKhop ? $laThietBiVuaQuet : true);
                                ?>
                                    <tr class="<?= $daTra ? 'opacity-50 bg-light' : ($laThietBiVuaQuet ? 'table-active' : '') ?>" id="hang_tb_<?= (int)$it['id'] ?>">
                                        <td class="text-center">
                                            <?php if (!$daTra): ?>
                                                <input type="checkbox" class="form-check-input check-nhan-tra"
                                                    name="chi_tiet_id[]" value="<?= (int)$it['id'] ?>"
                                                    data-ma="<?= e($it['ma_tai_san']) ?>"
                                                    data-qr="<?= e($it['ma_qr']) ?>"
                                                    data-ten="<?= e($it['ten_tai_san']) ?>"
                                                    onchange="capNhatPreviewTra()" <?= $macDinhChon ? 'checked' : '' ?>>
                                            <?php else: ?>
                                                <span class="text-success fw-bold">✓</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($it['ma_tai_san']) ?> - <?= e($it['ten_tai_san']) ?></div>
                                            <div class="mo-ta-phu">Phòng: <?= e($phieuHienTai['ma_phong']) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($daTra): ?>
                                                <span class="huy-hieu huy-hieu-thanh-cong">Đã trả (<?= dinh_dang_ngay_gio($it['thoi_gian_tra']) ?>)</span>
                                            <?php elseif ($laThietBiVuaQuet): ?>
                                                <span class="huy-hieu huy-hieu-chinh badge-trang-thai-tb">Thiết bị vừa quét</span>
                                            <?php else: ?>
                                                <span class="huy-hieu huy-hieu-xam badge-trang-thai-tb">Còn lại trong phiếu</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$daTra): ?>
                                                <select name="tinh_trang[<?= (int)$it['id'] ?>]" class="form-select form-select-sm sel-tinh-trang"
                                                    data-id="<?= (int)$it['id'] ?>" onchange="capNhatPreviewTra()">
                                                    <option value="TOT" selected>Bình thường</option>
                                                    <option value="HU_HONG">Hư hỏng</option>
                                                </select>
                                            <?php else: ?>
                                                <span class="text-phu small">Hoàn tất</span>
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

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-phu">
                    <?= icon('x', 'bieu-tuong-sm') ?>
                    <span>Hủy</span>
                </a>
                <button type="submit" class="btn btn-chinh" id="btnXacNhanTra" <?= (!$phieuHienTai || $soChuaTra === 0) ? 'disabled' : '' ?>>
                    <?= icon('save', 'bieu-tuong-sm') ?>
                    <span>Xác nhận trả & lưu phiếu</span>
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
        xuLyQuetThietBi(val);
    }

    document.getElementById('ma_tra_cuu_nhap')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            thucHienTraCuu();
        }
    });

    function chonTatCaThietBi(check) {
        document.querySelectorAll('.check-nhan-tra').forEach(cb => {
            cb.checked = check;
        });
        capNhatPreviewTra();
    }

    function chonChiThietBiQuet() {
        const maQuet = <?= json_encode($thietBiVuaQuet) ?>;
        document.querySelectorAll('.check-nhan-tra').forEach(cb => {
            const ma = (cb.getAttribute('data-ma') || '').toUpperCase();
            const qr = (cb.getAttribute('data-qr') || '').toUpperCase();
            cb.checked = (maQuet !== '' && (ma === maQuet || qr === maQuet));
        });
        capNhatPreviewTra();
    }

    function hienThongBaoQuet(msg, loai = 'thanh-cong') {
        const box = document.getElementById('thongBaoQuetKetQua');
        const text = document.getElementById('noiDungThongBaoQuet');
        if (!box || !text) return;
        text.textContent = msg;
        box.className = 'ket-qua-quet mt-2 ' + (loai === 'thanh-cong' ? 'ket-qua-quet-dat' : 'ket-qua-quet-loi');
        box.classList.remove('d-none');
    }

    function xuLyQuetThietBi(codeStr) {
        if (!codeStr) return;
        const ma = codeStr.trim().toUpperCase();

        // 1. Kiểm tra xem thiết bị này có nằm trong số các thiết bị chưa trả của phiếu đang mở không
        const cb = document.querySelector(`.check-nhan-tra[data-ma="${ma}"], .check-nhan-tra[data-qr="${ma}"]`);
        if (cb) {
            cb.checked = true;
            const tr = cb.closest('tr');
            if (tr) {
                tr.classList.add('table-active');
                const badge = tr.querySelector('.badge-trang-thai-tb');
                if (badge) {
                    badge.className = 'huy-hieu huy-hieu-chinh badge-trang-thai-tb';
                    badge.textContent = 'Thiết bị vừa quét';
                }
            }
            capNhatPreviewTra();
            hienThongBaoQuet(`Đã quét nhận thiết bị [${ma}].`, 'thanh-cong');
            return;
        }

        // 2. Nếu chưa mở phiếu hoặc thiết bị thuộc phiếu khác -> Chuyển hướng để nạp phiếu của thiết bị đó
        window.location.href = '<?= url("muon-tra/tra.php") ?>?tu_khoa=' + encodeURIComponent(ma);
    }

    function capNhatPreviewTra() {
        const checkedItems = document.querySelectorAll('.check-nhan-tra:checked');
        const dem = checkedItems.length;
        const tongChuaTra = <?= (int)$soChuaTra ?>;
        
        const demEl = document.getElementById('demNhanTra');
        if (demEl) demEl.textContent = dem;

        const pvTbody = document.getElementById('pvDanhSachNhanTra');
        if (!pvTbody) return;

        if (dem === 0) {
            pvTbody.innerHTML = '<tr><td colspan="2" class="text-center text-phu py-2">Chưa chọn thiết bị nào để nhận trả</td></tr>';
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
            const ttText = (sel && sel.value === 'HU_HONG') ? 'Hư hỏng' : 'Bình thường';

            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${escapeHtml(ma)} - ${escapeHtml(ten)}</td><td class="text-end text-phu">${ttText}</td>`;
            pvTbody.appendChild(tr);
        });

        const conLai = tongChuaTra - dem;
        let thongTinDem = `Tổng nhận: ${dem} thiết bị`;
        if (dem === tongChuaTra && tongChuaTra > 0) {
            thongTinDem += ' • Đã trả đủ toàn bộ phiếu';
        } else if (conLai > 0) {
            thongTinDem += ` (Còn lại ${conLai} thiết bị chưa trả)`;
        }
        document.getElementById('pvTongNhan').textContent = thongTinDem;
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
            videoEl.classList.remove('d-none');
            overlayEl.classList.add('d-none');
            await videoEl.play();
            dangQuet = true;
            const nhanNut = document.getElementById('nhanNutCamera');
            if (nhanNut) nhanNut.textContent = 'Tắt camera';
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
        videoEl.classList.add('d-none');
        overlayEl.classList.remove('d-none');
        const nhanNut = document.getElementById('nhanNutCamera');
        if (nhanNut) nhanNut.textContent = 'Bật camera';
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
                xuLyQuetThietBi(code.data);
                setTimeout(() => {
                    if (dangQuet) requestAnimationFrame(quetVongLap);
                }, 1200);
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