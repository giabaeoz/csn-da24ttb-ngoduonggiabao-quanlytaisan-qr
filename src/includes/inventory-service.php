<?php
declare(strict_types=1);
require_once __DIR__ . '/staff-scope.php';

const INVENTORY_TYPES = [
    'CUOI_BUOI' => 'Cuối buổi',
    'DINH_KY' => 'Định kỳ',
    'DOT_XUAT' => 'Đột xuất',
];
const INVENTORY_RESULTS = [
    'CHUA_KIEM_TRA' => 'Chưa kiểm',
    'TIM_THAY' => 'Có mặt',
    'CHUA_TIM_THAY' => 'Chưa tìm thấy',
];

function inventory_id(mixed $raw): int
{
    $id = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) throw new DomainException('Mã bản ghi không hợp lệ.');
    return (int)$id;
}

function inventory_text(mixed $raw, int $max, bool $required = false): string
{
    if (!is_string($raw)) throw new DomainException('Nội dung biểu mẫu không hợp lệ.');
    $value = trim($raw);
    if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $max || str_contains($value, "\0")) {
        throw new DomainException("Vui lòng nhập nội dung hợp lệ, tối đa $max ký tự.");
    }
    return $value;
}

function inventory_start(mysqli $db, int $staffId, array $input): int
{
    $name = inventory_text($input['ten_dot_kiem_ke'] ?? null, 150, true);
    $type = $input['loai_kiem_ke'] ?? null;
    if (!is_string($type) || !isset(INVENTORY_TYPES[$type])) throw new DomainException('Loại kiểm kê không hợp lệ.');
    $note = inventory_text($input['ghi_chu'] ?? '', 2000);
    $db->begin_transaction();
    try {
        $deskId = staff_assigned_desk($db, $staffId, true);
        if ($db->execute_query("SELECT id FROM dot_kiem_ke WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE' LIMIT 1", [$deskId])->fetch_row()) {
            throw new DomainException('Phòng trực đã có đợt kiểm kê đang thực hiện.');
        }
        $assets = $db->execute_query("SELECT id, tinh_trang, trang_thai, phong_hoc_hien_tai_id FROM tai_san WHERE phong_truc_id = ? AND trang_thai <> 'NGUNG_SU_DUNG' ORDER BY id FOR UPDATE", [$deskId])->fetch_all(MYSQLI_ASSOC);
        if (!$assets) throw new DomainException('Phòng trực chưa có tài sản đang sử dụng để kiểm kê.');
        $borrowed = [];
        foreach ($db->execute_query('SELECT c.tai_san_id FROM chi_tiet_muon c JOIN tai_san t ON t.id = c.tai_san_id WHERE t.phong_truc_id = ? AND c.thoi_gian_tra IS NULL', [$deskId])->fetch_all(MYSQLI_ASSOC) as $row) {
            $borrowed[(int)$row['tai_san_id']] = true;
        }
        $code = 'KK-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $db->execute_query('INSERT INTO dot_kiem_ke (ma_dot_kiem_ke, ten_dot_kiem_ke, phong_truc_id, nhan_vien_lap_id, loai_kiem_ke, ghi_chu) VALUES (?, ?, ?, ?, ?, ?)',
            [$code, $name, $deskId, $staffId, $type, $note ?: null]);
        $roundId = (int)$db->insert_id;
        foreach ($assets as $asset) {
            $state = isset($borrowed[(int)$asset['id']]) ? 'DANG_MUON' : $asset['trang_thai'];
            $db->execute_query('INSERT INTO chi_tiet_kiem_ke (dot_kiem_ke_id, tai_san_id, tinh_trang_so_sach, trang_thai_so_sach, phong_hoc_so_sach_id) VALUES (?, ?, ?, ?, ?)',
                [$roundId, (int)$asset['id'], $asset['tinh_trang'], $state, $asset['phong_hoc_hien_tai_id']]);
        }
        $db->commit();
        return $roundId;
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}

