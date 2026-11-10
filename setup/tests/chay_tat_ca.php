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
// Dọn dẹp dữ liệu kiểm thử an toàn - KHÔNG reset toàn bộ CSDL trừ khi có cờ --reset-db
require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
$db = db();

$canResetDb = in_array('--reset-db', $argv ?? [], true);
if ($canResetDb) {
    $sqlMau = file_get_contents(dirname(__DIR__) . '/du_lieu_mau.sql');
    if ($sqlMau !== false) {
        $db->multi_query($sqlMau);
        while ($db->more_results() && $db->next_result()) {
            if ($r = $db->store_result()) {
                $r->free();
            }
        }
        echo "  [CLEANUP] Đã khôi phục dữ liệu mẫu chuẩn theo yêu cầu (--reset-db).\n";
    }
} else {
    // Chỉ dọn các bản ghi phát sinh trong quá trình chạy test (chứa tiền tố TEST, TRA1, KHACBAN, ...)
    $db->query("DELETE FROM chi_tiet_muon WHERE ghi_chu_tra LIKE '%Test%' OR tai_san_id IN (SELECT id FROM tai_san WHERE ma_tai_san LIKE '%TEST%' OR ma_tai_san LIKE '%TRA1%')");
    $db->query("DELETE FROM phieu_muon WHERE ghi_chu LIKE '%Test%' OR nguoi_muon_id IN (SELECT id FROM nguoi_muon WHERE ma_so_truong LIKE '%TEST%' OR ma_so_truong LIKE '%TRA%')");
    $db->query("DELETE FROM chi_tiet_dieu_chuyen WHERE dieu_chuyen_id IN (SELECT id FROM dieu_chuyen WHERE ly_do LIKE '%Test%')");
    $db->query("DELETE FROM dieu_chuyen WHERE ly_do LIKE '%Test%'");
    $db->query("DELETE FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id IN (SELECT id FROM dot_kiem_ke WHERE ten_dot_kiem_ke LIKE '%Test%')");
    $db->query("DELETE FROM dot_kiem_ke WHERE ten_dot_kiem_ke LIKE '%Test%'");
    $db->query("DELETE FROM su_co_tai_san WHERE mo_ta_su_co LIKE '%Test%'");
    $db->query("DELETE FROM tai_san WHERE ma_tai_san LIKE '%TEST%' OR ma_tai_san LIKE '%TRA1%' OR ma_tai_san LIKE '%KHACBAN%' OR ma_tai_san LIKE '%PANA%'");
    $db->query("DELETE FROM nguoi_muon WHERE ma_so_truong LIKE '%TEST%' OR ma_so_truong LIKE '%TRA%'");
    $db->query("DELETE FROM phong_hoc WHERE ma_phong LIKE '%TEST%' OR ma_phong LIKE '%DICH%'");
    $db->query("DELETE FROM loai_tai_san WHERE ma_loai LIKE '%TEST%'");
    $db->query("DELETE FROM phong_truc WHERE ma_phong_truc LIKE '%TEST%'");
    echo "  [CLEANUP] Đã dọn sạch các bản ghi kiểm thử tạm thời (bảo toàn toàn bộ dữ liệu người dùng tạo).\n";
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
