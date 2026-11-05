<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/borrowers.php';
require_once __DIR__ . '/../includes/borrow-service.php';

$nguoiDung = kiem_tra_quyen(VAI_TRO_NHAN_VIEN);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$tab = ($_GET['tab'] ?? 'muon') === 'tra' ? 'tra' : 'muon';
$title = 'Mượn – trả tài sản';
$deskId = (int)($nguoiDung['phong_truc_id'] ?? 0);
$desk = $deskId ? $db->execute_query('SELECT ten_phong_truc, dang_hoat_dong FROM phong_truc WHERE id = ?', [$deskId])->fetch_assoc() : null;
$canOperate = $desk && (int)$desk['dang_hoat_dong'] === 1;
$error = '';
$oldBorrower = is_scalar($_POST['nguoi_muon_id'] ?? null) ? (string)$_POST['nguoi_muon_id'] : '';
$oldRoom = is_scalar($_POST['phong_hoc_id'] ?? null) ? (string)$_POST['phong_hoc_id'] : '';
$oldDue = is_string($_POST['han_tra'] ?? null) ? $_POST['han_tra'] : '';
$oldNote = is_string($_POST['ghi_chu'] ?? null) ? $_POST['ghi_chu'] : '';
$oldAssets = is_array($_POST['tai_san_id'] ?? null) ? array_filter($_POST['tai_san_id'], 'is_scalar') : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang.');
    }
    try {
        if ($tab === 'muon') {
            $id = loan_create($db, (int)$nguoiDung['id'], $_POST);
            set_flash('success', 'Đã lập phiếu mượn thành công.');
            header('Location: muon-tra.php?tab=tra&id=' . $id);
        } else {
            $open = loan_return($db, (int)$nguoiDung['id'], $_POST);
            set_flash('success', $open ? "Đã nhận trả. Phiếu còn $open thiết bị chưa trả." : 'Đã nhận trả đầy đủ và đóng phiếu.');
            header('Location: muon-tra.php?tab=tra&id=' . loan_id($_POST['phieu_muon_id'] ?? null));
        }
        exit;
    } catch (DomainException $ex) {
        $error = $ex->getMessage();
    } catch (mysqli_sql_exception $ex) {
        error_log('[LOAN_DB] ' . $ex->getMessage());
        $error = 'Không thể lưu phiếu vì dữ liệu đã thay đổi hoặc trùng lặp. Vui lòng tải lại và thử lại.';
    }
}

