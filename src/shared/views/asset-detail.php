<?php
declare(strict_types=1);

/**
 * @var array<string, mixed> $asset
 * @var array<string, mixed> $history
 * @var callable $link
 * @var bool|null $canViewBorrower
 * @var bool|null $canEditAsset
 * @var bool|null $showForm
 * @var string|null $borrowerProfileUrl
 */

require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/assets.php';

if (!isset($asset, $history)) {
    http_response_code(404);
    exit;
}
$canViewBorrower = !empty($canViewBorrower);
$canEditAsset = !empty($canEditAsset);
$borrowerProfileUrl = isset($borrowerProfileUrl) ? $borrowerProfileUrl : app_url('quan-ly/nguoi-muon.php');
$detailAutoOpen = empty($showForm);
$closeDetailUrl = $link([]);
?>
<div class="modal fade asset-detail-modal" id="asset-profile" tabindex="-1" aria-labelledby="assetDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <small>Hồ sơ tài sản</small>
                    <h2 class="modal-title" id="assetDetailTitle"><?= e($asset['ma_tai_san']) ?> · <?= e($asset['ten_tai_san']) ?></h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <section class="asset-detail-summary" aria-label="Thông tin tài sản">
                    <dl class="asset-properties">
                        <?php foreach ([
                            'Loại tài sản' => $asset['ten_loai'],
                            'Phòng trực quản lý' => $asset['ten_phong_truc'],
                            'Thuộc phòng / Vị trí' => $asset['phong_hien_tai'] ? ('Phòng ' . $asset['phong_hien_tai']) : 'Tại phòng trực thiết bị',
                            'Tình trạng' => ASSET_CONDITIONS[$asset['tinh_trang']],
                            'Trạng thái' => ASSET_STATES[$asset['trang_thai_hien_thi']],
                            'Ngày đưa vào sử dụng' => $asset['ngay_dua_vao_su_dung'] ?? 'Chưa xác định',
                        ] as $label => $value): ?>
                            <div>
                                <dt><?= e($label) ?></dt>
                                <dd><?= e($value) ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                    <div id="asset-qr" aria-label="Mã QR tài sản">
                        <strong>Mã QR tài sản</strong>
                        <div data-qr="<?= e($asset['ma_qr']) ?>" role="img" aria-label="Mã QR <?= e($asset['ma_tai_san']) ?>"></div>
                        <small><?= e($asset['ma_qr']) ?></small>
                        <div class="asset-qr-downloads">
                            <button type="button" data-download-qr data-asset-code="<?= e($asset['ma_tai_san']) ?>">Tải SVG</button>
                            <button type="button" data-download-qr-png data-asset-code="<?= e($asset['ma_tai_san']) ?>">Tải PNG</button>
                        </div>
                    </div>
                </section>

                <?php if ($asset['mo_ta']): ?>
                    <section class="asset-description">
                        <strong>Mô tả</strong>
                        <p><?= nl2br(e($asset['mo_ta'])) ?></p>
                    </section>
                <?php endif; ?>

                <div class="asset-history-heading">
                    <h3>Lịch sử nghiệp vụ</h3>
                    <small>Mở từng nhóm để xem chi tiết.</small>
                </div>
                <div class="asset-history-sections">
                    <details class="asset-history-section" <?= $history['loans'] ? 'open' : '' ?>>
                        <summary><span>Mượn – trả</span><b><?= count($history['loans']) ?></b></summary>
                        <div class="asset-history-list">
                            <?php if (!$history['loans']): ?><p>Chưa có lịch sử mượn – trả.</p><?php endif; ?>
                            <?php foreach ($history['loans'] as $loan): ?>
                                <article class="loan-detail">
                                    <strong><?= e($loan['ma_phieu_muon']) ?></strong>
                                    <div>
                                        <?php if ($canViewBorrower): ?><a href="<?= e($borrowerProfileUrl) ?>?tab=all&amp;id=<?= (int)$loan['nguoi_muon_id'] ?>"><?php endif; ?>
                                            <?= e($loan['ho_ten']) ?> (<?= e($loan['ma_so_truong']) ?>)
                                        <?php if ($canViewBorrower): ?></a><?php endif; ?>
                                    </div>
                                    <small>Mượn <?= e($loan['thoi_gian_muon']) ?> · Hạn trả <?= e($loan['han_tra']) ?></small>
                                    <span><?= $loan['thoi_gian_tra'] ? 'Đã trả ' . e($loan['thoi_gian_tra']) : ($loan['qua_han'] ? 'Đang giữ · Quá hạn' : 'Đang giữ tài sản') ?></span>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <details class="asset-history-section">
                        <summary><span>Kiểm kê</span><b><?= count($history['inventory']) ?></b></summary>
                        <div class="asset-history-list">
                            <?php if (!$history['inventory']): ?><p>Chưa có lịch sử kiểm kê.</p><?php endif; ?>
                            <?php foreach ($history['inventory'] as $historyItem): ?>
                                <article class="loan-detail">
                                    <strong><?= e($historyItem['ma_dot_kiem_ke']) ?> · <?= e($historyItem['ten_dot_kiem_ke']) ?></strong>
                                    <small>Mở đợt <?= e($historyItem['thoi_gian_bat_dau']) ?></small>
                                    <span><?= e(['CHUA_KIEM_TRA'=>'Chưa kiểm tra','TIM_THAY'=>'Tìm thấy','CHUA_TIM_THAY'=>'Chưa tìm thấy'][$historyItem['ket_qua']]) ?></span>
                                    <small><?= e($historyItem['thoi_gian_kiem_tra'] ?? 'Chưa ghi nhận thời điểm kiểm tra') ?> · <?= e($historyItem['ma_phong'] ?? 'Chưa ghi nhận vị trí thực tế') ?></small>
                                    <span><?= e(ASSET_CONDITIONS[$historyItem['tinh_trang_thuc_te']] ?? 'Chưa ghi nhận tình trạng thực tế') ?></span>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <details class="asset-history-section">
                        <summary><span>Điều chuyển</span><b><?= count($history['transfers']) ?></b></summary>
                        <div class="asset-history-list">
                            <?php if (!$history['transfers']): ?><p>Chưa có lịch sử điều chuyển.</p><?php endif; ?>
                            <?php foreach ($history['transfers'] as $historyItem): ?>
                                <article class="loan-detail">
                                    <strong><?= e($historyItem['ma_dieu_chuyen']) ?></strong>
                                    <div><?= e($historyItem['phong_di']) ?> → <?= e($historyItem['phong_den']) ?></div>
                                    <small><?= e($historyItem['thoi_gian_dieu_chuyen']) ?></small>
                                    <span><?= e($historyItem['ly_do']) ?></span>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </details>

                    <details class="asset-history-section">
                        <summary><span>Sự cố tài sản</span><b><?= count($history['incidents']) ?></b></summary>
                        <div class="asset-history-list">
                            <?php if (!$history['incidents']): ?><p>Chưa ghi nhận sự cố.</p><?php endif; ?>
                            <?php foreach ($history['incidents'] as $historyItem): ?>
                                <article class="loan-detail">
                                    <strong><?= e($historyItem['ma_su_co']) ?> · <?= e(['HU_HONG'=>'Hư hỏng','MAT'=>'Mất','SAI_VI_TRI'=>'Sai vị trí'][$historyItem['loai_su_co']]) ?></strong>
                                    <small><?= e($historyItem['thoi_gian_phat_hien']) ?> · <?= e(['CHUA_XU_LY'=>'Chưa xử lý','DANG_XU_LY'=>'Đang xử lý','DA_XU_LY'=>'Đã xử lý'][$historyItem['trang_thai']]) ?></small>
                                    <p><?= e($historyItem['mo_ta']) ?></p>
                                    <?php if ($historyItem['ket_qua_xu_ly']): ?><p><?= e($historyItem['ket_qua_xu_ly']) ?></p><?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </details>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($canEditAsset): ?><a class="button" href="tai-san.php?id=<?= (int)$asset['id'] ?>&amp;edit=1">Sửa tài sản</a><?php endif; ?>
                <a class="button primary" href="<?= e($closeDetailUrl) ?>" data-bs-dismiss="modal">Đóng</a>
            </div>
        </div>
    </div>
</div>
<?php if ($detailAutoOpen): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('asset-profile');
    if (!modalElement || typeof bootstrap === 'undefined') return;
    var detailModal = new bootstrap.Modal(modalElement);
    detailModal.show();
    modalElement.addEventListener('hidden.bs.modal', function () {
        window.location.href = <?= json_encode($closeDetailUrl, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    }, { once: true });
});
</script>
<?php endif; ?>
