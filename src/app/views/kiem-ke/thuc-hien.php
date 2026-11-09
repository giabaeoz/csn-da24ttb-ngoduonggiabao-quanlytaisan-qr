<?php
/**
 * Giao diện: Bàn làm việc thực hiện đối chiếu kiểm kê tài sản trực tiếp (G8).
 */

declare(strict_types=1);

require dirname(__DIR__) . '/layout/dau-trang.php';
require dirname(__DIR__) . '/layout/menu.php';

$thongKe = $dot['thong_ke'];
$canChot = ($thongKe['so_con_lai_can_kiem'] === 0);
?>

<div class="container-fluid py-3">
    <!-- Breadcrumb & Tiêu đề -->
    <div class="row align-items-center mb-3">
        <div class="col-md-7">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="<?= url('tong-quan.php') ?>" class="text-decoration-none">Tổng quan</a></li>
                    <li class="breadcrumb-item"><a href="<?= url('kiem-ke/index.php') ?>" class="text-decoration-none">Kiểm kê tài sản</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($dot['ma_dot_kiem_ke']) ?></li>
                </ol>
            </nav>
            <h1 class="h3 mb-0 text-gray-800 fw-bold d-flex align-items-center gap-2 flex-wrap">
                <span>🔍 <?= e($dot['ten_dot_kiem_ke']) ?></span>
                <span class="badge bg-warning text-dark font-monospace fs-6">Đang kiểm kê</span>
            </h1>
            <p class="text-muted small mb-0 mt-1">
                Bàn trực: <strong><?= e($dot['ten_phong_truc']) ?></strong> &bull; 
                Mở bởi: <strong><?= e($dot['ho_ten_nguoi_lap']) ?></strong> lúc <?= dinh_dang_ngay_gio($dot['thoi_gian_bat_dau']) ?> &bull;
                Mã đợt: <span class="font-monospace fw-bold text-primary"><?= e($dot['ma_dot_kiem_ke']) ?></span>
            </p>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2 flex-wrap">
            <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Danh sách
            </a>
            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHuyDot">
                <i class="bi bi-x-circle"></i> Hủy đợt
            </button>
            <button type="button" class="btn <?= $canChot ? 'btn-success' : 'btn-outline-success' ?> shadow-sm fw-semibold" 
                    data-bs-toggle="modal" data-bs-target="#modalHoanTat">
                <i class="bi bi-check2-circle me-1"></i> Hoàn tất & Chốt sổ
            </button>
        </div>
    </div>

    <!-- HÀNG THẺ THỐNG KÊ (5 TILES) -->
    <div class="row g-3 mb-4">
        <!-- Tổng sổ sách -->
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-bold">Tổng sổ sách</div>
                    <div class="h3 mb-0 fw-bold text-primary"><?= (int)$thongKe['tong_so_sach'] ?></div>
                    <div class="text-muted small mt-1">Thiết bị quản lý</div>
                </div>
            </div>
        </div>

        <!-- Còn lại cần kiểm -->
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 <?= ($thongKe['so_con_lai_can_kiem'] > 0) ? 'bg-warning bg-opacity-10' : '' ?>">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-bold">Còn cần kiểm</div>
                    <div class="h3 mb-0 fw-bold text-warning"><?= (int)$thongKe['so_con_lai_can_kiem'] ?></div>
                    <div class="text-muted small mt-1">Chưa đối chiếu</div>
                </div>
            </div>
        </div>

        <!-- Có mặt (Tốt + Hỏng) -->
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-bold">Tìm thấy (Có mặt)</div>
                    <div class="h3 mb-0 fw-bold text-success">
                        <?= (int)$thongKe['so_tim_thay'] ?>
                    </div>
                    <div class="small mt-1 text-muted">
                        <span class="text-success"><i class="bi bi-check"></i> <?= (int)$thongKe['so_tim_thay_tot'] ?> tốt</span> &bull; 
                        <span class="text-danger"><i class="bi bi-exclamation"></i> <?= (int)$thongKe['so_tim_thay_hong'] ?> hỏng</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chưa tìm thấy (Mất) -->
        <div class="col-6 col-lg-2">
            <div class="card border-0 shadow-sm border-start border-danger border-4 h-100 <?= ($thongKe['so_chua_tim_thay'] > 0) ? 'bg-danger bg-opacity-10' : '' ?>">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-bold">Chưa tìm thấy</div>
                    <div class="h3 mb-0 fw-bold text-danger"><?= (int)$thongKe['so_chua_tim_thay'] ?></div>
                    <div class="text-muted small mt-1">Nghi ngờ thất lạc</div>
                </div>
            </div>
        </div>

        <!-- Đang mượn (Bỏ qua) -->
        <div class="col-12 col-lg-3">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100">
                <div class="card-body p-3">
                    <div class="text-muted small text-uppercase fw-bold">Đang cho mượn</div>
                    <div class="h3 mb-0 fw-bold text-info"><?= (int)$thongKe['so_dang_muon'] ?></div>
                    <div class="text-muted small mt-1">Đã mượn trước đợt (bỏ qua)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- KHU VỰC QUÉT QR & TÌM KIẾM NHANH -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-qr-code-scan text-primary"></i> Quét mã QR đối chiếu trực tiếp
                    </h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleCamera" onclick="batTatCamera()">
                        <i class="bi bi-camera-video me-1"></i> Bật Camera
                    </button>
                </div>
                <div class="card-body p-3">
                    <!-- Khung quét camera -->
                    <div id="khuVucCamera" class="d-none text-center mb-3 p-3 bg-dark rounded-3 position-relative">
                        <div class="position-relative d-inline-block">
                            <video id="videoQuet" playsinline style="max-width: 100%; max-height: 240px; border-radius: 8px;"></video>
                            <canvas id="canvasQuet" class="d-none"></canvas>
                            <div class="position-absolute top-50 start-50 translate-middle border border-2 border-success rounded-3" 
                                 style="width: 180px; height: 180px; pointer-events: none; box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.45);">
                            </div>
                        </div>
                        <div class="text-white small mt-2">
                            Hướng camera vào tem QR trên thiết bị để hệ thống tự động nhận diện và mở cửa sổ đối chiếu...
                        </div>
                    </div>

                    <!-- Ô nhập nhanh mã thiết bị hoặc mã QR -->
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-upc-scan"></i></span>
                        <input type="text" id="inputMaQuet" class="form-control font-monospace" 
                               placeholder="Quét tem QR hoặc gõ mã TS (VD: MC-0001, QR-D6-MC0001)..." 
                               autocomplete="off">
                        <button type="button" class="btn btn-primary fw-semibold px-4" onclick="xuLyTimKiemTaiSan()">
                            <i class="bi bi-search me-1"></i> Đối chiếu
                        </button>
                    </div>
                    <div id="thongBaoQuet" class="mt-2 small fw-semibold d-none"></div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                        <i class="bi bi-info-circle text-info"></i> Trạng thái & Hướng dẫn kiểm kê
                    </h6>
                </div>
                <div class="card-body p-3">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <i class="bi bi-check-circle-fill text-success mt-1"></i>
                            <div><strong>Có mặt (Tốt):</strong> Thiết bị nguyên vẹn, sử dụng bình thường, đúng vị trí phòng học hoặc xác nhận vị trí mới.</div>
                        </li>
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <i class="bi bi-exclamation-triangle-fill text-warning mt-1"></i>
                            <div><strong>Có mặt (Hư hỏng):</strong> Thiết bị có mặt nhưng bị hỏng hoặc bể vỡ. <em>Bắt buộc ghi rõ mô tả hư hỏng</em>. Hệ thống sẽ tự tạo phiếu sự cố khi chốt sổ.</div>
                        </li>
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <i class="bi bi-x-circle-fill text-danger mt-1"></i>
                            <div><strong>Chưa tìm thấy (Mất):</strong> Không thấy thiết bị tại vị trí. <em>Bắt buộc ghi chú khu vực đã tìm</em>. Hệ thống sẽ tạo sự cố Mất và chuyển Ngừng sử dụng khi chốt sổ.</div>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-arrow-repeat text-info mt-1"></i>
                            <div><strong>Đang mượn:</strong> Thiết bị có phiếu mượn trước đợt kiểm kê chưa trả sẽ được tự động giữ nguyên và bỏ qua kiểm tra tại chỗ.</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- DANH SÁCH CHI TIẾT SỔ SÁCH & BỘ LỌC TABS -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <!-- Bộ lọc Tabs -->
                <ul class="nav nav-pills card-header-pills">
                    <?php 
                    $currTab = $boLocChiTiet['trang_thai_loc'];
                    $taoUrlTab = function(string $tab) use ($dot, $boLocChiTiet) {
                        return url('kiem-ke/thuc-hien.php?id=' . $dot['id'] . '&trang_thai_loc=' . $tab . ($boLocChiTiet['tu_khoa'] ? '&tu_khoa=' . urlencode($boLocChiTiet['tu_khoa']) : ''));
                    };
                    ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currTab === '') ? 'active' : '' ?>" href="<?= $taoUrlTab('') ?>">
                            Tất cả <span class="badge bg-secondary ms-1"><?= (int)$thongKe['tong_so_sach'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currTab === 'CHUA_KIEM') ? 'active bg-warning text-dark' : 'text-warning' ?>" href="<?= $taoUrlTab('CHUA_KIEM') ?>">
                            Chưa kiểm <span class="badge bg-warning text-dark ms-1"><?= (int)$thongKe['so_con_lai_can_kiem'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currTab === 'TIM_THAY') ? 'active bg-success' : 'text-success' ?>" href="<?= $taoUrlTab('TIM_THAY') ?>">
                            Có mặt <span class="badge bg-success ms-1"><?= (int)$thongKe['so_tim_thay'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currTab === 'CHUA_TIM_THAY') ? 'active bg-danger' : 'text-danger' ?>" href="<?= $taoUrlTab('CHUA_TIM_THAY') ?>">
                            Chưa tìm thấy <span class="badge bg-danger ms-1"><?= (int)$thongKe['so_chua_tim_thay'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currTab === 'DANG_MUON') ? 'active bg-info text-dark' : 'text-info' ?>" href="<?= $taoUrlTab('DANG_MUON') ?>">
                            Đang mượn <span class="badge bg-info text-dark ms-1"><?= (int)$thongKe['so_dang_muon'] ?></span>
                        </a>
                    </li>
                    <?php if ($thongKe['so_sai_vi_tri'] > 0): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currTab === 'SAI_VI_TRI') ? 'active bg-secondary' : 'text-secondary' ?>" href="<?= $taoUrlTab('SAI_VI_TRI') ?>">
                            Sai phòng <span class="badge bg-secondary ms-1"><?= (int)$thongKe['so_sai_vi_tri'] ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($thongKe['so_tim_thay_hong'] > 0): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= ($currTab === 'HU_HONG') ? 'active bg-danger' : 'text-danger' ?>" href="<?= $taoUrlTab('HU_HONG') ?>">
                            Hư hỏng <span class="badge bg-danger ms-1"><?= (int)$thongKe['so_tim_thay_hong'] ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Tìm kiếm theo từ khóa trong bảng -->
                <form action="<?= url('kiem-ke/thuc-hien.php') ?>" method="GET" class="d-flex gap-2">
                    <input type="hidden" name="id" value="<?= (int)$dot['id'] ?>">
                    <input type="hidden" name="trang_thai_loc" value="<?= e($currTab) ?>">
                    <div class="input-group input-group-sm" style="min-width: 250px;">
                        <input type="text" name="tu_khoa" class="form-control" placeholder="Tìm mã TS, tên, phòng..." 
                               value="<?= e($boLocChiTiet['tu_khoa']) ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                        <?php if ($boLocChiTiet['tu_khoa'] !== ''): ?>
                            <a href="<?= $taoUrlTab($currTab) ?>" class="btn btn-outline-secondary" title="Xóa tìm kiếm"><i class="bi bi-x"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="bangKiemKe">
                <thead class="table-light text-secondary text-uppercase small">
                    <tr>
                        <th style="width: 45px;" class="ps-3">#</th>
                        <th style="width: 140px;">Mã TS / Tem QR</th>
                        <th>Tên thiết bị</th>
                        <th style="width: 140px;">Vị trí sổ sách</th>
                        <th style="width: 140px;">Vị trí thực tế</th>
                        <th style="width: 110px;">TT Sổ sách</th>
                        <th style="width: 150px;">Kết quả đối chiếu</th>
                        <th>Ghi chú / Phát hiện</th>
                        <th style="width: 90px;" class="text-end pe-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($danhSachChiTiet)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Không tìm thấy thiết bị nào khớp với điều kiện lọc hiện tại.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($danhSachChiTiet as $idx => $ct): ?>
                            <?php 
                            $laDangMuon = ($ct['trang_thai_so_sach'] === 'DANG_MUON');
                            $chuaKiem = ($ct['ket_qua'] === 'CHUA_KIEM_TRA' && !$laDangMuon);
                            $saiViTri = ($ct['ket_qua'] === 'TIM_THAY' && $ct['phong_hoc_thuc_te_id'] !== null && (int)$ct['phong_hoc_thuc_te_id'] !== (int)$ct['phong_hoc_so_sach_id']);
                            ?>
                            <tr class="<?= $chuaKiem ? 'table-warning bg-opacity-25' : '' ?> <?= ($ct['ket_qua'] === 'CHUA_TIM_THAY') ? 'table-danger bg-opacity-25' : '' ?>" id="dong-ct-<?= (int)$ct['id'] ?>">
                                <td class="ps-3 text-muted"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-bold font-monospace text-dark"><?= e($ct['ma_tai_san']) ?></div>
                                    <small class="text-muted font-monospace"><?= e($ct['ma_qr']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($ct['ten_tai_san']) ?></div>
                                    <small class="text-muted">
                                        <?= e($ct['ten_loai_tai_san'] ?? 'Thiết bị') ?>
                                        <?= ($ct['hang_san_xuat'] || $ct['model']) ? ' &bull; ' . e(trim(($ct['hang_san_xuat'] ?? '') . ' ' . ($ct['model'] ?? ''))) : '' ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($ct['ma_phong_so_sach']): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-geo-alt text-primary"></i> <?= e($ct['ma_phong_so_sach']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Tại kho trực</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($laDangMuon): ?>
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info">Đang mượn</span>
                                    <?php elseif ($ct['ket_qua'] === 'TIM_THAY'): ?>
                                        <?php if ($ct['ma_phong_thuc_te']): ?>
                                            <span class="badge <?= $saiViTri ? 'bg-danger text-white' : 'bg-success bg-opacity-10 text-success border border-success' ?>">
                                                <?= e($ct['ma_phong_thuc_te']) ?>
                                                <?= $saiViTri ? ' (Sai phòng!)' : '' ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark border">Kho trực</span>
                                        <?php endif; ?>
                                    <?php elseif ($ct['ket_qua'] === 'CHUA_TIM_THAY'): ?>
                                        <span class="text-danger small fw-semibold">Không tìm thấy</span>
                                    <?php else: ?>
                                        <span class="text-muted small">--</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($ct['tinh_trang_so_sach'] === 'TOT'): ?>
                                        <span class="badge bg-success-subtle text-success">Tốt</span>
                                    <?php elseif ($ct['tinh_trang_so_sach'] === 'HU_HONG'): ?>
                                        <span class="badge bg-danger-subtle text-danger">Hỏng</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary"><?= e($ct['tinh_trang_so_sach']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($laDangMuon): ?>
                                        <span class="badge bg-info text-dark">
                                            <i class="bi bi-clock-history me-1"></i> Đang mượn
                                        </span>
                                    <?php elseif ($ct['ket_qua'] === 'CHUA_KIEM_TRA'): ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-hourglass-split me-1"></i> Chưa kiểm
                                        </span>
                                    <?php elseif ($ct['ket_qua'] === 'TIM_THAY'): ?>
                                        <?php if ($ct['tinh_trang_thuc_te'] === 'TOT'): ?>
                                            <span class="badge bg-success">
                                                <i class="bi bi-check-circle me-1"></i> Có mặt (Tốt)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">
                                                <i class="bi bi-exclamation-triangle me-1"></i> Có mặt (Hỏng)
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($ct['ket_qua'] === 'CHUA_TIM_THAY'): ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle me-1"></i> Chưa tìm thấy
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($ct['ghi_chu']): ?>
                                        <small class="text-dark d-inline-block text-truncate" style="max-width: 200px;" title="<?= e($ct['ghi_chu']) ?>">
                                            <?= e($ct['ghi_chu']) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="text-muted small">--</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-3">
                                    <?php if ($laDangMuon): ?>
                                        <button class="btn btn-sm btn-light text-muted" disabled title="Đang mượn - Không cần kiểm">
                                            <i class="bi bi-dash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm <?= $chuaKiem ? 'btn-primary' : 'btn-outline-secondary' ?>"
                                                onclick="moModalDoiChieu(<?= htmlspecialchars(json_encode($ct), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i class="bi bi-pencil-square"></i> Kiểm
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL 1: ĐỐI CHIẾU KIỂM TRA THIẾT BỊ -->
<div class="modal fade" id="modalDoiChieu" tabindex="-1" aria-labelledby="modalDoiChieuLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white py-3">
                <h6 class="modal-title fw-bold" id="modalDoiChieuLabel">
                    <i class="bi bi-clipboard2-check me-1"></i> Đối chiếu kiểm tra thiết bị
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('kiem-ke/thuc-hien.php') ?>" method="POST" id="formGhiNhan">
                <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$dot['id'] ?>">
                <input type="hidden" name="dot_kiem_ke_id" value="<?= (int)$dot['id'] ?>">
                <input type="hidden" name="chi_tiet_id" id="mdChiTietId" value="">
                <input type="hidden" name="hanh_dong" value="ghi_nhan">

                <div class="modal-body p-4">
                    <!-- Thông tin thiết bị tóm tắt -->
                    <div class="p-3 bg-light rounded-3 mb-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold font-monospace text-primary fs-6" id="mdMaTaiSan">MC-0001</span>
                            <span class="badge bg-secondary font-monospace" id="mdMaQr">QR-D6-MC0001</span>
                        </div>
                        <div class="fw-semibold text-dark fs-6" id="mdTenTaiSan">Máy chiếu Panasonic PT-LB385</div>
                        <div class="small text-muted mt-1">
                            Vị trí sổ sách: <strong id="mdViTriSoSach">Phòng A101</strong> &bull;
                            Tình trạng sổ sách: <strong id="mdTinhTrangSoSach">Tốt</strong>
                        </div>
                    </div>

                    <!-- Lựa chọn Kết quả kiểm tra -->
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-secondary">Kết quả đối chiếu <span class="text-danger">*</span></label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="ket_qua" id="kqTimThay" value="TIM_THAY" checked onchange="thayDoiKetQua()">
                                <label class="btn btn-outline-success w-100 py-2 fw-semibold" for="kqTimThay">
                                    <i class="bi bi-check-circle me-1"></i> Có mặt
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="ket_qua" id="kqChuaTimThay" value="CHUA_TIM_THAY" onchange="thayDoiKetQua()">
                                <label class="btn btn-outline-danger w-100 py-2 fw-semibold" for="kqChuaTimThay">
                                    <i class="bi bi-x-circle me-1"></i> Chưa tìm thấy
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Khung nếu TÌM THẤY: Tình trạng thực tế + Vị trí thực tế -->
                    <div id="khungTimThay">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase text-secondary">Tình trạng thực tế <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="tinh_trang_thuc_te" id="ttTot" value="TOT" checked>
                                    <label class="btn btn-outline-success w-100 py-2" for="ttTot">
                                        <i class="bi bi-shield-check"></i> Hoạt động tốt
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="tinh_trang_thuc_te" id="ttHuHong" value="HU_HONG">
                                    <label class="btn btn-outline-danger w-100 py-2" for="ttHuHong">
                                        <i class="bi bi-tools"></i> Hư hỏng / Lỗi
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Vị trí thực tế tìm thấy</label>
                            <select name="phong_hoc_thuc_te_id" id="mdPhongHocThucTeId" class="form-select">
                                <option value="">-- Kho trực / Phòng trực quản lý --</option>
                                <?php foreach ($danhSachPhongHoc as $ph): ?>
                                    <option value="<?= (int)$ph['id'] ?>">
                                        <?= e($ph['ma_phong']) ?> - <?= e($ph['ten_phong']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Nếu vị trí thực tế khác sổ sách, hệ thống sẽ tự động ghi nhận sự cố Sai vị trí khi chốt sổ.</div>
                        </div>
                    </div>

                    <!-- Ghi chú / Mô tả hư hỏng / lý do chưa thấy -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold" id="lblGhiChu">Ghi chú đối chiếu</label>
                        <textarea name="ghi_chu" id="mdGhiChu" class="form-control" rows="2" 
                                  placeholder="Nhập ghi chú thêm hoặc bắt buộc mô tả nếu hỏng/mất..."></textarea>
                        <div class="form-text" id="helpGhiChu">Bắt buộc nhập ghi chú khi thiết bị bị hư hỏng hoặc chưa tìm thấy.</div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary fw-semibold px-4">
                        <i class="bi bi-save me-1"></i> Lưu kết quả
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: HOÀN TẤT & CHỐT SỔ ĐỢT KIỂM KÊ -->
<div class="modal fade" id="modalHoanTat" tabindex="-1" aria-labelledby="modalHoanTatLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white py-3">
                <h6 class="modal-title fw-bold" id="modalHoanTatLabel">
                    <i class="bi bi-check2-circle me-1"></i> Xác nhận hoàn tất & chốt sổ kiểm kê
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('kiem-ke/thuc-hien.php') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$dot['id'] ?>">
                <input type="hidden" name="hanh_dong" value="hoan_tat">

                <div class="modal-body p-4">
                    <?php if (!$canChot): ?>
                        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
                            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                            <div>
                                <strong>Chưa thể chốt sổ!</strong><br>
                                Hiện còn <strong><?= (int)$thongKe['so_con_lai_can_kiem'] ?></strong> thiết bị chưa được đối chiếu thực tế. Hãy ghi nhận kết quả cho toàn bộ thiết bị trước.
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="mb-3">
                            Bạn đang chuẩn bị chốt sổ đợt kiểm kê <strong class="text-primary"><?= e($dot['ma_dot_kiem_ke']) ?></strong>.
                            Sau khi chốt sổ, các thông tin đối chiếu sẽ được khóa vĩnh viễn và các tác vụ sau sẽ tự động thực hiện:
                        </p>

                        <div class="list-group list-group-flush border rounded-3 mb-3">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Tổng số tài sản sổ sách:</span>
                                <span class="fw-bold"><?= (int)$thongKe['tong_so_sach'] ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center text-success">
                                <span>Có mặt bình thường (Tốt):</span>
                                <span class="fw-bold"><?= (int)$thongKe['so_tim_thay_tot'] ?></span>
                            </div>
                            <?php if ($thongKe['so_tim_thay_hong'] > 0): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center text-danger">
                                <span>Phát hiện hư hỏng (sẽ tạo sự cố BẢO TRÌ):</span>
                                <span class="fw-bold"><?= (int)$thongKe['so_tim_thay_hong'] ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($thongKe['so_chua_tim_thay'] > 0): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center text-danger">
                                <span>Chưa tìm thấy (sẽ tạo sự cố MẤT & NGỪNG SỬ DỤNG):</span>
                                <span class="fw-bold"><?= (int)$thongKe['so_chua_tim_thay'] ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($thongKe['so_sai_vi_tri'] > 0): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center text-warning">
                                <span>Sai vị trí phòng học (sẽ tạo sự cố SAI VỊ TRÍ):</span>
                                <span class="fw-bold"><?= (int)$thongKe['so_sai_vi_tri'] ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($thongKe['so_dang_muon'] > 0): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center text-info">
                                <span>Đang mượn lúc mở đợt:</span>
                                <span class="fw-bold"><?= (int)$thongKe['so_dang_muon'] ?></span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="alert alert-warning small mb-0">
                            <i class="bi bi-info-circle me-1"></i> Bàn trực sẽ được mở khóa chức năng Cho mượn và Điều chuyển ngay sau khi chốt sổ thành công.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-success fw-semibold px-4" <?= !$canChot ? 'disabled' : '' ?>>
                        <i class="bi bi-check-lg me-1"></i> Đồng ý chốt sổ hoàn tất
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: HỦY ĐỢT KIỂM KÊ -->
<div class="modal fade" id="modalHuyDot" tabindex="-1" aria-labelledby="modalHuyDotLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-3">
                <h6 class="modal-title fw-bold" id="modalHuyDotLabel">
                    <i class="bi bi-exclamation-triangle me-1"></i> Hủy đợt kiểm kê tài sản
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('kiem-ke/thuc-hien.php') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(tao_csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$dot['id'] ?>">
                <input type="hidden" name="hanh_dong" value="huy">

                <div class="modal-body p-4">
                    <div class="alert alert-warning small mb-3">
                        Hủy đợt kiểm kê sẽ khôi phục trạng thái hoạt động bình thường của bàn trực. Mọi kết quả đối chiếu đã ghi nhận trong đợt này sẽ được lưu trữ với trạng thái ĐÃ HỦY mà không làm thay đổi trạng thái tài sản.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lý do hủy đợt kiểm kê <span class="text-danger">*</span></label>
                        <textarea name="ly_do_huy" class="form-control" rows="3" required maxlength="500"
                                  placeholder="Nhập lý do hủy đợt (ví dụ: phát hiện lỗi hệ thống, thay đổi lịch ca trực, mở nhầm bàn trực...)..."></textarea>
                        <div class="form-text">Bắt buộc nhập lý do, tối đa 500 ký tự.</div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-danger fw-semibold px-4">
                        <i class="bi bi-trash me-1"></i> Xác nhận hủy đợt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPTS HỖ TRỢ JS QUÉT QR VÀ MODAL ĐỐI CHIẾU -->
<script src="<?= url('assets/vendor/jsqr/jsQR.js') ?>"></script>
<script>
let video = null;
let canvas = null;
let ctx = null;
let cameraRunning = false;
let modalDoiChieuInst = null;

document.addEventListener('DOMContentLoaded', function() {
    video = document.getElementById('videoQuet');
    canvas = document.getElementById('canvasQuet');
    if (canvas) {
        ctx = canvas.getContext('2d', { willReadFrequently: true });
    }
    const modalEl = document.getElementById('modalDoiChieu');
    if (modalEl) {
        modalDoiChieuInst = new bootstrap.Modal(modalEl);
    }

    // Nhấn Enter ở ô nhập mã
    const inputMaQuet = document.getElementById('inputMaQuet');
    if (inputMaQuet) {
        inputMaQuet.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                xuLyTimKiemTaiSan();
            }
        });
    }
});

