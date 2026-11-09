<?php
/**
 * Dịch vụ nghiệp vụ Báo cáo Thống kê & Phân tích Dữ liệu Chart.js (G10).
 * Cung cấp số liệu tổng hợp, dữ liệu chuỗi thời gian, phân tích cơ cấu tài sản,
 * hiệu suất mượn trả, phân loại đối tượng và thống kê sự cố.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/danh-muc-service.php';

/**
 * Thống kê các chỉ số tổng quan (KPIs) của hệ thống hoặc theo bàn trực.
 *
 * @param int|null $phongTrucId
 * @return array
 */
function thong_ke_tong_quan(?int $phongTrucId = null): array {
    $db = db();

    $whereTs = "1=1";
    $wherePm = "1=1";
    $whereSc = "1=1";
    $whereDk = "1=1";

    if ($phongTrucId !== null && $phongTrucId > 0) {
        $ptId = (int)$phongTrucId;
        $whereTs = "ts.phong_truc_id = $ptId";
        $wherePm = "pm.phong_truc_id = $ptId";
        $whereSc = "ts.phong_truc_id = $ptId";
        $whereDk = "dk.phong_truc_id = $ptId";
    }

    // 1. Thống kê tài sản
    $sqlTs = "SELECT 
        COUNT(*) AS tong_tai_san,
        SUM(CASE WHEN ts.trang_thai = 'SAN_SANG' THEN 1 ELSE 0 END) AS san_sang,
        SUM(CASE WHEN ts.trang_thai = 'DANG_MUON' THEN 1 ELSE 0 END) AS dang_muon,
        SUM(CASE WHEN ts.trang_thai = 'BAO_TRI' THEN 1 ELSE 0 END) AS bao_tri,
        SUM(CASE WHEN ts.trang_thai = 'NGUNG_SU_DUNG' THEN 1 ELSE 0 END) AS ngung_su_dung,
        SUM(CASE WHEN ts.tinh_trang = 'HU_HONG' THEN 1 ELSE 0 END) AS hu_hong
    FROM tai_san ts WHERE $whereTs";
    $rowTs = $db->query($sqlTs)->fetch_assoc();

    $tongTaiSan = (int)($rowTs['tong_tai_san'] ?? 0);
    $dangMuon = (int)($rowTs['dang_muon'] ?? 0);
    $sanSang = (int)($rowTs['san_sang'] ?? 0);
    $baoTri = (int)($rowTs['bao_tri'] ?? 0);
    $ngungSuDung = (int)($rowTs['ngung_su_dung'] ?? 0);
    $huHong = (int)($rowTs['hu_hong'] ?? 0);

    $tyLeSuDung = $tongTaiSan > 0 ? round(($dangMuon / $tongTaiSan) * 100, 1) : 0.0;

    // 2. Thống kê mượn trả
    $sqlPm = "SELECT 
        COUNT(*) AS tong_luot_muon,
        SUM(CASE WHEN pm.trang_thai = 'DANG_MUON' THEN 1 ELSE 0 END) AS phieu_dang_muon,
        SUM(CASE WHEN pm.trang_thai = 'DANG_MUON' AND pm.han_tra < NOW() THEN 1 ELSE 0 END) AS phieu_qua_han,
        SUM(CASE WHEN pm.thoi_gian_muon >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS luot_muon_30_ngay,
        SUM(CASE WHEN pm.thoi_gian_muon >= DATE_FORMAT(NOW(), '%Y-%m-01 00:00:00') THEN 1 ELSE 0 END) AS luot_muon_thang_nay
    FROM phieu_muon pm WHERE $wherePm";
    $rowPm = $db->query($sqlPm)->fetch_assoc();

    // 3. Thống kê sự cố
    $sqlSc = "SELECT 
        COUNT(*) AS tong_su_co,
        SUM(CASE WHEN sc.trang_thai = 'CHUA_XU_LY' THEN 1 ELSE 0 END) AS su_co_chua_xu_ly,
        SUM(CASE WHEN sc.trang_thai = 'DANG_XU_LY' THEN 1 ELSE 0 END) AS su_co_dang_xu_ly,
        SUM(CASE WHEN sc.trang_thai = 'DA_XU_LY' THEN 1 ELSE 0 END) AS su_co_da_xu_ly
    FROM su_co_tai_san sc 
    JOIN tai_san ts ON sc.tai_san_id = ts.id 
    WHERE $whereSc";
    $rowSc = $db->query($sqlSc)->fetch_assoc();

    // 4. Thống kê kiểm kê
    $sqlDk = "SELECT 
        COUNT(*) AS tong_dot_kiem_ke,
        SUM(CASE WHEN dk.trang_thai = 'DANG_KIEM_KE' THEN 1 ELSE 0 END) AS dot_dang_mo,
        SUM(CASE WHEN dk.trang_thai = 'HOAN_TAT' THEN 1 ELSE 0 END) AS dot_hoan_tat
    FROM dot_kiem_ke dk WHERE $whereDk";
    $rowDk = $db->query($sqlDk)->fetch_assoc();

    return [
        'tong_tai_san'       => $tongTaiSan,
        'tai_san_san_sang'   => $sanSang,
        'tai_san_dang_muon'  => $dangMuon,
        'tai_san_bao_tri'    => $baoTri,
        'tai_san_ngung_sd'   => $ngungSuDung,
        'tai_san_hu_hong'    => $huHong,
        'ty_le_su_dung'      => $tyLeSuDung,
        'tong_luot_muon'     => (int)($rowPm['tong_luot_muon'] ?? 0),
        'phieu_dang_muon'    => (int)($rowPm['phieu_dang_muon'] ?? 0),
        'phieu_qua_han'      => (int)($rowPm['phieu_qua_han'] ?? 0),
        'luot_muon_30_ngay'  => (int)($rowPm['luot_muon_30_ngay'] ?? 0),
        'luot_muon_thang_nay'=> (int)($rowPm['luot_muon_thang_nay'] ?? 0),
        'tong_su_co'         => (int)($rowSc['tong_su_co'] ?? 0),
        'su_co_chua_xu_ly'   => (int)($rowSc['su_co_chua_xu_ly'] ?? 0),
        'su_co_dang_xu_ly'   => (int)($rowSc['su_co_dang_xu_ly'] ?? 0),
        'su_co_da_xu_ly'     => (int)($rowSc['su_co_da_xu_ly'] ?? 0),
        'tong_dot_kiem_ke'   => (int)($rowDk['tong_dot_kiem_ke'] ?? 0),
        'dot_dang_mo'        => (int)($rowDk['dot_dang_mo'] ?? 0),
        'dot_hoan_tat'       => (int)($rowDk['dot_hoan_tat'] ?? 0),
    ];
}

/**
 * Thống kê xu hướng mượn và trả thiết bị theo chuỗi thời gian (Chart.js Line/Bar Chart).
 *
 * @param string $khoang '7_ngay', '30_ngay', hoặc '12_thang'
 * @param int|null $phongTrucId
 * @return array ['labels' => array, 'so_luot_muon' => array, 'so_luot_tra' => array, 'tong_muon' => int, 'tong_tra' => int]
 */
function thong_ke_muon_tra_theo_thoi_gian(string $khoang = '7_ngay', ?int $phongTrucId = null): array {
    $db = db();

    $wherePm = "1=1";
    $whereCtm = "1=1";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $ptId = (int)$phongTrucId;
        $wherePm = "pm.phong_truc_id = $ptId";
        $whereCtm = "ts.phong_truc_id = $ptId";
    }

    $labels = [];
    $dataMuonMap = [];
    $dataTraMap = [];

    if ($khoang === '12_thang') {
        // 12 tháng gần nhất
        for ($i = 11; $i >= 0; $i--) {
            $thoiDiem = strtotime("-$i months");
            $keyYm = date('Y-m', $thoiDiem);
            $lbl = date('m/Y', $thoiDiem);
            $labels[] = $lbl;
            $dataMuonMap[$keyYm] = 0;
            $dataTraMap[$keyYm] = 0;
        }

        // Lấy lượt mượn theo tháng
        $sqlM = "SELECT DATE_FORMAT(pm.thoi_gian_muon, '%Y-%m') AS ym, COUNT(*) AS so_luong 
                 FROM phieu_muon pm 
                 WHERE pm.thoi_gian_muon >= DATE_SUB(NOW(), INTERVAL 12 MONTH) AND $wherePm
                 GROUP BY DATE_FORMAT(pm.thoi_gian_muon, '%Y-%m')";
        $resM = $db->query($sqlM);
        while ($row = $resM->fetch_assoc()) {
            if (isset($dataMuonMap[$row['ym']])) {
                $dataMuonMap[$row['ym']] = (int)$row['so_luong'];
            }
        }

        // Lấy lượt trả thiết bị theo tháng
        $sqlT = "SELECT DATE_FORMAT(ctm.thoi_gian_tra, '%Y-%m') AS ym, COUNT(*) AS so_luong 
                 FROM chi_tiet_muon ctm 
                 JOIN tai_san ts ON ctm.tai_san_id = ts.id
                 WHERE ctm.thoi_gian_tra IS NOT NULL 
                   AND ctm.thoi_gian_tra >= DATE_SUB(NOW(), INTERVAL 12 MONTH) 
                   AND $whereCtm
                 GROUP BY DATE_FORMAT(ctm.thoi_gian_tra, '%Y-%m')";
        $resT = $db->query($sqlT);
        while ($row = $resT->fetch_assoc()) {
            if (isset($dataTraMap[$row['ym']])) {
                $dataTraMap[$row['ym']] = (int)$row['so_luong'];
            }
        }
    } else {
        // Mặc định theo ngày (7 ngày hoặc 30 ngày)
        $soNgay = ($khoang === '30_ngay') ? 30 : 7;
        for ($i = $soNgay - 1; $i >= 0; $i--) {
            $thoiDiem = strtotime("-$i days");
            $keyYmd = date('Y-m-d', $thoiDiem);
            $lbl = date('d/m', $thoiDiem);
            $labels[] = $lbl;
            $dataMuonMap[$keyYmd] = 0;
            $dataTraMap[$keyYmd] = 0;
        }

        // Lượt mượn
        $sqlM = "SELECT DATE(pm.thoi_gian_muon) AS ngay, COUNT(*) AS so_luong 
                 FROM phieu_muon pm 
                 WHERE pm.thoi_gian_muon >= DATE_SUB(CURDATE(), INTERVAL $soNgay DAY) AND $wherePm
                 GROUP BY DATE(pm.thoi_gian_muon)";
        $resM = $db->query($sqlM);
        while ($row = $resM->fetch_assoc()) {
            if (isset($dataMuonMap[$row['ngay']])) {
                $dataMuonMap[$row['ngay']] = (int)$row['so_luong'];
            }
        }

        // Lượt trả thiết bị
        $sqlT = "SELECT DATE(ctm.thoi_gian_tra) AS ngay, COUNT(*) AS so_luong 
                 FROM chi_tiet_muon ctm 
                 JOIN tai_san ts ON ctm.tai_san_id = ts.id
                 WHERE ctm.thoi_gian_tra IS NOT NULL 
                   AND ctm.thoi_gian_tra >= DATE_SUB(CURDATE(), INTERVAL $soNgay DAY) 
                   AND $whereCtm
                 GROUP BY DATE(ctm.thoi_gian_tra)";
        $resT = $db->query($sqlT);
        while ($row = $resT->fetch_assoc()) {
            if (isset($dataTraMap[$row['ngay']])) {
                $dataTraMap[$row['ngay']] = (int)$row['so_luong'];
            }
        }
    }

    $soLuotMuon = array_values($dataMuonMap);
    $soLuotTra = array_values($dataTraMap);

    return [
        'labels'       => $labels,
        'so_luot_muon' => $soLuotMuon,
        'so_luot_tra'  => $soLuotTra,
        'tong_muon'    => array_sum($soLuotMuon),
        'tong_tra'     => array_sum($soLuotTra),
    ];
}

