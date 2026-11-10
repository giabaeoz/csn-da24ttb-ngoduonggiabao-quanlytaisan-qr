<?php
/**
 * Giao diện: Mở đợt kiểm kê tài sản mới cho phòng trực (G8).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var list<array<string, mixed>> $danhSachPhongTruc
 * @var array<string, string> $duLieu
 * @var string $loi
 * @var array<string, mixed> $nguoiDung
 * @var int $phongTrucId
 */

$tieuDeTrang = 'Mở đợt kiểm kê';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang">Mở đợt kiểm kê tài sản</h1>
        <div class="mo-ta-phu">Chụp dữ liệu sổ sách tài sản của bàn trực và thiết lập bàn làm việc đối chiếu thực tế.</div>
    </div>
    <div>
        <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-phu">
            <?= icon('arrow-left', 'bieu-tuong-sm') ?>
            <span>Quay lại danh sách</span>
        </a>
    </div>
</div>

<?php if ($loi !== ''): ?>
    <div class="alert alert-danger mb-4" role="alert">
        <?= icon('alert-circle', 'bieu-tuong-md') ?>
        <div class="flex-grow-1"><?= e($loi) ?></div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card the-noi-dung">
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="tieu-de-khoi mb-0">
                    Thông tin thiết lập đợt kiểm kê
                </h2>
            </div>
            <div class="card-body p-4">
                <form action="<?= url('kiem-ke/tao.php') ?>" method="POST" id="formTaoKiemKe">
                    <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="form-label">Bàn trực áp dụng kiểm kê <span class="text-danger">*</span></label>
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

                    <div class="mb-3">
                        <label class="form-label">Tên đợt kiểm kê <span class="text-danger">*</span></label>
                        <input type="text" name="ten_dot_kiem_ke" class="form-control" required maxlength="150"
                               placeholder="Ví dụ: Kiểm kê thiết bị cuối buổi trực sáng"
                               value="<?= e($duLieu['ten_dot_kiem_ke']) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Hình thức kiểm kê <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="card h-100 p-3 border con-tro-tay <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_CUOI_BUOI) ? 'border-primary' : '' ?>">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="radio" name="loai_kiem_ke" id="loaiCuoiBuoi" 
                                               value="<?= LOAI_KIEM_KE_CUOI_BUOI ?>" <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_CUOI_BUOI) ? 'checked' : '' ?>>
                                        <span class="form-check-label fw-semibold d-block">
                                            Cuối buổi trực
                                        </span>
                                    </div>
                                    <small class="mo-ta-phu d-block mt-2">Đối chiếu nhanh cuối ca trực sau khi nhận lại thiết bị.</small>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="card h-100 p-3 border con-tro-tay <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_DINH_KY) ? 'border-primary' : '' ?>">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="radio" name="loai_kiem_ke" id="loaiDinhKy" 
                                               value="<?= LOAI_KIEM_KE_DINH_KY ?>" <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_DINH_KY) ? 'checked' : '' ?>>
                                        <span class="form-check-label fw-semibold d-block">
                                            Định kỳ
                                        </span>
                                    </div>
                                    <small class="mo-ta-phu d-block mt-2">Đợt kiểm kê định kỳ hàng tháng, quý hoặc kết thúc học kỳ.</small>
                                </label>
                            </div>
                            <div class="col-md-4">
                                <label class="card h-100 p-3 border con-tro-tay <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_DOT_XUAT) ? 'border-primary' : '' ?>">
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="radio" name="loai_kiem_ke" id="loaiDotXuat" 
                                               value="<?= LOAI_KIEM_KE_DOT_XUAT ?>" <?= ($duLieu['loai_kiem_ke'] === LOAI_KIEM_KE_DOT_XUAT) ? 'checked' : '' ?>>
                                        <span class="form-check-label fw-semibold d-block">
                                            Đột xuất
                                        </span>
                                    </div>
                                    <small class="mo-ta-phu d-block mt-2">Kiểm tra đột xuất theo yêu cầu của ban quản lý thiết bị.</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Ghi chú / Mục đích kiểm kê</label>
                        <textarea name="ghi_chu" class="form-control" rows="3" maxlength="2000"
                                  placeholder="Ghi chú chi tiết mục đích hoặc lưu ý đặc biệt (nếu có)..."><?= e($duLieu['ghi_chu']) ?></textarea>
                        <div class="form-text">Tối đa 2000 ký tự.</div>
                    </div>

                    <div class="d-flex align-items-center justify-content-end gap-2 pt-3 border-top">
                        <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-phu">
                            <?= icon('x', 'bieu-tuong-sm') ?>
                            <span>Hủy bỏ</span>
                        </a>
                        <button type="submit" class="btn btn-chinh">
                            <?= icon('save', 'bieu-tuong-sm') ?>
                            <span>Bắt đầu mở đợt kiểm kê</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card the-noi-dung mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h2 class="tieu-de-khoi mb-0">
                    Quy trình & Lưu ý kiểm kê tài sản
                </h2>
            </div>
            <div class="card-body">
                <div class="d-flex gap-3 mb-3">
                    <div class="badge bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center vong-so-buoc">
                        1
                    </div>
                    <div>
                        <div class="fw-semibold mb-1">Chụp sổ sách tức thời (Snapshot)</div>
                        <div class="mo-ta-phu">Hệ thống sẽ ghi nhận trạng thái tại thời điểm mở đợt của tất cả tài sản thuộc bàn trực vào sổ kiểm kê.</div>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <div class="badge bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center vong-so-buoc">
                        2
                    </div>
                    <div>
                        <div class="fw-semibold mb-1">Khóa tạm thời chức năng Mượn & Chuyển</div>
                        <div class="mo-ta-phu">Trong thời gian đợt kiểm kê đang diễn ra, bàn trực tạm ngừng lập phiếu mượn mới và điều chuyển thiết bị để đảm bảo tính toàn vẹn số liệu. Hoạt động thu hồi trả thiết bị vẫn diễn ra bình thường.</div>
                    </div>
                </div>

                <div class="d-flex gap-3 mb-3">
                    <div class="badge bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center vong-so-buoc">
                        3
                    </div>
                    <div>
                        <div class="fw-semibold mb-1">Tài sản đang mượn</div>
                        <div class="mo-ta-phu">Các thiết bị đang có phiếu mượn chưa trả sẽ được tự động ghi nhận là "Đang mượn" và tính vào sổ sách, không bắt buộc kiểm tra thực tế tại chỗ.</div>
                    </div>
                </div>

                <div class="d-flex gap-3">
                    <div class="badge bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center vong-so-buoc">
                        4
                    </div>
                    <div>
                        <div class="fw-semibold mb-1">Tự động phát sinh sự cố khi chốt đợt</div>
                        <div class="mo-ta-phu">Khi hoàn tất đợt kiểm kê, các trường hợp chênh lệch (Mất, Hư hỏng mới, Sai vị trí phòng học) sẽ được tự động tạo bản ghi sự cố để xử lý tiếp theo.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card the-noi-dung p-3">
            <div class="fw-semibold mb-1">Quy tắc độc quyền đợt kiểm kê</div>
            <div class="mo-ta-phu">
                Mỗi bàn trực tại một thời điểm chỉ được phép có duy nhất 01 đợt kiểm kê đang diễn ra. Sau khi kết thúc hoặc hủy đợt này thì mới có thể bắt đầu đợt mới.
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
