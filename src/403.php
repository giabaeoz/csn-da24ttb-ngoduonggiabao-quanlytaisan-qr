<?php

declare(strict_types=1);

if (!headers_sent()) {
    http_response_code(403);
}

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';

$nguoiDung = auth_nguoi_dung_hien_tai();
$vaiTroHienTai = $nguoiDung['vai_tro'] ?? null;
$duongDanKhoiDau = auth_duong_dan_khoi_dau($vaiTroHienTai);

$vaiTroYeuCau = $thongTinLoi['vai_tro_yeu_cau'] ?? [];
$vaiTroYeuCauText = !empty($vaiTroYeuCau)
    ? implode(', ', array_map('auth_ten_vai_tro', (array) $vaiTroYeuCau))
    : 'Chức năng giới hạn';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Từ chối truy cập | Hệ thống QLTS-QR</title>
    <link href="<?= e(app_url('vendor/bootstrap.min.css')) ?>" rel="stylesheet">
    <style>.bi-shield-lock::before{content:"\1F512"}</style>
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="card border-0 shadow-sm text-center p-4">
                    <div class="card-body">
                        <div class="display-1 text-danger fw-bold mb-3">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <h1 class="h3 fw-bold text-dark mb-2">403 - Không Có Quyền Truy Cập</h1>
                        <p class="text-secondary mb-4">
                            Tài khoản của bạn không được cấp quyền để truy cập vào phân hệ hoặc tài nguyên này.
                        </p>

                        <div class="alert alert-warning text-start small mb-4">
                            <div class="mb-1">
                                <strong>Vai trò hiện tại:</strong> 
                                <span class="badge <?= auth_badge_vai_tro($vaiTroHienTai) ?>"><?= e(auth_ten_vai_tro($vaiTroHienTai)) ?></span>
                            </div>
                            <div>
                                <strong>Phân hệ yêu cầu vai trò:</strong> 
                                <span class="text-dark fw-semibold"><?= e($vaiTroYeuCauText) ?></span>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <?php if ($vaiTroHienTai): ?>
                                <a href="<?= e($duongDanKhoiDau) ?>" class="btn btn-primary">
                                    <i class="bi bi-arrow-left"></i> Về trang khởi đầu của bạn
                                </a>
                            <?php else: ?>
                                <a href="<?= e(app_url('login.php')) ?>" class="btn btn-primary">
                                    <i class="bi bi-box-arrow-in-right"></i> Đến trang đăng nhập
                                </a>
                            <?php endif; ?>
                            
                            <form action="<?= e(app_url('logout.php')) ?>" method="POST" class="d-inline">
                                <?= csrf_input() ?>
                                <button type="submit" class="btn btn-outline-secondary">
                                    <i class="bi bi-person-x"></i> Đăng nhập tài khoản khác
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="text-center text-muted small mt-3">
                    Hệ thống Quản lý tài sản bằng mã QR &bull; Đồ án cơ sở ngành
                </div>
            </div>
        </div>
    </div>
</body>
</html>

