<?php
/**
 * Giao diện: Mở đợt kiểm kê tài sản mới cho phòng trực (G8).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="container-fluid py-3">
    <!-- Breadcrumb & Tiêu đề -->
    <div class="row align-items-center mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('tong-quan.php') ?>" class="text-decoration-none">Tổng quan</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('kiem-ke/index.php') ?>" class="text-decoration-none">Kiểm kê tài sản</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Mở đợt mới</li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold d-flex align-items-center gap-2">
                <span>📋 Mở đợt kiểm kê tài sản</span>
            </h1>
            <p class="text-muted small mb-0">Chụp dữ liệu sổ sách tài sản của bàn trực và thiết lập bàn làm việc đối chiếu thực tế.</p>
        </div>
        <div class="col-md-4 text-md-end mt-2 mt-md-0">
            <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
    </div>

    <?php if ($loi !== ''): ?>
        <div class="alert alert-danger shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><?= e($loi) ?></div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- CỘT TRÁI: FORM KHỞI TẠO ĐỢT -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-primary d-flex align-items-center gap-2">
                        <i class="bi bi-clipboard2-check"></i> Thông tin thiết lập đợt kiểm kê
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form action="<?= url('kiem-ke/tao.php') ?>" method="POST" id="formTaoKiemKe">
                        <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">

                        <!-- Phòng trực -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Bàn trực áp dụng kiểm kê <span class="text-danger">*</span></label>
                            <?php if ($nguoiDung['vai_tro'] === VAI_TRO_QUAN_TRI): ?>
                                <select name="phong_truc_id" class="form-select" required>
                                    <?php foreach ($danhSachPhongTruc as $pt): ?>
                                        <option value="<?= (int)$pt['id'] ?>" <?= ($phongTrucId === (int)$pt['id']) ? 'selected' : '' ?>>
                                            <?= e($pt['ten_phong_truc']) ?> (<?= e($pt['ma_phong_truc']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Quản trị viên có thể chọn bàn trực muốn tổ chức đợt kiểm kê.</div>
                            <?php else: ?>
                                <input type="hidden" name="phong_truc_id" value="<?= $phongTrucId ?>">
                                <input type="text" class="form-control bg-light" readonly 
                                       value="<?= e($nguoiDung['ten_phong_truc'] ?? ('Phòng trực #' . $phongTrucId)) ?>">
                                <div class="form-text">Đợt kiểm kê sẽ được mở tự động cho bàn trực bạn đang phụ trách.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Tên đợt kiểm kê -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tên đợt kiểm kê <span class="text-danger">*</span></label>
                            <input type="text" name="ten_dot_kiem_ke" class="form-control" required maxlength="150"
                                   placeholder="Ví dụ: Kiểm kê thiết bị cuối buổi trực sáng 09/11/2026"
                                   value="<?= e($duLieu['ten_dot_kiem_ke']) ?>">
                        </div>

                        <!-- Loại kiểm kê -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Hình thức kiểm kê <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded h-100 <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_CUOI_BUOI) ? 'border-primary bg-primary bg-opacity-10' : '' ?>">
                                        <input class="form-check-input" type="radio" name="loai_kiem_ke" id="loaiCuoiBuoi" 
                                               value="<?= LOAI_KIEM_KE_CUOI_BUOI ?>" <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_CUOI_BUOI) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold d-block" for="loaiCuoiBuoi">
                                            🌙 Cuối buổi trực
                                        </label>
                                        <small class="text-muted d-block mt-1">Đối chiếu nhanh cuối ca trực sau khi nhận lại thiết bị.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded h-100 <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_DINH_KY) ? 'border-primary bg-primary bg-opacity-10' : '' ?>">
                                        <input class="form-check-input" type="radio" name="loai_kiem_ke" id="loaiDinhKy" 
                                               value="<?= LOAI_KIEM_KE_DINH_KY ?>" <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_DINH_KY) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold d-block" for="loaiDinhKy">
                                            📅 Định kỳ
                                        </label>
                                        <small class="text-muted d-block mt-1">Đợt kiểm kê định kỳ hàng tháng, quý hoặc cuối kỳ học.</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check p-3 border rounded h-100 <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_DOT_XUAT) ? 'border-primary bg-primary bg-opacity-10' : '' ?>">
                                        <input class="form-check-input" type="radio" name="loai_kiem_ke" id="loaiDotXuat" 
                                               value="<?= LOAI_KIEM_KE_DOT_XUAT ?>" <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_DOT_XUAT) ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-bold d-block" for="loaiDotXuat">
                                            ⚡ Đột xuất
                                        </label>
                                        <small class="text-muted d-block mt-1">Kiểm tra đột xuất theo yêu cầu của ban quản trị/thanh tra.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ghi chú / Phạm vi -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Ghi chú / Mục đích kiểm kê</label>
                            <textarea name="ghi_chu" class="form-control" rows="3" maxlength="2000"
                                      placeholder="Ghi chú chi tiết mục đích, thành phần tham gia hoặc lưu ý đặc biệt (nếu có)..."><?= e($duLieu['ghi_chu']) ?></textarea>
                            <div class="form-text">Tối đa 2000 ký tự.</div>
                        </div>

                        <!-- Nút hành động -->
                        <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                            <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-1"></i> Hủy bỏ
                            </a>
                            <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                                <i class="bi bi-play-circle me-1"></i> Bắt đầu mở đợt kiểm kê
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- CỘT PHẢI: QUY TRÌNH & LƯU Ý NGHIỆP VỤ -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-info"></i> Quy trình & Lưu ý kiểm kê tài sản
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-3 mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <strong>1</strong>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Chụp sổ sách tức thời (Snapshot)</h6>
                            <p class="text-muted small mb-0">Hệ thống sẽ ghi nhận trạng thái tại thời điểm mở đợt của tất cả tài sản thuộc bàn trực đang hoạt động vào sổ kiểm kê.</p>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <strong>2</strong>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Khóa tạm thời chức năng Mượn & Chuyển</h6>
                            <p class="text-muted small mb-0">Trong thời gian đợt kiểm kê đang diễn ra, bàn trực sẽ tạm ngừng lập phiếu mượn mới và điều chuyển thiết bị để đảm bảo tính toàn vẹn số liệu. <em>Việc thu hồi trả thiết bị vẫn diễn ra bình thường.</em></p>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 text-success p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <strong>3</strong>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Tài sản đang mượn</h6>
                            <p class="text-muted small mb-0">Các thiết bị đang có phiếu mượn chưa trả sẽ được tự động ghi nhận là <code>Đang mượn</code> và tính vào sổ sách, không bắt buộc kiểm tra thực tế tại chỗ.</p>
                        </div>
                    </div>

                    <div class="d-flex gap-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 text-danger p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                            <strong>4</strong>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Tự động phát sinh sự cố khi chốt đợt</h6>
                            <p class="text-muted small mb-0">Khi hoàn tất đợt kiểm kê, các trường hợp chênh lệch (Mất, Hư hỏng mới, Sai vị trí phòng học) sẽ được tự động tạo bản ghi sự cố để xử lý tiếp theo.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-light border shadow-sm">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-shield-check text-success fs-5"></i>
                    <h6 class="mb-0 fw-bold">Quy tắc độc quyền đợt kiểm kê</h6>
                </div>
                <p class="small text-muted mb-0">
                    Mỗi bàn trực tại một thời điểm chỉ được phép có duy nhất <strong>01 đợt kiểm kê đang diễn ra</strong>. Sau khi kết thúc hoặc hủy đợt này thì mới có thể bắt đầu đợt mới.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
