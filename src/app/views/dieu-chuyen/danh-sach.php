<?php
/**
 * Giao diện: Danh sách lịch sử điều chuyển thiết bị (G7).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">
            <span class="text-primary">🔄</span> Điều chuyển thiết bị giữa các phòng học
        </h1>
        <p class="text-muted small mb-0">Quản lý lịch sử luân chuyển thiết bị, cập nhật vị trí và kiểm soát tính tương thích.</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0 gap-2">
        <?php if (co_quyen('tao_dieu_chuyen')): ?>
            <a href="<?= url('dieu-chuyen/them.php') ?>" class="btn btn-primary shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Tạo phiếu điều chuyển mới
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form action="<?= url('dieu-chuyen/index.php') ?>" method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Từ khóa tìm kiếm</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" name="tu_khoa" class="form-control" placeholder="Mã phiếu, tên cán bộ..." 
                           value="<?= e($boLoc['tu_khoa']) ?>">
                </div>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Phòng học đi (nguồn)</label>
                <select name="phong_hoc_di_id" class="form-select form-select-sm">
                    <option value="">-- Tất cả phòng đi --</option>
                    <?php foreach ($danhSachPhongHoc as $ph): ?>
                        <option value="<?= (int)$ph['id'] ?>" <?= ((int)$boLoc['phong_hoc_di_id'] === (int)$ph['id']) ? 'selected' : '' ?>>
                            <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Phòng học đến (đích)</label>
                <select name="phong_hoc_den_id" class="form-select form-select-sm">
                    <option value="">-- Tất cả phòng đến --</option>
                    <?php foreach ($danhSachPhongHoc as $ph): ?>
                        <option value="<?= (int)$ph['id'] ?>" <?= ((int)$boLoc['phong_hoc_den_id'] === (int)$ph['id']) ? 'selected' : '' ?>>
                            <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Từ ngày</label>
                <input type="date" name="tu_ngay" class="form-control form-control-sm" value="<?= e($boLoc['tu_ngay']) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-semibold">Đến ngày</label>
                <input type="date" name="den_ngay" class="form-control form-control-sm" value="<?= e($boLoc['den_ngay']) ?>">
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1" title="Tìm kiếm">
                    <i class="bi bi-filter"></i> Lọc
                </button>
                <a href="<?= url('dieu-chuyen/index.php') ?>" class="btn btn-sm btn-outline-secondary" title="Đặt lại bộ lọc">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh sách phiếu điều chuyển -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            Danh sách phiếu điều chuyển (Tổng số: <span class="text-primary"><?= (int)$tongSo ?></span>)
        </h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-secondary text-uppercase small">
                <tr>
                    <th class="ps-3" style="width: 150px;">Mã phiếu</th>
                    <th style="width: 140px;">Thời gian</th>
                    <th style="width: 280px;">Lộ trình chuyển</th>
                    <th class="text-center" style="width: 100px;">Số lượng</th>
                    <th style="width: 160px;">Cán bộ lập</th>
                    <th>Lý do điều chuyển</th>
                    <th class="text-end pe-3" style="width: 130px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachDieuChuyen)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-arrow-left-right fs-1 text-muted d-block mb-2"></i>
                            <p class="mb-0">Chưa có phiếu điều chuyển nào phù hợp với bộ lọc hiện tại.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danhSachDieuChuyen as $item): ?>
                        <tr>
                            <td class="ps-3">
                                <a href="<?= url('dieu-chuyen/chi-tiet.php?id=' . (int)$item['id']) ?>" 
                                   class="font-monospace fw-bold text-primary text-decoration-none">
                                    <?= e($item['ma_dieu_chuyen']) ?>
                                </a>
                            </td>
                            <td class="small text-muted">
                                <?= e(f_ngay_gio($item['thoi_gian_dieu_chuyen'])) ?>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        <?= e($item['ma_phong_di']) ?>
                                    </span>
                                    <i class="bi bi-arrow-right text-muted"></i>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        <?= e($item['ma_phong_den']) ?>
                                    </span>
                                </div>
                                <div class="small text-muted mt-1 text-truncate" style="max-width: 260px;">
                                    <?= e($item['ten_phong_di']) ?> ➔ <?= e($item['ten_phong_den']) ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill px-2 py-1">
                                    <?= (int)$item['so_luong_tai_san'] ?> thiết bị
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small"><?= e($item['ho_ten_nguoi_lap']) ?></div>
                                <div class="small text-muted font-monospace"><?= e($item['ma_nhan_vien_lap']) ?></div>
                            </td>
                            <td>
                                <div class="small text-dark text-truncate" style="max-width: 250px;" title="<?= e($item['ly_do']) ?>">
                                    <?= e($item['ly_do']) ?>
                                </div>
                                <?php if (!empty($item['ghi_chu'])): ?>
                                    <div class="small text-muted text-truncate" style="max-width: 250px;" title="<?= e($item['ghi_chu']) ?>">
                                        <i class="bi bi-info-circle me-1"></i><?= e($item['ghi_chu']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <a href="<?= url('dieu-chuyen/chi-tiet.php?id=' . (int)$item['id']) ?>" 
                                   class="btn btn-sm btn-outline-primary" title="Xem chi tiết & In phiếu">
                                    <i class="bi bi-eye"></i> Chi tiết
                                </a>
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
                            <a class="page-link" href="<?= url('dieu-chuyen/index.php?' . http_build_query(array_merge($boLoc, ['trang' => $trangHienTai - 1]))) ?>">
                                « Trước
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $trangHienTai - 2); $i <= min($tongTrang, $trangHienTai + 2); $i++): ?>
                        <li class="page-item <?= ($i === $trangHienTai) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('dieu-chuyen/index.php?' . http_build_query(array_merge($boLoc, ['trang' => $i]))) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($trangHienTai < $tongTrang): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= url('dieu-chuyen/index.php?' . http_build_query(array_merge($boLoc, ['trang' => $trangHienTai + 1]))) ?>">
                                Sau »
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
