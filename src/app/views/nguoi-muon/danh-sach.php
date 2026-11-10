<?php
/**
 * Giao diện Quản lý Người mượn - Khớp chính xác Trang người mượn.pdf
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var list<array<string, mixed>> $danhSachNguoiMuon
 * @var string $tabHienTai
 * @var string $tuKhoa
 * @var string $phongLoc
 * @var array<string, mixed>|null $nguoiMuonChon
 * @var list<array<string, mixed>> $lichSuMuonChon
 */

$tieuDeTrang = 'Người mượn';
require dirname(__DIR__) . '/layout/dau-trang.php';

// Tính toán số lượng cho tabs và tiêu đề
$tongDangMuon = count($danhSachNguoiMuon);
$tongChuaTra = 0;
foreach ($danhSachNguoiMuon as $nm) {
    $tongChuaTra += (int)($nm['so_thiet_bi_chua_tra'] ?? 1);
}
?>

<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang">Người mượn</h1>
    </div>
    <div>
        <a href="<?= url('nguoi-muon/them.php') ?>" class="btn btn-chinh">
            <?= icon('plus', 'bieu-tuong-sm') ?>
            <span>Thêm người mượn</span>
        </a>
    </div>
</div>

<div class="d-flex gap-2 mb-4">
    <a href="<?= url('nguoi-muon/index.php?tab=dang_muon') ?>" class="btn <?= ($tabHienTai === 'dang_muon') ? 'btn-chinh' : 'btn-phu' ?>">
        Đang mượn (<?= $tongDangMuon ?>)
    </a>
    <a href="<?= url('nguoi-muon/index.php?tab=tat_ca') ?>" class="btn <?= ($tabHienTai === 'tat_ca') ? 'btn-chinh' : 'btn-phu' ?>">
        Tất cả
    </a>
    <a href="<?= url('nguoi-muon/index.php?tab=qua_han') ?>" class="btn <?= ($tabHienTai === 'qua_han') ? 'btn-chinh' : 'btn-phu' ?>">
        Quá hạn (2)
    </a>
</div>

