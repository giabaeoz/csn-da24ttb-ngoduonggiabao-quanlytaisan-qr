<?php
/**
 * Giao diện Quản lý Phòng học & Cấu hình Thiết bị cố định (hãng/model).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Quản lý Phòng học & Thiết bị phòng';
require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <h1 class="h3 fw-bold text-dark mb-0">🏢 Quản lý Danh mục hệ thống</h1>
    <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPhongHoc" onclick="moModalThem()">
        + Thêm phòng học mới
    </button>
</div>

<!-- Thanh điều hướng Tab Danh mục -->
<ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item">
        <a class="nav-link active fw-semibold" href="<?= url('danh-muc/phong-hoc.php') ?>">
            🏫 Phòng học (Kèm Thiết bị phòng)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-semibold" href="<?= url('danh-muc/phong-truc.php') ?>">
            🏢 Phòng trực thiết bị
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-semibold" href="<?= url('danh-muc/loai-tai-san.php') ?>">
            🏷️ Loại tài sản
        </a>
    </li>
</ul>

<!-- Thanh tìm kiếm -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('danh-muc/phong-hoc.php') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Tìm theo mã phòng (B31.101), tên phòng, tòa nhà..." value="<?= e($tuKhoa ?? '') ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary fw-semibold">Tìm kiếm</button>
                <?php if (!empty($tuKhoa)): ?>
                    <a href="<?= url('danh-muc/phong-hoc.php') ?>" class="btn btn-outline-secondary">Xóa lọc</a>
                <?php endif; ?>
            </div>
            <div class="col text-end text-muted small">
                Tổng cộng: <strong><?= count($danhSachPhongHoc) ?></strong> phòng học
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh sách phòng học -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="fw-bold mb-0 text-dark">Danh sách Phòng học & Cấu hình thiết bị cố định</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 110px;">Mã phòng</th>
                    <th style="min-width: 180px;">Tên phòng</th>
                    <th style="width: 120px;">Tòa / Tầng</th>
                    <th>Thiết bị cố định (Hãng / Model tương thích)</th>
                    <th class="text-center" style="width: 130px;">Trạng thái</th>
                    <th class="text-end pe-3" style="width: 160px;">Thao tác</th>
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
                    <td class="ps-3">
                        <span class="badge bg-light text-primary border fs-6">
                            <?= e($ph['ma_phong']) ?>
                        </span>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark"><?= e($ph['ten_phong']) ?></div>
                        <div class="text-muted small">
                            Đang dùng: <strong><?= (int)$ph['so_tai_san_hien_tai'] ?></strong> thiết bị
                            &bull; Gợi ý tem: <strong><?= (int)$ph['so_tai_san_gan_nhan'] ?></strong>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                            <?= e($ph['toa_nha'] ?: 'Toà -') ?> / Tầng <?= e($ph['tang'] ?: '-') ?>
                        </span>
                    </td>
                    <td>
                        <?php if (empty($thietBi) || !is_array($thietBi)): ?>
                            <span class="text-muted small fst-italic">Chưa khai báo thiết bị cố định</span>
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
                            <span class="badge bg-success">Đang mở</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Tạm đóng</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary"
                                    onclick='moModalSua(<?= json_encode($ph, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    title="Chỉnh sửa thông tin">
                                Sửa
                            </button>

                            <form action="<?= url('danh-muc/phong-hoc.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái phòng học này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hanh_dong" value="doi_trang_thai">
                                <input type="hidden" name="id" value="<?= (int)$ph['id'] ?>">
                                <button type="submit" class="btn btn-outline-secondary" title="Đổi trạng thái">
                                    <?= (int)$ph['dang_hoat_dong'] === 1 ? 'Khóa' : 'Mở' ?>
                                </button>
                            </form>

                            <?php if ((int)$ph['so_tai_san_hien_tai'] === 0 && (int)$ph['so_tai_san_gan_nhan'] === 0): ?>
                            <form action="<?= url('danh-muc/phong-hoc.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn XÓA phòng học này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hanh_dong" value="xoa">
                                <input type="hidden" name="id" value="<?= (int)$ph['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger" title="Xóa phòng học">
                                    Xóa
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

<!-- Modal Thêm / Sửa Phòng học -->
<div class="modal fade" id="modalPhongHoc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="<?= url('danh-muc/phong-hoc.php') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="hanh_dong" value="luu">
            <input type="hidden" name="id" id="modal_ph_id" value="">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalPhTieuDe">Thêm phòng học mới</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-4">
                        <label for="modal_ph_ma" class="form-label fw-semibold small text-secondary">
                            Mã phòng <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control text-uppercase" id="modal_ph_ma" name="ma_phong" placeholder="Ví dụ: B31.101" required>
                    </div>
                    <div class="col-12 col-md-8">
                        <label for="modal_ph_ten" class="form-label fw-semibold small text-secondary">
                            Tên phòng học <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="modal_ph_ten" name="ten_phong" placeholder="Ví dụ: Phòng học lý thuyết B31.101" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-4">
                        <label for="modal_ph_toanha" class="form-label fw-semibold small text-secondary">Tòa nhà</label>
                        <input type="text" class="form-control" id="modal_ph_toanha" name="toa_nha" placeholder="Ví dụ: B31, B71, D6">
                    </div>
                    <div class="col-6 col-md-4">
                        <label for="modal_ph_tang" class="form-label fw-semibold small text-secondary">Tầng</label>
                        <input type="text" class="form-control" id="modal_ph_tang" name="tang" placeholder="Ví dụ: 1, 2, Trệt">
                    </div>
                    <div class="col-12 col-md-4 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="modal_ph_hoatdong" name="dang_hoat_dong" value="1" checked>
                            <label class="form-check-label fw-semibold small" for="modal_ph_hoatdong">Hoạt động</label>
                        </div>
                    </div>
                </div>

                <!-- CẤU HÌNH THIẾT BỊ CỐ ĐỊNH TRONG PHÒNG -->
                <div class="card border rounded-3 p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="fw-bold mb-0 text-primary">⚙️ Thiết bị cố định trong phòng (Hãng / Model)</h6>
                            <p class="small text-muted mb-0">Dùng để so khớp độ tương thích khi mượn hoặc điều chuyển remote, micro...</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary fw-semibold" onclick="themDongThietBi()">
                            + Thêm thiết bị
                        </button>
                    </div>

                    <div id="danhSachThietBiContainer" class="d-flex flex-column gap-2 mt-2">
                        <!-- Các dòng thiết bị sẽ được JavaScript tự động chèn vào đây -->
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary fw-semibold">Lưu phòng học</button>
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
            <input type="text" class="form-control form-control-sm" name="thiet_bi_phong[${idx}][ten]" 
                   placeholder="Loại TB (Máy lạnh, Máy chiếu...)" value="${escapeHtml(ten)}" required>
        </div>
        <div class="col-6 col-md-4">
            <input type="text" class="form-control form-control-sm" name="thiet_bi_phong[${idx}][hang]" 
                   placeholder="Hãng (Daikin, Panasonic, Sony...)" value="${escapeHtml(hang)}" required>
        </div>
        <div class="col-5 col-md-3">
            <input type="text" class="form-control form-control-sm" name="thiet_bi_phong[${idx}][model]" 
                   placeholder="Model (FTKB25W, PT-LB386...)" value="${escapeHtml(model)}">
        </div>
        <div class="col-1 col-md-1 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="xoaDongThietBi('dong_tb_${idx}')" title="Xóa">
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
    // Mặc định tạo sẵn 2 dòng mẫu: Máy lạnh và Máy chiếu
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

