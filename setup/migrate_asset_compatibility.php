<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../src/includes/database.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if ((int)$db->query("SELECT GET_LOCK('qlts_asset_compatibility', 10)")->fetch_row()[0] !== 1) {
    throw new RuntimeException('Đang có phiên cập nhật CSDL khác.');
}
try {
    $columns = array_column($db->query('SHOW COLUMNS FROM tai_san')->fetch_all(MYSQLI_ASSOC), null, 'Field');
    $additions = [];
    if (!isset($columns['pham_vi_tuong_thich'])) {
        $additions[] = "ADD COLUMN pham_vi_tuong_thich ENUM('THEO_PHONG','LINH_HOAT') NULL";
    }
    if (!isset($columns['phong_tuong_thich'])) {
        $additions[] = 'ADD COLUMN phong_tuong_thich JSON NULL';
    }
    $constraint = $db->execute_query("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'tai_san'
        AND CONSTRAINT_NAME = 'ck_tai_san_tuong_thich'")->fetch_row();
    if (!$constraint) {
        $additions[] = "ADD CONSTRAINT ck_tai_san_tuong_thich CHECK (
            (pham_vi_tuong_thich IS NULL AND phong_tuong_thich IS NULL)
            OR (pham_vi_tuong_thich IS NOT NULL AND phong_tuong_thich IS NOT NULL
                AND JSON_SCHEMA_VALID(
                    '{\"type\":\"array\",\"items\":{\"type\":\"string\",\"minLength\":1,\"maxLength\":30},\"uniqueItems\":true}',
                    phong_tuong_thich)
                AND ((pham_vi_tuong_thich = 'LINH_HOAT' AND JSON_LENGTH(phong_tuong_thich) = 0)
                    OR (pham_vi_tuong_thich = 'THEO_PHONG' AND JSON_LENGTH(phong_tuong_thich) > 0)))
        )";
    }
    if ($additions) {
        $db->query('ALTER TABLE tai_san ' . implode(', ', $additions));
    }
    echo "OK: hai thuộc tính tương thích đã sẵn sàng.\n";
} finally {
    $db->query("SELECT RELEASE_LOCK('qlts_asset_compatibility')");
}
