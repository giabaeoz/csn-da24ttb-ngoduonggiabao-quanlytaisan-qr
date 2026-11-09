<?php
/**
 * Giao diện Lập phiếu Mượn tài sản bằng mã QR (jsQR Camera Scanner & Manual Input).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Lập phiếu Mượn thiết bị bằng mã QR';
require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">📤 Lập phiếu Mượn tài sản bằng mã QR</h1>
        <p class="text-secondary small mb-0">
            Quét mã QR thiết bị bằng camera hoặc nhập mã thủ công. Bàn trực xử lý: 
            <strong class="text-primary"><?= e($phongTrucHienTai['ten_phong_truc'] ?? 'D6') ?></strong>
        </p>
    </div>
    <div>
        <a href="<?= url('muon-tra/index.php') ?>" class="btn btn-outline-secondary fw-semibold">
            📋 Lịch sử phiếu mượn
        </a>
    </div>
</div>

<?php if ($dangBiKhoaKiemKe): ?>
<div class="alert alert-danger shadow-sm rounded-3 mb-4 p-4" role="alert">
    <div class="d-flex align-items-center">
        <span class="fs-1 me-3">🔒</span>
        <div>
            <h5 class="fw-bold mb-1">Bàn trực đang bị khóa do Kiểm kê!</h5>
            <p class="mb-0">
                Phòng trực này hiện đang có một đợt kiểm kê tài sản chưa hoàn tất. Theo quy định đề tài,
                mọi hoạt động xuất mượn thiết bị tạm thời bị đình chỉ cho đến khi chốt hoặc hủy đợt kiểm kê.
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($thongBaoLoi)): ?>
<div class="alert alert-danger alert-dismissible fade show shadow-sm mb-4" role="alert">
    <strong>Lỗi xuất mượn:</strong> <?= e($thongBaoLoi) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form action="<?= url('muon-tra/muon.php') ?>" method="POST" id="formMuonTaiSan" class="<?= $dangBiKhoaKiemKe ? 'opacity-50 pe-none' : '' ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="nguoi_muon_id" id="nguoi_muon_id" value="<?= (int)($nguoiMuonMacDinh['id'] ?? 0) ?>">

    <div class="row g-4">
        <!-- CỘT TRÁI: THÔNG TIN NGƯỜI MƯỢN & PHÒNG HỌC -->
        <div class="col-12 col-lg-5">
            
            <!-- KHỐI 1: NGƯỜI MƯỢN -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">1. Người mượn tài sản</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleDangKyNhanh" onclick="toggleDangKyNhanh()">
                        + Đăng ký nhanh
                    </button>
                </div>

                <div class="card-body p-4">
                    <!-- Ô tìm kiếm người mượn -->
                    <div id="khuVucTimKiemNm" class="<?= !empty($nguoiMuonMacDinh) ? 'd-none' : '' ?>">
                        <label for="tu_khoa_nguoi_muon" class="form-label fw-semibold small text-secondary">
                            Quét thẻ hoặc tìm theo MSSV / MSGV / SĐT
                        </label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light">🔍</span>
                            <input type="text" class="form-control" id="tu_khoa_nguoi_muon" 
                                   placeholder="Gõ mã số trường hoặc số điện thoại..." autocomplete="off">
                        </div>
                        <div id="goiYNguoiMuon" class="list-group small shadow-sm d-none mb-3" style="max-height: 200px; overflow-y: auto;">
                            <!-- Kết quả tìm kiếm AJAX sẽ đổ vào đây -->
                        </div>
                    </div>

                    <!-- Thẻ hiển thị thông tin người mượn đã chọn -->
                    <div id="theThongTinNm" class="p-3 border rounded-3 bg-light <?= empty($nguoiMuonMacDinh) ? 'd-none' : '' ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0" id="hien_thi_ten_nm">
                                    <?= e($nguoiMuonMacDinh['ho_ten'] ?? '') ?>
                                </h6>
                                <span class="badge bg-primary small" id="hien_thi_loai_nm">
                                    <?= ($nguoiMuonMacDinh['loai_nguoi_muon'] ?? '') === 'GIANG_VIEN' ? 'Giảng viên' : 'Sinh viên' ?>
                                </span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="xoaNguoiMuonDaChon()" title="Chọn người khác">
                                Đổi người
                            </button>
                        </div>
                        <div class="small text-secondary">
                            <div>MSSV/MSGV: <strong class="text-dark" id="hien_thi_mst_nm"><?= e($nguoiMuonMacDinh['ma_so_truong'] ?? '') ?></strong></div>
                            <div>Lớp / Khoa: <span id="hien_thi_lop_nm"><?= e($nguoiMuonMacDinh['lop_don_vi'] ?? '-') ?></span></div>
                            <div>Số điện thoại: <span id="hien_thi_sdt_nm"><?= e($nguoiMuonMacDinh['so_dien_thoai'] ?? '-') ?></span></div>
                        </div>
                    </div>

                    <!-- Khung đăng ký nhanh người mượn mới (ẩn mặc định) -->
                    <div id="khuVucDangKyNhanh" class="d-none border-top pt-3 mt-3">
                        <input type="hidden" name="tao_nguoi_muon_moi" id="tao_nguoi_muon_moi" value="0">
                        <h6 class="fw-bold text-primary small mb-3">📝 Đăng ký nhanh hồ sơ người mượn mới</h6>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary">Mã số trường (MSSV/MSGV) *</label>
                            <input type="text" class="form-control form-control-sm" name="moi_ma_so_truong" id="moi_ma_so_truong" placeholder="Ví dụ: 110124999">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary">Họ và tên *</label>
                            <input type="text" class="form-control form-control-sm" name="moi_ho_ten" id="moi_ho_ten" placeholder="Ví dụ: Trần Văn Mới">
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Đối tượng</label>
                                <select class="form-select form-select-sm" name="moi_loai_nguoi_muon" id="moi_loai_nguoi_muon">
                                    <option value="SINH_VIEN">Sinh viên</option>
                                    <option value="GIANG_VIEN">Giảng viên</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Lớp / Khoa</label>
                                <input type="text" class="form-control form-control-sm" name="moi_lop_don_vi" id="moi_lop_don_vi" placeholder="DA22TTA">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary">Số điện thoại</label>
                            <input type="text" class="form-control form-control-sm" name="moi_so_dien_thoai" id="moi_so_dien_thoai" placeholder="0912345678">
                        </div>
                    </div>
                </div>
            </div>

            <!-- KHỐI 2: PHÒNG HỌC SỬ DỤNG -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">2. Phòng học đích</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="phong_hoc_id" class="form-label fw-semibold small text-secondary">
                            Chọn phòng học mang thiết bị đến <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg fs-6" id="phong_hoc_id" name="phong_hoc_id" required onchange="doiPhongHoc(this)">
                            <option value="">-- Chọn phòng học --</option>
                            <?php foreach ($danhSachPhongHoc as $ph): ?>
                            <option value="<?= (int)$ph['id'] ?>" 
                                    data-ma="<?= e($ph['ma_phong']) ?>"
                                    data-thiet-bi="<?= e($ph['thiet_bi_phong'] ?: '[]') ?>">
                                <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?> (<?= e($ph['toa_nha'] ?: 'Toà nhà') ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Hiển thị cấu hình thiết bị phòng học đã chọn -->
                    <div id="khuVucThietBiPhongHoc" class="p-3 border rounded-3 bg-light d-none small">
                        <div class="fw-bold text-dark mb-1">
                            ⚙️ Thiết bị cố định tại phòng <span id="lbl_ma_phong" class="text-primary font-monospace"></span>:
                        </div>
                        <div id="lbl_ds_thiet_bi_phong" class="d-flex flex-wrap gap-1 mt-1">
                            <!-- Các badge thiết bị cố định sẽ render ở đây -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- KHỐI 3: HẠN TRẢ & MỤC ĐÍCH -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="fw-bold mb-0 text-dark">3. Thời gian & Mục đích</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="han_tra" class="form-label fw-semibold small text-secondary">
                            Hạn trả dự kiến <span class="text-danger">*</span>
                        </label>
                        <?php
                            $macDinhHanTra = date('Y-m-d\T17:30');
                            if (time() >= strtotime(date('Y-m-d 17:00:00'))) {
                                $macDinhHanTra = date('Y-m-d\TH:i', time() + 14400); // Sau 4 giờ
                            }
                        ?>
                        <input type="datetime-local" class="form-control" id="han_tra" name="han_tra" 
                               value="<?= $macDinhHanTra ?>" required>
                        <div class="form-text small">Thời điểm người mượn phải hoàn trả thiết bị về bàn trực.</div>
                    </div>

                    <div>
                        <label for="muc_dich_su_dung" class="form-label fw-semibold small text-secondary">Mục đích sử dụng</label>
                        <input type="text" class="form-control" id="muc_dich_su_dung" name="muc_dich_su_dung" 
                               value="Phục vụ giảng dạy / học tập" placeholder="Ví dụ: Giảng bài, thuyết trình đề tài...">
                    </div>
                </div>
            </div>

        </div>

        <!-- CỘT PHẢI: QUÉT MÃ QR & DANH SÁCH THIẾT BỊ MƯỢN -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 bg-white mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">4. Quét mã QR thiết bị</h5>
                    <button type="button" class="btn btn-sm btn-outline-success fw-semibold" id="btnToggleCamera" onclick="toggleCamera()">
                        📹 Bật Camera quét QR
                    </button>
                </div>

                <div class="card-body p-4">
                    <!-- KHUNG QUÉT CAMERA (Dùng thư viện jsQR offline) -->
                    <div id="khuVucCamera" class="d-none text-center mb-4 p-3 bg-dark rounded-4">
                        <div class="position-relative d-inline-block">
                            <video id="videoQuet" playsinline style="max-width: 100%; max-height: 260px; border-radius: 8px;"></video>
                            <canvas id="canvasQuet" class="d-none"></canvas>
                            <div class="position-absolute top-50 start-50 translate-middle border border-2 border-success rounded-3" 
                                 style="width: 180px; height: 180px; pointer-events: none; box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.4);">
                            </div>
                        </div>
                        <div class="text-white small mt-2">
                            Hướng camera về phía tem mã QR dán trên thiết bị để quét tự động...
                        </div>
                    </div>

                    <!-- Ô NHẬP MÃ THỦ CÔNG HOẶC MÁY QUÉT BARCODE USB -->
                    <div class="mb-4">
                        <label for="ma_quet_nhap" class="form-label fw-semibold small text-secondary">
                            Nhập mã QR hoặc Mã thiết bị (hỗ trợ đầu đọc mã vạch USB)
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-lg font-monospace fs-6" id="ma_quet_nhap" 
                                   placeholder="Ví dụ: QR-D6-MC0001 hoặc MC-0001..." autocomplete="off">
                            <button type="button" class="btn btn-primary fw-semibold px-4" id="btnThemThietBi" onclick="xuLyThemThietBi()">
                                + Thêm thiết bị
                            </button>
                        </div>
                        <div id="thongBaoQuetLoi" class="text-danger small fw-semibold mt-1 d-none"></div>
                    </div>

                    <!-- BẢNG DANH SÁCH THIẾT BỊ ĐÃ CHỌN TRONG PHIẾU -->
                    <div class="border rounded-3 overflow-hidden">
                        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark">
                                Danh sách thiết bị trong phiếu (<span id="dem_so_luong_ts">0</span>/30)
                            </span>
                            <span class="text-muted small">Kiểm tra tương thích trực tiếp với phòng học</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="bangThietBiMuon">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-3" style="width: 120px;">Mã TS</th>
                                        <th>Tên thiết bị</th>
                                        <th>Hãng & Model</th>
                                        <th>Độ tương thích phòng</th>
                                        <th class="text-end pe-3" style="width: 80px;">Bỏ</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyThietBiMuon">
                                    <tr id="dongTrongTs">
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            Chưa có thiết bị nào trong phiếu. Hãy quét mã QR hoặc gõ mã thiết bị vào ô trên.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- NÚT XÁC NHẬN TẠO PHIẾU MƯỢN -->
                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="text-secondary small">
                            Thiết bị sẽ được ghi nhận phòng học hiện tại sau khi xác nhận.
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg fw-bold px-5 shadow-sm" id="btnXacNhanMuon" disabled>
                            Xác nhận tạo phiếu mượn 📤
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</form>

<!-- Nạp thư viện jsQR offline cho quét camera -->
<script src="<?= url('assets/vendor/jsqr/jsQR.js') ?>"></script>

<script>
// Mảng lưu danh sách thiết bị đang chọn trong phiếu: [{ id, ma_tai_san, ten_tai_san, ma_qr, hang_san_xuat, model, pham_vi_tuong_thich }]
const danhSachThietBi = [];

// =====================================================================
// 1. TÌM KIẾM NGƯỜI MƯỢN BẰNG AJAX
// =====================================================================
const inputTuKhoaNm = document.getElementById('tu_khoa_nguoi_muon');
const goiYNmBox = document.getElementById('goiYNguoiMuon');

let debounceNm = null;
if (inputTuKhoaNm) {
    inputTuKhoaNm.addEventListener('input', function () {
        clearTimeout(debounceNm);
        const val = this.value.trim();
        if (val.length < 2) {
            goiYNmBox.classList.add('d-none');
            goiYNmBox.innerHTML = '';
            return;
        }

        debounceNm = setTimeout(async function () {
            try {
                const res = await fetch('<?= url("api/nguoi-muon.php") ?>?tu_khoa=' + encodeURIComponent(val));
                const json = await res.json();
                if (json.thanh_cong && json.du_lieu.length > 0) {
                    goiYNmBox.innerHTML = '';
                    json.du_lieu.forEach(nm => {
                        const a = document.createElement('a');
                        a.href = '#';
                        a.className = 'list-group-item list-group-item-action py-2';
                        a.innerHTML = `<strong>${escapeHtml(nm.ho_ten)}</strong> (${escapeHtml(nm.ma_so_truong)}) - ${escapeHtml(nm.lop_don_vi || 'N/A')} - SĐT: ${escapeHtml(nm.so_dien_thoai || 'N/A')}`;
                        a.onclick = function (e) {
                            e.preventDefault();
                            chonNguoiMuon(nm);
                        };
                        goiYNmBox.appendChild(a);
                    });
                    goiYNmBox.classList.remove('d-none');
                } else {
                    goiYNmBox.innerHTML = '<div class="list-group-item text-muted py-2">Không tìm thấy người mượn phù hợp</div>';
                    goiYNmBox.classList.remove('d-none');
                }
            } catch (err) {
                console.error('Lỗi tìm người mượn:', err);
            }
        }, 300);
    });
}

function chonNguoiMuon(nm) {
    document.getElementById('nguoi_muon_id').value = nm.id;
    document.getElementById('hien_thi_ten_nm').innerText = nm.ho_ten;
    document.getElementById('hien_thi_mst_nm').innerText = nm.ma_so_truong;
    document.getElementById('hien_thi_loai_nm').innerText = nm.loai_nguoi_muon === 'GIANG_VIEN' ? 'Giảng viên' : 'Sinh viên';
    document.getElementById('hien_thi_lop_nm').innerText = nm.lop_don_vi || '-';
    document.getElementById('hien_thi_sdt_nm').innerText = nm.so_dien_thoai || '-';

    document.getElementById('khuVucTimKiemNm').classList.add('d-none');
    document.getElementById('theThongTinNm').classList.remove('d-none');
    goiYNmBox.classList.add('d-none');
    kiemTraDieuKienNutTaoPhieu();
}

function xoaNguoiMuonDaChon() {
    document.getElementById('nguoi_muon_id').value = '0';
    document.getElementById('khuVucTimKiemNm').classList.remove('d-none');
    document.getElementById('theThongTinNm').classList.add('d-none');
    inputTuKhoaNm.value = '';
    inputTuKhoaNm.focus();
    kiemTraDieuKienNutTaoPhieu();
}

function toggleDangKyNhanh() {
    const box = document.getElementById('khuVucDangKyNhanh');
    const flag = document.getElementById('tao_nguoi_muon_moi');
    const btn = document.getElementById('btnToggleDangKyNhanh');

    if (box.classList.contains('d-none')) {
        box.classList.remove('d-none');
        flag.value = '1';
        btn.innerText = 'Đóng đăng ký';
        xoaNguoiMuonDaChon();
        document.getElementById('khuVucTimKiemNm').classList.add('d-none');
    } else {
        box.classList.add('d-none');
        flag.value = '0';
        btn.innerText = '+ Đăng ký nhanh';
        document.getElementById('khuVucTimKiemNm').classList.remove('d-none');
    }
    kiemTraDieuKienNutTaoPhieu();
}

// =====================================================================
// 2. PHÒNG HỌC & THIẾT BỊ CỐ ĐỊNH
// =====================================================================
let phongHocHienTai = null;

function doiPhongHoc(sel) {
    const opt = sel.options[sel.selectedIndex];
    const box = document.getElementById('khuVucThietBiPhongHoc');
    const lblMa = document.getElementById('lbl_ma_phong');
    const lblDs = document.getElementById('lbl_ds_thiet_bi_phong');

    if (!opt.value) {
        phongHocHienTai = null;
        box.classList.add('d-none');
    } else {
        const ma = opt.getAttribute('data-ma');
        let tb = [];
        try {
            tb = JSON.parse(opt.getAttribute('data-thiet-bi') || '[]');
        } catch (e) {
            tb = [];
        }

        phongHocHienTai = {
            id: parseInt(opt.value, 10),
            ma_phong: ma,
            thiet_bi_phong: tb
        };

        lblMa.innerText = ma;
        lblDs.innerHTML = '';
        if (tb.length === 0) {
            lblDs.innerHTML = '<span class="text-muted fst-italic">Chưa có thiết bị cố định</span>';
        } else {
            tb.forEach(item => {
                const span = document.createElement('span');
                span.className = 'badge bg-white text-dark border py-1 px-2';
                span.innerHTML = `<strong>${escapeHtml(item.ten)}:</strong> ${escapeHtml(item.hang)} ${item.model ? '(' + escapeHtml(item.model) + ')' : ''}`;
                lblDs.appendChild(span);
            });
        }
        box.classList.remove('d-none');
    }

    // Tự động kiểm tra lại độ tương thích của toàn bộ danh sách thiết bị đang chọn
    capNhatBangThietBi();
    kiemTraDieuKienNutTaoPhieu();
}

// =====================================================================
// 3. THÊM THIẾT BỊ VÀO PHIẾU (QUÉT MÃ HOẶC NHẬP THỦ CÔNG)
// =====================================================================
const inputMaQuet = document.getElementById('ma_quet_nhap');
const thongBaoLoiQuet = document.getElementById('thongBaoQuetLoi');

if (inputMaQuet) {
    inputMaQuet.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            xuLyThemThietBi();
        }
    });
}

async function xuLyThemThietBi(maTruyenVao = null) {
    const ma = (maTruyenVao !== null ? maTruyenVao : inputMaQuet.value).trim();
    thongBaoLoiQuet.classList.add('d-none');

    if (!ma) {
        return;
    }

    // Chặn quét trùng trong danh sách hiện tại
    const daCo = danhSachThietBi.some(ts => ts.ma_tai_san.toUpperCase() === ma.toUpperCase() || ts.ma_qr.toUpperCase() === ma.toUpperCase());
    if (daCo) {
        hienThiLoiQuet(`Thiết bị [${ma}] đã có trong phiếu mượn này!`);
        inputMaQuet.value = '';
        return;
    }

    if (danhSachThietBi.length >= 30) {
        hienThiLoiQuet('Số lượng thiết bị trong một phiếu đã đạt mức tối đa (30 thiết bị).');
        return;
    }

    try {
        const phongHocId = phongHocHienTai ? phongHocHienTai.id : 0;
        let url = '<?= url("api/tai-san.php") ?>?';
        if (ma.toUpperCase().startsWith('QR-')) {
            url += 'ma_qr=' + encodeURIComponent(ma);
        } else {
            url += 'ma_tai_san=' + encodeURIComponent(ma);
        }
        if (phongHocId > 0) {
            url += '&phong_hoc_id=' + phongHocId;
        }

        const res = await fetch(url);
        if (!res.ok) {
            hienThiLoiQuet(`Không tìm thấy thiết bị có mã [${ma}] trong hệ thống.`);
            inputMaQuet.value = '';
            return;
        }

        const json = await res.json();
        if (!json.thanh_cong) {
            hienThiLoiQuet(json.loi || 'Lỗi tra cứu thiết bị.');
            inputMaQuet.value = '';
            return;
        }

        const ts = json.du_lieu;

        // Kiểm tra quyền bàn trực của nhân viên
        const banTrucId = <?= (int)$phongTrucId ?>;
        if (parseInt(ts.phong_truc_id, 10) !== banTrucId) {
            hienThiLoiQuet(`Thiết bị [${ts.ma_tai_san}] thuộc bàn trực ${ts.ma_phong_truc}. Bạn chỉ được mượn thiết bị của bàn trực phụ trách.`);
            inputMaQuet.value = '';
            return;
        }

        // Kiểm tra trạng thái sẵn sàng
        if (ts.trang_thai !== 'SAN_SANG' || ts.tinh_trang !== 'TOT') {
            hienThiLoiQuet(`Thiết bị [${ts.ma_tai_san}] hiện không sẵn sàng (Trạng thái: ${ts.trang_thai}, Tình trạng: ${ts.tinh_trang}).`);
            inputMaQuet.value = '';
            return;
        }

        // Thêm vào mảng thiết bị
        danhSachThietBi.push(ts);
        inputMaQuet.value = '';
        capNhatBangThietBi();
        kiemTraDieuKienNutTaoPhieu();

    } catch (err) {
        console.error('Lỗi quét thiết bị:', err);
        hienThiLoiQuet('Lỗi kết nối máy chủ khi tra cứu thiết bị.');
    }
}

function hienThiLoiQuet(msg) {
    thongBaoLoiQuet.innerText = msg;
    thongBaoLoiQuet.classList.remove('d-none');
}

function xoaThietBiKhoiPhieu(idx) {
    danhSachThietBi.splice(idx, 1);
    capNhatBangThietBi();
    kiemTraDieuKienNutTaoPhieu();
}

function capNhatBangThietBi() {
    const tbody = document.getElementById('tbodyThietBiMuon');
    const lblDem = document.getElementById('dem_so_luong_ts');
    lblDem.innerText = danhSachThietBi.length;

    if (danhSachThietBi.length === 0) {
        tbody.innerHTML = `
            <tr id="dongTrongTs">
                <td colspan="5" class="text-center py-5 text-muted">
                    Chưa có thiết bị nào trong phiếu. Hãy quét mã QR hoặc gõ mã thiết bị vào ô trên.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = '';
    danhSachThietBi.forEach((ts, idx) => {
        // Kiểm tra tương thích với phòng học đích
        let tuongThich = true;
        let lyDo = 'Linh hoạt cho mọi phòng';

        if (phongHocHienTai) {
            if (ts.pham_vi_tuong_thich === 'THEO_THIET_BI') {
                const hangTs = (ts.hang_san_xuat || '').trim().toLowerCase();
                const modelTs = (ts.model || '').trim().toLowerCase();

                let coKhop = false;
                if (Array.isArray(phongHocHienTai.thiet_bi_phong)) {
                    phongHocHienTai.thiet_bi_phong.forEach(tb => {
                        const hangTb = (tb.hang || '').trim().toLowerCase();
                        const modelTb = (tb.model || '').trim().toLowerCase();
                        if (hangTb && hangTb === hangTs) {
                            if (modelTs && modelTb) {
                                if (modelTs === modelTb) {
                                    coKhop = true;
                                    lyDo = `Khớp ${tb.ten} (${tb.hang} ${tb.model})`;
                                }
                            } else {
                                coKhop = true;
                                lyDo = `Khớp hãng ${tb.hang}`;
                            }
                        }
                    });
                }

                if (!coKhop) {
                    tuongThich = false;
                    lyDo = `Phòng không có thiết bị hãng ${ts.hang_san_xuat || 'N/A'}`;
                }
            } else if (ts.pham_vi_tuong_thich === 'THEO_PHONG') {
                let dsP = [];
                try {
                    dsP = typeof ts.phong_tuong_thich === 'string' ? JSON.parse(ts.phong_tuong_thich) : ts.phong_tuong_thich;
                } catch (e) {
                    dsP = [];
                }
                if (!Array.isArray(dsP) || !dsP.includes(phongHocHienTai.ma_phong)) {
                    tuongThich = false;
                    lyDo = `Chỉ dùng cho phòng: ${dsP.join(', ')}`;
                } else {
                    lyDo = `Đúng phòng chỉ định (${phongHocHienTai.ma_phong})`;
                }
            }
        } else {
            lyDo = 'Chưa chọn phòng học đích';
        }

        const tr = document.createElement('tr');
        tr.className = tuongThich ? '' : 'table-danger';
        tr.innerHTML = `
            <td class="ps-3 font-monospace fw-bold text-primary">
                ${escapeHtml(ts.ma_tai_san)}
                <input type="hidden" name="tai_san_ids[]" value="${ts.id}">
            </td>
            <td>
                <div class="fw-semibold">${escapeHtml(ts.ten_tai_san)}</div>
                <div class="text-muted small">${escapeHtml(ts.ma_qr)}</div>
            </td>
            <td>
                <span class="badge bg-light text-dark border">
                    ${escapeHtml(ts.hang_san_xuat || '-')} ${ts.model ? '(' + escapeHtml(ts.model) + ')' : ''}
                </span>
            </td>
            <td>
                ${tuongThich 
                    ? `<span class="badge bg-success bg-opacity-10 text-success fw-semibold">✅ ${escapeHtml(lyDo)}</span>`
                    : `<span class="badge bg-danger fw-semibold" title="${escapeHtml(lyDo)}">❌ Không tương thích</span><div class="small text-danger fst-italic">${escapeHtml(lyDo)}</div>`
                }
            </td>
            <td class="text-end pe-3">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="xoaThietBiKhoiPhieu(${idx})" title="Xóa">
                    ✕
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function kiemTraDieuKienNutTaoPhieu() {
    const btn = document.getElementById('btnXacNhanMuon');
    const nguoiMuonId = parseInt(document.getElementById('nguoi_muon_id').value, 10);
    const coDangKyNhanh = document.getElementById('tao_nguoi_muon_moi').value === '1';
    const phongHocId = parseInt(document.getElementById('phong_hoc_id').value, 10);
    const coThietBi = danhSachThietBi.length > 0;

    let hopLeNguoiMuon = nguoiMuonId > 0;
    if (coDangKyNhanh) {
        const mst = (document.getElementById('moi_ma_so_truong').value || '').trim();
        const ten = (document.getElementById('moi_ho_ten').value || '').trim();
        hopLeNguoiMuon = (mst !== '' && ten !== '');
    }

    // Kiểm tra xem có thiết bị nào bị đánh dấu KHÔNG TƯƠNG THÍCH không
    let coThietBiLoi = false;
    if (phongHocHienTai && danhSachThietBi.length > 0) {
        danhSachThietBi.forEach(ts => {
            if (ts.pham_vi_tuong_thich === 'THEO_THIET_BI') {
                const hangTs = (ts.hang_san_xuat || '').trim().toLowerCase();
                const modelTs = (ts.model || '').trim().toLowerCase();
                let match = false;
                if (Array.isArray(phongHocHienTai.thiet_bi_phong)) {
                    phongHocHienTai.thiet_bi_phong.forEach(tb => {
                        const hangTb = (tb.hang || '').trim().toLowerCase();
                        const modelTb = (tb.model || '').trim().toLowerCase();
                        if (hangTb && hangTb === hangTs) {
                            if (modelTs && modelTb) {
                                if (modelTs === modelTb) match = true;
                            } else {
                                match = true;
                            }
                        }
                    });
                }
                if (!match) coThietBiLoi = true;
            }
        });
    }

    btn.disabled = !(hopLeNguoiMuon && phongHocId > 0 && coThietBi && !coThietBiLoi);
}

// =====================================================================
// 4. QUÉT CAMERA BẰNG JSQR (OFFLINE)
// =====================================================================
let streamCamera = null;
let dangQuet = false;

async function toggleCamera() {
    const box = document.getElementById('khuVucCamera');
    const btn = document.getElementById('btnToggleCamera');
    const video = document.getElementById('videoQuet');

    if (dangQuet) {
        // Tắt camera
        if (streamCamera) {
            streamCamera.getTracks().forEach(track => track.stop());
            streamCamera = null;
        }
        dangQuet = false;
        box.classList.add('d-none');
        btn.innerText = '📹 Bật Camera quét QR';
        btn.className = 'btn btn-sm btn-outline-success fw-semibold';
    } else {
        // Bật camera
        try {
            streamCamera = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' }
            });
            video.srcObject = streamCamera;
            video.setAttribute('playsinline', true);
            video.play();
            dangQuet = true;
            box.classList.remove('d-none');
            btn.innerText = '⏹️ Tắt Camera';
            btn.className = 'btn btn-sm btn-danger fw-semibold';
            requestAnimationFrame(vongLapQuetCamera);
        } catch (err) {
            console.error('Không mở được camera:', err);
            alert('Không thể truy cập camera. Vui lòng cấp quyền truy cập camera trong trình duyệt hoặc sử dụng ô nhập mã thủ công.');
        }
    }
}

function vongLapQuetCamera() {
    if (!dangQuet) return;

    const video = document.getElementById('videoQuet');
    const canvas = document.getElementById('canvasQuet');
    const ctx = canvas.getContext('2d');

    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.height = video.videoHeight;
        canvas.width = video.videoWidth;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: "dontInvert"
        });

        if (code && code.data) {
            const ketQuaQuet = code.data.trim();
            // Nếu phát hiện mã QR hợp lệ thì gửi xử lý
            xuLyThemThietBi(ketQuaQuet);

            // Tạm dừng 1.5 giây để tránh quét lặp liên tục một tem
            setTimeout(() => {
                if (dangQuet) requestAnimationFrame(vongLapQuetCamera);
            }, 1500);
            return;
        }
    }

    if (dangQuet) {
        requestAnimationFrame(vongLapQuetCamera);
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
