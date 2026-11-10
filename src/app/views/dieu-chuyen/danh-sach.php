<?php
/**
 * Giao diện: Danh sách lịch sử điều chuyển thiết bị - Khớp Hệ thống thiết kế
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var list<array<string, mixed>> $danhSachDieuChuyen
 * @var list<array<string, mixed>> $danhSachPhongHoc
 * @var array<string, string> $boLoc
 * @var int $tongSo
 * @var int $tongTrang
 * @var int $trangHienTai
 * @var int $soMoiTrang
 */

$tieuDeTrang = 'Điều chuyển';
require dirname(__DIR__) . '/layout/dau-trang.php';

$viTriBatDau = ($trangHienTai - 1) * $soMoiTrang + (count($danhSachDieuChuyen) > 0 ? 1 : 0);
$viTriKetThuc = ($trangHienTai - 1) * $soMoiTrang + count($danhSachDieuChuyen);
?>

<!-- Tiêu đề trang & Nút hành động -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang">Điều chuyển tài sản</h1>
        <div class="mo-ta-phu">Lịch sử điều chuyển thiết bị giữa các phòng học.</div>
    </div>
    <div>
        <?php if (co_quyen('tao_dieu_chuyen')): ?>
        <a href="<?= url('dieu-chuyen/them.php') ?>" class="btn btn-chinh">
            Lập phiếu điều chuyển
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Bộ lọc tìm kiếm -->
<div class="card mb-4">
    <div class="card-body p-3">
        <form action="<?= url('dieu-chuyen/index.php') ?>" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">Từ khóa</label>
                <input type="text" name="tu_khoa" class="form-control" placeholder="Mã phiếu, cán bộ..." 
                       value="<?= e($boLoc['tu_khoa']) ?>">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Phòng đi</label>
                <select name="phong_hoc_di_id" class="form-select">
                    <option value="">Tất cả phòng đi</option>
                    <?php foreach ($danhSachPhongHoc as $ph): ?>
                    <option value="<?= (int)$ph['id'] ?>" <?= ((int)$boLoc['phong_hoc_di_id'] === (int)$ph['id']) ? 'selected' : '' ?>>
                        <?= e($ph['ma_phong']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Phòng đến</label>
                <select name="phong_hoc_den_id" class="form-select">
                    <option value="">Tất cả phòng đến</option>
                    <?php foreach ($danhSachPhongHoc as $ph): ?>
                    <option value="<?= (int)$ph['id'] ?>" <?= ((int)$boLoc['phong_hoc_den_id'] === (int)$ph['id']) ? 'selected' : '' ?>>
                        <?= e($ph['ma_phong']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="tu_ngay" class="form-control" value="<?= e($boLoc['tu_ngay']) ?>">
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Đến ngày</label>
                <input type="date" name="den_ngay" class="form-control" value="<?= e($boLoc['den_ngay']) ?>">
            </div>

            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-phu">Tra cứu</button>
                <a href="<?= url('dieu-chuyen/index.php') ?>" class="btn btn-phu">Đặt lại</a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng lịch sử điều chuyển -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="tieu-de-khoi mb-0">Danh sách phiếu điều chuyển</h2>
        <div class="mo-ta-phu">Tổng số: <?= $tongSo ?> phiếu</div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã phiếu</th>
                    <th>Thời gian</th>
                    <th>Phòng đi</th>
                    <th>Phòng đến</th>
                    <th>Thiết bị</th>
                    <th>Cán bộ thực hiện</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachDieuChuyen)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-phu">Không có dữ liệu.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($danhSachDieuChuyen as $dc): ?>
                <tr>
                    <td class="fw-semibold text-primary">
                        <?= e($dc['ma_dieu_chuyen']) ?>
                    </td>
                    <td><?= dinh_dang_ngay_gio($dc['thoi_gian_tao']) ?></td>
                    <td><?= e($dc['ma_phong_di']) ?></td>
                    <td><?= e($dc['ma_phong_den']) ?></td>
                    <td><?= (int)$dc['tong_so_thiet_bi'] ?> thiết bị</td>
                    <td><?= e($dc['ho_ten_can_bo']) ?></td>
                    <td class="text-end">
                        <a href="<?= url('dieu-chuyen/chi-tiet.php?id=' . (int)$dc['id']) ?>" class="btn btn-phu btn-sm">
                            Xem phiếu
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang -->
    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
        <div class="mo-ta-phu">
            <?= $viTriBatDau ?>–<?= $viTriKetThuc ?> trong <?= $tongSo ?> phiếu
        </div>
        <div>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= ($trangHienTai <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= url('dieu-chuyen/index.php?' . http_build_query(array_merge($_GET, ['trang' => max(1, $trangHienTai - 1)]))) ?>">Trước</a>
                </li>
                <?php for ($i = 1; $i <= $tongTrang; $i++): ?>
                <li class="page-item <?= ($i === $trangHienTai) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= url('dieu-chuyen/index.php?' . http_build_query(array_merge($_GET, ['trang' => $i]))) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($trangHienTai >= $tongTrang) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= url('dieu-chuyen/index.php?' . http_build_query(array_merge($_GET, ['trang' => min($tongTrang, $trangHienTai + 1)]))) ?>">Sau</a>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
