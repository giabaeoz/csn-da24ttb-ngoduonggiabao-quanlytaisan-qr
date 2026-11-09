<?php
/**
 * Giao diện: Chi tiết sự cố tài sản & Tiếp nhận / Hoàn tất xử lý sự cố (G9).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';

$chuaXuLy = ($suCo['trang_thai'] === 'CHUA_XU_LY');
$dangXuLy = ($suCo['trang_thai'] === 'DANG_XU_LY');
$daXuLy = ($suCo['trang_thai'] === 'DA_XU_LY');
$coQuyenXuLy = co_quyen('xu_ly_su_co');
?>

<div class="container-fluid py-3">
    <!-- Breadcrumb & Tiêu đề -->
    <div class="row align-items-center mb-3">
        <div class="col-md-7">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('tong-quan.php') ?>" class="text-decoration-none">Tổng quan</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('su-co/index.php') ?>" class="text-decoration-none">Sự cố & Bảo trì</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($suCo['ma_su_co']) ?></li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold d-flex align-items-center gap-2 flex-wrap">
                <span>⚠️ Sự cố: <?= e($suCo['ma_su_co']) ?></span>
                <?php if ($daXuLy): ?>
                    <span class="badge bg-success font-monospace fs-6"><i class="bi bi-check2-circle me-1"></i> Đã xử lý xong</span>
                <?php elseif ($dangXuLy): ?>
                    <span class="badge bg-warning text-dark font-monospace fs-6"><i class="bi bi-gear-wide-connected me-1"></i> Đang xử lý</span>
                <?php else: ?>
                    <span class="badge bg-danger font-monospace fs-6"><i class="bi bi-hourglass-split me-1"></i> Chưa xử lý</span>
                <?php endif; ?>
            </h1>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2 flex-wrap">
            <a href="<?= url('su-co/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
            <?php if (!$daXuLy && $coQuyenXuLy): ?>
                <?php if ($chuaXuLy): ?>
                    <form action="<?= url('su-co/chi-tiet.php?id=' . $suCo['id']) ?>" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                        <input type="hidden" name="hanh_dong" value="tiep_nhan">
                        <button type="submit" class="btn btn-warning fw-semibold shadow-sm">
                            <i class="bi bi-play-circle me-1"></i> Tiếp nhận xử lý
                        </button>
                    </form>
                <?php endif; ?>
                <button type="button" class="btn btn-success fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalHoanTatXuLy">
                    <i class="bi bi-check2-all me-1"></i> Hoàn tất khắc phục
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- CỘT TRÁI: CHI TIẾT SỰ CỐ & LỊCH SỬ KHẮC PHỤC -->
        <div class="col-lg-7">
            <!-- Thẻ 1: Thông tin sự cố -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-card-text text-primary"></i> Diễn biến & Hiện trạng sự cố
                    </h6>
                </div>
                <div class="card-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <div class="text-muted small">Phân loại sự cố</div>
                            <div class="mt-1">
                                <?php if ($suCo['loai_su_co'] === 'HU_HONG'): ?>
                                    <span class="badge bg-danger fs-6"><i class="bi bi-tools me-1"></i> Hư hỏng kỹ thuật</span>
                                <?php elseif ($suCo['loai_su_co'] === 'MAT'): ?>
                                    <span class="badge bg-dark fs-6"><i class="bi bi-question-diamond me-1"></i> Mất / Thất lạc</span>
                                <?php elseif ($suCo['loai_su_co'] === 'SAI_VI_TRI'): ?>
                                    <span class="badge bg-warning text-dark fs-6"><i class="bi bi-geo me-1"></i> Sai vị trí phòng</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="text-muted small">Nguồn phát sinh sự cố</div>
                            <div class="mt-1">
                                <?php if ($suCo['ma_phieu_muon']): ?>
                                    <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$suCo['phieu_muon_id']) ?>" class="badge bg-light text-primary border fs-6 text-decoration-none">
                                        <i class="bi bi-box-arrow-in-left me-1"></i> Qua phiếu trả: <?= e($suCo['ma_phieu_muon']) ?>
                                    </a>
                                <?php elseif ($suCo['ma_dot_kiem_ke']): ?>
                                    <a href="<?= url('kiem-ke/chi-tiet.php?id=' . (int)$suCo['dot_kiem_ke_id']) ?>" class="badge bg-light text-warning-emphasis border fs-6 text-decoration-none">
                                        <i class="bi bi-clipboard2-check me-1"></i> Qua đợt kiểm kê: <?= e($suCo['ma_dot_kiem_ke']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border fs-6">
                                        <i class="bi bi-pencil me-1"></i> Báo cáo trực tiếp tại bàn trực
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="text-muted small">Thời gian phát hiện</div>
                            <div class="fw-semibold text-dark mt-1">
                                <i class="bi bi-clock me-1 text-secondary"></i> <?= dinh_dang_ngay_gio($suCo['thoi_gian_phat_hien']) ?>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="text-muted small">Cán bộ lập biên bản / phát hiện</div>
                            <div class="fw-semibold text-dark mt-1">
                                <i class="bi bi-person me-1 text-secondary"></i> <?= e($suCo['ho_ten_nguoi_lap']) ?> (<?= e($suCo['ma_nhan_vien_lap']) ?>)
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <label class="form-label text-muted small fw-bold text-uppercase">Mô tả hiện trạng sự cố:</label>
                        <div class="p-3 bg-light rounded-3 border text-dark" style="white-space: pre-wrap; font-size: 0.95rem;"><?= e($suCo['mo_ta']) ?></div>
                    </div>
                </div>
            </div>

            <!-- Thẻ 2: Kết quả xử lý / khắc phục (nếu đã xử lý) -->
            <?php if ($daXuLy): ?>
                <div class="card border-0 shadow-sm border-start border-success border-4 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-success d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Biên bản kết quả giải quyết & khắc phục
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <div class="text-muted small">Thời gian hoàn tất xử lý</div>
                                <div class="fw-bold text-dark mt-1">
                                    <?= dinh_dang_ngay_gio($suCo['thoi_gian_xu_ly']) ?>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="text-muted small">Trạng thái hiện tại của thiết bị</div>
                                <div class="mt-1">
                                    <span class="badge bg-success-subtle text-success border border-success fw-bold">
                                        <?= e(nhan_trang_thai_tai_san($suCo['trang_thai_tai_san'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3">
                            <label class="form-label text-muted small fw-bold text-uppercase">Biện pháp khắc phục / Kết quả giải quyết:</label>
                            <div class="p-3 bg-light rounded-3 border text-dark" style="white-space: pre-wrap; font-size: 0.95rem;"><?= e($suCo['ket_qua_xu_ly']) ?></div>
                        </div>
                    </div>
                </div>
            <?php elseif ($dangXuLy): ?>
                <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center gap-3 p-3 mb-4 rounded-3">
                    <div class="spinner-border text-warning" role="status"></div>
                    <div>
                        <h6 class="alert-heading fw-bold mb-1">Sự cố đang trong quá trình xử lý & khắc phục!</h6>
                        <p class="small mb-0 text-muted">
                            Thiết bị đang được gửi đi bảo hành, sửa chữa linh kiện hoặc điều chỉnh vị trí. Sau khi hoàn thành, hãy nhấn <strong>Hoàn tất khắc phục</strong> để cập nhật kết quả và phục hồi thiết bị.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- CỘT PHẢI: THÔNG TIN THIẾT BỊ & THAO TÁC XỬ LÝ -->
        <div class="col-lg-5">
            <!-- Thẻ 3: Thông tin thiết bị gặp sự cố -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-laptop text-primary"></i> Thông tin thiết bị
                    </h6>
                    <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$suCo['tai_san_id']) ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                        Hồ sơ thiết bị <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <div class="fw-bold font-monospace text-primary fs-5"><?= e($suCo['ma_tai_san']) ?></div>
                        <div class="fw-semibold text-dark fs-6 mt-1"><?= e($suCo['ten_tai_san']) ?></div>
                        <div class="small text-muted font-monospace mt-1"><?= e($suCo['ma_qr']) ?></div>
                    </div>

                    <ul class="list-group list-group-flush border rounded-3 small">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Loại thiết bị:</span>
                            <span class="fw-semibold"><?= e($suCo['ten_loai_tai_san'] ?? 'Thiết bị') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Hãng / Model:</span>
                            <span class="fw-semibold"><?= e(trim(($suCo['hang_san_xuat'] ?? '') . ' ' . ($suCo['model'] ?? ''))) ?: '--' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Bàn trực quản lý:</span>
                            <span class="fw-semibold"><?= e($suCo['ten_phong_truc'] ?? 'Kho trực') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Vị trí phòng hiện tại:</span>
                            <span class="fw-semibold"><?= e($suCo['ma_phong_hien_tai'] ?? 'Kho trực bàn trực') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Tình trạng máy:</span>
                            <span>
                                <?php if ($suCo['tinh_trang_tai_san'] === 'TOT'): ?>
                                    <span class="badge bg-success-subtle text-success">Hoạt động tốt</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger">Hư hỏng</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Trạng thái máy:</span>
                            <span>
                                <span class="badge bg-secondary-subtle text-dark">
                                    <?= e(nhan_trang_thai_tai_san($suCo['trang_thai_tai_san'])) ?>
                                </span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Thẻ 4: Thao tác tiếp nhận & xử lý nhanh (dành cho quản lý/quản trị) -->
            <?php if (!$daXuLy && $coQuyenXuLy): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                            <i class="bi bi-tools text-warning"></i> Thao tác xử lý sự cố
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($chuaXuLy): ?>
                            <p class="small text-muted mb-3">
                                Sự cố này chưa được tiếp nhận xử lý. Bạn có thể bấm tiếp nhận để bắt đầu quá trình kiểm tra, sửa chữa hoặc bảo hành:
                            </p>
                            <form action="<?= url('su-co/chi-tiet.php?id=' . $suCo['id']) ?>" method="POST" class="mb-3">
                                <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                                <input type="hidden" name="hanh_dong" value="tiep_nhan">
                                <button type="submit" class="btn btn-warning w-100 fw-semibold">
                                    <i class="bi bi-play-circle me-1"></i> Bấm tiếp nhận xử lý sự cố
                                </button>
                            </form>
                            <div class="text-center text-muted small my-2">&mdash; HOẶC &mdash;</div>
                        <?php endif; ?>

                        <p class="small text-muted mb-3">
                            Nếu sự cố đã được khắc phục xong (đã sửa chữa, đã tìm thấy, hoặc bãi bỏ thanh lý), hãy hoàn tất sự cố:
                        </p>
                        <button type="button" class="btn btn-success w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalHoanTatXuLy">
                            <i class="bi bi-check2-all me-1"></i> Hoàn tất khắc phục sự cố
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL HOÀN TẤT XỬ LÝ SỰ CỐ -->
<?php if (!$daXuLy && $coQuyenXuLy): ?>
<div class="modal fade" id="modalHoanTatXuLy" tabindex="-1" aria-labelledby="modalHoanTatXuLyLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-3">
                <h6 class="modal-title fw-bold" id="modalHoanTatXuLyLabel">
                    <i class="bi bi-check2-circle me-1"></i> Hoàn tất xử lý sự cố [<?= e($suCo['ma_su_co']) ?>]
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('su-co/chi-tiet.php?id=' . $suCo['id']) ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                <input type="hidden" name="hanh_dong" value="hoan_tat">

                <div class="modal-body p-4">
                    <div class="alert alert-info small mb-3">
                        Ghi nhận chi tiết kết quả sửa chữa, giải trình hoặc thay thế linh kiện. Hệ thống sẽ tự động cập nhật thời gian xử lý và khôi phục trạng thái cho thiết bị <strong><?= e($suCo['ten_tai_san']) ?></strong>.
                    </div>

                    <!-- Kết quả xử lý -->
                    <div class="mb-3">
                        <label class="form-label fw-bold">Chi tiết kết quả xử lý / Sửa chữa / Khắc phục <span class="text-danger">*</span></label>
                        <textarea name="ket_qua_xu_ly" class="form-control" rows="4" required maxlength="2000"
                                  placeholder="Mô tả cụ thể: Đã thay bóng đèn máy chiếu mới, đã hàn lại cổng cáp HDMI, đã tìm thấy thiết bị tại phòng B201, hoặc đề nghị thanh lý do hỏng hoàn toàn..."></textarea>
                        <div class="form-text">Bắt buộc nhập kết quả, tối đa 2000 ký tự.</div>
                    </div>

                    <div class="row g-3">
                        <!-- Tình trạng tài sản mới -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tình trạng hoạt động sau xử lý <span class="text-danger">*</span></label>
                            <select name="tinh_trang_tai_san" class="form-select">
                                <option value="TOT" selected>Hoạt động tốt (Bình thường)</option>
                                <option value="HU_HONG">Vẫn hư hỏng (Cần thanh lý/bãi bỏ)</option>
                            </select>
                        </div>

                        <!-- Trạng thái tài sản mới -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Trạng thái đưa vào sử dụng <span class="text-danger">*</span></label>
                            <select name="trang_thai_tai_san" class="form-select">
                                <option value="SAN_SANG" selected>Sẵn sàng cho mượn (Hoạt động lại)</option>
                                <option value="NGUNG_SU_DUNG">Ngừng sử dụng (Thanh lý / Bãi bỏ / Mất vĩnh viễn)</option>
                                <option value="BAO_TRI">Tiếp tục bảo trì (Chờ linh kiện)</option>
                            </select>
                        </div>

                        <!-- Vị trí phòng học mới (nếu cần cập nhật vị trí) -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Cập nhật vị trí phòng học mới (tùy chọn)</label>
                            <select name="phong_hoc_moi_id" class="form-select">
                                <option value="">-- Giữ nguyên vị trí hiện tại (<?= e($suCo['ma_phong_hien_tai'] ?? 'Kho trực') ?>) --</option>
                                <?php foreach ($danhSachPhongHoc as $ph): ?>
                                    <option value="<?= (int)$ph['id'] ?>">
                                        <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Áp dụng khi thiết bị được bàn giao lại về đúng phòng sau sự cố Sai vị trí.</div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-success fw-semibold px-4">
                        <i class="bi bi-check-lg me-1"></i> Lưu kết quả xử lý
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