function batTatCamera() {
    const khuVuc = document.getElementById('khuVucCamera');
    const btn = document.getElementById('btnToggleCamera');
    if (cameraRunning) {
        dungCamera();
        khuVuc.classList.add('d-none');
        btn.innerHTML = '<i class="bi bi-camera-video me-1"></i> Bật Camera';
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-outline-primary');
    } else {
        khuVuc.classList.remove('d-none');
        btn.innerHTML = '<i class="bi bi-camera-video-off me-1"></i> Tắt Camera';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-danger');
        khoiDongCamera();
    }
}

function khoiDongCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Trình duyệt không hỗ trợ truy cập camera.');
        return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(stream) {
            video.srcObject = stream;
            video.setAttribute('playsinline', true);
            video.play();
            cameraRunning = true;
            requestAnimationFrame(quetKhungHinh);
        })
        .catch(function(err) {
            console.error('Lỗi truy cập camera:', err);
            alert('Không thể khởi động camera. Vui lòng cấp quyền camera trong trình duyệt.');
        });
}

function dungCamera() {
    if (video && video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
        video.srcObject = null;
    }
    cameraRunning = false;
}

function quetKhungHinh() {
    if (!cameraRunning) return;

    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.height = video.videoHeight;
        canvas.width = video.videoWidth;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const code = jsQR(imageData.data, imageData.width, imageData.height, {
            inversionAttempts: 'dontInvert',
        });

        if (code && code.data) {
            const scannedData = code.data.trim();
            if (scannedData !== '') {
                // Tạm dừng camera khi nhận diện được mã
                dungCamera();
                const btn = document.getElementById('btnToggleCamera');
                const khuVuc = document.getElementById('khuVucCamera');
                khuVuc.classList.add('d-none');
                btn.innerHTML = '<i class="bi bi-camera-video me-1"></i> Bật Camera';
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-outline-primary');

                traCuuVaMoModal(scannedData);
                return;
            }
        }
    }
    requestAnimationFrame(quetKhungHinh);
}

