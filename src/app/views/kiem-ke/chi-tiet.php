<?php
/**
 * Giao diện: Xem chi tiết báo cáo đợt kiểm kê tài sản và in biên bản kiểm kê (G8).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * @var array<string, mixed> $dot
 * @var list<array<string, mixed>> $danhSachChiTiet
 */

require dirname(__DIR__) . '/layout/dau-trang.php';
$thongKe = $dot['thong_ke'];
$laHoanTat = ($dot['trang_thai'] === KIEM_KE_HOAN_TAT);
$laDaHuy = ($dot['trang_thai'] === KIEM_KE_DA_HUY);

// Lọc riêng danh sách thiết bị có chênh lệch / sự cố
$dsChenhLech = array_filter($danhSachChiTiet, function($item) {
    if ($item['ket_qua'] === 'CHUA_TIM_THAY') return true;
    if ($item['ket_qua'] === 'TIM_THAY' && $item['tinh_trang_thuc_te'] === 'HU_HONG') return true;
    if ($item['ket_qua'] === 'TIM_THAY' && $item['phong_hoc_thuc_te_id'] !== null && (int)$item['phong_hoc_thuc_te_id'] !== (int)$item['phong_hoc_so_sach_id']) return true;
    return false;
});
?>

<div class="container-fluid py-3">
<!-- Tiêu đề & Nút thao tác (NO PRINT) -->
<div class="thanh-cong-cu-trang no-print">
    <div>
        <h1 class="tieu-de-trang mb-1">Biên bản kiểm kê: <?= e($dot['ma_dot_kiem_ke']) ?></h1>
        <div class="mo-ta-phu">
            <?php if ($laHoanTat): ?>
                <span class="huy-hieu huy-hieu-thanh-cong">Đã hoàn tất</span>
            <?php elseif ($laDaHuy): ?>
                <span class="huy-hieu huy-hieu-nguy-hiem">Đã hủy</span>
            <?php else: ?>
                <span class="huy-hieu huy-hieu-canh-bao">Đang kiểm kê</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('kiem-ke/index.php') ?>" class="btn btn-phu">
            <?= icon('arrow-left', 'bieu-tuong-sm') ?>
            <span>Quay lại</span>
        </a>
        <?php if (!$laHoanTat && !$laDaHuy): ?>
            <a href="<?= url('kiem-ke/thuc-hien.php?id=' . $dot['id']) ?>" class="btn btn-chinh">
                <?= icon('clipboard-check', 'bieu-tuong-sm') ?>
                <span>Bàn làm việc kiểm kê</span>
            </a>
        <?php endif; ?>
        <button type="button" class="btn btn-phu" onclick="window.print()">
            <?= icon('printer', 'bieu-tuong-sm') ?>
            <span>In biên bản</span>
        </button>
    </div>
