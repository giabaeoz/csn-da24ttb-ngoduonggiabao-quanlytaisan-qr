<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') exit("Chỉ chạy bằng PHP CLI.\n");
require __DIR__ . '/../src/includes/database.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function demo_type(mysqli $db, string $code, string $name): int
{
    $row = $db->execute_query('SELECT id FROM loai_tai_san WHERE ma_loai = ? ORDER BY id LIMIT 1', [$code])->fetch_row();
    if ($row) {
        $db->execute_query('UPDATE loai_tai_san SET ten_loai = ?, dang_hoat_dong = 1 WHERE id = ?', [$name, (int)$row[0]]);
        return (int)$row[0];
    }
    $db->execute_query('INSERT INTO loai_tai_san (ma_loai, ten_loai) VALUES (?, ?)', [$code, $name]);
    return (int)$db->insert_id;
}

function room_references(mysqli $db, int $id): int
{
    $queries = [
        'SELECT COUNT(*) FROM tai_san WHERE phong_hoc_phu_hop_id = ? OR phong_hoc_hien_tai_id = ?',
        'SELECT COUNT(*) FROM phieu_muon WHERE phong_hoc_id = ?',
        'SELECT COUNT(*) FROM dieu_chuyen WHERE phong_hoc_di_id = ? OR phong_hoc_den_id = ?',
        'SELECT COUNT(*) FROM chi_tiet_kiem_ke WHERE phong_hoc_so_sach_id = ? OR phong_hoc_thuc_te_id = ?',
    ];
    $count = 0;
    foreach ($queries as $sql) {
        $params = substr_count($sql, '?') === 2 ? [$id, $id] : [$id];
        $count += (int)$db->execute_query($sql, $params)->fetch_row()[0];
    }
    return $count;
}

