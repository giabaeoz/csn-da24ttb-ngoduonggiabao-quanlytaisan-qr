<?php
/**
 * Giao diện: Danh sách sự cố tài sản & Quản lý bảo trì - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$tieuDeTrang = 'Quản lý sự cố & bảo trì';
$thongKe = $thongKe ?? ['tong_so' => 0, 'so_chua_xu_ly' => 0, 'so_dang_xu_ly' => 0, 'so_da_xu_ly' => 0];
$danhSachSuCo = $danhSachSuCo ?? [];
$boLoc = $boLoc ?? [];
$tongSo = $tongSo ?? 0;
$tongTrang = $tongTrang ?? 1;
$trangHienTai = $trangHienTai ?? 1;
$danhSachPhongTruc = $danhSachPhongTruc ?? [];
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<!-- Tiêu đề trang & Nút tác vụ -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="tieu-de-trang mb-0">Quản lý sự cố & bảo trì</h1>
        <div class="mo-ta-phu">Theo dõi, tiếp nhận và ghi nhận kết quả xử lý sửa chữa thiết bị hư hỏng, mất mát, sai vị trí.</div>
    </div>
    <div>
        <?php if (co_quyen('ghi_nhan_su_co')): ?>
            <a href="<?= url('su-co/bao-cao.php') ?>" class="btn btn-chinh">
                + Báo cáo sự cố mới
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- 4 Thẻ thống kê KPI -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="the-so-lieu">
            <div class="nhan-so-lieu">Tổng sự cố</div>
            <div class="so-lieu"><?= sprintf('%02d', (int)$thongKe['tong_so']) ?></div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="the-so-lieu">
            <div class="nhan-so-lieu">Chưa xử lý</div>
            <div class="so-lieu text-danger"><?= sprintf('%02d', (int)$thongKe['so_chua_xu_ly']) ?></div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="the-so-lieu">
            <div class="nhan-so-lieu">Đang xử lý / Bảo trì</div>
            <div class="so-lieu text-warning"><?= sprintf('%02d', (int)$thongKe['so_dang_xu_ly']) ?></div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="the-so-lieu">
            <div class="nhan-so-lieu">Đã xử lý xong</div>
            <div class="so-lieu text-success"><?= sprintf('%02d', (int)$thongKe['so_da_xu_ly']) ?></div>
        </div>
    </div>
</div>

<!-- Bộ lọc tìm kiếm sự cố -->
<div class="the-noi-dung p-3 mb-4">
    <form action="<?= url('su-co/index.php') ?>" method="GET" class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
            <label class="nhan-truong">Từ khóa tìm kiếm</label>
            <input type="text" name="tu_khoa" class="o-nhap" placeholder="Mã SC, mã TS, tên thiết bị, người lập..." 
                   value="<?= e($boLoc['tu_khoa']) ?>">
        </div>

        <div class="col-12 col-sm-6 col-md-2">
            <label class="nhan-truong">Loại sự cố</label>
            <select name="loai_su_co" class="o-nhap">
                <option value="">Tất cả loại sự cố</option>
                <option value="HU_HONG" <?= ($boLoc['loai_su_co'] === 'HU_HONG') ? 'selected' : '' ?>>Hư hỏng</option>
                <option value="MAT" <?= ($boLoc['loai_su_co'] === 'MAT') ? 'selected' : '' ?>>Mất / Thất lạc</option>
                <option value="SAI_VI_TRI" <?= ($boLoc['loai_su_co'] === 'SAI_VI_TRI') ? 'selected' : '' ?>>Sai vị trí phòng</option>
            </select>
        </div>

        <div class="col-12 col-sm-6 col-md-2">
            <label class="nhan-truong">Trạng thái xử lý</label>
            <select name="trang_thai" class="o-nhap">
                <option value="">Tất cả trạng thái</option>
                <option value="CHUA_XU_LY" <?= ($boLoc['trang_thai'] === 'CHUA_XU_LY') ? 'selected' : '' ?>>Chưa xử lý</option>
                <option value="DANG_XU_LY" <?= ($boLoc['trang_thai'] === 'DANG_XU_LY') ? 'selected' : '' ?>>Đang xử lý</option>
                <option value="DA_XU_LY" <?= ($boLoc['trang_thai'] === 'DA_XU_LY') ? 'selected' : '' ?>>Đã xử lý xong</option>
            </select>
        </div>

        <div class="col-12 col-sm-6 col-md-2">
            <label class="nhan-truong">Phòng trực quản lý</label>
            <select name="phong_truc_id" class="o-nhap" <?= ($nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN && $phongTrucId > 0) ? 'disabled' : '' ?>>
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

        <div class="col-12 col-sm-6 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-chinh w-100">Lọc</button>
            <a href="<?= url('su-co/index.php') ?>" class="btn btn-phu">Xóa</a>
        </div>
    </form>
</div>

<!-- Bảng danh sách sự cố -->
<div class="the-noi-dung mb-4">
    <div class="the-noi-dung-dau d-flex justify-content-between align-items-center">
        <h3 class="tieu-de-khoi mb-0">Danh sách sự cố tài sản</h3>
        <span class="badge bg-light text-secondary border"><?= (int)$tongSo ?> sự cố</span>
    </div>

    <div class="the-noi-dung-than p-0">
        <div class="table-responsive">
            <table class="bang-du-lieu mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 140px;">Mã sự cố</th>
                        <th>Thiết bị gặp sự cố</th>
                        <th style="width: 150px;">Bàn trực / Vị trí</th>
                        <th style="width: 130px;">Phân loại</th>
                        <th style="width: 140px;">Nguồn phát sinh</th>
                        <th style="width: 130px;">Trạng thái</th>
                        <th style="width: 140px;">Thời gian phát hiện</th>
                        <th style="width: 80px;" class="text-end">Chi tiết</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($danhSachSuCo)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                Không có sự cố nào khớp với điều kiện lọc hiện tại.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($danhSachSuCo as $idx => $sc): ?>
                            <tr>
                                <td class="text-muted"><?= ($trangHienTai - 1) * $soMoiTrang + $idx + 1 ?></td>
                                <td class="font-monospace fw-semibold text-primary">
                                    <a href="<?= url('su-co/chi-tiet.php?id=' . $sc['id']) ?>" class="text-primary text-decoration-none">
                                        <?= e($sc['ma_su_co']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark"><?= e($sc['ten_tai_san']) ?></div>
                                    <div class="text-muted small font-monospace">
                                        <?= e($sc['ma_tai_san']) ?> &bull; <?= e($sc['ten_loai_tai_san'] ?? 'Thiết bị') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold small"><?= e($sc['ten_phong_truc'] ?? 'Kho trực') ?></div>
                                    <?php if ($sc['ma_phong_hien_tai']): ?>
                                        <div class="text-muted small">Phòng <?= e($sc['ma_phong_hien_tai']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sc['loai_su_co'] === 'HU_HONG'): ?>
                                        <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Hư hỏng</span>
                                    <?php elseif ($sc['loai_su_co'] === 'MAT'): ?>
                                        <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Mất mát</span>
                                    <?php elseif ($sc['loai_su_co'] === 'SAI_VI_TRI'): ?>
                                        <span class="huy-hieu-tt huy-hieu-tt-canh-bao">Sai vị trí</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sc['ma_phieu_muon']): ?>
                                        <span class="badge bg-light text-primary border">
                                            <?= e($sc['ma_phieu_muon']) ?>
                                        </span>
                                    <?php elseif ($sc['ma_dot_kiem_ke']): ?>
                                        <span class="badge bg-light text-warning-emphasis border">
                                            <?= e($sc['ma_dot_kiem_ke']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-secondary border">
                                            Báo cáo trực tiếp
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sc['trang_thai'] === 'CHUA_XU_LY'): ?>
                                        <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Chưa xử lý</span>
                                    <?php elseif ($sc['trang_thai'] === 'DANG_XU_LY'): ?>
                                        <span class="huy-hieu-tt huy-hieu-tt-canh-bao">Đang xử lý</span>
                                    <?php elseif ($sc['trang_thai'] === 'DA_XU_LY'): ?>
                                        <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Đã xử lý</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= dinh_dang_ngay_gio($sc['thoi_gian_phat_hien']) ?></div>
                                    <div class="text-muted small text-truncate" style="max-width: 140px;">
                                        bởi <?= e($sc['ho_ten_nguoi_lap']) ?>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('su-co/chi-tiet.php?id=' . $sc['id']) ?>" class="btn btn-sm btn-phu">
                                        Xem
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Phân trang -->
    <?php if ($tongTrang > 1): ?>
        <div class="the-noi-dung-chan d-flex justify-content-between align-items-center py-2">
            <span class="small text-muted">Trang <?= $trangHienTai ?> / <?= $tongTrang ?> (Tổng <?= $tongSo ?> bản ghi)</span>
            <div class="d-flex align-items-center gap-1">
                <?php for ($p = 1; $p <= $tongTrang; $p++): ?>
                    <a class="btn btn-sm <?= ($p === $trangHienTai) ? 'btn-chinh' : 'btn-phu' ?>" href="<?= url('su-co/index.php?' . http_build_query(array_merge($boLoc, ['trang' => $p]))) ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
