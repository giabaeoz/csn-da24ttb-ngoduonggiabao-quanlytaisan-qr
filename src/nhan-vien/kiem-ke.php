<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/inventory-service.php';

$nguoiDung = kiem_tra_quyen(VAI_TRO_NHAN_VIEN);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$title = 'Kiểm kê tài sản';
$staffPageCss = 'inventory.css';
$deskId = (int)($nguoiDung['phong_truc_id'] ?? 0);
$desk = $deskId ? $db->execute_query('SELECT id, ma_phong_truc, ten_phong_truc, dang_hoat_dong FROM phong_truc WHERE id = ?', [$deskId])->fetch_assoc() : null;
$canOperate = $desk && (int)$desk['dang_hoat_dong'] === 1;
$error = '';
$action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!csrf_kiem_tra(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        exit('Phiên biểu mẫu không hợp lệ. Hãy tải lại trang.');
    }
    try {
        if ($action === 'create') {
            $roundId = inventory_start($db, (int)$nguoiDung['id'], $_POST);
            set_flash('success', 'Đã mở đợt kiểm kê và chụp danh sách tài sản trong phòng trực.');
        } elseif ($action === 'record') {
            $roundId = inventory_record($db, (int)$nguoiDung['id'], $_POST);
            set_flash('success', 'Đã lưu kết quả đối chiếu tài sản.');
        } elseif ($action === 'finish') {
            $roundId = inventory_id($_POST['dot_kiem_ke_id'] ?? null);
            $result = inventory_finish($db, (int)$nguoiDung['id'], $roundId);
            set_flash('success', 'Đã hoàn tất đợt kiểm kê. Ghi nhận ' . $result['incidents'] . ' sự cố cần xử lý.');
        } elseif ($action === 'cancel') {
            $roundId = inventory_id($_POST['dot_kiem_ke_id'] ?? null);
            inventory_cancel($db, (int)$nguoiDung['id'], $roundId, $_POST['ly_do_huy'] ?? null);
            set_flash('success', 'Đã hủy đợt kiểm kê và lưu lý do hủy.');
        } else {
            throw new DomainException('Thao tác kiểm kê không hợp lệ.');
        }
        header('Location: kiem-ke.php?id=' . $roundId, true, 303);
        exit;
    } catch (DomainException $exception) {
        http_response_code(422);
        $error = $exception->getMessage();
    } catch (mysqli_sql_exception $exception) {
        error_log('[INVENTORY_DB] ' . $exception->getMessage());
        http_response_code(503);
        $error = 'Chưa lưu được kiểm kê vì dữ liệu đã thay đổi. Hãy tải lại trang và thử lại.';
    }
}

$rounds = [];
$round = null;
$details = [];
$visibleDetails = [];
$selectedDetail = null;
$rooms = [];
$counts = ['required' => 0, 'checked' => 0, 'remaining' => 0, 'borrowed' => 0, 'normal' => 0, 'damaged' => 0, 'missing' => 0];
$incidentCount = 0;
$keyword = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '';
$filter = is_string($_GET['result'] ?? null) ? $_GET['result'] : 'all';
if (!in_array($filter, ['all', 'found', 'missing', 'unchecked', 'borrowed'], true)) {
    $filter = 'all';
}

