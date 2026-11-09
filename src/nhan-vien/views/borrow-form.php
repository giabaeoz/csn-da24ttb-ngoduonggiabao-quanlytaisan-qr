<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $rooms
 * @var array<string, mixed>|null $oldBorrowerRow
 * @var array<int, array<string, mixed>> $oldAssetRows
 * @var string $bq
 * @var string $oldBorrower
 * @var string $oldRoom
 * @var string $oldDue
 * @var string $oldNote
 * @var string $formNonce
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($rooms, $formNonce)) {
    http_response_code(404);
    exit;
}

$bq = isset($bq) ? (string)$bq : '';
$oldBorrowerRow = $oldBorrowerRow ?? null;
$oldAssetRows = $oldAssetRows ?? [];
$scanTitle = 'Quét QR thiết bị';
$scanHint = 'Quét QR phòng học, sau đó quét QR từng thiết bị';
$scanPlaceholder = 'Mã phòng / mã tài sản / QR';
$scanButton = 'Thêm';
$minDue = date('Y-m-d\TH:i', time() + 300);
?>
<div class="borrow-layout" data-page="muon">
    <div class="borrow-col-left">
        <?php require __DIR__ . '/qr-scan-card.php'; ?>

        <div class="sketch-card receipt-card">
            <h2>Phiếu mượn</h2>
            <div class="receipt-meta">
                <div id="rec-borrower"><strong>Chưa chọn người mượn</strong></div>
                <div id="rec-phone-room">SĐT: — · Phòng cần mượn: —</div>
                <div id="rec-time">Thời gian mượn: <?= date('d/m/Y H:i') ?> · Hạn trả: —</div>
            </div>
            <div class="receipt-items-header">
                <span>Thiết bị</span>
                <span>Số lượng</span>
            </div>
            <div id="rec-items-list">
                <div class="receipt-item-row text-muted">Chưa quét thiết bị nào</div>
            </div>
            <div class="receipt-total" id="rec-total-row">Tổng cộng: 0 thiết bị</div>
        </div>
    </div>

    <div class="borrow-col-right">
        <form method="post" id="borrow-form" novalidate>
            <?= csrf_input() ?>
            <input type="hidden" name="form_nonce" value="<?= e($formNonce) ?>">
            <input type="hidden" name="nguoi_muon_id" id="borrower-id" value="<?= e($oldBorrowerRow ? (string)$oldBorrowerRow['id'] : '') ?>"
                <?php if ($oldBorrowerRow): ?>
                    data-code="<?= e($oldBorrowerRow['ma_so_truong']) ?>" data-name="<?= e($oldBorrowerRow['ho_ten']) ?>"
                    data-phone="<?= e($oldBorrowerRow['so_dien_thoai'] ?? '') ?>" data-unit="<?= e($oldBorrowerRow['lop_don_vi'] ?? '') ?>"
                <?php endif; ?>>

            <div class="sketch-card">
                <h2>Thông tin mượn</h2>
                <div class="borrower-search">
                    <div class="search-borrower-row">
                        <input id="borrower-search-input" class="form-control" placeholder="Tìm người mượn cũ: mã số / họ tên / SĐT" value="<?= e($bq) ?>" autocomplete="off" aria-label="Tìm người mượn">
                        <button class="btn-sketch btn-sketch-gray" type="button" id="btn-search-borrower">Tìm</button>
                        <button class="btn-sketch" type="button" id="btn-new-borrower" aria-expanded="false" aria-controls="quick-borrower">Người mượn mới</button>
                    </div>
                    <div class="borrower-results" id="borrower-results" role="listbox" hidden></div>
                </div>
                <div id="borrower-search-status" class="form-status" role="status"></div>

                <div class="quick-borrower" id="quick-borrower" hidden>
                    <div class="form-grid-3">
                        <label>Loại người mượn
                            <select class="form-select" id="qb-type">
                                <option value="SINH_VIEN">Sinh viên</option>
                                <option value="GIANG_VIEN">Giảng viên</option>
                            </select>
                        </label>
                        <label>Mã số *
                            <input class="form-control" id="qb-code" maxlength="30" placeholder="Mã số">
                        </label>
                        <label>Họ tên *
                            <input class="form-control" id="qb-name" maxlength="100" placeholder="Họ tên">
                        </label>
                    </div>
                    <div class="form-grid-3">
                        <label>Số điện thoại *
                            <input class="form-control" id="qb-phone" maxlength="20" inputmode="tel" placeholder="Số điện thoại">
                        </label>
                        <label>Lớp / đơn vị
                            <input class="form-control" id="qb-unit" maxlength="100" placeholder="Lớp / đơn vị">
                        </label>
                        <div class="quick-borrower-actions">
                            <button class="btn-sketch" type="button" id="qb-cancel">Hủy</button>
                            <button class="btn-sketch-dark" type="button" id="qb-save">Lưu người mượn</button>
                        </div>
                    </div>
                </div>

                <div class="form-grid-3">
                    <label>Mã số *
                        <input id="disp-code" name="ma_so_truong" class="form-control" placeholder="Nhập mã số SV / GV" value="<?= e($oldBorrowerRow['ma_so_truong'] ?? '') ?>" maxlength="30" autocomplete="off" required>
                    </label>
                    <label>Họ tên *
                        <input id="disp-name" name="ho_ten" class="form-control" placeholder="Nhập họ và tên" value="<?= e($oldBorrowerRow['ho_ten'] ?? '') ?>" maxlength="100" required>
                    </label>
                    <label>Số điện thoại *
                        <input id="disp-phone" name="so_dien_thoai" class="form-control" placeholder="Nhập số điện thoại" value="<?= e($oldBorrowerRow['so_dien_thoai'] ?? '') ?>" maxlength="20" inputmode="tel" required>
                    </label>
                </div>

                <div class="form-grid-3">
                    <label>Lớp / đơn vị
                        <input id="disp-unit" name="lop_don_vi" class="form-control" placeholder="Nhập lớp hoặc khoa/đơn vị" value="<?= e($oldBorrowerRow['lop_don_vi'] ?? '') ?>" maxlength="100">
                    </label>
                    <label>Phòng cần mượn *
                        <select class="form-select" id="room" name="phong_hoc_id" required>
                            <option value="">Chọn phòng học</option>
                            <?php foreach ($rooms as $room): ?>
                                <option value="<?= (int)$room['id'] ?>" data-code="<?= e($room['ma_phong']) ?>" <?= $oldRoom === (string)$room['id'] ? 'selected' : '' ?>>
                                    <?= e($room['ma_phong'] . ' — ' . $room['ten_phong']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Hạn trả *
                        <input type="datetime-local" class="form-control" id="due" name="han_tra" value="<?= e($oldDue) ?>" min="<?= e($minDue) ?>" required>
                    </label>
                </div>

                <div class="borrower-overview" id="borrower-overview" hidden></div>

                <label class="field-label" for="borrow-note">Ghi chú phiếu (nếu có)</label>
                <textarea class="form-control" id="borrow-note" name="ghi_chu" maxlength="2000" rows="1" placeholder="Nhập ghi chú phiếu"><?= e($oldNote) ?></textarea>
            </div>

            <div class="sketch-card">
                <h2>Thiết bị cần mượn — đã quét (<span id="scanned-count">0</span>)</h2>
                <div class="table-scroll">
                    <table class="scanned-table">
                        <thead>
                            <tr>
                                <th>Mã thiết bị</th>
                                <th>Loại / tên thiết bị</th>
                                <th>Phòng sử dụng</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="scanned-table-body">
                            <tr><td colspan="4" class="table-empty">Chưa có thiết bị nào được quét.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div id="asset-inputs">
                    <?php foreach ($oldAssetRows as $a): ?>
                        <input type="hidden" name="tai_san_id[]" value="<?= (int)$a['id'] ?>" data-code="<?= e($a['ma_tai_san']) ?>" data-name="<?= e($a['ten_loai'] . ' — ' . $a['ten_tai_san']) ?>">
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="borrow-actions-bottom">
                <a class="btn-sketch-white" href="muon-tra.php?tab=muon">Hủy</a>
                <button class="btn-sketch-confirm" type="submit" id="borrow-submit">Xác nhận mượn &amp; lưu phiếu</button>
            </div>
        </form>
    </div>
</div>
