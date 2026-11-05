<?php
declare(strict_types=1);
require_once __DIR__ . '/staff-scope.php';

function loan_id(mixed $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        throw new DomainException('Mã tham chiếu không hợp lệ.');
    }
    return $id;
}

function loan_text(mixed $value, int $max): string
{
    if (!is_string($value) || mb_strlen(trim($value)) > $max) {
        throw new DomainException("Nội dung không hợp lệ hoặc vượt quá $max ký tự.");
    }
    return trim($value);
}

function loan_staff_desk(mysqli $db, int $staffId): int
{
    return staff_assigned_desk($db, $staffId);
}

function loan_compatible(array $asset, array $room): bool
{
    if ($asset['pham_vi_tuong_thich'] === 'LINH_HOAT') return true;
    if ($asset['pham_vi_tuong_thich'] === 'THEO_PHONG') {
        $codes = json_decode((string)$asset['phong_tuong_thich'], true);
        return is_array($codes) && in_array($room['ma_phong'], $codes, true);
    }
    return (int)$asset['phong_hoc_phu_hop_id'] === (int)$room['id'];
}

function loan_create(mysqli $db, int $staffId, array $input): int
{
    $borrowerId = loan_id($input['nguoi_muon_id'] ?? null);
    $roomId = loan_id($input['phong_hoc_id'] ?? null);
    $rawAssets = $input['tai_san_id'] ?? null;
    if (!is_array($rawAssets) || !$rawAssets || count($rawAssets) > 30) {
        throw new DomainException('Chọn từ 1 đến 30 tài sản.');
    }
    $assetIds = array_map('loan_id', $rawAssets);
    if (count(array_unique($assetIds)) !== count($assetIds)) {
        throw new DomainException('Một tài sản chỉ được chọn một lần.');
    }
    sort($assetIds, SORT_NUMERIC);
    $dueInput = loan_text($input['han_tra'] ?? null, 16);
    $due = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $dueInput);
    $databaseNow = $db->query('SELECT NOW()')->fetch_row()[0];
    if (!$due || $due->format('Y-m-d\TH:i') !== $dueInput || $due->format('Y-m-d H:i:s') <= $databaseNow) {
        throw new DomainException('Hạn trả phải là thời điểm hợp lệ trong tương lai.');
    }
    $note = loan_text($input['ghi_chu'] ?? '', 2000);
    $db->begin_transaction();
    try {
        $desk = loan_staff_desk($db, $staffId);
        if ($db->execute_query("SELECT id FROM dot_kiem_ke WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE' LIMIT 1", [$desk])->fetch_row()) {
            throw new DomainException('Phòng trực đang kiểm kê. Hãy hoàn tất đợt trước khi lập phiếu mượn mới.');
        }
        if (!$db->execute_query('SELECT id FROM nguoi_muon WHERE id = ? FOR SHARE', [$borrowerId])->fetch_row()) {
            throw new DomainException('Người mượn không tồn tại. Quản lý cần tạo hồ sơ trước.');
        }
        $room = $db->execute_query('SELECT id, ma_phong FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1 FOR SHARE', [$roomId])->fetch_assoc();
        if (!$room) throw new DomainException('Phòng học không còn hoạt động.');
        foreach ($assetIds as $assetId) {
            $asset = $db->execute_query('SELECT id, phong_truc_id, phong_hoc_hien_tai_id, phong_hoc_phu_hop_id, pham_vi_tuong_thich, phong_tuong_thich, tinh_trang, trang_thai FROM tai_san WHERE id = ? FOR UPDATE', [$assetId])->fetch_assoc();
            if (!$asset || (int)$asset['phong_truc_id'] !== $desk || $asset['trang_thai'] !== 'SAN_SANG' || $asset['tinh_trang'] !== 'TOT' || $asset['phong_hoc_hien_tai_id'] !== null) {
                throw new DomainException("Tài sản #$assetId không sẵn sàng tại phòng trực của bạn.");
            }
            if (!loan_compatible($asset, $room)) throw new DomainException("Tài sản #$assetId không phù hợp phòng học đã chọn.");
            if ($db->execute_query('SELECT id FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL LIMIT 1', [$assetId])->fetch_row()) {
                throw new DomainException("Tài sản #$assetId đang có phiếu mượn chưa trả.");
            }
        }
        $code = 'PM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $db->execute_query('INSERT INTO phieu_muon (ma_phieu_muon, nguoi_muon_id, phong_hoc_id, phong_truc_id, nhan_vien_lap_id, han_tra, ghi_chu) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$code, $borrowerId, $roomId, $desk, $staffId, $due->format('Y-m-d H:i:s'), $note ?: null]);
        $loanId = (int)$db->insert_id;
        foreach ($assetIds as $assetId) {
            $db->execute_query('INSERT INTO chi_tiet_muon (phieu_muon_id, tai_san_id, tinh_trang_khi_muon) VALUES (?, ?, \'TOT\')', [$loanId, $assetId]);
            $db->execute_query('UPDATE tai_san SET trang_thai = \'DANG_MUON\', phong_hoc_hien_tai_id = ? WHERE id = ?', [$roomId, $assetId]);
        }
        $db->commit();
        return $loanId;
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

