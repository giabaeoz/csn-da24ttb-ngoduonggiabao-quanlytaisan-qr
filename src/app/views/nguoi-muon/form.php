<?php
/**
 * Giao diện Biểu mẫu Thêm mới / Chỉnh sửa Người mượn.
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
            <?= $laThemMoi ? '➕ Thêm người mượn mới' : '✏️ Chỉnh sửa hồ sơ người mượn' ?>
        </h1>
        <p class="text-secondary small mb-0">
            <?= $laThemMoi ? 'Đăng ký hồ sơ sinh viên hoặc giảng viên sử dụng thiết bị.' : 'Cập nhật thông tin định danh và liên hệ của người mượn.' ?>
        </p>
    </div>
    <div>
        <a href="<?= url('nguoi-muon/index.php') ?>" class="btn btn-outline-secondary fw-semibold">
            ← Quay lại danh sách
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">

        <?php if (!empty($thongBaoLoi)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
            <strong>Lỗi thao tác:</strong> <?= e($thongBaoLoi) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="fw-bold mb-0 text-dark">Thông tin cá nhân & Đơn vị</h5>
            </div>

            <form action="<?= $laThemMoi ? url('nguoi-muon/them.php') : url('nguoi-muon/sua.php?id=' . (int)$nguoiMuon['id']) ?>" method="POST" class="card-body p-4">
                <?= csrf_field() ?>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="ma_so_truong" class="form-label fw-semibold small text-secondary">
                            Mã số trường (MSSV / MSGV) <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-lg fs-6" id="ma_so_truong" name="ma_so_truong" 
                               value="<?= e($nguoiMuon['ma_so_truong'] ?? '') ?>" 
                               placeholder="Ví dụ: 110124001 hoặc GV001" required autofocus>
                        <div class="form-text small">Mã định danh duy nhất trong trường Đại học / Cao đẳng.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="ho_ten" class="form-label fw-semibold small text-secondary">
                            Họ và tên đầy đủ <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control form-control-lg fs-6" id="ho_ten" name="ho_ten" 
                               value="<?= e($nguoiMuon['ho_ten'] ?? '') ?>" 
                               placeholder="Ví dụ: Nguyễn Văn An" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-semibold small text-secondary">
                            Đối tượng người mượn <span class="text-danger">*</span>
                        </label>
                        <div class="d-flex gap-4 pt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="loai_nguoi_muon" id="loai_sv" value="SINH_VIEN" 
                                       <?= ($nguoiMuon['loai_nguoi_muon'] ?? 'SINH_VIEN') === 'SINH_VIEN' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold text-primary" for="loai_sv">
                                    👨‍🎓 Sinh viên
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="loai_nguoi_muon" id="loai_gv" value="GIANG_VIEN" 
                                       <?= ($nguoiMuon['loai_nguoi_muon'] ?? '') === 'GIANG_VIEN' ? 'checked' : '' ?>>
                                <label class="form-check-label fw-semibold text-info" for="loai_gv">
                                    👨‍🏫 Giảng viên
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="lop_don_vi" class="form-label fw-semibold small text-secondary">
                            Lớp sinh hoạt / Khoa bộ môn
                        </label>
                        <input type="text" class="form-control" id="lop_don_vi" name="lop_don_vi" 
                               value="<?= e($nguoiMuon['lop_don_vi'] ?? '') ?>" 
                               placeholder="Ví dụ: DA21TTA hoặc Khoa CNTT">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label for="so_dien_thoai" class="form-label fw-semibold small text-secondary">
                            Số điện thoại liên lạc
                        </label>
                        <input type="text" class="form-control" id="so_dien_thoai" name="so_dien_thoai" 
                               value="<?= e($nguoiMuon['so_dien_thoai'] ?? '') ?>" 
                               placeholder="Ví dụ: 0912345678">
                        <div class="form-text small">Dùng để liên hệ nhắc trả hoặc đối chiếu khi mượn tài sản.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="ma_nguoi_muon" class="form-label fw-semibold small text-secondary">
                            Mã hồ sơ hệ thống
                        </label>
                        <input type="text" class="form-control" id="ma_nguoi_muon" name="ma_nguoi_muon" 
                               value="<?= e($nguoiMuon['ma_nguoi_muon'] ?? '') ?>" 
                               placeholder="Tự động sinh (Ví dụ: NM-110124001)">
                        <div class="form-text small">Để trống để hệ thống tự động sinh theo tiền tố NM-[MSSV].</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <a href="<?= url('nguoi-muon/index.php') ?>" class="btn btn-outline-secondary fw-semibold px-4">
                        Hủy bỏ
                    </a>
                    <button type="submit" class="btn btn-primary fw-semibold px-5 shadow-sm">
                        <?= $laThemMoi ? 'Lưu người mượn' : 'Cập nhật thay đổi' ?>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>

