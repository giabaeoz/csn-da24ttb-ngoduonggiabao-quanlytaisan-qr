<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rbac.php';

$nguoiDung = kiem_tra_quyen(VAI_TRO_NHAN_VIEN);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$deskId = (int)($nguoiDung['phong_truc_id'] ?? 0);
$desk = $deskId ? $db->execute_query('SELECT ten_phong_truc, ma_phong_truc, dang_hoat_dong FROM phong_truc WHERE id = ?', [$deskId])->fetch_assoc() : null;
$activeDesk = $desk && (int)$desk['dang_hoat_dong'] === 1;
$stats = ['ready' => 0, 'open' => 0, 'late' => 0];
$activeInventory = null;

if ($activeDesk) {
    $stats['ready'] = (int)$db->execute_query("SELECT COUNT(*) FROM tai_san t WHERE t.phong_truc_id = ? AND t.trang_thai = 'SAN_SANG' AND t.tinh_trang = 'TOT' AND t.phong_hoc_hien_tai_id IS NULL AND NOT EXISTS (SELECT 1 FROM chi_tiet_muon c WHERE c.tai_san_id = t.id AND c.thoi_gian_tra IS NULL)", [$deskId])->fetch_row()[0];
    $stats['open'] = (int)$db->execute_query('SELECT COUNT(*) FROM chi_tiet_muon c JOIN phieu_muon p ON p.id = c.phieu_muon_id WHERE p.phong_truc_id = ? AND c.thoi_gian_tra IS NULL', [$deskId])->fetch_row()[0];
    $stats['late'] = (int)$db->execute_query('SELECT COUNT(*) FROM chi_tiet_muon c JOIN phieu_muon p ON p.id = c.phieu_muon_id WHERE p.phong_truc_id = ? AND c.thoi_gian_tra IS NULL AND p.han_tra < NOW()', [$deskId])->fetch_row()[0];
    $activeInventory = $db->execute_query("SELECT id, ma_dot_kiem_ke FROM dot_kiem_ke WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE' ORDER BY id DESC LIMIT 1", [$deskId])->fetch_assoc();
}

$title = 'Tổng quan';
require __DIR__ . '/staff-head.php';
?>
<h1>Tổng quan phòng trực</h1>
<p class="muted">Xin chào <strong><?= e($nguoiDung['ho_ten']) ?></strong>.
    <?= $desk ? 'Phòng trực: ' . e($desk['ten_phong_truc'] . ' (' . $desk['ma_phong_truc'] . ')') : 'Chưa được phân công phòng trực.' ?></p>
<?php if (!$activeDesk): ?><div class="alert alert-warning">Cần được phân công một phòng trực đang hoạt động trước khi thao tác nghiệp vụ.</div><?php endif; ?>
<div class="row g-3 mb-3">
    <?php foreach (['Thiết bị sẵn sàng' => $stats['ready'], 'Thiết bị đang mượn' => $stats['open'], 'Thiết bị quá hạn' => $stats['late']] as $label => $value): ?>
        <div class="col-md-4"><section class="panel h-100"><span class="muted"><?= e($label) ?></span><div class="display-5"><?= (int)$value ?></div></section></div>
    <?php endforeach; ?>
</div>
<div class="row g-3">
    <div class="col-lg-4"><section class="panel h-100">
        <h2>Mượn – trả tài sản</h2>
        <p>Lập phiếu bằng mã QR hoặc mã thiết bị; khi nhận lại, ghi tình trạng từng thiết bị.</p>
        <div class="d-flex gap-2 flex-wrap"><a class="btn btn-dark" href="muon-tra.php?tab=muon">Lập phiếu mượn</a><a class="btn btn-outline-secondary" href="muon-tra.php?tab=tra">Nhận trả</a></div>
    </section></div>
    <div class="col-lg-4"><section class="panel h-100">
        <h2>Điều chuyển phòng học</h2>
        <p>Quét QR và chuyển tài sản đang sẵn sàng từ phòng học nguồn sang phòng học đích.</p>
        <a class="btn btn-outline-secondary" href="dieu-chuyen.php">Lập phiếu điều chuyển</a>
    </section></div>
    <div class="col-lg-4"><section class="panel h-100">
        <h2>Kiểm kê tài sản</h2>
        <p><?= $activeInventory ? 'Đợt ' . e($activeInventory['ma_dot_kiem_ke']) . ' đang thực hiện. Ghi nhận từng thiết bị trước khi hoàn tất.' : 'Mở đợt, quét QR đối chiếu tài sản và ghi nhận chênh lệch.' ?></p>
        <a class="btn btn-outline-secondary" href="kiem-ke.php<?= $activeInventory ? '?id=' . (int)$activeInventory['id'] : '' ?>"><?= $activeInventory ? 'Tiếp tục kiểm kê' : 'Mở trang kiểm kê' ?></a>
    </section></div>
</div>
<?php require __DIR__ . '/staff-foot.php'; ?>
