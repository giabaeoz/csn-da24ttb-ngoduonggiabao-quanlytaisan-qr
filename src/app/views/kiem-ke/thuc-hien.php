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
 * @var string $boLocChiTiet
 * @var list<array<string, mixed>> $dotGanDay
 */

$tieuDeTrang = 'Kiểm kê';
require dirname(__DIR__) . '/layout/dau-trang.php';

$thongKe = $dot['thong_ke'];
$phaiKiemThucTe = max(0, (int)$thongKe['tong_so_sach'] - (int)$thongKe['so_dang_muon_khi_mo']);
$daKiem = (int)$thongKe['so_tim_thay'] + (int)$thongKe['so_chua_tim_thay'];
$chuaKiem = (int)$thongKe['so_con_lai_can_kiem'];
$dangMuon = (int)$thongKe['so_dang_muon_khi_mo'];
?>

<!-- Tiêu đề trang & Nút Tạo đợt kiểm kê -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang">Kiểm kê tài sản</h1>
    </div>
    <div>
        <a href="<?= url('kiem-ke/tao.php') ?>" class="btn btn-phu">
            + Tạo đợt kiểm kê
        </a>
    </div>
</div>

<?php if (!empty($thongBaoLoi)): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <?= e($thongBaoLoi) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <!-- CỘT TRÁI: QUÉT QR ĐỐI CHIẾU -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body p-3">
                <h2 class="tieu-de-khoi mb-3">Quét QR để đối chiếu thiết bị</h2>
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
                                Tìm
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
    </div>

    <!-- CỘT PHẢI: THÔNG TIN ĐỢT KIỂM KÊ -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="tieu-de-khoi mb-0">Thông tin đợt kiểm kê</h2>
                    <span class="huy-hieu huy-hieu-chinh">Đang thực hiện</span>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label">Mã đợt</label>
                        <input type="text" class="form-control" value="<?= e($dot['ma_dot_kiem_ke']) ?>" readonly>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Loại kiểm kê</label>
                        <input type="text" class="form-control" value="<?= e(TEN_LOAI_KIEM_KE[$dot['loai_kiem_ke']] ?? $dot['loai_kiem_ke']) ?>" readonly>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label">Phạm vi tài sản</label>
                    <input type="text" class="form-control" value="Thiết bị thuộc phòng <?= e($dot['ten_phong_truc']) ?>" readonly>
                </div>

                <div class="mb-0">
                    <label class="form-label">Nơi kiểm tra thực tế</label>
                    <input type="text" class="form-control" value="Phòng trực thiết bị" readonly>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4 Thẻ số liệu đợt kiểm kê -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu"><?= sprintf('%02d', $phaiKiemThucTe) ?></div>
            <div class="nhan-so-lieu">Phải kiểm thực tế</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu"><?= sprintf('%02d', $daKiem) ?></div>
            <div class="nhan-so-lieu">Đã kiểm</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu text-warning"><?= sprintf('%02d', $chuaKiem) ?></div>
            <div class="nhan-so-lieu">Chưa kiểm</div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="so-lieu"><?= sprintf('%02d', $dangMuon) ?></div>
            <div class="nhan-so-lieu">Đang mượn theo phiếu</div>
        </div>
    </div>
</div>

<!-- Bộ lọc tìm thiết bị trong đợt -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="<?= url('kiem-ke/thuc-hien.php') ?>" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="id" value="<?= (int)$dot['id'] ?>">
            <div class="col-12 col-md-5">
                <label class="form-label">Tìm thiết bị</label>
                <input type="text" name="tu_khoa" class="form-control" placeholder="Mã / tên thiết bị" 
                       value="<?= e($boLocChiTiet['tu_khoa']) ?>">
            </div>
            <div class="col-12 col-md-5">
                <label class="form-label">Kết quả đối chiếu</label>
                <select name="trang_thai_loc" class="form-select">
                    <option value="">Tất cả</option>
                    <option value="TIM_THAY" <?= ($boLocChiTiet['trang_thai_loc'] === 'TIM_THAY') ? 'selected' : '' ?>>Có mặt</option>
                    <option value="DANG_MUON" <?= ($boLocChiTiet['trang_thai_loc'] === 'DANG_MUON') ? 'selected' : '' ?>>Đang mượn</option>
                    <option value="CHUA_KIEM" <?= ($boLocChiTiet['trang_thai_loc'] === 'CHUA_KIEM') ? 'selected' : '' ?>>Chưa kiểm</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <button type="submit" class="btn btn-phu w-100">Tìm</button>
            </div>
        </form>
    </div>
