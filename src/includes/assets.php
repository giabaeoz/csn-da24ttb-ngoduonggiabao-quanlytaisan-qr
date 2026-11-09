<?php
declare(strict_types=1);

const ASSET_STATES = [
    'SAN_SANG' => 'Sẵn sàng', 'DANG_MUON' => 'Đang mượn',
    'BAO_TRI' => 'Bảo trì', 'NGUNG_SU_DUNG' => 'Ngừng sử dụng',
];
const ASSET_CONDITIONS = ['TOT' => 'Tốt', 'HU_HONG' => 'Hư hỏng', 'MAT' => 'Mất'];

const ASSET_SELECT = "SELECT t.*, l.ten_loai, p.ten_phong_truc,
    h.ma_phong AS phong_hien_tai, f.ma_phong AS phong_cu,
    CASE WHEN EXISTS (
        SELECT 1 FROM chi_tiet_muon c WHERE c.tai_san_id = t.id AND c.thoi_gian_tra IS NULL
    ) THEN 'DANG_MUON' ELSE t.trang_thai END AS trang_thai_hien_thi
    FROM tai_san t
    JOIN loai_tai_san l ON l.id = t.loai_tai_san_id
    JOIN phong_truc p ON p.id = t.phong_truc_id
    LEFT JOIN phong_hoc h ON h.id = t.phong_hoc_hien_tai_id
    LEFT JOIN phong_hoc f ON f.id = t.phong_hoc_phu_hop_id";

function asset_text(array $input, string $key, int $max = 100): string
{
    return is_string($input[$key] ?? null) ? mb_substr(trim($input[$key]), 0, $max) : '';
}

function asset_filter_id(array $input, string $key): ?int
{
    if (!isset($input[$key]) || $input[$key] === '') {
        return null;
    }
    $id = filter_var($input[$key], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('Mã bộ lọc không hợp lệ.');
    }
    return $id;
}

function asset_compatibility(array $asset): string
{
    if ($asset['pham_vi_tuong_thich'] === 'LINH_HOAT') {
        return 'Không giới hạn phòng cố định';
    }
    if ($asset['pham_vi_tuong_thich'] === 'THEO_PHONG') {
        return implode(', ', json_decode($asset['phong_tuong_thich'], true, 512, JSON_THROW_ON_ERROR));
    }
    return $asset['phong_cu'] ?? 'Chưa xác định';
}

function asset_list(mysqli $db, array $filters, int $page): array
{
    $where = [];
    $params = [];
    if ($filters['q'] !== '') {
        $pattern = '%' . strtr($filters['q'], ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $where[] = "(a.ma_tai_san LIKE ? ESCAPE '!' OR a.ten_tai_san LIKE ? ESCAPE '!' OR a.ma_qr LIKE ? ESCAPE '!')";
        array_push($params, $pattern, $pattern, $pattern);
    }
    foreach (['type' => 'loai_tai_san_id', 'desk' => 'phong_truc_id', 'location' => 'phong_hoc_hien_tai_id'] as $key => $column) {
        if ($filters[$key] !== null) {
            $where[] = "a.$column = ?";
            $params[] = $filters[$key];
        }
    }
    foreach (['state' => 'trang_thai_hien_thi', 'condition' => 'tinh_trang', 'scope' => 'pham_vi_tuong_thich'] as $key => $column) {
        if ($filters[$key] !== '') {
            $where[] = "a.$column = ?";
            $params[] = $filters[$key];
        }
    }
    if ($filters['compatible'] !== '') {
        $where[] = "(a.pham_vi_tuong_thich = 'LINH_HOAT'
            OR JSON_CONTAINS(a.phong_tuong_thich, JSON_QUOTE(?))
            OR (a.pham_vi_tuong_thich IS NULL AND a.phong_cu = ?))";
        array_push($params, $filters['compatible'], $filters['compatible']);
    }
    $from = ' FROM (' . ASSET_SELECT . ') a';
    if ($where) {
        $from .= ' WHERE ' . implode(' AND ', $where);
    }
    $total = (int)$db->execute_query('SELECT COUNT(*)' . $from, $params)->fetch_row()[0];
    $pages = max(1, (int)ceil($total / 20));
    $page = min(max(1, $page), $pages);
    $rows = $db->execute_query('SELECT a.*' . $from . ' ORDER BY a.ma_tai_san, a.id LIMIT 20 OFFSET ?',
        [...$params, ($page - 1) * 20])->fetch_all(MYSQLI_ASSOC);
    return [$rows, $total, $page, $pages];
}

function asset_history(mysqli $db, int $id): array
{
    return [
        'loans' => $db->execute_query('SELECT p.ma_phieu_muon, p.thoi_gian_muon, p.han_tra,
                n.id AS nguoi_muon_id, n.ho_ten, n.ma_so_truong, c.thoi_gian_tra,
                c.tinh_trang_khi_muon, c.tinh_trang_khi_tra,
                c.thoi_gian_tra IS NULL AND p.han_tra < NOW() AS qua_han
            FROM chi_tiet_muon c JOIN phieu_muon p ON p.id = c.phieu_muon_id
            JOIN nguoi_muon n ON n.id = p.nguoi_muon_id
            WHERE c.tai_san_id = ? ORDER BY p.thoi_gian_muon DESC, c.id DESC', [$id])->fetch_all(MYSQLI_ASSOC),
        'inventory' => $db->execute_query('SELECT d.ma_dot_kiem_ke, d.ten_dot_kiem_ke, d.thoi_gian_bat_dau,
                c.thoi_gian_kiem_tra, c.ket_qua, c.tinh_trang_thuc_te, h.ma_phong
            FROM chi_tiet_kiem_ke c JOIN dot_kiem_ke d ON d.id = c.dot_kiem_ke_id
            LEFT JOIN phong_hoc h ON h.id = c.phong_hoc_thuc_te_id
            WHERE c.tai_san_id = ? ORDER BY d.thoi_gian_bat_dau DESC, c.id DESC', [$id])->fetch_all(MYSQLI_ASSOC),
        'transfers' => $db->execute_query('SELECT d.ma_dieu_chuyen, d.thoi_gian_dieu_chuyen, d.ly_do,
                a.ma_phong AS phong_di, b.ma_phong AS phong_den
            FROM chi_tiet_dieu_chuyen c JOIN dieu_chuyen d ON d.id = c.dieu_chuyen_id
            JOIN phong_hoc a ON a.id = d.phong_hoc_di_id JOIN phong_hoc b ON b.id = d.phong_hoc_den_id
            WHERE c.tai_san_id = ? ORDER BY d.thoi_gian_dieu_chuyen DESC, c.id DESC', [$id])->fetch_all(MYSQLI_ASSOC),
        'incidents' => $db->execute_query('SELECT ma_su_co, loai_su_co, thoi_gian_phat_hien, mo_ta,
                trang_thai, thoi_gian_xu_ly, ket_qua_xu_ly FROM su_co_tai_san
            WHERE tai_san_id = ? ORDER BY thoi_gian_phat_hien DESC, id DESC', [$id])->fetch_all(MYSQLI_ASSOC),
    ];
}
