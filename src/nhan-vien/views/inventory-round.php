<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $round
 * @var array<int, array<string, mixed>> $details
 * @var array<string, int> $counts
 * @var array<int, array<string, mixed>> $visibleDetails
 * @var array<int, array<string, mixed>> $rooms
 * @var array<string, mixed>|null $selectedDetail
 * @var string $keyword
 * @var string $filter
 * @var int $incidentCount
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/inventory-service.php';

if (!isset($round, $details, $counts, $visibleDetails, $rooms)) {
    http_response_code(404);
    exit;
}

$selectedDetail = isset($selectedDetail) ? $selectedDetail : null;
$keyword = isset($keyword) ? (string)$keyword : '';
$filter = isset($filter) ? (string)$filter : 'all';
$incidentCount = isset($incidentCount) ? (int)$incidentCount : 0;
$isOpen = $round['trang_thai'] === 'DANG_KIEM_KE';
$isCancelled = $round['trang_thai'] === 'DA_HUY';
$statusLabel = $isOpen ? 'Đang thực hiện' : ($isCancelled ? 'Đã hủy' : 'Đã hoàn tất');
?>

<div class="inventory-top-row">
    <div class="sketch-card" data-qr-scanner>
        <div style="font-size: 16px; font-weight: 700; color: #111; margin-bottom: 16px;">Quét QR để đối chiếu thiết bị</div>
        <?php if ($isOpen): ?>
            <div style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 16px;">
                <div class="camera-bracket-box" data-qr-preview>
                    <div class="corner-bracket tl"></div>
                    <div class="corner-bracket tr"></div>
                    <div class="corner-bracket bl"></div>
                    <div class="corner-bracket br"></div>
                    <div class="camera-inner-text">
                        <div style="font-size: 13px; font-weight: 500; color: #555;">Khung camera</div>
                        <div style="font-size: 11px; color: #777; margin-top: 4px;">Đưa mã QR thiết bị<br>vào khung</div>
                    </div>
                </div>

                <div>
                    <select class="form-select form-select-sm mb-3" style="background:#e8e8e8; border:1px solid #ccc; font-size:12px; height: 34px;">
                        <option>Chọn camera</option>
                    </select>

                    <form method="get" id="inventory-scan-form" class="mb-3">
                        <input type="hidden" name="id" value="<?= (int)$round['id'] ?>">
                        <label for="inventory-scan" style="display:block; font-size: 12px; color: #333; margin-bottom: 6px;">Nhập mã khi không quét được</label>
                        <div style="display: flex; gap: 6px;">
                            <input id="inventory-scan" name="scan" maxlength="100" class="form-control form-control-sm" style="font-size: 12px;" placeholder="">
                            <button class="btn-sketch" type="submit" style="padding: 6px 14px; font-size: 12px;">Tìm</button>
                        </div>
                    </form>

                    <button type="button" class="btn-sketch-gray w-100" style="padding: 8px 12px; font-size: 12px;" data-qr-start>Bật camera</button>
                    <button type="button" class="btn-sketch-gray w-100" style="padding: 8px 12px; font-size: 12px;" data-qr-stop hidden>Tắt camera</button>
                    <input id="inventory-image" type="file" accept="image/png,image/jpeg,image/webp" style="display: none;" data-qr-file>
                    <label for="inventory-image" class="btn-sketch w-100 mt-2" style="padding:8px 12px; font-size:12px; cursor:pointer;">Chọn ảnh QR</label>
                </div>
            </div>
            <small class="muted d-block mt-2" data-qr-status role="status">Camera cần HTTPS hoặc localhost; có thể chọn ảnh QR từ thiết bị.</small>
        <?php else: ?>
            <p class="muted mb-0" style="font-size: 13px;"><?= $isCancelled ? 'Đợt kiểm kê đã bị hủy. Dữ liệu được giữ lại để đối chiếu và không thể sửa.' : 'Đợt đã hoàn tất. Kết quả chỉ có thể xem, không sửa.' ?></p>
        <?php endif; ?>
    </div>

    <div class="sketch-card">
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 16px;">
            <div style="font-size: 16px; font-weight: 700; color: #111;">Thông tin đợt kiểm kê</div>
            <div style="font-size: 13px; color: #777; font-weight: 500;"><?= $statusLabel ?></div>
        </div>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 12px;">
            <div>
                <label style="display:block; font-size: 12px; color: #444; margin-bottom: 4px;">Mã đợt</label>
                <input type="text" class="form-control form-control-sm" style="background: #fff; font-size: 13px;" readonly value="<?= e($round['ma_dot_kiem_ke']) ?>">
            </div>
            <div>
                <label style="display:block; font-size: 12px; color: #444; margin-bottom: 4px;">Loại kiểm kê</label>
                <input type="text" class="form-control form-control-sm" style="background: #fff; font-size: 13px;" readonly value="<?= e(INVENTORY_TYPES[$round['loai_kiem_ke']] ?? $round['loai_kiem_ke']) ?>">
            </div>
        </div>
        <div style="margin-bottom: 12px;">
            <label style="display:block; font-size: 12px; color: #444; margin-bottom: 4px;">Phạm vi tài sản</label>
            <input type="text" class="form-control form-control-sm" style="background: #fff; font-size: 13px;" readonly value="<?= e($round['ten_dot_kiem_ke']) ?> (Tài sản thuộc <?= e($round['ten_phong_truc']) ?>)">
        </div>
        <div>
            <label style="display:block; font-size: 12px; color: #444; margin-bottom: 4px;">Nơi kiểm tra thực tế</label>
            <input type="text" class="form-control form-control-sm" style="background: #fff; font-size: 13px;" readonly value="Phòng trực thiết bị">
        </div>
        <?php if ($isCancelled): ?>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:12px;">
                <div>
                    <label style="display:block; font-size:12px; color:#444; margin-bottom:4px;">Người hủy</label>
                    <input type="text" class="form-control form-control-sm" readonly value="<?= e($round['nguoi_huy'] ?? '—') ?>">
                </div>
                <div>
                    <label style="display:block; font-size:12px; color:#444; margin-bottom:4px;">Thời gian hủy</label>
                    <input type="text" class="form-control form-control-sm" readonly value="<?= e($round['thoi_gian_ket_thuc'] ?? '—') ?>">
                </div>
            </div>
            <div style="margin-top:12px;">
                <label style="display:block; font-size:12px; color:#444; margin-bottom:4px;">Lý do hủy</label>
                <textarea class="form-control form-control-sm" rows="2" readonly><?= e($round['ly_do_huy'] ?? '—') ?></textarea>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="inventory-stats-grid">
    <div class="inventory-stat-box">
        <div class="lbl">Phải kiểm thực tế</div>
        <div class="num"><?= (int)$counts['required'] ?></div>
    </div>
    <div class="inventory-stat-box">
        <div class="lbl">Đã kiểm</div>
        <div class="num"><?= (int)$counts['checked'] ?></div>
    </div>
    <div class="inventory-stat-box">
        <div class="lbl">Chưa kiểm</div>
        <div class="num"><?= (int)$counts['remaining'] ?></div>
    </div>
    <div class="inventory-stat-box">
        <div class="lbl">Đang mượn theo phiếu</div>
        <div class="num"><?= (int)$counts['borrowed'] ?></div>
    </div>
