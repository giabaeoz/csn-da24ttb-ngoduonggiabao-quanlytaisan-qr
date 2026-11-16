<?php
/**
 * Giao diện: Tra cứu, thống kê (Bám sát 100% Trang tra cứu - thống kê.pdf)
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var int $kpiThietBi
 * @var int $kpiPhieuMuon
 * @var int $kpiChuaTraDu
 * @var int $kpiDaTraDu
 * @var list<string> $chartLabels
 * @var list<int> $chartMuon
 * @var list<int> $chartTra
 * @var list<string> $loaiLabels
 * @var list<int> $loaiData
 * @var int $tongThietBiLoai
 * @var list<array<string, mixed>> $danhSach
 * @var list<array<string, mixed>> $danhSachDc
 * @var list<array<string, mixed>> $danhSachKk
 * @var list<array<string, mixed>> $danhSachPhongHoc
 * @var list<array<string, mixed>> $danhSachPhongTruc
 * @var string $tuNgay
 * @var string $denNgay
 * @var string $tuKhoa
 * @var string $trangThai
 * @var string $tab
 * @var int $phongHocId
 * @var int $phongTrucFilter
 * @var int $tongSo
 * @var int $tongSoTrang
 * @var int $trang
 * @var int $offset
 * @var array<string, mixed> $nguoiDung
 */

$tieuDeTrang = 'Tra cứu, thống kê';
require dirname(__DIR__) . '/layout/dau-trang.php';
?>

<div class="thanh-cong-cu-trang mb-3">
    <div>
        <h1 class="tieu-de-trang mb-0">Tra cứu, thống kê</h1>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('thong-ke/index.php?' . http_build_query(array_merge($_GET, ['xuat' => 'excel']))) ?>" class="btn btn-phu">
            <?= icon('save', 'bieu-tuong-sm') ?>
            <span>Xuất Excel</span>
        </a>
        <button type="button" class="btn btn-phu" onclick="window.print()">
            <?= icon('printer', 'bieu-tuong-sm') ?>
            <span>In báo cáo</span>
        </button>
    </div>
</div>

<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
    <a class="btn-tab-xanh <?= $tab === 'muon-tra' ? 'active' : '' ?>" href="<?= url('thong-ke/index.php?tab=muon-tra') ?>">
        <?= icon('repeat', 'bieu-tuong-sm') ?>
        <span>Mượn – trả</span>
    </a>
    <a class="btn-tab-xanh <?= $tab === 'dieu-chuyen' ? 'active' : '' ?>" href="<?= url('thong-ke/index.php?tab=dieu-chuyen') ?>">
        <?= icon('arrow-left-right', 'bieu-tuong-sm') ?>
        <span>Điều chuyển</span>
    </a>
    <a class="btn-tab-xanh <?= $tab === 'kiem-ke' ? 'active' : '' ?>" href="<?= url('thong-ke/index.php?tab=kiem-ke') ?>">
        <?= icon('clipboard-check', 'bieu-tuong-sm') ?>
        <span>Kiểm kê</span>
    </a>
</div>

