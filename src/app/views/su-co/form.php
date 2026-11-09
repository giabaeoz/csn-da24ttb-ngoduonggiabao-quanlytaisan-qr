<?php
/**
 * Giao diện: Báo cáo sự cố thiết bị mới (thủ công / đột xuất) (G9).
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
                    <li class="breadcrumb-item"><a href="<?= url('su-co/index.php') ?>" class="text-decoration-none">Sự cố & Bảo trì</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Báo cáo mới</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold d-flex align-items-center gap-2">
                <span>⚠️ Báo cáo sự cố thiết bị</span>
            </h1>
            <p class="text-muted small mb-0">Ghi nhận thông tin hư hỏng, mất mát hoặc sai vị trí để tiến hành kiểm tra, sửa chữa hoặc bảo trì.</p>
        </div>
        <div class="col-md-4 text-md-end mt-2 mt-md-0">
            <a href="<?= url('su-co/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
    </div>

    <?php if ($loi !== ''): ?>
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><?= e($loi) ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- CỘT TRÁI: FORM BÁO CÁO SỰ CỐ -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-danger d-flex align-items-center gap-2">
                        <i class="bi bi-pencil-square"></i> Thông tin thiết bị & Mô tả sự cố
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="<?= url('su-co/bao-cao.php') ?>" method="POST" id="formBaoCaoSuCo">
                        <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                        <input type="hidden" name="tai_san_id" id="inputTaiSanId" value="<?= (int)$duLieu['tai_san_id'] ?>">

                        <!-- Khung chọn thiết bị gặp sự cố -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Thiết bị gặp sự cố <span class="text-danger">*</span></label>
                            
                            <!-- Thẻ hiển thị thiết bị đã chọn -->
                            <div id="theThietBiChon" class="<?= $taiSanChon ? '' : 'd-none' ?> p-3 bg-light rounded-3 border mb-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold font-monospace text-primary fs-6" id="viewMaTaiSan">
                                            <?= e($taiSanChon['ma_tai_san'] ?? '') ?>
                                        </div>
                                        <div class="fw-semibold text-dark fs-6 mt-1" id="viewTenTaiSan">
                                            <?= e($taiSanChon['ten_tai_san'] ?? '') ?>
                                        </div>
                                        <div class="small text-muted mt-1" id="viewMoTaPhu">
                                            <?php if ($taiSanChon): ?>
                                                Bàn trực: <strong><?= e($taiSanChon['ten_phong_truc'] ?? '') ?></strong> &bull;
                                                Vị trí: <strong><?= e($taiSanChon['ma_phong'] ?? 'Kho trực') ?></strong>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="huyChonThietBi()">
                                        <i class="bi bi-x"></i> Đổi thiết bị
                                    </button>
                                </div>
                            </div>

                            <!-- Hoặc chọn từ danh sách dropdown hoặc quét QR -->
                            <div id="khungChonThietBi" class="<?= $taiSanChon ? 'd-none' : '' ?>">
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                    <input type="text" id="inputTraCuuNhanh" class="form-control font-monospace" 
                                           placeholder="Nhập mã tài sản hoặc quét mã QR tem để chọn...">
                                    <button type="button" class="btn btn-outline-primary" onclick="traCuuThietBiNhanh()">
                                        Tra cứu
                                    </button>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small text-muted">Hoặc chọn nhanh từ danh mục thiết bị của bàn trực:</label>
                                    <select class="form-select" id="selectTaiSan" onchange="chonTaiSanTuSelect(this)">
                                        <option value="">-- Chọn thiết bị từ danh sách --</option>
                                        <?php foreach ($danhSachTaiSan as $ts): ?>
                                            <option value="<?= (int)$ts['id'] ?>" 
                                                    data-ma="<?= e($ts['ma_tai_san']) ?>" 
                                                    data-ten="<?= e($ts['ten_tai_san']) ?>"
                                                    data-vitri="<?= e($ts['ma_phong'] ?? 'Kho trực') ?>"
                                                    data-phongtruc="<?= e($ts['ten_phong_truc'] ?? '') ?>"
                                                    <?= ((int)$duLieu['tai_san_id'] === (int)$ts['id']) ? 'selected' : '' ?>>
                                                <?= e($ts['ma_tai_san']) ?> - <?= e($ts['ten_tai_san']) ?> (<?= e($ts['ten_loai_tai_san'] ?? '') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="thongBaoTraCuu" class="small fw-semibold d-none"></div>
                            </div>
                        </div>

                        <!-- Loại sự cố -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Phân loại sự cố <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded h-100 <?= ($duLieu['loai_su_co'] === 'HU_HONG') ? 'border-danger bg-danger bg-opacity-10' : '' ?>">
                                        <input class="form-check-input" type="radio" name="loai_su_co" id="loaiHuHong" 
                                               value="HU_HONG" <?= ($duLieu['loai_su_co'] === 'HU_HONG') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold d-block text-danger" for="loaiHuHong">
                                            🛠️ Hư hỏng / Lỗi
                                        </label>
                                        <small class="text-muted d-block mt-1">Hỏng hóc linh kiện, bể vỡ, không hoạt động. Tự chuyển sang <code>BẢO TRÌ</code>.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded h-100 <?= ($duLieu['loai_su_co'] === 'MAT') ? 'border-danger bg-danger bg-opacity-10' : '' ?>">
                                        <input class="form-check-input" type="radio" name="loai_su_co" id="loaiMat" 
                                               value="MAT" <?= ($duLieu['loai_su_co'] === 'MAT') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold d-block text-dark" for="loaiMat">
                                            🔍 Mất / Thất lạc
                                        </label>
                                        <small class="text-muted d-block mt-1">Không tìm thấy thiết bị tại vị trí phân công. Tự chuyển sang <code>NGỪNG SỬ DỤNG</code>.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded h-100 <?= ($duLieu['loai_su_co'] === 'SAI_VI_TRI') ? 'border-danger bg-danger bg-opacity-10' : '' ?>">
                                        <input class="form-check-input" type="radio" name="loai_su_co" id="loaiSaiViTri" 
                                               value="SAI_VI_TRI" <?= ($duLieu['loai_su_co'] === 'SAI_VI_TRI') ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold d-block text-warning-emphasis" for="loaiSaiViTri">
                                            🔄 Sai vị trí phòng
                                        </label>
                                        <small class="text-muted d-block mt-1">Thiết bị bị di dời sang phòng khác chưa điều chuyển. Tạm chuyển <code>NGỪNG SỬ DỤNG</code>.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mô tả chi tiết sự cố -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Mô tả hiện trạng sự cố <span class="text-danger">*</span></label>
                            <textarea name="mo_ta" id="txtMoTa" class="form-control" rows="4" required maxlength="2000"
                                      placeholder="Mô tả cụ thể biểu hiện lỗi, nguyên nhân nghi ngờ hoặc tình trạng thực tế phát hiện (ví dụ: máy chiếu bị nhòe màu, cáp HDMI đứt đầu cắm, thất lạc remote sau ca học...)..."><?= e($duLieu['mo_ta']) ?></textarea>
                            <div class="form-text">Bắt buộc nhập mô tả, tối đa 2000 ký tự.</div>
                        </div>

                        <!-- Nút lưu báo cáo -->
                        <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                            <a href="<?= url('su-co/index.php') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Hủy bỏ
                            </a>
                            <button type="submit" class="btn btn-danger px-4 fw-semibold shadow-sm" id="btnLuuBaoCao">
                                <i class="bi bi-check2-circle me-1"></i> Lưu báo cáo sự cố
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI: QUY TRÌNH TIẾP NHẬN & XỬ LÝ SỰ CỐ -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-info"></i> Quy trình vòng đời xử lý sự cố
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-3 mb-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <strong>1</strong>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Ghi nhận sự cố & Tự động khóa thiết bị</h6>
                            <p class="text-muted small mb-0">Khi lưu báo cáo sự cố, thiết bị sẽ tự động được chuyển sang trạng thái <code>BẢO TRÌ</code> hoặc <code>NGỪNG SỬ DỤNG</code> để tránh cho mượn nhầm.</p>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <strong>2</strong>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Tiếp nhận xử lý (Đang xử lý)</h6>
                            <p class="text-muted small mb-0">Cán bộ quản lý hoặc kỹ thuật viên tiếp nhận, đưa thiết bị đi sửa chữa, bảo hành chính hãng hoặc rà soát tìm kiếm thiết bị thất lạc.</p>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="rounded-circle bg-success bg-opacity-10 text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <strong>3</strong>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Hoàn tất xử lý & Khôi phục sử dụng</h6>
                            <p class="text-muted small mb-0">Ghi nhận chi tiết kết quả xử lý (đã thay thế linh kiện, đã sửa xong, hoặc bãi bỏ thanh lý). Thiết bị được khôi phục sang trạng thái <code>SẴN SÀNG</code>.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nguồn phát hiện sự cố -->
            <div class="alert alert-light border shadow-sm">
                <h6 class="fw-bold mb-2 d-flex align-items-center gap-2">
                    <i class="bi bi-link-45deg text-primary fs-5"></i> Các nguồn ghi nhận sự cố trong hệ thống
                </h6>
                <ul class="list-unstyled mb-0 small text-muted">
                    <li class="mb-1">&bull; <strong>Báo cáo trực tiếp (Form này):</strong> Phát hiện đột xuất tại phòng học hoặc phòng trực.</li>
                    <li class="mb-1">&bull; <strong>Qua tiếp nhận trả mượn (G6):</strong> Phát hiện thiết bị hư hỏng khi người mượn bàn giao lại.</li>
                    <li>&bull; <strong>Qua đợt kiểm kê (G8):</strong> Tự động sinh khi đối chiếu phát hiện thiết bị Mất, Hỏng mới hoặc Sai phòng học.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function chonTaiSanTuSelect(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;

    hienThiTheThietBi(opt.value, opt.dataset.ma, opt.dataset.ten, opt.dataset.vitri, opt.dataset.phongtruc);
}

function hienThiTheThietBi(id, ma, ten, vitri, phongtruc) {
    document.getElementById('inputTaiSanId').value = id;
    document.getElementById('viewMaTaiSan').textContent = ma;
    document.getElementById('viewTenTaiSan').textContent = ten;
    document.getElementById('viewMoTaPhu').innerHTML = 'Bàn trực: <strong>' + (phongtruc || 'Kho trực') + '</strong> &bull; Vị trí: <strong>' + (vitri || 'Kho trực') + '</strong>';

    document.getElementById('theThietBiChon').classList.remove('d-none');
    document.getElementById('khungChonThietBi').classList.add('d-none');
}

function huyChonThietBi() {
    document.getElementById('inputTaiSanId').value = '';
    document.getElementById('theThietBiChon').classList.add('d-none');
    document.getElementById('khungChonThietBi').classList.remove('d-none');
    document.getElementById('selectTaiSan').value = '';
}

function traCuuThietBiNhanh() {
    const input = document.getElementById('inputTraCuuNhanh');
    const ma = input.value.trim();
    const tb = document.getElementById('thongBaoTraCuu');
    if (ma === '') {
        alert('Vui lòng nhập mã tài sản hoặc mã QR để tra cứu.');
        input.focus();
        return;
    }

    tb.className = 'small fw-semibold text-primary';
    tb.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i> Đang tra cứu thiết bị...';
    tb.classList.remove('d-none');

    fetch('<?= url("api/tra-cuu-qr.php") ?>?ma_qr=' + encodeURIComponent(ma) + '&ma_tai_san=' + encodeURIComponent(ma))
        .then(res => res.json())
        .then(data => {
            if (data.thanh_cong && data.du_lieu) {
                const ts = data.du_lieu;
                tb.className = 'small fw-semibold text-success';
                tb.innerHTML = '<i class="bi bi-check-circle me-1"></i> Đã tìm thấy thiết bị: ' + ts.ten_tai_san;
                hienThiTheThietBi(ts.id, ts.ma_tai_san, ts.ten_tai_san, ts.ma_phong || 'Kho trực', ts.ten_phong_truc || '');
            } else {
                tb.className = 'small fw-semibold text-danger';
                tb.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> ' + (data.thong_bao || data.loi || 'Không tìm thấy thiết bị trong hệ thống.');
            }
        })
        .catch(err => {
            console.error(err);
            tb.className = 'small fw-semibold text-danger';
            tb.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Lỗi kết nối đến máy chủ tra cứu.';
        });
}

// Nhấn Enter ở ô nhập mã
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('inputTraCuuNhanh');
    if (input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                traCuuThietBiNhanh();
            }
        });
    }

    // Kiểm tra trước khi submit
    const form = document.getElementById('formBaoCaoSuCo');
    if (form) {
        form.addEventListener('submit', function(e) {
            const tsId = document.getElementById('inputTaiSanId').value;
            if (!tsId || parseInt(tsId) <= 0) {
                e.preventDefault();
                alert('Vui lòng chọn hoặc tra cứu thiết bị gặp sự cố trước khi gửi báo cáo.');
            }
        });
    }
});
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>

