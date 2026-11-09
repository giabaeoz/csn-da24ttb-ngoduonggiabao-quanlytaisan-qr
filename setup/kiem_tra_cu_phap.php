<?php
declare(strict_types=1);

$dir = dirname(__DIR__);
$folders = [$dir . '/src', $dir . '/setup'];

$phpExe = 'C:\\xampp\\php\\php.exe';
if (!file_exists($phpExe)) {
    $phpExe = 'php';
}

$tongFile = 0;
$soLoi = 0;

foreach ($folders as $folder) {
    if (!is_dir($folder)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $tongFile++;
            $filePath = $file->getRealPath();
            $cmd = escapeshellcmd($phpExe) . ' -l ' . escapeshellarg($filePath);
            $output = [];
            $code = 0;
            exec($cmd, $output, $code);

            if ($code !== 0) {
                $soLoi++;
                echo "[ERROR] $filePath\n";
                echo implode("\n", $output) . "\n\n";
            }
        }
    }
}

echo "============================================================\n";
echo "  KIỂM TRA CÚ PHÁP TOÀN DỰ ÁN\n";
echo "  Tổng số tệp: $tongFile | Số lỗi: $soLoi\n";
echo "============================================================\n";

exit($soLoi > 0 ? 1 : 0);
