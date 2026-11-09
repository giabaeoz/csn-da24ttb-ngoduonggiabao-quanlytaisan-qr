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

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$returnAt = is_string($_GET['tra'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $_GET['tra']) ? $_GET['tra'] : '';
if (!$id || !$deskId) {
    http_response_code(400);
    exit('Thiếu mã phiếu.');
}

$loan = $db->execute_query(
    'SELECT p.*, n.ma_so_truong, n.ho_ten, n.lop_don_vi, n.so_dien_thoai, n.loai_nguoi_muon,
            h.ma_phong, h.ten_phong, pt.ten_phong_truc, nv.ho_ten AS nhan_vien_lap
     FROM phieu_muon p
     JOIN nguoi_muon n ON n.id = p.nguoi_muon_id
     JOIN phong_hoc h ON h.id = p.phong_hoc_id
     JOIN phong_truc pt ON pt.id = p.phong_truc_id
     JOIN nhan_vien nv ON nv.id = p.nhan_vien_lap_id
     WHERE p.id = ? AND p.phong_truc_id = ?',
    [$id, $deskId]
)->fetch_assoc();
if (!$loan) {
    http_response_code(404);
    exit('Không tìm thấy phiếu trong phòng trực được phân công.');
}

$items = $db->execute_query(
    'SELECT c.*, t.ma_tai_san, t.ten_tai_san, l.ten_loai, nn.ho_ten AS nhan_vien_nhan
     FROM chi_tiet_muon c
     JOIN tai_san t ON t.id = c.tai_san_id
     JOIN loai_tai_san l ON l.id = t.loai_tai_san_id
     LEFT JOIN nhan_vien nn ON nn.id = c.nhan_vien_nhan_id
     WHERE c.phieu_muon_id = ? ' . ($returnAt !== '' ? 'AND c.thoi_gian_tra = ? ' : '') . 'ORDER BY c.id',
    $returnAt !== '' ? [$id, $returnAt] : [$id]
)->fetch_all(MYSQLI_ASSOC);
if ($returnAt !== '' && !$items) {
    http_response_code(404);
    exit('Không tìm thấy lần trả này.');
}

$isReturn = $returnAt !== '';
$remaining = $isReturn ? (int)$db->execute_query('SELECT COUNT(*) FROM chi_tiet_muon WHERE phieu_muon_id = ? AND (thoi_gian_tra IS NULL OR thoi_gian_tra > ?)', [$id, $returnAt])->fetch_row()[0] : 0;
$receiver = $isReturn ? ($items[0]['nhan_vien_nhan'] ?? '—') : '';
$fmt = static fn(?string $v): string => $v ? date('H:i d/m/Y', strtotime($v)) : '—';
$lateText = '';
if ($isReturn && strtotime($returnAt) > strtotime((string)$loan['han_tra'])) {
    $s = strtotime($returnAt) - strtotime((string)$loan['han_tra']);
    $h = intdiv($s, 3600);
    $lateText = $h >= 24 ? 'Quá hạn ' . intdiv($h, 24) . ' ngày ' . ($h % 24) . ' giờ' : 'Quá hạn ' . $h . ' giờ ' . intdiv($s % 3600, 60) . ' phút';
}
$docTitle = $isReturn ? 'CHỨNG TỪ TRẢ TÀI SẢN' : 'PHIẾU MƯỢN TÀI SẢN';
$typeLabel = ['SINH_VIEN' => 'Sinh viên', 'GIANG_VIEN' => 'Giảng viên'][$loan['loai_nguoi_muon']] ?? '';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="data:,">
    <title><?= e($docTitle . ' ' . $loan['ma_phieu_muon']) ?></title>
    <link rel="stylesheet" href="../shared/print-doc.css">
</head>
<body>
<div class="doc-toolbar">
    <button type="button" onclick="window.print()">In</button>
    <a href="muon-tra.php?tab=tra&id=<?= (int)$loan['id'] ?>">Quay lại</a>
</div>
<main class="doc">
    <header class="doc-head">
        <div><strong><?= e($loan['ten_phong_truc']) ?></strong></div>
        <div class="doc-code">Số: <?= e($loan['ma_phieu_muon']) ?></div>
    </header>
    <h1><?= e($docTitle) ?></h1>
    <table class="doc-info">
        <tr><th>Người mượn</th><td><?= e($loan['ho_ten']) ?> (<?= e($typeLabel) ?>)</td><th>Mã số</th><td><?= e($loan['ma_so_truong']) ?></td></tr>
        <tr><th>Lớp / đơn vị</th><td><?= e($loan['lop_don_vi'] ?? '—') ?></td><th>Số điện thoại</th><td><?= e($loan['so_dien_thoai'] ?? '—') ?></td></tr>
        <tr><th>Phòng học</th><td><?= e($loan['ma_phong'] . ' — ' . $loan['ten_phong']) ?></td><th>Nhân viên lập</th><td><?= e($loan['nhan_vien_lap']) ?></td></tr>
        <tr><th>Ngày mượn</th><td><?= $fmt($loan['thoi_gian_muon']) ?></td><th>Hạn trả</th><td><?= $fmt($loan['han_tra']) ?></td></tr>
        <?php if ($isReturn): ?>
            <tr><th>Thời gian trả</th><td><?= $fmt($returnAt) ?></td><th>Nhân viên tiếp nhận</th><td><?= e($receiver) ?></td></tr>
            <tr><th>Tình trạng hạn</th><td><?= e($lateText ?: 'Trong hạn') ?></td><th>Còn chưa trả</th><td><?= $remaining ?> thiết bị</td></tr>
        <?php else: ?>
            <tr><th>Trạng thái</th><td><?= $loan['trang_thai'] === 'DA_TRA' ? 'Đã trả' : 'Đang mượn' ?></td><th>Ghi chú</th><td><?= e($loan['ghi_chu'] ?? '') ?></td></tr>
        <?php endif; ?>
    </table>

    <table class="doc-items">
        <thead>
            <tr>
                <th>STT</th><th>Mã tài sản</th><th>Tên tài sản</th><th>Loại</th>
                <?php if ($isReturn): ?><th>Tình trạng khi trả</th><th>Ghi chú</th><?php else: ?><th>Tình trạng khi mượn</th><th>Đã trả</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $i => $it): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($it['ma_tai_san']) ?></td>
                    <td><?= e($it['ten_tai_san']) ?></td>
                    <td><?= e($it['ten_loai']) ?></td>
                    <?php if ($isReturn): ?>
                        <td><?= $it['tinh_trang_khi_tra'] === 'HU_HONG' ? 'Hư hỏng' : 'Bình thường' ?></td>
                        <td><?= e($it['ghi_chu'] ?? '') ?></td>
                    <?php else: ?>
                        <td><?= $it['tinh_trang_khi_muon'] === 'HU_HONG' ? 'Hư hỏng' : 'Bình thường' ?></td>
                        <td><?= $it['thoi_gian_tra'] ? $fmt($it['thoi_gian_tra']) : '—' ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="6">Tổng cộng: <?= count($items) ?> thiết bị</td></tr></tfoot>
    </table>

    <div class="doc-sign">
        <div><strong>Người <?= $isReturn ? 'trả' : 'mượn' ?></strong><span>(Ký, ghi rõ họ tên)</span><em><?= e($loan['ho_ten']) ?></em></div>
        <div><strong>Nhân viên <?= $isReturn ? 'tiếp nhận' : 'lập phiếu' ?></strong><span>(Ký, ghi rõ họ tên)</span><em><?= e($isReturn ? $receiver : $loan['nhan_vien_lap']) ?></em></div>
    </div>
</main>
</body>
</html>