function inventory_record(mysqli $db, int $staffId, array $input): int
{
    $roundId = inventory_id($input['dot_kiem_ke_id'] ?? null);
    $detailId = inventory_id($input['chi_tiet_kiem_ke_id'] ?? null);
    $result = $input['ket_qua'] ?? null;
    if (!in_array($result, ['TIM_THAY', 'CHUA_TIM_THAY'], true)) throw new DomainException('Kết quả đối chiếu không hợp lệ.');
    $note = inventory_text($input['ghi_chu'] ?? '', 2000, $result === 'CHUA_TIM_THAY');
    $condition = null;
    $roomId = null;
    if ($result === 'TIM_THAY') {
        $condition = $input['tinh_trang_thuc_te'] ?? null;
        if (!in_array($condition, ['TOT', 'HU_HONG'], true)) throw new DomainException('Hãy chọn tình trạng thực tế.');
        if ($condition === 'HU_HONG' && $note === '') throw new DomainException('Hãy mô tả hư hỏng.');
        if (($input['phong_hoc_thuc_te_id'] ?? '') !== '') $roomId = inventory_id($input['phong_hoc_thuc_te_id']);
    }

    $db->begin_transaction();
    try {
        $deskId = staff_assigned_desk($db, $staffId);
        $round = $db->execute_query('SELECT id, trang_thai FROM dot_kiem_ke WHERE id = ? AND phong_truc_id = ? FOR UPDATE', [$roundId, $deskId])->fetch_assoc();
        if (!$round) throw new DomainException('Không tìm thấy đợt kiểm kê trong phòng trực của bạn.');
        if ($round['trang_thai'] !== 'DANG_KIEM_KE') throw new DomainException('Đợt đã hoàn tất, không thể sửa kết quả.');
        $detail = $db->execute_query('SELECT c.id, c.trang_thai_so_sach, t.phong_truc_id, t.id AS tai_san_id FROM chi_tiet_kiem_ke c JOIN tai_san t ON t.id = c.tai_san_id WHERE c.id = ? AND c.dot_kiem_ke_id = ? FOR UPDATE', [$detailId, $roundId])->fetch_assoc();
        if (!$detail || (int)$detail['phong_truc_id'] !== $deskId) throw new DomainException('Tài sản không thuộc phạm vi đợt kiểm kê.');
        if ($detail['trang_thai_so_sach'] === 'DANG_MUON') throw new DomainException('Tài sản đang mượn lúc mở đợt, không kiểm tra trực tiếp trong đợt này.');
        if ($db->execute_query('SELECT id FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL LIMIT 1', [(int)$detail['tai_san_id']])->fetch_row()) {
            throw new DomainException('Tài sản hiện đang mượn, không thể ghi nhận kiểm kê.');
        }
        if ($roomId && !$db->execute_query('SELECT id FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1 FOR SHARE', [$roomId])->fetch_row()) {
            throw new DomainException('Phòng học thực tế không hoạt động.');
        }
        $db->execute_query('UPDATE chi_tiet_kiem_ke SET ket_qua = ?, tinh_trang_thuc_te = ?, phong_hoc_thuc_te_id = ?, thoi_gian_kiem_tra = NOW(), ghi_chu = ? WHERE id = ?',
            [$result, $condition, $roomId, $note ?: null, $detailId]);
        $db->commit();
        return $roundId;
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}

function inventory_finish(mysqli $db, int $staffId, int $roundId): array
{
    $db->begin_transaction();
    try {
        $deskId = staff_assigned_desk($db, $staffId);
        $round = $db->execute_query('SELECT id, ma_dot_kiem_ke, trang_thai FROM dot_kiem_ke WHERE id = ? AND phong_truc_id = ? FOR UPDATE', [$roundId, $deskId])->fetch_assoc();
        if (!$round) throw new DomainException('Không tìm thấy đợt kiểm kê trong phòng trực của bạn.');
        if ($round['trang_thai'] !== 'DANG_KIEM_KE') throw new DomainException('Đợt kiểm kê đã hoàn tất.');
        $details = $db->execute_query('SELECT c.id, c.tai_san_id, c.ket_qua, c.tinh_trang_so_sach, c.trang_thai_so_sach, c.phong_hoc_so_sach_id, c.tinh_trang_thuc_te, c.phong_hoc_thuc_te_id, c.ghi_chu, t.phong_truc_id, t.tinh_trang, t.trang_thai, t.phong_hoc_hien_tai_id FROM chi_tiet_kiem_ke c JOIN tai_san t ON t.id = c.tai_san_id WHERE c.dot_kiem_ke_id = ? ORDER BY c.tai_san_id FOR UPDATE', [$roundId])->fetch_all(MYSQLI_ASSOC);
        $incidents = 0;
        foreach ($details as $detail) {
            if ($detail['trang_thai_so_sach'] === 'DANG_MUON') continue;
            if ($detail['ket_qua'] === 'CHUA_KIEM_TRA') throw new DomainException('Còn tài sản chưa đối chiếu. Hãy ghi nhận có mặt hoặc chưa tìm thấy.');
            if ((int)$detail['phong_truc_id'] !== $deskId || $detail['tinh_trang'] !== $detail['tinh_trang_so_sach']
                || $detail['trang_thai'] !== $detail['trang_thai_so_sach']
                || $detail['phong_hoc_hien_tai_id'] !== $detail['phong_hoc_so_sach_id']) {
                throw new DomainException('Hồ sơ tài sản đã thay đổi trong lúc kiểm kê. Hãy liên hệ Quản lý trước khi hoàn tất.');
            }
            if ($detail['ket_qua'] === 'CHUA_TIM_THAY') {
                inventory_incident($db, $staffId, $detail, 'MAT', 'Chưa tìm thấy trong ' . $round['ma_dot_kiem_ke'] . '. ' . $detail['ghi_chu']);
                $db->execute_query("UPDATE tai_san SET trang_thai = 'NGUNG_SU_DUNG' WHERE id = ?", [(int)$detail['tai_san_id']]);
                $incidents++;
                continue;
            }
            $wrongRoom = $detail['phong_hoc_thuc_te_id'] !== $detail['phong_hoc_so_sach_id'];
            if ($detail['tinh_trang_thuc_te'] === 'HU_HONG' && $detail['tinh_trang_so_sach'] !== 'HU_HONG') {
                inventory_incident($db, $staffId, $detail, 'HU_HONG', 'Phát hiện hư hỏng trong ' . $round['ma_dot_kiem_ke'] . '. ' . $detail['ghi_chu']);
                $db->execute_query("UPDATE tai_san SET tinh_trang = 'HU_HONG', trang_thai = 'BAO_TRI' WHERE id = ?", [(int)$detail['tai_san_id']]);
                $incidents++;
            }
            if ($wrongRoom) {
                inventory_incident($db, $staffId, $detail, 'SAI_VI_TRI', 'Vị trí thực tế khác sổ sách trong ' . $round['ma_dot_kiem_ke'] . '. ' . ($detail['ghi_chu'] ?: 'Cần kiểm tra lại vị trí.'));
                $db->execute_query("UPDATE tai_san SET trang_thai = 'NGUNG_SU_DUNG' WHERE id = ?", [(int)$detail['tai_san_id']]);
                $incidents++;
            }
        }
        $db->execute_query("UPDATE dot_kiem_ke SET trang_thai = 'HOAN_TAT', thoi_gian_ket_thuc = NOW() WHERE id = ?", [$roundId]);
        $db->commit();
        return ['incidents' => $incidents, 'assets' => count($details)];
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}

function inventory_cancel(mysqli $db, int $staffId, int $roundId, mixed $rawReason): int
{
    $reason = inventory_text($rawReason, 500, true);

    $db->begin_transaction();
    try {
        $deskId = staff_assigned_desk($db, $staffId, true);
        $round = $db->execute_query(
            'SELECT id, trang_thai FROM dot_kiem_ke WHERE id = ? AND phong_truc_id = ? FOR UPDATE',
            [$roundId, $deskId]
        )->fetch_assoc();
        if (!$round) {
            throw new DomainException('Không tìm thấy đợt kiểm kê trong phòng trực của bạn.');
        }
        if ($round['trang_thai'] !== 'DANG_KIEM_KE') {
            throw new DomainException('Chỉ có thể hủy đợt kiểm kê đang thực hiện.');
        }

        $db->execute_query(
            "UPDATE dot_kiem_ke
             SET trang_thai = 'DA_HUY', thoi_gian_ket_thuc = NOW(), ly_do_huy = ?, nhan_vien_huy_id = ?
             WHERE id = ?",
            [$reason, $staffId, $roundId]
        );
        $db->commit();
        return $roundId;
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}

function inventory_incident(mysqli $db, int $staffId, array $detail, string $type, string $description): void
{
    $code = 'SC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    $db->execute_query('INSERT INTO su_co_tai_san (ma_su_co, tai_san_id, nhan_vien_lap_id, chi_tiet_kiem_ke_id, loai_su_co, mo_ta) VALUES (?, ?, ?, ?, ?, ?)',
        [$code, (int)$detail['tai_san_id'], $staffId, (int)$detail['id'], $type, $description]);
}
