<?php
declare(strict_types=1);

const BORROWER_TYPES = ['SINH_VIEN' => 'Sinh viên', 'GIANG_VIEN' => 'Giảng viên'];
const BORROWER_FIELDS = [
    'ma_nguoi_muon' => ['Mã người mượn', 30, true],
    'ma_so_truong'   => ['Mã số trường', 30, true],
    'ho_ten'         => ['Họ tên', 100, true],
    'lop_don_vi'     => ['Lớp / đơn vị', 100, false],
    'so_dien_thoai'  => ['Số điện thoại', 20, false],
];

function borrower_text(array $input, string $key): string
{
    return is_string($input[$key] ?? null) ? trim($input[$key]) : '';
}

function borrower_write(mysqli $db, string $action, int $id, array $input): int
{
    if ($action === 'delete' && $id > 0) {
        // FK RESTRICT của phieu_muon bảo vệ cả trường hợp có người lập phiếu đồng thời.
        $db->execute_query('DELETE FROM nguoi_muon WHERE id = ?', [$id]);
        if (!$db->affected_rows) {
            throw new DomainException('Hồ sơ không còn tồn tại.');
        }
        return 0;
    }

    if (!(($action === 'create' && $id === 0) || ($action === 'edit' && $id > 0))) {
        throw new DomainException('Thao tác hồ sơ không hợp lệ.');
    }

    $values = [];
    foreach (BORROWER_FIELDS as $key => [$label, $max, $required]) {
        $value = borrower_text($input, $key);
        if ($required && $value === '') {
            throw new DomainException("Vui lòng nhập $label.");
        }
        if (mb_strlen($value, 'UTF-8') > $max) {
            throw new DomainException("$label tối đa $max ký tự.");
        }
        $values[] = $value === '' ? null : $value;
    }

    $type = borrower_text($input, 'loai_nguoi_muon');
    if (!isset(BORROWER_TYPES[$type])) {
        throw new DomainException('Hãy chọn Sinh viên hoặc Giảng viên.');
    }
    $values[] = $type;

    if ($action === 'create') {
        $db->execute_query('INSERT INTO nguoi_muon
            (ma_nguoi_muon, ma_so_truong, ho_ten, lop_don_vi, so_dien_thoai, loai_nguoi_muon)
            VALUES (?, ?, ?, ?, ?, ?)', $values);
        return (int) $db->insert_id;
    }

    if (!$db->execute_query('SELECT id FROM nguoi_muon WHERE id = ? FOR UPDATE', [$id])->fetch_row()) {
        throw new DomainException('Hồ sơ không còn tồn tại.');
    }
    $db->execute_query('UPDATE nguoi_muon SET ma_nguoi_muon = ?, ma_so_truong = ?, ho_ten = ?,
        lop_don_vi = ?, so_dien_thoai = ?, loai_nguoi_muon = ? WHERE id = ?', [...$values, $id]);
    return $id;
}

function borrower_pattern(string $value): string
{
    return '%' . strtr($value, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
}

function borrower_list(mysqli $db, string $tab, string $keyword, string $room, ?int $desk, int $page): array
{
    // Tổng hợp theo người trước khi nối hồ sơ để mỗi người chỉ xuất hiện một dòng.
    $from = " FROM nguoi_muon n LEFT JOIN (
        SELECT p.nguoi_muon_id, COUNT(*) AS open_count, MIN(p.han_tra) AS due,
               GROUP_CONCAT(DISTINCT h.ma_phong ORDER BY h.ma_phong SEPARATOR ', ') AS rooms
        FROM phieu_muon p
        JOIN chi_tiet_muon c ON c.phieu_muon_id = p.id AND c.thoi_gian_tra IS NULL
        JOIN phong_hoc h ON h.id = p.phong_hoc_id
        WHERE (? IS NULL OR p.phong_truc_id = ?) AND h.ma_phong LIKE ? ESCAPE '!'
        GROUP BY p.nguoi_muon_id
    ) m ON m.nguoi_muon_id = n.id
    WHERE (n.ho_ten LIKE ? ESCAPE '!' OR n.ma_so_truong LIKE ? ESCAPE '!'
        OR n.ma_nguoi_muon LIKE ? ESCAPE '!' OR n.so_dien_thoai LIKE ? ESCAPE '!')";
    if ($tab === 'late') {
        $from .= ' AND m.due < NOW()';
    } elseif ($tab === 'open' || $room !== '') {
        $from .= ' AND m.open_count > 0';
    }

    $pattern = borrower_pattern($keyword);
    $args = [$desk, $desk, borrower_pattern($room), $pattern, $pattern, $pattern, $pattern];
    $total = (int) $db->execute_query('SELECT COUNT(*)' . $from, $args)->fetch_row()[0];
    $pages = max(1, (int) ceil($total / 20));
    $page = min(max(1, $page), $pages);
    $rows = $db->execute_query('SELECT n.*, m.rooms, m.open_count, m.due, m.due < NOW() AS overdue'
        . $from . ' ORDER BY n.ho_ten, n.id LIMIT 20 OFFSET ?', [...$args, ($page - 1) * 20]);
    return [$rows, $total, $page, $pages];
}

function borrower_history(mysqli $db, int $id, ?int $desk): mysqli_result
{
    return $db->execute_query('SELECT p.ma_phieu_muon, p.thoi_gian_muon, p.han_tra,
            h.ma_phong, t.ma_tai_san, t.ten_tai_san, c.thoi_gian_tra,
            (c.thoi_gian_tra IS NULL AND p.han_tra < NOW()) AS overdue
        FROM phieu_muon p
        JOIN phong_hoc h ON h.id = p.phong_hoc_id
        JOIN chi_tiet_muon c ON c.phieu_muon_id = p.id
        JOIN tai_san t ON t.id = c.tai_san_id
        WHERE p.nguoi_muon_id = ? AND (? IS NULL OR p.phong_truc_id = ?)
        ORDER BY p.thoi_gian_muon DESC, p.id DESC, c.id', [$id, $desk, $desk]);
}
