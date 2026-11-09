<?php
/**
 * Giao diện Quản lý Chủng loại tài sản.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Quản lý Loại tài sản';
require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <h1 class="h3 fw-bold text-dark mb-0">🏢 Quản lý Danh mục hệ thống</h1>
    <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalLoai" onclick="moModalThem()">
        + Thêm loại tài sản
    </button>
</div>

<!-- Thanh điều hướng Tab Danh mục -->
<ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item">
        <a class="nav-link fw-semibold" href="<?= url('danh-muc/phong-hoc.php') ?>">
            🏫 Phòng học (Kèm Thiết bị phòng)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link fw-semibold" href="<?= url('danh-muc/phong-truc.php') ?>">
            🏢 Phòng trực thiết bị
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active fw-semibold" href="<?= url('danh-muc/loai-tai-san.php') ?>">
            🏷️ Loại tài sản
        </a>
    </li>
</ul>

<!-- Bảng danh sách loại tài sản -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white py-3 border-bottom">
        <h5 class="fw-bold mb-0 text-dark">Danh mục Chủng loại thiết bị</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 100px;">Mã loại</th>
                    <th style="min-width: 200px;">Tên loại tài sản</th>
                    <th>Mô tả quy cách</th>
                    <th class="text-center" style="width: 140px;">Số lượng tài sản</th>
                    <th class="text-center" style="width: 130px;">Trạng thái</th>
                    <th class="text-end pe-3" style="width: 160px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachLoai)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        Chưa có loại tài sản nào.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($danhSachLoai as $loai): ?>
                <tr>
                    <td class="ps-3">
                        <span class="badge bg-light text-primary border fs-6">
                            <?= e($loai['ma_loai']) ?>
                        </span>
                    </td>
                    <td class="fw-semibold text-dark">
                        <?= e($loai['ten_loai']) ?>
                    </td>
                    <td class="text-secondary small">
                        <?= e($loai['mo_ta'] ?: '-') ?>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1">
                            <?= (int)$loai['so_tai_san'] ?> thiết bị
                        </span>
                    </td>
                    <td class="text-center">
                        <?php if ((int)$loai['dang_hoat_dong'] === 1): ?>
                            <span class="badge bg-success">Đang hoạt động</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Tạm tắt</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary"
                                    onclick='moModalSua(<?= json_encode($loai, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    title="Chỉnh sửa">
                                Sửa
                            </button>

                            <form action="<?= url('danh-muc/loai-tai-san.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn đổi trạng thái loại tài sản này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hanh_dong" value="doi_trang_thai">
                                <input type="hidden" name="id" value="<?= (int)$loai['id'] ?>">
                                <button type="submit" class="btn btn-outline-secondary" title="Bật/Tắt hoạt động">
                                    <?= (int)$loai['dang_hoat_dong'] === 1 ? 'Khóa' : 'Mở' ?>
                                </button>
                            </form>

                            <?php if ((int)$loai['so_tai_san'] === 0): ?>
                            <form action="<?= url('danh-muc/loai-tai-san.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn XÓA loại tài sản này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hanh_dong" value="xoa">
                                <input type="hidden" name="id" value="<?= (int)$loai['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger" title="Xóa loại tài sản">
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

<!-- Modal Thêm / Sửa Loại tài sản -->
<div class="modal fade" id="modalLoai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= url('danh-muc/loai-tai-san.php') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="hanh_dong" value="luu">
            <input type="hidden" name="id" id="modal_loai_id" value="">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold" id="modalLoaiTieuDe">Thêm loại tài sản</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="modal_loai_ma" class="form-label fw-semibold small text-secondary">
                        Mã loại tài sản <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control text-uppercase" id="modal_loai_ma" name="ma_loai" placeholder="Ví dụ: MC, RM, MIC, LOA, CAP, OD" required>
                    <div class="form-text small">Viết tắt 2-5 ký tự in hoa đại diện chủng loại.</div>
                </div>

                <div class="mb-3">
                    <label for="modal_loai_ten" class="form-label fw-semibold small text-secondary">
                        Tên chủng loại <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="modal_loai_ten" name="ten_loai" placeholder="Ví dụ: Máy chiếu di động, Remote điều khiển" required>
                </div>

                <div class="mb-3">
                    <label for="modal_loai_mota" class="form-label fw-semibold small text-secondary">Mô tả đặc tính</label>
                    <textarea class="form-control" id="modal_loai_mota" name="mo_ta" rows="3" placeholder="Mô tả công năng hoặc tiêu chuẩn kỹ thuật"></textarea>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="modal_loai_hoatdong" name="dang_hoat_dong" value="1" checked>
                    <label class="form-check-label fw-semibold small" for="modal_loai_hoatdong">Hoạt động</label>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary fw-semibold" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary fw-semibold">Lưu loại tài sản</button>
            </div>
        </form>
    </div>
</div>

<script>
function moModalThem() {
    document.getElementById('modalLoaiTieuDe').innerText = 'Thêm loại tài sản mới';
    document.getElementById('modal_loai_id').value = '';
    document.getElementById('modal_loai_ma').value = '';
    document.getElementById('modal_loai_ma').readOnly = false;
    document.getElementById('modal_loai_ten').value = '';
    document.getElementById('modal_loai_mota').value = '';
    document.getElementById('modal_loai_hoatdong').checked = true;
}

function moModalSua(loai) {
    document.getElementById('modalLoaiTieuDe').innerText = 'Chỉnh sửa loại: ' + loai.ma_loai;
    document.getElementById('modal_loai_id').value = loai.id;
    document.getElementById('modal_loai_ma').value = loai.ma_loai;
    document.getElementById('modal_loai_ten').value = loai.ten_loai;
    document.getElementById('modal_loai_mota').value = loai.mo_ta || '';
    document.getElementById('modal_loai_hoatdong').checked = parseInt(loai.dang_hoat_dong, 10) === 1;

    const modal = new bootstrap.Modal(document.getElementById('modalLoai'));
    modal.show();
}
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
