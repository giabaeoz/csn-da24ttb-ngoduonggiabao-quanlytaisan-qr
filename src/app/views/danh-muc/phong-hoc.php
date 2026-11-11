<?php

/**
 * Giao diện Quản lý Phòng học & Thiết bị phòng - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var list<array<string, mixed>> $danhSachPhongHoc
 * @var string $tuKhoa
 */

$tieuDeTrang = 'Quản lý phòng học';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="thanh-cong-cu-trang mb-3">
    <div>
        <h1 class="tieu-de-trang mb-0">Quản lý danh mục</h1>
        
    </div>
    <button type="button" class="btn btn-chinh" data-bs-toggle="modal" data-bs-target="#modalPhongHoc" onclick="moModalThem()">
        <?= icon('plus', 'bieu-tuong-sm') ?>
        <span>Thêm phòng học mới</span>
    </button>
</div>

<ul class="nav nav-tabs mb-3 border-bottom">
    <li class="nav-item">
        <a class="nav-link active fw-semibold" href="<?= url('danh-muc/phong-hoc.php') ?>">
            Phòng học
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-secondary fw-semibold" href="<?= url('danh-muc/phong-truc.php') ?>">
            Phòng trực
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-secondary fw-semibold" href="<?= url('danh-muc/loai-tai-san.php') ?>">
            Loại tài sản
        </a>
    </li>
</ul>

<div class="the-noi-dung p-3 mb-4">
    <form action="<?= url('danh-muc/phong-hoc.php') ?>" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
            <div class="o-tim-kiem">
                <?= icon('search', 'bieu-tuong-sm') ?>
                <input type="text" name="q" class="o-nhap" placeholder="Tìm theo mã phòng (B31.101), tên phòng, tòa nhà..." value="<?= e($tuKhoa ?? '') ?>">
            </div>
        </div>
        <div class="col-auto d-flex gap-2">
            <button type="submit" class="btn btn-chinh">
                <?= icon('search', 'bieu-tuong-sm') ?>
                <span>Tìm kiếm</span>
            </button>
            <?php if (!empty($tuKhoa)): ?>
                <a href="<?= url('danh-muc/phong-hoc.php') ?>" class="btn btn-phu">
                    <?= icon('rotate-ccw', 'bieu-tuong-sm') ?>
                    <span>Xóa lọc</span>
                </a>
            <?php endif; ?>
        </div>
        <div class="col text-end mo-ta-phu mb-0">
            Tổng cộng: <strong><?= count($danhSachPhongHoc) ?></strong> phòng học
        </div>
    </form>
</div>