</div>

    <!-- KHUNG IN BIÊN BẢN CHÍNH THỨC (HIỂN THỊ KHI IN & TRÊN WEB) -->
    <div class="card border-0 shadow-sm p-4 bg-white" id="khungBienBanIn">
        <!-- TIÊU NGỮ QUỐC GIA & ĐƠN VỊ (CHUẨN HÀNH CHÍNH) -->
        <div class="row align-items-start pb-3 border-bottom mb-4">
            <div class="col-6 text-center">
                <div class="text-uppercase fw-bold text-secondary small">TRƯỜNG ĐẠI HỌC CÔNG NGHỆ</div>
                <div class="fw-bold fs-6 text-uppercase text-dark">BAN QUẢN LÝ THIẾT BỊ VÀ TÀI SẢN</div>
                <div class="small text-muted font-monospace mt-1">Phòng trực: <?= e($dot['ten_phong_truc']) ?> (<?= e($dot['ma_phong_truc']) ?>)</div>
            </div>
            <div class="col-6 text-center">
                <div class="text-uppercase fw-bold text-dark fs-6">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                <div class="fw-bold small text-dark">Độc lập - Tự do - Hạnh phúc</div>
                <div class="small text-muted mt-1">***</div>
            </div>
        </div>

        <!-- TIÊU ĐỀ BIÊN BẢN -->
        <div class="text-center mb-4">
            <h3 class="fw-bold text-uppercase text-dark mb-1">BIÊN BẢN KIỂM KÊ TÀI SẢN & THIẾT BỊ</h3>
            <div class="text-muted small">
                Mã đợt kiểm kê: <span class="font-monospace fw-bold text-primary"><?= e($dot['ma_dot_kiem_ke']) ?></span> &bull; 
                Loại: <strong><?= e(nhan_loai_kiem_ke($dot['loai_kiem_ke'])) ?></strong>
            </div>
        </div>

        <!-- THÔNG TIN CHUNG ĐỢT KIỂM KÊ -->
        <div class="p-3 bg-light rounded-3 mb-4 border">
            <div class="row g-2 small">
                <div class="col-md-6">
                    <strong>Tên đợt kiểm kê:</strong> <?= e($dot['ten_dot_kiem_ke']) ?>
                </div>
                <div class="col-md-6">
                    <strong>Bàn trực phụ trách:</strong> <?= e($dot['ten_phong_truc']) ?>
                </div>
                <div class="col-md-6">
                    <strong>Cán bộ lập đợt:</strong> <?= e($dot['ho_ten_nguoi_lap']) ?> (<?= e($dot['ma_nhan_vien_lap']) ?>)
                </div>
                <div class="col-md-6">
                    <strong>Thời gian bắt đầu:</strong> <?= dinh_dang_ngay_gio($dot['thoi_gian_bat_dau']) ?>
                </div>
                <div class="col-md-6">
                    <strong>Thời gian kết thúc:</strong> <?= $dot['thoi_gian_ket_thuc'] ? dinh_dang_ngay_gio($dot['thoi_gian_ket_thuc']) : 'Chưa kết thúc' ?>
                </div>
                <div class="col-md-6">
                    <strong>Trạng thái:</strong> 
                    <?php if ($laHoanTat): ?>
                        <span class="text-success fw-bold">Hoàn tất chốt sổ</span>
                    <?php elseif ($laDaHuy): ?>
                        <span class="text-danger fw-bold">Đã hủy (Lý do: <?= e($dot['ly_do_huy'] ?? '') ?>)</span>
                    <?php else: ?>
                        <span class="text-warning fw-bold">Đang tiến hành</span>
                    <?php endif; ?>
                </div>
                <?php if ($dot['ghi_chu']): ?>
                    <div class="col-12 mt-2 pt-2 border-top">
                        <strong>Mục đích / Ghi chú:</strong> <?= e($dot['ghi_chu']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PHẦN I: TỔNG HỢP SỐ LIỆU ĐỐI CHIẾU -->
        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
            I. TỔNG HỢP SỐ LIỆU ĐỐI CHIẾU SỔ SÁCH & THỰC TẾ
        </h5>
        <div class="table-responsive mb-4">
            <table class="table table-bordered text-center align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th rowspan="2" class="align-middle cot-140">Tổng tài sản sổ sách</th>
                        <th colspan="2" class="text-success">Thực tế có mặt</th>
                        <th rowspan="2" class="align-middle text-danger cot-130">Chưa tìm thấy (Mất)</th>
                        <th rowspan="2" class="align-middle text-info cot-130">Đang cho mượn</th>
                        <th rowspan="2" class="align-middle text-warning cot-130">Sai vị trí phòng</th>
                        <th rowspan="2" class="align-middle cot-120">Tỷ lệ đối chiếu</th>
                    </tr>
                    <tr>
                        <th class="small text-success cot-120">Tình trạng Tốt</th>
                        <th class="small text-danger cot-120">Hư hỏng mới</th>
                    </tr>
                </thead>
                <tbody class="fw-bold fs-6">
                    <tr>
                        <td class="text-primary"><?= (int)$thongKe['tong_so_sach'] ?></td>
                        <td class="text-success"><?= (int)$thongKe['so_tim_thay_tot'] ?></td>
                        <td class="text-danger"><?= (int)$thongKe['so_tim_thay_hong'] ?></td>
                        <td class="text-danger"><?= (int)$thongKe['so_chua_tim_thay'] ?></td>
                        <td class="text-info"><?= (int)$thongKe['so_dang_muon'] ?></td>
                        <td class="text-warning"><?= (int)$thongKe['so_sai_vi_tri'] ?></td>
                        <td class="text-dark">
                            <?php 
                            $tongKiem = (int)$thongKe['tong_so_sach'];
                            $daXong = (int)$thongKe['so_tim_thay'] + (int)$thongKe['so_chua_tim_thay'] + (int)$thongKe['so_dang_muon'];
                            $tyLe = $tongKiem > 0 ? round(($daXong / $tongKiem) * 100, 1) : 100;
                            echo $tyLe . '%';
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PHẦN II: DANH SÁCH THIẾT BỊ PHÁT HIỆN CHÊNH LỆCH / SỰ CỐ -->
        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3 d-flex justify-content-between align-items-center">
            <span>II. DANH MỤC THIẾT BỊ PHÁT SINH CHÊNH LỆCH & SỰ CỐ (<?= count($dsChenhLech) ?>)</span>
            <?php if (count($dsChenhLech) === 0): ?>
                <span class="huy-hieu huy-hieu-thanh-cong">Không phát hiện chênh lệch</span>
            <?php endif; ?>
        </h5>

        <?php if (empty($dsChenhLech)): ?>
            <div class="alert alert-success border-0 small mb-4 py-2">
                <?= icon('check-circle-2', 'bieu-tuong-md') ?>
                <span>Toàn bộ thiết bị có mặt đầy đủ, đúng vị trí và hoạt động tốt theo sổ sách.</span>
            </div>
        <?php else: ?>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle small mb-0">
                    <thead class="table-light text-uppercase">
                        <tr>
                            <th class="text-center cot-40">#</th>
                            <th class="cot-120">Mã tài sản</th>
                            <th>Tên thiết bị</th>
                            <th class="cot-130">Vị trí sổ sách</th>
                            <th class="cot-130">Vị trí thực tế</th>
                            <th class="cot-140">Phân loại chênh lệch</th>
                            <th>Mô tả sự cố / Ghi chú kiểm kê</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $cIdx = 1; foreach ($dsChenhLech as $item): ?>
                            <?php 
                            $loaiChenhLech = '';
                            $badgeClass = '';
                            if ($item['ket_qua'] === 'CHUA_TIM_THAY') {
                                $loaiChenhLech = 'MẤT / CHƯA TÌM THẤY';
                                $badgeClass = 'huy-hieu huy-hieu-nguy-hiem';
                            } elseif ($item['ket_qua'] === 'TIM_THAY' && $item['tinh_trang_thuc_te'] === 'HU_HONG') {
                                $loaiChenhLech = 'HƯ HỎNG MỚI';
                                $badgeClass = 'huy-hieu huy-hieu-canh-bao';
                            } elseif ($item['ket_qua'] === 'TIM_THAY' && $item['phong_hoc_thuc_te_id'] !== null && (int)$item['phong_hoc_thuc_te_id'] !== (int)$item['phong_hoc_so_sach_id']) {
                                $loaiChenhLech = 'SAI VỊ TRÍ PHÒNG';
                                $badgeClass = 'huy-hieu huy-hieu-chinh';
                            }
                            ?>
                            <tr>
                                <td class="text-center text-muted"><?= $cIdx++ ?></td>
                                <td class="font-monospace fw-bold"><?= e($item['ma_tai_san']) ?></td>
                                <td class="fw-semibold"><?= e($item['ten_tai_san']) ?></td>
                                <td><?= e($item['ma_phong_so_sach'] ?? 'Kho trực') ?></td>
                                <td>
                                    <?php if ($item['ket_qua'] === 'CHUA_TIM_THAY'): ?>
                                        <span class="text-danger">Không thấy</span>
                                    <?php else: ?>
                                        <strong class="text-primary"><?= e($item['ma_phong_thuc_te'] ?? 'Kho trực') ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td><span class="<?= $badgeClass ?>"><?= $loaiChenhLech ?></span></td>
                                <td><?= e($item['ghi_chu'] ?? 'Chưa ghi chú') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- PHẦN III: TOÀN BỘ SỔ SÁCH KIỂM KÊ -->
        <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
            III. CHI TIẾT TOÀN BỘ DANH MỤC THIẾT BỊ KIỂM KÊ (<?= count($danhSachChiTiet) ?>)
        </h5>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle small mb-0">
                <thead class="table-light text-uppercase">
                    <tr>
                        <th class="text-center cot-35">#</th>
                        <th class="cot-110">Mã TS</th>
                        <th>Tên thiết bị</th>
                        <th class="cot-100">Vị trí sổ</th>
                        <th class="cot-100">Vị trí thực</th>
                        <th class="cot-80">TT Sổ</th>
                        <th class="cot-110">Kết quả thực tế</th>
                        <th class="cot-130">Thời gian kiểm</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($danhSachChiTiet as $idx => $ct): ?>
                        <tr>
                            <td class="text-center text-muted"><?= $idx + 1 ?></td>
                            <td class="font-monospace fw-bold"><?= e($ct['ma_tai_san']) ?></td>
                            <td><?= e($ct['ten_tai_san']) ?></td>
                            <td><?= e($ct['ma_phong_so_sach'] ?? 'Kho trực') ?></td>
                            <td>
                                <?php if ($ct['trang_thai_so_sach'] === 'DANG_MUON'): ?>
                                    <span class="text-info">Đang mượn</span>
                                <?php elseif ($ct['ket_qua'] === 'CHUA_TIM_THAY'): ?>
                                    <span class="text-danger">Không thấy</span>
                                <?php else: ?>
                                    <?= e($ct['ma_phong_thuc_te'] ?? 'Kho trực') ?>
                                <?php endif; ?>
                            </td>
                            <td><?= e($ct['tinh_trang_so_sach']) ?></td>
                            <td>
                                <?php if ($ct['trang_thai_so_sach'] === 'DANG_MUON'): ?>
                                    <span class="badge bg-info text-dark">Đang mượn</span>
                                <?php elseif ($ct['ket_qua'] === 'TIM_THAY'): ?>
                                    <?php if ($ct['tinh_trang_thuc_te'] === 'TOT'): ?>
                                        <span class="badge bg-success">Có mặt (Tốt)</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Có mặt (Hỏng)</span>
                                    <?php endif; ?>
                                <?php elseif ($ct['ket_qua'] === 'CHUA_TIM_THAY'): ?>
                                    <span class="badge bg-danger">Chưa tìm thấy</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Chưa kiểm</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= $ct['thoi_gian_kiem_tra'] ? dinh_dang_ngay_gio($ct['thoi_gian_kiem_tra']) : '--' ?></td>
                            <td class="text-muted small"><?= e($ct['ghi_chu'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- PHẦN IV: CHỮ KÝ VÀ XÁC NHẬN CÁC BÊN -->
        <div class="row text-center mt-5 pt-3">
            <div class="col-4">
                <div class="fw-bold text-uppercase small text-dark">CÁN BỘ KIỂM KÊ LẬP BIÊN BẢN</div>
                <div class="text-muted small fst-italic mb-5">(Ký, ghi rõ họ tên)</div>
                <div class="fw-bold mt-4 text-dark"><?= e($dot['ho_ten_nguoi_lap']) ?></div>
            </div>
            <div class="col-4">
                <div class="fw-bold text-uppercase small text-dark">ĐẠI DIỆN PHÒNG TRỰC</div>
                <div class="text-muted small fst-italic mb-5">(Ký, ghi rõ họ tên)</div>
                <div class="fw-bold mt-4 text-dark"><?= e($dot['ten_phong_truc']) ?></div>
            </div>
            <div class="col-4">
                <div class="fw-bold text-uppercase small text-dark">BAN GIÁM HIỆU / QUẢN TRỊ VIÊN</div>
                <div class="text-muted small fst-italic mb-5">(Ký, ghi rõ họ tên và đóng dấu)</div>
                <div class="fw-bold mt-4 text-dark">...................................................</div>
            </div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/layout/chan-trang.php'; ?>
