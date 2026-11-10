<?php
/**
 * Layout phần chân trang dùng chung (Footer & Scripts) - Bám sát phác thảo
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}
?>
        </main>
    </div>
</div>

<!-- Modal tương tác bật lên dùng chung (Xem / Thêm / Sửa) -->
<div class="modal fade" id="modalTuongTac" tabindex="-1" aria-labelledby="modalTuongTacLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" id="modalTuongTacDialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalTuongTacLabel">Thông tin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4" id="modalTuongTacBody"></div>
        </div>
    </div>
</div>

<!-- Modal xác nhận thao tác bật lên dùng chung (Xóa / Khóa / Mở khóa) -->
<div class="modal fade" id="modalXacNhan" tabindex="-1" aria-labelledby="modalXacNhanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalXacNhanLabel">Xác nhận thao tác</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalXacNhanNoiDung" class="text-dark"></div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-phu" data-bs-dismiss="modal">
                    <?= icon('x', 'bieu-tuong-sm') ?>
                    <span>Hủy</span>
                </button>
                <button type="button" class="btn btn-nguy-hiem" id="btnDongYXacNhan">
                    <?= icon('check-circle-2', 'bieu-tuong-sm') ?>
                    <span>Xác nhận</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
