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

/** Lý do tài sản không được cho mượn (null = hợp lệ). $room = null khi chưa xác định phòng. */
function loan_asset_problem(array $asset, ?array $room, int $desk): ?string
{
    $code = (string)($asset['ma_tai_san'] ?? ('#' . $asset['id']));
    if ((int)$asset['phong_truc_id'] !== $desk) return "Tài sản $code không thuộc phòng trực của bạn.";
    if ($asset['trang_thai'] === 'DANG_MUON') return "Tài sản $code đang có phiếu mượn chưa trả.";
    if ($asset['phong_hoc_hien_tai_id'] !== null) return "Tài sản $code đang ở phòng học khác, chưa thu hồi về phòng trực.";
    if ($asset['tinh_trang'] === 'HU_HONG') return "Tài sản $code đang hư hỏng.";
    if ($asset['tinh_trang'] === 'MAT') return "Tài sản $code đã ghi nhận mất.";
    if ($asset['trang_thai'] === 'BAO_TRI') return "Tài sản $code đang bảo trì.";
    if ($asset['trang_thai'] !== 'SAN_SANG') return "Tài sản $code đã ngừng sử dụng.";
    if ($room !== null && !loan_compatible($asset, $room)) return "Tài sản $code không phù hợp phòng {$room['ma_phong']}.";
    return null;
}

/** Nội dung QR phòng học: ổn định theo mã phòng, không chứa thông tin cá nhân. */
function room_qr_payload(string $roomCode): string
{
    return 'TVU:PHONG:' . $roomCode;
}

/**
 * Tra cứu mã quét/nhập thủ công trên MySQL: phòng học hoặc tài sản.
 * Trả về mảng mô tả, kèm 'ok' và 'message' cho tài sản.
 */
function loan_lookup(mysqli $db, int $desk, string $raw, ?int $roomId): array
{
    $raw = trim($raw);
    if ($raw === '' || mb_strlen($raw) > 100) throw new DomainException('Mã quét không hợp lệ.');
    $roomCode = null;
    if (stripos($raw, 'TVU:PHONG:') === 0) {
        $roomCode = substr($raw, 10);
    } else {
        $cleanNoPrefix = stripos($raw, 'TVU:') === 0 ? substr($raw, 4) : $raw;
        $asset = $db->execute_query(
            'SELECT t.id, t.ma_tai_san, t.ma_qr, t.ten_tai_san, t.phong_truc_id, t.phong_hoc_hien_tai_id, t.phong_hoc_phu_hop_id,
                    t.pham_vi_tuong_thich, t.phong_tuong_thich, t.tinh_trang, t.trang_thai, l.ten_loai
             FROM tai_san t JOIN loai_tai_san l ON l.id = t.loai_tai_san_id
             WHERE t.ma_qr = ? OR t.ma_tai_san = ? OR t.ma_qr = ? OR t.ma_tai_san = ? OR LOWER(t.ma_tai_san) = LOWER(?) LIMIT 1',
            [$raw, $raw, 'TVU:' . $cleanNoPrefix, $cleanNoPrefix, $cleanNoPrefix]
        )->fetch_assoc();
        if ($asset) {
            $room = $roomId ? $db->execute_query('SELECT id, ma_phong FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1', [$roomId])->fetch_assoc() : null;
            $problem = loan_asset_problem($asset, $room ?: null, $desk);
            $open = $db->execute_query(
                'SELECT p.id, p.ma_phieu_muon FROM chi_tiet_muon c JOIN phieu_muon p ON p.id = c.phieu_muon_id
                 WHERE c.tai_san_id = ? AND c.thoi_gian_tra IS NULL LIMIT 1', [(int)$asset['id']]
            )->fetch_assoc();
            if ($problem === null && $open) {
                $problem = "Tài sản {$asset['ma_tai_san']} đang có phiếu mượn chưa trả ({$open['ma_phieu_muon']}).";
            }
            $suggestedRoom = null;
            if ($asset['phong_hoc_phu_hop_id']) {
                $suggestedRoom = $db->execute_query('SELECT id, ma_phong, ten_phong FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1', [(int)$asset['phong_hoc_phu_hop_id']])->fetch_assoc();
            } elseif ($asset['pham_vi_tuong_thich'] === 'THEO_PHONG') {
                $compatCodes = json_decode((string)$asset['phong_tuong_thich'], true);
                if (is_array($compatCodes) && count($compatCodes) === 1) {
                    $suggestedRoom = $db->execute_query('SELECT id, ma_phong, ten_phong FROM phong_hoc WHERE ma_phong = ? AND dang_hoat_dong = 1', [$compatCodes[0]])->fetch_assoc();
                }
            }
            return [
                'type' => 'asset', 'id' => (int)$asset['id'], 'ma_tai_san' => $asset['ma_tai_san'],
                'ten_tai_san' => $asset['ten_tai_san'], 'ten_loai' => $asset['ten_loai'],
                'ok' => $problem === null, 'message' => $problem ?? "Đã thêm {$asset['ma_tai_san']}.",
                'phieu_muon_id' => $open ? (int)$open['id'] : null,
                'ma_phieu_muon' => $open['ma_phieu_muon'] ?? null,
                'suggested_room' => $suggestedRoom,
            ];
        }
        $roomCode = $raw;
    }
    $room = $db->execute_query('SELECT id, ma_phong, ten_phong FROM phong_hoc WHERE ma_phong = ? AND dang_hoat_dong = 1', [$roomCode])->fetch_assoc();
    if (!$room) throw new DomainException('Không tìm thấy tài sản hoặc phòng học với mã "' . $raw . '".');
    return ['type' => 'room', 'id' => (int)$room['id'], 'ma_phong' => $room['ma_phong'], 'ten_phong' => $room['ten_phong']];
}

