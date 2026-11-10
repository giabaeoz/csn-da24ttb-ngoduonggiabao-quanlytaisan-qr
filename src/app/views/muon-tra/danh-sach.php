<?php
/**
 * Giao diện Lịch sử Danh sách Phiếu Mượn - Trả.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Lịch sử mượn - trả';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<!-- Tiêu đề trang & Nút thao tác -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang">Lịch sử mượn - trả</h1>
        <div class="mo-ta-phu">Theo dõi toàn bộ nhật ký giao dịch mượn, trả và hiện trạng thiết bị theo thời gian thực.</div>
    </div>
    <div class="d-flex gap-2">
        <?php if (co_quyen('nghiep_vu_muon_tra')): ?>
        <a href="<?= url('muon-tra/tra.php') ?>" class="btn btn-phu">
            Nhận trả thiết bị
        </a>
        <a href="<?= url('muon-tra/muon.php') ?>" class="btn btn-chinh">
            + Lập phiếu mượn mới
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Bộ lọc tìm kiếm lịch sử -->
<div class="card the-noi-dung mb-4">
    <div class="card-body p-3">
        <form action="<?= url('muon-tra/index.php') ?>" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="q" class="form-control" 
                       placeholder="Mã phiếu, MSSV, họ tên, phòng..." 
                       value="<?= e($tuKhoa ?? '') ?>">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Trạng thái</label>
                <select name="trang_thai" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="DANG_MUON" <?= ($trangThai === 'DANG_MUON') ? 'selected' : '' ?>>Đang mượn</option>
                    <option value="DA_TRA" <?= ($trangThai === 'DA_TRA') ? 'selected' : '' ?>>Đã hoàn tất trả</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Bàn trực</label>
                <select name="phong_truc_id" class="form-select">
                    <option value="">Tất cả bàn trực</option>
                    <?php foreach ($danhSachPhongTruc as $pt): ?>
                    <option value="<?= (int)$pt['id'] ?>" <?= ((int)$phongTrucId === (int)$pt['id']) ? 'selected' : '' ?>>
                        <?= e($pt['ten_phong_truc']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Ngày mượn</label>
                <input type="date" name="ngay_muon" class="form-control" value="<?= e($ngayMuon ?? '') ?>">
            </div>

            <div class="col-6 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-phu">Lọc</button>
                <a href="<?= url('muon-tra/index.php') ?>" class="btn btn-phu">Đặt lại</a>
            </div>

            <div class="col-12 col-md text-md-end mo-ta-phu">
                Tổng cộng: <strong><?= $tongSo ?></strong> phiếu mượn
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh sách phiếu mượn -->
<div class="card the-noi-dung">
    <div class="table-responsive">
        <table class="table bang-du-lieu mb-0">
            <thead>
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
                    <td colspan="9" class="text-center py-5 mo-ta-phu">
                        Chưa có phiếu mượn nào được ghi nhận.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($danhSachPhieu as $pm): ?>
                <tr>
                    <td class="ps-3">
                        <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$pm['id']) ?>" class="font-monospace fw-semibold text-primary">
                            <?= e($pm['ma_phieu']) ?>
                        </a>
                    </td>
                    <td>
                        <div class="fw-semibold"><?= e($pm['ten_nguoi_muon']) ?></div>
                        <div class="mo-ta-phu">MSSV: <?= e($pm['ma_so_truong']) ?></div>
                    </td>
                    <td>
                        <span class="huy-hieu huy-hieu-trung-tinh">
                            <?= e($pm['ma_phong']) ?>
                        </span>
                        <div class="mo-ta-phu text-truncate" style="max-width: 140px;"><?= e($pm['ten_phong']) ?></div>
                    </td>
                    <td>
                        <span class="mo-ta-phu">
                            <?= e($pm['ma_phong_truc']) ?>
                        </span>
                    </td>
                    <td><?= dinh_dang_ngay_gio($pm['thoi_gian_muon']) ?></td>
                    <td>
                        <?php
                            $quaHan = ($pm['trang_thai'] === 'DANG_MUON' && strtotime($pm['han_tra']) < time());
                        ?>
                        <span class="<?= $quaHan ? 'text-danger fw-semibold' : '' ?>">
                            <?= dinh_dang_ngay_gio($pm['han_tra']) ?>
                            <?php if ($quaHan): ?>
                                <span class="huy-hieu huy-hieu-nguy-hiem ms-1">Quá hạn</span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="huy-hieu huy-hieu-trung-tinh">
                            <?= (int)$pm['tong_so_thiet_bi'] ?> thiết bị
                        </span>
                        <?php if ((int)$pm['so_chua_tra'] > 0): ?>
                            <div class="small text-danger fw-semibold mt-1">Còn giữ: <?= (int)$pm['so_chua_tra'] ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($pm['trang_thai'] === 'DA_TRA'): ?>
                            <span class="huy-hieu huy-hieu-thanh-cong">Đã trả đủ</span>
                        <?php else: ?>
                            <span class="huy-hieu huy-hieu-canh-bao">Đang mượn</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$pm['id']) ?>" class="btn btn-phu btn-sm">
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
        <span class="small mo-ta-phu">
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
