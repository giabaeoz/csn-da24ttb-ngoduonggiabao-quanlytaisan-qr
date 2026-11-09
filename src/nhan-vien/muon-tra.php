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

/* ---------- API JSON cho trang mượn/trả (tra cứu người mượn, quét QR, đăng ký nhanh) ---------- */
$ajax = is_string($_GET['ajax'] ?? null) ? $_GET['ajax'] : '';
if ($ajax !== '') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $reply = static function (array $data, int $status = 200): never {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    };
    if (!$canOperate) $reply(['error' => 'Tài khoản chưa được phân công phòng trực đang hoạt động.'], 403);
    try {
        if ($ajax === 'borrowers') {
            $reply(['items' => loan_borrower_search($db, is_string($_GET['q'] ?? null) ? $_GET['q'] : '', $deskId)]);
        }
        if ($ajax === 'overview') {
            $reply(loan_borrower_overview($db, loan_id($_GET['id'] ?? null), $deskId));
        }
        if ($ajax === 'lookup') {
            $room = filter_var($_GET['room'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $reply(loan_lookup($db, $deskId, is_string($_GET['code'] ?? null) ? $_GET['code'] : '', $room ?: null));
        }
        if ($ajax === 'quick_borrower' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!csrf_kiem_tra(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
                $reply(['error' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'], 403);
            }
            $reply(['item' => loan_borrower_quick_create($db, (int)$nguoiDung['id'], $_POST)]);
        }
        $reply(['error' => 'Yêu cầu không hợp lệ.'], 400);
    } catch (DomainException $ex) {
        $reply(['error' => $ex->getMessage()], 422);
    } catch (mysqli_sql_exception $ex) {
        error_log('[LOAN_API] ' . $ex->getMessage());
        $reply(['error' => (int)$ex->getCode() === 1062 ? 'Dữ liệu bị trùng với hồ sơ đã có.' : 'Không thể xử lý yêu cầu. Vui lòng thử lại.'], 500);
    }
}

$oldBorrower = is_scalar($_POST['nguoi_muon_id'] ?? null) ? (string)$_POST['nguoi_muon_id'] : '';
$oldRoom = is_scalar($_POST['phong_hoc_id'] ?? null) ? (string)$_POST['phong_hoc_id'] : '';
$oldDue = is_string($_POST['han_tra'] ?? null) ? $_POST['han_tra'] : '';
$oldNote = is_string($_POST['ghi_chu'] ?? null) ? $_POST['ghi_chu'] : '';
$oldAssets = is_array($_POST['tai_san_id'] ?? null) ? array_values(array_filter($_POST['tai_san_id'], 'is_scalar')) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_kiem_tra(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ. Vui lòng tải lại trang.');
    }
    // Mã gửi một lần: chặn gửi lặp (bấm nhiều lần / tải lại trang) tạo phiếu trùng.
    $nonce = is_string($_POST['form_nonce'] ?? null) ? $_POST['form_nonce'] : '';
    $nonces = is_array($_SESSION['_loan_nonces'] ?? null) ? $_SESSION['_loan_nonces'] : [];
    $nonceOk = $nonce !== '' && isset($nonces[$nonce]);
    unset($nonces[$nonce]);
    $_SESSION['_loan_nonces'] = $nonces;
    try {
        if (!$nonceOk) throw new DomainException('Biểu mẫu đã được gửi trước đó. Hãy kiểm tra lại danh sách phiếu trước khi thao tác tiếp.');
        if ($tab === 'muon') {
            $id = loan_create($db, (int)$nguoiDung['id'], $_POST);
            $code = (string)$db->execute_query('SELECT ma_phieu_muon FROM phieu_muon WHERE id = ?', [$id])->fetch_row()[0];
            set_flash('success', "Đã lập phiếu mượn $code thành công.");
            header('Location: muon-tra.php?tab=tra&id=' . $id, true, 303);
        } else {
            $loanId = loan_id($_POST['phieu_muon_id'] ?? null);
            $returnedAt = null;
            $open = loan_return($db, (int)$nguoiDung['id'], $_POST, $returnedAt);
            set_flash('success', $open ? "Đã nhận trả. Phiếu còn $open thiết bị chưa trả." : 'Đã nhận trả đầy đủ và đóng phiếu.');
            header('Location: muon-tra.php?' . http_build_query(['tab' => 'tra', 'id' => $loanId, 'tra' => $returnedAt]), true, 303);
        }
        exit;
    } catch (DomainException $ex) {
        $error = $ex->getMessage();
    } catch (mysqli_sql_exception $ex) {
        error_log('[LOAN_DB] ' . $ex->getMessage());
        $error = 'Không thể lưu phiếu vì dữ liệu đã thay đổi hoặc trùng lặp. Vui lòng tải lại và thử lại.';
    }
}

$formNonce = bin2hex(random_bytes(16));
$nonces = is_array($_SESSION['_loan_nonces'] ?? null) ? $_SESSION['_loan_nonces'] : [];
$nonces[$formNonce] = time();
$_SESSION['_loan_nonces'] = array_slice($nonces, -20, null, true);

$rooms = [];
$loans = [];
$availableAssets = [];
$selectedLoan = null;
$details = [];
$returnBatches = [];
$oldBorrowerRow = null;
$oldAssetRows = [];
$bq = '';
$q = '';
$scanCode = '';
$justReturned = '';

if ($canOperate) {
    $rooms = $db->execute_query('SELECT id, ma_phong, ten_phong FROM phong_hoc WHERE dang_hoat_dong = 1 ORDER BY ma_phong')->fetch_all(MYSQLI_ASSOC);

    if ($tab === 'muon') {
        $availableAssets = $db->execute_query(
            "SELECT t.id, t.ma_tai_san, t.ten_tai_san, l.ten_loai, t.ma_qr
             FROM tai_san t
             JOIN loai_tai_san l ON l.id = t.loai_tai_san_id
             WHERE t.phong_truc_id = ? AND t.trang_thai = 'SAN_SANG' AND t.tinh_trang = 'TOT' AND t.phong_hoc_hien_tai_id IS NULL
             ORDER BY t.ma_tai_san",
            [$deskId]
        )->fetch_all(MYSQLI_ASSOC);
        $bq = is_string($_GET['nguoi_muon'] ?? null) ? mb_substr(trim($_GET['nguoi_muon']), 0, 100) : '';
        $borrowerKey = filter_var($oldBorrower !== '' ? $oldBorrower : ($_GET['nguoi_muon_id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($borrowerKey) {
            $oldBorrower = (string)$borrowerKey;
            $oldBorrowerRow = $db->execute_query('SELECT id, ma_so_truong, ho_ten, loai_nguoi_muon, lop_don_vi, so_dien_thoai FROM nguoi_muon WHERE id = ?', [$borrowerKey])->fetch_assoc();
        }
        $assetIds = array_values(array_filter(array_map(static fn($v) => filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]), $oldAssets)));
        if ($assetIds) {
            $marks = implode(',', array_fill(0, count($assetIds), '?'));
            $oldAssetRows = $db->execute_query("SELECT t.id, t.ma_tai_san, t.ten_tai_san, l.ten_loai FROM tai_san t JOIN loai_tai_san l ON l.id = t.loai_tai_san_id WHERE t.id IN ($marks) AND t.phong_truc_id = ?", [...$assetIds, $deskId])->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        $q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
        $scanCode = is_string($_GET['scan'] ?? null) ? mb_substr(trim($_GET['scan']), 0, 100) : '';
        $justReturned = is_string($_GET['tra'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_GET['tra']) ? $_GET['tra'] : '';
        $pattern = borrower_pattern($q);
        $loans = $db->execute_query(
            "SELECT p.id, p.ma_phieu_muon, p.han_tra, p.trang_thai, p.han_tra < NOW() AS qua_han, n.ho_ten, n.ma_so_truong, h.ma_phong, COUNT(c.id) AS so_chua_tra
             FROM phieu_muon p
             JOIN nguoi_muon n ON n.id = p.nguoi_muon_id
             JOIN phong_hoc h ON h.id = p.phong_hoc_id
             JOIN chi_tiet_muon c ON c.phieu_muon_id = p.id AND c.thoi_gian_tra IS NULL
             WHERE p.phong_truc_id = ?
             AND (p.ma_phieu_muon LIKE ? ESCAPE '!' OR n.ma_so_truong LIKE ? ESCAPE '!' OR n.ho_ten LIKE ? ESCAPE '!' OR n.so_dien_thoai LIKE ? ESCAPE '!'
                  OR EXISTS (
                      SELECT 1 FROM chi_tiet_muon cc JOIN tai_san t ON t.id = cc.tai_san_id
                      WHERE cc.phieu_muon_id = p.id AND cc.thoi_gian_tra IS NULL
                      AND (t.ma_tai_san LIKE ? ESCAPE '!' OR t.ma_qr LIKE ? ESCAPE '!')
                  ))
             GROUP BY p.id
             ORDER BY p.han_tra, p.id LIMIT 50",
            [$deskId, $pattern, $pattern, $pattern, $pattern, $pattern, $pattern]
        )->fetch_all(MYSQLI_ASSOC);

        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id && $q !== '') {
            // Quét QR tài sản hoặc nhập đúng mã phiếu: tự xác định giao dịch đang mượn.
            $match = $db->execute_query(
                'SELECT p.id FROM chi_tiet_muon c
                 JOIN phieu_muon p ON p.id = c.phieu_muon_id
                 JOIN tai_san t ON t.id = c.tai_san_id
                 WHERE p.phong_truc_id = ? AND c.thoi_gian_tra IS NULL AND (t.ma_qr = ? OR t.ma_tai_san = ? OR t.ma_qr = ?)
                 LIMIT 1',
                [$deskId, $q, $q, 'TVU:' . $q]
            )->fetch_row();
            if ($match) {
                $scanCode = $q;
            } else {
                $match = $db->execute_query('SELECT id FROM phieu_muon WHERE phong_truc_id = ? AND ma_phieu_muon = ?', [$deskId, $q])->fetch_row();
            }
            if (!$match && count($loans) === 1) $match = [(int)$loans[0]['id']];
            $id = $match ? (int)$match[0] : null;
        }
        if ($id) {
            $selectedLoan = $db->execute_query(
                'SELECT p.*, p.han_tra < NOW() AS qua_han, n.ma_so_truong, n.ho_ten, n.so_dien_thoai, n.lop_don_vi, h.ma_phong, nv.ho_ten AS nhan_vien_lap
                 FROM phieu_muon p
                 JOIN nguoi_muon n ON n.id = p.nguoi_muon_id
                 JOIN phong_hoc h ON h.id = p.phong_hoc_id
                 JOIN nhan_vien nv ON nv.id = p.nhan_vien_lap_id
                 WHERE p.id = ? AND p.phong_truc_id = ?',
                [(int)$id, $deskId]
            )->fetch_assoc();

            if ($selectedLoan) {
                $details = $db->execute_query(
                    'SELECT c.*, t.ma_tai_san, t.ma_qr, t.ten_tai_san
                     FROM chi_tiet_muon c
                     JOIN tai_san t ON t.id = c.tai_san_id
                     WHERE c.phieu_muon_id = ?
                     ORDER BY c.id',
                    [(int)$id]
                )->fetch_all(MYSQLI_ASSOC);
                $returnBatches = $db->execute_query(
                    'SELECT c.thoi_gian_tra, COUNT(*) AS so_tb, SUM(c.tinh_trang_khi_tra = \'HU_HONG\') AS so_hong
                     FROM chi_tiet_muon c WHERE c.phieu_muon_id = ? AND c.thoi_gian_tra IS NOT NULL
                     GROUP BY c.thoi_gian_tra ORDER BY c.thoi_gian_tra',
                    [(int)$id]
                )->fetch_all(MYSQLI_ASSOC);
            } elseif ($q === '') {
                $error = 'Không tìm thấy phiếu mượn trong phòng trực được phân công.';
            }
        }
        if (!$selectedLoan && $q !== '' && !$loans) {
            $error = 'Không tìm thấy phiếu đang mượn phù hợp với "' . $q . '".';
        }
    }
}

$staffPageCss = 'borrow.css';
require __DIR__ . '/staff-head.php';
?>
<div class="page-head">
    <h1>Mượn – trả tài sản</h1>
    <div class="staff-tabs">
        <a class="btn <?= $tab === 'muon' ? 'active' : '' ?>" href="muon-tra.php?tab=muon">Mượn</a>
        <a class="btn <?= $tab === 'tra' ? 'active' : '' ?>" href="muon-tra.php?tab=tra">Trả</a>
    </div>
</div>

<?php if (!$canOperate): ?>
    <div class="alert alert-warning">Tài khoản chưa được phân công phòng trực đang hoạt động. Vui lòng liên hệ Quản trị viên.</div>
<?php else: ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($tab === 'muon'): ?>
        <?php require __DIR__ . '/views/borrow-form.php'; ?>
    <?php else: ?>
        <?php require __DIR__ . '/views/return-form.php'; ?>
    <?php endif; ?>
<?php endif; ?>

<script src="../vendor/jsQR.js"></script>
<script src="../shared/qr-scanner.js"></script>
<script src="borrow-ui.js"></script>

<?php require __DIR__ . '/staff-foot.php'; ?>
