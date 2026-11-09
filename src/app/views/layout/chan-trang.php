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

<script src="<?= url('assets/vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