<div class="the-noi-dung mb-4">
    <div class="the-noi-dung-dau">
        <h3 class="tieu-de-khoi mb-0">Danh sách phòng học & cấu hình thiết bị cố định</h3>
    </div>
    <div class="the-noi-dung-than p-0">
        <div class="table-responsive">
            <table class="bang-du-lieu mb-0">
                <thead>
                    <tr>
                        <th class="cot-120">Mã phòng</th>
                        <th class="cot-min-180">Tên phòng</th>
                        <th class="cot-140">Tòa / Tầng</th>
                        <th>Thiết bị cố định (Hãng / Model tương thích)</th>
                        <th class="text-center cot-130">Trạng thái</th>
                        <th class="text-end cot-160">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($danhSachPhongHoc)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Không tìm thấy phòng học nào.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($danhSachPhongHoc as $ph):
                            $thietBi = [];
                            if (!empty($ph['thiet_bi_phong'])) {
                                $thietBi = is_string($ph['thiet_bi_phong']) ? json_decode($ph['thiet_bi_phong'], true) : $ph['thiet_bi_phong'];
                            }
                        ?>
                            <tr>
                                <td class="fw-semibold text-primary">
                                    <span class="badge bg-light text-primary border">
                                        <?= e($ph['ma_phong']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= e($ph['ten_phong']) ?></div>
                                    <div class="text-muted small">
                                        Đang dùng: <strong><?= (int)$ph['so_tai_san_hien_tai'] ?></strong> thiết bị
                                        &bull; Gợi ý tem: <strong><?= (int)$ph['so_tai_san_gan_nhan'] ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?= e($ph['toa_nha'] ?: 'Toà -') ?> / Tầng <?= e($ph['tang'] ?: '-') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (empty($thietBi) || !is_array($thietBi)): ?>
                                        <span class="text-muted small">Chưa khai báo thiết bị cố định</span>
                                    <?php else: ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($thietBi as $tb): ?>
                                                <span class="badge bg-light text-dark border py-1 px-2 small">
                                                    <strong><?= e($tb['ten'] ?? 'Thiết bị') ?>:</strong>
                                                    <span class="text-primary"><?= e($tb['hang'] ?? '-') ?></span>
                                                    <?php if (!empty($tb['model'])): ?>
                                                        <span class="text-muted">(<?= e($tb['model']) ?>)</span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$ph['dang_hoat_dong'] === 1): ?>
                                        <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Đang mở</span>
                                    <?php else: ?>
                                        <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Tạm đóng</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <?php
                                        $chiTietPh = [
                                            ['nhan' => 'Mã phòng học', 'gia_tri' => $ph['ma_phong'] ?? ''],
                                            ['nhan' => 'Tên phòng học', 'gia_tri' => $ph['ten_phong'] ?? ''],
                                            ['nhan' => 'Tòa nhà / Tầng', 'gia_tri' => (($ph['toa_nha'] ?? '') ?: 'Tòa -') . ' / Tầng ' . (($ph['tang'] ?? '') ?: '-')],
                                            ['nhan' => 'Thiết bị đang dùng', 'gia_tri' => (int)($ph['so_tai_san_hien_tai'] ?? 0) . ' thiết bị'],
                                            ['nhan' => 'Trạng thái', 'gia_tri' => (int)($ph['dang_hoat_dong'] ?? 0) === 1 ? 'Đang mở' : 'Tạm đóng'],
                                            ['nhan' => 'Ghi chú', 'gia_tri' => ($ph['ghi_chu'] ?? '') ?: '---'],
                                        ];
                                        ?>
                                        <button type="button" class="btn-vien-thuoc"
                                            onclick='xemChiTietNhanh(<?= json_encode("Phòng học: " . ($ph["ma_phong"] ?? "")) ?>, <?= json_encode($chiTietPh, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            <span>Xem</span>
                                        </button>
                                        <button type="button" class="btn-vien-thuoc"
                                            onclick='moModalSua(<?= json_encode($ph, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            <span>Sửa</span>
                                        </button>

                                        <form action="<?= url('danh-muc/phong-hoc.php') ?>" method="POST" class="d-inline" data-xac-nhan="Bạn có chắc muốn đổi trạng thái phòng học này?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="hanh_dong" value="doi_trang_thai">
                                            <input type="hidden" name="id" value="<?= (int)$ph['id'] ?>">
                                            <?php $nhanKhoaMoPh = (int)$ph['dang_hoat_dong'] === 1 ? 'Khóa' : 'Mở'; ?>
                                            <button type="submit" class="btn-vien-thuoc">
                                                <span><?= $nhanKhoaMoPh ?></span>
                                            </button>
                                        </form>

                                        <?php if ((int)$ph['so_tai_san_hien_tai'] === 0 && (int)$ph['so_tai_san_gan_nhan'] === 0): ?>
                                            <form action="<?= url('danh-muc/phong-hoc.php') ?>" method="POST" class="d-inline" data-xac-nhan="Bạn có chắc muốn XÓA phòng học này?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="hanh_dong" value="xoa">
                                                <input type="hidden" name="id" value="<?= (int)$ph['id'] ?>">
                                                <button type="submit" class="btn-vien-thuoc text-danger" title="Xóa phòng học">
                                                    <span>Xóa</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPhongHoc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="<?= url('danh-muc/phong-hoc.php') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="hanh_dong" value="luu">
            <input type="hidden" name="id" id="modal_ph_id" value="">

            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalPhTieuDe">Thêm phòng học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="modal_ph_ma" class="nhan-truong">
                            Mã phòng <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="o-nhap text-uppercase" id="modal_ph_ma" name="ma_phong" placeholder="Ví dụ: B31.101" required>
                    </div>
                    <div class="col-12 col-md-8">
                        <label for="modal_ph_ten" class="nhan-truong">
                            Tên phòng học <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="o-nhap" id="modal_ph_ten" name="ten_phong" placeholder="Ví dụ: Phòng học lý thuyết B31.101" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4">
                        <label for="modal_ph_toanha" class="nhan-truong">Tòa nhà</label>
                        <input type="text" class="o-nhap" id="modal_ph_toanha" name="toa_nha" placeholder="Ví dụ: B31, B71, D6">
                    </div>
                    <div class="col-6 col-md-4">
                        <label for="modal_ph_tang" class="nhan-truong">Tầng</label>
                        <input type="text" class="o-nhap" id="modal_ph_tang" name="tang" placeholder="Ví dụ: 1, 2, Trệt">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="modal_ph_hoatdong" name="dang_hoat_dong" value="1" checked>
                            <label class="form-check-label fw-semibold small" for="modal_ph_hoatdong">Hoạt động</label>
                        </div>
                    </div>
                </div>

                <div class="the-noi-dung p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h4 class="tieu-de-khoi mb-0">Thiết bị cố định trong phòng (Hãng / Model)</h4>
                            
                        </div>
                        <button type="button" class="btn btn-sm btn-phu" onclick="themDongThietBi()">
                            <?= icon('plus', 'bieu-tuong-sm') ?>
                            <span>Thêm thiết bị</span>
                        </button>
                    </div>

                    <div id="danhSachThietBiContainer" class="d-flex flex-column gap-2 mt-2">
                    </div>
                </div>
            </div>

            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-phu" data-bs-dismiss="modal">
                    <?= icon('x', 'bieu-tuong-sm') ?>
                    <span>Hủy</span>
                </button>
                <button type="submit" class="btn btn-chinh">
                    <?= icon('save', 'bieu-tuong-sm') ?>
                    <span>Lưu phòng học</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let demThietBi = 0;

    function themDongThietBi(ten = '', hang = '', model = '') {
        const container = document.getElementById('danhSachThietBiContainer');
        const idx = demThietBi++;

        const div = document.createElement('div');
        div.className = 'row g-2 align-items-center bg-white p-2 rounded border dong-thiet-bi';
        div.id = 'dong_tb_' + idx;
        div.innerHTML = `
        <div class="col-12 col-md-4">
            <input type="text" class="o-nhap py-1" name="thiet_bi_phong[${idx}][ten]" 
                   placeholder="Loại TB (Máy lạnh, Máy chiếu...)" value="${escapeHtml(ten)}" required>
        </div>
        <div class="col-6 col-md-4">
            <input type="text" class="o-nhap py-1" name="thiet_bi_phong[${idx}][hang]" 
                   placeholder="Hãng (Daikin, Panasonic, Sony...)" value="${escapeHtml(hang)}" required>
        </div>
        <div class="col-5 col-md-3">
            <input type="text" class="o-nhap py-1" name="thiet_bi_phong[${idx}][model]" 
                   placeholder="Model (FTKB25W, PT-LB386...)" value="${escapeHtml(model)}">
        </div>
        <div class="col-1 col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-phu text-danger" onclick="xoaDongThietBi('dong_tb_${idx}')" title="Xóa">
                ✕
            </button>
        </div>
    `;
        container.appendChild(div);
    }

    function xoaDongThietBi(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
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

    function moModalThem() {
        document.getElementById('modalPhTieuDe').innerText = 'Thêm phòng học mới';
        document.getElementById('modal_ph_id').value = '';
        document.getElementById('modal_ph_ma').value = '';
        document.getElementById('modal_ph_ma').readOnly = false;
        document.getElementById('modal_ph_ten').value = '';
        document.getElementById('modal_ph_toanha').value = '';
        document.getElementById('modal_ph_tang').value = '';
        document.getElementById('modal_ph_hoatdong').checked = true;

        const container = document.getElementById('danhSachThietBiContainer');
        container.innerHTML = '';
        demThietBi = 0;
        themDongThietBi('Máy lạnh', '', '');
        themDongThietBi('Máy chiếu', '', '');
    }

    function moModalSua(ph) {
        document.getElementById('modalPhTieuDe').innerText = 'Chỉnh sửa phòng học: ' + ph.ma_phong;
        document.getElementById('modal_ph_id').value = ph.id;
        document.getElementById('modal_ph_ma').value = ph.ma_phong;
        document.getElementById('modal_ph_ten').value = ph.ten_phong;
        document.getElementById('modal_ph_toanha').value = ph.toa_nha || '';
        document.getElementById('modal_ph_tang').value = ph.tang || '';
        document.getElementById('modal_ph_hoatdong').checked = parseInt(ph.dang_hoat_dong, 10) === 1;

        const container = document.getElementById('danhSachThietBiContainer');
        container.innerHTML = '';
        demThietBi = 0;

        let cacTb = [];
        if (ph.thiet_bi_phong) {
            cacTb = typeof ph.thiet_bi_phong === 'string' ? JSON.parse(ph.thiet_bi_phong) : ph.thiet_bi_phong;
        }

        if (Array.isArray(cacTb) && cacTb.length > 0) {
            cacTb.forEach(tb => {
                themDongThietBi(tb.ten || '', tb.hang || '', tb.model || '');
            });
        } else {
            themDongThietBi('Máy lạnh', '', '');
            themDongThietBi('Máy chiếu', '', '');
        }

        const modal = new bootstrap.Modal(document.getElementById('modalPhongHoc'));
        modal.show();
    }
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>