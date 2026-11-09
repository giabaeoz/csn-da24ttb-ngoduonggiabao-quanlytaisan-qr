<?php
/**
 * Giao diện: Danh sách các đợt kiểm kê tài sản (G8).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">
            <span class="text-primary">🔍</span> Kiểm kê tài sản theo phòng trực
        </h1>
        <p class="text-muted small mb-0">Chụp sổ sách, đối chiếu thực tế bằng mã QR, phát hiện chênh lệch và tự động ghi nhận sự cố.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <?php if (co_quyen('mo_dot_kiem_ke') && $dotDangMo === null): ?>
            <a href="<?= url('kiem-ke/tao.php') ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Mở đợt kiểm kê mới
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Banner nhắc nhở nếu có đợt đang mở -->
<?php if ($dotDangMo !== null): ?>
    <div class="alert alert-warning border-0 shadow-sm d-flex justify-content-between align-items-center p-3 mb-4 rounded-3">
        <div class="d-flex align-items-center gap-3">
            <div class="spinner-grow text-warning" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
            <div>
                <h6 class="alert-heading fw-bold mb-1">
                    Đợt kiểm kê <span class="font-monospace text-dark">[<?= e($dotDangMo['ma_dot_kiem_ke']) ?>]</span> đang diễn ra!
                </h6>
                <div class="small text-muted">
                    <?= e($dotDangMo['ten_dot_kiem_ke']) ?> — Còn lại <strong><?= (int)($dotDangMo['thong_ke']['so_con_lai_can_kiem'] ?? 0) ?></strong> thiết bị cần đối chiếu thực tế.
                </div>
            </div>
        </div>
        <div>
            <a href="<?= url('kiem-ke/thuc-hien.php?id=' . (int)$dotDangMo['id']) ?>" class="btn btn-warning fw-semibold shadow-sm">
                <i class="bi bi-arrow-right-circle me-1"></i> Tiếp tục kiểm kê
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Bộ lọc tìm kiếm -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="<?= url('kiem-ke/index.php') ?>" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Từ khóa tìm kiếm</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="tu_khoa" class="form-control" placeholder="Mã đợt, tên đợt, cán bộ..." 
                           value="<?= e($boLoc['tu_khoa']) ?>">
                </div>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Phòng trực</label>
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

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Trạng thái đợt</label>
                <select name="trang_thai" class="form-select form-select-sm">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="<?= KIEM_KE_DANG_KIEM_KE ?>" <?= ($boLoc['trang_thai'] === KIEM_KE_DANG_KIEM_KE) ? 'selected' : '' ?>>Đang kiểm kê</option>
                    <option value="<?= KIEM_KE_HOAN_TAT ?>" <?= ($boLoc['trang_thai'] === KIEM_KE_HOAN_TAT) ? 'selected' : '' ?>>Hoàn tất</option>
                    <option value="<?= KIEM_KE_DA_HUY ?>" <?= ($boLoc['trang_thai'] === KIEM_KE_DA_HUY) ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Loại kiểm kê</label>
                <select name="loai_kiem_ke" class="form-select form-select-sm">
                    <option value="">-- Tất cả loại --</option>
                    <option value="<?= LOAI_KIEM_KE_CUOI_BUOI ?>" <?= ($boLoc['loai_kiem_ke'] === LOAI_KIEM_KE_CUOI_BUOI) ? 'selected' : '' ?>>Cuối buổi</option>
                    <option value="<?= LOAI_KIEM_KE_DINH_KY ?>" <?= ($boLoc['loai_kiem_ke'] === LOAI_KIEM_KE_DINH_KY) ? 'selected' : '' ?>>Định kỳ</option>
                    <option value="<?= LOAI_KIEM_KE_DOT_XUAT ?>" <?= ($boLoc['loai_kiem_ke'] === LOAI_KIEM_KE_DOT_XUAT) ? 'selected' : '' ?>>Đột xuất</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Từ ngày</label>
                <input type="date" name="tu_ngay" class="form-control form-control-sm" value="<?= e($boLoc['tu_ngay']) ?>">
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1" title="Lọc">
                    <i class="bi bi-filter"></i> Lọc
                </button>
                <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Đặt lại">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh sách các đợt kiểm kê -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            Danh sách đợt kiểm kê (Tổng số: <span class="text-primary"><?= (int)$tongSo ?></span>)
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary text-uppercase small">
                <tr>
                    <th class="ps-3" style="width: 140px;">Mã đợt</th>
                    <th>Tên đợt kiểm kê</th>
                    <th style="width: 130px;">Loại</th>
                    <th style="width: 160px;">Phòng trực</th>
                    <th style="width: 150px;">Thời gian</th>
                    <th style="width: 180px;">Tiến độ / Số lượng</th>
                    <th style="width: 120px;" class="text-center">Trạng thái</th>
                    <th class="text-end pe-3" style="width: 120px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachDotKiemKe)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-clipboard2-check fs-1 text-muted d-block mb-2"></i>
                            <p class="mb-0">Chưa có đợt kiểm kê nào phù hợp.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danhSachDotKiemKe as $item): ?>
                        <tr>
                            <td class="ps-3">
                                <span class="font-monospace fw-bold text-primary">
                                    <?= e($item['ma_dot_kiem_ke']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small"><?= e($item['ten_dot_kiem_ke']) ?></div>
                                <div class="small text-muted">Lập bởi: <?= e($item['ho_ten_nguoi_lap']) ?></div>
                            </td>
                            <td>
                                <?php if ($item['loai_kiem_ke'] === LOAI_KIEM_KE_CUOI_BUOI): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">Cuối buổi</span>
                                <?php elseif ($item['loai_kiem_ke'] === LOAI_KIEM_KE_DINH_KY): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Định kỳ</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Đột xuất</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-dark">
                                <?= e($item['ten_phong_truc']) ?>
                            </td>
                            <td class="small text-muted">
                                <div><i class="bi bi-play-circle me-1 text-success"></i><?= e(f_ngay_gio($item['thoi_gian_bat_dau'])) ?></div>
                                <?php if (!empty($item['thoi_gian_ket_thuc'])): ?>
                                    <div><i class="bi bi-stop-circle me-1 text-secondary"></i><?= e(f_ngay_gio($item['thoi_gian_ket_thuc'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small text-dark mb-1">
                                    Tổng: <strong><?= (int)$item['tong_tai_san_so_sach'] ?></strong> thiết bị
                                </div>
                                <div class="d-flex gap-1">
                                    <span class="badge bg-success" title="Có mặt"><?= (int)$item['so_tim_thay'] ?></span>
                                    <span class="badge bg-danger" title="Chưa tìm thấy"><?= (int)$item['so_chua_tim_thay'] ?></span>
                                    <span class="badge bg-warning text-dark" title="Đang mượn"><?= (int)$item['so_dang_muon'] ?></span>
                                    <span class="badge bg-secondary" title="Chưa kiểm"><?= (int)$item['so_chua_kiem_tra'] ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($item['trang_thai'] === KIEM_KE_DANG_KIEM_KE): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i>Đang kiểm</span>
                                <?php elseif ($item['trang_thai'] === KIEM_KE_HOAN_TAT): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Hoàn tất</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Đã hủy</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <?php if ($item['trang_thai'] === KIEM_KE_DANG_KIEM_KE): ?>
                                    <a href="<?= url('kiem-ke/thuc-hien.php?id=' . (int)$item['id']) ?>" 
                                       class="btn btn-sm btn-primary" title="Thực hiện kiểm kê">
                                        <i class="bi bi-play-fill"></i> Kiểm tra
                                    </a>
                                <?php else: ?>
                                    <a href="<?= url('kiem-ke/chi-tiet.php?id=' . (int)$item['id']) ?>" 
                                       class="btn btn-sm btn-outline-secondary" title="Xem chi tiết & Báo cáo">
                                        <i class="bi bi-eye"></i> Báo cáo
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <?php if ($tongTrang > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="small text-muted">Trang <?= (int)$trangHienTai ?> / <?= (int)$tongTrang ?></span>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <?php if ($trangHienTai > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= url('kiem-ke/index.php?' . http_build_query(array_merge($boLoc, ['trang' => $trangHienTai - 1]))) ?>">« Trước</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $trangHienTai - 2); $i <= min($tongTrang, $trangHienTai + 2); $i++): ?>
                        <li class="page-item <?= ($i === $trangHienTai) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('kiem-ke/index.php?' . http_build_query(array_merge($boLoc, ['trang' => $i]))) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($trangHienTai < $tongTrang): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= url('kiem-ke/index.php?' . http_build_query(array_merge($boLoc, ['trang' => $trangHienTai + 1]))) ?>">Sau »</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