function loan_borrower_search(mysqli $db, string $keyword, int $desk): array
{
    $keyword = mb_substr(trim($keyword), 0, 100);
    if ($keyword === '') return [];
    $pattern = '%' . strtr($keyword, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    return $db->execute_query(
        "SELECT n.id, n.ma_so_truong, n.ho_ten, n.loai_nguoi_muon, n.lop_don_vi, n.so_dien_thoai,
                (SELECT COUNT(*) FROM chi_tiet_muon c JOIN phieu_muon p ON p.id = c.phieu_muon_id
                 WHERE p.nguoi_muon_id = n.id AND p.phong_truc_id = ? AND c.thoi_gian_tra IS NULL) AS dang_muon
         FROM nguoi_muon n
         WHERE n.ma_so_truong LIKE ? ESCAPE '!' OR n.ho_ten LIKE ? ESCAPE '!' OR n.so_dien_thoai LIKE ? ESCAPE '!' OR n.ma_nguoi_muon LIKE ? ESCAPE '!'
         ORDER BY (n.ma_so_truong = ? OR n.so_dien_thoai = ?) DESC, n.ho_ten LIMIT 10",
        [$desk, $pattern, $pattern, $pattern, $pattern, $keyword, $keyword]
    )->fetch_all(MYSQLI_ASSOC);
}

/** Nhân viên trực đăng ký nhanh người mượn (không cần tài khoản đăng nhập). */
function loan_borrower_quick_create(mysqli $db, int $staffId, array $input): array
{
    $type = is_string($input['loai_nguoi_muon'] ?? null) ? $input['loai_nguoi_muon'] : '';
    if (!in_array($type, ['SINH_VIEN', 'GIANG_VIEN'], true)) throw new DomainException('Hãy chọn Sinh viên hoặc Giảng viên.');
    $code = loan_text($input['ma_so_truong'] ?? '', 30);
    $name = loan_text($input['ho_ten'] ?? '', 100);
    $unit = loan_text($input['lop_don_vi'] ?? '', 100);
    $phone = preg_replace('/[\s.\-]/', '', loan_text($input['so_dien_thoai'] ?? '', 20));
    if ($code === '' || !preg_match('/^[\p{L}\p{N}._\-]+$/u', $code)) throw new DomainException('Mã số không hợp lệ.');
    if ($name === '') throw new DomainException('Vui lòng nhập họ tên.');
    if ($phone === '' || !preg_match('/^\+?\d{9,12}$/', $phone)) throw new DomainException('Số điện thoại không hợp lệ (9–12 chữ số).');

    $db->begin_transaction();
    try {
        staff_assigned_desk($db, $staffId);
        $dup = $db->execute_query('SELECT ma_so_truong, ho_ten, so_dien_thoai FROM nguoi_muon WHERE ma_so_truong = ? OR so_dien_thoai = ? LIMIT 1 FOR UPDATE', [$code, $phone])->fetch_assoc();
        if ($dup) {
            $field = $dup['ma_so_truong'] === $code ? 'Mã số' : 'Số điện thoại';
            throw new DomainException("$field đã thuộc người mượn {$dup['ho_ten']} ({$dup['ma_so_truong']}). Hãy chọn hồ sơ có sẵn.");
        }
        $key = 'NM-' . mb_substr($code, 0, 27);
        if ($db->execute_query('SELECT 1 FROM nguoi_muon WHERE ma_nguoi_muon = ?', [$key])->fetch_row()) {
            $key = 'NM-' . strtoupper(bin2hex(random_bytes(6)));
        }
        $db->execute_query('INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon, lop_don_vi, so_dien_thoai) VALUES (?, ?, ?, ?, ?, ?)',
            [$key, $code, $name, $type, $unit ?: null, $phone]);
        $id = (int)$db->insert_id;
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
    return ['id' => $id, 'ma_so_truong' => $code, 'ho_ten' => $name, 'loai_nguoi_muon' => $type,
        'lop_don_vi' => $unit, 'so_dien_thoai' => $phone, 'dang_muon' => 0];
}

/** Tài sản đang mượn và lịch sử giao dịch của người mượn tại phòng trực. */
function loan_borrower_overview(mysqli $db, int $borrowerId, int $desk): array
{
    $open = $db->execute_query(
        'SELECT t.ma_tai_san, t.ten_tai_san, p.ma_phieu_muon, p.id AS phieu_muon_id, p.han_tra, h.ma_phong, p.han_tra < NOW() AS qua_han
         FROM chi_tiet_muon c JOIN phieu_muon p ON p.id = c.phieu_muon_id JOIN tai_san t ON t.id = c.tai_san_id JOIN phong_hoc h ON h.id = p.phong_hoc_id
         WHERE p.nguoi_muon_id = ? AND p.phong_truc_id = ? AND c.thoi_gian_tra IS NULL ORDER BY p.han_tra',
        [$borrowerId, $desk]
    )->fetch_all(MYSQLI_ASSOC);
    $history = $db->execute_query(
        'SELECT p.id, p.ma_phieu_muon, p.thoi_gian_muon, p.trang_thai, h.ma_phong, COUNT(c.id) AS so_tb
         FROM phieu_muon p JOIN phong_hoc h ON h.id = p.phong_hoc_id JOIN chi_tiet_muon c ON c.phieu_muon_id = p.id
         WHERE p.nguoi_muon_id = ? AND p.phong_truc_id = ? GROUP BY p.id ORDER BY p.thoi_gian_muon DESC LIMIT 5',
        [$borrowerId, $desk]
    )->fetch_all(MYSQLI_ASSOC);
    return ['open' => $open, 'history' => $history];
}

function loan_create(mysqli $db, int $staffId, array $input): int
{
    $rawBorrowerId = filter_var($input['nguoi_muon_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $borrowerId = $rawBorrowerId !== false && $rawBorrowerId !== null ? (int)$rawBorrowerId : 0;
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

        // Tự động tìm hoặc tạo hồ sơ người mượn nếu nhập trực tiếp
        if ($borrowerId <= 0) {
            $code = loan_text($input['ma_so_truong'] ?? '', 30);
            $name = loan_text($input['ho_ten'] ?? '', 100);
            $phone = preg_replace('/[\s.\-]/', '', loan_text($input['so_dien_thoai'] ?? '', 20));
            $unit = loan_text($input['lop_don_vi'] ?? '', 100);
            $type = is_string($input['loai_nguoi_muon'] ?? null) && in_array($input['loai_nguoi_muon'], ['SINH_VIEN', 'GIANG_VIEN'], true) ? $input['loai_nguoi_muon'] : 'SINH_VIEN';
            if ($code === '' || $name === '') {
                throw new DomainException('Vui lòng nhập mã số và họ tên người mượn.');
            }
            $existing = $db->execute_query('SELECT id, ho_ten FROM nguoi_muon WHERE ma_so_truong = ? OR (so_dien_thoai = ? AND so_dien_thoai IS NOT NULL AND so_dien_thoai <> "") LIMIT 1 FOR UPDATE', [$code, $phone ?: null])->fetch_assoc();
            if ($existing) {
                $borrowerId = (int)$existing['id'];
            } else {
                $key = 'NM-' . mb_substr($code, 0, 27);
                if ($db->execute_query('SELECT 1 FROM nguoi_muon WHERE ma_nguoi_muon = ?', [$key])->fetch_row()) {
                    $key = 'NM-' . strtoupper(bin2hex(random_bytes(6)));
                }
                $db->execute_query('INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon, lop_don_vi, so_dien_thoai) VALUES (?, ?, ?, ?, ?, ?)',
                    [$key, $code, $name, $type, $unit ?: null, $phone ?: null]);
                $borrowerId = (int)$db->insert_id;
            }
        } else {
            if (!$db->execute_query('SELECT id FROM nguoi_muon WHERE id = ? FOR SHARE', [$borrowerId])->fetch_row()) {
                throw new DomainException('Người mượn không tồn tại. Hãy tìm lại hoặc đăng ký người mượn mới.');
            }
        }
        $room = $db->execute_query('SELECT id, ma_phong FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1 FOR SHARE', [$roomId])->fetch_assoc();
        if (!$room) throw new DomainException('Phòng học không còn hoạt động.');
        foreach ($assetIds as $assetId) {
            $asset = $db->execute_query('SELECT id, ma_tai_san, phong_truc_id, phong_hoc_hien_tai_id, phong_hoc_phu_hop_id, pham_vi_tuong_thich, phong_tuong_thich, tinh_trang, trang_thai FROM tai_san WHERE id = ? FOR UPDATE', [$assetId])->fetch_assoc();
            if (!$asset) throw new DomainException("Tài sản #$assetId không tồn tại.");
            $problem = loan_asset_problem($asset, $room, $desk);
            if ($problem !== null) throw new DomainException($problem);
            if ($db->execute_query('SELECT id FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL LIMIT 1', [$assetId])->fetch_row()) {
                throw new DomainException("Tài sản {$asset['ma_tai_san']} đang có phiếu mượn chưa trả.");
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

function loan_return(mysqli $db, int $staffId, array $input, ?string &$returnedAt = null): int
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
        // Một thời điểm chung cho cả lần trả để nhóm chứng từ trả.
        $now = (string)$db->query('SELECT NOW()')->fetch_row()[0];
        foreach ($ids as $id) {
            $detail = $db->execute_query('SELECT id, tai_san_id, thoi_gian_tra FROM chi_tiet_muon WHERE id = ? AND phieu_muon_id = ? FOR UPDATE', [$id, $loanId])->fetch_assoc();
            if (!$detail || $detail['thoi_gian_tra'] !== null) throw new DomainException("Thiết bị #$id không còn chờ trả.");
            $condition = $conditions[$id] ?? null;
            if (!in_array($condition, ['TOT', 'HU_HONG'], true)) throw new DomainException("Chọn tình trạng khi nhận thiết bị #$id.");
            $note = loan_text($notes[$id] ?? '', 2000);
            if ($condition === 'HU_HONG' && $note === '') throw new DomainException("Ghi rõ hư hỏng của thiết bị #$id.");
            $asset = $db->execute_query('SELECT phong_truc_id FROM tai_san WHERE id = ? FOR UPDATE', [(int)$detail['tai_san_id']])->fetch_assoc();
            if (!$asset || (int)$asset['phong_truc_id'] !== $desk) throw new DomainException('Tài sản đã chuyển khỏi phạm vi phòng trực.');
            $db->execute_query('UPDATE chi_tiet_muon SET thoi_gian_tra = ?, tinh_trang_khi_tra = ?, nhan_vien_nhan_id = ?, ghi_chu = ? WHERE id = ?', [$now, $condition, $staffId, $note ?: null, $id]);
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
        $returnedAt = $now;
        return $open;
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}
