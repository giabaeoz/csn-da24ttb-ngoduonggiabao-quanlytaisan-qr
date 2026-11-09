<?php
/**
 * Giao diện Danh sách Tài sản / Thiết bị & Quản lý Mã QR.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Quản lý Tài sản / Thiết bị';
require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">💻 Quản lý Tài sản & Thiết bị</h1>
        <p class="text-secondary small mb-0">Quản lý định danh, sinh mã QR, theo dõi tình trạng và độ tương thích thiết bị.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-dark fw-semibold shadow-sm" onclick="inCacTemDaChon()">
            🖨️ In tem QR đã chọn
        </button>
        <?php if (co_quyen('quan_ly_tai_san')): ?>
        <a href="<?= url('tai-san/them.php') ?>" class="btn btn-primary fw-semibold shadow-sm">
            + Thêm thiết bị mới
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- 4 Thẻ thống kê nhanh số liệu -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded-3 p-3 fs-3 me-3">
                        💻
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Tổng tài sản</div>
                        <h4 class="fw-bold mb-0 text-dark"><?= $thongKe['tong_so'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded-3 p-3 fs-3 me-3">
                        ✅
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Đang sẵn sàng</div>
                        <h4 class="fw-bold mb-0 text-success"><?= $thongKe['san_sang'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-warning bg-opacity-10 text-warning rounded-3 p-3 fs-3 me-3">
                        📤
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Đang được mượn</div>
                        <h4 class="fw-bold mb-0 text-warning"><?= $thongKe['dang_muon'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 bg-white">
            <div class="card-body p-3">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-danger bg-opacity-10 text-danger rounded-3 p-3 fs-3 me-3">
                        ⚠️
                    </div>
                    <div>
                        <div class="text-muted small fw-semibold">Bảo trì / Hư hỏng</div>
                        <h4 class="fw-bold mb-0 text-danger"><?= $thongKe['bao_tri_hu_hong'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bộ lọc tìm kiếm đa tiêu chí -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form action="<?= url('tai-san/index.php') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <input type="text" name="q" class="form-control" 
                       placeholder="Mã TS, QR, tên thiết bị, hãng, model..." 
                       value="<?= e($tuKhoa ?? '') ?>">
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
                <select name="loai_id" class="form-select">
                    <option value="">-- Chủng loại --</option>
                    <?php foreach ($danhSachLoai as $loai): ?>
                    <option value="<?= (int)$loai['id'] ?>" <?= ((int)$loaiId === (int)$loai['id']) ? 'selected' : '' ?>>
                        <?= e($loai['ten_loai']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <select name="trang_thai" class="form-select">
                    <option value="">-- Trạng thái --</option>
                    <option value="SAN_SANG" <?= ($trangThai === 'SAN_SANG') ? 'selected' : '' ?>>Sẵn sàng</option>
                    <option value="DANG_MUON" <?= ($trangThai === 'DANG_MUON') ? 'selected' : '' ?>>Đang mượn</option>
                    <option value="BAO_TRI" <?= ($trangThai === 'BAO_TRI') ? 'selected' : '' ?>>Bảo trì</option>
                    <option value="NGUNG_SU_DUNG" <?= ($trangThai === 'NGUNG_SU_DUNG') ? 'selected' : '' ?>>Ngừng sử dụng</option>
                </select>
            </div>

            <div class="col-6 col-md-auto">
                <button type="submit" class="btn btn-primary fw-semibold">Lọc dữ liệu</button>
                <a href="<?= url('tai-san/index.php') ?>" class="btn btn-outline-secondary">Xóa lọc</a>
            </div>

            <div class="col-12 col-md text-md-end text-muted small">
                Hiển thị: <strong><?= count($danhSachTaiSan) ?></strong> / <strong><?= $tongSo ?></strong> thiết bị
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh sách tài sản -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width: 40px;">
                        <input type="checkbox" class="form-check-input" id="checkAll" onclick="chonTatCa(this)">
                    </th>
                    <th style="width: 140px;">Mã QR / Mã TS</th>
                    <th>Tên tài sản</th>
                    <th>Hãng & Model</th>
                    <th style="width: 110px;">Bàn trực</th>
                    <th>Vị trí hiện tại</th>
                    <th>Nhãn kệ phòng</th>
                    <th class="text-center" style="width: 130px;">Trạng thái</th>
                    <th class="text-end pe-3" style="width: 170px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachTaiSan)): ?>
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        Không tìm thấy tài sản nào phù hợp với bộ lọc tìm kiếm.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($danhSachTaiSan as $ts): ?>
                <tr>
                    <td class="ps-3">
                        <input type="checkbox" class="form-check-input check-item" value="<?= (int)$ts['id'] ?>">
                    </td>
                    <td>
                        <span class="badge bg-light text-primary border font-monospace fs-6">
                            <?= e($ts['ma_tai_san']) ?>
                        </span>
                        <div class="font-monospace small text-muted"><?= e($ts['ma_qr']) ?></div>
                    </td>
                    <td>
                        <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$ts['id']) ?>" class="fw-bold text-decoration-none text-dark">
                            <?= e($ts['ten_tai_san']) ?>
                        </a>
                        <div class="text-muted small"><?= e($ts['ten_loai']) ?></div>
                    </td>
                    <td>
                        <?php if (!empty($ts['hang_san_xuat'])): ?>
                            <span class="badge bg-light text-dark border">
                                <?= e($ts['hang_san_xuat']) ?>
                                <?php if (!empty($ts['model'])): ?>
                                    <span class="text-muted">(<?= e($ts['model']) ?>)</span>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary">
                            <?= e($ts['ma_phong_truc']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($ts['ma_phong_hien_tai'])): ?>
                            <span class="badge bg-warning text-dark fw-bold">
                                🏫 <?= e($ts['ma_phong_hien_tai']) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-success border">
                                🏠 Tại bàn <?= e($ts['ma_phong_truc']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($ts['ma_phong_phu_hop'])): ?>
                            <span class="badge bg-info bg-opacity-10 text-info" title="Nhãn dán phòng gợi ý để xếp kệ">
                                Tem: <?= e($ts['ma_phong_phu_hop']) ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted small">Linh hoạt</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php
                            $badgeClass = match ($ts['trang_thai']) {
                                'SAN_SANG'      => 'bg-success',
                                'DANG_MUON'     => 'bg-warning text-dark',
                                'BAO_TRI'       => 'bg-danger',
                                'NGUNG_SU_DUNG' => 'bg-secondary',
                                default         => 'bg-secondary',
                            };
                        ?>
                        <span class="badge <?= $badgeClass ?> fw-semibold">
                            <?= e(TEN_TRANG_THAI[$ts['trang_thai']] ?? $ts['trang_thai']) ?>
                        </span>
                        <?php if ($ts['tinh_trang'] !== 'TOT'): ?>
                            <div class="badge bg-danger bg-opacity-10 text-danger border mt-1">
                                <?= e(TEN_TINH_TRANG[$ts['tinh_trang']] ?? $ts['tinh_trang']) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-end pe-3">
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('tai-san/in-qr.php?id=' . (int)$ts['id']) ?>" class="btn btn-outline-dark" title="In tem mã QR" target="_blank">
                                🖨️
                            </a>
                            <a href="<?= url('tai-san/chi-tiet.php?id=' . (int)$ts['id']) ?>" class="btn btn-outline-info" title="Xem chi tiết">
                                Chi tiết
                            </a>

                            <?php if (co_quyen('quan_ly_tai_san')): ?>
                            <a href="<?= url('tai-san/sua.php?id=' . (int)$ts['id']) ?>" class="btn btn-outline-primary" title="Sửa thông tin">
                                Sửa
                            </a>

                            <?php if ($ts['trang_thai'] !== 'DANG_MUON'): ?>
                            <form action="<?= url('tai-san/index.php') ?>" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa hoặc ngừng sử dụng thiết bị này?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hanh_dong" value="xoa">
                                <input type="hidden" name="id" value="<?= (int)$ts['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger" title="Xóa / Ngừng sử dụng">
                                    ✕
                                </button>
                            </form>
                            <?php endif; ?>
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
                    <a class="page-link" href="<?= url('tai-san/index.php?' . http_build_query(array_merge($_GET, ['trang' => $i]))) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
function chonTatCa(master) {
    const items = document.querySelectorAll('.check-item');
    items.forEach(el => el.checked = master.checked);
}

function inCacTemDaChon() {
    const checked = Array.from(document.querySelectorAll('.check-item:checked')).map(el => el.value);
    if (checked.length === 0) {
        alert('Vui lòng tích chọn ít nhất một thiết bị trong bảng để in tem mã QR.');
        return;
    }
    const url = '<?= url("tai-san/in-qr.php") ?>?ids=' + checked.join(',');
    window.open(url, '_blank');
}
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