function loan_return(mysqli $db, int $staffId, array $input): int
{
    $loanId = loan_id($input['phieu_muon_id'] ?? null);
    $selected = $input['chi_tiet_id'] ?? null;
    $conditions = $input['tinh_trang'] ?? null;
    $notes = $input['ghi_chu'] ?? [];
    if (!is_array($selected) || !$selected || !is_array($conditions) || !is_array($notes)) {
        throw new DomainException('Chọn ít nhất một thiết bị cần nhận trả.');
    }
    $ids = array_map('loan_id', $selected);
    if (count(array_unique($ids)) !== count($ids)) throw new DomainException('Thiết bị nhận trả bị lặp.');
    sort($ids, SORT_NUMERIC);
    $db->begin_transaction();
    try {
        $desk = loan_staff_desk($db, $staffId);
        $loan = $db->execute_query('SELECT id, phong_truc_id, trang_thai FROM phieu_muon WHERE id = ? FOR UPDATE', [$loanId])->fetch_assoc();
        if (!$loan || (int)$loan['phong_truc_id'] !== $desk) throw new DomainException('Không tìm thấy phiếu trong phòng trực được phân công.');
        if ($loan['trang_thai'] !== 'DANG_MUON') throw new DomainException('Phiếu này đã được trả đầy đủ.');
        foreach ($ids as $id) {
            $detail = $db->execute_query('SELECT id, tai_san_id, thoi_gian_tra FROM chi_tiet_muon WHERE id = ? AND phieu_muon_id = ? FOR UPDATE', [$id, $loanId])->fetch_assoc();
            if (!$detail || $detail['thoi_gian_tra'] !== null) throw new DomainException("Thiết bị #$id không còn chờ trả.");
            $condition = $conditions[$id] ?? null;
            if (!in_array($condition, ['TOT', 'HU_HONG'], true)) throw new DomainException("Chọn tình trạng khi nhận thiết bị #$id.");
            $note = loan_text($notes[$id] ?? '', 2000);
            if ($condition === 'HU_HONG' && $note === '') throw new DomainException("Ghi rõ hư hỏng của thiết bị #$id.");
            $asset = $db->execute_query('SELECT phong_truc_id FROM tai_san WHERE id = ? FOR UPDATE', [(int)$detail['tai_san_id']])->fetch_assoc();
            if (!$asset || (int)$asset['phong_truc_id'] !== $desk) throw new DomainException('Tài sản đã chuyển khỏi phạm vi phòng trực.');
            $db->execute_query('UPDATE chi_tiet_muon SET thoi_gian_tra = NOW(), tinh_trang_khi_tra = ?, ghi_chu = ? WHERE id = ?', [$condition, $note ?: null, $id]);
            $db->execute_query('UPDATE tai_san SET tinh_trang = ?, trang_thai = ?, phong_hoc_hien_tai_id = NULL WHERE id = ?',
                [$condition, $condition === 'TOT' ? 'SAN_SANG' : 'BAO_TRI', (int)$detail['tai_san_id']]);
            if ($condition === 'HU_HONG') {
                $code = 'SC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
                $db->execute_query('INSERT INTO su_co_tai_san (ma_su_co, tai_san_id, nhan_vien_lap_id, chi_tiet_muon_id, loai_su_co, mo_ta) VALUES (?, ?, ?, ?, \'HU_HONG\', ?)',
                    [$code, (int)$detail['tai_san_id'], $staffId, $id, $note]);
            }
        }
        $open = (int)$db->execute_query('SELECT COUNT(*) FROM chi_tiet_muon WHERE phieu_muon_id = ? AND thoi_gian_tra IS NULL', [$loanId])->fetch_row()[0];
        if ($open === 0) $db->execute_query('UPDATE phieu_muon SET trang_thai = \'DA_TRA\' WHERE id = ?', [$loanId]);
        $db->commit();
        return $open;
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}