if ($canOperate) {
    $rounds = $db->execute_query(
        "SELECT d.id, d.ma_dot_kiem_ke, d.ten_dot_kiem_ke, d.loai_kiem_ke, d.trang_thai, d.thoi_gian_bat_dau, d.thoi_gian_ket_thuc, h.ten_phong_truc
         FROM dot_kiem_ke d
         JOIN phong_truc h ON h.id = d.phong_truc_id
         WHERE d.phong_truc_id = ?
         ORDER BY (d.trang_thai = 'DANG_KIEM_KE') DESC, d.id DESC LIMIT 20",
        [$deskId]
    )->fetch_all(MYSQLI_ASSOC);

    $requestedId = filter_var($_GET['id'] ?? ($_POST['dot_kiem_ke_id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($requestedId) {
        $round = $db->execute_query(
            'SELECT d.*, h.ten_phong_truc, h.ma_phong_truc, n.ho_ten AS nguoi_lap, x.ho_ten AS nguoi_huy
             FROM dot_kiem_ke d
             JOIN phong_truc h ON h.id = d.phong_truc_id
             JOIN nhan_vien n ON n.id = d.nhan_vien_lap_id
             LEFT JOIN nhan_vien x ON x.id = d.nhan_vien_huy_id
             WHERE d.id = ? AND d.phong_truc_id = ?',
            [(int)$requestedId, $deskId]
        )->fetch_assoc();
    } elseif (!isset($_GET['new'])) {
        foreach ($rounds as $item) {
            if ($item['trang_thai'] === 'DANG_KIEM_KE') {
                $round = $db->execute_query(
                    'SELECT d.*, h.ten_phong_truc, h.ma_phong_truc, n.ho_ten AS nguoi_lap, x.ho_ten AS nguoi_huy
                     FROM dot_kiem_ke d
                     JOIN phong_truc h ON h.id = d.phong_truc_id
                     JOIN nhan_vien n ON n.id = d.nhan_vien_lap_id
                     LEFT JOIN nhan_vien x ON x.id = d.nhan_vien_huy_id
                     WHERE d.id = ? AND d.phong_truc_id = ?',
                    [(int)$item['id'], $deskId]
                )->fetch_assoc();
                break;
            }
        }
    }

    if ($round) {
        $details = $db->execute_query(
            "SELECT c.*, t.ma_tai_san, t.ma_qr, t.ten_tai_san, h.ma_phong AS phong_so_sach, x.ma_phong AS phong_thuc_te, p.id AS phieu_muon_id, p.ma_phieu_muon
             FROM chi_tiet_kiem_ke c
             JOIN tai_san t ON t.id = c.tai_san_id
             LEFT JOIN phong_hoc h ON h.id = c.phong_hoc_so_sach_id
             LEFT JOIN phong_hoc x ON x.id = c.phong_hoc_thuc_te_id
             LEFT JOIN chi_tiet_muon m ON m.tai_san_id = t.id AND m.thoi_gian_tra IS NULL
             LEFT JOIN phieu_muon p ON p.id = m.phieu_muon_id
             WHERE c.dot_kiem_ke_id = ?
             ORDER BY t.ma_tai_san, c.id",
            [(int)$round['id']]
        )->fetch_all(MYSQLI_ASSOC);

        $rooms = $db->query('SELECT id, ma_phong, ten_phong FROM phong_hoc WHERE dang_hoat_dong = 1 ORDER BY ma_phong')->fetch_all(MYSQLI_ASSOC);

        foreach ($details as $detail) {
            if ($detail['trang_thai_so_sach'] === 'DANG_MUON') {
                $counts['borrowed']++;
            } else {
                $counts['required']++;
                if ($detail['ket_qua'] === 'CHUA_KIEM_TRA') {
                    $counts['remaining']++;
                } else {
                    $counts['checked']++;
                }
                if ($detail['ket_qua'] === 'CHUA_TIM_THAY') {
                    $counts['missing']++;
                } elseif ($detail['tinh_trang_thuc_te'] === 'HU_HONG') {
                    $counts['damaged']++;
                } elseif ($detail['ket_qua'] === 'TIM_THAY') {
                    $counts['normal']++;
                }
            }
            $haystack = mb_strtolower($detail['ma_tai_san'] . ' ' . $detail['ma_qr'] . ' ' . $detail['ten_tai_san'], 'UTF-8');
            if ($keyword !== '' && !str_contains($haystack, mb_strtolower($keyword, 'UTF-8'))) {
                continue;
            }
            $matches = match ($filter) {
                'found' => $detail['ket_qua'] === 'TIM_THAY',
                'missing' => $detail['ket_qua'] === 'CHUA_TIM_THAY',
                'unchecked' => $detail['ket_qua'] === 'CHUA_KIEM_TRA' && $detail['trang_thai_so_sach'] !== 'DANG_MUON',
                'borrowed' => $detail['trang_thai_so_sach'] === 'DANG_MUON',
                default => true,
            };
            if ($matches) {
                $visibleDetails[] = $detail;
            }
        }

        $scan = is_string($_GET['scan'] ?? null) ? trim($_GET['scan']) : '';
        $requestedDetail = filter_var($_GET['detail'] ?? ($_POST['chi_tiet_kiem_ke_id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        foreach ($details as $detail) {
            if (($scan !== '' && in_array($scan, [$detail['ma_qr'], $detail['ma_tai_san']], true))
                || ($requestedDetail && (int)$detail['id'] === (int)$requestedDetail)) {
                $selectedDetail = $detail;
                break;
            }
        }
        if ($round['trang_thai'] === 'HOAN_TAT') {
            $incidentCount = (int)$db->execute_query(
                'SELECT COUNT(*) FROM su_co_tai_san s
                 JOIN chi_tiet_kiem_ke c ON c.id = s.chi_tiet_kiem_ke_id
                 WHERE c.dot_kiem_ke_id = ?',
                [(int)$round['id']]
            )->fetch_row()[0];
        }
    }
}

$showCreate = isset($_GET['new']) || ($action === 'create' && $error !== '') || (!$round && !$rounds);

require __DIR__ . '/staff-head.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <h1 style="font-size: 26px; font-weight: 700; margin: 0; color: #111;">Kiểm kê tài sản</h1>
    <?php if ($canOperate && !$showCreate): ?>
        <a class="btn-sketch-gray" href="kiem-ke.php?new=1" style="padding: 8px 18px; font-size: 13px; font-weight: 500; text-decoration: none;">+ Tạo đợt kiểm kê</a>
    <?php endif; ?>
</div>

<?php if (!$canOperate): ?>
    <div class="alert alert-warning">Tài khoản chưa được phân công phòng trực đang hoạt động.</div>
<?php else: ?>
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($showCreate): ?>
        <?php require __DIR__ . '/views/inventory-create.php'; ?>
    <?php endif; ?>

    <?php if ($round): ?>
        <?php require __DIR__ . '/views/inventory-round.php'; ?>
    <?php endif; ?>

    <?php require __DIR__ . '/views/inventory-recent.php'; ?>
<?php endif; ?>

<script src="../vendor/jsQR.js"></script>
<script src="../shared/qr-scanner.js"></script>
<script src="inventory.js"></script>

<?php require __DIR__ . '/staff-foot.php'; ?>