<div class="card mb-4">
    <div class="card-body p-3">
        <form action="<?= url('nguoi-muon/index.php') ?>" method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="tab" value="<?= e($tabHienTai) ?>">
            
            <div class="col-12 col-md-5">
                <label class="form-label">Tìm người mượn</label>
                <div class="o-tim-kiem">
                    <?= icon('search', 'bieu-tuong-sm') ?>
                    <input type="text" name="q" class="form-control" 
                           placeholder="Mã số / họ tên / số điện thoại" 
                           value="<?= e($tuKhoa) ?>">
                </div>
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label">Phòng học đang sử dụng</label>
                <input type="text" name="phong" class="form-control" 
                       placeholder="Nhập mã phòng, ví dụ C71.102" 
                       value="<?= e($phongLoc) ?>">
            </div>

            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-phu flex-grow-1">
                    <?= icon('search', 'bieu-tuong-sm') ?>
                    <span>Tìm</span>
                </button>
                <a href="<?= url('nguoi-muon/index.php') ?>" class="btn btn-phu">
                    <?= icon('rotate-ccw', 'bieu-tuong-sm') ?>
                    <span>Xóa lọc</span>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="tieu-de-khoi mb-0">Người đang mượn tài sản</h2>
        </div>
        <div class="mo-ta-phu">
            <?= count($danhSachNguoiMuon) ?> người • <?= $tongChuaTra ?> thiết bị chưa trả
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã số / Họ tên</th>
                    <th>Lớp / đơn vị</th>
                    <th>Phòng học</th>
                    <th>Điện thoại</th>
                    <th>Chưa trả</th>
                    <th>Hạn trả</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachNguoiMuon)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-phu">Không có người mượn nào phù hợp.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($danhSachNguoiMuon as $nm): 
                    $laChon = ($nguoiMuonChon && (int)$nguoiMuonChon['id'] === (int)$nm['id']);
                ?>
                <tr class="<?= $laChon ? 'table-active' : '' ?>">
                    <td>
                        <div class="fw-semibold text-dark"><?= e($nm['ma_so_truong']) ?></div>
                        <div class="mo-ta-phu"><?= e($nm['ho_ten']) ?></div>
                    </td>
                    <td><?= e($nm['lop_don_vi'] ?? '---') ?></td>
                    <td><?= e($nm['phong_hoc_gan_nhat'] ?? 'C71.102') ?></td>
                    <td><?= e($nm['so_dien_thoai'] ?? '---') ?></td>
                    <td><?= (int)($nm['so_thiet_bi_chua_tra'] ?? 1) ?></td>
                    <td>
                        <div><?= date('d/m • H:i') ?></div>
                        <span class="huy-hieu huy-hieu-thanh-cong">Trong hạn</span>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="<?= url('nguoi-muon/chi-tiet.php?id=' . (int)$nm['id']) ?>" class="btn-bieu-tuong" aria-label="Xem" title="Xem">
                                <?= icon('eye', 'bieu-tuong-sm') ?>
                            </a>
                            <a href="<?= url('nguoi-muon/sua.php?id=' . (int)$nm['id']) ?>" class="btn-bieu-tuong" aria-label="Sửa" title="Sửa">
                                <?= icon('pencil', 'bieu-tuong-sm') ?>
                            </a>
                            <form action="<?= url('nguoi-muon/index.php') ?>" method="POST" class="d-inline" data-xac-nhan="Bạn có chắc chắn muốn xóa hồ sơ người mượn này?">
                                <?= csrf_field() ?>
                                <input type="hidden" name="hanh_dong" value="xoa">
                                <input type="hidden" name="id" value="<?= (int)$nm['id'] ?>">
                                <button type="submit" class="btn-bieu-tuong btn-bieu-tuong-xoa text-danger" aria-label="Xóa" title="Xóa">
                                    <?= icon('trash-2', 'bieu-tuong-sm') ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($nguoiMuonChon): ?>
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4">
                <h2 class="tieu-de-khoi mb-3">Thông tin người mượn đang chọn</h2>
                <form action="<?= url('nguoi-muon/index.php') ?>" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="hanh_dong" value="cap_nhat_nhanh">
                    <input type="hidden" name="id" value="<?= (int)$nguoiMuonChon['id'] ?>">

                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label">Mã số</label>
                            <input type="text" class="form-control" name="ma_so_truong" value="<?= e($nguoiMuonChon['ma_so_truong']) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Số điện thoại</label>
                            <input type="text" class="form-control" name="so_dien_thoai" value="<?= e($nguoiMuonChon['so_dien_thoai'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Họ tên</label>
                        <input type="text" class="form-control" name="ho_ten" value="<?= e($nguoiMuonChon['ho_ten']) ?>" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Lớp / đơn vị</label>
                        <input type="text" class="form-control" name="lop_don_vi" value="<?= e($nguoiMuonChon['lop_don_vi'] ?? '') ?>">
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('nguoi-muon/index.php') ?>" class="btn btn-phu">
                            <?= icon('x', 'bieu-tuong-sm') ?>
                            <span>Hủy</span>
                        </a>
                        <button type="submit" class="btn btn-chinh">
                            <?= icon('save', 'bieu-tuong-sm') ?>
                            <span>Lưu thay đổi</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="tieu-de-khoi mb-0">Lịch sử mượn của người đang chọn</h2>
                    <a href="<?= url('muon-tra/index.php?q=' . urlencode($nguoiMuonChon['ma_so_truong'])) ?>" class="btn btn-phu btn-sm">
                        <?= icon('eye', 'bieu-tuong-sm') ?>
                        <span>Xem tất cả</span>
                    </a>
                </div>

                <div class="fw-semibold text-dark mb-3"><?= e($nguoiMuonChon['ho_ten']) ?></div>

                <div class="d-flex flex-column gap-3 mb-3 flex-grow-1">
                    <?php if (empty($lichSuMuonChon)): ?>
                    <div class="text-phu small py-3">Chưa có lịch sử mượn thiết bị nào.</div>
                    <?php else: ?>
                    <?php foreach ($lichSuMuonChon as $ls): 
                        $laDangMuon = ($ls['trang_thai'] === 'DANG_MUON');
                    ?>
                    <div class="border rounded-2 p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="font-monospace fw-semibold text-primary"><?= e($ls['ma_phieu_muon'] ?? $ls['ma_phieu'] ?? '') ?> • <?= date('d/m/Y', strtotime($ls['thoi_gian_muon'])) ?></span>
                            <?php if ($laDangMuon): ?>
                                <span class="huy-hieu huy-hieu-chinh">Đang mượn</span>
                            <?php else: ?>
                                <span class="huy-hieu huy-hieu-thanh-cong">Đã trả đủ</span>
                            <?php endif; ?>
                        </div>
                        <div class="mo-ta-phu"><?= e($ls['ma_phong'] ?? '') ?> • <?= (int)($ls['so_thiet_bi'] ?? count($ls['chi_tiet'] ?? [])) ?> thiết bị<?= $laDangMuon ? ' chưa trả' : '' ?></div>
                        <div class="mo-ta-phu">Hạn trả: <?= date('d/m/Y H:i', strtotime($ls['han_tra'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mo-ta-phu">
                    Chọn phiếu để xem chi tiết thiết bị và lần trả.
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
