<?php

/**
 * Giao diện Quản lý Chủng loại tài sản - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var list<array<string, mixed>> $danhSachLoai
 */

$tieuDeTrang = 'Quản lý loại tài sản';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="thanh-cong-cu-trang mb-3">
    <div>
        <h1 class="tieu-de-trang mb-0">Quản lý danh mục</h1>
        
    </div>
    <button type="button" class="btn btn-chinh" data-bs-toggle="modal" data-bs-target="#modalLoai" onclick="moModalThem()">
        <?= icon('plus', 'bieu-tuong-sm') ?>
        <span>Thêm loại tài sản</span>
    </button>
</div>

<ul class="nav nav-tabs mb-3 border-bottom">
    <li class="nav-item">
        <a class="nav-link text-secondary fw-semibold" href="<?= url('danh-muc/phong-hoc.php') ?>">
            Phòng học
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-secondary fw-semibold" href="<?= url('danh-muc/phong-truc.php') ?>">
            Phòng trực
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active fw-semibold" href="<?= url('danh-muc/loai-tai-san.php') ?>">
            Loại tài sản
        </a>
    </li>
</ul>

<div class="the-noi-dung mb-4">
    <div class="the-noi-dung-dau">
        <h3 class="tieu-de-khoi mb-0">Danh mục chủng loại thiết bị</h3>
    </div>
    <div class="the-noi-dung-than p-0">
        <div class="table-responsive">
            <table class="bang-du-lieu mb-0">
                <thead>
                    <tr>
                        <th class="cot-120">Mã loại</th>
                        <th class="cot-min-200">Tên loại tài sản</th>
                        <th>Mô tả quy cách</th>
                        <th class="text-center cot-140">Số lượng tài sản</th>
                        <th class="text-center cot-140">Trạng thái</th>
                        <th class="text-end cot-160">Thao tác</th>
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
                                <td class="fw-semibold text-primary">
                                    <span class="badge bg-light text-primary border">
                                        <?= e($loai['ma_loai']) ?>
                                    </span>
                                </td>
                                <td class="fw-medium text-dark">
                                    <?= e($loai['ten_loai']) ?>
                                </td>
                                <td class="text-secondary small">
                                    <?= e($loai['mo_ta'] ?: '-') ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-primary border px-2 py-1">
                                        <?= (int)$loai['so_tai_san'] ?> thiết bị
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$loai['dang_hoat_dong'] === 1): ?>
                                        <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Đang hoạt động</span>
                                    <?php else: ?>
                                        <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Tạm tắt</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <?php
                                        $chiTietLoai = [
                                            ['nhan' => 'Mã loại tài sản', 'gia_tri' => $loai['ma_loai'] ?? ''],
                                            ['nhan' => 'Tên chủng loại', 'gia_tri' => $loai['ten_loai'] ?? ''],
                                            ['nhan' => 'Tổng số tài sản', 'gia_tri' => (int)($loai['so_tai_san'] ?? 0) . ' thiết bị'],
                                            ['nhan' => 'Trạng thái', 'gia_tri' => (int)($loai['dang_hoat_dong'] ?? 0) === 1 ? 'Đang dùng' : 'Tạm tắt'],
                                            ['nhan' => 'Mô tả / Đặc điểm', 'gia_tri' => ($loai['mo_ta'] ?? '') ?: '---'],
                                        ];
                                        ?>
                                        <button type="button" class="btn-bieu-tuong" aria-label="Xem" title="Xem"
                                            onclick='xemChiTietNhanh(<?= json_encode("Loại tài sản: " . ($loai["ten_loai"] ?? "")) ?>, <?= json_encode($chiTietLoai, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            <?= icon('eye', 'bieu-tuong-sm') ?>
                                        </button>
                                        <button type="button" class="btn-bieu-tuong" aria-label="Sửa" title="Sửa"
                                            onclick='moModalSua(<?= json_encode($loai, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            <?= icon('pencil', 'bieu-tuong-sm') ?>
                                        </button>

                                        <form action="<?= url('danh-muc/loai-tai-san.php') ?>" method="POST" class="d-inline" data-xac-nhan="Bạn có chắc muốn đổi trạng thái loại tài sản này?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="hanh_dong" value="doi_trang_thai">
                                            <input type="hidden" name="id" value="<?= (int)$loai['id'] ?>">
                                            <?php $nhanKhoaMoLoai = (int)$loai['dang_hoat_dong'] === 1 ? 'Khóa' : 'Mở'; ?>
                                            <button type="submit" class="btn-bieu-tuong" aria-label="<?= $nhanKhoaMoLoai ?>" title="<?= $nhanKhoaMoLoai ?>">
                                                <?= icon((int)$loai['dang_hoat_dong'] === 1 ? 'lock' : 'rotate-ccw', 'bieu-tuong-sm') ?>
                                            </button>
                                        </form>

                                        <?php if ((int)$loai['so_tai_san'] === 0): ?>
                                            <form action="<?= url('danh-muc/loai-tai-san.php') ?>" method="POST" class="d-inline" data-xac-nhan="Bạn có chắc muốn XÓA loại tài sản này?">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="hanh_dong" value="xoa">
                                                <input type="hidden" name="id" value="<?= (int)$loai['id'] ?>">
                                                <button type="submit" class="btn-bieu-tuong btn-bieu-tuong-xoa text-danger" aria-label="Xóa" title="Xóa">
                                                    <?= icon('trash-2', 'bieu-tuong-sm') ?>
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

<div class="modal fade" id="modalLoai" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="<?= url('danh-muc/loai-tai-san.php') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="hanh_dong" value="luu">
            <input type="hidden" name="id" id="modal_loai_id" value="">

            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalLoaiTieuDe">Thêm loại tài sản</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <label for="modal_loai_ma" class="nhan-truong">
                        Mã loại tài sản <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="o-nhap text-uppercase" id="modal_loai_ma" name="ma_loai" placeholder="Ví dụ: MC, RM, MIC, LOA, CAP, OD" required>
                    
                </div>

                <div class="mb-3">
                    <label for="modal_loai_ten" class="nhan-truong">
                        Tên chủng loại <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="o-nhap" id="modal_loai_ten" name="ten_loai" placeholder="Ví dụ: Máy chiếu di động, Remote điều khiển" required>
                </div>

                <div class="mb-3">
                    <label for="modal_loai_mota" class="nhan-truong">Mô tả đặc tính</label>
                    <textarea class="o-nhap" id="modal_loai_mota" name="mo_ta" rows="3" placeholder="Mô tả công năng hoặc tiêu chuẩn kỹ thuật"></textarea>
                </div>

                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="modal_loai_hoatdong" name="dang_hoat_dong" value="1" checked>
                    <label class="form-check-label fw-semibold small" for="modal_loai_hoatdong">Hoạt động</label>
                </div>
            </div>

            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-phu" data-bs-dismiss="modal">
                    <?= icon('x', 'bieu-tuong-sm') ?>
                    <span>Hủy</span>
                </button>
                <button type="submit" class="btn btn-chinh">
                    <?= icon('save', 'bieu-tuong-sm') ?>
                    <span>Lưu loại tài sản</span>
                </button>
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