function xuLyTimKiemTaiSan() {
    const input = document.getElementById('inputMaQuet');
    const ma = input.value.trim();
    if (ma === '') {
        alert('Vui lòng nhập mã tài sản hoặc mã QR để đối chiếu.');
        input.focus();
        return;
    }
    traCuuVaMoModal(ma);
}

function traCuuVaMoModal(ma) {
    const tb = document.getElementById('thongBaoQuet');
    tb.className = 'mt-2 small fw-semibold text-primary';
    tb.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i> Đang tra cứu thiết bị...';
    tb.classList.remove('d-none');

    const dotId = <?= (int)$dot['id'] ?>;
    const urlApi = '<?= url("api/kiem-ke.php") ?>?hanh_dong=tra_cuu&dot_kiem_ke_id=' + dotId + '&ma_qr=' + encodeURIComponent(ma) + '&ma_tai_san=' + encodeURIComponent(ma);

    fetch(urlApi)
        .then(response => response.json())
        .then(data => {
            if (data.thanh_cong && data.chi_tiet) {
                tb.className = 'mt-2 small fw-semibold text-success';
                tb.innerHTML = '<i class="bi bi-check-circle me-1"></i> Đã tìm thấy: ' + data.tai_san.ten_tai_san;
                // Merge tai_san info into chi_tiet object for modal
                const item = Object.assign({}, data.chi_tiet, {
                    ma_tai_san: data.tai_san.ma_tai_san,
                    ma_qr: data.tai_san.ma_qr,
                    ten_tai_san: data.tai_san.ten_tai_san
                });
                moModalDoiChieu(item);
            } else {
                tb.className = 'mt-2 small fw-semibold text-danger';
                tb.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> ' + (data.loai || data.loi || 'Không tìm thấy thiết bị trong đợt kiểm kê này.');
            }
        })
        .catch(err => {
            console.error(err);
            tb.className = 'mt-2 small fw-semibold text-danger';
            tb.innerHTML = '<i class="bi bi-exclamation-triangle me-1"></i> Lỗi kết nối đến máy chủ tra cứu.';
        });
}

