<?php
declare(strict_types=1);

/** Check the current staff assignment inside the caller's transaction. */
function staff_assigned_desk(mysqli $db, int $staffId, bool $lockDesk = false): int
{
    $staff = $db->execute_query(
        "SELECT phong_truc_id FROM nhan_vien WHERE id = ? AND vai_tro = 'NHAN_VIEN' AND dang_hoat_dong = 1 FOR SHARE",
        [$staffId]
    )->fetch_assoc();
    if (!$staff || !$staff['phong_truc_id']) {
        throw new DomainException('Tài khoản chưa được phân công phòng trực đang hoạt động.');
    }
    $deskId = (int)$staff['phong_truc_id'];
    $lock = $lockDesk ? 'FOR UPDATE' : 'FOR SHARE';
    $desk = $db->execute_query(
        "SELECT id FROM phong_truc WHERE id = ? AND dang_hoat_dong = 1 $lock",
        [$deskId]
    )->fetch_row();
    if (!$desk) throw new DomainException('Phòng trực được phân công không còn hoạt động.');
    return $deskId;
}
