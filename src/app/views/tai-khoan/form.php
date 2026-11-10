<?php
/**
 * Giao diện Biểu mẫu Thêm mới / Chỉnh sửa Tài khoản Cán bộ - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var bool $laThemMoi
 * @var array<string, mixed> $taiKhoan
 * @var list<array<string, mixed>> $danhSachPhongTruc
 * @var string $thongBaoLoi
 * @var int $nguoiThucHienId
 */

$tieuDeTrang = $laThemMoi ? 'Thêm tài khoản cán bộ' : 'Chỉnh sửa tài khoản cán bộ';
require dirname(__DIR__) . '/layout/dau-trang.php';

$laChinhMinh = (!$laThemMoi && (int)($taiKhoan['id'] ?? 0) === $nguoiThucHienId);
?>

<!-- Tiêu đề trang & Nút tác vụ -->
<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang mb-0">
            <?= $laThemMoi ? 'Thêm tài khoản cán bộ' : 'Chỉnh sửa tài khoản cán bộ' ?>
        </h1>
        <div class="mo-ta-phu">
            <?= $laThemMoi ? 'Khởi tạo tài khoản đăng nhập và phân quyền vận hành hệ thống.' : 'Cập nhật thông tin cán bộ, vai trò phân quyền và phòng trực phụ trách.' ?>
        </div>
    </div>
    <div>
        <a href="<?= url('tai-khoan/index.php') ?>" class="btn btn-phu">
            <?= icon('arrow-left', 'bieu-tuong-sm') ?>
            <span>Quay lại danh sách</span>
        </a>
    </div>
</div>

