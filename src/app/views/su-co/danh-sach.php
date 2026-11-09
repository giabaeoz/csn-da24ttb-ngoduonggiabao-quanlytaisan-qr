<?php
/**
 * Giao diện: Danh sách các sự cố tài sản & Quản lý bảo trì (G9).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">
            <span class="text-danger">⚠️</span> Quản lý sự cố tài sản & Bảo trì
        </h1>
        <p class="text-muted small mb-0">Theo dõi, tiếp nhận và ghi nhận kết quả xử lý sửa chữa thiết bị hư hỏng, mất mát, sai vị trí.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <?php if (co_quyen('ghi_nhan_su_co')): ?>
            <a href="<?= url('su-co/bao-cao.php') ?>" class="btn btn-danger shadow-sm">
                <i class="bi bi-exclamation-triangle me-1"></i> Báo cáo sự cố mới
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- THẺ THỐNG KÊ NHANH 4 TILES -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
            <div class="card-body p-3">
                <div class="text-muted small text-uppercase fw-bold">Tổng sự cố</div>
                <div class="h3 mb-0 fw-bold text-primary"><?= (int)$thongKe['tong_so'] ?></div>
                <div class="text-muted small mt-1">Ghi nhận toàn hệ thống</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm border-start border-danger border-4 h-100 <?= ($thongKe['so_chua_xu_ly'] > 0) ? 'bg-danger bg-opacity-10' : '' ?>">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small text-uppercase fw-bold">Chưa xử lý</div>
                    <?php if ($thongKe['so_chua_xu_ly'] > 0): ?>
                        <span class="spinner-grow spinner-grow-sm text-danger" role="status"></span>
                    <?php endif; ?>
                </div>
                <div class="h3 mb-0 fw-bold text-danger"><?= (int)$thongKe['so_chua_xu_ly'] ?></div>
                <div class="text-muted small mt-1">Cần tiếp nhận khắc phục</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm border-start border-warning border-4 h-100">
            <div class="card-body p-3">
                <div class="text-muted small text-uppercase fw-bold">Đang xử lý / Bảo trì</div>
                <div class="h3 mb-0 fw-bold text-warning"><?= (int)$thongKe['so_dang_xu_ly'] ?></div>
                <div class="text-muted small mt-1">Đang bảo hành / sửa chữa</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
            <div class="card-body p-3">
                <div class="text-muted small text-uppercase fw-bold">Đã xử lý xong</div>
                <div class="h3 mb-0 fw-bold text-success"><?= (int)$thongKe['so_da_xu_ly'] ?></div>
                <div class="text-muted small mt-1">Đã phục hồi / thanh lý</div>
            </div>
        </div>
    </div>
</div>

<!-- BỘ LỌC TÌM KIẾM SỰ CỐ -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="<?= url('su-co/index.php') ?>" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Từ khóa tìm kiếm</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="tu_khoa" class="form-control" placeholder="Mã SC, mã TS, tên, người lập..." 
                           value="<?= e($boLoc['tu_khoa']) ?>">
                </div>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Loại sự cố</label>
                <select name="loai_su_co" class="form-select form-select-sm">
                    <option value="">-- Tất cả loại sự cố --</option>
                    <option value="HU_HONG" <?= ($boLoc['loai_su_co'] === 'HU_HONG') ? 'selected' : '' ?>>Hư hỏng</option>
                    <option value="MAT" <?= ($boLoc['loai_su_co'] === 'MAT') ? 'selected' : '' ?>>Mất / Thất lạc</option>
                    <option value="SAI_VI_TRI" <?= ($boLoc['loai_su_co'] === 'SAI_VI_TRI') ? 'selected' : '' ?>>Sai vị trí phòng</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Trạng thái xử lý</label>
                <select name="trang_thai" class="form-select form-select-sm">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="CHUA_XU_LY" <?= ($boLoc['trang_thai'] === 'CHUA_XU_LY') ? 'selected' : '' ?>>Chưa xử lý</option>
                    <option value="DANG_XU_LY" <?= ($boLoc['trang_thai'] === 'DANG_XU_LY') ? 'selected' : '' ?>>Đang xử lý</option>
                    <option value="DA_XU_LY" <?= ($boLoc['trang_thai'] === 'DA_XU_LY') ? 'selected' : '' ?>>Đã xử lý xong</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Phòng trực quản lý</label>
                <select name="phong_truc_id" class="form-select form-select-sm" <?= ($nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN && $phongTrucId > 0) ? 'disabled' : '' ?>>
                    <?php if ($nguoiDung['vai_tro'] !== VAI_TRO_NHAN_VIEN): ?>
                        <option value="">-- Tất cả bàn trực --</option>
                    <?php endif; ?>
                    <?php foreach ($danhSachPhongTruc as $pt): ?>
                        <option value="<?= (int)$pt['id'] ?>" <?= ((int)$boLoc['phong_truc_id'] === (int)$pt['id']) ? 'selected' : '' ?>>
                            <?= e($pt['ten_phong_truc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Lọc dữ liệu
                </button>
                <a href="<?= url('su-co/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Đặt lại bộ lọc">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- BẢNG DANH SÁCH SỰ CỐ -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            Danh sách sự cố tài sản (<span class="text-primary fw-bold"><?= $tongSo ?></span>)
        </h6>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary text-uppercase small">
                <tr>
                    <th style="width: 45px;" class="ps-3">#</th>
                    <th style="width: 140px;">Mã sự cố</th>
                    <th>Thiết bị gặp sự cố</th>
                    <th style="width: 140px;">Bàn trực / Vị trí</th>
                    <th style="width: 120px;">Phân loại</th>
                    <th style="width: 130px;">Nguồn phát sinh</th>
                    <th style="width: 130px;">Trạng thái</th>
                    <th style="width: 140px;">Thời gian phát hiện</th>
                    <th style="width: 90px;" class="text-end pe-3">Chi tiết</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachSuCo)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-shield-check fs-1 d-block text-success mb-2"></i>
                            Không có sự cố nào khớp với điều kiện lọc hiện tại.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danhSachSuCo as $idx => $sc): ?>
                        <tr class="<?= ($sc['trang_thai'] === 'CHUA_XU_LY') ? 'table-danger bg-opacity-25' : '' ?>">
                            <td class="ps-3 text-muted"><?= ($trangHienTai - 1) * $soMoiTrang + $idx + 1 ?></td>
                            <td>
                                <a href="<?= url('su-co/chi-tiet.php?id=' . $sc['id']) ?>" class="fw-bold font-monospace text-decoration-none">
                                    <?= e($sc['ma_su_co']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($sc['ten_tai_san']) ?></div>
                                <small class="text-muted font-monospace">
                                    <?= e($sc['ma_tai_san']) ?> &bull; <?= e($sc['ten_loai_tai_san'] ?? 'Thiết bị') ?>
                                </small>
                            </td>
                            <td>
                                <div><small class="fw-semibold"><?= e($sc['ten_phong_truc'] ?? 'Kho trực') ?></small></div>
                                <?php if ($sc['ma_phong_hien_tai']): ?>
                                    <small class="text-muted"><i class="bi bi-geo-alt"></i> <?= e($sc['ma_phong_hien_tai']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sc['loai_su_co'] === 'HU_HONG'): ?>
                                    <span class="badge bg-danger"><i class="bi bi-tools me-1"></i> Hư hỏng</span>
                                <?php elseif ($sc['loai_su_co'] === 'MAT'): ?>
                                    <span class="badge bg-dark"><i class="bi bi-question-diamond me-1"></i> Mất mát</span>
                                <?php elseif ($sc['loai_su_co'] === 'SAI_VI_TRI'): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-geo me-1"></i> Sai vị trí</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sc['ma_phieu_muon']): ?>
                                    <span class="badge bg-light text-primary border" title="Phát hiện khi hoàn trả mượn">
                                        <i class="bi bi-box-arrow-in-left me-1"></i> <?= e($sc['ma_phieu_muon']) ?>
                                    </span>
                                <?php elseif ($sc['ma_dot_kiem_ke']): ?>
                                    <span class="badge bg-light text-warning-emphasis border" title="Phát hiện trong đợt kiểm kê">
                                        <i class="bi bi-clipboard2-check me-1"></i> <?= e($sc['ma_dot_kiem_ke']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border">
                                        <i class="bi bi-pencil me-1"></i> Báo cáo trực tiếp
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($sc['trang_thai'] === 'CHUA_XU_LY'): ?>
                                    <span class="badge bg-danger"><i class="bi bi-hourglass-split me-1"></i> Chưa xử lý</span>
                                <?php elseif ($sc['trang_thai'] === 'DANG_XU_LY'): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-gear-wide-connected me-1"></i> Đang xử lý</span>
                                <?php elseif ($sc['trang_thai'] === 'DA_XU_LY'): ?>
                                    <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i> Đã xử lý</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-muted"><?= dinh_dang_ngay_gio($sc['thoi_gian_phat_hien']) ?></div>
                                <small class="text-muted d-block text-truncate" style="max-width: 140px;" title="<?= e($sc['ho_ten_nguoi_lap']) ?>">
                                    bởi <?= e($sc['ho_ten_nguoi_lap']) ?>
                                </small>
                            </td>
                            <td class="text-end pe-3">
                                <a href="<?= url('su-co/chi-tiet.php?id=' . $sc['id']) ?>" class="btn btn-sm btn-outline-primary" title="Xem chi tiết sự cố">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PHÂN TRANG -->
    <?php if ($tongTrang > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="small text-muted">Trang <?= $trangHienTai ?> / <?= $tongTrang ?> (Tổng <?= $tongSo ?> bản ghi)</span>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $tongTrang; $p++): ?>
                    <li class="page-item <?= ($p === $trangHienTai) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('su-co/index.php?' . http_build_query(array_merge($boLoc, ['trang' => $p]))) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>

