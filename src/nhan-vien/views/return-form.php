<?php
declare(strict_types=1);

/**
 * @var array<int, array<string, mixed>> $loans
 * @var array<int, array<string, mixed>> $details
 * @var array<int, array<string, mixed>> $returnBatches
 * @var array<string, mixed>|null $selectedLoan
 * @var string $q
 * @var string $scanCode
 * @var string $justReturned
 * @var string $formNonce
 */

require_once __DIR__ . '/../../includes/session.php';

if (!isset($loans, $details, $formNonce)) {
    http_response_code(404);
    exit;
}

$q = isset($q) ? (string)$q : '';
$selectedLoan = $selectedLoan ?? null;
$returnBatches = $returnBatches ?? [];
$scanCode = $scanCode ?? '';
$justReturned = $justReturned ?? '';
$fmt = static fn(?string $v, string $f = 'd/m/Y H:i'): string => $v ? date($f, strtotime($v)) : '—';
$late = static function (string $due, string $at): string {
    $s = strtotime($at) - strtotime($due);
    if ($s <= 0) return '';
    $h = intdiv($s, 3600);
    return $h >= 24 ? 'Quá hạn ' . intdiv($h, 24) . ' ngày ' . ($h % 24) . ' giờ' : 'Quá hạn ' . max(1, $h) . ' giờ ' . intdiv($s % 3600, 60) . ' phút';
};
$openDetails = array_values(array_filter($details, static fn($d) => $d['thoi_gian_tra'] === null));
$isOpenLoan = $selectedLoan && $selectedLoan['trang_thai'] === 'DANG_MUON';
$nowText = date('Y-m-d H:i:s');