/**
 * Thống kê số lượng tài sản phân bổ theo từng loại (Doughnut / Bar Chart).
 *
 * @param int|null $phongTrucId
 * @return array
 */
function thong_ke_tai_san_theo_loai(?int $phongTrucId = null): array {
    $db = db();
    $whereTs = "1=1";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $whereTs = "ts.phong_truc_id = " . (int)$phongTrucId;
    }

    $sql = "SELECT lts.id, lts.ten_loai, lts.ma_loai,
                   COUNT(ts.id) AS tong_so,
                   SUM(CASE WHEN ts.trang_thai = 'SAN_SANG' THEN 1 ELSE 0 END) AS san_sang,
                   SUM(CASE WHEN ts.trang_thai = 'DANG_MUON' THEN 1 ELSE 0 END) AS dang_muon,
                   SUM(CASE WHEN ts.trang_thai = 'BAO_TRI' THEN 1 ELSE 0 END) AS bao_tri,
                   SUM(CASE WHEN ts.trang_thai = 'NGUNG_SU_DUNG' THEN 1 ELSE 0 END) AS ngung_su_dung
            FROM loai_tai_san lts
            LEFT JOIN tai_san ts ON lts.id = ts.loai_tai_san_id AND $whereTs
            GROUP BY lts.id, lts.ten_loai, lts.ma_loai
            HAVING tong_so > 0
            ORDER BY tong_so DESC";

    $res = $db->query($sql);
    $rows = $res->fetch_all(MYSQLI_ASSOC);

    $labels = [];
    $soLuong = [];
    foreach ($rows as $r) {
        $labels[] = $r['ten_loai'];
        $soLuong[] = (int)$r['tong_so'];
    }

    return [
        'labels'   => $labels,
        'so_luong' => $soLuong,
        'chi_tiet' => $rows,
    ];
}