$borrowers = [];
$rooms = [];
$assets = [];
$loans = [];
$selectedLoan = null;
$details = [];
if ($canOperate) {
    $rooms = $db->execute_query('SELECT id, ma_phong, ten_phong FROM phong_hoc WHERE dang_hoat_dong = 1 ORDER BY ma_phong')->fetch_all(MYSQLI_ASSOC);
    if ($tab === 'muon') {
        $bq = is_string($_GET['nguoi_muon'] ?? null) ? mb_substr(trim($_GET['nguoi_muon']), 0, 100) : '';
        $pattern = borrower_pattern($bq);
        $borrowers = $db->execute_query("SELECT id, ma_nguoi_muon, ma_so_truong, ho_ten, lop_don_vi, so_dien_thoai FROM nguoi_muon WHERE ma_so_truong LIKE ? ESCAPE '!' OR ho_ten LIKE ? ESCAPE '!' OR so_dien_thoai LIKE ? ESCAPE '!' OR ma_nguoi_muon LIKE ? ESCAPE '!' ORDER BY ho_ten LIMIT 50", [$pattern, $pattern, $pattern, $pattern])->fetch_all(MYSQLI_ASSOC);
        $assets = $db->execute_query("SELECT t.id, t.ma_tai_san, t.ma_qr, t.ten_tai_san, t.pham_vi_tuong_thich, t.phong_tuong_thich, t.phong_hoc_phu_hop_id FROM tai_san t WHERE t.phong_truc_id = ? AND t.tinh_trang = 'TOT' AND t.trang_thai = 'SAN_SANG' AND t.phong_hoc_hien_tai_id IS NULL AND NOT EXISTS (SELECT 1 FROM chi_tiet_muon c WHERE c.tai_san_id = t.id AND c.thoi_gian_tra IS NULL) ORDER BY t.ma_tai_san LIMIT 500", [$deskId])->fetch_all(MYSQLI_ASSOC);
    } else {
        $q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
        $pattern = borrower_pattern($q);
        $loans = $db->execute_query("SELECT p.id, p.ma_phieu_muon, p.han_tra, p.trang_thai, n.ho_ten, n.ma_so_truong, h.ma_phong, COUNT(c.id) AS so_chua_tra FROM phieu_muon p JOIN nguoi_muon n ON n.id = p.nguoi_muon_id JOIN phong_hoc h ON h.id = p.phong_hoc_id JOIN chi_tiet_muon c ON c.phieu_muon_id = p.id AND c.thoi_gian_tra IS NULL WHERE p.phong_truc_id = ? AND (p.ma_phieu_muon LIKE ? ESCAPE '!' OR n.ma_so_truong LIKE ? ESCAPE '!' OR n.ho_ten LIKE ? ESCAPE '!' OR EXISTS (SELECT 1 FROM chi_tiet_muon cc JOIN tai_san t ON t.id = cc.tai_san_id WHERE cc.phieu_muon_id = p.id AND cc.thoi_gian_tra IS NULL AND (t.ma_tai_san LIKE ? ESCAPE '!' OR t.ma_qr LIKE ? ESCAPE '!'))) GROUP BY p.id ORDER BY p.han_tra, p.id LIMIT 50", [$deskId, $pattern, $pattern, $pattern, $pattern, $pattern])->fetch_all(MYSQLI_ASSOC);
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id && $q !== '') {
            // QR and asset codes are unique; an exact scan opens its still-active loan.
            $match = $db->execute_query('SELECT p.id FROM chi_tiet_muon c JOIN phieu_muon p ON p.id = c.phieu_muon_id JOIN tai_san t ON t.id = c.tai_san_id WHERE p.phong_truc_id = ? AND c.thoi_gian_tra IS NULL AND (t.ma_qr = ? OR t.ma_tai_san = ?) LIMIT 1', [$deskId, $q, $q])->fetch_row();
            $id = $match ? (int)$match[0] : null;
        }
        if ($id) {
            $selectedLoan = $db->execute_query('SELECT p.*, n.ma_so_truong, n.ho_ten, n.so_dien_thoai, n.lop_don_vi, h.ma_phong FROM phieu_muon p JOIN nguoi_muon n ON n.id = p.nguoi_muon_id JOIN phong_hoc h ON h.id = p.phong_hoc_id WHERE p.id = ? AND p.phong_truc_id = ?', [(int)$id, $deskId])->fetch_assoc();
            if ($selectedLoan) {
                $details = $db->execute_query('SELECT c.*, t.ma_tai_san, t.ma_qr, t.ten_tai_san FROM chi_tiet_muon c JOIN tai_san t ON t.id = c.tai_san_id WHERE c.phieu_muon_id = ? ORDER BY c.id', [(int)$id])->fetch_all(MYSQLI_ASSOC);
            }
        }
    }
}
require __DIR__ . '/staff-head.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3"><h1 class="m-0">Mượn – trả tài sản</h1><div class="staff-tabs d-flex gap-2"><a class="btn btn-outline-secondary <?= $tab === 'muon' ? 'active' : '' ?>" href="muon-tra.php?tab=muon">Mượn</a><a class="btn btn-outline-secondary <?= $tab === 'tra' ? 'active' : '' ?>" href="muon-tra.php?tab=tra">Trả</a></div></div>
<?php if (!$canOperate): ?><div class="alert alert-warning">Tài khoản chưa được phân công phòng trực đang hoạt động. Vui lòng liên hệ Quản trị viên.</div><?php else: ?>
<p class="muted mb-3">Phạm vi: <strong><?= e($desk['ten_phong_truc']) ?></strong>. Chỉ tài sản thuộc phòng trực này được mượn hoặc nhận trả.</p>
<?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
<?php if ($tab === 'muon'): ?>
<?php require __DIR__ . '/views/borrow-form.php'; ?>
<?php else: ?>
<?php require __DIR__ . '/views/return-form.php'; ?>
<?php endif; endif; ?>
<script src="../vendor/jsQR.js"></script>
<script src="../shared/qr-scanner.js"></script>
<script src="borrow-ui.js"></script>

<?php require __DIR__ . '/staff-foot.php'; ?>