$scanTitle = 'Quét QR thiết bị trả';
$scanHint = 'Đưa mã QR thiết bị vào khung';
$scanPlaceholder = 'Mã QR / mã thiết bị / mã phiếu';
$scanButton = 'Tìm';
$scanValue = '';
?>
<div class="borrow-layout" data-page="tra" data-loan-id="<?= $selectedLoan ? (int)$selectedLoan['id'] : '' ?>" data-scan="<?= e($scanCode) ?>">
    <div class="borrow-col-left">
        <?php require __DIR__ . '/qr-scan-card.php'; ?>

        <div class="sketch-card receipt-card">
            <h2>Phiếu trả tài sản</h2>
            <?php if ($selectedLoan): ?>
                <div class="receipt-meta">
                    <div><strong><?= e($selectedLoan['ho_ten']) ?> - <?= e($selectedLoan['ma_so_truong']) ?><?= $selectedLoan['lop_don_vi'] ? ' - ' . e($selectedLoan['lop_don_vi']) : '' ?></strong></div>
                    <div>Phiếu mượn: <?= e($selectedLoan['ma_phieu_muon']) ?> - Phòng: <?= e($selectedLoan['ma_phong']) ?></div>
                    <?php if ($isOpenLoan): ?>
                        <?php $lateNow = $late((string)$selectedLoan['han_tra'], $nowText); ?>
                        <div>Trả: <?= date('d/m/Y H:i') ?> - <span class="<?= $lateNow ? 'text-overdue' : '' ?>"><?= e($lateNow ?: 'Trong hạn') ?></span></div>
                    <?php else: ?>
                        <div>Trạng thái: Đã trả đủ</div>
                    <?php endif; ?>
                </div>
                <?php if ($isOpenLoan): ?>
                    <div class="receipt-items-header">
                        <span>Thiết bị nhận trả</span>
                        <span>Tình trạng</span>
                    </div>
                    <div id="ret-items-list">
                        <div class="receipt-item-row text-muted">Chưa chọn thiết bị nhận trả</div>
                    </div>
                    <div class="receipt-total" id="ret-total-row">Tổng nhận: 0 / <?= count($openDetails) ?> thiết bị</div>
                <?php endif; ?>
                <?php if ($returnBatches): ?>
                    <div class="receipt-batches">
                        <div class="receipt-items-header"><span>Chứng từ trả đã lập</span><span></span></div>
                        <?php foreach ($returnBatches as $b): ?>
                            <div class="receipt-item-row <?= $b['thoi_gian_tra'] === $justReturned ? 'is-new' : '' ?>">
                                <span><?= $fmt($b['thoi_gian_tra']) ?> · <?= (int)$b['so_tb'] ?> thiết bị<?= (int)$b['so_hong'] ? ' · ' . (int)$b['so_hong'] . ' hư hỏng' : '' ?></span>
                                <a href="phieu-in.php?<?= e(http_build_query(['id' => (int)$selectedLoan['id'], 'tra' => $b['thoi_gian_tra']])) ?>" target="_blank" rel="noopener">In chứng từ</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-muted m-0">Quét mã thiết bị hoặc chọn phiếu bên phải để mở chi tiết nhận trả.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="borrow-col-right">
        <?php if ($selectedLoan): ?>
            <form method="post" action="muon-tra.php?tab=tra&id=<?= (int)$selectedLoan['id'] ?>" id="return-form" novalidate>
                <?= csrf_input() ?>
                <input type="hidden" name="form_nonce" value="<?= e($formNonce) ?>">
                <input type="hidden" name="phieu_muon_id" value="<?= (int)$selectedLoan['id'] ?>">

                <div class="sketch-card">
                    <div class="card-head">
                        <h2>Thông tin phiếu mượn</h2>
                        <div class="card-head-actions">
                            <span class="loan-code"><?= e($selectedLoan['ma_phieu_muon']) ?></span>
                            <a class="btn-sketch btn-sm-sketch" href="phieu-in.php?id=<?= (int)$selectedLoan['id'] ?>" target="_blank" rel="noopener">In phiếu mượn</a>
                        </div>
                    </div>

                    <div class="form-grid-3">
                        <label>Mã số
                            <input class="form-control" value="<?= e($selectedLoan['ma_so_truong']) ?>" readonly>
                        </label>
                        <label>Họ tên
                            <input class="form-control" value="<?= e($selectedLoan['ho_ten']) ?>" readonly>
                        </label>
                        <label>Số điện thoại
                            <input class="form-control" value="<?= e($selectedLoan['so_dien_thoai'] ?? '') ?>" readonly>
                        </label>
                    </div>

                    <div class="form-grid-3">
                        <label>Lớp / đơn vị
                            <input class="form-control" value="<?= e($selectedLoan['lop_don_vi'] ?? '') ?>" readonly>
                        </label>
                        <label>Phòng đã mượn
                            <input class="form-control" value="<?= e($selectedLoan['ma_phong']) ?>" readonly>
                        </label>
                        <label>Hạn trả
                            <input class="form-control <?= $isOpenLoan && (int)$selectedLoan['qua_han'] ? 'is-overdue' : '' ?>" value="<?= $fmt((string)$selectedLoan['han_tra'], 'd/m/Y - H:i') ?>" readonly>
                        </label>
                    </div>
                </div>

                <div class="sketch-card">
                    <h2>Thiết bị nhận trả — đã quét (<span id="return-count">0</span>/<?= count($openDetails) ?>)</h2>
                    <div class="table-scroll">
                        <table class="scanned-table return-table">
                            <thead>
                                <tr>
                                    <th class="col-check">Nhận</th>
                                    <th>Mã / tên thiết bị</th>
                                    <th>Tình trạng khi nhận</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details as $detail): ?>
                                    <?php $done = $detail['thoi_gian_tra'] !== null; $did = (int)$detail['id']; ?>
                                    <tr class="<?= $done ? 'is-returned' : '' ?>" data-detail data-code="<?= e(mb_strtolower($detail['ma_tai_san'])) ?>" data-qr="<?= e(mb_strtolower($detail['ma_qr'])) ?>">
                                        <td>
                                            <?php if ($done): ?>
                                                <input type="checkbox" class="form-check-input" checked disabled aria-label="Đã trả">
                                            <?php else: ?>
                                                <input type="checkbox" class="form-check-input" name="chi_tiet_id[]" value="<?= $did ?>" data-label="<?= e($detail['ma_tai_san'] . ' — ' . $detail['ten_tai_san']) ?>" aria-label="Nhận <?= e($detail['ma_tai_san']) ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= e($detail['ma_tai_san']) ?></strong> — <?= e($detail['ten_tai_san']) ?>
                                            <small class="text-muted d-block"><?= $done ? 'Đã trả ' . $fmt($detail['thoi_gian_tra']) : e($selectedLoan['ma_phong']) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($done): ?>
                                                <span class="status-pill <?= $detail['tinh_trang_khi_tra'] === 'HU_HONG' ? 'is-bad' : 'is-ok' ?>"><?= $detail['tinh_trang_khi_tra'] === 'HU_HONG' ? 'Hư hỏng' : 'Bình thường' ?></span>
                                            <?php else: ?>
                                                <select class="form-select form-select-sm" name="tinh_trang[<?= $did ?>]">
                                                    <option value="TOT">Bình thường</option>
                                                    <option value="HU_HONG">Hư hỏng</option>
                                                </select>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($done): ?>
                                                <span class="text-muted"><?= e($detail['ghi_chu'] ?? '') ?></span>
                                            <?php else: ?>
                                                <input class="form-control form-control-sm" name="ghi_chu[<?= $did ?>]" maxlength="2000" placeholder="Hư hỏng / thiếu phụ kiện">
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($isOpenLoan): ?>
                    <div class="borrow-actions-bottom">
                        <a class="btn-sketch-white" href="muon-tra.php?tab=tra">Hủy</a>
                        <button class="btn-sketch-confirm" type="submit" id="return-submit">Xác nhận trả &amp; lưu phiếu</button>
                    </div>
                <?php else: ?>
                    <div class="borrow-actions-bottom">
                        <a class="btn-sketch-white" href="muon-tra.php?tab=tra">Đóng</a>
                    </div>
                <?php endif; ?>
            </form>
        <?php else: ?>
            <div class="sketch-card">
                <h2>Thông tin phiếu mượn</h2>
                <form method="get" class="search-borrower-row">
                    <input type="hidden" name="tab" value="tra">
                    <input class="form-control" name="q" placeholder="Tìm người mượn: mã số / họ tên / SĐT / mã phiếu" value="<?= e($q) ?>">
                    <button class="btn-sketch btn-sketch-gray" type="submit">Tìm</button>
                </form>
            </div>

            <div class="sketch-card">
                <h2>Thiết bị nhận trả — đã quét (<?= count($loans) ?> phiếu đang mượn)</h2>
                <?php if ($loans): ?>
                    <div class="loan-list">
                        <?php foreach ($loans as $loan): ?>
                            <a href="muon-tra.php?tab=tra&id=<?= (int)$loan['id'] ?>" class="loan-list-item">
                                <div>
                                    <strong><?= e($loan['ma_phieu_muon']) ?></strong> — <?= e($loan['ho_ten']) ?> (<?= e($loan['ma_so_truong']) ?>)
                                    <small class="d-block text-muted">Phòng <?= e($loan['ma_phong']) ?> · <?= (int)$loan['so_chua_tra'] ?> thiết bị chưa trả · <span class="<?= (int)$loan['qua_han'] ? 'text-overdue' : '' ?>">Hạn <?= $fmt((string)$loan['han_tra'], 'H:i d/m') ?></span></small>
                                </div>
                                <span class="btn-sketch">Chọn phiếu</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="table-empty m-0">Không có phiếu đang mượn.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
