<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/transfer-service.php';

$nguoiDung = kiem_tra_quyen(VAI_TRO_NHAN_VIEN);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$deskId = (int)($nguoiDung['phong_truc_id'] ?? 0);
$desk = $deskId ? $db->execute_query('SELECT id, ma_phong_truc, ten_phong_truc, dang_hoat_dong FROM phong_truc WHERE id = ?', [$deskId])->fetch_assoc() : null;
$canOperate = $desk && (int)$desk['dang_hoat_dong'] === 1;
$error = '';
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

if ($isPost) {
    if (!csrf_kiem_tra(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        exit('Phiên biểu mẫu không hợp lệ. Hãy tải lại trang.');
    }
    try {
        if (($_POST['action'] ?? '') !== 'create') throw new DomainException('Thao tác điều chuyển không hợp lệ.');
        $transferId = transfer_create($db, (int)$nguoiDung['id'], $_POST);
        set_flash('success', 'Đã điều chuyển tài sản giữa hai phòng học và cập nhật vị trí hiện tại.');
        header('Location: dieu-chuyen.php?id=' . $transferId, true, 303);
        exit;
    } catch (DomainException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    } catch (mysqli_sql_exception $exception) {
        error_log('[TRANSFER_DB] ' . $exception->getMessage());
        http_response_code(503);
        $error = 'Chưa lưu được phiếu vì dữ liệu đã thay đổi. Hãy tải lại trang và thử lại.';
    }
}

$rooms = [];
$assets = [];
$recent = [];
$selectedTransfer = null;
$selectedTransferAssets = [];
if ($canOperate) {
    $rooms = $db->query('SELECT id, ma_phong, ten_phong FROM phong_hoc WHERE dang_hoat_dong = 1 ORDER BY ma_phong')->fetch_all(MYSQLI_ASSOC);
    $assets = $db->execute_query("SELECT t.id, t.ma_tai_san, t.ma_qr, t.ten_tai_san, t.phong_hoc_hien_tai_id,
        t.phong_hoc_phu_hop_id, t.pham_vi_tuong_thich, t.phong_tuong_thich, t.tinh_trang,
        h.ma_phong AS phong_hien_tai
        FROM tai_san t JOIN phong_hoc h ON h.id = t.phong_hoc_hien_tai_id
        WHERE t.phong_truc_id = ? AND t.trang_thai = 'SAN_SANG' AND t.tinh_trang = 'TOT'
        AND NOT EXISTS (SELECT 1 FROM chi_tiet_muon c WHERE c.tai_san_id = t.id AND c.thoi_gian_tra IS NULL)
        ORDER BY h.ma_phong, t.ma_tai_san LIMIT 500", [$deskId])->fetch_all(MYSQLI_ASSOC);
    foreach ($assets as &$asset) {
        if ($asset['pham_vi_tuong_thich'] === 'LINH_HOAT') $asset['compatibility_tokens'] = '*';
        elseif ($asset['pham_vi_tuong_thich'] === 'THEO_PHONG') {
            $codes = json_decode((string)$asset['phong_tuong_thich'], true);
            $asset['compatibility_tokens'] = is_array($codes) ? implode('|', $codes) : '';
        } else $asset['compatibility_tokens'] = '#' . (int)$asset['phong_hoc_phu_hop_id'];
    }
    unset($asset);
    $recent = $db->execute_query('SELECT d.id, d.ma_dieu_chuyen, d.thoi_gian_dieu_chuyen,
        a.ma_phong AS phong_di, b.ma_phong AS phong_den, COUNT(c.id) AS asset_count
        FROM dieu_chuyen d JOIN phong_hoc a ON a.id = d.phong_hoc_di_id
        JOIN phong_hoc b ON b.id = d.phong_hoc_den_id
        JOIN chi_tiet_dieu_chuyen c ON c.dieu_chuyen_id = d.id
        JOIN tai_san t ON t.id = c.tai_san_id
        WHERE t.phong_truc_id = ? GROUP BY d.id ORDER BY d.thoi_gian_dieu_chuyen DESC, d.id DESC LIMIT 20', [$deskId])->fetch_all(MYSQLI_ASSOC);
    $requestedId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($requestedId) {
        $selectedTransfer = $db->execute_query('SELECT d.*, a.ma_phong AS phong_di, a.ten_phong AS ten_phong_di,
            b.ma_phong AS phong_den, b.ten_phong AS ten_phong_den, n.ho_ten AS nguoi_lap
            FROM dieu_chuyen d JOIN phong_hoc a ON a.id = d.phong_hoc_di_id
            JOIN phong_hoc b ON b.id = d.phong_hoc_den_id JOIN nhan_vien n ON n.id = d.nhan_vien_lap_id
            WHERE d.id = ? AND EXISTS (SELECT 1 FROM chi_tiet_dieu_chuyen c JOIN tai_san t ON t.id = c.tai_san_id
                WHERE c.dieu_chuyen_id = d.id AND t.phong_truc_id = ?)', [(int)$requestedId, $deskId])->fetch_assoc();
        if (!$selectedTransfer) { http_response_code(404); exit('Không tìm thấy phiếu điều chuyển trong phạm vi phòng trực của bạn.'); }
        $selectedTransferAssets = $db->execute_query('SELECT t.ma_tai_san, t.ten_tai_san, c.ghi_chu
            FROM chi_tiet_dieu_chuyen c JOIN tai_san t ON t.id = c.tai_san_id
            WHERE c.dieu_chuyen_id = ? AND t.phong_truc_id = ? ORDER BY t.ma_tai_san', [(int)$requestedId, $deskId])->fetch_all(MYSQLI_ASSOC);
    }
}

$oldOrigin = is_scalar($_POST['phong_hoc_di_id'] ?? null) ? (string)$_POST['phong_hoc_di_id'] : '';
$oldDestination = is_scalar($_POST['phong_hoc_den_id'] ?? null) ? (string)$_POST['phong_hoc_den_id'] : '';
$oldReason = is_string($_POST['ly_do'] ?? null) ? $_POST['ly_do'] : '';
$oldNote = is_string($_POST['ghi_chu'] ?? null) ? $_POST['ghi_chu'] : '';
$oldAssets = is_array($_POST['tai_san_id'] ?? null) ? array_map('strval', array_filter($_POST['tai_san_id'], 'is_scalar')) : [];
$title = 'Điều chuyển tài sản';
$staffPageCss = 'transfer.css';
require __DIR__ . '/staff-head.php';
?>
<h1>Điều chuyển tài sản</h1>
<?php if (!$canOperate): ?>
    <div class="alert alert-warning">Tài khoản cần được gán một phòng trực đang hoạt động trước khi điều chuyển.</div>
<?php else: ?>
    <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php require __DIR__ . '/views/transfer-form.php'; ?>
    <?php if ($selectedTransfer): require __DIR__ . '/views/transfer-detail.php'; endif; ?>
    <?php require __DIR__ . '/views/transfer-recent.php'; ?>
<?php endif; ?>
<script src="../vendor/jsQR.js"></script>
<script src="../shared/qr-scanner.js"></script>
<script src="transfer.js"></script>
<?php require __DIR__ . '/staff-foot.php'; ?>
