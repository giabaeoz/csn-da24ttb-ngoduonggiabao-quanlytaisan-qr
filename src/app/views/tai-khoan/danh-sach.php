<?php
/**
 * Giao diện Danh sách & Quản lý Tài khoản Cán bộ - Chuẩn Design System
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var list<array<string, mixed>> $danhSachTaiKhoan
 * @var array<string, int> $thongKe
 * @var list<array<string, mixed>> $danhSachPhongTruc
 * @var array<string, mixed> $nguoiDungHienTai
 * @var int $nguoiThucHienId
 * @var int $trang
 * @var int $tongSoTrang
 * @var int $tongSo
 * @var string $tuKhoa
 * @var string $vaiTro
 * @var int $phongTrucId
 * @var string $trangThai
 */

$tieuDeTrang = 'Tài khoản cán bộ';
require dirname(__DIR__) . '/layout/dau-trang.php';

$viTriBatDau = ($trang - 1) * 15 + (count($danhSachTaiKhoan) > 0 ? 1 : 0);
$viTriKetThuc = ($trang - 1) * 15 + count($danhSachTaiKhoan);
?>

<div class="thanh-cong-cu-trang">
    <div>
        <h1 class="tieu-de-trang">Tài khoản cán bộ</h1>
        
    </div>
    <div>
        <a href="<?= url('tai-khoan/them.php') ?>" class="btn btn-chinh">
            <?= icon('plus', 'bieu-tuong-sm') ?>
            <span>Thêm tài khoản</span>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="o-bieu-tuong-thong-ke"><?= icon('users', 'bieu-tuong-lg') ?></div>
            <div>
                <div class="so-lieu"><?= sprintf('%02d', (int)($thongKe['tong_so'] ?? 0)) ?></div>
                <div class="nhan-so-lieu">Tổng tài khoản</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="o-bieu-tuong-thong-ke"><?= icon('check-circle-2', 'bieu-tuong-lg') ?></div>
            <div>
                <div class="so-lieu"><?= sprintf('%02d', (int)($thongKe['dang_hoat_dong'] ?? 0)) ?></div>
                <div class="nhan-so-lieu">Đang hoạt động</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="o-bieu-tuong-thong-ke"><?= icon('user-cog', 'bieu-tuong-lg') ?></div>
            <div>
                <div class="so-lieu"><?= sprintf('%02d', (int)($thongKe['nhan_vien'] ?? 0)) ?></div>
                <div class="nhan-so-lieu">Nhân viên trực</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="the-so-lieu">
            <div class="o-bieu-tuong-thong-ke"><?= icon('lock', 'bieu-tuong-lg') ?></div>
            <div>
                <div class="so-lieu <?= ((int)($thongKe['tam_khoa'] ?? 0) > 0) ? 'chu-nguy-hiem' : '' ?>">
                    <?= sprintf('%02d', (int)($thongKe['tam_khoa'] ?? 0)) ?>
                </div>
                <div class="nhan-so-lieu">Tạm khóa</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-3">
        <form action="<?= url('tai-khoan/index.php') ?>" method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label">Tìm kiếm</label>
                    <div class="o-tim-kiem">
                        <?= icon('search', 'bieu-tuong-sm') ?>
                        <input type="text" name="q" class="form-control"
                               placeholder="Mã NV, tên đăng nhập, họ tên..."
                               value="<?= e($tuKhoa ?? '') ?>">
                    </div>
                </div>

                <div class="col-6 col-md-3">
                    <label class="form-label">Vai trò</label>
                    <select name="vai_tro" class="form-select">
                        <option value="">Tất cả vai trò</option>
                        <?php foreach (TEN_VAI_TRO as $maVt => $tenVt): ?>
                            <option value="<?= e($maVt) ?>" <?= ($vaiTro === $maVt) ? 'selected' : '' ?>>
                                <?= e($tenVt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Phòng trực</label>
                    <select name="phong_truc_id" class="form-select">
                        <option value="">Tất cả phòng trực</option>
                        <?php foreach ($danhSachPhongTruc as $pt): ?>
                            <option value="<?= (int)$pt['id'] ?>" <?= ((int)$phongTrucId === (int)$pt['id']) ? 'selected' : '' ?>>
                                <?= e($pt['ten_phong_truc']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label">Trạng thái</label>
                    <select name="trang_thai" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="1" <?= ($trangThai === '1') ? 'selected' : '' ?>>Đang hoạt động</option>
                        <option value="0" <?= ($trangThai === '0') ? 'selected' : '' ?>>Tạm khóa</option>
                    </select>
                </div>

                <div class="col-6 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-phu flex-grow-1">
                        <?= icon('filter', 'bieu-tuong-sm') ?>
                        <span>Lọc</span>
                    </button>
                    <a href="<?= url('tai-khoan/index.php') ?>" class="btn btn-phu">
                        <?= icon('rotate-ccw', 'bieu-tuong-sm') ?>
                        <span>Đặt lại</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="tieu-de-khoi mb-0">Danh sách tài khoản cán bộ</h2>
        </div>
        <div class="mo-ta-phu">
            Hiển thị <?= count($danhSachTaiKhoan) ?> / <?= $tongSo ?> tài khoản
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã nhân viên</th>
                    <th>Họ và tên</th>
                    <th>Tên đăng nhập</th>
                    <th>Vai trò</th>
                    <th>Phòng trực</th>
                    <th>Điện thoại</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danhSachTaiKhoan)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-phu">
                            Không có tài khoản nào phù hợp.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danhSachTaiKhoan as $tk):
                        $laChinhMinh = ((int)$tk['id'] === $nguoiThucHienId);
                        $dangHoatDong = ((int)$tk['dang_hoat_dong'] === 1);
                        $coGiaoDich = ((int)($tk['tong_giao_dich'] ?? 0) > 0);
                    ?>
                        <tr>
                            <td class="fw-semibold font-monospace">
                                <?= e($tk['ma_nhan_vien']) ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">
                                    <?= e($tk['ho_ten']) ?>
                                    <?php if ($laChinhMinh): ?>
                                        <span class="mo-ta-phu">(Bạn)</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="font-monospace">
                                <?= e($tk['ten_dang_nhap']) ?>
                            </td>
                            <td>
                                <?php if ($tk['vai_tro'] === VAI_TRO_QUAN_TRI): ?>
                                    <span class="huy-hieu huy-hieu-chinh"><?= e(TEN_VAI_TRO[VAI_TRO_QUAN_TRI]) ?></span>
                                <?php elseif ($tk['vai_tro'] === VAI_TRO_QUAN_LY): ?>
                                    <span class="huy-hieu huy-hieu-canh-bao"><?= e(TEN_VAI_TRO[VAI_TRO_QUAN_LY]) ?></span>
                                <?php else: ?>
                                    <span class="huy-hieu huy-hieu-xam"><?= e(TEN_VAI_TRO[VAI_TRO_NHAN_VIEN]) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($tk['ten_phong_truc'])): ?>
                                    <?= e($tk['ten_phong_truc']) ?>
                                <?php else: ?>
                                    <span class="text-phu">Toàn hệ thống</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= e($tk['so_dien_thoai'] ?: '---') ?>
                            </td>
                            <td>
                                <?php if ($dangHoatDong): ?>
                                    <span class="huy-hieu huy-hieu-thanh-cong">Đang hoạt động</span>
                                <?php else: ?>
                                    <span class="huy-hieu huy-hieu-nguy-hiem">Tạm khóa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <?php
                                    $chiTietTk = [
                                        ['nhan' => 'Mã nhân viên', 'gia_tri' => $tk['ma_nhan_vien']],
                                        ['nhan' => 'Họ và tên', 'gia_tri' => $tk['ho_ten']],
                                        ['nhan' => 'Tên đăng nhập', 'gia_tri' => $tk['ten_dang_nhap']],
                                        ['nhan' => 'Vai trò', 'gia_tri' => TEN_VAI_TRO[$tk['vai_tro']] ?? $tk['vai_tro']],
                                        ['nhan' => 'Phòng trực', 'gia_tri' => $tk['ten_phong_truc'] ?: 'Toàn hệ thống'],
                                        ['nhan' => 'Số điện thoại', 'gia_tri' => $tk['so_dien_thoai'] ?: '---'],
                                        ['nhan' => 'Trạng thái', 'gia_tri' => $dangHoatDong ? 'Đang hoạt động' : 'Tạm khóa'],
                                    ];
                                    ?>
                                    <button type="button" class="btn-vien-thuoc"
                                            onclick='xemChiTietNhanh(<?= json_encode("Tài khoản: " . $tk["ho_ten"]) ?>, <?= json_encode($chiTietTk, JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode(url("tai-khoan/sua.php?id=" . (int)$tk["id"])) ?>)'>
                                        <span>Xem</span>
                                    </button>
                                    <a href="<?= url('tai-khoan/sua.php?id=' . (int)$tk['id']) ?>" class="btn-vien-thuoc">
                                        <span>Sửa</span>
                                    </a>

                                    <button type="button" class="btn-vien-thuoc"
                                            onclick='moModalDatLaiMatKhau(<?= (int)$tk["id"] ?>, <?= json_encode((string)$tk["ten_dang_nhap"]) ?>, <?= json_encode((string)$tk["ho_ten"]) ?>)'>
                                        <span>Đổi MK</span>
                                    </button>

                                    <?php if (!$laChinhMinh): ?>
                                        <form action="<?= url('tai-khoan/index.php') ?>" method="POST" class="d-inline"
                                              data-xac-nhan="Bạn có chắc muốn <?= $dangHoatDong ? 'khóa' : 'mở khóa' ?> tài khoản này?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="hanh_dong" value="doi_trang_thai">
                                            <input type="hidden" name="id" value="<?= (int)$tk['id'] ?>">
                                            <?php $nhanKhoaMoTk = $dangHoatDong ? 'Khóa' : 'Mở khóa'; ?>
                                            <button type="submit" class="btn-vien-thuoc">
                                                <span><?= $nhanKhoaMoTk ?></span>
                                            </button>
                                        </form>

                                        <?php if (!$coGiaoDich): ?>
                                            <form action="<?= url('tai-khoan/index.php') ?>" method="POST" class="d-inline"
                                                  data-xac-nhan="Bạn có chắc chắn muốn XÓA tài khoản này? Thao tác không thể hoàn tác.">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="hanh_dong" value="xoa">
                                                <input type="hidden" name="id" value="<?= (int)$tk['id'] ?>">
                                                <button type="submit" class="btn-vien-thuoc text-danger" title="Xóa tài khoản">
                                                    <span>Xóa</span>
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

    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
        <div class="mo-ta-phu">
            <?= $viTriBatDau ?>–<?= $viTriKetThuc ?> trong <?= $tongSo ?> tài khoản
        </div>
        <div>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= ($trang <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= url('tai-khoan/index.php?' . http_build_query(array_merge($_GET, ['trang' => max(1, $trang - 1)]))) ?>">Trước</a>
                </li>
                <?php for ($i = 1; $i <= $tongSoTrang; $i++): ?>
                    <li class="page-item <?= ($i === $trang) ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url('tai-khoan/index.php?' . http_build_query(array_merge($_GET, ['trang' => $i]))) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= ($trang >= $tongSoTrang) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= url('tai-khoan/index.php?' . http_build_query(array_merge($_GET, ['trang' => min($tongSoTrang, $trang + 1)]))) ?>">Sau</a>
                </li>
            </ul>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDatLaiMatKhau" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="<?= url('tai-khoan/index.php') ?>" method="POST" class="modal-content border-0 shadow">
            <?= csrf_field() ?>
            <input type="hidden" name="hanh_dong" value="dat_lai_mat_khau">
            <input type="hidden" name="id" id="reset_id" value="">

            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Đặt lại mật khẩu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body p-4">
                <div class="mb-3">
                    <div class="mo-ta-phu">Tài khoản cán bộ:</div>
                    <div class="fw-semibold text-dark" id="reset_thong_tin"></div>
                </div>

                <div class="mb-3">
                    <label for="mat_khau_moi" class="form-label">
                        Mật khẩu mới <span class="text-danger">*</span>
                    </label>
                    <input type="password" class="form-control" id="mat_khau_moi" name="mat_khau_moi"
                           minlength="6" placeholder="Nhập tối thiểu 6 ký tự" required>
                </div>

                <div class="mb-2">
                    <label for="xac_nhan_mat_khau" class="form-label">
                        Xác nhận mật khẩu mới <span class="text-danger">*</span>
                    </label>
                    <input type="password" class="form-control" id="xac_nhan_mat_khau" name="xac_nhan_mat_khau"
                           minlength="6" placeholder="Nhập lại mật khẩu mới" required>
                </div>
            </div>

            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-phu" data-bs-dismiss="modal">
                    <?= icon('x', 'bieu-tuong-sm') ?>
                    <span>Hủy</span>
                </button>
                <button type="submit" class="btn btn-chinh">
                    <?= icon('save', 'bieu-tuong-sm') ?>
                    <span>Lưu mật khẩu mới</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function moModalDatLaiMatKhau(id, tenDangNhap, hoTen) {
    document.getElementById('reset_id').value = id;
    document.getElementById('reset_thong_tin').textContent = hoTen + ' (' + tenDangNhap + ')';
    document.getElementById('mat_khau_moi').value = '';
    document.getElementById('xac_nhan_mat_khau').value = '';

    const modal = new bootstrap.Modal(document.getElementById('modalDatLaiMatKhau'));
    modal.show();
}
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
