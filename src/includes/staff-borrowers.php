<?php
declare(strict_types=1);

require_once __DIR__ . '/staff-scope.php';
require_once __DIR__ . '/borrowers.php';

const STAFF_BORROWER_FIELDS = [
    'ma_so_truong' => ['Mã số trường', 30, true],
    'ho_ten' => ['Họ tên', 100, true],
    'lop_don_vi' => ['Lớp / đơn vị', 100, false],
    'so_dien_thoai' => ['Số điện thoại', 20, false],
];

function staff_borrower_can_edit(mysqli $db, int $borrowerId, int $deskId): bool
{
    return (bool)$db->execute_query(
        'SELECT id FROM phieu_muon WHERE nguoi_muon_id = ? AND phong_truc_id = ? LIMIT 1',
        [$borrowerId, $deskId]
    )->fetch_row();
}

function staff_borrower_update(mysqli $db, int $staffId, int $borrowerId, array $input): void
{
    if ($borrowerId < 1) throw new DomainException('Chưa chọn người mượn cần cập nhật.');
    $values = [];
    foreach (STAFF_BORROWER_FIELDS as $field => [$label, $max, $required]) {
        if (!is_string($input[$field] ?? null)) throw new DomainException("$label không hợp lệ.");
        $value = trim($input[$field]);
        if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $max || str_contains($value, "\0")) {
            throw new DomainException("$label phải hợp lệ và tối đa $max ký tự.");
        }
        $values[] = $value === '' ? null : $value;
    }

    $db->begin_transaction();
    try {
        $deskId = staff_assigned_desk($db, $staffId);
        $allowed = $db->execute_query(
            'SELECT n.id FROM nguoi_muon n WHERE n.id = ? AND EXISTS (
                SELECT 1 FROM phieu_muon p WHERE p.nguoi_muon_id = n.id AND p.phong_truc_id = ?
            ) FOR UPDATE', [$borrowerId, $deskId]
        )->fetch_row();
        if (!$allowed) {
            throw new DomainException('Chỉ cập nhật người mượn đã có phiếu tại phòng trực của bạn.');
        }
        $db->execute_query('UPDATE nguoi_muon SET ma_so_truong = ?, ho_ten = ?, lop_don_vi = ?, so_dien_thoai = ? WHERE id = ?',
            [...$values, $borrowerId]);
        $db->commit();
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}

function staff_borrower_summary(mysqli $db, int $deskId): array
{
    return $db->execute_query('SELECT COUNT(DISTINCT p.nguoi_muon_id) AS open_count,
        COUNT(DISTINCT CASE WHEN p.han_tra < NOW() THEN p.nguoi_muon_id END) AS late_count,
        COUNT(c.id) AS asset_count
        FROM phieu_muon p JOIN chi_tiet_muon c ON c.phieu_muon_id = p.id AND c.thoi_gian_tra IS NULL
        WHERE p.phong_truc_id = ?', [$deskId])->fetch_assoc();
}

function staff_borrower_loans(mysqli $db, int $borrowerId, int $deskId): array
{
    return $db->execute_query('SELECT p.id, p.ma_phieu_muon, p.thoi_gian_muon, p.han_tra, p.trang_thai,
        h.ma_phong, p.han_tra < NOW() AS overdue,
        (SELECT COUNT(*) FROM chi_tiet_muon c WHERE c.phieu_muon_id = p.id) AS asset_count,
        (SELECT COUNT(*) FROM chi_tiet_muon c WHERE c.phieu_muon_id = p.id AND c.thoi_gian_tra IS NULL) AS open_count
        FROM phieu_muon p JOIN phong_hoc h ON h.id = p.phong_hoc_id
        WHERE p.nguoi_muon_id = ? AND p.phong_truc_id = ?
        ORDER BY p.thoi_gian_muon DESC, p.id DESC LIMIT 20',
        [$borrowerId, $deskId])->fetch_all(MYSQLI_ASSOC);
}
