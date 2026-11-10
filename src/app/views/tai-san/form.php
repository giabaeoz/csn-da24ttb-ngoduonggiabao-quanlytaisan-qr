<?php

/**
 * Giao diện Biểu mẫu Thêm mới / Chỉnh sửa Tài sản - Mã QR sinh trực tiếp từ Mã tài sản
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

// Lấy danh sách mã tài sản hiện có để gợi ý số thứ tự tiếp theo chính xác
$dsMaHienCo = [];
try {
    $resMa = db()->query("SELECT ma_tai_san FROM tai_san");
    if ($resMa) {
        while ($r = $resMa->fetch_assoc()) {
            $dsMaHienCo[] = strtoupper((string)$r['ma_tai_san']);
        }
    }
} catch (Throwable $e) {
    $dsMaHienCo = [];
}
?>

<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang mb-0">
            <?= $laThemMoi ? 'Thêm thiết bị mới' : 'Chỉnh sửa thông tin thiết bị' ?>
        </h1>
        
    </div>
    <div>
        <a href="<?= url('tai-san/index.php') ?>" class="btn btn-phu">
            <?= icon('arrow-left', 'bieu-tuong-sm') ?>
            <span>Quay lại danh sách</span>
        </a>
    </div>
</div>

<div class="khung-bieu-mau">

    <?php if (!empty($thongBaoLoi)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
            <?= icon('alert-circle', 'bieu-tuong-md') ?>
            <div class="flex-grow-1"><strong>Lỗi thao tác:</strong> <?= e($thongBaoLoi) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="<?= $laThemMoi ? url('tai-san/them.php') : url('tai-san/sua.php?id=' . (int)$taiSan['id']) ?>" method="POST" class="the-noi-dung p-0">
        <?= csrf_field() ?>

        <div class="the-noi-dung-dau">
            <h3 class="tieu-de-khoi mb-0">Thông tin cơ bản & mã QR thiết bị</h3>
        </div>

        <div class="the-noi-dung-than p-4">
            <div class="row g-3 mb-3 align-items-start">
                <div class="col-12 col-md-8">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="ten_tai_san" class="nhan-truong">
                                Tên thiết bị <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="o-nhap" id="ten_tai_san" name="ten_tai_san"
                                value="<?= e($taiSan['ten_tai_san'] ?? '') ?>"
                                placeholder="Ví dụ: Máy chiếu Epson EB-X06, Remote máy chiếu Sony, Micro Shure..." required <?= $laThemMoi ? 'autofocus' : '' ?>>
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="ma_tai_san" class="nhan-truong">
                                Mã tài sản (Dùng tạo QR) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input type="text" class="form-control text-uppercase font-monospace fw-bold" id="ma_tai_san" name="ma_tai_san"
                                    value="<?= e($taiSan['ma_tai_san'] ?? '') ?>"
                                    placeholder="Ví dụ: MC-01, RMC-01" required>
                                <button type="button" class="btn btn-phu" onclick="goiYMaTuTen(true)" title="Tự động đặt mã viết tắt từ tên thiết bị">
                                    <span>Gợi ý mã</span>
                                </button>
                            </div>
                            
                        </div>

                        <div class="col-12 col-sm-6">
                            <label for="loai_tai_san_id" class="nhan-truong">
                                Nhóm tài sản <span class="text-danger">*</span>
                            </label>
                            <select class="o-nhap" id="loai_tai_san_id" name="loai_tai_san_id" required>
                                <option value="">-- Chọn nhóm tài sản --</option>
                                <?php foreach ($danhSachLoai as $loai): ?>
                                    <option value="<?= (int)$loai['id'] ?>" <?= ((int)($taiSan['loai_tai_san_id'] ?? 0) === (int)$loai['id']) ? 'selected' : '' ?>>
                                        <?= e($loai['ten_loai']) ?> (<?= e($loai['ma_loai']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="nhan-truong">Mã QR sinh từ Mã tài sản</label>
                    <div class="p-3 border rounded bg-light d-flex align-items-center gap-3">
                        <div id="preview_qr_box" class="bg-white p-1 border flex-shrink-0 khung-qr-84 d-flex align-items-center justify-content-center">
                            <span class="text-muted small chu-nho-10">Chưa có mã</span>
                        </div>
                        <div class="small overflow-hidden">
                            <div class="text-secondary chu-nho-11">Mã nhúng trong QR:</div>
                            <div id="preview_qr_val" class="font-monospace text-primary fw-bold fs-6 mt-1">---</div>
                            <div class="text-muted chu-nho-11 mt-1">Quét QR sẽ nhận diện trực tiếp theo mã tài sản này.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-6">
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

                <div class="col-12 col-md-6">
                    <label for="ngay_dua_vao_su_dung" class="nhan-truong">
                        Ngày đưa vào sử dụng
                    </label>
                    <input type="date" class="o-nhap" id="ngay_dua_vao_su_dung" name="ngay_dua_vao_su_dung"
                        value="<?= e($taiSan['ngay_dua_vao_su_dung'] ?? date('Y-m-d')) ?>">
                </div>
            </div>

            <div class="the-noi-dung p-3 mb-3 bg-light">
                <h4 class="tieu-de-khoi mb-2">Thông số kỹ thuật & phạm vi sử dụng</h4>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="hang_san_xuat" class="nhan-truong">
                            Hãng sản xuất
                        </label>
                        <input type="text" class="o-nhap bg-white" id="hang_san_xuat" name="hang_san_xuat"
                            value="<?= e($taiSan['hang_san_xuat'] ?? '') ?>"
                            placeholder="Ví dụ: Epson, Panasonic, Sony, Daikin, Shure">
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="model" class="nhan-truong">
                            Model kỹ thuật
                        </label>
                        <input type="text" class="o-nhap bg-white" id="model" name="model"
                            value="<?= e($taiSan['model'] ?? '') ?>"
                            placeholder="Ví dụ: EB-X06, PT-LB386, VPL-EX435, FTKB25W">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="pham_vi_tuong_thich" class="nhan-truong">
                            Phạm vi tương thích phòng học
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
                            Nhãn phòng học gợi ý trên kệ
                        </label>
                        <select class="o-nhap bg-white" id="phong_hoc_phu_hop_id" name="phong_hoc_phu_hop_id">
                            <option value="">-- Không gán nhãn cụ thể (Linh hoạt) --</option>
                            <?php foreach ($danhSachPhongHoc as $ph): ?>
                                <option value="<?= (int)$ph['id'] ?>" <?= ((int)($taiSan['phong_hoc_phu_hop_id'] ?? 0) === (int)$ph['id']) ? 'selected' : '' ?>>
                                    <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="khuVucTheoPhong" class="mt-3 <?= ($taiSan['pham_vi_tuong_thich'] ?? '') === 'THEO_PHONG' ? '' : 'd-none' ?>">
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

            <?php if (!$laThemMoi): ?>
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="tinh_trang" class="nhan-truong">Tình trạng vật lý</label>
                        <select class="o-nhap" id="tinh_trang" name="tinh_trang">
                            <option value="TOT" <?= ($taiSan['tinh_trang'] ?? '') === 'TOT' ? 'selected' : '' ?>>Tốt</option>
                            <option value="HU_HONG" <?= ($taiSan['tinh_trang'] ?? '') === 'HU_HONG' ? 'selected' : '' ?>>Hư hỏng</option>
                            <option value="MAT" <?= ($taiSan['tinh_trang'] ?? '') === 'MAT' ? 'selected' : '' ?>>Mất</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="trang_thai" class="nhan-truong">Trạng thái vận hành</label>
                        <select class="o-nhap" id="trang_thai" name="trang_thai">
                            <option value="SAN_SANG" <?= ($taiSan['trang_thai'] ?? '') === 'SAN_SANG' ? 'selected' : '' ?>>Sẵn sàng</option>
                            <option value="DANG_MUON" <?= ($taiSan['trang_thai'] ?? '') === 'DANG_MUON' ? 'selected' : '' ?> disabled>Đang mượn (Hệ thống khóa)</option>
                            <option value="BAO_TRI" <?= ($taiSan['trang_thai'] ?? '') === 'BAO_TRI' ? 'selected' : '' ?>>Bảo trì</option>
                            <option value="NGUNG_SU_DUNG" <?= ($taiSan['trang_thai'] ?? '') === 'NGUNG_SU_DUNG' ? 'selected' : '' ?>>Ngừng sử dụng</option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <label for="mo_ta" class="nhan-truong">Mô tả đặc điểm / ghi chú</label>
                <textarea class="o-nhap" id="mo_ta" name="mo_ta" rows="2" placeholder="Ví dụ: Đi kèm cáp nguồn 1.5m, đựng trong túi chống sốc đen"><?= e($taiSan['mo_ta'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end align-items-center gap-2 pt-3 border-top">
                <a href="<?= url('tai-san/index.php') ?>" class="btn btn-phu">
                    <?= icon('x', 'bieu-tuong-sm') ?>
                    <span>Hủy bỏ</span>
                </a>
                <button type="submit" class="btn btn-chinh">
                    <?= icon('save', 'bieu-tuong-sm') ?>
                    <span><?= $laThemMoi ? 'Lưu thiết bị' : 'Cập nhật thay đổi' ?></span>
                </button>
            </div>
        </div>
    </form>

</div>

<script src="<?= url('assets/vendor/qrcode/qrcode.js') ?>"></script>
<script src="<?= url('assets/vendor/qrcode/qrcode_UTF8.js') ?>"></script>
<script src="<?= url('assets/js/qr-tao.js') ?>"></script>

<script>
    const DS_MA_HIEN_CO = <?= json_encode($dsMaHienCo) ?>;
    const LA_THEM_MOI = <?= $laThemMoi ? 'true' : 'false' ?>;
    let daSuaMaThuCong = !LA_THEM_MOI;

    function doiPhamViTuongThich(val) {
        const box = document.getElementById('khuVucTheoPhong');
        if (val === 'THEO_PHONG') {
            box.classList.remove('d-none');
        } else {
            box.classList.add('d-none');
        }
    }

    function boDauTiengViet(str) {
        return str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase().trim();
    }

    function vietTatTuTen(ten) {
        const kd = boDauTiengViet(ten);
        if (!kd) return '';
        if (kd.startsWith('remote may chieu')) return 'RMC';
        if (kd.startsWith('remote may lanh') || kd.startsWith('remote dieu hoa')) return 'RML';
        if (kd.startsWith('remote')) return 'RM';
        if (kd.startsWith('may chieu')) return 'MC';
        if (kd.startsWith('micro')) return 'MIC';
        if (kd.startsWith('loa')) return 'LOA';
        if (kd.startsWith('cap ') || kd.startsWith('day cap')) return 'CAP';
        if (kd.startsWith('o cam') || kd.startsWith('o dien')) return 'OCD';
        if (kd.startsWith('but trinh chieu') || kd.startsWith('but chi')) return 'BTC';

        const words = kd.split(/[^a-z0-9]+/).filter(Boolean).slice(0, 3);
        return words.map(w => w[0].toUpperCase()).join('') || 'TS';
    }

    function goiYMaTuTen(batBuoc = false) {
        const inputTen = document.getElementById('ten_tai_san');
        const inputMa = document.getElementById('ma_tai_san');
        if (!inputTen || !inputMa) return;
        if (!batBuoc && daSuaMaThuCong) return;

        const tienTo = vietTatTuTen(inputTen.value);
        if (!tienTo) {
            if (!batBuoc) inputMa.value = '';
            capNhatXemTruocQr();
            return;
        }

        let maxSo = 0;
        const regex = new RegExp('^' + tienTo + '-(\\d+)$', 'i');
        DS_MA_HIEN_CO.forEach(ma => {
            const m = ma.match(regex);
            if (m) {
                const so = parseInt(m[1], 10);
                if (so > maxSo) maxSo = so;
            }
        });

        const soTiep = String(maxSo + 1).padStart(2, '0');
        inputMa.value = tienTo + '-' + soTiep;
        if (batBuoc) daSuaMaThuCong = false;
        capNhatXemTruocQr();
    }

    // Xem trước mã QR trực tiếp từ Mã tài sản
    function capNhatXemTruocQr() {
        const inputMaTs = document.getElementById('ma_tai_san');
        const boxQr = document.getElementById('preview_qr_box');
        const textQr = document.getElementById('preview_qr_val');

        const maTs = inputMaTs ? inputMaTs.value.trim().toUpperCase() : '';

        if (maTs) {
            textQr.textContent = maTs;
            taoMaQr(boxQr, maTs, {
                size: 76,
                margin: 1
            });
        } else {
            textQr.textContent = '---';
            boxQr.innerHTML = '<span class="text-muted small chu-nho-10">Chưa có mã</span>';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inputTen = document.getElementById('ten_tai_san');
        const inputMaTs = document.getElementById('ma_tai_san');

        if (inputTen) {
            inputTen.addEventListener('input', function() {
                goiYMaTuTen(false);
            });
        }
        if (inputMaTs) {
            inputMaTs.addEventListener('input', function() {
                daSuaMaThuCong = this.value.trim() !== '';
                capNhatXemTruocQr();
            });
        }

        capNhatXemTruocQr();
    });
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
