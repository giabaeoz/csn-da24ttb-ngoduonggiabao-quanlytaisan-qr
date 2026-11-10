<?php
/**
 * Giao diện: Báo cáo sự cố thiết bị mới - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var string $loi
 * @var array<string, mixed>|null $taiSanChon
 * @var array<string, mixed> $duLieu
 * @var list<array<string, mixed>> $danhSachTaiSan
 */

$tieuDeTrang = 'Báo cáo sự cố thiết bị';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang mb-0">Báo cáo sự cố thiết bị</h1>
        <div class="mo-ta-phu">Ghi nhận thông tin hư hỏng, mất mát hoặc sai vị trí để tiến hành kiểm tra, sửa chữa hoặc bảo trì.</div>
    </div>
    <div>
        <a href="<?= url('su-co/index.php') ?>" class="btn btn-phu">
            <?= icon('arrow-left', 'bieu-tuong-sm') ?>
            <span>Quay lại danh sách</span>
        </a>
    </div>
</div>

<?php if ($loi !== ''): ?>
    <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
        <?= icon('alert-circle', 'bieu-tuong-md') ?>
        <div class="flex-grow-1"><strong>Lỗi thao tác:</strong> <?= e($loi) ?></div>
    </div>
<?php endif; ?>

<div class="khung-bieu-mau">
    <div class="the-noi-dung mb-4 p-0">
        <div class="the-noi-dung-dau">
            <h2 class="tieu-de-khoi mb-0">Thông tin thiết bị & mô tả sự cố</h2>
        </div>
        <div class="the-noi-dung-than p-4">
                <form action="<?= url('su-co/bao-cao.php') ?>" method="POST" id="formBaoCaoSuCo">
                    <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                    <input type="hidden" name="tai_san_id" id="inputTaiSanId" value="<?= (int)$duLieu['tai_san_id'] ?>">

                    <div class="mb-3">
                        <label class="nhan-truong">Thiết bị gặp sự cố <span class="text-danger">*</span></label>
                        
                        <div id="theThietBiChon" class="<?= $taiSanChon ? '' : 'd-none' ?> p-3 bg-light rounded border mb-2">
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
                                <button type="button" class="btn btn-sm btn-phu text-danger" onclick="huyChonThietBi()">
                                    Đổi thiết bị
                                </button>
                            </div>
                        </div>

                        <div id="khungChonThietBi" class="<?= $taiSanChon ? 'd-none' : '' ?>">
                            <div class="d-flex gap-2 mb-2">
                                <input type="text" id="inputTraCuuNhanh" class="o-nhap font-monospace" 
                                       placeholder="Nhập mã tài sản hoặc quét mã QR tem để chọn...">
                                <button type="button" class="btn btn-phu" onclick="traCuuThietBiNhanh()">
                                    Tra cứu
                                </button>
                            </div>

                            <div class="mb-2">
                                <label class="mo-ta-phu mb-1">Hoặc chọn nhanh từ danh mục thiết bị của bàn trực:</label>
                                <select class="o-nhap" id="selectTaiSan" onchange="chonTaiSanTuSelect(this)">
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

                    <div class="mb-4">
                        <label class="nhan-truong">Phân loại sự cố <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <div class="p-3 border rounded h-100 <?= ($duLieu['loai_su_co'] === 'HU_HONG') ? 'bg-danger bg-opacity-10 border-danger' : 'bg-light' ?>">
                                    <input class="form-check-input" type="radio" name="loai_su_co" id="loaiHuHong" 
                                           value="HU_HONG" <?= ($duLieu['loai_su_co'] === 'HU_HONG') ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold d-block text-danger mt-1" for="loaiHuHong">
                                        Hư hỏng / Lỗi
                                    </label>
                                    
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded h-100 <?= ($duLieu['loai_su_co'] === 'MAT') ? 'bg-danger bg-opacity-10 border-danger' : 'bg-light' ?>">
                                    <input class="form-check-input" type="radio" name="loai_su_co" id="loaiMat" 
                                           value="MAT" <?= ($duLieu['loai_su_co'] === 'MAT') ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold d-block text-dark mt-1" for="loaiMat">
                                        Mất / Thất lạc
                                    </label>
                                    
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded h-100 <?= ($duLieu['loai_su_co'] === 'SAI_VI_TRI') ? 'bg-danger bg-opacity-10 border-danger' : 'bg-light' ?>">
                                    <input class="form-check-input" type="radio" name="loai_su_co" id="loaiSaiViTri" 
                                           value="SAI_VI_TRI" <?= ($duLieu['loai_su_co'] === 'SAI_VI_TRI') ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold d-block text-warning-emphasis mt-1" for="loaiSaiViTri">
                                        Sai vị trí phòng
                                    </label>
                                    
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="nhan-truong">Mô tả hiện trạng sự cố <span class="text-danger">*</span></label>
                        <textarea name="mo_ta" id="txtMoTa" class="o-nhap" rows="4" required maxlength="2000"
                                  placeholder="Mô tả cụ thể biểu hiện lỗi, nguyên nhân nghi ngờ hoặc tình trạng thực tế phát hiện..."><?= e($duLieu['mo_ta']) ?></textarea>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('su-co/index.php') ?>" class="btn btn-phu">
                            <?= icon('x', 'bieu-tuong-sm') ?>
                            <span>Hủy bỏ</span>
                        </a>
                        <button type="submit" class="btn btn-chinh" id="btnLuuBaoCao">
                            <?= icon('save', 'bieu-tuong-sm') ?>
                            <span>Lưu báo cáo sự cố</span>
                        </button>
                    </div>
                </form>
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
    const kw = document.getElementById('inputTraCuuNhanh').value.trim();
    const tb = document.getElementById('thongBaoTraCuu');
    if (!kw) return;

    tb.className = 'small fw-semibold text-secondary mt-1';
    tb.textContent = 'Đang tìm kiếm...';
    tb.classList.remove('d-none');

    const select = document.getElementById('selectTaiSan');
    let timThay = false;

    for (let i = 0; i < select.options.length; i++) {
        const opt = select.options[i];
        if (!opt.value) continue;
        const ma = (opt.dataset.ma || '').toUpperCase();
        const ten = (opt.dataset.ten || '').toUpperCase();
        const upperKw = kw.toUpperCase();

        if (ma === upperKw || ma.includes(upperKw) || ten.includes(upperKw)) {
            select.selectedIndex = i;
            chonTaiSanTuSelect(select);
            tb.className = 'small fw-semibold text-success mt-1';
            tb.textContent = 'Đã tìm thấy thiết bị: ' + opt.dataset.ma;
            timThay = true;
            break;
        }
    }

    if (!timThay) {
        tb.className = 'small fw-semibold text-danger mt-1';
        tb.textContent = 'Không tìm thấy thiết bị khớp với mã: ' + kw;
    }
}
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