</div>

<?php if (isset($_GET['scan']) && is_string($_GET['scan']) && trim($_GET['scan']) !== '' && !$selectedDetail): ?>
    <div class="alert alert-warning mb-4">Mã vừa quét không thuộc danh sách tài sản của đợt kiểm kê này.</div>
<?php endif; ?>

<?php if ($selectedDetail && $isOpen): ?>
    <?php if ($selectedDetail['trang_thai_so_sach'] === 'DANG_MUON'): ?>
        <div class="alert alert-info mb-4">Tài sản <?= e($selectedDetail['ma_tai_san']) ?> đang mượn lúc mở đợt; không phải kiểm thực tế trong đợt này.</div>
    <?php else: ?>
        <div class="sketch-card mb-4" id="inventory-record" style="border: 2px solid #555;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="font-size: 16px; font-weight: 700; margin: 0; color: #111;">Ghi nhận <?= e($selectedDetail['ma_tai_san'] . ' — ' . $selectedDetail['ten_tai_san']) ?></h3>
                <a class="btn-sketch" href="kiem-ke.php?id=<?= (int)$round['id'] ?>" style="padding: 4px 12px; font-size: 12px; text-decoration: none;">Đóng</a>
            </div>
            <p style="font-size: 13px; color: #666; margin-bottom: 16px;">Sổ sách: <?= e($selectedDetail['tinh_trang_so_sach']) ?> · <?= e($selectedDetail['phong_so_sach'] ?? 'Tại phòng trực') ?></p>
            <form method="post" class="row g-3" id="inventory-record-form">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="record">
                <input type="hidden" name="dot_kiem_ke_id" value="<?= (int)$round['id'] ?>">
                <input type="hidden" name="chi_tiet_kiem_ke_id" value="<?= (int)$selectedDetail['id'] ?>">

                <div class="col-md-4">
                    <label class="form-label" for="inventory-result" style="font-size: 12px;">Kết quả đối chiếu</label>
                    <select class="form-select form-select-sm" name="ket_qua" id="inventory-result">
                        <option value="TIM_THAY" <?= $selectedDetail['ket_qua'] !== 'CHUA_TIM_THAY' ? 'selected' : '' ?>>Có mặt</option>
                        <option value="CHUA_TIM_THAY" <?= $selectedDetail['ket_qua'] === 'CHUA_TIM_THAY' ? 'selected' : '' ?>>Chưa tìm thấy</option>
                    </select>
                </div>

                <div class="col-md-4 inventory-found-field">
                    <label class="form-label" for="inventory-condition" style="font-size: 12px;">Tình trạng thực tế</label>
                    <select class="form-select form-select-sm" name="tinh_trang_thuc_te" id="inventory-condition">
                        <option value="TOT" <?= $selectedDetail['tinh_trang_thuc_te'] !== 'HU_HONG' ? 'selected' : '' ?>>Bình thường</option>
                        <option value="HU_HONG" <?= $selectedDetail['tinh_trang_thuc_te'] === 'HU_HONG' ? 'selected' : '' ?>>Hư hỏng</option>
                    </select>
                </div>

                <div class="col-md-4 inventory-found-field">
                    <label class="form-label" for="inventory-room" style="font-size: 12px;">Vị trí thực tế</label>
                    <select class="form-select form-select-sm" name="phong_hoc_thuc_te_id" id="inventory-room">
                        <option value="">Tại phòng trực</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= (int)$room['id'] ?>" <?= (int)$selectedDetail['phong_hoc_thuc_te_id'] === (int)$room['id'] ? 'selected' : '' ?>>
                                <?= e($room['ma_phong'] . ' — ' . $room['ten_phong']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label" for="inventory-note" style="font-size: 12px;">Ghi chú <span class="muted">(bắt buộc khi hư hỏng hoặc chưa tìm thấy)</span></label>
                    <textarea class="form-control form-control-sm" name="ghi_chu" id="inventory-note" rows="2" maxlength="2000" placeholder="Mô tả hư hỏng hoặc nơi đã tìm kiếm"><?= e($selectedDetail['ghi_chu'] ?? '') ?></textarea>
                </div>

                <div class="col-12 d-flex justify-content-end">
                    <button class="btn-sketch-dark" type="submit" style="padding: 8px 24px; font-size: 13px;">Lưu kết quả đối chiếu</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="inventory-filter-card">
    <form method="get" class="inventory-filter-row">
        <input type="hidden" name="id" value="<?= (int)$round['id'] ?>">
        <label>Tìm thiết bị
            <input type="text" name="q" maxlength="100" value="<?= e($keyword) ?>" placeholder="Mã / tên thiết bị">
        </label>
        <label>Kết quả đối chiếu
            <select name="result">
                <?php foreach (['all' => 'Tất cả', 'found' => 'Có mặt', 'missing' => 'Chưa tìm thấy', 'unchecked' => 'Chưa kiểm', 'borrowed' => 'Đang mượn'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filter === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button class="btn-sketch" type="submit" style="padding: 8px 24px; font-size: 13px;">Tìm</button>
    </form>
</div>

<div class="inventory-table-card">
    <div class="head">
        <h2>Danh sách đối chiếu</h2>
        <span class="meta"><?= count($visibleDetails) ?> tài sản trong phạm vi</span>
    </div>
    <div class="sketch-table-wrap">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th style="width: 32%;">Mã / tên thiết bị</th>
                    <th style="width: 18%;">Đối chiếu</th>
                    <th style="width: 22%;">Tình trạng thực tế</th>
                    <th style="width: 16%;">Ghi chú</th>
                    <th style="width: 12%; text-align: right;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visibleDetails as $item): ?>
                    <tr>
                        <td>
                            <strong><?= e($item['ma_tai_san']) ?></strong> — <?= e($item['ten_tai_san']) ?>
                            <small class="muted d-block" style="font-size: 11px; margin-top: 2px;">Phòng quản lý: <?= e($item['phong_so_sach'] ?? $round['ten_phong_truc']) ?></small>
                        </td>
                        <td>
                            <?php if ($item['trang_thai_so_sach'] === 'DANG_MUON'): ?>
                                <span>Đang mượn</span>
                            <?php elseif ($item['ket_qua'] === 'TIM_THAY'): ?>
                                <span>Có mặt</span>
                            <?php elseif ($item['ket_qua'] === 'CHUA_TIM_THAY'): ?>
                                <span>Chưa tìm thấy</span>
                            <?php else: ?>
                                <span style="color: #666;">Chưa kiểm</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['trang_thai_so_sach'] === 'DANG_MUON'): ?>
                                <span style="color: #555;">Theo phiếu mượn</span>
                            <?php elseif ($item['ket_qua'] === 'CHUA_KIEM_TRA'): ?>
                                <span style="color: #777;">Chưa ghi nhận</span>
                            <?php elseif ($isOpen): ?>
                                <select class="form-select form-select-sm" style="font-size: 12px; width: 140px; display: inline-block;" onchange="location.href='kiem-ke.php?id=<?= (int)$round['id'] ?>&detail=<?= (int)$item['id'] ?>#inventory-record'">
                                    <option value="TOT" <?= $item['tinh_trang_thuc_te'] !== 'HU_HONG' ? 'selected' : '' ?>>Bình thường</option>
                                    <option value="HU_HONG" <?= $item['tinh_trang_thuc_te'] === 'HU_HONG' ? 'selected' : '' ?>>Hỏng</option>
                                </select>
                            <?php else: ?>
                                <span><?= $item['tinh_trang_thuc_te'] === 'HU_HONG' ? 'Hỏng' : 'Bình thường' ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['trang_thai_so_sach'] === 'DANG_MUON' && $item['ma_phieu_muon']): ?>
                                <span><?= e($item['ma_phieu_muon']) ?></span>
                            <?php elseif ($item['ghi_chu']): ?>
                                <span><?= e($item['ghi_chu']) ?></span>
                            <?php else: ?>
                                <span style="color: #888;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right;">
                            <?php if ($item['trang_thai_so_sach'] === 'DANG_MUON'): ?>
                                <?php if ($item['phieu_muon_id']): ?>
                                    <a class="btn-sketch" href="muon-tra.php?tab=tra&amp;id=<?= (int)$item['phieu_muon_id'] ?>" style="padding: 4px 12px; font-size: 12px; text-decoration: none;">Xem phiếu</a>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #777;">Mượn ngoài</span>
                                <?php endif; ?>
                            <?php elseif ($isOpen): ?>
                                <?php if ($item['ket_qua'] === 'CHUA_KIEM_TRA'): ?>
                                    <a class="btn-sketch" href="kiem-ke.php?id=<?= (int)$round['id'] ?>&amp;detail=<?= (int)$item['id'] ?>#inventory-record" style="padding: 4px 12px; font-size: 12px; text-decoration: none;">Ghi nhận</a>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #555;">Đã lưu</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #555;">Đã lưu</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$visibleDetails): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #888; padding: 24px;">Không có tài sản phù hợp bộ lọc.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="temp-result-card">
    <h2>Kết quả <?= $isOpen ? 'tạm thời' : ($isCancelled ? 'trước khi hủy' : 'đã hoàn tất') ?></h2>
    <div class="counts-line">
        Bình thường: <?= (int)$counts['normal'] ?> • Hỏng: <?= (int)$counts['damaged'] ?> • Không tìm thấy: <?= (int)$counts['missing'] ?> • Đang mượn: <?= (int)$counts['borrowed'] ?> • Chưa kiểm: <?= (int)$counts['remaining'] ?>
    </div>
    <div class="hint-line">
        <?php if ($isOpen): ?>
            Đối chiếu <?= (int)$counts['remaining'] ?> thiết bị còn lại trước khi hoàn tất đợt kiểm kê.
        <?php elseif ($isCancelled): ?>
            Đợt đã hủy nên kết quả tạm thời không làm thay đổi hồ sơ tài sản và không tạo sự cố.
        <?php else: ?>
            <?= (int)$incidentCount ?> sự cố đã được lập từ kết quả kiểm kê. Tài sản hư hỏng hoặc chưa tìm thấy được tạm chặn cho mượn để chờ xử lý.
        <?php endif; ?>
    </div>

    <?php if ($isOpen): ?>
        <div class="temp-result-actions">
            <a href="index.php" class="btn-sketch" style="padding: 8px 24px; font-size: 13px; text-decoration: none;">Lưu nháp</a>
            <button type="button" class="btn btn-outline-danger" style="padding:8px 24px; font-size:13px;" data-bs-toggle="modal" data-bs-target="#cancelInventoryModal">Hủy đợt kiểm kê</button>
            <form method="post" onsubmit="return confirm('Hoàn tất đợt kiểm kê? Sau khi chốt sẽ không thể sửa kết quả.');" style="margin: 0;">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="finish">
                <input type="hidden" name="dot_kiem_ke_id" value="<?= (int)$round['id'] ?>">
                <button type="submit" class="btn-sketch-gray" style="padding: 8px 24px; font-size: 13px;" <?= $counts['remaining'] ? 'disabled' : '' ?>>Hoàn tất kiểm kê</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php if ($isOpen): ?>
    <div class="modal fade" id="cancelInventoryModal" tabindex="-1" aria-labelledby="cancelInventoryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelInventoryModalLabel">Hủy đợt kiểm kê</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <?= csrf_input() ?>
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="dot_kiem_ke_id" value="<?= (int)$round['id'] ?>">
                        <p style="font-size:13px; color:#555;">Kết quả tạm thời sẽ được giữ để truy vết nhưng không cập nhật hồ sơ tài sản và không tạo sự cố.</p>
                        <label class="form-label" for="inventory-cancel-reason">Lý do hủy <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="inventory-cancel-reason" name="ly_do_huy" rows="3" maxlength="500" required placeholder="Nhập lý do hủy đợt kiểm kê"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Quay lại</button>
                        <button type="submit" class="btn btn-danger">Xác nhận hủy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>
