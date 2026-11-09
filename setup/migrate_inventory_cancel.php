<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Chỉ chạy migration này bằng PHP CLI.\n");
}

require __DIR__ . '/../src/includes/database.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$schema = (string)$db->query('SELECT DATABASE()')->fetch_row()[0];

$columnExists = static function (string $column) use ($db, $schema): bool {
    return (bool)$db->execute_query(
        'SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$schema, 'dot_kiem_ke', $column]
    )->fetch_row();
};
$constraintExists = static function (string $constraint) use ($db, $schema): bool {
    return (bool)$db->execute_query(
        'SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
        [$schema, 'dot_kiem_ke', $constraint]
    )->fetch_row();
};

if ($constraintExists('ck_dot_kiem_ke_hoan_tat')) {
    $db->query('ALTER TABLE dot_kiem_ke DROP CHECK ck_dot_kiem_ke_hoan_tat');
}

$db->query("ALTER TABLE dot_kiem_ke MODIFY trang_thai
    ENUM('DANG_KIEM_KE','HOAN_TAT','DA_HUY') NOT NULL DEFAULT 'DANG_KIEM_KE'");

if (!$columnExists('nhan_vien_huy_id')) {
    $db->query('ALTER TABLE dot_kiem_ke ADD COLUMN nhan_vien_huy_id INT UNSIGNED NULL AFTER nhan_vien_lap_id');
}
if (!$columnExists('ly_do_huy')) {
    $db->query('ALTER TABLE dot_kiem_ke ADD COLUMN ly_do_huy VARCHAR(500) NULL AFTER ghi_chu');
}
if (!$constraintExists('fk_dot_kiem_ke_nhan_vien_huy')) {
    $db->query('ALTER TABLE dot_kiem_ke ADD CONSTRAINT fk_dot_kiem_ke_nhan_vien_huy
        FOREIGN KEY (nhan_vien_huy_id) REFERENCES nhan_vien(id)
        ON DELETE RESTRICT ON UPDATE RESTRICT');
}
if (!$constraintExists('ck_dot_kiem_ke_hoan_tat')) {
    $db->query("ALTER TABLE dot_kiem_ke ADD CONSTRAINT ck_dot_kiem_ke_hoan_tat CHECK (
        (trang_thai = 'DANG_KIEM_KE' AND thoi_gian_ket_thuc IS NULL AND nhan_vien_huy_id IS NULL AND ly_do_huy IS NULL)
        OR
        (trang_thai = 'HOAN_TAT' AND thoi_gian_ket_thuc IS NOT NULL AND nhan_vien_huy_id IS NULL AND ly_do_huy IS NULL)
        OR
        (trang_thai = 'DA_HUY' AND thoi_gian_ket_thuc IS NOT NULL AND nhan_vien_huy_id IS NOT NULL AND CHAR_LENGTH(TRIM(ly_do_huy)) > 0)
    )");
}

echo "Đã bổ sung trạng thái hủy đợt kiểm kê và thông tin kiểm soát liên quan.\n";