<?php if ($tab === 'muon-tra'): ?>

    <div class="the-noi-dung p-3 mb-4">
        <form method="GET" action="<?= url('thong-ke/index.php') ?>">
            <input type="hidden" name="tab" value="muon-tra">
            <?php if ($phongTrucFilter !== null && $nguoiDung['vai_tro'] !== VAI_TRO_NHAN_VIEN): ?>
                <input type="hidden" name="phong_truc_id" value="<?= (int)$phongTrucFilter ?>">
            <?php endif; ?>

            <div class="row g-3 align-items-end">
                <div class="col-12 col-lg-3">
                    <label class="nhan-truong">Từ khóa</label>
                    <div class="o-tim-kiem">
                        <?= icon('search', 'bieu-tuong-sm') ?>
                        <input type="text" name="tu_khoa" class="o-nhap" 
                               placeholder="Mã phiếu / họ tên / thiết bị..." 
                               value="<?= e($tuKhoa) ?>">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="nhan-truong">Phòng học</label>
                    <select name="phong_hoc_id" class="o-nhap">
                        <option value="">Tất cả phòng</option>
                        <?php foreach ($danhSachPhongHoc as $ph): ?>
                            <option value="<?= (int)$ph['id'] ?>" <?= $phongHocId === (int)$ph['id'] ? 'selected' : '' ?>>
                                <?= e($ph['ma_phong']) ?> (<?= e($ph['ten_phong']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="nhan-truong">Trạng thái phiếu</label>
                    <select name="trang_thai" class="o-nhap">
                        <option value="TAT_CA" <?= ($trangThai === 'TAT_CA' || $trangThai === '') ? 'selected' : '' ?>>Tất cả</option>
                        <option value="DANG_MUON" <?= $trangThai === 'DANG_MUON' ? 'selected' : '' ?>>Đang mượn</option>
                        <option value="TRA_MOT_PHAN" <?= $trangThai === 'TRA_MOT_PHAN' ? 'selected' : '' ?>>Trả một phần</option>
                        <option value="DA_TRA" <?= $trangThai === 'DA_TRA' ? 'selected' : '' ?>>Đã trả đủ</option>
                        <option value="QUA_HAN" <?= $trangThai === 'QUA_HAN' ? 'selected' : '' ?>>Quá hạn</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="nhan-truong">Từ ngày</label>
                    <input type="date" name="tu_ngay" class="o-nhap" value="<?= e($tuNgay) ?>">
                </div>
                <div class="col-6 col-lg-3 d-flex gap-2 align-items-end">
                    <div class="flex-grow-1">
                        <label class="nhan-truong">Đến ngày</label>
                        <input type="date" name="den_ngay" class="o-nhap" value="<?= e($denNgay) ?>">
                    </div>
                    <button type="submit" class="btn btn-chinh px-3" title="Tra cứu">
                        <?= icon('search', 'bieu-tuong-sm') ?>
                        <span>Tra cứu</span>
                    </button>
                    <a href="<?= url('thong-ke/index.php?tab=muon-tra') ?>" class="btn btn-phu px-2" title="Xóa bộ lọc">
                        <?= icon('rotate-ccw', 'bieu-tuong-sm') ?>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="the-noi-dung mb-4">
        <div class="the-noi-dung-dau d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="tieu-de-khoi mb-0">Danh sách kết quả mượn – trả</h3>
            </div>
            <div>
                <span class="badge bg-light text-secondary border px-2 py-1 fw-semibold">
                    <?= (int)$tongSo ?> phiếu
                </span>
            </div>
        </div>

        <div class="the-noi-dung-than p-0">
            <div class="table-responsive">
                <table class="bang-du-lieu mb-0">
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Người mượn</th>
                            <th>Phòng</th>
                            <th class="text-center">Thiết bị</th>
                            <th>Mượn / hạn trả</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($danhSach)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Không tìm thấy phiếu mượn nào phù hợp với điều kiện tra cứu.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($danhSach as $row): ?>
                                <?php
                                $soTb = (int)($row['so_thiet_bi'] ?? 0);
                                $soChuaTra = (int)($row['so_chua_tra'] ?? 0);
                                $isOverdue = ($row['trang_thai'] === 'DANG_MUON' && !empty($row['han_tra']) && strtotime((string)$row['han_tra']) < time());
                                $isPartial = ($row['trang_thai'] === 'DANG_MUON' && $soChuaTra < $soTb && $soChuaTra > 0);
                                ?>
                                <tr>
                                    <td class="fw-semibold text-primary">
                                        <?= e($row['ma_phieu_muon']) ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-secondary small"><?= e($row['ma_so_truong']) ?></div>
                                        <div class="fw-medium text-dark"><?= e($row['ho_ten_nguoi_muon']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= e($row['ma_phong']) ?></span>
                                    </td>
                                    <td class="text-center fw-bold">
                                        <?= $soTb ?>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold"><?= !empty($row['thoi_gian_muon']) ? date('d/m • H:i', strtotime((string)$row['thoi_gian_muon'])) : '—' ?></div>
                                        <div class="small text-muted"><?= !empty($row['han_tra']) ? date('d/m • H:i', strtotime((string)$row['han_tra'])) : '—' ?></div>
                                    </td>
                                    <td>
                                        <?php if ($row['trang_thai'] === 'DA_TRA'): ?>
                                            <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Đã trả đủ</span>
                                        <?php elseif ($isOverdue): ?>
                                            <span class="huy-hieu-tt huy-hieu-tt-nguy-hiem">Quá hạn</span>
                                        <?php elseif ($isPartial): ?>
                                            <span class="huy-hieu-tt huy-hieu-tt-canh-bao">Trả một phần</span>
                                            <div class="text-muted small mt-1">Còn <?= $soChuaTra ?> thiết bị</div>
                                        <?php else: ?>
                                            <span class="huy-hieu-tt huy-hieu-tt-chinh">Đang mượn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= url('muon-tra/chi-tiet.php?id=' . (int)$row['id']) ?>" class="btn-vien-thuoc">
                                            <span>Chi tiết</span>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($tongSo > 0): ?>
            <?php
            $tuBanGhi = $offset + 1;
            $denBanGhi = min($offset + count($danhSach), $tongSo);
            $queryBase = $_GET;
            unset($queryBase['trang']);
            ?>
            <div class="the-noi-dung-chan d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="mo-ta-phu mb-0">
                    Hiển thị <?= $tuBanGhi ?>–<?= $denBanGhi ?> / <?= (int)$tongSo ?> phiếu
                </div>
                <?php if ($tongSoTrang > 1): ?>
                    <div class="d-flex align-items-center gap-1">
                        <?php if ($trang > 1): ?>
                            <a href="<?= url('thong-ke/index.php?' . http_build_query(array_merge($queryBase, ['trang' => $trang - 1]))) ?>" class="btn btn-sm btn-phu">
                                Trước
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-phu" disabled>Trước</button>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $tongSoTrang; $p++): ?>
                            <?php if ($p === $trang): ?>
                                <span class="btn btn-sm btn-chinh"><?= $p ?></span>
                            <?php elseif ($p <= 3 || $p >= $tongSoTrang - 2 || abs($p - $trang) <= 1): ?>
                                <a href="<?= url('thong-ke/index.php?' . http_build_query(array_merge($queryBase, ['trang' => $p]))) ?>" class="btn btn-sm btn-phu">
                                    <?= $p ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($trang < $tongSoTrang): ?>
                            <a href="<?= url('thong-ke/index.php?' . http_build_query(array_merge($queryBase, ['trang' => $trang + 1]))) ?>" class="btn btn-sm btn-phu">
                                Sau
                            </a>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-phu" disabled>Sau</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($tab === 'dieu-chuyen'): ?>

    <div class="the-noi-dung p-3 mb-4">
        <form method="GET" action="<?= url('thong-ke/index.php') ?>" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="dieu-chuyen">
            <div class="col-12 col-md-5">
                <label class="nhan-truong">Từ khóa điều chuyển</label>
                <div class="o-tim-kiem">
                    <?= icon('search', 'bieu-tuong-sm') ?>
                    <input type="text" name="tu_khoa" class="o-nhap" placeholder="Mã phiếu / phòng chuyển / người lập..." value="<?= e($tuKhoa) ?>">
                </div>
            </div>
            <div class="col-6 col-md-2">
                <label class="nhan-truong">Từ ngày</label>
                <input type="date" name="tu_ngay" class="o-nhap" value="<?= e($tuNgay) ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="nhan-truong">Đến ngày</label>
                <input type="date" name="den_ngay" class="o-nhap" value="<?= e($denNgay) ?>">
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-chinh">
                    <?= icon('search', 'bieu-tuong-sm') ?>
                    <span>Tra cứu</span>
                </button>
                <a href="<?= url('thong-ke/index.php?tab=dieu-chuyen') ?>" class="btn btn-phu">
                    <?= icon('rotate-ccw', 'bieu-tuong-sm') ?>
                    <span>Xóa lọc</span>
                </a>
            </div>
        </form>
    </div>

    <div class="the-noi-dung mb-4">
        <div class="the-noi-dung-dau d-flex justify-content-between align-items-center">
            <h3 class="tieu-de-khoi mb-0">Danh sách kết quả điều chuyển</h3>
            <span class="badge bg-light text-secondary border"><?= (int)$tongSo ?> phiếu</span>
        </div>
        <div class="the-noi-dung-than p-0">
            <div class="table-responsive">
                <table class="bang-du-lieu mb-0">
                    <thead>
                        <tr>
                            <th>Mã phiếu</th>
                            <th>Ngày chuyển</th>
                            <th>Phòng đi</th>
                            <th>Phòng đến</th>
                            <th class="text-center">Số thiết bị</th>
                            <th>Người tạo</th>
                            <th class="text-end">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($danhSachDc)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Không có dữ liệu điều chuyển.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($danhSachDc as $dc): ?>
                                <tr>
                                    <td class="fw-semibold text-primary"><?= e($dc['ma_dieu_chuyen'] ?? $dc['ma_phieu_dieu_chuyen'] ?? $dc['ma_phieu'] ?? '') ?></td>
                                    <td><?= !empty($dc['thoi_gian_tao'] ?? $dc['ngay_tao'] ?? '') ? date('d/m/Y H:i', strtotime((string)($dc['thoi_gian_tao'] ?? $dc['ngay_tao']))) : '—' ?></td>
                                    <td><?= e($dc['ma_phong_di'] ?? $dc['ten_phong_chuyen'] ?? $dc['phong_chuyen_ten'] ?? '—') ?></td>
                                    <td><?= e($dc['ma_phong_den'] ?? $dc['ten_phong_nhan'] ?? $dc['phong_nhan_ten'] ?? '—') ?></td>
                                    <td class="text-center fw-bold"><?= (int)($dc['tong_so_thiet_bi'] ?? $dc['so_luong_thiet_bi'] ?? $dc['so_thiet_bi'] ?? 0) ?></td>
                                    <td><?= e($dc['ho_ten_can_bo'] ?? $dc['ho_ten_nguoi_tao'] ?? $dc['nguoi_tao_ten'] ?? '—') ?></td>
                                    <td class="text-end cot-thao-tac">
                                        <a href="<?= url('dieu-chuyen/chi-tiet.php?id=' . (int)$dc['id']) ?>" class="btn-vien-thuoc">Chi tiết</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php elseif ($tab === 'kiem-ke'): ?>

    <div class="the-noi-dung p-3 mb-4">
        <form method="GET" action="<?= url('thong-ke/index.php') ?>" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="kiem-ke">
            <div class="col-12 col-md-4">
                <label class="nhan-truong">Từ khóa kiểm kê</label>
                <div class="o-tim-kiem">
                    <?= icon('search', 'bieu-tuong-sm') ?>
                    <input type="text" name="tu_khoa" class="o-nhap" placeholder="Mã đợt / tên đợt / phòng trực..." value="<?= e($tuKhoa) ?>">
                </div>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <label class="nhan-truong">Trạng thái</label>
                <select name="trang_thai" class="o-nhap">
                    <option value="">Tất cả</option>
                    <option value="DANG_KIEM_KE" <?= $trangThai === 'DANG_KIEM_KE' ? 'selected' : '' ?>>Đang thực hiện</option>
                    <option value="HOAN_TAT" <?= $trangThai === 'HOAN_TAT' ? 'selected' : '' ?>>Hoàn tất</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="nhan-truong">Từ ngày</label>
                <input type="date" name="tu_ngay" class="o-nhap" value="<?= e($tuNgay) ?>">
            </div>
            <div class="col-6 col-md-4 d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <label class="nhan-truong">Đến ngày</label>
                    <input type="date" name="den_ngay" class="o-nhap" value="<?= e($denNgay) ?>">
                </div>
                <button type="submit" class="btn btn-chinh px-3">
                    <?= icon('search', 'bieu-tuong-sm') ?>
                    <span>Tra cứu</span>
                </button>
                <a href="<?= url('thong-ke/index.php?tab=kiem-ke') ?>" class="btn btn-phu px-2" title="Xóa bộ lọc">
                    <?= icon('rotate-ccw', 'bieu-tuong-sm') ?>
                </a>
            </div>
        </form>
    </div>

    <div class="the-noi-dung mb-4">
        <div class="the-noi-dung-dau d-flex justify-content-between align-items-center">
            <h3 class="tieu-de-khoi mb-0">Danh sách kết quả kiểm kê</h3>
            <span class="badge bg-light text-secondary border"><?= (int)$tongSo ?> đợt</span>
        </div>
        <div class="the-noi-dung-than p-0">
            <div class="table-responsive">
                <table class="bang-du-lieu mb-0">
                    <thead>
                        <tr>
                            <th>Mã đợt</th>
                            <th>Tên đợt kiểm kê</th>
                            <th>Bàn trực</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Chi tiết</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($danhSachKk)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Không có dữ liệu đợt kiểm kê.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($danhSachKk as $kk): ?>
                                <tr>
                                    <td class="fw-semibold text-primary"><?= e($kk['ma_dot_kiem_ke'] ?? '') ?></td>
                                    <td class="fw-medium"><?= e($kk['ten_dot_kiem_ke'] ?? '') ?></td>
                                    <td><?= e($kk['ten_phong_truc'] ?? '—') ?></td>
                                    <td><?= !empty($kk['thoi_gian_bat_dau'] ?? $kk['ngay_bat_dau'] ?? '') ? date('d/m/Y H:i', strtotime((string)($kk['thoi_gian_bat_dau'] ?? $kk['ngay_bat_dau']))) : '—' ?></td>
                                    <td>
                                        <?php if (($kk['trang_thai'] ?? '') === 'HOAN_TAT'): ?>
                                            <span class="huy-hieu-tt huy-hieu-tt-thanh-cong">Hoàn tất</span>
                                        <?php else: ?>
                                            <span class="huy-hieu-tt huy-hieu-tt-canh-bao">Đang thực hiện</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end cot-thao-tac">
                                        <a href="<?= url('kiem-ke/chi-tiet.php?id=' . (int)$kk['id']) ?>" class="btn-vien-thuoc">Chi tiết</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
