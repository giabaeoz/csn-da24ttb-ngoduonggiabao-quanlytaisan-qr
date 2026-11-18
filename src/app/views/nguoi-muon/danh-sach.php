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
 */

$tieuDeTrang = 'Người mượn';
require dirname(__DIR__) . '/layout/dau-trang.php';

// Tính toán số lượng cho tabs và tiêu đề
$tongDangMuon = $demDangMuon ?? count($danhSachNguoiMuon);
$tongTatCa = $demTatCa ?? count($danhSachNguoiMuon);
$tongQuaHan = $demQuaHan ?? 0;
$tongChuaTra = 0;
foreach ($danhSachNguoiMuon as $nm) {
    $tongChuaTra += (int)($nm['so_thiet_bi_chua_tra'] ?? $nm['so_thiet_bi_dang_muon'] ?? 0);
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
    <a href="<?= url('nguoi-muon/index.php?tab=tat_ca') ?>" class="btn <?= ($tabHienTai === 'tat_ca') ? 'btn-chinh' : 'btn-phu bg-white' ?>">
        Tất cả (<?= (int)$tongTatCa ?>)
    </a>
    <a href="<?= url('nguoi-muon/index.php?tab=dang_muon') ?>" class="btn <?= ($tabHienTai === 'dang_muon') ? 'btn-chinh' : 'btn-phu bg-white' ?>">
        Đang mượn (<?= (int)$tongDangMuon ?>)
    </a>
    <a href="<?= url('nguoi-muon/index.php?tab=qua_han') ?>" class="btn <?= ($tabHienTai === 'qua_han') ? 'btn-chinh' : 'btn-phu bg-white' ?>">
        Quá hạn (<?= (int)$tongQuaHan ?>)
    </a>
</div>

<div class="card mb-4">
    <div class="card-body p-3">
        <form action="<?= url('nguoi-muon/index.php') ?>" method="GET" class="row g-3 align-items-end">
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
            <h2 class="tieu-de-khoi mb-0">Danh sách người mượn tài sản</h2>
        </div>
        <span class="huy-hieu huy-hieu-chinh">
            <?= count($danhSachNguoiMuon) ?> người • <?= $tongChuaTra ?> thiết bị chưa trả
        </span>
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
                        $soChuaTraNm = (int)($nm['so_thiet_bi_chua_tra'] ?? $nm['so_thiet_bi_dang_muon'] ?? 0);
                        $soQuaHanNm = (int)($nm['so_thiet_bi_qua_han'] ?? 0);
                        $hanTraNm = (string)($nm['han_tra_gan_nhat'] ?? '');
                    ?>
                        <tr>
                            <td class="text-nowrap">
                                <div class="fw-bold text-primary text-nowrap"><?= e($nm['ma_so_truong']) ?></div>
                                <div class="text-dark text-nowrap"><?= e($nm['ho_ten']) ?></div>
                            </td>
                            <td class="text-nowrap"><?= e($nm['lop_don_vi'] ?: '---') ?></td>
                            <td class="text-nowrap"><?= e($nm['phong_hoc_gan_nhat'] ?: '---') ?></td>
                            <td class="text-nowrap"><?= e($nm['so_dien_thoai'] ?: '---') ?></td>
                            <td class="text-center text-nowrap"><span class="badge rounded-pill bg-light text-dark border px-2 py-1"><?= $soChuaTraNm ?></span></td>
                            <td class="text-nowrap">
                                <?php if ($soChuaTraNm > 0): ?>
                                    <div class="mb-1 text-nowrap"><?= $hanTraNm !== '' ? date('d/m • H:i', strtotime($hanTraNm)) : '---' ?></div>
                                    <?php if ($soQuaHanNm > 0): ?>
                                        <span class="huy-hieu huy-hieu-nguy-hiem"><?= icon('alert-triangle') ?> <span>Quá hạn</span></span>
                                    <?php else: ?>
                                        <span class="huy-hieu huy-hieu-thanh-cong"><?= icon('check-circle-2') ?> <span>Trong hạn</span></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="huy-hieu huy-hieu-xam"><span>Không nợ TB</span></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-2 align-items-center">
                                    <a href="<?= url('nguoi-muon/chi-tiet.php?id=' . (int)$nm['id']) ?>" class="btn-bieu-tuong" title="Chi tiết">
                                        <?= icon('eye') ?>
                                    </a>
                                    <a href="<?= url('nguoi-muon/sua.php?id=' . (int)$nm['id']) ?>" class="btn-bieu-tuong" title="Sửa">
                                        <?= icon('pencil') ?>
                                    </a>
                                    <form action="<?= url('nguoi-muon/index.php') ?>" method="POST" class="d-inline" data-xac-nhan="Bạn có chắc chắn muốn xóa hồ sơ người mượn này?">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="hanh_dong" value="xoa">
                                        <input type="hidden" name="id" value="<?= (int)$nm['id'] ?>">
                                        <button type="submit" class="btn-bieu-tuong text-danger" title="Xóa người mượn">
                                            <?= icon('trash-2') ?>
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

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
