<?php
declare(strict_types=1);

require_once __DIR__ . '/staff-scope.php';

function transfer_id(mixed $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) throw new DomainException('Mã tham chiếu không hợp lệ.');
    return (int)$id;
}

function transfer_text(mixed $value, int $max, bool $required = false): string
{
    if (!is_string($value)) throw new DomainException('Nội dung biểu mẫu không hợp lệ.');
    $text = trim($value);
    if (($required && $text === '') || mb_strlen($text, 'UTF-8') > $max || str_contains($text, "\0")) {
        throw new DomainException("Nội dung phải hợp lệ và tối đa $max ký tự.");
    }
    return $text;
}

function transfer_asset_compatible(array $asset, array $destination): bool
{
    if ($asset['pham_vi_tuong_thich'] === 'LINH_HOAT') return true;
    if ($asset['pham_vi_tuong_thich'] === 'THEO_PHONG') {
        $codes = json_decode((string)$asset['phong_tuong_thich'], true);
        return is_array($codes) && in_array($destination['ma_phong'], $codes, true);
    }
    return (int)$asset['phong_hoc_phu_hop_id'] === (int)$destination['id'];
}

/**
 * Tạo phiếu điều chuyển tài sản giữa hai phòng học.
 * Phòng trực chỉ giới hạn tài sản Nhân viên được phép thao tác và không bị thay đổi.
 */
function transfer_create(mysqli $db, int $staffId, array $input): int
{
    $originId = transfer_id($input['phong_hoc_di_id'] ?? null);
    $destinationId = transfer_id($input['phong_hoc_den_id'] ?? null);
    if ($originId === $destinationId) throw new DomainException('Phòng đi và phòng đến phải khác nhau.');
    if (($input['xac_nhan_tuong_thich'] ?? '') !== '1') {
        throw new DomainException('Hãy xác nhận đã kiểm tra thiết bị phù hợp với phòng đến.');
    }
    $reason = transfer_text($input['ly_do'] ?? null, 2000, true);
    $note = transfer_text($input['ghi_chu'] ?? '', 2000);
    $rawAssets = $input['tai_san_id'] ?? null;
    if (!is_array($rawAssets) || !$rawAssets || count($rawAssets) > 30) {
        throw new DomainException('Chọn từ 1 đến 30 tài sản cần điều chuyển.');
    }
    $assetIds = array_map('transfer_id', $rawAssets);
    if (count(array_unique($assetIds)) !== count($assetIds)) {
        throw new DomainException('Một tài sản chỉ được chọn một lần.');
    }
    sort($assetIds, SORT_NUMERIC);

    $db->begin_transaction();
    try {
        $deskId = staff_assigned_desk($db, $staffId);
        if ($db->execute_query("SELECT id FROM dot_kiem_ke WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE' LIMIT 1", [$deskId])->fetch_row()) {
            throw new DomainException('Phòng trực đang kiểm kê. Hãy hoàn tất đợt trước khi điều chuyển tài sản.');
        }
        $origin = $db->execute_query('SELECT id, ma_phong FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1 FOR SHARE', [$originId])->fetch_assoc();
        $destination = $db->execute_query('SELECT id, ma_phong FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1 FOR SHARE', [$destinationId])->fetch_assoc();
        if (!$origin || !$destination) throw new DomainException('Phòng đi hoặc phòng đến không còn hoạt động.');

        foreach ($assetIds as $assetId) {
            $asset = $db->execute_query('SELECT id, ma_tai_san, phong_truc_id, phong_hoc_hien_tai_id,
                phong_hoc_phu_hop_id, pham_vi_tuong_thich, phong_tuong_thich, tinh_trang, trang_thai
                FROM tai_san WHERE id = ? FOR UPDATE', [$assetId])->fetch_assoc();
            if (!$asset || (int)$asset['phong_truc_id'] !== $deskId) {
                throw new DomainException("Tài sản #$assetId không thuộc phòng trực của bạn.");
            }
            if ((int)$asset['phong_hoc_hien_tai_id'] !== $originId) {
                throw new DomainException('Tài sản ' . $asset['ma_tai_san'] . ' không còn ở phòng học nguồn đã chọn.');
            }
            if ($asset['trang_thai'] !== 'SAN_SANG' || $asset['tinh_trang'] !== 'TOT') {
                throw new DomainException('Tài sản ' . $asset['ma_tai_san'] . ' không ở trạng thái tốt và sẵn sàng.');
            }
            if ($db->execute_query('SELECT id FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL LIMIT 1', [$assetId])->fetch_row()) {
                throw new DomainException('Tài sản ' . $asset['ma_tai_san'] . ' đang có phiếu mượn chưa trả.');
            }
            if (!transfer_asset_compatible($asset, $destination)) {
                throw new DomainException('Tài sản ' . $asset['ma_tai_san'] . ' không phù hợp phòng học ' . $destination['ma_phong'] . '.');
            }
        }

        $code = 'DC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
        $db->execute_query('INSERT INTO dieu_chuyen
            (ma_dieu_chuyen, phong_hoc_di_id, phong_hoc_den_id, nhan_vien_lap_id, ly_do, ghi_chu)
            VALUES (?, ?, ?, ?, ?, ?)', [$code, $originId, $destinationId, $staffId, $reason, $note ?: null]);
        $transferId = (int)$db->insert_id;
        foreach ($assetIds as $assetId) {
            $db->execute_query('INSERT INTO chi_tiet_dieu_chuyen (dieu_chuyen_id, tai_san_id) VALUES (?, ?)', [$transferId, $assetId]);
            $db->execute_query('UPDATE tai_san SET phong_hoc_hien_tai_id = ? WHERE id = ?', [$destinationId, $assetId]);
        }
        $db->commit();
        return $transferId;
    } catch (Throwable $error) {
        $db->rollback();
        throw $error;
    }
}