<div class="khung-bieu-mau">

        <?php if (!empty($thongBaoLoi)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <?= icon('alert-circle', 'bieu-tuong-md') ?>
                <div class="flex-grow-1"><strong>Lỗi thao tác:</strong> <?= e($thongBaoLoi) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="tieu-de-khoi mb-0">Thông tin tài khoản & phân quyền</h2>
            </div>

            <form action="<?= $laThemMoi ? url('tai-khoan/them.php') : url('tai-khoan/sua.php?id=' . (int)$taiKhoan['id']) ?>" method="POST" class="card-body p-4">
                <?= csrf_field() ?>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="ma_nhan_vien" class="form-label">
                            Mã nhân viên <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control text-uppercase font-monospace" id="ma_nhan_vien" name="ma_nhan_vien"
                               value="<?= e($taiKhoan['ma_nhan_vien'] ?? '') ?>"
                               placeholder="Ví dụ: NV-TRUC01, NV-QL01" required autofocus>
                        <div class="mo-ta-phu mt-1">Mã định danh duy nhất của cán bộ trong hệ thống.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="ten_dang_nhap" class="form-label">
                            Tên đăng nhập <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control font-monospace" id="ten_dang_nhap" name="ten_dang_nhap"
                               value="<?= e($taiKhoan['ten_dang_nhap'] ?? '') ?>"
                               placeholder="Ví dụ: nhanvien01, quanly01" required>
                        <div class="mo-ta-phu mt-1">Viết liền không dấu, từ 3 đến 50 ký tự.</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="ho_ten" class="form-label">
                            Họ và tên cán bộ <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="ho_ten" name="ho_ten"
                               value="<?= e($taiKhoan['ho_ten'] ?? '') ?>"
                               placeholder="Ví dụ: Nguyễn Văn An" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="so_dien_thoai" class="form-label">
                            Số điện thoại liên hệ
                        </label>
                        <input type="text" class="form-control" id="so_dien_thoai" name="so_dien_thoai"
                               value="<?= e($taiKhoan['so_dien_thoai'] ?? '') ?>"
                               placeholder="Ví dụ: 0901234567">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="vai_tro" class="form-label">
                            Vai trò hệ thống <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="vai_tro" name="vai_tro" onchange="capNhatYeuCauPhongTruc(this.value)" required>
                            <?php foreach (TEN_VAI_TRO as $maVt => $tenVt): ?>
                                <option value="<?= e($maVt) ?>" <?= (($taiKhoan['vai_tro'] ?? VAI_TRO_NHAN_VIEN) === $maVt) ? 'selected' : '' ?>>
                                    <?= e($tenVt) ?> (<?= e($maVt) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mo-ta-phu mt-1">Quyết định quyền hạn truy cập các chức năng trên hệ thống.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="phong_truc_id" class="form-label">
                            Phòng trực phân công <span class="text-danger" id="dauSaoPhongTruc">*</span>
                        </label>
                        <select class="form-select" id="phong_truc_id" name="phong_truc_id">
                            <option value="">-- Không cố định (Toàn hệ thống) --</option>
                            <?php foreach ($danhSachPhongTruc as $pt): ?>
                                <option value="<?= (int)$pt['id'] ?>" <?= ((int)($taiKhoan['phong_truc_id'] ?? 0) === (int)$pt['id']) ? 'selected' : '' ?>>
                                    <?= e($pt['ten_phong_truc']) ?> (<?= e($pt['ma_phong_truc']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mo-ta-phu mt-1" id="moTaPhongTruc">Bắt buộc chọn đối với tài khoản Nhân viên trực.</div>
                    </div>
                </div>

                <!-- Khối mật khẩu -->
                <div class="p-3 border rounded bg-light mb-3">
                    <div class="fw-semibold text-dark mb-1">
                        <?= $laThemMoi ? 'Mật khẩu khởi tạo' : 'Đổi mật khẩu (tùy chọn)' ?>
                    </div>
                    <div class="mo-ta-phu mb-3">
                        <?= $laThemMoi ? 'Nhập mật khẩu đăng nhập ban đầu cho cán bộ (tối thiểu 6 ký tự).' : 'Để trống cả hai ô nếu giữ nguyên mật khẩu hiện tại.' ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="mat_khau" class="form-label">
                                <?= $laThemMoi ? 'Mật khẩu' : 'Mật khẩu mới' ?>
                                <?php if ($laThemMoi): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>
                            <input type="password" class="form-control bg-white" id="mat_khau" name="mat_khau"
                                   minlength="6" placeholder="Tối thiểu 6 ký tự" <?= $laThemMoi ? 'required' : '' ?>>
                        </div>

                        <div class="col-12 col-md-6">
                            <label for="xac_nhan_mat_khau" class="form-label">
                                Xác nhận mật khẩu
                                <?php if ($laThemMoi): ?><span class="text-danger">*</span><?php endif; ?>
                            </label>
                            <input type="password" class="form-control bg-white" id="xac_nhan_mat_khau" name="xac_nhan_mat_khau"
                                   minlength="6" placeholder="Nhập lại mật khẩu" <?= $laThemMoi ? 'required' : '' ?>>
                        </div>
                    </div>
                </div>

                <!-- Trạng thái hoạt động -->
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="dang_hoat_dong" name="dang_hoat_dong" value="1"
                               <?= ((int)($taiKhoan['dang_hoat_dong'] ?? 1) === 1) ? 'checked' : '' ?>
                               <?= $laChinhMinh ? 'onclick="return false;"' : '' ?>>
                        <label class="form-check-label fw-semibold" for="dang_hoat_dong">
                            Cho phép tài khoản đăng nhập và hoạt động
                        </label>
                    </div>
                    <?php if ($laChinhMinh): ?>
                        <div class="mo-ta-phu mt-1">Tài khoản đang đăng nhập hiện tại luôn ở trạng thái hoạt động.</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end align-items-center gap-2 pt-3 border-top">
                    <a href="<?= url('tai-khoan/index.php') ?>" class="btn btn-phu">
                        <?= icon('x', 'bieu-tuong-sm') ?>
                        <span>Hủy bỏ</span>
                    </a>
                    <button type="submit" class="btn btn-chinh">
                        <?= icon('save', 'bieu-tuong-sm') ?>
                        <span><?= $laThemMoi ? 'Lưu tài khoản' : 'Cập nhật thay đổi' ?></span>
                    </button>
                </div>
            </form>
        </div>

</div>

<script>
function capNhatYeuCauPhongTruc(vaiTro) {
    const selectPhong = document.getElementById('phong_truc_id');
    const dauSao = document.getElementById('dauSaoPhongTruc');
    if (vaiTro === 'NHAN_VIEN') {
        selectPhong.required = true;
        dauSao.classList.remove('d-none');
    } else {
        selectPhong.required = false;
        dauSao.classList.add('d-none');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const selVaiTro = document.getElementById('vai_tro');
    if (selVaiTro) {
        capNhatYeuCauPhongTruc(selVaiTro.value);
    }
});
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
