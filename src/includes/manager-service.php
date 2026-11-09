<?php
declare(strict_types=1);

const MANAGER_CATALOGS = [
    'loai' => [
        'table' => 'loai_tai_san',
        'code' => 'ma_loai',
        'name' => 'ten_loai',
        'extra' => 'mo_ta',
        'title' => 'Loại tài sản',
        'extra_label' => 'Mô tả',
        'code_max' => 30,
        'name_max' => 100,
        'extra_max' => 2000,
    ],
    'phong-hoc' => [
        'table' => 'phong_hoc',
        'code' => 'ma_phong',
        'name' => 'ten_phong',
        'extra' => 'toa_nha',
        'title' => 'Phòng học',
        'extra_label' => 'Tòa nhà',
        'code_max' => 30,
        'name_max' => 100,
        'extra_max' => 50,
    ],
    'phong-truc' => [
        'table' => 'phong_truc',
        'code' => 'ma_phong_truc',
        'name' => 'ten_phong_truc',
        'extra' => 'vi_tri',
        'title' => 'Phòng trực',
        'extra_label' => 'Vị trí',
        'code_max' => 20,
        'name_max' => 100,
        'extra_max' => 150,
    ],
];

function manager_check_actor(mysqli $db, int $actor, array $allowedRoles = ['QUAN_LY']): void
{
    $row = $db->execute_query(
        'SELECT vai_tro, dang_hoat_dong FROM nhan_vien WHERE id = ? FOR SHARE',
        [$actor]
    )->fetch_assoc();

    if (!$row || !in_array($row['vai_tro'], $allowedRoles, true) || !(int)$row['dang_hoat_dong']) {
        throw new DomainException('Quyền truy cập của phiên này đã thay đổi.');
    }
}

function manager_field(array $data, string $key, int $max, bool $required = true): string
{
    $value = $data[$key] ?? null;
    if (!is_string($value)) {
        throw new DomainException('Dữ liệu biểu mẫu không hợp lệ.');
    }
    $value = trim($value);
    if (($required && $value === '') || mb_strlen($value) > $max || str_contains($value, "\0")) {
        throw new DomainException('Vui lòng kiểm tra trường ' . $key . ' và độ dài dữ liệu.');
    }
    return $value;
}

function manager_id(mixed $value, bool $optional = false): ?int
{
    if ($optional && ($value === null || $value === '')) {
        return null;
    }
    if (!is_string($value) && !is_int($value)) {
        throw new DomainException('Mã bản ghi không hợp lệ.');
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new DomainException('Mã bản ghi không hợp lệ.');
    }
    return (int)$id;
}