/**
 * Thống kê tài sản theo 4 trạng thái hoạt động (Doughnut Chart).
 *
 * @param int|null $phongTrucId
 * @return array
 */
function thong_ke_tai_san_theo_trang_thai(?int $phongTrucId = null): array {
    $db = db();
    $whereTs = "1=1";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $whereTs = "phong_truc_id = " . (int)$phongTrucId;
    }

    $sql = "SELECT 
        SUM(CASE WHEN trang_thai = 'SAN_SANG' THEN 1 ELSE 0 END) AS san_sang,
        SUM(CASE WHEN trang_thai = 'DANG_MUON' THEN 1 ELSE 0 END) AS dang_muon,
        SUM(CASE WHEN trang_thai = 'BAO_TRI' THEN 1 ELSE 0 END) AS bao_tri,
        SUM(CASE WHEN trang_thai = 'NGUNG_SU_DUNG' THEN 1 ELSE 0 END) AS ngung_su_dung
    FROM tai_san WHERE $whereTs";
    $r = $db->query($sql)->fetch_assoc();

    return [
        'labels' => ['Sẵn sàng', 'Đang cho mượn', 'Bảo trì / Hư hỏng', 'Ngừng sử dụng'],
        'data'   => [
            (int)($r['san_sang'] ?? 0),
            (int)($r['dang_muon'] ?? 0),
            (int)($r['bao_tri'] ?? 0),
            (int)($r['ngung_su_dung'] ?? 0),
        ],
        'colors' => ['#198754', '#ffc107', '#dc3545', '#6c757d'],
    ];
}

