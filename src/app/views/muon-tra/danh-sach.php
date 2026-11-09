<?php
/**
 * Giao diện Lịch sử Danh sách Phiếu Mượn - Trả.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Lịch sử Mượn - Trả tài sản';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">📋 Lịch sử Mượn - Trả thiết bị</h1>
        <p class="text-secondary small mb-0">Theo dõi toàn bộ nhật ký giao dịch mượn, trả và hiện trạng thiết bị theo thời gian thực.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (co_quyen('nghiep_vu_muon_tra')): ?>
        <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-outline-success fw-semibold shadow-sm">
            📥 Nhận trả thiết bị
        </a>
        <a href="<?= url('muon-tra/muon.php') ?>" class="btn btn-primary fw-semibold shadow-sm">
            + Lập phiếu mượn mới
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Bộ lọc tìm kiếm lịch sử -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('muon-tra/index.php') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <input type="text" name="q" class="form-control" 
                       placeholder="Mã phiếu (PM-...), MSSV, tên người mượn, phòng..." 
                       value="<?= e($tuKhoa ?? '') ?>">
            </div>

            <div class="col-6 col-md-2">
                <select name="trang_thai" class="form-select">
                    <option value="">-- Tất cả trạng thái --</option>
                    <option value="DANG_MUON" <?= ($trangThai === 'DANG_MUON') ? 'selected' : '' ?>>Đang mượn</option>
                    <option value="DA_TRA" <?= ($trangThai === 'DA_TRA') ? 'selected' : '' ?>>Đã hoàn tất trả</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="phong_truc_id" class="form-select">
                    <option value="">-- Tất cả bàn trực --</option>
                    <?php foreach ($danhSachPhongTruc as $pt): ?>
                    <option value="<?= (int)$pt['id'] ?>" <?= ((int)$phongTrucId === (int)$pt['id']) ? 'selected' : '' ?>>
                        <?= e($pt['ten_phong_truc']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <input type="date" name="ngay_muon" class="form-control" value="<?= e($ngayMuon ?? '') ?>">
            </div>

            <div class="col-6 col-md-auto">
                <button type="submit" class="btn btn-primary fw-semibold">Lọc</button>
                <a href="<?= url('muon-tra/index.php') ?>" class="btn btn-outline-secondary">Xóa lọc</a>
            </div>

            <div class="col-12 col-md text-md-end text-muted small">
                Tổng cộng: <strong><?= $tongSo ?></strong> phiếu mượn
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh sách phiếu mượn -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 140px;">Mã phiếu</th>
                    <th>Người mượn</th>
                    <th>Phòng học</th>
                    <th>Bàn trực</th>
                    <th>Thời gian mượn</th>
                    <th>Hạn trả dự kiến</th>
                    <th class="text-center">Thiết bị</th>
                    <th class="text-center" style="width: 130px;">Trạng thái</th>
                    <th class="text-end pe-3" style="width: 120px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachPhieu)): ?>
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        Chưa có phiếu mượn nào được ghi nhận.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($danhSachPhieu as $pm): ?>
                <tr>
                    <td class="ps-3">
                        <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$pm['id']) ?>" class="font-monospace fw-bold text-decoration-none text-primary">
                            <?= e($pm['ma_phieu']) ?>
                        </a>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark"><?= e($pm['ten_nguoi_muon']) ?></div>
                        <div class="text-muted small">MSSV: <?= e($pm['ma_so_truong']) ?></div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            <?= e($pm['ma_phong']) ?>
                        </span>
                        <div class="small text-muted text-truncate" style="max-width: 140px;"><?= e($pm['ten_phong']) ?></div>
                    </td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                            <?= e($pm['ma_phong_truc']) ?>
                        </span>
                    </td>
                    <td><?= dinh_dang_ngay_gio($pm['thoi_gian_muon']) ?></td>
                    <td>
                        <?php
                            $quaHan = ($pm['trang_thai'] === 'DANG_MUON' && strtotime($pm['han_tra']) < time());
                        ?>
                        <span class="<?= $quaHan ? 'text-danger fw-bold' : '' ?>">
                            <?= dinh_dang_ngay_gio($pm['han_tra']) ?>
                            <?php if ($quaHan): ?>
                                <span class="badge bg-danger small ms-1">Quá hạn</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">
                            <?= (int)$pm['tong_so_thiet_bi'] ?> thiết bị
                        </span>
                        <?php if ((int)$pm['so_chua_tra'] > 0): ?>
                            <div class="small text-warning fw-bold mt-1">Còn giữ: <?= (int)$pm['so_chua_tra'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($pm['trang_thai'] === 'DA_TRA'): ?>
                            <span class="badge bg-success">Đã trả đủ</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Đang mượn</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$pm['id']) ?>" class="btn btn-sm btn-outline-info">
                            Chi tiết
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <?php if ($tongSoTrang > 1): ?>
    <div class="card-footer bg-white py-3 border-top d-flex justify-content-between align-items-center">
        <span class="small text-muted">
            Trang <strong><?= $trang ?></strong> / <strong><?= $tongSoTrang ?></strong>
        </span>
        <nav aria-label="Phân trang">
            <ul class="pagination pagination-sm mb-0">
                <?php for ($i = 1; $i <= $tongSoTrang; $i++): ?>
                <li class="page-item <?= $i === $trang ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('muon-tra/index.php?' . http_build_query(array_merge($_GET, ['trang' => $i]))) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>