function manager_catalog_save(mysqli $db, string $kind, string $action, ?int $id, array $data): int
{
    $spec = MANAGER_CATALOGS[$kind] ?? null;
    if (!$spec || !in_array($action, ['create', 'edit', 'status', 'delete'], true)) {
        throw new DomainException('Thao tác không hợp lệ.');
    }

    $table = $spec['table'];
    if ($action === 'create' && $id !== null) {
        throw new DomainException('Mã tạo mới không hợp lệ.');
    }

    $codeCol = $spec['code'];
    $nameCol = $spec['name'];
    $extraCol = $spec['extra'];
    $old = null;

    if ($action !== 'create') {
        if ($id === null) {
            throw new DomainException('Chưa chọn bản ghi.');
        }
        $old = $db->execute_query("SELECT * FROM $table WHERE id = ? FOR UPDATE", [$id])->fetch_assoc();
        if (!$old) {
            throw new DomainException('Không tìm thấy bản ghi.');
        }
    }

    if ($action === 'status' || $action === 'delete') {
        $count = match ($kind) {
            'loai' => (int)$db->execute_query(
                'SELECT COUNT(*) FROM tai_san WHERE loai_tai_san_id = ? AND trang_thai <> \'NGUNG_SU_DUNG\'',
                [$id]
            )->fetch_row()[0],
            'phong-truc' => (int)$db->execute_query(
                'SELECT COUNT(*) FROM tai_san WHERE phong_truc_id = ? AND trang_thai <> \'NGUNG_SU_DUNG\'',
                [$id]
            )->fetch_row()[0]
                + (int)$db->execute_query(
                    'SELECT COUNT(*) FROM nhan_vien WHERE phong_truc_id = ? AND dang_hoat_dong = 1',
                    [$id]
                )->fetch_row()[0],
            'phong-hoc' => (int)$db->execute_query(
                "SELECT COUNT(*) FROM tai_san WHERE trang_thai <> 'NGUNG_SU_DUNG' AND
                (phong_hoc_phu_hop_id = ? OR phong_hoc_hien_tai_id = ? OR JSON_CONTAINS(phong_tuong_thich, JSON_QUOTE(?)))",
                [$id, $id, $old[$codeCol]]
            )->fetch_row()[0],
        };

        if ($count > 0 && ($action === 'delete' || (int)$old['dang_hoat_dong'] === 1)) {
            throw new DomainException('Bản ghi đang được sử dụng; hãy xử lý tài sản hoặc phân công trước.');
        }

        if ($action === 'status') {
            $newStatus = (int)!((int)$old['dang_hoat_dong']);
            $db->execute_query("UPDATE $table SET dang_hoat_dong = ? WHERE id = ?", [$newStatus, $id]);
            return $id;
        }

        if ($kind === 'phong-hoc') {
            $used = (int)$db->execute_query(
                'SELECT COUNT(*) FROM tai_san WHERE JSON_CONTAINS(phong_tuong_thich, JSON_QUOTE(?))',
                [$old[$codeCol]]
            )->fetch_row()[0];
            if ($used) {
                throw new DomainException('Mã phòng còn nằm trong danh sách tương thích tài sản.');
            }
        }

        $db->execute_query("DELETE FROM $table WHERE id = ?", [$id]);
        return $id;
    }

    $code = manager_field($data, 'code', $spec['code_max']);
    $name = manager_field($data, 'name', $spec['name_max']);
    $extra = manager_field($data, 'extra', $spec['extra_max'], false);
    $second = $kind === 'phong-hoc' ? manager_field($data, 'tang', 20, false) : '';

    if ($kind === 'phong-hoc' && $action === 'edit' && $code !== $old[$codeCol]) {
        $used = (int)$db->execute_query(
            'SELECT COUNT(*) FROM tai_san WHERE JSON_CONTAINS(phong_tuong_thich, JSON_QUOTE(?))',
            [$old[$codeCol]]
        )->fetch_row()[0];
        if ($used) {
            throw new DomainException('Không thể đổi mã phòng đang có trong danh sách tương thích tài sản.');
        }
    }

    if ($action === 'create') {
        if ($kind === 'phong-hoc') {
            $db->execute_query(
                "INSERT INTO $table ($codeCol, $nameCol, $extraCol, tang) VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''))",
                [$code, $name, $extra, $second]
            );
        } else {
            $db->execute_query(
                "INSERT INTO $table ($codeCol, $nameCol, $extraCol) VALUES (?, ?, NULLIF(?, ''))",
                [$code, $name, $extra]
            );
        }
        return (int)$db->insert_id;
    }

    if ($kind === 'phong-hoc') {
        $db->execute_query(
            "UPDATE $table SET $codeCol = ?, $nameCol = ?, $extraCol = NULLIF(?, ''), tang = NULLIF(?, '') WHERE id = ?",
            [$code, $name, $extra, $second, $id]
        );
    } else {
        $db->execute_query(
            "UPDATE $table SET $codeCol = ?, $nameCol = ?, $extraCol = NULLIF(?, '') WHERE id = ?",
            [$code, $name, $extra, $id]
        );
    }
    return $id;
}