/**
 * Top phòng học sử dụng mượn thiết bị nhiều nhất.
 *
 * @param int $limit
 * @param int|null $phongTrucId
 * @return array
 */
function thong_ke_top_phong_hoc(int $limit = 5, ?int $phongTrucId = null): array {
    $db = db();
    $wherePm = "1=1";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $wherePm = "pm.phong_truc_id = " . (int)$phongTrucId;
    }

    $sql = "SELECT ph.ma_phong, ph.ten_phong, ph.toa_nha, COUNT(pm.id) AS so_luot_muon
            FROM phong_hoc ph
            JOIN phieu_muon pm ON ph.id = pm.phong_hoc_id
            WHERE $wherePm
            GROUP BY ph.id, ph.ma_phong, ph.ten_phong, ph.toa_nha
            ORDER BY so_luot_muon DESC
            LIMIT ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $labels = [];
    $data = [];
    foreach ($rows as $r) {
        $labels[] = $r['ma_phong'];
        $data[] = (int)$r['so_luot_muon'];
    }

    return [
        'labels'   => $labels,
        'data'     => $data,
        'danh_sach'=> $rows,
    ];
}

/**
 * Top thiết bị có tần suất mượn cao nhất (thiết bị được sử dụng nhiều nhất).
 *
 * @param int $limit
 * @param int|null $phongTrucId
 * @return array
 */
function thong_ke_top_tai_san_muon(int $limit = 5, ?int $phongTrucId = null): array {
    $db = db();
    $whereTs = "1=1";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $whereTs = "ts.phong_truc_id = " . (int)$phongTrucId;
    }

    $sql = "SELECT ts.id, ts.ma_tai_san, ts.ten_tai_san, lts.ten_loai, COUNT(ctm.id) AS so_luot_muon
            FROM tai_san ts
            JOIN chi_tiet_muon ctm ON ts.id = ctm.tai_san_id
            LEFT JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
            WHERE $whereTs
            GROUP BY ts.id, ts.ma_tai_san, ts.ten_tai_san, lts.ten_loai
            ORDER BY so_luot_muon DESC
            LIMIT ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $labels = [];
    $data = [];
    foreach ($rows as $r) {
        $labels[] = $r['ma_tai_san'];
        $data[] = (int)$r['so_luot_muon'];
    }

    return [
        'labels'   => $labels,
        'data'     => $data,
        'danh_sach'=> $rows,
    ];
}

/**
 * Thống kê phân loại đối tượng mượn: Giảng viên vs Sinh viên (Doughnut Chart).
 *
 * @param int|null $phongTrucId
 * @return array
 */
