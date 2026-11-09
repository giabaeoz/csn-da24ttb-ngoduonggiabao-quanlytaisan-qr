<?php
/**
 * Giao diện Danh sách Người mượn (Sinh viên & Giảng viên).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Quản lý Người mượn';
require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">👥 Hồ sơ Người mượn</h1>
        <p class="text-secondary small mb-0">Quản lý hồ sơ Sinh viên & Giảng viên mượn thiết bị giảng dạy, học tập.</p>
    </div>
    <div>
        <a href="<?= url('nguoi-muon/them.php') ?>" class="btn btn-primary fw-semibold shadow-sm">
            + Thêm người mượn mới
        </a>
    </div>
</div>

<!-- Bộ lọc tìm kiếm & phân trang -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('nguoi-muon/index.php') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <input type="text" name="q" class="form-control" 
                       placeholder="Tìm theo MSSV/MSGV, họ tên, số điện thoại, lớp..." 
                       value="<?= e($tuKhoa ?? '') ?>">
            </div>
            <div class="col-6 col-md-3">
                <select name="loai" class="form-select">
                    <option value="">-- Tất cả đối tượng --</option>
                    <option value="SINH_VIEN" <?= ($loaiNguoiMuon ?? '') === 'SINH_VIEN' ? 'selected' : '' ?>>Sinh viên</option>
                    <option value="GIANG_VIEN" <?= ($loaiNguoiMuon ?? '') === 'GIANG_VIEN' ? 'selected' : '' ?>>Giảng viên</option>
                </select>
            </div>
            <div class="col-6 col-md-auto">
                <button type="submit" class="btn btn-primary fw-semibold">Tìm kiếm</button>
                <?php if (!empty($tuKhoa) || !empty($loaiNguoiMuon)): ?>
                    <a href="<?= url('nguoi-muon/index.php') ?>" class="btn btn-outline-secondary">Xóa lọc</a>
                <?php endif; ?>
            </div>
            <div class="col-12 col-md text-md-end text-muted small">
                Tổng cộng: <strong><?= $tongSo ?></strong> người mượn
            </div>
        </form>
    </div>
</div>

<!-- Bảng dữ liệu người mượn -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 140px;">Mã số trường</th>
                    <th>Họ và tên</th>
                    <th style="width: 130px;">Đối tượng</th>
                    <th>Lớp / Đơn vị</th>
                    <th>Số điện thoại</th>
                    <th class="text-center" style="width: 150px;">Đang mượn</th>
                    <th class="text-end pe-3" style="width: 180px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachNguoiMuon)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        Không tìm thấy người mượn nào phù hợp với điều kiện tìm kiếm.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($danhSachNguoiMuon as $nm): ?>
                <tr>
                    <td class="ps-3">
                        <span class="badge bg-light text-primary border font-monospace fs-6">
                            <?= e($nm['ma_so_truong']) ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= url('nguoi-muon/chi-tiet.php?id=' . (int)$nm['id']) ?>" class="fw-bold text-decoration-none text-dark">
                            <?= e($nm['ho_ten']) ?>
                        </a>
                        <div class="text-muted small">Mã: <?= e($nm['ma_nguoi_muon']) ?></div>
                    </td>
                    <td>
                        <?php if ($nm['loai_nguoi_muon'] === 'GIANG_VIEN'): ?>
                            <span class="badge bg-info bg-opacity-10 text-info fw-semibold px-2 py-1">Giảng viên</span>
                        <?php else: ?>
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold px-2 py-1">Sinh viên</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-secondary small">
                        <?= e($nm['lop_don_vi'] ?: '-') ?>
                    </td>
                    <td>
                        <?= e($nm['so_dien_thoai'] ?: '-') ?>
                    </td>
                    <td class="text-center">
                        <?php if ((int)$nm['so_thiet_bi_dang_muon'] > 0): ?>
                            <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                                📤 <?= (int)$nm['so_thiet_bi_dang_muon'] ?> thiết bị
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('nguoi-muon/chi-tiet.php?id=' . (int)$nm['id']) ?>" class="btn btn-outline-info" title="Xem hồ sơ & Lịch sử mượn">
                                Lịch sử
                            </a>
                            <a href="<?= url('nguoi-muon/sua.php?id=' . (int)$nm['id']) ?>" class="btn btn-outline-primary" title="Sửa thông tin">
                                Sửa
                            </a>

                            <?php if ((int)$nm['tong_so_luot_muon'] === 0): ?>
                            <form action="<?= url('nguoi-muon/index.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn muốn xóa hồ sơ người mượn này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hanh_dong" value="xoa">
                                <input type="hidden" name="id" value="<?= (int)$nm['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger" title="Xóa">
                                    Xóa
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
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
                    <a class="page-link" href="<?= url('nguoi-muon/index.php?q=' . urlencode($tuKhoa) . '&loai=' . urlencode($loaiNguoiMuon) . '&trang=' . $i) ?>">
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

