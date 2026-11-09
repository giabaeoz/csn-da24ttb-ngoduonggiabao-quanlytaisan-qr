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
if ($tongLoi === 0) {
    echo "  KẾT QUẢ CUỐI CÙNG: TẤT CẢ " . count($teps) . " BỘ TEST ĐÃ VƯỢT QUA (100% PASS)!\n";
    echo "============================================================\n";
    exit(0);
} else {
    echo "  KẾT QUẢ CUỐI CÙNG: CÓ {$tongLoi} BỘ TEST THẤT BẠI!\n";
    echo "============================================================\n";
    exit(1);
}