function thong_ke_doi_tuong_muon(?int $phongTrucId = null): array {
    $db = db();
    $wherePm = "1=1";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $wherePm = "pm.phong_truc_id = " . (int)$phongTrucId;
    }

    $sql = "SELECT 
        SUM(CASE WHEN nm.loai_nguoi_muon = 'GIANG_VIEN' THEN 1 ELSE 0 END) AS giang_vien,
        SUM(CASE WHEN nm.loai_nguoi_muon = 'SINH_VIEN' THEN 1 ELSE 0 END) AS sinh_vien
    FROM phieu_muon pm
    JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
    WHERE $wherePm";

    $r = $db->query($sql)->fetch_assoc();
    $gv = (int)($r['giang_vien'] ?? 0);
    $sv = (int)($r['sinh_vien'] ?? 0);

    return [
        'labels' => ['Giảng viên', 'Sinh viên'],
        'data'   => [$gv, $sv],
        'colors' => ['#0d6efd', '#20c997'],
    ];
}

/**
 * Thống kê sự cố thiết bị theo loại và trạng thái xử lý.
 *
 * @param int|null $phongTrucId
 * @return array
 */
function thong_ke_su_co_chi_tiet(?int $phongTrucId = null): array {
    $db = db();
    $whereSc = "1=1";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $whereSc = "ts.phong_truc_id = " . (int)$phongTrucId;
    }

    $sql = "SELECT 
        SUM(CASE WHEN sc.loai_su_co = 'HU_HONG' THEN 1 ELSE 0 END) AS hu_hong,
        SUM(CASE WHEN sc.loai_su_co = 'MAT' THEN 1 ELSE 0 END) AS mat,
        SUM(CASE WHEN sc.loai_su_co = 'SAI_VI_TRI' THEN 1 ELSE 0 END) AS sai_vi_tri,
        SUM(CASE WHEN sc.trang_thai = 'CHUA_XU_LY' THEN 1 ELSE 0 END) AS chua_xu_ly,
        SUM(CASE WHEN sc.trang_thai = 'DANG_XU_LY' THEN 1 ELSE 0 END) AS dang_xu_ly,
        SUM(CASE WHEN sc.trang_thai = 'DA_XU_LY' THEN 1 ELSE 0 END) AS da_xu_ly
    FROM su_co_tai_san sc
    JOIN tai_san ts ON sc.tai_san_id = ts.id
    WHERE $whereSc";

    $r = $db->query($sql)->fetch_assoc();

    return [
        'theo_loai' => [
            'labels' => ['Hư hỏng', 'Mất / Thất lạc', 'Sai vị trí'],
            'data'   => [
                (int)($r['hu_hong'] ?? 0),
                (int)($r['mat'] ?? 0),
                (int)($r['sai_vi_tri'] ?? 0),
            ],
            'colors' => ['#dc3545', '#212529', '#fd7e14'],
        ],
        'theo_trang_thai' => [
            'labels' => ['Chưa xử lý', 'Đang xử lý', 'Đã xử lý xong'],
            'data'   => [
                (int)($r['chua_xu_ly'] ?? 0),
                (int)($r['dang_xu_ly'] ?? 0),
                (int)($r['da_xu_ly'] ?? 0),
            ],
            'colors' => ['#dc3545', '#ffc107', '#198754'],
        ],
    ];
}

/**
 * Lấy danh sách hoạt động mượn trả gần đây cho Bảng điều khiển (Dashboard feed).
 *
 * @param int $limit
 * @param int|null $phongTrucId
 * @return array
 */
function thong_ke_hoat_dong_gan_day(int $limit = 8, ?int $phongTrucId = null): array {
    $db = db();
    $where = "1=1";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $where = "pm.phong_truc_id = " . (int)$phongTrucId;
    }

    $sql = "SELECT pm.id, pm.ma_phieu_muon, pm.thoi_gian_muon, pm.han_tra, pm.trang_thai,
                   nm.ho_ten AS ho_ten_nguoi_muon, nm.loai_nguoi_muon,
                   ph.ma_phong,
                   nv.ho_ten AS ho_ten_nhan_vien,
                   (SELECT COUNT(*) FROM chi_tiet_muon WHERE phieu_muon_id = pm.id) AS so_thiet_bi
            FROM phieu_muon pm
            JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
            JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
            JOIN nhan_vien nv ON pm.nhan_vien_lap_id = nv.id
            WHERE $where
            ORDER BY pm.thoi_gian_muon DESC, pm.id DESC
            LIMIT ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}
