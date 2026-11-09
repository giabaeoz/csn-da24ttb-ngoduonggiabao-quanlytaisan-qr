<?php
/**
 * Thư viện hỗ trợ chạy bộ kiểm thử tự động (Test Runner Helper).
 */

declare(strict_types=1);

$tongSoThu = 0;
$soPass = 0;
$soFail = 0;

function kiem_thu(string $tenKiemThu, bool $dieuKien, string $thongTinThem = ''): void {
    global $tongSoThu, $soPass, $soFail;
    $tongSoThu++;

    if ($dieuKien) {
        $soPass++;
        echo "  [PASS] " . $tenKiemThu;
        if ($thongTinThem !== '') {
            echo " ($thongTinThem)";
        }
        echo "\n";
    } else {
        $soFail++;
        echo "  [FAIL] " . $tenKiemThu . "\n";
        if ($thongTinThem !== '') {
            echo "         Chi tiết: " . $thongTinThem . "\n";
        }
    }
}

function tong_ket_kiem_thu(string $tenBoKiemThu): void {
    global $tongSoThu, $soPass, $soFail;
    echo "\n------------------------------------------------------------\n";
    echo "  KẾT QUẢ [$tenBoKiemThu]: $soPass / $tongSoThu bài đạt.";
    if ($soFail > 0) {
        echo " ($soFail bài THẤT BẠI)";
    }
    echo "\n============================================================\n\n";

    if ($soFail > 0) {
        exit(1);
    }
}

