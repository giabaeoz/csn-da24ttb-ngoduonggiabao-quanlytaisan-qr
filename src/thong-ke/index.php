<?php

/**
 * Controller: Tra cứu, thống kê (Bám sát Trang tra cứu - thống kê.pdf)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/thong-ke-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';
require_once dirname(__DIR__) . '/app/services/muon-tra-service.php';
require_once dirname(__DIR__) . '/app/services/dieu-chuyen-service.php';
require_once dirname(__DIR__) . '/app/services/kiem-ke-service.php';

yeu_cau_dang_nhap();
yeu_cau_quyen('xem_thong_ke');

$db = db();
$nguoiDung = nguoi_dung_hien_tai();
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);

$phongTrucFilter = null;
if ($nguoiDung['vai_tro'] === VAI_TRO_NHAN_VIEN && $phongTrucId > 0) {
    $phongTrucFilter = $phongTrucId;
} else {
    $reqPt = doc_so_nguyen('phong_truc_id', 0, $_GET);
    $phongTrucFilter = $reqPt > 0 ? $reqPt : null;
}

$tab = trim((string)($_GET['tab'] ?? 'muon-tra'));
if (!in_array($tab, ['muon-tra', 'dieu-chuyen', 'kiem-ke'], true)) {
    $tab = 'muon-tra';
}

$tuKhoa = trim((string)($_GET['tu_khoa'] ?? ''));
$phongHocId = doc_so_nguyen('phong_hoc_id', 0, $_GET);
$trangThai = trim((string)($_GET['trang_thai'] ?? ''));
$tuNgay = trim((string)($_GET['tu_ngay'] ?? ''));
$denNgay = trim((string)($_GET['den_ngay'] ?? ''));
$trang = max(1, doc_so_nguyen('trang', 1, $_GET));
$soMoiTrang = 100; // Hiển thị danh sách toàn bộ kết quả tra cứu

$danhSachPhongHoc = phong_hoc_danh_sach(true);
$danhSachPhongTruc = phong_truc_danh_sach(true);

// =========================================================================
// TAB 1: MƯỢN - TRẢ (Khớp chính xác Trang tra cứu - thống kê.pdf)
// =========================================================================
if ($tab === 'muon-tra') {
    $where = " WHERE 1=1";
    $params = [];
    $types = '';

    if ($phongTrucFilter !== null) {
        $where .= " AND pm.phong_truc_id = ?";
        $params[] = $phongTrucFilter;
        $types .= 'i';
    }

    if ($tuKhoa !== '') {
        $kw = '%' . $tuKhoa . '%';
        $where .= " AND (pm.ma_phieu_muon LIKE ? OR nm.ma_so_truong LIKE ? OR nm.ho_ten LIKE ? OR ph.ma_phong LIKE ? OR EXISTS (
            SELECT 1 FROM chi_tiet_muon ctm_kw 
            JOIN tai_san ts_kw ON ctm_kw.tai_san_id = ts_kw.id 
            WHERE ctm_kw.phieu_muon_id = pm.id AND (ts_kw.ma_tai_san LIKE ? OR ts_kw.ten_tai_san LIKE ?)
        ))";
        $params = array_merge($params, [$kw, $kw, $kw, $kw, $kw, $kw]);
        $types .= 'ssssss';
    }

    if ($phongHocId > 0) {
        $where .= " AND pm.phong_hoc_id = ?";
        $params[] = $phongHocId;
        $types .= 'i';
    }

    if ($tuNgay !== '') {
        $where .= " AND DATE(pm.thoi_gian_muon) >= ?";
        $params[] = $tuNgay;
        $types .= 's';
    }

    if ($denNgay !== '') {
        $where .= " AND DATE(pm.thoi_gian_muon) <= ?";
        $params[] = $denNgay;
        $types .= 's';
    }

    if ($trangThai !== '' && $trangThai !== 'TAT_CA') {
        if ($trangThai === 'DA_TRA') {
            $where .= " AND pm.trang_thai = 'DA_TRA'";
        } elseif ($trangThai === 'QUA_HAN') {
            $where .= " AND pm.trang_thai = 'DANG_MUON' AND pm.han_tra < NOW()";
        } elseif ($trangThai === 'TRA_MOT_PHAN') {
            $where .= " AND pm.trang_thai = 'DANG_MUON' AND EXISTS (
                SELECT 1 FROM chi_tiet_muon ctm_p WHERE ctm_p.phieu_muon_id = pm.id AND ctm_p.thoi_gian_tra IS NOT NULL
            ) AND EXISTS (
                SELECT 1 FROM chi_tiet_muon ctm_u WHERE ctm_u.phieu_muon_id = pm.id AND ctm_u.thoi_gian_tra IS NULL
            )";
        } elseif ($trangThai === 'DANG_MUON') {
            $where .= " AND pm.trang_thai = 'DANG_MUON' AND (pm.han_tra >= NOW() OR pm.han_tra IS NULL) AND NOT EXISTS (
                SELECT 1 FROM chi_tiet_muon ctm_p WHERE ctm_p.phieu_muon_id = pm.id AND ctm_p.thoi_gian_tra IS NOT NULL
            )";
        }
    }

    // Xử lý xuất Excel
    if (isset($_GET['xuat']) && $_GET['xuat'] === 'excel') {
        $sqlExport = "SELECT pm.ma_phieu_muon, nm.ma_so_truong, nm.ho_ten, ph.ma_phong,
            COUNT(ctm.id) AS so_thiet_bi,
            pm.thoi_gian_muon, pm.han_tra, pm.trang_thai,
            SUM(CASE WHEN ctm.thoi_gian_tra IS NULL THEN 1 ELSE 0 END) AS so_chua_tra
        FROM phieu_muon pm
        JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
        JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
        LEFT JOIN chi_tiet_muon ctm ON ctm.phieu_muon_id = pm.id
        $where
        GROUP BY pm.id, pm.ma_phieu_muon, nm.ma_so_truong, nm.ho_ten, ph.ma_phong, pm.thoi_gian_muon, pm.han_tra, pm.trang_thai
        ORDER BY pm.id DESC";

        if (!empty($params)) {
            $stmtExp = $db->prepare($sqlExport);
            $stmtExp->bind_param($types, ...$params);
            $stmtExp->execute();
            $resExp = $stmtExp->get_result();
        } else {
            $resExp = $db->query($sqlExport);
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=tra-cuu-thong-ke-' . date('Ymd-His') . '.csv');
        echo "\xEF\xBB\xBF";
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Mã phiếu', 'Mã số', 'Họ tên người mượn', 'Phòng', 'Số lượng thiết bị', 'Mượn lúc', 'Hạn trả', 'Trạng thái']);
        while ($r = $resExp->fetch_assoc()) {
            $tt = 'Đang mượn';
            if ($r['trang_thai'] === 'DA_TRA') {
                $tt = 'Đã trả đủ';
            } elseif (strtotime((string)$r['han_tra']) < time()) {
                $tt = 'Quá hạn';
            } elseif ((int)$r['so_chua_tra'] < (int)$r['so_thiet_bi'] && (int)$r['so_chua_tra'] > 0) {
                $tt = 'Trả một phần (Còn ' . (int)$r['so_chua_tra'] . ' thiết bị)';
            }
            fputcsv($out, [
                $r['ma_phieu_muon'],
                $r['ma_so_truong'],
                $r['ho_ten'],
                $r['ma_phong'],
                $r['so_thiet_bi'],
                $r['thoi_gian_muon'],
                $r['han_tra'],
                $tt
            ]);
        }
        fclose($out);
        exit;
    }

    // 4 Thẻ chỉ số KPI
    $sqlKpi = "SELECT 
        COUNT(DISTINCT pm.id) AS tong_phieu,
        COALESCE(SUM(sub.so_thiet_bi), 0) AS tong_thiet_bi,
        SUM(CASE WHEN pm.trang_thai = 'DA_TRA' THEN 1 ELSE 0 END) AS phieu_da_tra_du,
        SUM(CASE WHEN pm.trang_thai = 'DANG_MUON' THEN 1 ELSE 0 END) AS phieu_chua_tra_du
    FROM phieu_muon pm
    JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
    JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
    LEFT JOIN (
        SELECT phieu_muon_id, COUNT(*) AS so_thiet_bi 
        FROM chi_tiet_muon 
        GROUP BY phieu_muon_id
    ) sub ON sub.phieu_muon_id = pm.id
    $where";

    if (!empty($params)) {
        $stmtKpi = $db->prepare($sqlKpi);
        $stmtKpi->bind_param($types, ...$params);
        $stmtKpi->execute();
        $rowKpi = $stmtKpi->get_result()->fetch_assoc();
        $stmtKpi->close();
    } else {
        $rowKpi = $db->query($sqlKpi)->fetch_assoc();
    }

    $kpiPhieuMuon = (int)($rowKpi['tong_phieu'] ?? 0);
    $kpiThietBi = (int)($rowKpi['tong_thiet_bi'] ?? 0);
    $kpiDaTraDu = (int)($rowKpi['phieu_da_tra_du'] ?? 0);
    $kpiChuaTraDu = (int)($rowKpi['phieu_chua_tra_du'] ?? 0);

    // Biểu đồ 1: Mượn - trả theo ngày (3 ngày gần nhất hoặc theo khoảng lọc)
    $chartLabels = [];
    $chartMuon = [];
    $chartTra = [];

    $ngayBatDau = !empty($tuNgay) ? strtotime($tuNgay) : strtotime("-2 days");
    $ngayKetThuc = !empty($denNgay) ? strtotime($denNgay) : time();
    if ($ngayKetThuc < $ngayBatDau) {
        $ngayKetThuc = $ngayBatDau;
    }
    // Giới hạn hiển thị tối đa 7 mốc ngày cho biểu đồ line
    $diffDays = (int)floor(($ngayKetThuc - $ngayBatDau) / 86400);
    if ($diffDays > 6) {
        $ngayBatDau = strtotime("-6 days", $ngayKetThuc);
    }

    for ($t = $ngayBatDau; $t <= $ngayKetThuc; $t += 86400) {
        $ymd = date('Y-m-d', $t);
        $lbl = date('d/m', $t);
        $chartLabels[] = $lbl;

        // Số phiếu mượn mới
        $sqlM = "SELECT COUNT(*) FROM phieu_muon pm WHERE DATE(pm.thoi_gian_muon) = '$ymd'" . ($phongTrucFilter ? " AND pm.phong_truc_id = $phongTrucFilter" : "");
        $chartMuon[] = (int)($db->query($sqlM)->fetch_row()[0] ?? 0);

        // Số phiếu có nhận trả
        $sqlT = "SELECT COUNT(DISTINCT ctm.phieu_muon_id) FROM chi_tiet_muon ctm 
                 JOIN tai_san ts ON ctm.tai_san_id = ts.id 
                 WHERE DATE(ctm.thoi_gian_tra) = '$ymd'" . ($phongTrucFilter ? " AND ts.phong_truc_id = $phongTrucFilter" : "");
        $chartTra[] = (int)($db->query($sqlT)->fetch_row()[0] ?? 0);
    }

    // Biểu đồ 2: Thiết bị cho mượn theo loại
    $sqlLoai = "SELECT COALESCE(lts.ten_loai, 'Khác') AS ten_loai, COUNT(ctm.id) AS so_luong
    FROM chi_tiet_muon ctm
    JOIN phieu_muon pm ON ctm.phieu_muon_id = pm.id
    JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
    JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
    JOIN tai_san ts ON ctm.tai_san_id = ts.id
    LEFT JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
    $where
    GROUP BY lts.id, lts.ten_loai
    ORDER BY so_luong DESC
    LIMIT 6";

    if (!empty($params)) {
        $stmtL = $db->prepare($sqlLoai);
        $stmtL->bind_param($types, ...$params);
        $stmtL->execute();
        $resL = $stmtL->get_result();
    } else {
        $resL = $db->query($sqlLoai);
    }

    $loaiLabels = [];
    $loaiData = [];
    while ($r = $resL->fetch_assoc()) {
        $loaiLabels[] = $r['ten_loai'];
        $loaiData[] = (int)$r['so_luong'];
    }
    if (empty($loaiLabels)) {
        $loaiLabels = ['Remote', 'Loa / micro', 'Cáp', 'Ổ cắm'];
        $loaiData = [0, 0, 0, 0];
    }
    $tongThietBiLoai = array_sum($loaiData);

    // Bảng danh sách kết quả tra cứu
    $sqlCount = "SELECT COUNT(DISTINCT pm.id)
    FROM phieu_muon pm
    JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
    JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
    $where";

    if (!empty($params)) {
        $stmtC = $db->prepare($sqlCount);
        $stmtC->bind_param($types, ...$params);
        $stmtC->execute();
        $tongSo = (int)($stmtC->get_result()->fetch_row()[0] ?? 0);
        $stmtC->close();
    } else {
        $tongSo = (int)($db->query($sqlCount)->fetch_row()[0] ?? 0);
    }

    $tongSoTrang = max(1, (int)ceil($tongSo / $soMoiTrang));
    $offset = ($trang - 1) * $soMoiTrang;

    $sqlList = "SELECT pm.id, pm.ma_phieu_muon, pm.thoi_gian_muon, pm.han_tra, pm.trang_thai,
        nm.ma_so_truong, nm.ho_ten AS ho_ten_nguoi_muon,
        ph.ma_phong,
        COUNT(ctm.id) AS so_thiet_bi,
        SUM(CASE WHEN ctm.thoi_gian_tra IS NULL THEN 1 ELSE 0 END) AS so_chua_tra
    FROM phieu_muon pm
    JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
    JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
    LEFT JOIN chi_tiet_muon ctm ON ctm.phieu_muon_id = pm.id
    $where
    GROUP BY pm.id, pm.ma_phieu_muon, pm.thoi_gian_muon, pm.han_tra, pm.trang_thai, nm.ma_so_truong, nm.ho_ten, ph.ma_phong
    ORDER BY pm.id DESC
    LIMIT ?, ?";

    $paramsList = $params;
    $typesList = $types . 'ii';
    $paramsList[] = $offset;
    $paramsList[] = $soMoiTrang;

    $stmtList = $db->prepare($sqlList);
    $stmtList->bind_param($typesList, ...$paramsList);
    $stmtList->execute();
    $danhSach = $stmtList->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtList->close();
}

// =========================================================================
// TAB 2: ĐIỀU CHUYỂN
// =========================================================================
elseif ($tab === 'dieu-chuyen') {
    $ketQuaDc = dieu_chuyen_danh_sach([
        'tu_khoa'       => $tuKhoa,
        'tu_ngay'       => $tuNgay,
        'den_ngay'      => $denNgay,
        'phong_truc_id' => $phongTrucFilter ?? 0,
    ], $trang, $soMoiTrang);
    $danhSachDc = $ketQuaDc['danh_sach'] ?? [];
    $tongSo = $ketQuaDc['tong_so'] ?? 0;
    $tongSoTrang = $ketQuaDc['tong_trang'] ?? $ketQuaDc['tong_so_trang'] ?? 1;
}

// =========================================================================
// TAB 3: KIỂM KÊ
// =========================================================================
elseif ($tab === 'kiem-ke') {
    $ketQuaKk = kiem_ke_danh_sach([
        'tu_khoa'       => $tuKhoa,
        'trang_thai'    => $trangThai,
        'tu_ngay'       => $tuNgay,
        'den_ngay'      => $denNgay,
        'phong_truc_id' => $phongTrucFilter ?? 0,
    ], $trang, $soMoiTrang);
    $danhSachKk = $ketQuaKk['danh_sach'] ?? [];
    $tongSo = $ketQuaKk['tong_so'] ?? 0;
    $tongSoTrang = $ketQuaKk['tong_trang'] ?? $ketQuaKk['tong_so_trang'] ?? 1;
}

$tieuDeTrang = 'Tra cứu, thống kê';
require dirname(__DIR__) . '/app/views/thong-ke/index.php';