function manager_asset_save(mysqli $db, string $action, ?int $id, array $data): int
{
    if (!in_array($action, ['create', 'edit', 'stop', 'delete'], true)) {
        throw new DomainException('Thao tác không hợp lệ.');
    }
    if ($action === 'create' && $id !== null) {
        throw new DomainException('Mã tạo mới không hợp lệ.');
    }

    $old = null;
    if ($action !== 'create') {
        if (!$id) {
            throw new DomainException('Chưa chọn tài sản.');
        }
        $old = $db->execute_query('SELECT * FROM tai_san WHERE id = ? FOR UPDATE', [$id])->fetch_assoc();
        if (!$old) {
            throw new DomainException('Không tìm thấy tài sản.');
        }
        $db->execute_query('SELECT id FROM phong_truc WHERE id = ? FOR SHARE', [(int)$old['phong_truc_id']]);
        if ($db->execute_query("SELECT id FROM dot_kiem_ke WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE' LIMIT 1", [(int)$old['phong_truc_id']])->fetch_row()) {
            throw new DomainException('Tài sản thuộc phòng trực đang kiểm kê; hãy hoàn tất đợt trước khi sửa.');
        }
    }

    $openLoan = $old && $db->execute_query(
        'SELECT 1 FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL LIMIT 1',
        [$id]
    )->fetch_row();

    if ($action === 'stop') {
        if ($openLoan) {
            throw new DomainException('Tài sản chưa được trả, không thể ngừng sử dụng.');
        }
        $db->execute_query("UPDATE tai_san SET trang_thai = 'NGUNG_SU_DUNG' WHERE id = ?", [$id]);
        return $id;
    }

    if ($action === 'delete') {
        $references = 0;
        foreach (['chi_tiet_muon', 'chi_tiet_kiem_ke', 'chi_tiet_dieu_chuyen', 'su_co_tai_san'] as $table) {
            $references += (int)$db->execute_query("SELECT COUNT(*) FROM $table WHERE tai_san_id = ?", [$id])->fetch_row()[0];
        }
        if ($references > 0) {
            throw new DomainException('Tài sản đã có lịch sử nghiệp vụ; hãy ngừng sử dụng để giữ hồ sơ.');
        }
        $db->execute_query('DELETE FROM tai_san WHERE id = ?', [$id]);
        return $id;
    }

    $code = $action === 'create' ? manager_field($data, 'ma_tai_san', 30) : $old['ma_tai_san'];
    if ($action === 'create' && !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/D', $code)) {
        throw new DomainException('Mã tài sản chỉ gồm chữ Latin, số, dấu chấm, gạch ngang và gạch dưới.');
    }

    $name = manager_field($data, 'ten_tai_san', 150);
    if (($data['loai_tai_san_id'] ?? '') === '__NEW__' || !empty($data['ten_loai_moi'])) {
        $newTypeName = trim((string)($data['ten_loai_moi'] ?? ''));
        if ($newTypeName === '' || mb_strlen($newTypeName, 'UTF-8') > 100) {
            throw new DomainException('Tên phân loại mới không được để trống và tối đa 100 ký tự.');
        }
        $newTypeCode = trim((string)($data['ma_loai_moi'] ?? ''));
        if ($newTypeCode === '') {
            $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', preg_replace('/[áàảãạăắặằẳẵâấầẩẫậéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵđ]/iu', 'x', $newTypeName)));
            if (strlen($base) < 2) {
                $base = 'PL';
            }
            $newTypeCode = substr($base, 0, 10) . '_' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        } elseif (!preg_match('/^[A-Za-z0-9_-]{1,30}$/', $newTypeCode)) {
            throw new DomainException('Mã phân loại mới chỉ gồm chữ cái, số, gạch ngang, gạch dưới (tối đa 30 ký tự).');
        }
        $exist = $db->execute_query('SELECT id FROM loai_tai_san WHERE ma_loai = ?', [$newTypeCode])->fetch_assoc();
        if ($exist) {
            $type = (int)$exist['id'];
        } else {
            $db->execute_query(
                'INSERT INTO loai_tai_san (ma_loai, ten_loai, mo_ta, dang_hoat_dong, tao_luc, cap_nhat_luc) VALUES (?, ?, ?, 1, NOW(), NOW())',
                [$newTypeCode, $newTypeName, 'Thêm nhanh từ biểu mẫu tài sản']
            );
            $type = (int)$db->insert_id;
        }
    } else {
        $type = manager_id($data['loai_tai_san_id'] ?? null);
    }
    $desk = manager_id($data['phong_truc_id'] ?? null);
    $current = manager_id($data['phong_hoc_hien_tai_id'] ?? null, true);
    $scope = manager_field($data, 'pham_vi_tuong_thich', 20);

    if (!in_array($scope, ['THEO_PHONG', 'LINH_HOAT'], true)) {
        throw new DomainException('Phạm vi tương thích không hợp lệ.');
    }

    $rawRooms = $data['rooms'] ?? [];
    if (!is_array($rawRooms) || count($rawRooms) > 100) {
        throw new DomainException('Danh sách phòng không hợp lệ.');
    }

    $roomIds = [];
    foreach ($rawRooms as $raw) {
        $roomIds[] = manager_id($raw);
    }
    if (count($roomIds) !== count(array_unique($roomIds))) {
        throw new DomainException('Không chọn trùng phòng tương thích.');
    }
    if (($scope === 'THEO_PHONG' && !$roomIds) || ($scope === 'LINH_HOAT' && $roomIds)) {
        throw new DomainException('Theo phòng phải chọn ít nhất một phòng; linh hoạt không chọn phòng cố định.');
    }

    $condition = manager_field($data, 'tinh_trang', 10);
    $state = manager_field($data, 'trang_thai', 20);
    $allowedStates = ['SAN_SANG', 'BAO_TRI', 'NGUNG_SU_DUNG'];
    if ($openLoan && $old['trang_thai'] === 'DANG_MUON') {
        $allowedStates[] = 'DANG_MUON';
    }
    if (!in_array($condition, ['TOT', 'HU_HONG', 'MAT'], true) || !in_array($state, $allowedStates, true)) {
        throw new DomainException('Tình trạng hoặc trạng thái không hợp lệ.');
    }
    if ($state === 'SAN_SANG' && $condition !== 'TOT') {
        throw new DomainException('Chỉ tài sản tốt mới được ở trạng thái sẵn sàng.');
    }
    if ($condition === 'MAT' && $state !== 'NGUNG_SU_DUNG') {
        throw new DomainException('Tài sản mất phải ngừng sử dụng.');
    }

    $date = manager_field($data, 'ngay_dua_vao_su_dung', 10, false);
    if ($date !== '') {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new DomainException('Ngày đưa vào sử dụng không hợp lệ.');
        }
    }

    $note = manager_field($data, 'mo_ta', 5000, false);

    if ($openLoan) {
        $oldRooms = json_decode($old['phong_tuong_thich'] ?? '[]', true);
        $submittedCodes = [];
        foreach ($roomIds as $roomId) {
            $found = $db->execute_query('SELECT ma_phong FROM phong_hoc WHERE id = ?', [$roomId])->fetch_row();
            if ($found) {
                $submittedCodes[] = $found[0];
            }
        }
        sort($oldRooms);
        sort($submittedCodes);
        if (
            (int)$old['loai_tai_san_id'] !== $type
            || (int)$old['phong_truc_id'] !== $desk
            || (int)$old['phong_hoc_hien_tai_id'] !== (int)$current
            || $old['pham_vi_tuong_thich'] !== $scope
            || $oldRooms !== $submittedCodes
            || $old['tinh_trang'] !== $condition
            || $old['trang_thai'] !== $state
            || ($old['ngay_dua_vao_su_dung'] ?? '') !== $date
        ) {
            throw new DomainException('Tài sản đang được mượn; chỉ có thể sửa tên và mô tả.');
        }
    }

    $typeValid = $db->execute_query('SELECT 1 FROM loai_tai_san WHERE id = ? AND dang_hoat_dong = 1 FOR SHARE', [$type])->fetch_row();
    $deskValid = $db->execute_query('SELECT 1 FROM phong_truc WHERE id = ? AND dang_hoat_dong = 1 FOR SHARE', [$desk])->fetch_row();
    if (!$typeValid || !$deskValid) {
        throw new DomainException('Loại tài sản hoặc phòng trực chưa hoạt động.');
    }
    if ($db->execute_query("SELECT id FROM dot_kiem_ke WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE' LIMIT 1", [$desk])->fetch_row()) {
        throw new DomainException('Phòng trực đang kiểm kê; chưa thể thêm hoặc chuyển tài sản vào phòng trực này.');
    }

    $allRoomIds = array_values(array_unique(array_merge($roomIds, $current ? [$current] : [])));
    $roomMap = [];
    foreach ($allRoomIds as $roomId) {
        $row = $db->execute_query('SELECT ma_phong FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1 FOR SHARE', [$roomId])->fetch_row();
        if (!$row) {
            throw new DomainException('Phòng học chưa hoạt động hoặc không tồn tại.');
        }
        $roomMap[$roomId] = $row[0];
    }
    $codes = array_map(static fn(int $roomId): string => $roomMap[$roomId], $roomIds);
    if ($current && $scope === 'THEO_PHONG' && !in_array($roomMap[$current], $codes, true)) {
        throw new DomainException('Phòng hiện tại phải thuộc danh sách phòng tương thích.');
    }

    $singleRoom = count($roomIds) === 1 ? $roomIds[0] : null;
    $values = [
        $name,
        $type,
        $desk,
        $singleRoom,
        $current,
        $scope,
        json_encode($codes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        $condition,
        $state,
        $date,
        $note,
    ];

    if ($action === 'create') {
        $db->execute_query(
            'INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, phong_hoc_phu_hop_id, phong_hoc_hien_tai_id, pham_vi_tuong_thich, phong_tuong_thich, tinh_trang, trang_thai, ngay_dua_vao_su_dung, mo_ta)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'))',
            [$code, 'TVU:' . $code, ...$values]
        );
        return (int)$db->insert_id;
    }

    $db->execute_query(
        'UPDATE tai_san
         SET ten_tai_san = ?, loai_tai_san_id = ?, phong_truc_id = ?, phong_hoc_phu_hop_id = ?, phong_hoc_hien_tai_id = ?, pham_vi_tuong_thich = ?, phong_tuong_thich = ?, tinh_trang = ?, trang_thai = ?, ngay_dua_vao_su_dung = NULLIF(?, \'\'), mo_ta = NULLIF(?, \'\')
         WHERE id = ?',
        [...$values, $id]
    );
    return $id;
}
