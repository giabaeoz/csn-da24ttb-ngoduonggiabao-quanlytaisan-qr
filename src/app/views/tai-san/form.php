<?php
/**
 * Giao diện Biểu mẫu Thêm mới / Chỉnh sửa Tài sản - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var bool $laThemMoi
 * @var array<string, mixed> $taiSan
 * @var list<array<string, mixed>> $danhSachLoai
 * @var list<array<string, mixed>> $danhSachPhongTruc
 * @var list<array<string, mixed>> $danhSachPhongHoc
 * @var string $thongBaoLoi
 */

$tieuDeTrang = $laThemMoi ? 'Thêm thiết bị mới' : 'Chỉnh sửa thông tin thiết bị';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<!-- Tiêu đề trang & Nút tác vụ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang mb-0">
            <?= $laThemMoi ? 'Thêm thiết bị mới' : 'Chỉnh sửa thông tin thiết bị' ?>
        </h1>
        <div class="mo-ta-phu">
            <?= $laThemMoi ? 'Đăng ký tài sản vào hệ thống, thiết lập độ tương thích và sinh mã QR tự động.' : 'Cập nhật thông tin kỹ thuật, nhãn vị trí kệ và phạm vi tương thích.' ?>
        </div>
    </div>
    <div>
        <a href="<?= url('tai-san/index.php') ?>" class="btn btn-phu">
            Quay lại danh sách
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

        <form action="<?= $laThemMoi ? url('tai-san/them.php') : url('tai-san/sua.php?id=' . (int)$taiSan['id']) ?>" method="POST" class="the-noi-dung p-0">
            <?= csrf_field() ?>

            <div class="the-noi-dung-dau">
                <h3 class="tieu-de-khoi mb-0">Thông tin cơ bản & định danh thiết bị</h3>
            </div>

            <div class="the-noi-dung-than p-4">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="ma_tai_san" class="nhan-truong">
                            Mã tài sản <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="o-nhap text-uppercase font-monospace fw-bold" id="ma_tai_san" name="ma_tai_san" 
                               value="<?= e($taiSan['ma_tai_san'] ?? '') ?>" 
                               placeholder="Ví dụ: MC-0001, RM-0001" required autofocus>
                        <div class="mo-ta-phu mt-1">Mã định danh viết tắt theo chủng loại.</div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="ma_qr" class="nhan-truong">
                            Mã QR định danh
                        </label>
                        <input type="text" class="o-nhap font-monospace" id="ma_qr" name="ma_qr" 
                               value="<?= e($taiSan['ma_qr'] ?? '') ?>" 
                               placeholder="Tự động sinh (Ví dụ: QR-D6-MC0001)">
                        <div class="mo-ta-phu mt-1 mb-2">Để trống để hệ thống tự động sinh theo mẫu chuẩn.</div>

                        <!-- Khung xem trước mã QR tự động -->
                        <div class="p-2 border rounded bg-light d-flex align-items-center gap-3">
                            <div id="preview_qr_box" class="bg-white p-1 border flex-shrink-0" style="width: 84px; height: 84px; display: flex; align-items: center; justify-content: center;">
                                <span class="text-muted small" style="font-size: 10px; text-align: center;">Chưa có QR</span>
                            </div>
                            <div class="small overflow-hidden">
                                <div class="fw-semibold text-secondary" style="font-size: 11px;">Xem trước tem mã QR:</div>
                                <div id="preview_qr_val" class="font-monospace text-primary fw-bold" style="word-break: break-all; font-size: 12px;">---</div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="loai_tai_san_id" class="nhan-truong">
                            Chủng loại thiết bị <span class="text-danger">*</span>
                        </label>
                        <select class="o-nhap" id="loai_tai_san_id" name="loai_tai_san_id" required>
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
                        <label for="ten_tai_san" class="nhan-truong">
                            Tên thiết bị <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="o-nhap" id="ten_tai_san" name="ten_tai_san" 
                               value="<?= e($taiSan['ten_tai_san'] ?? '') ?>" 
                               placeholder="Ví dụ: Máy chiếu Epson EB-X06, Remote máy lạnh Daikin" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="phong_truc_id" class="nhan-truong">
                            Phòng trực quản lý <span class="text-danger">*</span>
                        </label>
                        <select class="o-nhap" id="phong_truc_id" name="phong_truc_id" required>
                            <option value="">-- Chọn bàn trực --</option>
                            <?php foreach ($danhSachPhongTruc as $pt): ?>
                            <option value="<?= (int)$pt['id'] ?>" <?= ((int)($taiSan['phong_truc_id'] ?? 0) === (int)$pt['id']) ? 'selected' : '' ?>>
                                <?= e($pt['ten_phong_truc']) ?> (<?= e($pt['ma_phong_truc']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Khu vực thông số hãng, model & độ tương thích -->
                <div class="the-noi-dung p-3 mb-3 bg-light">
                    <h4 class="tieu-de-khoi mb-1">Thông số kỹ thuật & độ tương thích</h4>
                    <p class="mo-ta-phu mb-3">
                        Hệ thống đối chiếu Hãng/Model thiết bị với cấu hình cố định trong phòng học khi nhân viên quét mã.
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-12 col-md-6">
                            <label for="hang_san_xuat" class="nhan-truong">
                                Hãng sản xuất
                            </label>
                            <input type="text" class="o-nhap bg-white" id="hang_san_xuat" name="hang_san_xuat" 
                                   value="<?= e($taiSan['hang_san_xuat'] ?? '') ?>" 
                                   placeholder="Ví dụ: Daikin, Panasonic, Sony, Epson, Soundking, Shure">
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="model" class="nhan-truong">
                                Model kỹ thuật
                            </label>
                            <input type="text" class="o-nhap bg-white" id="model" name="model" 
                                   value="<?= e($taiSan['model'] ?? '') ?>" 
                                   placeholder="Ví dụ: FTKB25W, PT-LB386, EB-X06, SK-12">
                        </div>
                    </div>

                    <div class="row g-3 mb-2">
                        <div class="col-12 col-md-6">
                            <label for="pham_vi_tuong_thich" class="nhan-truong">
                                Phạm vi tương thích phòng học <span class="text-danger">*</span>
                            </label>
                            <select class="o-nhap bg-white" id="pham_vi_tuong_thich" name="pham_vi_tuong_thich" onchange="doiPhamViTuongThich(this.value)">
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
                            <label for="phong_hoc_phu_hop_id" class="nhan-truong">
                                Nhãn phòng học gợi ý dán trên tem
                            </label>
                            <select class="o-nhap bg-white" id="phong_hoc_phu_hop_id" name="phong_hoc_phu_hop_id">
                                <option value="">-- Không gán nhãn cụ thể (Linh hoạt) --</option>
                                <?php foreach ($danhSachPhongHoc as $ph): ?>
                                <option value="<?= (int)$ph['id'] ?>" <?= ((int)($taiSan['phong_hoc_phu_hop_id'] ?? 0) === (int)$ph['id']) ? 'selected' : '' ?>>
                                    <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mo-ta-phu mt-1">Chỉ nhằm mục đích sắp xếp vị trí ngăn kệ trên giá tủ để lấy nhanh, không khóa cứng phòng.</div>
                        </div>
                    </div>

                    <!-- Danh sách phòng tương thích nếu chọn THEO_PHONG -->
                    <div id="khuVucTheoPhong" class="mt-2 <?= ($taiSan['pham_vi_tuong_thich'] ?? '') === 'THEO_PHONG' ? '' : 'd-none' ?>">
                        <label class="nhan-truong">
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
                        <input type="text" class="o-nhap bg-white" name="phong_tuong_thich" value="<?= e($cacPhongStr) ?>" placeholder="Ví dụ: B31.101, B31.102, B71.101">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="ngay_dua_vao_su_dung" class="nhan-truong">
                            Ngày đưa vào sử dụng
                        </label>
                        <input type="date" class="o-nhap" id="ngay_dua_vao_su_dung" name="ngay_dua_vao_su_dung" 
                               value="<?= e($taiSan['ngay_dua_vao_su_dung'] ?? date('Y-m-d')) ?>">
                    </div>

                    <?php if (!$laThemMoi): ?>
                    <div class="col-12 col-md-4">
                        <label for="tinh_trang" class="nhan-truong">Tình trạng vật lý</label>
                        <select class="o-nhap" id="tinh_trang" name="tinh_trang">
                            <option value="TOT" <?= ($taiSan['tinh_trang'] ?? '') === 'TOT' ? 'selected' : '' ?>>Tốt</option>
                            <option value="HU_HONG" <?= ($taiSan['tinh_trang'] ?? '') === 'HU_HONG' ? 'selected' : '' ?>>Hư hỏng</option>
                            <option value="MAT" <?= ($taiSan['tinh_trang'] ?? '') === 'MAT' ? 'selected' : '' ?>>Mất</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label for="trang_thai" class="nhan-truong">Trạng thái vận hành</label>
                        <select class="o-nhap" id="trang_thai" name="trang_thai">
                            <option value="SAN_SANG" <?= ($taiSan['trang_thai'] ?? '') === 'SAN_SANG' ? 'selected' : '' ?>>Sẵn sàng</option>
                            <option value="DANG_MUON" <?= ($taiSan['trang_thai'] ?? '') === 'DANG_MUON' ? 'selected' : '' ?> disabled>Đang mượn (Hệ thống khóa)</option>
                            <option value="BAO_TRI" <?= ($taiSan['trang_thai'] ?? '') === 'BAO_TRI' ? 'selected' : '' ?>>Bảo trì</option>
                            <option value="NGUNG_SU_DUNG" <?= ($taiSan['trang_thai'] ?? '') === 'NGUNG_SU_DUNG' ? 'selected' : '' ?>>Ngừng sử dụng</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <label for="mo_ta" class="nhan-truong">Mô tả đặc điểm / ghi chú</label>
                    <textarea class="o-nhap" id="mo_ta" name="mo_ta" rows="2" placeholder="Ví dụ: Đi kèm cáp nguồn 1.5m, đựng trong túi chống sốc đen"><?= e($taiSan['mo_ta'] ?? '') ?></textarea>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="<?= url('tai-san/index.php') ?>" class="btn btn-phu">
                        Hủy bỏ
                    </a>
                    <button type="submit" class="btn btn-chinh">
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
