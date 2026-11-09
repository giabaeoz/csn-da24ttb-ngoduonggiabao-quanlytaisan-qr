<?php
/**
 * Giao diện Hồ sơ chi tiết Người mượn & Toàn bộ Lịch sử Mượn trả.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Hồ sơ người mượn: ' . ($nguoiMuon['ho_ten'] ?? '');
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">📋 Chi tiết Hồ sơ Người mượn</h1>
        <p class="text-secondary small mb-0">Truy vết thông tin cá nhân và lịch sử tất cả các giao dịch mượn - trả tài sản.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('nguoi-muon/sua.php?id=' . (int)$nguoiMuon['id']) ?>" class="btn btn-outline-primary fw-semibold">
            ✏️ Chỉnh sửa hồ sơ
        </a>
        <a href="<?= url('nguoi-muon/index.php') ?>" class="btn btn-outline-secondary fw-semibold">
            ← Quay lại danh sách
        </a>
    </div>
</div>

<!-- Thẻ tóm tắt thông tin người mượn -->
<div class="card border-0 shadow-sm rounded-4 mb-4 bg-white overflow-hidden">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-12 col-md-8">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span class="fs-1">
                        <?= $nguoiMuon['loai_nguoi_muon'] === 'GIANG_VIEN' ? '👨‍🏫' : '👨‍🎓' ?>
                    </span>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark"><?= e($nguoiMuon['ho_ten']) ?></h4>
                        <div class="text-muted small">
                            Mã số: <strong><?= e($nguoiMuon['ma_so_truong']) ?></strong> &bull; Mã hệ thống: <?= e($nguoiMuon['ma_nguoi_muon']) ?>
                        </div>
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
                        <strong class="text-dark ms-1"><?= e($nguoiMuon['lop_don_vi'] ?: '-') ?></strong>
                    </div>
                    <div class="col-sm-4">
                        <span class="text-muted">Số điện thoại:</span>
                        <strong class="text-dark ms-1"><?= e($nguoiMuon['so_dien_thoai'] ?: '-') ?></strong>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4 mt-3 mt-md-0 text-md-end border-start-md ps-md-4">
                <div class="text-muted small mb-1">Trạng thái giữ thiết bị hiện tại:</div>
                <?php if ((int)$nguoiMuon['so_thiet_bi_dang_muon'] > 0): ?>
                    <div class="badge bg-warning text-dark fs-6 px-3 py-2 shadow-sm mb-2">
                        ⚠️ Đang giữ <?= (int)$nguoiMuon['so_thiet_bi_dang_muon'] ?> thiết bị chưa trả
                    </div>
                <?php else: ?>
                    <div class="badge bg-success bg-opacity-10 text-success fs-6 px-3 py-2 border mb-2">
                        ✅ Không giữ thiết bị nào
                    </div>
                <?php endif; ?>

                <?php if (co_quyen('nghiep_vu_muon_tra')): ?>
                <div>
                    <a href="<?= url('muon-tra/muon.php?ma_so=' . urlencode($nguoiMuon['ma_so_truong'])) ?>" class="btn btn-sm btn-primary fw-semibold shadow-sm">
                        📤 Lập phiếu mượn mới
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- LỊCH SỬ CÁC GIAO DỊCH MƯỢN TRẢ -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 text-dark">Lịch sử mượn trả tài sản</h5>
        <span class="badge bg-light text-muted border">
            Tổng cộng <?= count($lichSuMuon) ?> lượt mượn
        </span>
    </div>

    <div class="card-body p-0">
        <?php if (empty($lichSuMuon)): ?>
        <div class="text-center py-5 text-muted">
            <div class="fs-2 mb-2">📦</div>
            <p class="mb-0">Người mượn này chưa từng phát sinh giao dịch mượn thiết bị nào trên hệ thống.</p>
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
                                    🏫 Phòng <?= e($pm['ten_phong']) ?> (<?= e($pm['ma_phong']) ?>)
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
                                    <span class="badge bg-success">Đã trả đủ</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Đang mượn</span>
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
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light small">
                                    <tr>
                                        <th class="ps-2">Mã thiết bị</th>
                                        <th>Tên tài sản</th>
                                        <th>Mã QR</th>
                                        <th>Tình trạng khi mượn</th>
                                        <th>Thời gian trả</th>
                                        <th>Tình trạng khi trả</th>
                                        <th>Cán bộ nhận</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    <?php foreach ($pm['chi_tiet'] as $ct): ?>
                                    <tr>
                                        <td class="ps-2 font-monospace fw-bold text-primary">
                                            <?= e($ct['ma_tai_san']) ?>
                                        </td>
                                        <td class="fw-semibold">
                                            <?= e($ct['ten_tai_san']) ?>
                                        </td>
                                        <td class="font-monospace small text-muted">
                                            <?= e($ct['ma_qr']) ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-success bg-opacity-10 text-success">
                                                <?= e(TEN_TINH_TRANG[$ct['tinh_trang_khi_muon']] ?? $ct['tinh_trang_khi_muon']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($ct['thoi_gian_tra'] !== null): ?>
                                                <span class="text-success fw-semibold"><?= dinh_dang_ngay_gio($ct['thoi_gian_tra']) ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Chưa trả</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($ct['tinh_trang_khi_tra'] !== null): ?>
                                                <?php if ($ct['tinh_trang_khi_tra'] === 'HU_HONG'): ?>
                                                    <span class="badge bg-danger">Hư hỏng</span>
                                                    <?php if (!empty($ct['ghi_chu_tra'])): ?>
                                                        <div class="text-danger small fst-italic"><?= e($ct['ghi_chu_tra']) ?></div>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Tốt</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= e($ct['ten_nhan_vien_nhan'] ?: '-') ?>
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

