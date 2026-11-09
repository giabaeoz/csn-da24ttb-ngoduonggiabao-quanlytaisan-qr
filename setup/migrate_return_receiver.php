<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Chỉ chạy migration này bằng PHP CLI.\n");
}

require __DIR__ . '/../src/includes/database.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$schema = (string)$db->query('SELECT DATABASE()')->fetch_row()[0];

$exists = static function (string $sql, array $args) use ($db): bool {
    return (bool)$db->execute_query($sql, $args)->fetch_row();
};

if (!$exists('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
    [$schema, 'chi_tiet_muon', 'nhan_vien_nhan_id'])) {
    $db->query('ALTER TABLE chi_tiet_muon ADD COLUMN nhan_vien_nhan_id INT UNSIGNED NULL AFTER tinh_trang_khi_tra');
}
if (!$exists('SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
    [$schema, 'chi_tiet_muon', 'fk_chi_tiet_muon_nhan_vien_nhan'])) {
    $db->query('ALTER TABLE chi_tiet_muon ADD CONSTRAINT fk_chi_tiet_muon_nhan_vien_nhan
        FOREIGN KEY (nhan_vien_nhan_id) REFERENCES nhan_vien(id) ON DELETE RESTRICT ON UPDATE RESTRICT');
}
if (!$exists('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
    [$schema, 'chi_tiet_muon', 'idx_chi_tiet_muon_lan_tra'])) {
    $db->query('ALTER TABLE chi_tiet_muon ADD INDEX idx_chi_tiet_muon_lan_tra (phieu_muon_id, thoi_gian_tra)');
}

echo "Đã bổ sung nhân viên tiếp nhận trả cho chi_tiet_muon.\n";

