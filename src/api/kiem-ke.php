<?php
/**
 * API JSON Phục vụ thao tác kiểm kê nhanh: tra cứu mã QR và ghi nhận kết quả trực tiếp.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/kiem-ke-service.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';

yeu_cau_dang_nhap();
yeu_cau_mot_trong_cac_quyen(['thuc_hien_kiem_ke', 'mo_dot_kiem_ke']);

$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

$hanhDong = trim((string)($_REQUEST['hanh_dong'] ?? 'tra_cuu'));

if ($hanhDong === 'tra_cuu') {
    $maQr = trim((string)($_GET['ma_qr'] ?? $_GET['ma'] ?? $_GET['code'] ?? $_GET['tu_khoa'] ?? ''));
    $maTaiSan = trim((string)($_GET['ma_tai_san'] ?? ''));
    $dotId = doc_so_nguyen('dot_kiem_ke_id', 0, $_GET);

    if ($dotId <= 0) {
        phan_hoi_json(['thanh_cong' => false, 'loi' => 'Thiếu ID đợt kiểm kê.', 'thong_diep' => 'Thiếu ID đợt kiểm kê.'], 400);
    }

    $taiSan = null;
    if ($maQr !== '') {
        $taiSan = tai_san_tim_theo_qr($maQr) ?? tai_san_tim_theo_ma($maQr);
    } elseif ($maTaiSan !== '') {
        $taiSan = tai_san_tim_theo_ma($maTaiSan);
    }

    if ($taiSan === null) {
        phan_hoi_json(['thanh_cong' => false, 'loi' => 'Không tìm thấy thiết bị trong hệ thống.', 'thong_diep' => 'Không tìm thấy thiết bị trong hệ thống.'], 404);
    }

    // Tìm bản ghi chi tiết kiểm kê của tài sản này trong đợt
    $db = db();
    $stmt = $db->prepare("SELECT ct.*, ct.ket_qua AS ket_qua_kiem_ke,
                                 ts.ma_tai_san, ts.ma_qr, ts.ten_tai_san,
                                 ps.ma_phong AS ma_phong_so_sach, ps.ten_phong AS ten_phong_so_sach,
                                 pt.ma_phong AS ma_phong_thuc_te, pt.ten_phong AS ten_phong_thuc_te
                          FROM chi_tiet_kiem_ke ct
                          JOIN tai_san ts ON ct.tai_san_id = ts.id
                          LEFT JOIN phong_hoc ps ON ct.phong_hoc_so_sach_id = ps.id
                          LEFT JOIN phong_hoc pt ON ct.phong_hoc_thuc_te_id = pt.id
                          WHERE ct.dot_kiem_ke_id = ? AND ct.tai_san_id = ? LIMIT 1");
    $stmt->bind_param('ii', $dotId, $taiSan['id']);
    $stmt->execute();
    $chiTiet = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$chiTiet) {
        // Tự động bổ sung thiết bị hợp lệ vào đợt kiểm kê hiện tại để đối chiếu ngay khi quét QR
        $ttSoSach = ($taiSan['trang_thai'] === 'DANG_MUON') ? 'DANG_MUON' : ($taiSan['trang_thai'] ?? 'SAN_SANG');
        $phSoSach = !empty($taiSan['phong_hoc_hien_tai_id']) ? (int)$taiSan['phong_hoc_hien_tai_id'] : null;
        $stmtIns = $db->prepare("INSERT INTO chi_tiet_kiem_ke (dot_kiem_ke_id, tai_san_id, tinh_trang_so_sach, trang_thai_so_sach, phong_hoc_so_sach_id) VALUES (?, ?, ?, ?, ?)");
        $stmtIns->bind_param('iissi', $dotId, $taiSan['id'], $taiSan['tinh_trang'], $ttSoSach, $phSoSach);
        $stmtIns->execute();
        $stmtIns->close();

        $stmt2 = $db->prepare("SELECT ct.*, ct.ket_qua AS ket_qua_kiem_ke,
                                     ts.ma_tai_san, ts.ma_qr, ts.ten_tai_san,
                                     ps.ma_phong AS ma_phong_so_sach, ps.ten_phong AS ten_phong_so_sach,
                                     pt.ma_phong AS ma_phong_thuc_te, pt.ten_phong AS ten_phong_thuc_te
                              FROM chi_tiet_kiem_ke ct
                              JOIN tai_san ts ON ct.tai_san_id = ts.id
                              LEFT JOIN phong_hoc ps ON ct.phong_hoc_so_sach_id = ps.id
                              LEFT JOIN phong_hoc pt ON ct.phong_hoc_thuc_te_id = pt.id
                              WHERE ct.dot_kiem_ke_id = ? AND ct.tai_san_id = ? LIMIT 1");
        $stmt2->bind_param('ii', $dotId, $taiSan['id']);
        $stmt2->execute();
        $chiTiet = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
    }

    phan_hoi_json([
        'thanh_cong' => true,
        'tai_san'    => $taiSan,
        'chi_tiet'   => $chiTiet,
    ]);
} elseif ($hanhDong === 'ghi_nhan') {
    if (!la_post()) {
        phan_hoi_json(['thanh_cong' => false, 'loi' => 'Phương thức không hợp lệ.'], 405);
    }
    if (!csrf_kiem_tra()) {
        phan_hoi_json(['thanh_cong' => false, 'loi' => 'Phiên làm việc bảo mật đã hết hạn. Vui lòng tải lại trang.'], 403);
    }

    $ketQua = kiem_ke_ghi_nhan($_POST, (int)$nguoiDung['id'], $phongTrucId);
    if ($ketQua['thanh_cong']) {
        // Lấy lại thống kê mới nhất
        $dotId = doc_so_nguyen('dot_kiem_ke_id', 0, $_POST);
        $dot = kiem_ke_tim_theo_id($dotId, $phongTrucId > 0 ? $phongTrucId : null);

        phan_hoi_json([
            'thanh_cong' => true,
            'thong_ke'   => $dot['thong_ke'] ?? [],
        ]);
    } else {
        phan_hoi_json([
            'thanh_cong' => false,
            'loi'        => $ketQua['loi'],
        ], 422);
    }
}

phan_hoi_json(['thanh_cong' => false, 'loi' => 'Hành động không hợp lệ.'], 400);
