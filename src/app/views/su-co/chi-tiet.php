<?php
/**
 * Giao diện: Chi tiết sự cố tài sản & Tiếp nhận / Hoàn tất xử lý sự cố - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$suCo = $suCo ?? [];
$danhSachPhongHoc = $danhSachPhongHoc ?? [];

$tieuDeTrang = 'Chi tiết sự cố: ' . ($suCo['ma_su_co'] ?? '');
require dirname(__DIR__) . '/layout/dau-trang.php';

$chuaXuLy = (($suCo['trang_thai'] ?? '') === 'CHUA_XU_LY');
$dangXuLy = (($suCo['trang_thai'] ?? '') === 'DANG_XU_LY');
$daXuLy = (($suCo['trang_thai'] ?? '') === 'DA_XU_LY');
$coQuyenXuLy = co_quyen('xu_ly_su_co');
?>

<!-- Tiêu đề trang & Nút tác vụ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang mb-0">Sự cố: <?= e($suCo['ma_su_co']) ?></h1>
        <div class="mo-ta-phu">Theo dõi hiện trạng, tiếp nhận và ghi nhận biên bản xử lý khắc phục sự cố thiết bị.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('su-co/index.php') ?>" class="btn btn-phu">
            Quay lại
        </a>
        <?php if (!$daXuLy && $coQuyenXuLy): ?>
            <?php if ($chuaXuLy): ?>
                <form action="<?= url('su-co/chi-tiet.php?id=' . $suCo['id']) ?>" method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                    <input type="hidden" name="hanh_dong" value="tiep_nhan">
                    <button type="submit" class="btn btn-chinh">
                        Tiếp nhận xử lý
                    </button>
                </form>
            <?php endif; ?>
            <button type="button" class="btn btn-chinh" data-bs-toggle="modal" data-bs-target="#modalHoanTatXuLy">
                Hoàn tất khắc phục
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- CỘT TRÁI: CHI TIẾT SỰ CỐ & LỊCH SỬ KHẮC PHỤC -->
    <div class="col-12 col-lg-7">
        <!-- Thẻ 1: Thông tin sự cố -->
        <div class="the-noi-dung mb-3">
            <div class="the-noi-dung-dau d-flex justify-content-between align-items-center">
                <h3 class="tieu-de-khoi mb-0">Hiện trạng sự cố</h3>
                <div>
                    <?php if ($daXuLy): ?>
                        <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Đã xử lý xong</span>
                    <?php elseif ($dangXuLy): ?>
                        <span class="huy-hieu-tt huy-hieu-tt-canh-bao">Đang xử lý</span>
                    <?php else: ?>
                        <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Chưa xử lý</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="the-noi-dung-than p-4">
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Phân loại sự cố</div>
                        <div class="mt-1">
                            <?php if ($suCo['loai_su_co'] === 'HU_HONG'): ?>
                                <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Hư hỏng kỹ thuật</span>
                            <?php elseif ($suCo['loai_su_co'] === 'MAT'): ?>
                                <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Mất / Thất lạc</span>
                            <?php elseif ($suCo['loai_su_co'] === 'SAI_VI_TRI'): ?>
                                <span class="huy-hieu-tt huy-hieu-tt-canh-bao">Sai vị trí phòng</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-muted small">Nguồn phát sinh sự cố</div>
                        <div class="mt-1">
                            <?php if ($suCo['ma_phieu_muon']): ?>
                                <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$suCo['phieu_muon_id']) ?>" class="badge bg-light text-primary border text-decoration-none">
                                    Phiếu trả: <?= e($suCo['ma_phieu_muon']) ?>
                                </a>
                            <?php elseif ($suCo['ma_dot_kiem_ke']): ?>
                                <a href="<?= url('kiem-ke/chi-tiet.php?id=' . (int)$suCo['dot_kiem_ke_id']) ?>" class="badge bg-light text-warning-emphasis border text-decoration-none">
                                    Đợt kiểm kê: <?= e($suCo['ma_dot_kiem_ke']) ?>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border">
                                    Báo cáo trực tiếp
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-muted small">Thời gian phát hiện</div>
                        <div class="fw-semibold text-dark mt-1">
                            <?= dinh_dang_ngay_gio($suCo['thoi_gian_phat_hien']) ?>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="text-muted small">Cán bộ phát hiện</div>
                        <div class="fw-semibold text-dark mt-1">
                            <?= e($suCo['ho_ten_nguoi_lap']) ?> (<?= e($suCo['ma_nhan_vien_lap']) ?>)
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3">
                    <label class="nhan-truong">Mô tả hiện trạng sự cố:</label>
                    <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 0.95rem;"><?= e($suCo['mo_ta']) ?></div>
                </div>
            </div>
        </div>

        <!-- Thẻ 2: Kết quả xử lý / khắc phục (nếu đã xử lý) -->
        <?php if ($daXuLy): ?>
            <div class="the-noi-dung mb-3 border-start border-success border-4">
                <div class="the-noi-dung-dau">
                    <h3 class="tieu-de-khoi mb-0 text-success">Biên bản kết quả giải quyết & khắc phục</h3>
                </div>
                <div class="the-noi-dung-than p-4">
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
                                <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">
                                    <?= e(nhan_trang_thai_tai_san($suCo['trang_thai_tai_san'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-3">
                        <label class="nhan-truong">Biện pháp khắc phục / Kết quả giải quyết:</label>
                        <div class="p-3 bg-light rounded border text-dark" style="white-space: pre-wrap; font-size: 0.95rem;"><?= e($suCo['ket_qua_xu_ly']) ?></div>
                    </div>
                </div>
            </div>
        <?php elseif ($dangXuLy): ?>
            <div class="the-noi-dung p-3 mb-3 bg-light border-start border-warning border-4">
                <div class="fw-semibold text-warning-emphasis mb-1">Sự cố đang trong quá trình xử lý & khắc phục</div>
                <div class="small text-muted">
                    Thiết bị đang được gửi đi bảo hành, sửa chữa linh kiện hoặc điều chỉnh vị trí. Sau khi hoàn thành, hãy nhấn <strong>Hoàn tất khắc phục</strong> để cập nhật kết quả và phục hồi thiết bị.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- CỘT PHẢI: THÔNG TIN THIẾT BỊ & THAO TÁC XỬ LÝ -->
    <div class="col-12 col-lg-5">
        <!-- Thẻ 3: Thông tin thiết bị gặp sự cố -->
        <div class="the-noi-dung mb-3">
            <div class="the-noi-dung-dau d-flex justify-content-between align-items-center">
                <h3 class="tieu-de-khoi mb-0">Thông tin thiết bị</h3>
                <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$suCo['tai_san_id']) ?>" class="btn btn-sm btn-phu" target="_blank">
                    Hồ sơ thiết bị
                </a>
            </div>
            <div class="the-noi-dung-than p-4">
                <div class="mb-3">
                    <div class="fw-bold font-monospace text-primary fs-5"><?= e($suCo['ma_tai_san']) ?></div>
                    <div class="fw-semibold text-dark fs-6 mt-1"><?= e($suCo['ten_tai_san']) ?></div>
                    <div class="small text-muted font-monospace mt-1"><?= e($suCo['ma_qr']) ?></div>
                </div>

                <div class="row g-2 border-top pt-2 small">
                    <div class="col-6 text-muted">Loại thiết bị:</div>
                    <div class="col-6 fw-semibold text-end"><?= e($suCo['ten_loai_tai_san'] ?? 'Thiết bị') ?></div>

                    <div class="col-6 text-muted">Hãng / Model:</div>
                    <div class="col-6 fw-semibold text-end"><?= e(trim(($suCo['hang_san_xuat'] ?? '') . ' ' . ($suCo['model'] ?? ''))) ?: '—' ?></div>

                    <div class="col-6 text-muted">Bàn trực quản lý:</div>
                    <div class="col-6 fw-semibold text-end"><?= e($suCo['ten_phong_truc'] ?? 'Kho trực') ?></div>

                    <div class="col-6 text-muted">Vị trí phòng hiện tại:</div>
                    <div class="col-6 fw-semibold text-end"><?= e($suCo['ma_phong_hien_tai'] ?? 'Kho trực bàn trực') ?></div>

                    <div class="col-6 text-muted">Tình trạng máy:</div>
                    <div class="col-6 text-end">
                        <?php if ($suCo['tinh_trang_tai_san'] === 'TOT'): ?>
                            <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Tốt</span>
                        <?php else: ?>
                            <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Hư hỏng</span>
                        <?php endif; ?>
                    </div>

                    <div class="col-6 text-muted">Trạng thái máy:</div>
                    <div class="col-6 text-end">
                        <span class="badge bg-light text-dark border">
                            <?= e(nhan_trang_thai_tai_san($suCo['trang_thai_tai_san'])) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL HOÀN TẤT XỬ LÝ SỰ CỐ -->
<?php if (!$daXuLy && $coQuyenXuLy): ?>
<div class="modal fade" id="modalHoanTatXuLy" tabindex="-1" aria-labelledby="modalHoanTatXuLyLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalHoanTatXuLyLabel">
                    Hoàn tất xử lý sự cố [<?= e($suCo['ma_su_co']) ?>]
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('su-co/chi-tiet.php?id=' . $suCo['id']) ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                <input type="hidden" name="hanh_dong" value="hoan_tat">

                <div class="modal-body p-4">
                    <div class="mo-ta-phu mb-3">
                        Ghi nhận chi tiết kết quả sửa chữa, giải trình hoặc thay thế linh kiện cho thiết bị <strong><?= e($suCo['ten_tai_san']) ?></strong>.
                    </div>

                    <!-- Kết quả xử lý -->
                    <div class="mb-3">
                        <label class="nhan-truong">Chi tiết kết quả xử lý / Khắc phục <span class="text-danger">*</span></label>
                        <textarea name="ket_qua_xu_ly" class="o-nhap" rows="4" required maxlength="2000"
                                  placeholder="Mô tả cụ thể: Đã thay bóng đèn máy chiếu mới, đã hàn lại cổng cáp HDMI, đã tìm thấy thiết bị tại phòng B201, hoặc đề nghị thanh lý do hỏng hoàn toàn..."></textarea>
                    </div>

                    <div class="row g-3">
                        <!-- Tình trạng tài sản mới -->
                        <div class="col-md-6">
                            <label class="nhan-truong">Tình trạng hoạt động sau xử lý <span class="text-danger">*</span></label>
                            <select name="tinh_trang_tai_san" class="o-nhap">
                                <option value="TOT" selected>Hoạt động tốt (Bình thường)</option>
                                <option value="HU_HONG">Vẫn hư hỏng (Cần thanh lý/bãi bỏ)</option>
                            </select>
                        </div>

                        <!-- Trạng thái tài sản mới -->
                        <div class="col-md-6">
                            <label class="nhan-truong">Trạng thái đưa vào sử dụng <span class="text-danger">*</span></label>
                            <select name="trang_thai_tai_san" class="o-nhap">
                                <option value="SAN_SANG" selected>Sẵn sàng cho mượn (Hoạt động lại)</option>
                                <option value="NGUNG_SU_DUNG">Ngừng sử dụng (Thanh lý / Bãi bỏ / Mất vĩnh viễn)</option>
                                <option value="BAO_TRI">Tiếp tục bảo trì (Chờ linh kiện)</option>
                            </select>
                        </div>

                        <!-- Vị trí phòng học mới (nếu cần cập nhật vị trí) -->
                        <div class="col-md-12">
                            <label class="nhan-truong">Cập nhật vị trí phòng học mới (tùy chọn)</label>
                            <select name="phong_hoc_moi_id" class="o-nhap">
                                <option value="">-- Giữ nguyên vị trí hiện tại (<?= e($suCo['ma_phong_hien_tai'] ?? 'Kho trực') ?>) --</option>
                                <?php foreach ($danhSachPhongHoc as $ph): ?>
                                    <option value="<?= (int)$ph['id'] ?>">
                                        <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-phu" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-chinh">
                        Lưu kết quả xử lý
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