$db->begin_transaction();
try {
    $oldAssets = array_column($db->query('SELECT ma_tai_san FROM tai_san ORDER BY id')->fetch_all(MYSQLI_ASSOC), 'ma_tai_san');
    $desks = $db->query('SELECT id, ma_phong_truc, (SELECT COUNT(*) FROM tai_san t WHERE t.phong_truc_id = p.id) AS asset_count FROM phong_truc p ORDER BY id FOR UPDATE')->fetch_all(MYSQLI_ASSOC);
    if (count($desks) !== 2) throw new RuntimeException('Cần đúng hai phòng trực hiện có trước khi chuẩn hóa.');
    $k1 = null;
    foreach ($desks as $desk) {
        if (in_array($desk['ma_phong_truc'], ['PT_K1', 'PT_K1B'], true) || (int)$desk['asset_count'] > 0) {
            $k1 = $desk;
            break;
        }
    }
    if (!$k1) $k1 = $desks[0];
    $k2 = (int)$desks[0]['id'] === (int)$k1['id'] ? $desks[1] : $desks[0];
    $db->execute_query('UPDATE phong_truc SET ma_phong_truc = ?, ten_phong_truc = ?, vi_tri = ?, dang_hoat_dong = 1 WHERE id = ?',
        ['PT_K1', 'Phòng trực thiết bị Khu 1', 'Khu 1 (Khu B)', (int)$k1['id']]);
    $db->execute_query('UPDATE phong_truc SET ma_phong_truc = ?, ten_phong_truc = ?, vi_tri = ?, dang_hoat_dong = 1 WHERE id = ?',
        ['PT_K2', 'Phòng trực thiết bị Khu 2', 'Khu 2', (int)$k2['id']]);

    $roomPlan = [
        'B21.101' => ['B21', '1', []],
        'B21.203' => ['B21', '2', []],
        'B21.206' => ['B21', '2', []],
        'B21.207' => ['B21', '2', ['B21.2.7']],
        'B21.301' => ['B21', '3', []],
        'B21.302' => ['B21', '3', []],
        'B21.307' => ['B21', '3', []],
        'B71.101' => ['B71', '1', []],
    ];
    $roomIds = [];
    foreach ($roomPlan as $code => [$building, $floor, $aliases]) {
        $candidates = [$code, ...$aliases];
        $marks = implode(',', array_fill(0, count($candidates), '?'));
        $row = $db->execute_query("SELECT id, ma_phong FROM phong_hoc WHERE ma_phong IN ($marks) ORDER BY ma_phong = ? DESC, id LIMIT 1 FOR UPDATE", [...$candidates, $code])->fetch_assoc();
        if ($row) {
            $id = (int)$row['id'];
            $db->execute_query('UPDATE phong_hoc SET ma_phong = ?, ten_phong = ?, toa_nha = ?, tang = ?, dang_hoat_dong = 1 WHERE id = ?',
                [$code, 'Phòng học ' . $code, $building, $floor, $id]);
        } else {
            $db->execute_query('INSERT INTO phong_hoc (ma_phong, ten_phong, toa_nha, tang) VALUES (?, ?, ?, ?)',
                [$code, 'Phòng học ' . $code, $building, $floor]);
            $id = (int)$db->insert_id;
        }
        $roomIds[$code] = $id;
    }

    foreach ($db->query('SELECT id, phong_tuong_thich FROM tai_san')->fetch_all(MYSQLI_ASSOC) as $asset) {
        $codes = json_decode((string)$asset['phong_tuong_thich'], true);
        if (!is_array($codes)) continue;
        $normalized = array_values(array_unique(array_map(static fn(string $code): string => $code === 'B21.2.7' ? 'B21.207' : $code, $codes)));
        if ($normalized !== $codes) {
            $db->execute_query('UPDATE tai_san SET phong_tuong_thich = ? WHERE id = ?',
                [json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), (int)$asset['id']]);
        }
    }

    $wantedIds = array_values($roomIds);
    foreach ($db->query('SELECT id FROM phong_hoc ORDER BY id FOR UPDATE')->fetch_all(MYSQLI_ASSOC) as $room) {
        $id = (int)$room['id'];
        if (in_array($id, $wantedIds, true)) continue;
        if (room_references($db, $id) > 0) throw new RuntimeException("Phòng học id=$id còn lịch sử nghiệp vụ, không thể xóa an toàn.");
        $db->execute_query('DELETE FROM phong_hoc WHERE id = ?', [$id]);
    }

    $oc = $db->query("SELECT id FROM loai_tai_san WHERE ma_loai = 'OC' OR ten_loai IN ('Ô cắm','Ổ cắm') ORDER BY ma_loai = 'OC' DESC, id LIMIT 1 FOR UPDATE")->fetch_row();
    if ($oc) {
        $ocType = (int)$oc[0];
        $db->execute_query("UPDATE loai_tai_san SET ma_loai = 'OC', ten_loai = 'Ổ cắm', mo_ta = NULL, dang_hoat_dong = 1 WHERE id = ?", [$ocType]);
    } else {
        $ocType = demo_type($db, 'OC', 'Ổ cắm');
    }
    $newTypes = [
        'LTAY' => ['Loa tay', 'LTAY-001', 'Loa tay'],
        'CAPCD' => ['Cáp chuyển đổi', 'CAPCD-001', 'Cáp chuyển đổi'],
        'LVT' => ['Loa vi tính', 'LVT-001', 'Loa vi tính'],
    ];
    $assetPlan = [['OC-001', 'Ổ cắm đa năng', $ocType]];
    foreach ($newTypes as $typeCode => [$typeName, $assetCode, $assetName]) {
        $assetPlan[] = [$assetCode, $assetName, demo_type($db, $typeCode, $typeName)];
    }
    foreach ($assetPlan as [$code, $name, $typeId]) {
        $row = $db->execute_query('SELECT id FROM tai_san WHERE ma_tai_san = ? OR ma_qr = ? ORDER BY id LIMIT 1 FOR UPDATE', [$code, 'TVU:' . $code])->fetch_row();
        if ($row) {
            $db->execute_query('UPDATE tai_san SET ten_tai_san = ?, loai_tai_san_id = ? WHERE id = ?', [$name, $typeId, (int)$row[0]]);
            continue;
        }
        $db->execute_query("INSERT INTO tai_san
            (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, pham_vi_tuong_thich, phong_tuong_thich, tinh_trang, trang_thai, mo_ta)
            VALUES (?, ?, ?, ?, ?, 'LINH_HOAT', '[]', 'TOT', 'SAN_SANG', ?)",
            [$code, 'TVU:' . $code, $name, $typeId, (int)$k1['id'], 'Dữ liệu bổ sung theo yêu cầu người dùng cho hệ thống demo.']);
    }

    foreach ($oldAssets as $code) {
        if (!$db->execute_query('SELECT id FROM tai_san WHERE ma_tai_san = ?', [$code])->fetch_row()) {
            throw new RuntimeException('Tài sản hiện có bị thiếu sau chuẩn hóa: ' . $code);
        }
    }
    if ((int)$db->query('SELECT COUNT(*) FROM phong_truc')->fetch_row()[0] !== 2) throw new RuntimeException('Danh sách phòng trực chưa đúng hai phòng.');
    if ((int)$db->query('SELECT COUNT(*) FROM phong_hoc')->fetch_row()[0] !== count($roomPlan)) throw new RuntimeException('Danh sách phòng học chưa đúng kế hoạch.');
    $db->commit();
    echo "Đã chuẩn hóa 2 phòng trực, 8 phòng học; giữ nguyên tài sản cũ và bổ sung tài sản mới.\n";
} catch (Throwable $error) {
    $db->rollback();
    throw $error;
}