function moModalDoiChieu(ct) {
    document.getElementById('mdChiTietId').value = ct.id;
    document.getElementById('mdMaTaiSan').textContent = ct.ma_tai_san || '';
    document.getElementById('mdMaQr').textContent = ct.ma_qr || '';
    document.getElementById('mdTenTaiSan').textContent = ct.ten_tai_san || '';
    document.getElementById('mdViTriSoSach').textContent = ct.ma_phong_so_sach ? (ct.ma_phong_so_sach + ' - ' + (ct.ten_phong_so_sach || '')) : 'Kho trực';
    document.getElementById('mdTinhTrangSoSach').textContent = ct.tinh_trang_so_sach === 'TOT' ? 'Tốt' : (ct.tinh_trang_so_sach === 'HU_HONG' ? 'Hỏng' : (ct.tinh_trang_so_sach || 'Tốt'));

    // Gán kết quả
    if (ct.ket_qua === 'CHUA_TIM_THAY') {
        document.getElementById('kqChuaTimThay').checked = true;
    } else {
        document.getElementById('kqTimThay').checked = true;
    }

    // Tình trạng thực tế
    if (ct.tinh_trang_thuc_te === 'HU_HONG') {
        document.getElementById('ttHuHong').checked = true;
    } else {
        document.getElementById('ttTot').checked = true;
    }

    // Phòng học thực tế
    const selPhong = document.getElementById('mdPhongHocThucTeId');
    const phongId = ct.phong_hoc_thuc_te_id || ct.phong_hoc_so_sach_id || '';
    selPhong.value = phongId;

    // Ghi chú
    document.getElementById('mdGhiChu').value = ct.ghi_chu || '';

    thayDoiKetQua();
    if (modalDoiChieuInst) {
        modalDoiChieuInst.show();
    }
}

function thayDoiKetQua() {
    const laTimThay = document.getElementById('kqTimThay').checked;
    const khung = document.getElementById('khungTimThay');
    const lblGhiChu = document.getElementById('lblGhiChu');
    const helpGhiChu = document.getElementById('helpGhiChu');

    if (laTimThay) {
        khung.style.display = 'block';
        lblGhiChu.textContent = 'Ghi chú đối chiếu';
        helpGhiChu.textContent = 'Bắt buộc ghi chú nếu tình trạng thực tế là Hư hỏng.';
    } else {
        khung.style.display = 'none';
        lblGhiChu.innerHTML = 'Lý do chưa tìm thấy thiết bị <span class="text-danger">*</span>';
        helpGhiChu.textContent = 'Bắt buộc ghi rõ khu vực đã rà soát hoặc lý do chưa tìm thấy.';
    }
}
</script>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
