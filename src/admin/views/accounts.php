<?php

declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $list
 * @var string $keyword
 * @var string $role
 * @var string $status
 * @var int $total
 * @var int $id
 * @var callable $link
 * @var int $page
 * @var int $pages
 * @var string $error
 * @var bool $showForm
 * @var array<string, mixed> $data
 * @var array<int, array<string, mixed>> $rooms
 * @var array<string, mixed> $nguoiDung
 * @var array<string, mixed> $item
 */

require_once __DIR__ . '/../accounts-service.php';
require_once __DIR__ . '/../../includes/session.php';

if (!isset($list)) {
    http_response_code(404);
    exit;
}
?>
<section class="panel">
    <form method="get" class="row-tools">
        <label>Tìm tài khoản
            <input name="q" maxlength="100" value="<?= e($keyword) ?>" placeholder="Mã nhân viên / họ tên / tên đăng nhập">
        </label>
        <label>Vai trò
            <select name="role">
                <option value="">Tất cả vai trò</option>
                <?php foreach (ACCOUNT_ROLES as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $role === $key ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Trạng thái
            <select name="status">
                <option value="">Tất cả trạng thái</option>
                <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Hoạt động</option>
                <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Đã khóa</option>
            </select>
        </label>
        <button type="submit">Tìm</button>
        <a class="button" href="tai-khoan.php">Xóa lọc</a>
    </form>
</section>

<section class="panel">
    <div class="card-title">
        <h2>Danh sách tài khoản <small><?= $total ?> tài khoản</small></h2>
        <a class="button primary" href="?new=1">Thêm tài khoản</a>
    </div>
    <div class="scroll">
        <table>
            <thead>
                <tr>
                    <th>Mã / Họ tên</th>
                    <th>Tên đăng nhập</th>
                    <th>Vai trò</th>
                    <th>Phòng trực</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $row): ?>
                    <tr class="<?= (int)$row['id'] === $id ? 'selected' : '' ?>">
                        <td>
                            <small><?= e($row['ma_nhan_vien']) ?></small>
                            <?= e($row['ho_ten']) ?>
                        </td>
                        <td><?= e($row['ten_dang_nhap']) ?></td>
                        <td><?= e(ACCOUNT_ROLES[$row['vai_tro']] ?? $row['vai_tro']) ?></td>
                        <td><?= e($row['ten_phong_truc'] ?? '---') ?></td>
                        <td><?= !empty($row['dang_hoat_dong']) ? 'Hoạt động' : 'Đã khóa' ?></td>
                        <td>
                            <a class="button" href="<?= e($link(['id' => $row['id']])) ?>">Xem / sửa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$total): ?>
                    <tr>
                        <td colspan="6">Không tìm thấy tài khoản phù hợp.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
        <div class="actions">
            <?php if ($page > 1): ?>
                <a href="<?= e($link(['page' => $page - 1])) ?>">Trang trước</a>
            <?php endif; ?>
            <span>Trang <?= $page ?> / <?= $pages ?></span>
            <?php if ($page < $pages): ?>
                <a href="<?= e($link(['page' => $page + 1])) ?>">Trang sau</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($error !== ''): ?>
    <p role="alert" class="error"><?= e($error) ?></p>
<?php endif; ?>

<?php if ($showForm): ?>
    <div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountModalLabel"><?= $id ? 'Thông tin tài khoản đang chọn' : 'Thêm tài khoản' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng" onclick="window.location.href='tai-khoan.php'"></button>
                </div>
                <div class="modal-body">
                    <form method="post" id="accountForm">
                        <?= csrf_input() ?>
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <input type="hidden" name="action" value="<?= $id ? 'edit' : 'create' ?>">
                        <div class="fields">
                            <?php foreach (['ma_nhan_vien' => ['Mã nhân viên', 30], 'ho_ten' => ['Họ tên', 100], 'ten_dang_nhap' => ['Tên đăng nhập', 50], 'so_dien_thoai' => ['Số điện thoại', 20]] as $key => [$label, $max]): ?>
                                <label>
                                    <?= $label ?>
                                    <input name="<?= $key ?>" maxlength="<?= $max ?>" value="<?= e(is_string($data[$key] ?? null) ? $data[$key] : '') ?>" <?= $key === 'so_dien_thoai' ? '' : 'required' ?>>
                                </label>
                            <?php endforeach; ?>
                            <label>Vai trò
                                <select name="vai_tro">
                                    <?php foreach (ACCOUNT_ROLES as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= ($data['vai_tro'] ?? 'NHAN_VIEN') === $key ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>Phòng trực
                                <select name="phong_truc_id">
                                    <option value="">Chưa phân công</option>
                                    <?php foreach ($rooms as $room): ?>
                                        <option value="<?= (int)$room['id'] ?>" <?= (int)($data['phong_truc_id'] ?? 0) === (int)$room['id'] ? 'selected' : '' ?>>
                                            <?= e($room['ten_phong_truc']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <?php if (!$id): ?>
                                <label>Mật khẩu ban đầu
                                    <input type="password" name="mat_khau" minlength="8" required autocomplete="new-password">
                                </label>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if ($id): ?>
                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">Bảo mật tài khoản</h6>
                        <form method="post">
                            <?= csrf_input() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="action" value="password">
                            <label>Mật khẩu mới
                                <input name="mat_khau" type="password" required minlength="8" autocomplete="new-password">
                            </label>
                            <div class="actions">
                                <button type="submit">Đặt lại mật khẩu</button>
                            </div>
                        </form>
                        <?php if ($id !== (int)$nguoiDung['id']): ?>
                            <hr class="my-3">
                            <form method="post">
                                <?= csrf_input() ?>
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="status" value="<?= !empty($item['dang_hoat_dong']) ? '0' : '1' ?>">
                                <button type="submit">
                                    <?= !empty($item['dang_hoat_dong']) ? 'Khóa tài khoản' : 'Mở khóa tài khoản' ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <a class="button" href="tai-khoan.php" data-bs-dismiss="modal">Hủy</a>
                    <button type="submit" form="accountForm" class="primary">
                        <?= $id ? 'Lưu thay đổi' : 'Tạo tài khoản' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var modalEl = document.getElementById('accountModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            var myModal = new bootstrap.Modal(modalEl);
            myModal.show();
            modalEl.addEventListener('hidden.bs.modal', function () {
                if (window.location.search.includes('new=1') || window.location.search.includes('id=')) {
                    window.location.href = 'tai-khoan.php';
                }
            });
        }
    });
    </script>
<?php endif; ?>