</div>

<!-- Bảng: Danh sách đối chiếu -->
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
        <table class="table">
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
                    $laDangMuon = ($ct['trang_thai_so_sach'] === 'DANG_MUON');
                    $daKiemDoi = ($ct['ket_qua_kiem_ke'] !== null);
                ?>
                <tr>
                    <td>
                        <div class="fw-semibold text-dark"><?= e($ct['ma_tai_san']) ?> — <?= e($ct['ten_tai_san']) ?></div>
                        <div class="mo-ta-phu">Phòng quản lý: <?= e($dot['ten_phong_truc']) ?></div>
                    </td>
                    <td>
                        <?php if ($laDangMuon): ?>
                            <span class="huy-hieu huy-hieu-chinh">Đang mượn</span>
                        <?php elseif ($ct['ket_qua_kiem_ke'] === 'TIM_THAY'): ?>
                            <span class="huy-hieu huy-hieu-thanh-cong">Có mặt</span>
                        <?php elseif ($ct['ket_qua_kiem_ke'] === 'CHUA_TIM_THAY'): ?>
                            <span class="huy-hieu huy-hieu-nguy-hiem">Chưa tìm thấy</span>
                        <?php else: ?>
                            <span class="huy-hieu huy-hieu-xam">Chưa kiểm</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($laDangMuon): ?>
                            <span class="text-phu">Theo phiếu mượn</span>
                        <?php elseif (!$daKiemDoi): ?>
                            <span class="text-phu">Chưa ghi nhận</span>
                        <?php elseif ($ct['tinh_trang_thuc_te'] === 'HU_HONG'): ?>
                            <span class="text-danger fw-semibold">Hỏng</span>
                        <?php else: ?>
                            <span>Bình thường</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($ct['ghi_chu'])): ?>
                            <?= e($ct['ghi_chu']) ?>
                        <?php elseif ($laDangMuon && !empty($ct['ma_phieu_muon_hien_tai'])): ?>
                            <span class="font-monospace text-primary"><?= e($ct['ma_phieu_muon_hien_tai']) ?></span>
                        <?php else: ?>
                            <span class="text-phu">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ($laDangMuon): ?>
                            <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)($ct['phieu_muon_id'] ?? 1)) ?>" class="btn btn-phu btn-sm">
                                Xem phiếu
                            </a>
                        <?php elseif ($daKiemDoi): ?>
                            <button type="button" class="btn btn-phu btn-sm text-success" onclick="moModalGhiNhan(<?= htmlspecialchars(json_encode($ct), ENT_QUOTES, 'UTF-8') ?>)">
                                Đã lưu
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-phu btn-sm" onclick="moModalGhiNhan(<?= htmlspecialchars(json_encode($ct), ENT_QUOTES, 'UTF-8') ?>)">
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

<!-- Khối: Kết quả tạm thời & Hành động hoàn tất -->
<div class="card mb-4">
    <div class="card-body p-4">
        <h2 class="tieu-de-khoi mb-2">Kết quả tạm thời</h2>
        <div class="mo-ta-phu mb-2">
            Bình thường: <?= (int)$thongKe['so_tim_thay_tot'] ?> • 
            Hỏng: <?= (int)$thongKe['so_tim_thay_hong'] ?> • 
            Không tìm thấy: <?= (int)$thongKe['so_chua_tim_thay'] ?> • 
            Đang mượn: <?= (int)$thongKe['so_dang_muon_khi_mo'] ?> • 
            Chưa kiểm: <?= (int)$thongKe['so_con_lai_can_kiem'] ?>
        </div>
        <div class="small text-dark mb-3">
            <?php if ($chuaKiem > 0): ?>
                Đối chiếu <?= $chuaKiem ?> thiết bị còn lại trước khi hoàn tất đợt kiểm kê.
            <?php else: ?>
                Đã đối chiếu đầy đủ mọi thiết bị trong phạm vi đợt kiểm kê.
            <?php endif; ?>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-phu">Lưu nháp</a>
            
            <form action="<?= url('kiem-ke/thuc-hien.php?id=' . (int)$dot['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn chốt sổ và hoàn tất đợt kiểm kê này? Các sự cố phát sinh (nếu có) sẽ được tự động ghi nhận.');">
                <?= csrf_field() ?>
                <input type="hidden" name="hanh_dong" value="hoan_tat">
                <button type="submit" class="btn btn-chinh">Hoàn tất kiểm kê</button>
            </form>
        </div>
    </div>
