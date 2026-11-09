<?php
declare(strict_types=1);

const ACCOUNT_ROLES = [
    'NHAN_VIEN' => 'Nhân viên',
    'QUAN_LY' => 'Quản lý',
    'QUAN_TRI' => 'Quản trị viên',
];

function account_field(array $input, string $key, int $limit, bool $required = true): string
{
    $raw = $input[$key] ?? '';
    if (!is_string($raw)) {
        throw new DomainException('Dữ liệu gửi lên không hợp lệ.');
    }
    $value = trim($raw);
    if (($required && $value === '') || mb_strlen($value, 'UTF-8') > $limit) {
        throw new DomainException('Vui lòng kiểm tra các trường bắt buộc và độ dài thông tin.');
    }
    return $value;
}

function account_password(array $input): string
{
    $password = $input['mat_khau'] ?? null;
    if (!is_string($password) || mb_strlen($password, 'UTF-8') < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
        throw new DomainException('Mật khẩu cần ít nhất 8 ký tự và tối đa 72 byte.');
    }
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Gọi trong transaction. Khóa người thực hiện cùng tài khoản đích trước khi kiểm tra quyền.
 */
function account_save(mysqli $db, int $actor, string $action, int $id, array $input): void
{
    $rows = $db->execute_query(
        'SELECT id, vai_tro, dang_hoat_dong FROM nhan_vien WHERE id IN (?, ?) ORDER BY id FOR UPDATE',
        [$actor, $id]
    );
    $accounts = array_column($rows->fetch_all(MYSQLI_ASSOC), null, 'id');
    if (($accounts[$actor]['vai_tro'] ?? '') !== 'QUAN_TRI' || !(int)$accounts[$actor]['dang_hoat_dong']) {
        throw new DomainException('Quyền quản trị của phiên này đã thay đổi.');
    }
    if ($action !== 'create' && !isset($accounts[$id])) {
        throw new DomainException('Không tìm thấy tài khoản.');
    }
    if ($action === 'status') {
        $status = $input['status'] ?? '';
        if (!in_array($status, ['0', '1'], true) || ($actor === $id && $status === '0')) {
            throw new DomainException('Không thể thực hiện thay đổi trạng thái này.');
        }
        $db->execute_query('UPDATE nhan_vien SET dang_hoat_dong = ? WHERE id = ?', [$status, $id]);
        return;
    }
    if ($action === 'password') {
        $db->execute_query('UPDATE nhan_vien SET mat_khau_hash = ? WHERE id = ?', [account_password($input), $id]);
        return;
    }
    if (!in_array($action, ['edit', 'create'], true)) {
        throw new DomainException('Thao tác không hợp lệ.');
    }

    $role = account_field($input, 'vai_tro', 20);
    if (!isset(ACCOUNT_ROLES[$role]) || ($actor === $id && $role !== 'QUAN_TRI')) {
        throw new DomainException('Vai trò không hợp lệ hoặc bạn đang đổi quyền của chính mình.');
    }
    $room = account_field($input, 'phong_truc_id', 10, false);
    if ($room === '' && $role === 'NHAN_VIEN') {
        throw new DomainException('Nhân viên phải được phân công phòng trực.');
    }
    if ($room !== '' && (!ctype_digit($room) || !$db->execute_query('SELECT id FROM phong_truc WHERE id = ? AND dang_hoat_dong = 1 FOR SHARE', [$room])->fetch_row())) {
        throw new DomainException('Hãy chọn phòng trực đang hoạt động.');
    }

    $values = [
        account_field($input, 'ma_nhan_vien', 30),
        account_field($input, 'ho_ten', 100),
        account_field($input, 'ten_dang_nhap', 50),
        account_field($input, 'so_dien_thoai', 20, false),
        $role,
        $room === '' ? null : $room,
    ];

    if ($action === 'create') {
        $values[] = account_password($input);
        $db->execute_query(
            'INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, ten_dang_nhap, so_dien_thoai, vai_tro, phong_truc_id, mat_khau_hash)
             VALUES (?, ?, ?, NULLIF(?, \'\'), ?, ?, ?)',
            $values
        );
    } else {
        $values[] = $id;
        $db->execute_query(
            'UPDATE nhan_vien
             SET ma_nhan_vien = ?, ho_ten = ?, ten_dang_nhap = ?, so_dien_thoai = NULLIF(?, \'\'), vai_tro = ?, phong_truc_id = ?
             WHERE id = ?',
            $values
        );
    }
}

