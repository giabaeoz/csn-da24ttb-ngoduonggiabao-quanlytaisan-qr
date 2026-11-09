<?php
/**
 * Giao diện: Lập phiếu điều chuyển thiết bị mới (G7).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';
?>

<div class="container-fluid py-3">
    <!-- Breadcrumb & Tiêu đề -->
    <div class="row align-items-center mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('tong-quan.php') ?>" class="text-decoration-none">Tổng quan</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('dieu-chuyen/index.php') ?>" class="text-decoration-none">Điều chuyển</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Lập phiếu mới</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold d-flex align-items-center gap-2">
                <span>🔄 Lập phiếu điều chuyển thiết bị</span>
            </h1>
            <p class="text-muted small mb-0">Chuyển giao và cập nhật vị trí thiết bị giữa các phòng học có kiểm tra tính tương thích thiết bị.</p>
        </div>
        <div class="col-md-4 text-md-end mt-2 mt-md-0">
            <a href="<?= url('dieu-chuyen/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
    </div>

    <?php if ($dangKiemKe): ?>
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center gap-3 py-3 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-2"></i>
            <div>
                <h5 class="alert-heading mb-1 fw-bold">Bàn trực đang trong đợt kiểm kê tài sản!</h5>
                <p class="mb-0">
                    Mọi hoạt động điều chuyển thiết bị tạm thời bị khóa cho đến khi chốt đợt kiểm kê.
                    Vui lòng hoàn tất hoặc hủy đợt kiểm kê trước khi tiếp tục thao tác.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <form id="formDieuChuyen" action="<?= url('dieu-chuyen/them.php') ?>" method="POST" <?= $dangKiemKe ? 'onsubmit="return false;"' : '' ?>>
        <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">

        <div class="row g-4">
            <!-- CỘT TRÁI: THIẾT LẬP PHÒNG & THÔNG TIN ĐIỀU CHUYỂN -->
            <div class="col-lg-5">
                <!-- Thẻ 1: Lộ trình điều chuyển -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle">1</span> Lộ trình điều chuyển
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="phong_hoc_di_id" class="form-label fw-semibold">
                                Phòng học nguồn (Phòng đi) <span class="text-danger">*</span>
                            </label>
                            <select id="phong_hoc_di_id" name="phong_hoc_di_id" class="form-select" required>
                                <option value="">-- Chọn phòng học có thiết bị cần chuyển --</option>
                                <?php foreach ($danhSachPhongHoc as $ph): ?>
                                    <option value="<?= (int)$ph['id'] ?>" 
                                            data-ma-phong="<?= e($ph['ma_phong']) ?>"
                                            <?= ((int)$duLieu['phong_hoc_di_id'] === (int)$ph['id']) ? 'selected' : '' ?>>
                                        <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Hệ thống sẽ lọc các thiết bị thuộc phòng trực của bạn đang ở phòng này.</div>
                        </div>

                        <div class="text-center my-2 text-muted">
                            <i class="bi bi-arrow-down-up fs-4 text-primary"></i>
                        </div>

                        <div class="mb-3">
                            <label for="phong_hoc_den_id" class="form-label fw-semibold">
                                Phòng học đích (Phòng đến) <span class="text-danger">*</span>
                            </label>
                            <select id="phong_hoc_den_id" name="phong_hoc_den_id" class="form-select" required>
                                <option value="">-- Chọn phòng học tiếp nhận thiết bị --</option>
                                <?php foreach ($danhSachPhongHoc as $ph): ?>
                                    <option value="<?= (int)$ph['id'] ?>" 
                                            data-ma-phong="<?= e($ph['ma_phong']) ?>"
                                            <?= ((int)$duLieu['phong_hoc_den_id'] === (int)$ph['id']) ? 'selected' : '' ?>>
                                        <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Thiết bị sẽ được kiểm tra độ tương thích với cấu hình hãng/model phòng đích.</div>
                        </div>
                    </div>
                </div>

                <!-- Thẻ 2: Lý do & Ghi chú -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle">2</span> Thông tin & Cam kết
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="ly_do" class="form-label fw-semibold">
                                Lý do điều chuyển <span class="text-danger">*</span>
                            </label>
                            <textarea id="ly_do" name="ly_do" class="form-control" rows="3" 
                                      placeholder="Ví dụ: Thay thế máy chiếu phòng B11.301 bị lỗi, bổ sung micro cho giảng viên..." 
                                      required><?= e($duLieu['ly_do']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="ghi_chu" class="form-label fw-semibold">Ghi chú bổ sung</label>
                            <textarea id="ghi_chu" name="ghi_chu" class="form-control" rows="2" 
                                      placeholder="Thông tin tình trạng hoặc lưu ý kèm theo..."><?= e($duLieu['ghi_chu']) ?></textarea>
                        </div>

                        <div class="form-check p-3 bg-light rounded border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="xac_nhan_tuong_thich" value="1" id="xac_nhan_tuong_thich" 
                                   <?= ($duLieu['xac_nhan_tuong_thich'] === '1') ? 'checked' : '' ?> required>
                            <label class="form-check-label small fw-semibold text-dark" for="xac_nhan_tuong_thich">
                                Tôi xác nhận đã kiểm tra thực tế tính tương thích của thiết bị với cấu hình phòng học đích. <span class="text-danger">*</span>
                            </label>
                        </div>

                        <div class="mt-4 d-grid">
                            <button type="submit" id="btnSubmitDieuChuyen" class="btn btn-primary btn-lg shadow-sm" <?= $dangKiemKe ? 'disabled' : '' ?>>
                                <i class="bi bi-check2-circle me-1"></i> Xác nhận điều chuyển (<span id="demThietBiNhan">0</span>)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CỘT PHẢI: QUÉT MÃ QR & DANH SÁCH THIẾT BỊ ĐIỀU CHUYỂN -->
            <div class="col-lg-7">
                <!-- Thẻ 3: Quét QR & Tra cứu nhanh -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <span class="badge bg-primary rounded-circle">3</span> Thêm thiết bị điều chuyển
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnMoModalChonPhong">
                            <i class="bi bi-list-check me-1"></i> Chọn nhanh từ phòng nguồn
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Ô Quét Camera QR -->
                        <?php 
                        $tieuDeQuet = 'Quét mã QR tem tài sản trên thiết bị';
                        $tenHamXuLy = 'xuLyMaQuetTaiSan';
                        require dirname(__DIR__) . '/partials/o-quet-qr.php'; 
                        ?>

                        <!-- Hoặc nhập mã thủ công -->
                        <div class="mt-3">
                            <label class="form-label small fw-semibold">Hoặc nhập mã tài sản / mã QR</label>
                            <div class="input-group">
                                <input type="text" id="inputMaTaiSan" class="form-control" placeholder="Nhập mã tài sản hoặc quét mã vạch...">
                                <button type="button" id="btnThemMaThuCong" class="btn btn-dark">
                                    <i class="bi bi-plus-lg"></i> Thêm vào phiếu
                                </button>
                            </div>
                            <div id="thongBaoLoiQuet" class="text-danger small mt-1 d-none fw-semibold"></div>
                        </div>
                    </div>
                </div>

                <!-- Thẻ 4: Danh sách thiết bị đã chọn -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-dark">
                            Danh sách thiết bị chuyển đi (<span id="tongSoDaChon" class="text-primary fw-bold">0</span>/30)
                        </h6>
                        <button type="button" id="btnXoaHet" class="btn btn-sm btn-outline-danger d-none">
                            <i class="bi bi-trash"></i> Xóa tất cả
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="bangThietBiChon">
                            <thead class="table-light text-secondary text-uppercase small">
                                <tr>
                                    <th style="width: 40px;" class="ps-3">#</th>
                                    <th style="width: 140px;">Mã TS</th>
                                    <th>Tên thiết bị</th>
                                    <th style="width: 160px;">Hãng / Model</th>
                                    <th style="width: 150px;">Tương thích</th>
                                    <th style="width: 60px;" class="text-end pe-3">Xóa</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyThietBiChon">
                                <tr id="dongTrong">
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-qr-code-scan fs-2 text-muted d-block mb-2"></i>
                                        Chưa có thiết bị nào được chọn. Hãy quét mã QR hoặc chọn từ phòng nguồn.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal Chọn Nhanh Thiết Bị Từ Phòng Nguồn -->
<div class="modal fade" id="modalChonNhanh" tabindex="-1" aria-labelledby="modalChonNhanhLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalChonNhanhLabel">
                    <i class="bi bi-laptop me-1 text-primary"></i> Thiết bị sẵn sàng tại phòng nguồn
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="taiDanhSachNguon" class="p-4 text-center text-muted">
                    <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                    Đang tải danh sách thiết bị...
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 d-none" id="bangDanhSachNguon">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-3" style="width: 40px;">
                                    <input type="checkbox" class="form-check-input" id="checkChonTatCa">
                                </th>
                                <th>Mã TS</th>
                                <th>Tên thiết bị</th>
                                <th>Hãng / Model</th>
                                <th>Tương thích phòng đến</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDanhSachNguon"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="btnXacNhanChonNhanh">
                    <i class="bi bi-check-lg me-1"></i> Thêm các thiết bị đã chọn
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Lưu trữ danh sách thiết bị đã chọn: map id => { id, ma_tai_san, ten_tai_san, hang, model, tuong_thich, ly_do }
    const dsDaChon = new Map();

    const selectPhongDi = document.getElementById('phong_hoc_di_id');
    const selectPhongDen = document.getElementById('phong_hoc_den_id');
    const tbodyChon = document.getElementById('tbodyThietBiChon');
    const dongTrong = document.getElementById('dongTrong');
    const tongSoDaChonEl = document.getElementById('tongSoDaChon');
    const demThietBiNhanEl = document.getElementById('demThietBiNhan');
    const btnXoaHet = document.getElementById('btnXoaHet');
    const thongBaoLoiQuet = document.getElementById('thongBaoLoiQuet');
    const inputMa = document.getElementById('inputMaTaiSan');
    const btnThemThuCong = document.getElementById('btnThemMaThuCong');

    // Modal chọn nhanh
    const modalEl = document.getElementById('modalChonNhanh');
    const bsModal = modalEl ? new bootstrap.Modal(modalEl) : null;
    const btnMoModal = document.getElementById('btnMoModalChonPhong');
    const taiDanhSachEl = document.getElementById('taiDanhSachNguon');
    const bangNguonEl = document.getElementById('bangDanhSachNguon');
    const tbodyNguonEl = document.getElementById('tbodyDanhSachNguon');
    const checkAll = document.getElementById('checkChonTatCa');
    const btnXacNhanChonNhanh = document.getElementById('btnXacNhanChonNhanh');

    let danhSachNguonCache = [];

    function capNhatGiaoDien() {
        const count = dsDaChon.size;
        tongSoDaChonEl.textContent = count;
        demThietBiNhanEl.textContent = count + ' thiết bị';

        if (count === 0) {
            tbodyChon.innerHTML = '';
            tbodyChon.appendChild(dongTrong);
            btnXoaHet.classList.add('d-none');
            return;
        }

        btnXoaHet.classList.remove('d-none');
        tbodyChon.innerHTML = '';

        let stt = 1;
        dsDaChon.forEach((item, id) => {
            const tr = document.createElement('tr');
            
            let badgeTuongThich = '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Tương thích</span>';
            if (item.tuong_thich === false) {
                badgeTuongThich = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="${item.ly_do || ''}"><i class="bi bi-exclamation-octagon me-1"></i>Không tương thích</span>`;
            }

            tr.innerHTML = `
                <td class="ps-3 text-muted small">${stt++}</td>
                <td>
                    <span class="font-monospace fw-bold text-primary">${escapeHtml(item.ma_tai_san)}</span>
                    <input type="hidden" name="tai_san_ids[]" value="${id}">
                </td>
                <td>
                    <div class="fw-semibold text-dark small">${escapeHtml(item.ten_tai_san)}</div>
                </td>
                <td class="small text-muted">
                    ${escapeHtml((item.hang_san_xuat || '') + (item.model ? ' / ' + item.model : '')) || '—'}
                </td>
                <td>${badgeTuongThich}</td>
                <td class="text-end pe-3">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-xoa-ts" data-id="${id}" title="Xóa">
                        <i class="bi bi-x"></i>
                    </button>
                </td>
            `;
            tbodyChon.appendChild(tr);
        });

        // Gán sự kiện xóa
        tbodyChon.querySelectorAll('.btn-xoa-ts').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = parseInt(this.getAttribute('data-id'), 10);
                dsDaChon.delete(id);
                capNhatGiaoDien();
            });
        });
    }

    function baoLoi(msg) {
        if (!msg) {
            thongBaoLoiQuet.classList.add('d-none');
            thongBaoLoiQuet.textContent = '';
        } else {
            thongBaoLoiQuet.classList.remove('d-none');
            thongBaoLoiQuet.textContent = msg;
            setTimeout(() => { thongBaoLoiQuet.classList.add('d-none'); }, 6000);
        }
    }

    // Tra cứu và nạp thiết bị theo mã QR hoặc mã tài sản
    async function napThietBi(maQr, maTaiSan) {
        baoLoi('');
        const phongDiId = selectPhongDi.value;
        const phongDenId = selectPhongDen.value;

        if (!phongDiId) {
            baoLoi('Vui lòng chọn Phòng học nguồn (phòng đi) trước khi quét thiết bị.');
            return;
        }

        if (dsDaChon.size >= 30) {
            baoLoi('Mỗi phiếu điều chuyển tối đa 30 thiết bị.');
            return;
        }

        let url = '<?= url("api/tai-san.php") ?>?';
        if (maQr) url += 'ma_qr=' + encodeURIComponent(maQr);
        else if (maTaiSan) url += 'ma_tai_san=' + encodeURIComponent(maTaiSan);
        if (phongDiId) url += '&phong_hoc_nguon_id=' + phongDiId;
        if (phongDenId) url += '&phong_hoc_id=' + phongDenId;

        try {
            const resp = await fetch(url);
            const data = await resp.json();

            if (!data.thanh_cong) {
                baoLoi(data.loi || 'Không tìm thấy thông tin thiết bị.');
                return;
            }

            const ts = data.du_lieu;
            if (dsDaChon.has(ts.id)) {
                baoLoi(`Thiết bị [${ts.ma_tai_san}] đã có trong danh sách.`);
                return;
            }

            if (!data.co_the_dieu_chuyen) {
                baoLoi(`Thiết bị [${ts.ma_tai_san}] không thể chuyển: ${data.ly_do_khong_the_chuyen || 'Không đủ điều kiện'}`);
                return;
            }

            let tuongThich = true;
            let lyDoTuongThich = '';
            if (data.kiem_tra_tuong_thich) {
                tuongThich = data.kiem_tra_tuong_thich.tuong_thich;
                lyDoTuongThich = data.kiem_tra_tuong_thich.ly_do;
            }

            if (!tuongThich) {
                if (!confirm(`Cảnh báo tương thích: Thiết bị [${ts.ma_tai_san}] ${ts.ten_tai_san} được hệ thống đánh giá KHÔNG tương thích với phòng đích (${lyDoTuongThich}). Bạn vẫn muốn thêm?`)) {
                    return;
                }
            }

            dsDaChon.set(ts.id, {
                id: ts.id,
                ma_tai_san: ts.ma_tai_san,
                ten_tai_san: ts.ten_tai_san,
                hang_san_xuat: ts.hang_san_xuat,
                model: ts.model,
                tuong_thich: tuongThich,
                ly_do: lyDoTuongThich,
            });

            capNhatGiaoDien();
        } catch (err) {
            baoLoi('Lỗi kết nối máy chủ tra cứu tài sản.');
        }
    }

    // Hàm toàn cục cho camera quét QR
    window.xuLyMaQuetTaiSan = function (ma) {
        if (ma) napThietBi(ma, null);
    };

    // Nhập thủ công
    btnThemThuCong.addEventListener('click', function () {
        const val = inputMa.value.trim();
        if (val) {
            napThietBi(null, val);
            inputMa.value = '';
        }
    });
    inputMa.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btnThemThuCong.click();
        }
    });

    // Xóa hết
    btnXoaHet.addEventListener('click', function () {
        if (confirm('Bạn có chắc muốn xóa tất cả thiết bị khỏi danh sách?')) {
            dsDaChon.clear();
            capNhatGiaoDien();
        }
    });

    // Mở modal chọn nhanh từ phòng nguồn
    btnMoModal.addEventListener('click', async function () {
        const phongDiId = selectPhongDi.value;
        const phongDenId = selectPhongDen.value;

        if (!phongDiId) {
            alert('Vui lòng chọn Phòng học nguồn (phòng đi) trước!');
            selectPhongDi.focus();
            return;
        }

        taiDanhSachEl.classList.remove('d-none');
        bangNguonEl.classList.add('d-none');
        tbodyNguonEl.innerHTML = '';
        checkAll.checked = false;
        bsModal.show();

        try {
            const resp = await fetch(`<?= url("api/tai-san-theo-phong.php") ?>?phong_hoc_di_id=${phongDiId}&phong_hoc_den_id=${phongDenId}`);
            const data = await resp.json();

            taiDanhSachEl.classList.add('d-none');
            bangNguonEl.classList.remove('d-none');

            if (!data.thanh_cong || !data.danh_sach || data.danh_sach.length === 0) {
                tbodyNguonEl.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Không có thiết bị sẵn sàng điều chuyển tại phòng học này.</td></tr>`;
                return;
            }

            danhSachNguonCache = data.danh_sach;

            data.danh_sach.forEach(ts => {
                const tr = document.createElement('tr');
                const daChonRoi = dsDaChon.has(ts.id);
                
                let badge = '<span class="badge bg-success-subtle text-success">Tương thích</span>';
                if (ts.tuong_thich === false) {
                    badge = `<span class="badge bg-danger-subtle text-danger" title="${ts.ly_do_tuong_thich}">Không tương thích</span>`;
                }

                tr.innerHTML = `
                    <td class="ps-3">
                        <input type="checkbox" class="form-check-input check-item-nguon" value="${ts.id}" ${daChonRoi ? 'checked disabled' : ''}>
                    </td>
                    <td class="font-monospace fw-bold text-primary">${escapeHtml(ts.ma_tai_san)}</td>
                    <td class="small fw-semibold">${escapeHtml(ts.ten_tai_san)}</td>
                    <td class="small text-muted">${escapeHtml((ts.hang_san_xuat || '') + (ts.model ? ' / ' + ts.model : '')) || '—'}</td>
                    <td>${badge}</td>
                `;
                tbodyNguonEl.appendChild(tr);
            });
        } catch (e) {
            taiDanhSachEl.innerHTML = '<span class="text-danger">Lỗi tải danh sách thiết bị.</span>';
        }
    });

    // Check all checkbox
    checkAll.addEventListener('change', function () {
        const checks = tbodyNguonEl.querySelectorAll('.check-item-nguon:not(:disabled)');
        checks.forEach(c => c.checked = checkAll.checked);
    });

    // Xác nhận chọn nhanh từ modal
    btnXacNhanChonNhanh.addEventListener('click', function () {
        const checks = tbodyNguonEl.querySelectorAll('.check-item-nguon:checked:not(:disabled)');
        checks.forEach(c => {
            const id = parseInt(c.value, 10);
            const ts = danhSachNguonCache.find(x => x.id === id);
            if (ts && !dsDaChon.has(id)) {
                dsDaChon.set(id, {
                    id: ts.id,
                    ma_tai_san: ts.ma_tai_san,
                    ten_tai_san: ts.ten_tai_san,
                    hang_san_xuat: ts.hang_san_xuat,
                    model: ts.model,
                    tuong_thich: ts.tuong_thich !== false,
                    ly_do: ts.ly_do_tuong_thich || '',
                });
            }
        });
        capNhatGiaoDien();
        bsModal.hide();
    });

    // Khi đổi phòng đi, reset danh sách đã chọn nếu xác nhận
    selectPhongDi.addEventListener('change', function () {
        if (dsDaChon.size > 0) {
            if (confirm('Thay đổi phòng học nguồn sẽ làm mới danh sách thiết bị đã chọn. Bạn có chắc không?')) {
                dsDaChon.clear();
                capNhatGiaoDien();
            }
        }
    });

    // Form submit validation
    document.getElementById('formDieuChuyen').addEventListener('submit', function (e) {
        if (dsDaChon.size === 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất 1 thiết bị để điều chuyển.');
            return false;
        }
        if (selectPhongDi.value === selectPhongDen.value) {
            e.preventDefault();
            alert('Phòng học nguồn và phòng học đích phải khác nhau.');
            return false;
        }
        return true;
    });

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