</div>

<!-- Khối: Đợt kiểm kê gần đây -->
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
        <table class="table">
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
                        <div class="fw-semibold text-primary"><?= e($d['ma_dot_kiem_ke']) ?></div>
                        <div class="mo-ta-phu"><?= e(TEN_LOAI_KIEM_KE[$d['loai_kiem_ke']] ?? $d['loai_kiem_ke']) ?> • <?= date('d/m/Y', strtotime($d['thoi_gian_bat_dau'])) ?></div>
                    </td>
                    <td>Phòng <?= e($d['ten_phong_truc']) ?></td>
                    <td>
                        <?php if ($d['trang_thai'] === 'HOAN_TAT'): ?>
                            <span class="huy-hieu huy-hieu-thanh-cong">Đã hoàn tất</span>
                        <?php elseif ($d['trang_thai'] === 'DANG_KIEM_KE'): ?>
                            <span class="huy-hieu huy-hieu-chinh">Đang thực hiện</span>
                        <?php else: ?>
                            <span class="huy-hieu huy-hieu-xam">Đã hủy</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <a href="<?= url('kiem-ke/chi-tiet.php?id=' . (int)$d['id']) ?>" class="btn btn-phu btn-sm">
                            Xem
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Ghi nhận kết quả kiểm kê từng thiết bị -->
<div class="modal fade" id="modalGhiNhan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="<?= url('kiem-ke/thuc-hien.php?id=' . (int)$dot['id']) ?>" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="hanh_dong" value="ghi_nhan">
                <input type="hidden" name="chi_tiet_id" id="gn_chi_tiet_id">
                <input type="hidden" name="tai_san_id" id="gn_tai_san_id">

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fs-6 fw-semibold">Đối chiếu thiết bị</h5>
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
                    <button type="button" class="btn btn-phu" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-chinh">Lưu kết quả</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= url('assets/vendor/jsqr/jsQR.js') ?>"></script>
<script>
function moModalGhiNhan(ct) {
    document.getElementById('gn_chi_tiet_id').value = ct.id;
    document.getElementById('gn_tai_san_id').value = ct.tai_san_id;
    document.getElementById('gn_ten_thiet_bi').textContent = ct.ten_tai_san;
    document.getElementById('gn_ma_tai_san').textContent = ct.ma_tai_san;
    document.getElementById('gn_ket_qua').value = ct.ket_qua_kiem_ke || 'TIM_THAY';
    document.getElementById('gn_tinh_trang_thuc_te').value = ct.tinh_trang_thuc_te || 'TOT';
    document.getElementById('gn_ghi_chu').value = ct.ghi_chu || '';
    doiKetQuaGhiNhan(document.getElementById('gn_ket_qua').value);

    const m = new bootstrap.Modal(document.getElementById('modalGhiNhan'));
    m.show();
}

function doiKetQuaGhiNhan(val) {
    const box = document.getElementById('gn_box_tinh_trang');
    if (val === 'CHUA_TIM_THAY') {
        box.style.display = 'none';
    } else {
        box.style.display = 'block';
    }
}

// Tìm thiết bị
function xuLyThemMaThietBi() {
    const ma = document.getElementById('ma_quet_nhap').value.trim();
    if (!ma) return;
    window.location.href = '<?= url("kiem-ke/thuc-hien.php?id=" . (int)$dot['id']) ?>&tu_khoa=' + encodeURIComponent(ma);
}

document.getElementById('ma_quet_nhap')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        xuLyThemMaThietBi();
    }
});

// Camera
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
        const code = jsQR(imgData.data, imgData.width, imgData.height, { inversionAttempts: 'dontInvert' });

        if (code && code.data) {
            dungCamera();
            window.location.href = '<?= url("kiem-ke/thuc-hien.php?id=" . (int)$dot['id']) ?>&tu_khoa=' + encodeURIComponent(code.data);
            return;
        }
    }
    requestAnimationFrame(quetVongLap);
}
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
