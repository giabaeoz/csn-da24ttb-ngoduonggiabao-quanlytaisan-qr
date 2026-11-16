<?php
/**
 * Giao diện Biểu mẫu Thêm mới / Chỉnh sửa Người mượn - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var bool $laThemMoi
 * @var array<string, mixed> $nguoiMuon
 * @var string $thongBaoLoi
 */

$tieuDeTrang = $laThemMoi ? 'Thêm người mượn mới' : 'Chỉnh sửa hồ sơ người mượn';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="thanh-cong-cu-trang">
    <div class="d-flex align-items-center gap-3">
        <div class="hop-icon-trang">
            <?= icon('users') ?>
        </div>
        <div>
            <h1 class="tieu-de-trang mb-1">
                <?= $laThemMoi ? 'Thêm người mượn mới' : 'Chỉnh sửa hồ sơ người mượn' ?>
            </h1>
            <div class="mo-ta-phu">
                <?= $laThemMoi ? 'Đăng ký hồ sơ sinh viên hoặc giảng viên sử dụng thiết bị.' : 'Cập nhật thông tin định danh và liên hệ của người mượn.' ?>
            </div>
        </div>
    </div>
    <div>
        <a href="<?= url('nguoi-muon/index.php') ?>" class="btn btn-phu">
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

        <div class="the-noi-dung p-0">
            <div class="the-noi-dung-dau d-flex align-items-center gap-2">
                <div class="hop-icon-dau">
                    <?= icon('users', 'bieu-tuong-sm') ?>
                </div>
                <h3 class="tieu-de-khoi mb-0">Thông tin cá nhân & đơn vị</h3>
            </div>

            <form action="<?= $laThemMoi ? url('nguoi-muon/them.php') : url('nguoi-muon/sua.php?id=' . (int)$nguoiMuon['id']) ?>" method="POST" class="the-noi-dung-than p-4">
                <?= csrf_field() ?>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="ma_so_truong" class="nhan-truong">
                            Mã số trường (MSSV / MSGV) <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="o-nhap" id="ma_so_truong" name="ma_so_truong" 
                               value="<?= e($nguoiMuon['ma_so_truong'] ?? '') ?>" 
                               placeholder="Ví dụ: 110124001 hoặc GV001" required autofocus>
                        
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="ho_ten" class="nhan-truong">
                            Họ và tên đầy đủ <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="o-nhap" id="ho_ten" name="ho_ten" 
                               value="<?= e($nguoiMuon['ho_ten'] ?? '') ?>" 
                               placeholder="Ví dụ: Nguyễn Văn An" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="nhan-truong">
                            Đối tượng người mượn <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-4 pt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="loai_nguoi_muon" id="loai_sv" value="SINH_VIEN" 
                                       <?= ($nguoiMuon['loai_nguoi_muon'] ?? 'SINH_VIEN') === 'SINH_VIEN' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold text-primary" for="loai_sv">
                                    Sinh viên
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="loai_nguoi_muon" id="loai_gv" value="GIANG_VIEN" 
                                       <?= ($nguoiMuon['loai_nguoi_muon'] ?? '') === 'GIANG_VIEN' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold text-secondary" for="loai_gv">
                                    Giảng viên
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="lop_don_vi" class="nhan-truong">
                            Lớp sinh hoạt / Khoa bộ môn
                        </label>
                        <input type="text" class="o-nhap" id="lop_don_vi" name="lop_don_vi" 
                               value="<?= e($nguoiMuon['lop_don_vi'] ?? '') ?>" 
                               placeholder="Ví dụ: DA21TTA hoặc Khoa CNTT">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label for="so_dien_thoai" class="nhan-truong">
                            Số điện thoại liên lạc
                        </label>
                        <input type="text" class="o-nhap" id="so_dien_thoai" name="so_dien_thoai" 
                               value="<?= e($nguoiMuon['so_dien_thoai'] ?? '') ?>" 
                               placeholder="Ví dụ: 0912345678">
                        <div class="mo-ta-phu mt-1">Dùng để liên hệ nhắc trả hoặc đối chiếu khi mượn tài sản.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="ma_nguoi_muon" class="nhan-truong">
                            Mã hồ sơ hệ thống
                        </label>
                        <input type="text" class="o-nhap font-monospace" id="ma_nguoi_muon" name="ma_nguoi_muon" 
                               value="<?= e($nguoiMuon['ma_nguoi_muon'] ?? '') ?>" 
                               placeholder="Tự động sinh (Ví dụ: NM-110124001)">
                        <div class="mo-ta-phu mt-1">Để trống để hệ thống tự động sinh theo tiền tố NM-[MSSV].</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end align-items-center gap-2 pt-3 border-top">
                    <a href="<?= url('nguoi-muon/index.php') ?>" class="btn btn-phu">
                        <?= icon('x', 'bieu-tuong-sm') ?>
                        <span>Hủy bỏ</span>
                    </a>
                    <button type="submit" class="btn btn-chinh">
                        <?= icon('save', 'bieu-tuong-sm') ?>
                        <span><?= $laThemMoi ? 'Lưu người mượn' : 'Cập nhật thay đổi' ?></span>
                    </button>
                </div>
            </form>
        </div>

</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
