<?php
/**
 * Giao diện Biểu mẫu Thêm mới / Chỉnh sửa Tài sản.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">
            <?= $laThemMoi ? '➕ Thêm thiết bị mới' : '✏️ Chỉnh sửa thông tin thiết bị' ?>
        </h1>
        <p class="text-secondary small mb-0">
            <?= $laThemMoi ? 'Đăng ký tài sản vào hệ thống, thiết lập độ tương thích và sinh mã QR tự động.' : 'Cập nhật thông tin kỹ thuật, nhãn vị trí kệ và phạm vi tương thích.' ?>
        </p>
    </div>
    <div>
        <a href="<?= url('tai-san/index.php') ?>" class="btn btn-outline-secondary fw-semibold">
            ← Quay lại danh sách
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-xl-10">

        <?php if (!empty($thongBaoLoi)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
            <strong>Lỗi thao tác:</strong> <?= e($thongBaoLoi) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <form action="<?= $laThemMoi ? url('tai-san/them.php') : url('tai-san/sua.php?id=' . (int)$taiSan['id']) ?>" method="POST" class="card border-0 shadow-sm rounded-4">
            <?= csrf_field() ?>

            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0 text-dark">Thông tin cơ bản & Định danh thiết bị</h5>
            </div>

            <div class="card-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="ma_tai_san" class="form-label fw-semibold small text-secondary">
                            Mã tài sản <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control text-uppercase font-monospace fw-bold" id="ma_tai_san" name="ma_tai_san" 
                               value="<?= e($taiSan['ma_tai_san'] ?? '') ?>" 
                               placeholder="Ví dụ: MC-0001, RM-0001" required autofocus>
                        <div class="form-text small">Mã định danh viết tắt theo chủng loại.</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="ma_qr" class="form-label fw-semibold small text-secondary">
                            Mã QR định danh
                        </label>
                        <input type="text" class="form-control font-monospace" id="ma_qr" name="ma_qr" 
                               value="<?= e($taiSan['ma_qr'] ?? '') ?>" 
                               placeholder="Tự động sinh (Ví dụ: QR-D6-MC0001)">
                        <div class="form-text small mb-2">Để trống để hệ thống tự động sinh theo mẫu chuẩn.</div>

                        <!-- Khung xem trước mã QR tự động -->
                        <div class="p-2 border rounded bg-light d-flex align-items-center gap-3">
                            <div id="preview_qr_box" class="bg-white p-1 border rounded flex-shrink-0" style="width: 84px; height: 84px; display: flex; align-items: center; justify-content: center;">
                                <span class="text-muted small" style="font-size: 10px; text-align: center;">Chưa có QR</span>
                            </div>
                            <div class="small overflow-hidden">
                                <div class="fw-semibold text-secondary" style="font-size: 11px;">Xem trước tem mã QR:</div>
                                <div id="preview_qr_val" class="font-monospace text-primary fw-bold" style="word-break: break-all; font-size: 12px;">---</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="loai_tai_san_id" class="form-label fw-semibold small text-secondary">
                            Chủng loại thiết bị <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="loai_tai_san_id" name="loai_tai_san_id" required>
                            <option value="">-- Chọn chủng loại --</option>
                            <?php foreach ($danhSachLoai as $loai): ?>
                            <option value="<?= (int)$loai['id'] ?>" <?= ((int)($taiSan['loai_tai_san_id'] ?? 0) === (int)$loai['id']) ? 'selected' : '' ?>>
                                <?= e($loai['ten_loai']) ?> (<?= e($loai['ma_loai']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-8">
                        <label for="ten_tai_san" class="form-label fw-semibold small text-secondary">
                            Tên thiết bị <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="ten_tai_san" name="ten_tai_san" 
                               value="<?= e($taiSan['ten_tai_san'] ?? '') ?>" 
                               placeholder="Ví dụ: Máy chiếu Epson EB-X06, Remote máy lạnh Daikin" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="phong_truc_id" class="form-label fw-semibold small text-secondary">
                            Phòng trực quản lý <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="phong_truc_id" name="phong_truc_id" required>
                            <option value="">-- Chọn bàn trực --</option>
                            <?php foreach ($danhSachPhongTruc as $pt): ?>
                            <option value="<?= (int)$pt['id'] ?>" <?= ((int)($taiSan['phong_truc_id'] ?? 0) === (int)$pt['id']) ? 'selected' : '' ?>>
                                <?= e($pt['ten_phong_truc']) ?> (<?= e($pt['ma_phong_truc']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- KHU VỰC THÔNG SỐ HÃNG, MODEL & ĐỘ TƯƠNG THÍCH -->
                <div class="card border rounded-3 p-3 bg-light mb-3">
                    <h6 class="fw-bold text-primary mb-1">⚙️ Thông số Kỹ thuật & Độ tương thích</h6>
                    <p class="small text-muted mb-3">
                        Hệ thống đối chiếu Hãng/Model thiết bị với cấu hình cố định trong phòng học khi nhân viên quét mã.
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="hang_san_xuat" class="form-label fw-semibold small text-secondary">
                                Hãng sản xuất
                            </label>
                            <input type="text" class="form-control" id="hang_san_xuat" name="hang_san_xuat" 
                                   value="<?= e($taiSan['hang_san_xuat'] ?? '') ?>" 
                                   placeholder="Ví dụ: Daikin, Panasonic, Sony, Epson, Soundking, Shure">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="model" class="form-label fw-semibold small text-secondary">
                                Model kỹ thuật
                            </label>
                            <input type="text" class="form-control" id="model" name="model" 
                                   value="<?= e($taiSan['model'] ?? '') ?>" 
                                   placeholder="Ví dụ: FTKB25W, PT-LB386, EB-X06, SK-12">
                        </div>
                    </div>

                    <div class="row g-3 mb-2">
                        <div class="col-12 col-md-6">
                            <label for="pham_vi_tuong_thich" class="form-label fw-semibold small text-secondary">
                                Phạm vi tương thích phòng học <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="pham_vi_tuong_thich" name="pham_vi_tuong_thich" onchange="doiPhamViTuongThich(this.value)">
                                <option value="LINH_HOAT" <?= ($taiSan['pham_vi_tuong_thich'] ?? '') === 'LINH_HOAT' ? 'selected' : '' ?>>
                                    Linh hoạt (Dùng chung cho mọi phòng học)
                                </option>
                                <option value="THEO_THIET_BI" <?= ($taiSan['pham_vi_tuong_thich'] ?? '') === 'THEO_THIET_BI' ? 'selected' : '' ?>>
                                    Theo Hãng/Model thiết bị phòng (Khớp Daikin, Sony...)
                                </option>
                                <option value="THEO_PHONG" <?= ($taiSan['pham_vi_tuong_thich'] ?? '') === 'THEO_PHONG' ? 'selected' : '' ?>>
                                    Theo danh sách phòng chỉ định
                                </option>
                            </select>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="phong_hoc_phu_hop_id" class="form-label fw-semibold small text-secondary">
                                Nhãn phòng học gợi ý dán trên tem
                            </label>
                            <select class="form-select" id="phong_hoc_phu_hop_id" name="phong_hoc_phu_hop_id">
                                <option value="">-- Không gán nhãn cụ thể (Linh hoạt) --</option>
                                <?php foreach ($danhSachPhongHoc as $ph): ?>
                                <option value="<?= (int)$ph['id'] ?>" <?= ((int)($taiSan['phong_hoc_phu_hop_id'] ?? 0) === (int)$ph['id']) ? 'selected' : '' ?>>
                                    <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">Chỉ nhằm mục đích sắp xếp vị trí ngăn kệ trên giá tủ để lấy nhanh, không khóa cứng phòng.</div>
                        </div>
                    </div>

                    <!-- Danh sách phòng tương thích nếu chọn THEO_PHONG -->
                    <div id="khuVucTheoPhong" class="mt-2 <?= ($taiSan['pham_vi_tuong_thich'] ?? '') === 'THEO_PHONG' ? '' : 'd-none' ?>">
                        <label class="form-label fw-semibold small text-secondary">
                            Danh sách mã phòng học được phép mượn (phân cách bằng dấu phẩy)
                        </label>
                        <?php
                            $cacPhongStr = '';
                            if (!empty($taiSan['phong_tuong_thich'])) {
                                $arr = is_string($taiSan['phong_tuong_thich']) ? json_decode($taiSan['phong_tuong_thich'], true) : $taiSan['phong_tuong_thich'];
                                if (is_array($arr)) {
                                    $cacPhongStr = implode(', ', $arr);
                                }
                            }
                        ?>
                        <input type="text" class="form-control" name="phong_tuong_thich" value="<?= e($cacPhongStr) ?>" placeholder="Ví dụ: B31.101, B31.102, B71.101">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="ngay_dua_vao_su_dung" class="form-label fw-semibold small text-secondary">
                            Ngày đưa vào sử dụng
                        </label>
                        <input type="date" class="form-control" id="ngay_dua_vao_su_dung" name="ngay_dua_vao_su_dung" 
                               value="<?= e($taiSan['ngay_dua_vao_su_dung'] ?? date('Y-m-d')) ?>">
                    </div>

                    <?php if (!$laThemMoi): ?>
                    <div class="col-12 col-md-4">
                        <label for="tinh_trang" class="form-label fw-semibold small text-secondary">Tình trạng vật lý</label>
                        <select class="form-select" id="tinh_trang" name="tinh_trang">
                            <option value="TOT" <?= ($taiSan['tinh_trang'] ?? '') === 'TOT' ? 'selected' : '' ?>>Tốt</option>
                            <option value="HU_HONG" <?= ($taiSan['tinh_trang'] ?? '') === 'HU_HONG' ? 'selected' : '' ?>>Hư hỏng</option>
                            <option value="MAT" <?= ($taiSan['tinh_trang'] ?? '') === 'MAT' ? 'selected' : '' ?>>Mất</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="trang_thai" class="form-label fw-semibold small text-secondary">Trạng thái vận hành</label>
                        <select class="form-select" id="trang_thai" name="trang_thai">
                            <option value="SAN_SANG" <?= ($taiSan['trang_thai'] ?? '') === 'SAN_SANG' ? 'selected' : '' ?>>Sẵn sàng</option>
                            <option value="DANG_MUON" <?= ($taiSan['trang_thai'] ?? '') === 'DANG_MUON' ? 'selected' : '' ?> disabled>Đang mượn (Hệ thống khóa)</option>
                            <option value="BAO_TRI" <?= ($taiSan['trang_thai'] ?? '') === 'BAO_TRI' ? 'selected' : '' ?>>Bảo trì</option>
                            <option value="NGUNG_SU_DUNG" <?= ($taiSan['trang_thai'] ?? '') === 'NGUNG_SU_DUNG' ? 'selected' : '' ?>>Ngừng sử dụng</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="mo_ta" class="form-label fw-semibold small text-secondary">Mô tả đặc điểm / ghi chú</label>
                    <textarea class="form-control" id="mo_ta" name="mo_ta" rows="2" placeholder="Ví dụ: Đi kèm cáp nguồn 1.5m, đựng trong túi chống sốc đen"><?= e($taiSan['mo_ta'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="<?= url('tai-san/index.php') ?>" class="btn btn-outline-secondary fw-semibold px-4">
                        Hủy bỏ
                    </a>
                    <button type="submit" class="btn btn-primary fw-semibold px-5 shadow-sm">
                        <?= $laThemMoi ? 'Lưu thiết bị' : 'Cập nhật thay đổi' ?>
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

<!-- Nạp thư viện tạo mã QR offline -->
<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script src="<?= url('assets/vendor/qrcode/qrcode_UTF8.js') ?>"></script>
<script src="<?= url('assets/js/qr-tao.js') ?>"></script>

<script>
function doiPhamViTuongThich(val) {
    const box = document.getElementById('khuVucTheoPhong');
    if (val === 'THEO_PHONG') {
        box.classList.remove('d-none');
    } else {
        box.classList.add('d-none');
    }
}

// Xử lý xem trước mã QR tự động thời gian thực
function capNhatXemTruocQr() {
    const inputMaQr = document.getElementById('ma_qr');
    const inputMaTs = document.getElementById('ma_tai_san');
    const selectPhongTruc = document.getElementById('phong_truc_id');
    const boxQr = document.getElementById('preview_qr_box');
    const textQr = document.getElementById('preview_qr_val');

    let maQrDuKien = inputMaQr ? inputMaQr.value.trim() : '';

    if (!maQrDuKien && inputMaTs && inputMaTs.value.trim()) {
        const maTs = inputMaTs.value.trim().toUpperCase().replace(/[^A-Z0-9_-]/g, '');
        let maPt = 'D6';
        if (selectPhongTruc && selectPhongTruc.selectedIndex > 0) {
            const txt = selectPhongTruc.options[selectPhongTruc.selectedIndex].text;
            const match = txt.match(/\(([^)]+)\)/);
            if (match) {
                maPt = match[1];
            }
        }
        maQrDuKien = 'QR-' + maPt + '-' + maTs.replace(/[^A-Z0-9]/g, '');
    }

    if (maQrDuKien) {
        textQr.textContent = maQrDuKien;
        taoMaQr(boxQr, maQrDuKien, { size: 76, margin: 1 });
    } else {
        textQr.textContent = '---';
        boxQr.innerHTML = '<span class="text-muted small" style="font-size: 10px; text-align: center;">Chưa có QR</span>';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const inputMaQr = document.getElementById('ma_qr');
    const inputMaTs = document.getElementById('ma_tai_san');
    const selectPhongTruc = document.getElementById('phong_truc_id');

    if (inputMaQr) inputMaQr.addEventListener('input', capNhatXemTruocQr);
    if (inputMaTs) inputMaTs.addEventListener('input', capNhatXemTruocQr);
    if (selectPhongTruc) selectPhongTruc.addEventListener('change', capNhatXemTruocQr);

    capNhatXemTruocQr();
});
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>

