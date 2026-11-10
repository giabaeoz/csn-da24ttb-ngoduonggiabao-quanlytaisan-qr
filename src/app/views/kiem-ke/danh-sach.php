<?php

/**
 * Giao diện: Danh sách các đợt kiểm kê tài sản (G8).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var list<array<string, mixed>> $danhSachDotKiemKe
 * @var array<string, mixed>|null $dotDangMo
 * @var list<array<string, mixed>> $danhSachPhongTruc
 * @var array<string, string|int> $boLoc
 * @var int $tongSo
 * @var int $tongTrang
 * @var int $trangHienTai
 * @var array<string, mixed> $nguoiDung
 * @var int $phongTrucId
 */

$tieuDeTrang = 'Kiểm kê tài sản';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<!-- Tiêu đề trang & Nút mở đợt -->
<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang">Kiểm kê tài sản theo phòng trực</h1>
        <div class="mo-ta-phu">Chụp sổ sách, đối chiếu thực tế bằng mã QR, phát hiện chênh lệch và tự động ghi nhận sự cố.</div>
    </div>
    <div>
        <?php if (co_quyen('mo_dot_kiem_ke') && $dotDangMo === null): ?>
            <a href="<?= url('kiem-ke/tao.php') ?>" class="btn btn-chinh">
                <?= icon('plus', 'bieu-tuong-sm') ?>
                <span>Mở đợt kiểm kê mới</span>
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Cảnh báo đợt đang mở -->
<?php if ($dotDangMo !== null): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center p-3 mb-4 rounded-2">
        <div class="d-flex align-items-center gap-2">
            <?= icon('alert-triangle') ?>
            <div>
                <div class="fw-semibold">
                    Đợt kiểm kê <span class="font-monospace">[<?= e($dotDangMo['ma_dot_kiem_ke']) ?>]</span> đang diễn ra
                </div>
                <div class="small text-muted">
                    <?= e($dotDangMo['ten_dot_kiem_ke']) ?> - Còn lại <strong><?= (int)($dotDangMo['thong_ke']['so_con_lai_can_kiem'] ?? 0) ?></strong> thiết bị cần đối chiếu thực tế.
                </div>
            </div>
        </div>
        <div>
            <a href="<?= url('kiem-ke/thuc-hien.php?id=' . (int)$dotDangMo['id']) ?>" class="btn btn-chinh">
                <?= icon('scan-line', 'bieu-tuong-sm') ?>
                <span>Tiếp tục kiểm kê</span>
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Bộ lọc tìm kiếm -->
<div class="card the-noi-dung mb-4">
    <div class="card-body p-3">
        <form action="<?= url('kiem-ke/index.php') ?>" method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label">Từ khóa tìm kiếm</label>
                <div class="o-tim-kiem">
                    <?= icon('search', 'bieu-tuong-sm') ?>
                    <input type="text" name="tu_khoa" class="form-control" placeholder="Mã đợt, tên đợt, cán bộ..."
                        value="<?= e($boLoc['tu_khoa']) ?>">
                </div>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Phòng trực</label>
                <select name="phong_truc_id" class="form-select" <?= ($nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN && $phongTrucId > 0) ? 'disabled' : '' ?>>
                    <?php if ($nguoiDung['vai_tro'] !== VAI_TRO_NHAN_VIEN): ?>
                        <option value="">Tất cả bàn trực</option>
                    <?php endif; ?>
                    <?php foreach ($danhSachPhongTruc as $pt): ?>
                        <option value="<?= (int)$pt['id'] ?>" <?= ((int)$boLoc['phong_truc_id'] === (int)$pt['id']) ? 'selected' : '' ?>>
                            <?= e($pt['ten_phong_truc']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Trạng thái đợt</label>
                <select name="trang_thai" class="form-select">
                    <option value="">Tất cả trạng thái</option>
                    <option value="<?= KIEM_KE_DANG_KIEM_KE ?>" <?= ($boLoc['trang_thai'] === KIEM_KE_DANG_KIEM_KE) ? 'selected' : '' ?>>Đang kiểm kê</option>
                    <option value="<?= KIEM_KE_HOAN_TAT ?>" <?= ($boLoc['trang_thai'] === KIEM_KE_HOAN_TAT) ? 'selected' : '' ?>>Hoàn tất</option>
                    <option value="<?= KIEM_KE_DA_HUY ?>" <?= ($boLoc['trang_thai'] === KIEM_KE_DA_HUY) ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Loại kiểm kê</label>
                <select name="loai_kiem_ke" class="form-select">
                    <option value="">Tất cả loại</option>
                    <option value="<?= LOAI_KIEM_KE_CUOI_BUOI ?>" <?= ($boLoc['loai_kiem_ke'] === LOAI_KIEM_KE_CUOI_BUOI) ? 'selected' : '' ?>>Cuối buổi</option>
                    <option value="<?= LOAI_KIEM_KE_DINH_KY ?>" <?= ($boLoc['loai_kiem_ke'] === LOAI_KIEM_KE_DINH_KY) ? 'selected' : '' ?>>Định kỳ</option>
                    <option value="<?= LOAI_KIEM_KE_DOT_XUAT ?>" <?= ($boLoc['loai_kiem_ke'] === LOAI_KIEM_KE_DOT_XUAT) ? 'selected' : '' ?>>Đột xuất</option>
                </select>
            </div>

            <div class="col-6 col-md-2">
                <label class="form-label">Từ ngày</label>
                <input type="date" name="tu_ngay" class="form-control" value="<?= e($boLoc['tu_ngay']) ?>">
            </div>

            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-phu w-100">
                    <?= icon('filter', 'bieu-tuong-sm') ?>
                    <span>Lọc</span>
                </button>
                <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-phu" aria-label="Đặt lại" title="Đặt lại">✕</a>
            </div>
        </form>
    </div>
</div>

<!-- Bảng danh sách các đợt kiểm kê -->
<div class="card the-noi-dung">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
        <h2 class="tieu-de-khoi mb-0">
            Danh sách đợt kiểm kê (Tổng số: <?= (int)$tongSo ?>)
        </h2>
    </div>
    <div class="table-responsive">
        <table class="table bang-du-lieu mb-0">
            <thead>
                <tr>
                    <th class="ps-3 cot-140">Mã đợt</th>
                    <th>Tên đợt kiểm kê</th>
                    <th class="cot-130">Loại</th>
                    <th class="cot-160">Phòng trực</th>
                    <th class="cot-150">Thời gian</th>
                    <th class="cot-180">Tiến độ / Số lượng</th>
                    <th class="text-center cot-120">Trạng thái</th>
                    <th class="text-end pe-3 cot-130">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachDotKiemKe)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 mo-ta-phu">
                            Chưa có đợt kiểm kê nào phù hợp.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danhSachDotKiemKe as $item): ?>
                        <tr>
                            <td class="ps-3">
                                <span class="font-monospace fw-semibold text-primary">
                                    <?= e($item['ma_dot_kiem_ke']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= e($item['ten_dot_kiem_ke']) ?></div>
                                <div class="mo-ta-phu">Lập bởi: <?= e($item['ho_ten_nguoi_lap']) ?></div>
                            </td>
                            <td>
                                <?php if ($item['loai_kiem_ke'] === LOAI_KIEM_KE_CUOI_BUOI): ?>
                                    <span class="huy-hieu huy-hieu-chinh">Cuối buổi</span>
                                <?php elseif ($item['loai_kiem_ke'] === LOAI_KIEM_KE_DINH_KY): ?>
                                    <span class="huy-hieu huy-hieu-trung-tinh">Định kỳ</span>
                                <?php else: ?>
                                    <span class="huy-hieu huy-hieu-canh-bao">Đột xuất</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?= e($item['ten_phong_truc']) ?>
                            </td>
                            <td class="small mo-ta-phu">
                                <div>Bắt đầu: <?= e(f_ngay_gio($item['thoi_gian_bat_dau'])) ?></div>
                                <?php if (!empty($item['thoi_gian_ket_thuc'])): ?>
                                    <div>Kết thúc: <?= e(f_ngay_gio($item['thoi_gian_ket_thuc'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small mb-1">
                                    Tổng sổ sách: <strong><?= (int)$item['tong_tai_san_so_sach'] ?></strong>
                                </div>
                                <div class="d-flex gap-1 flex-wrap">
                                    <span class="huy-hieu huy-hieu-thanh-cong" title="Có mặt">Tìm thấy: <?= (int)$item['so_tim_thay'] ?></span>
                                    <span class="huy-hieu huy-hieu-nguy-hiem" title="Chưa tìm thấy">Chưa thấy: <?= (int)$item['so_chua_tim_thay'] ?></span>
                                    <span class="huy-hieu huy-hieu-canh-bao" title="Đang mượn">Mượn: <?= (int)$item['so_dang_muon'] ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($item['trang_thai'] === KIEM_KE_DANG_KIEM_KE): ?>
                                    <span class="huy-hieu huy-hieu-canh-bao">Đang kiểm</span>
                                <?php elseif ($item['trang_thai'] === KIEM_KE_HOAN_TAT): ?>
                                    <span class="huy-hieu huy-hieu-thanh-cong">Hoàn tất</span>
                                <?php else: ?>
                                    <span class="huy-hieu huy-hieu-nguy-hiem">Đã hủy</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <?php if ($item['trang_thai'] === KIEM_KE_DANG_KIEM_KE): ?>
                                    <a href="<?= url('kiem-ke/thuc-hien.php?id=' . (int)$item['id']) ?>"
                                        class="btn-bieu-tuong" aria-label="Kiểm tra" title="Kiểm tra">
                                        <?= icon('clipboard-check', 'bieu-tuong-sm') ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= url('kiem-ke/chi-tiet.php?id=' . (int)$item['id']) ?>"
                                        class="btn-bieu-tuong" aria-label="Báo cáo" title="Báo cáo">
                                        <?= icon('eye', 'bieu-tuong-sm') ?>
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
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2 border-top">
            <span class="small mo-ta-phu">Trang <?= (int)$trangHienTai ?> / <?= (int)$tongTrang ?></span>
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