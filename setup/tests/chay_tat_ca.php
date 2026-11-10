<?php
declare(strict_types=1);

/**
 * Script chạy toàn bộ 11 bộ kiểm thử tự động (Unit / Integration Tests)
 * Vị trí: setup/tests/chay_tat_ca.php
 * Trả về exit code 0 nếu tất cả PASS, hoặc exit code 1 nếu có lỗi.
 */

$thuMucTest = __DIR__;
$teps = glob($thuMucTest . '/thu_*.php');
sort($teps);

$tongTests = 0;
$tongLoi = 0;

echo "============================================================\n";
echo "  HỆ THỐNG KIỂM THỬ TỰ ĐỘNG TOÀN DIỆN (CHAY_TAT_CA.PHP)\n";
echo "============================================================\n";

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
$sqlMauDau = file_get_contents(dirname(__DIR__) . '/du_lieu_mau.sql');
if ($sqlMauDau !== false) {
    $dbDau = db();
    $dbDau->multi_query($sqlMauDau);
    while ($dbDau->more_results() && $dbDau->next_result()) {
        if ($r = $dbDau->store_result()) {
            $r->free();
        }
    }
}

foreach ($teps as $tep) {
    $tenTep = basename($tep);
    echo "\n>>> ĐANG CHẠY BỘ TEST: {$tenTep} ...\n";
    $cmd = 'C:\\xampp\\php\\php.exe ' . escapeshellarg($tep);
    passthru($cmd, $exitCode);
    if ($exitCode !== 0) {
        $tongLoi++;
        echo "  [FAIL] Bộ test {$tenTep} kết thúc với mã lỗi {$exitCode}!\n";
    }
}

echo "\n============================================================\n";
// Khôi phục dữ liệu mẫu sạch sẽ sau khi chạy kiểm thử để không để lại dữ liệu rác (TS_MUON_..., SV_TEST_...)
require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
$sqlMau = file_get_contents(dirname(__DIR__) . '/du_lieu_mau.sql');
if ($sqlMau !== false) {
    $db = db();
    $db->multi_query($sqlMau);
    while ($db->more_results() && $db->next_result()) {
        if ($r = $db->store_result()) {
            $r->free();
        }
    }
    echo "  [CLEANUP] Đã dọn sạch dữ liệu kiểm thử tạm thời và khôi phục dữ liệu mẫu chuẩn.\n";
}

if ($tongLoi === 0) {
    echo "  KẾT QUẢ CUỐI CÙNG: TẤT CẢ " . count($teps) . " BỘ TEST ĐÃ VƯỢT QUA (100% PASS)!\n";
    echo "============================================================\n";
    exit(0);
} else {
    echo "  KẾT QUẢ CUỐI CÙNG: CÓ {$tongLoi} BỘ TEST THẤT BẠI!\n";
    echo "============================================================\n";
    exit(1);
}
