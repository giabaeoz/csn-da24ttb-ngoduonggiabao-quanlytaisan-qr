<?php

/**
 * Giao diện Lập phiếu Mượn tài sản - Khớp chính xác Trang mượn tài sản.pdf
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var bool $dangBiKhoaKiemKe
 * @var list<array<string, mixed>> $danhSachPhongHoc
 * @var array<string, mixed>|null $nguoiMuonMacDinh
 * @var string $thongBaoLoi
 */

$tieuDeTrang = 'Mượn - trả';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<!-- Tiêu đề trang & Tabs chuyển đổi -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang">Mượn - trả tài sản</h1>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('muon-tra/muon.php') ?>" class="btn btn-chinh">Mượn</a>
        <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-phu">Trả</a>
    </div>
</div>

<?php if ($dangBiKhoaKiemKe): ?>
    <div class="alert alert-danger mb-4" role="alert">
        Bàn trực hiện đang có đợt kiểm kê tài sản. Hoạt động mượn tạm thời bị khóa.
    </div>
<?php endif; ?>

<?php if (!empty($thongBaoLoi)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <?= e($thongBaoLoi) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
<?php endif; ?>

<form action="<?= url('muon-tra/muon.php') ?>" method="POST" id="formMuonTaiSan" class="<?= $dangBiKhoaKiemKe ? 'opacity-50 pe-none' : '' ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="nguoi_muon_id" id="nguoi_muon_id" value="<?= (int)($nguoiMuonMacDinh['id'] ?? 0) ?>">
    <input type="hidden" name="tao_nguoi_muon_moi" id="tao_nguoi_muon_moi" value="0">
    <div id="vungTaiSanIds"></div>

    <div class="row g-4">
        <!-- CỘT TRÁI: QUÉT QR & XEM TRƯỚC PHIẾU MƯỢN -->
        <div class="col-12 col-lg-5">
            <!-- Khối: Quét QR thiết bị -->
            <div class="card mb-4">
                <div class="card-body p-3">
                    <h2 class="tieu-de-khoi mb-3">Quét QR thiết bị</h2>
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

            <!-- Khối: Phiếu mượn (Xem trước thời gian thực) -->
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="tieu-de-khoi mb-3">Phiếu mượn</h2>
                    <div class="mb-3">
                        <div class="fw-semibold text-dark" id="pvThongTinNguoi">Chưa chọn người mượn</div>
                        <div class="mo-ta-phu" id="pvThongTinPhong">SĐT: --- • Phòng cần mượn: ---</div>
                        <div class="mo-ta-phu" id="pvThongTinThoiGian">Thời gian mượn: <?= date('d/m/Y H:i') ?> • Hạn trả: ---</div>
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

        <!-- CỘT PHẢI: THÔNG TIN MƯỢN & THIẾT BỊ ĐÃ QUÉT -->
        <div class="col-12 col-lg-7">
            <!-- Khối: Thông tin mượn -->
            <div class="card mb-4">
                <div class="card-body p-4">
                    <h2 class="tieu-de-khoi mb-3">Thông tin mượn</h2>

                    <!-- Tìm người mượn cũ -->
                    <div class="mb-3 position-relative">
                        <div class="input-group">
                            <input type="text" class="form-control" id="tu_khoa_nguoi_muon"
                                placeholder="Tìm người mượn cũ: mã số / họ tên / SĐT" autocomplete="off">
                            <button type="button" class="btn btn-phu" id="btnTimNm" onclick="timNguoiMuon()">
                                Tìm
                            </button>
                        </div>
                        <div id="goiYNguoiMuon" class="list-group position-absolute w-100 shadow-sm d-none mt-1" style="z-index: 1050; max-height: 200px; overflow-y: auto;"></div>
                    </div>

                    <!-- Lưới các trường thông tin -->
                    <div class="row g-2 mb-2">
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Mã số</label>
                            <input type="text" class="form-control" id="inputMaSo" name="moi_ma_so_truong"
                                value="<?= e($nguoiMuonMacDinh['ma_so_truong'] ?? '') ?>" oninput="capNhatPreview()">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Họ tên</label>
                            <input type="text" class="form-control" id="inputHoTen" name="moi_ho_ten"
                                value="<?= e($nguoiMuonMacDinh['ho_ten'] ?? '') ?>" oninput="capNhatPreview()">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control" id="inputSdt" name="moi_so_dien_thoai"
                                value="<?= e($nguoiMuonMacDinh['so_dien_thoai'] ?? '') ?>" oninput="capNhatPreview()">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Lớp / đơn vị</label>
                            <input type="text" class="form-control" id="inputLop" name="moi_lop_don_vi"
                                value="<?= e($nguoiMuonMacDinh['lop_don_vi'] ?? '') ?>" oninput="capNhatPreview()">
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Phòng cần mượn</label>
                            <select class="form-select" id="phong_hoc_id" name="phong_hoc_id" onchange="doiPhongHoc(this)">
                                <option value="">Chọn phòng học</option>
                                <?php foreach ($danhSachPhongHoc as $ph): ?>
                                    <option value="<?= (int)$ph['id'] ?>" data-ma="<?= e($ph['ma_phong']) ?>" data-thiet-bi="<?= e($ph['thiet_bi_phong'] ?? '[]') ?>">
                                        <?= e($ph['ma_phong']) ?> (<?= e($ph['ten_phong']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-sm-4">
                            <label class="form-label">Hạn trả</label>
                            <input type="datetime-local" class="form-control" id="han_tra" name="han_tra"
                                value="<?= date('Y-m-d\TH:i', strtotime('+3 hours')) ?>" onchange="capNhatPreview()">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Khối: Thiết bị cần mượn - đã quét -->
            <div class="card mb-4">
                <div class="card-header">
                    <h2 class="tieu-de-khoi mb-0">Thiết bị cần mượn - đã quét (<span id="demThietBi">0</span>)</h2>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mã thiết bị</th>
                                <th>Loại / tên thiết bị</th>
                                <th>Phòng sử dụng</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="bangThietBiQuet">
                            <tr id="hangChuaCoThietBi">
                                <td colspan="4" class="text-center py-4 text-phu">
                                    Chưa có thiết bị nào được quét.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Nút hành động chân trang -->
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('tong-quan.php') ?>" class="btn btn-phu">Hủy</a>
                <button type="submit" class="btn btn-chinh" id="btnXacNhanMuon" disabled>
                    Xác nhận mượn & lưu phiếu
                </button>
            </div>
        </div>
    </div>
</form>

<script src="<?= url('assets/vendor/jsqr/jsQR.js') ?>"></script>
<script>
    const danhSachThietBi = [];
    let phongHocHienTai = null;

    // ==========================================
    // 1. TÌM KIẾM NGƯỜI MƯỢN
    // ==========================================
    const inputTuKhoa = document.getElementById('tu_khoa_nguoi_muon');
    const goiYBox = document.getElementById('goiYNguoiMuon');

    async function timNguoiMuon() {
        const val = inputTuKhoa.value.trim();
        if (!val) return;
        try {
            const res = await fetch('<?= url("api/nguoi-muon.php") ?>?tu_khoa=' + encodeURIComponent(val));
            const json = await res.json();
            if (json.thanh_cong && json.du_lieu.length > 0) {
                goiYBox.innerHTML = '';
                json.du_lieu.forEach(nm => {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className = 'list-group-item list-group-item-action py-2';
                    a.textContent = `${nm.ho_ten} (${nm.ma_so_truong}) - ${nm.lop_don_vi || 'N/A'} - SĐT: ${nm.so_dien_thoai || 'N/A'}`;
                    a.onclick = (e) => {
                        e.preventDefault();
                        chonNguoiMuon(nm);
                    };
                    goiYBox.appendChild(a);
                });
                goiYBox.classList.remove('d-none');
            } else {
                goiYBox.innerHTML = '<div class="list-group-item text-muted py-2">Không tìm thấy người mượn cũ. Hãy điền thông tin vào các ô bên dưới để đăng ký mới.</div>';
                goiYBox.classList.remove('d-none');
                document.getElementById('tao_nguoi_muon_moi').value = '1';
            }
        } catch (e) {
            console.error(e);
        }
    }

    if (inputTuKhoa) {
        inputTuKhoa.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                timNguoiMuon();
            }
        });
    }

    function chonNguoiMuon(nm) {
        document.getElementById('nguoi_muon_id').value = nm.id;
        document.getElementById('tao_nguoi_muon_moi').value = '0';
        document.getElementById('inputMaSo').value = nm.ma_so_truong;
        document.getElementById('inputHoTen').value = nm.ho_ten;
        document.getElementById('inputSdt').value = nm.so_dien_thoai || '';
        document.getElementById('inputLop').value = nm.lop_don_vi || '';
        goiYBox.classList.add('d-none');
        capNhatPreview();
    }

    function doiPhongHoc(sel) {
        const opt = sel.options[sel.selectedIndex];
        if (!opt.value) {
            phongHocHienTai = null;
        } else {
            let tb = [];
            try {
                tb = JSON.parse(opt.getAttribute('data-thiet-bi') || '[]');
            } catch (e) {
                tb = [];
            }
            phongHocHienTai = {
                id: parseInt(opt.value, 10),
                ma_phong: opt.getAttribute('data-ma'),
                thiet_bi_phong: tb
            };
        }
        capNhatBangThietBi();
        capNhatPreview();
    }

    // ==========================================
    // 2. THÊM THIẾT BỊ BẰNG MÃ QR HOẶC NHẬP TAY
    // ==========================================
    const inputMaQuet = document.getElementById('ma_quet_nhap');
    const thongBaoLoiQuet = document.getElementById('thongBaoQuetLoi');

    if (inputMaQuet) {
        inputMaQuet.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                xuLyThemMaThietBi();
            }
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
            const res = await fetch('<?= url("api/tra-cuu-qr.php") ?>?ma=' + encodeURIComponent(ma) + '&hanh_dong=MUON');
            const json = await res.json();

            if (!json.thanh_cong) {
                hienLoiQuet(json.thong_diep || 'Không tìm thấy thiết bị.');
                return;
            }

            const ts = json.du_lieu;
            if (danhSachThietBi.some(item => item.id === ts.id)) {
                hienLoiQuet(`Thiết bị [${ts.ma_tai_san}] đã có trong phiếu mượn.`);
                return;
            }

            if (danhSachThietBi.length >= 30) {
                hienLoiQuet('Một phiếu mượn chỉ được chứa tối đa 30 thiết bị.');
                return;
            }

            danhSachThietBi.push(ts);
            inputMaQuet.value = '';
            capNhatBangThietBi();
            capNhatPreview();
        } catch (e) {
            hienLoiQuet('Lỗi kết nối máy chủ.');
        }
    }

    function hienLoiQuet(msg) {
        thongBaoLoiQuet.textContent = msg;
        thongBaoLoiQuet.classList.remove('d-none');
    }

    function xoaThietBi(idx) {
        danhSachThietBi.splice(idx, 1);
        capNhatBangThietBi();
        capNhatPreview();
    }

    function capNhatBangThietBi() {
        const tbody = document.getElementById('bangThietBiQuet');
        const demEl = document.getElementById('demThietBi');
        const hiddenContainer = document.getElementById('vungTaiSanIds');
        demEl.textContent = danhSachThietBi.length;

        hiddenContainer.innerHTML = '';
        if (danhSachThietBi.length === 0) {
            tbody.innerHTML = '<tr id="hangChuaCoThietBi"><td colspan="4" class="text-center py-4 text-phu">Chưa có thiết bị nào được quét.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        const maPhong = phongHocHienTai ? phongHocHienTai.ma_phong : '---';

        danhSachThietBi.forEach((ts, idx) => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'tai_san_ids[]';
            inp.value = ts.id;
            hiddenContainer.appendChild(inp);

            const tr = document.createElement('tr');
            tr.innerHTML = `
            <td class="fw-semibold">${escapeHtml(ts.ma_tai_san)}</td>
            <td>${escapeHtml(ts.ten_tai_san)}</td>
            <td>${escapeHtml(maPhong)}</td>
            <td class="text-end">
                <button type="button" class="btn btn-phu btn-sm py-1 px-2 text-danger" onclick="xoaThietBi(${idx})">
                    Xóa
                </button>
            </td>
        `;
            tbody.appendChild(tr);
        });
    }

    function capNhatPreview() {
        const maSo = document.getElementById('inputMaSo').value.trim() || '---';
        const hoTen = document.getElementById('inputHoTen').value.trim() || 'Chưa chọn người mượn';
        const sdt = document.getElementById('inputSdt').value.trim() || '---';
        const lop = document.getElementById('inputLop').value.trim() || '---';
        const maPhong = phongHocHienTai ? phongHocHienTai.ma_phong : '---';

        const hanTraVal = document.getElementById('han_tra').value;
        let hanTraStr = '---';
        if (hanTraVal) {
            const d = new Date(hanTraVal);
            hanTraStr = `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()} ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
        }

        document.getElementById('pvThongTinNguoi').textContent = `${hoTen} - ${maSo} - ${lop}`;
        document.getElementById('pvThongTinPhong').textContent = `SĐT: ${sdt} • Phòng cần mượn: ${maPhong}`;
        document.getElementById('pvThongTinThoiGian').textContent = `Thời gian mượn: <?= date('d/m/Y H:i') ?> • Hạn trả: ${hanTraStr}`;

        const pvTbody = document.getElementById('pvDanhSachThietBi');
        if (danhSachThietBi.length === 0) {
            pvTbody.innerHTML = '<tr><td colspan="2" class="text-center text-phu py-3">Chưa có thiết bị nào</td></tr>';
        } else {
            pvTbody.innerHTML = '';
            danhSachThietBi.forEach(ts => {
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${escapeHtml(ts.ma_tai_san)} - ${escapeHtml(ts.ten_tai_san)}</td><td class="text-end">1</td>`;
                pvTbody.appendChild(tr);
            });
        }
        document.getElementById('pvTongCongThietBi').textContent = `Tổng cộng: ${danhSachThietBi.length} thiết bị`;

        // Kiểm tra nút xác nhận
        const btnSubmit = document.getElementById('btnXacNhanMuon');
        const coNguoi = (document.getElementById('nguoi_muon_id').value !== '0') || (maSo !== '---' && hoTen !== 'Chưa chọn người mượn');
        const coPhong = (phongHocHienTai !== null);
        const coThietBi = (danhSachThietBi.length > 0);
        btnSubmit.disabled = !(coNguoi && coPhong && coThietBi);
    }

    // ==========================================
    // 3. CAMERA JSQR
    // ==========================================
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
            const code = jsQR(imgData.data, imgData.width, imgData.height, {
                inversionAttempts: 'dontInvert'
            });

            if (code && code.data) {
                traCuuVaThem(code.data);
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
    capNhatPreview();
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>