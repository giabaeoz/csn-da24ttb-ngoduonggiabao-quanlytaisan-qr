<?php
declare(strict_types=1);

$thuMucTest = __DIR__ . '/tests';
$teps = glob($thuMucTest . '/thu_*.php');
sort($teps);

$tongTests = 0;
$tongLoi = 0;

foreach ($teps as $tep) {
    echo "\n=== CHẠY TEST: " . basename($tep) . " ===\n";
    $cmd = 'C:\\xampp\\php\\php.exe ' . escapeshellarg($tep);
    passthru($cmd, $exitCode);
    if ($exitCode !== 0) {
        $tongLoi++;
    }
}

echo "\n============================================================\n";
if ($tongLoi === 0) {
    echo "TẤT CẢ " . count($teps) . " BỘ TEST ĐÃ VƯỢT QUA HOÀN TOÀN!\n";
} else {
    echo "CÓ $tongLoi BỘ TEST THẤT BẠI!\n";
}
echo "============================================================\n";
