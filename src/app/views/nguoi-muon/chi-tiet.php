<?php
/**
 * Giao diện Hồ sơ chi tiết Người mượn & Toàn bộ Lịch sử Mượn trả - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Hồ sơ người mượn: ' . ($nguoiMuon['ho_ten'] ?? '');
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<!-- Tiêu đề trang & Nút tác vụ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang mb-0">Hồ sơ người mượn: <?= e($nguoiMuon['ho_ten']) ?></h1>
        <div class="mo-ta-phu">Thông tin cá nhân và lịch sử tất cả các giao dịch mượn - trả tài sản.</div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('nguoi-muon/sua.php?id=' . (int)$nguoiMuon['id']) ?>" class="btn btn-phu">
            Chỉnh sửa hồ sơ
        </a>
        <a href="<?= url('nguoi-muon/index.php') ?>" class="btn btn-phu">
            Quay lại danh sách
        </a>
    </div>
</div>

<!-- Thẻ tóm tắt thông tin người mượn -->
<div class="the-noi-dung p-4 mb-4">
    <div class="row align-items-center">
        <div class="col-12 col-md-8">
            <div class="mb-2">
                <h3 class="tieu-de-khoi mb-1"><?= e($nguoiMuon['ho_ten']) ?></h3>
                <div class="mo-ta-phu">
                    Mã số trường: <strong><?= e($nguoiMuon['ma_so_truong']) ?></strong> &bull; Mã hệ thống: <?= e($nguoiMuon['ma_nguoi_muon']) ?>
                </div>
            </div>

            <div class="row g-2 mt-2 pt-2 border-top small">
                <div class="col-sm-4">
                    <span class="text-muted">Đối tượng:</span>
                    <strong class="text-primary ms-1">
                        <?= $nguoiMuon['loai_nguoi_muon'] === 'GIANG_VIEN' ? 'Giảng viên' : 'Sinh viên' ?>
                    </strong>
                </div>
                <div class="col-sm-4">
                    <span class="text-muted">Lớp / Khoa:</span>
                    <strong class="text-dark ms-1"><?= e($nguoiMuon['lop_don_vi'] ?: '—') ?></strong>
                </div>
                <div class="col-sm-4">
                    <span class="text-muted">Số điện thoại:</span>
                    <strong class="text-dark ms-1"><?= e($nguoiMuon['so_dien_thoai'] ?: '—') ?></strong>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4 mt-3 mt-md-0 text-md-end border-start-md ps-md-4">
            <div class="mo-ta-phu mb-2">Trạng thái giữ thiết bị hiện tại:</div>
            <?php if ((int)$nguoiMuon['so_thiet_bi_dang_muon'] > 0): ?>
                <div class="huy-hieu-tt huy-hieu-tt-canh-bao fs-6 px-3 py-2 mb-2 d-inline-block">
                    Đang giữ <?= (int)$nguoiMuon['so_thiet_bi_dang_muon'] ?> thiết bị chưa trả
                </div>
            <?php else: ?>
                <div class="huy-hieu-tt huy-hieu-tt-thanh-cong fs-6 px-3 py-2 mb-2 d-inline-block">
                    Không giữ thiết bị nào
                </div>
            <?php endif; ?>

            <?php if (co_quyen('nghiep_vu_muon_tra')): ?>
            <div>
                <a href="<?= url('muon-tra/muon.php?ma_so=' . urlencode($nguoiMuon['ma_so_truong'])) ?>" class="btn btn-sm btn-chinh">
                    Lập phiếu mượn mới
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lịch sử mượn trả tài sản -->
<div class="the-noi-dung mb-4">
    <div class="the-noi-dung-dau d-flex justify-content-between align-items-center">
        <h3 class="tieu-de-khoi mb-0">Lịch sử mượn trả tài sản</h3>
        <span class="badge bg-light text-secondary border">
            Tổng cộng <?= count($lichSuMuon) ?> lượt mượn
        </span>
    </div>

    <div class="the-noi-dung-than p-0">
        <?php if (empty($lichSuMuon)): ?>
        <div class="text-center py-4 text-muted">
            Người mượn này chưa từng phát sinh giao dịch mượn thiết bị nào trên hệ thống.
        </div>
        <?php else: ?>
        <div class="accordion accordion-flush" id="accordionLichSu">
            <?php foreach ($lichSuMuon as $idx => $pm): ?>
            <div class="accordion-item border-bottom">
                <h2 class="accordion-header" id="heading_<?= $pm['id'] ?>">
                    <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= $pm['id'] ?>" aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>">
                        <div class="d-flex flex-wrap align-items-center justify-content-between w-100 pe-3 gap-2">
                            <div>
                                <span class="badge bg-light text-primary border font-monospace me-2">
                                    <?= e($pm['ma_phieu']) ?>
                                </span>
                                <span class="fw-semibold text-dark">
                                    Phòng <?= e($pm['ten_phong']) ?> (<?= e($pm['ma_phong']) ?>)
                                </span>
                                <span class="text-muted small ms-2">
                                    &bull; Bàn trực: <?= e($pm['ten_phong_truc']) ?>
                                </span>
                            </div>
                            <div class="small">
                                <span class="text-muted me-3">
                                    Mượn lúc: <strong><?= dinh_dang_ngay_gio($pm['thoi_gian_muon']) ?></strong>
                                </span>
                                <?php if ($pm['trang_thai'] === 'DA_TRA'): ?>
                                    <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Đã trả đủ</span>
                                <?php else: ?>
                                    <span class="huy-hieu-tt huy-hieu-tt-canh-bao">Đang mượn</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </button>
                </h2>
                <div id="collapse_<?= $pm['id'] ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" data-bs-parent="#accordionLichSu">
                    <div class="accordion-body bg-light p-3">
                        <div class="row g-2 mb-2 small text-muted">
                            <div class="col-sm-4">
                                Hạn trả dự kiến: <strong class="text-danger"><?= dinh_dang_ngay_gio($pm['han_tra']) ?></strong>
                            </div>
                            <div class="col-sm-4">
                                Cán bộ lập phiếu: <strong><?= e($pm['ten_nhan_vien_lap']) ?></strong>
                            </div>
                            <div class="col-sm-4">
                                Mục đích: <?= e($pm['muc_dich_su_dung'] ?: 'Giảng dạy / học tập') ?>
                            </div>
                        </div>

                        <!-- Bảng chi tiết thiết bị trong phiếu -->
                        <div class="table-responsive bg-white rounded border">
                            <table class="bang-du-lieu mb-0">
                                <thead>
                                    <tr>
                                        <th>Mã thiết bị</th>
                                        <th>Tên tài sản</th>
                                        <th>Mã QR</th>
                                        <th>Tình trạng khi mượn</th>
                                        <th>Thời gian trả</th>
                                        <th>Tình trạng khi trả</th>
                                        <th>Cán bộ nhận</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pm['chi_tiet'] as $ct): ?>
                                    <tr>
                                        <td class="font-monospace fw-semibold text-primary">
                                            <?= e($ct['ma_tai_san']) ?>
                                        </td>
                                        <td class="fw-medium">
                                            <?= e($ct['ten_tai_san']) ?>
                                        </td>
                                        <td class="font-monospace small text-muted">
                                            <?= e($ct['ma_qr']) ?>
                                        </td>
                                        <td>
                                            <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">
                                                <?= e(TEN_TINH_TRANG[$ct['tinh_trang_khi_muon']] ?? $ct['tinh_trang_khi_muon']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($ct['thoi_gian_tra'] !== null): ?>
                                                <span class="text-success fw-semibold"><?= dinh_dang_ngay_gio($ct['thoi_gian_tra']) ?></span>
                                            <?php else: ?>
                                                <span class="huy-hieu-tt huy-hieu-tt-canh-bao">Chưa trả</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($ct['tinh_trang_khi_tra'] !== null): ?>
                                                <?php if ($ct['tinh_trang_khi_tra'] === 'HU_HONG'): ?>
                                                    <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Hư hỏng</span>
                                                    <?php if (!empty($ct['ghi_chu_tra'])): ?>
                                                        <div class="text-danger small fst-italic"><?= e($ct['ghi_chu_tra']) ?></div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Tốt</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= e($ct['ten_nhan_vien_nhan'] ?: '—') ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
