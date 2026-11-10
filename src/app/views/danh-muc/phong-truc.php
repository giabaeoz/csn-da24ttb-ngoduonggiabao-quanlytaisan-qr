<?php
/**
 * Giao diện Quản lý Phòng trực thiết bị - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Quản lý phòng trực';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<!-- Tiêu đề trang & Nút tác vụ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang mb-0">Quản lý danh mục</h1>
        <div class="mo-ta-phu">Cấu hình phòng học, phòng trực và loại tài sản trong hệ thống.</div>
    </div>
    <button type="button" class="btn btn-chinh" data-bs-toggle="modal" data-bs-target="#modalPhongTruc" onclick="moModalThem()">
        + Thêm phòng trực
    </button>
</div>

<!-- Thanh điều hướng Tab Danh mục -->
<ul class="nav nav-tabs mb-3 border-bottom">
    <li class="nav-item">
        <a class="nav-link text-secondary fw-semibold" href="<?= url('danh-muc/phong-hoc.php') ?>">
            Phòng học
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active fw-semibold" href="<?= url('danh-muc/phong-truc.php') ?>">
            Phòng trực
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-secondary fw-semibold" href="<?= url('danh-muc/loai-tai-san.php') ?>">
            Loại tài sản
        </a>
    </li>
</ul>

<!-- Bảng danh sách phòng trực -->
<div class="the-noi-dung mb-4">
    <div class="the-noi-dung-dau">
        <h3 class="tieu-de-khoi mb-0">Danh sách phòng trực thiết bị</h3>
    </div>
    <div class="the-noi-dung-than p-0">
        <div class="table-responsive">
            <table class="bang-du-lieu mb-0">
                <thead>
                    <tr>
                        <th style="width: 100px;">Mã</th>
                        <th>Tên phòng trực</th>
                        <th>Vị trí vật lý</th>
                        <th class="text-center" style="width: 140px;">Cán bộ trực</th>
                        <th class="text-center" style="width: 140px;">Tài sản quản lý</th>
                        <th class="text-center" style="width: 140px;">Trạng thái</th>
                        <th class="text-end" style="width: 160px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($danhSachPhongTruc)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            Chưa có phòng trực nào trong hệ thống.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($danhSachPhongTruc as $pt): ?>
                    <tr>
                        <td class="fw-semibold text-primary">
                            <span class="badge bg-light text-primary border">
                                <?= e($pt['ma_phong_truc']) ?>
                            </span>
                        </td>
                        <td class="fw-medium text-dark">
                            <?= e($pt['ten_phong_truc']) ?>
                        </td>
                        <td class="text-secondary small">
                            <?= e($pt['vi_tri'] ?: '—') ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-secondary border px-2 py-1">
                                <?= (int)$pt['so_nhan_vien'] ?> cán bộ
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-primary border px-2 py-1">
                                <?= (int)$pt['so_tai_san'] ?> thiết bị
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ((int)$pt['dang_hoat_dong'] === 1): ?>
                                <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Đang hoạt động</span>
                            <?php else: ?>
                                <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Tạm tắt</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <button type="button" class="btn btn-sm btn-phu" 
                                        onclick='moModalSua(<?= json_encode($pt, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                    Sửa
                                </button>

                                <form action="<?= url('danh-muc/phong-truc.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái phòng trực này?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="hanh_dong" value="doi_trang_thai">
                                    <input type="hidden" name="id" value="<?= (int)$pt['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-phu">
                                        <?= (int)$pt['dang_hoat_dong'] === 1 ? 'Khóa' : 'Mở' ?>
                                    </button>
                                </form>

                                <?php if ((int)$pt['so_nhan_vien'] === 0 && (int)$pt['so_tai_san'] === 0): ?>
                                <form action="<?= url('danh-muc/phong-truc.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA phòng trực này? Thao tác không thể hoàn tác.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="hanh_dong" value="xoa">
                                    <input type="hidden" name="id" value="<?= (int)$pt['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-phu text-danger">
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
</div>

<!-- Modal Thêm / Sửa Phòng trực -->
<div class="modal fade" id="modalPhongTruc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= url('danh-muc/phong-truc.php') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="hanh_dong" value="luu">
            <input type="hidden" name="id" id="modal_id" value="">

            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalTieuDe">Thêm phòng trực mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="modal_ma" class="nhan-truong">
                        Mã phòng trực <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="o-nhap text-uppercase" id="modal_ma" name="ma_phong_truc" placeholder="Ví dụ: D6, B2" required>
                    <div class="mo-ta-phu mt-1">Mã viết tắt duy nhất định danh phòng trực.</div>
                </div>

                <div class="mb-3">
                    <label for="modal_ten" class="nhan-truong">
                        Tên phòng trực <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="o-nhap" id="modal_ten" name="ten_phong_truc" placeholder="Ví dụ: Phòng trực thiết bị D6" required>
                </div>

                <div class="mb-3">
                    <label for="modal_vi_tri" class="nhan-truong">Vị trí thực tế</label>
                    <input type="text" class="o-nhap" id="modal_vi_tri" name="vi_tri" placeholder="Ví dụ: Tầng trệt tòa D6">
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="modal_hoat_dong" name="dang_hoat_dong" value="1" checked>
                    <label class="form-check-label fw-semibold small" for="modal_hoat_dong">Cho phép hoạt động</label>
                </div>
            </div>

            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-phu" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-chinh">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>

<script>
function moModalThem() {
    document.getElementById('modalTieuDe').innerText = 'Thêm phòng trực mới';
    document.getElementById('modal_id').value = '';
    document.getElementById('modal_ma').value = '';
    document.getElementById('modal_ma').readOnly = false;
    document.getElementById('modal_ten').value = '';
    document.getElementById('modal_vi_tri').value = '';
    document.getElementById('modal_hoat_dong').checked = true;
}

function moModalSua(pt) {
    document.getElementById('modalTieuDe').innerText = 'Chỉnh sửa phòng trực: ' + pt.ma_phong_truc;
    document.getElementById('modal_id').value = pt.id;
    document.getElementById('modal_ma').value = pt.ma_phong_truc;
    document.getElementById('modal_ten').value = pt.ten_phong_truc;
    document.getElementById('modal_vi_tri').value = pt.vi_tri || '';
    document.getElementById('modal_hoat_dong').checked = parseInt(pt.dang_hoat_dong, 10) === 1;

    const modal = new bootstrap.Modal(document.getElementById('modalPhongTruc'));
    modal.show();
}
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
