<?php
/**
 * Dịch vụ nghiệp vụ Kiểm kê tài sản theo phòng trực (G8).
 * Quản lý mở đợt kiểm kê, chụp ảnh sổ sách, đối chiếu thực tế (quét QR / thủ công),
 * xử lý phát hiện chênh lệch (mất, hư hỏng, sai vị trí), tự động sinh sự cố và hoàn tất/hủy đợt.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/tai-san-service.php';
require_once __DIR__ . '/danh-muc-service.php';

/**
 * Lấy danh sách các đợt kiểm kê tài sản có phân trang và bộ lọc.
 *
 * @param array $boLoc
 * @param int $trang
 * @param int $soMoiTrang
 * @return array
 */
function kiem_ke_danh_sach(array $boLoc = [], int $trang = 1, int $soMoiTrang = 15): array {
    $db = db();
    $trang = max(1, $trang);
    $soMoiTrang = max(1, min(100, $soMoiTrang));
    $offset = ($trang - 1) * $soMoiTrang;

    $where = ["1=1"];
    $types = '';
    $params = [];

    $tuKhoa = trim((string)($boLoc['tu_khoa'] ?? ''));
    if ($tuKhoa !== '') {
        $where[] = "(dk.ma_dot_kiem_ke LIKE ? OR dk.ten_dot_kiem_ke LIKE ? OR pt.ten_phong_truc LIKE ? OR nv.ho_ten LIKE ?)";
        $like = '%' . $tuKhoa . '%';
        $types .= 'ssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $phongTrucId = (int)($boLoc['phong_truc_id'] ?? 0);
    if ($phongTrucId > 0) {
        $where[] = "dk.phong_truc_id = ?";
        $types .= 'i';
        $params[] = $phongTrucId;
    }

    $trangThai = trim((string)($boLoc['trang_thai'] ?? ''));
    if ($trangThai !== '' && in_array($trangThai, [KIEM_KE_DANG_KIEM_KE, KIEM_KE_HOAN_TAT, KIEM_KE_DA_HUY], true)) {
        $where[] = "dk.trang_thai = ?";
        $types .= 's';
        $params[] = $trangThai;
    }

    $loaiKiemKe = trim((string)($boLoc['loai_kiem_ke'] ?? ''));
    if ($loaiKiemKe !== '' && in_array($loaiKiemKe, [LOAI_KIEM_KE_CUOI_BUOI, LOAI_KIEM_KE_DINH_KY, LOAI_KIEM_KE_DOT_XUAT], true)) {
        $where[] = "dk.loai_kiem_ke = ?";
        $types .= 's';
        $params[] = $loaiKiemKe;
    }

    $tuNgay = trim((string)($boLoc['tu_ngay'] ?? ''));
    if ($tuNgay !== '') {
        $where[] = "dk.thoi_gian_bat_dau >= ?";
        $types .= 's';
        $params[] = $tuNgay . ' 00:00:00';
    }

    $denNgay = trim((string)($boLoc['den_ngay'] ?? ''));
    if ($denNgay !== '') {
        $where[] = "dk.thoi_gian_bat_dau <= ?";
        $types .= 's';
        $params[] = $denNgay . ' 23:59:59';
    }

    $whereSql = implode(' AND ', $where);

    // Đếm tổng số đợt
    $sqlCount = "SELECT COUNT(*) AS tong 
                 FROM dot_kiem_ke dk
                 JOIN phong_truc pt ON dk.phong_truc_id = pt.id
                 JOIN nhan_vien nv ON dk.nhan_vien_lap_id = nv.id
                 WHERE $whereSql";
    $stmtCount = $db->prepare($sqlCount);
    if ($types !== '') {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $tongSo = (int)($stmtCount->get_result()->fetch_assoc()['tong'] ?? 0);
    $stmtCount->close();

    $tongTrang = (int)ceil($tongSo / $soMoiTrang);

    // Lấy dữ liệu trang
    $sqlData = "SELECT dk.*,
                       pt.ten_phong_truc, pt.ma_phong_truc,
                       nv.ho_ten AS ho_ten_nguoi_lap, nv.ma_nhan_vien AS ma_nhan_vien_lap,
                       nvh.ho_ten AS ho_ten_nguoi_huy,
                       (SELECT COUNT(*) FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = dk.id) AS tong_tai_san_so_sach,
                       (SELECT COUNT(*) FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = dk.id AND ket_qua = 'TIM_THAY') AS so_tim_thay,
                       (SELECT COUNT(*) FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = dk.id AND ket_qua = 'CHUA_TIM_THAY') AS so_chua_tim_thay,
                       (SELECT COUNT(*) FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = dk.id AND ket_qua = 'CHUA_KIEM_TRA') AS so_chua_kiem_tra,
                       (SELECT COUNT(*) FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = dk.id AND trang_thai_so_sach = 'DANG_MUON') AS so_dang_muon
                FROM dot_kiem_ke dk
                JOIN phong_truc pt ON dk.phong_truc_id = pt.id
                JOIN nhan_vien nv ON dk.nhan_vien_lap_id = nv.id
                LEFT JOIN nhan_vien nvh ON dk.nhan_vien_huy_id = nvh.id
                WHERE $whereSql
                ORDER BY (dk.trang_thai = 'DANG_KIEM_KE') DESC, dk.thoi_gian_bat_dau DESC, dk.id DESC
                LIMIT ? OFFSET ?";

    $typesData = $types . 'ii';
    $paramsData = array_merge($params, [$soMoiTrang, $offset]);

    $stmtData = $db->prepare($sqlData);
    $stmtData->bind_param($typesData, ...$paramsData);
    $stmtData->execute();
    $danhSach = $stmtData->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtData->close();

    return [
        'danh_sach'   => $danhSach,
        'tong_so'     => $tongSo,
        'trang'       => $trang,
        'so_moi_trang'=> $soMoiTrang,
        'tong_trang'  => $tongTrang,
    ];
}

/**
 * Tìm đợt kiểm kê đang diễn ra (DANG_KIEM_KE) của một phòng trực.
 *
 * @param int $phongTrucId ID phòng trực
 * @return array|null
 */
function kiem_ke_tim_dot_dang_mo(int $phongTrucId): ?array {
    if ($phongTrucId <= 0) {
        return null;
    }
    $db = db();
    $stmt = $db->prepare("SELECT id FROM dot_kiem_ke WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE' LIMIT 1");
    $stmt->bind_param('i', $phongTrucId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }
    return kiem_ke_tim_theo_id((int)$row['id']);
}

/**
 * Tìm đợt kiểm kê chi tiết theo ID.
 *
 * @param int $id
 * @param int|null $phongTrucId Giới hạn quyền theo phòng trực nếu có
 * @return array|null
 */
function kiem_ke_tim_theo_id(int $id, ?int $phongTrucId = null): ?array {
    if ($id <= 0) {
        return null;
    }
    $db = db();

    $wherePt = "";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $wherePt = " AND dk.phong_truc_id = " . (int)$phongTrucId;
    }

    $sql = "SELECT dk.*,
                   pt.ten_phong_truc, pt.ma_phong_truc,
                   nv.ho_ten AS ho_ten_nguoi_lap, nv.ma_nhan_vien AS ma_nhan_vien_lap,
                   nvh.ho_ten AS ho_ten_nguoi_huy, nvh.ma_nhan_vien AS ma_nhan_vien_huy
            FROM dot_kiem_ke dk
            JOIN phong_truc pt ON dk.phong_truc_id = pt.id
            JOIN nhan_vien nv ON dk.nhan_vien_lap_id = nv.id
            LEFT JOIN nhan_vien nvh ON dk.nhan_vien_huy_id = nvh.id
            WHERE dk.id = ? $wherePt
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $dot = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$dot) {
        return null;
    }

    // Thống kê số lượng theo các trạng thái
    $sqlStat = "SELECT 
        COUNT(*) AS tong_so_sach,
        SUM(CASE WHEN ket_qua = 'TIM_THAY' THEN 1 ELSE 0 END) AS so_tim_thay,
        SUM(CASE WHEN ket_qua = 'TIM_THAY' AND tinh_trang_thuc_te = 'TOT' THEN 1 ELSE 0 END) AS so_tim_thay_tot,
        SUM(CASE WHEN ket_qua = 'TIM_THAY' AND tinh_trang_thuc_te = 'HU_HONG' THEN 1 ELSE 0 END) AS so_tim_thay_hong,
        SUM(CASE WHEN ket_qua = 'CHUA_TIM_THAY' THEN 1 ELSE 0 END) AS so_chua_tim_thay,
        SUM(CASE WHEN trang_thai_so_sach = 'DANG_MUON' THEN 1 ELSE 0 END) AS so_dang_muon,
        SUM(CASE WHEN trang_thai_so_sach <> 'DANG_MUON' AND ket_qua = 'CHUA_KIEM_TRA' THEN 1 ELSE 0 END) AS so_con_lai_can_kiem,
        SUM(CASE WHEN ket_qua = 'TIM_THAY' AND phong_hoc_thuc_te_id IS NOT NULL AND phong_hoc_thuc_te_id <> phong_hoc_so_sach_id THEN 1 ELSE 0 END) AS so_sai_vi_tri
    FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?";
    $stmtStat = $db->prepare($sqlStat);
    $stmtStat->bind_param('i', $id);
    $stmtStat->execute();
    $stats = $stmtStat->get_result()->fetch_assoc();
    $stmtStat->close();

    $dot['thong_ke'] = [
        'tong_so_sach'         => (int)($stats['tong_so_sach'] ?? 0),
        'so_tim_thay'          => (int)($stats['so_tim_thay'] ?? 0),
        'so_tim_thay_tot'      => (int)($stats['so_tim_thay_tot'] ?? 0),
        'so_tim_thay_hong'     => (int)($stats['so_tim_thay_hong'] ?? 0),
        'so_chua_tim_thay'     => (int)($stats['so_chua_tim_thay'] ?? 0),
        'so_dang_muon'         => (int)($stats['so_dang_muon'] ?? 0),
        'so_dang_muon_khi_mo'  => (int)($stats['so_dang_muon'] ?? 0),
        'so_con_lai_can_kiem'  => (int)($stats['so_con_lai_can_kiem'] ?? 0),
        'so_sai_vi_tri'        => (int)($stats['so_sai_vi_tri'] ?? 0),
    ];

    return $dot;
}

/**
 * Lấy danh sách các tài sản chi tiết trong một đợt kiểm kê kèm bộ lọc trạng thái.
 *
 * @param int $dotKiemKeId
 * @param array $boLoc (tu_khoa, trang_thai_loc)
 * @return array
 */
function kiem_ke_lay_chi_tiet_dot(int $dotKiemKeId, array $boLoc = []): array {
    if ($dotKiemKeId <= 0) {
        return [];
    }
    $db = db();

    $where = ["ct.dot_kiem_ke_id = ?"];
    $types = 'i';
    $params = [$dotKiemKeId];

    $tuKhoa = trim((string)($boLoc['tu_khoa'] ?? ''));
    if ($tuKhoa !== '') {
        $maChuan = tai_san_chuan_hoa_ma_tim_kiem($tuKhoa);
        $where[] = "(ts.ma_tai_san LIKE ? OR ts.ma_qr LIKE ? OR ts.ma_tai_san = ? OR ts.ten_tai_san LIKE ? OR ps.ma_phong LIKE ? OR pt.ma_phong LIKE ?)";
        $like = '%' . $tuKhoa . '%';
        $types .= 'ssssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $maChuan;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $locTrangThai = trim((string)($boLoc['trang_thai_loc'] ?? ''));
    if ($locTrangThai === 'CHUA_KIEM') {
        $where[] = "ct.trang_thai_so_sach <> 'DANG_MUON' AND ct.ket_qua = 'CHUA_KIEM_TRA'";
    } elseif ($locTrangThai === 'TIM_THAY') {
        $where[] = "ct.ket_qua = 'TIM_THAY'";
    } elseif ($locTrangThai === 'CHUA_TIM_THAY') {
        $where[] = "ct.ket_qua = 'CHUA_TIM_THAY'";
    } elseif ($locTrangThai === 'DANG_MUON') {
        $where[] = "ct.trang_thai_so_sach = 'DANG_MUON'";
    } elseif ($locTrangThai === 'SAI_VI_TRI') {
        $where[] = "ct.ket_qua = 'TIM_THAY' AND ct.phong_hoc_thuc_te_id IS NOT NULL AND ct.phong_hoc_thuc_te_id <> ct.phong_hoc_so_sach_id";
    } elseif ($locTrangThai === 'HU_HONG') {
        $where[] = "ct.ket_qua = 'TIM_THAY' AND ct.tinh_trang_thuc_te = 'HU_HONG'";
    }

    $whereSql = implode(' AND ', $where);

    $sql = "SELECT ct.*,
                   ct.ket_qua AS ket_qua_kiem_ke,
                   ts.ma_tai_san, ts.ma_qr, ts.ten_tai_san, ts.hang_san_xuat, ts.model,
                   ts.tinh_trang AS tinh_trang_hien_tai, ts.trang_thai AS trang_thai_hien_tai,
                   lts.ten_loai AS ten_loai_tai_san,
                   ps.ma_phong AS ma_phong_so_sach, ps.ten_phong AS ten_phong_so_sach,
                   pt.ma_phong AS ma_phong_thuc_te, pt.ten_phong AS ten_phong_thuc_te,
                   (SELECT pm.id FROM chi_tiet_muon ctm JOIN phieu_muon pm ON ctm.phieu_muon_id = pm.id WHERE ctm.tai_san_id = ct.tai_san_id AND ctm.thoi_gian_tra IS NULL ORDER BY ctm.id DESC LIMIT 1) AS phieu_muon_id,
                   (SELECT pm.ma_phieu_muon FROM chi_tiet_muon ctm JOIN phieu_muon pm ON ctm.phieu_muon_id = pm.id WHERE ctm.tai_san_id = ct.tai_san_id AND ctm.thoi_gian_tra IS NULL ORDER BY ctm.id DESC LIMIT 1) AS ma_phieu_muon_hien_tai
            FROM chi_tiet_kiem_ke ct
            JOIN tai_san ts ON ct.tai_san_id = ts.id
            LEFT JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
            LEFT JOIN phong_hoc ps ON ct.phong_hoc_so_sach_id = ps.id
            LEFT JOIN phong_hoc pt ON ct.phong_hoc_thuc_te_id = pt.id
            WHERE $whereSql
            ORDER BY (ct.ket_qua = 'CHUA_KIEM_TRA') DESC, ts.id ASC";

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * Mở đợt kiểm kê tài sản mới cho phòng trực.
 * Chụp snapshot sổ sách của toàn bộ tài sản đang hoạt động thuộc phòng trực.
 *
 * @param array $duLieu
 * @param int $nhanVienLapId
 * @param int|null $phongTrucId
 * @return array ['thanh_cong' => bool, 'loi' => string, 'id' => int, 'ma_dot_kiem_ke' => string]
 */
function kiem_ke_mo_dot(array $duLieu, int $nhanVienLapId, ?int $phongTrucId = null): array {
    $db = db();

    $tenDot = trim((string)($duLieu['ten_dot_kiem_ke'] ?? ''));
    if ($tenDot === '') {
        return ['thanh_cong' => false, 'loi' => 'Vui lòng nhập tên đợt kiểm kê tài sản.', 'id' => 0, 'ma_dot_kiem_ke' => ''];
    }
    if (mb_strlen($tenDot, 'UTF-8') > 150) {
        return ['thanh_cong' => false, 'loi' => 'Tên đợt kiểm kê không được vượt quá 150 ký tự.', 'id' => 0, 'ma_dot_kiem_ke' => ''];
    }

    $loaiKiemKe = trim((string)($duLieu['loai_kiem_ke'] ?? LOAI_KIEM_KE_CUOI_BUOI));
    if (!in_array($loaiKiemKe, [LOAI_KIEM_KE_CUOI_BUOI, LOAI_KIEM_KE_DINH_KY, LOAI_KIEM_KE_DOT_XUAT], true)) {
        return ['thanh_cong' => false, 'loi' => 'Loại kiểm kê không hợp lệ.', 'id' => 0, 'ma_dot_kiem_ke' => ''];
    }

    $ghiChu = trim((string)($duLieu['ghi_chu'] ?? ''));
    if (mb_strlen($ghiChu, 'UTF-8') > 2000) {
        return ['thanh_cong' => false, 'loi' => 'Ghi chú không được vượt quá 2000 ký tự.', 'id' => 0, 'ma_dot_kiem_ke' => ''];
    }

    if ($phongTrucId === null || $phongTrucId <= 0) {
        $stmtNv = $db->prepare("SELECT phong_truc_id FROM nhan_vien WHERE id = ? LIMIT 1");
        $stmtNv->bind_param('i', $nhanVienLapId);
        $stmtNv->execute();
        $nvRow = $stmtNv->get_result()->fetch_assoc();
        $stmtNv->close();
        $phongTrucId = (int)($nvRow['phong_truc_id'] ?? 0);
    }

    if ($phongTrucId <= 0) {
        return ['thanh_cong' => false, 'loi' => 'Tài khoản chưa được phân công quản lý phòng trực nào.', 'id' => 0, 'ma_dot_kiem_ke' => ''];
    }

    $db->begin_transaction();
    try {
        // Kiểm tra phòng trực có đợt đang mở không
        $stmtCheck = $db->prepare("SELECT id, ma_dot_kiem_ke FROM dot_kiem_ke WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE' LIMIT 1 FOR UPDATE");
        $stmtCheck->bind_param('i', $phongTrucId);
        $stmtCheck->execute();
        $dangMo = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        if ($dangMo) {
            throw new RuntimeException("Phòng trực hiện đã có đợt kiểm kê [{$dangMo['ma_dot_kiem_ke']}] đang diễn ra. Vui lòng hoàn tất hoặc hủy đợt trước.");
        }

        // Lấy danh sách tài sản đang sử dụng của phòng trực
        $stmtTs = $db->prepare("SELECT id, tinh_trang, trang_thai, phong_hoc_hien_tai_id 
                                FROM tai_san 
                                WHERE phong_truc_id = ? AND trang_thai <> 'NGUNG_SU_DUNG' 
                                ORDER BY id ASC FOR UPDATE");
        $stmtTs->bind_param('i', $phongTrucId);
        $stmtTs->execute();
        $dsTaiSan = $stmtTs->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtTs->close();

        if (empty($dsTaiSan)) {
            throw new RuntimeException("Phòng trực này chưa có tài sản đang sử dụng để kiểm kê.");
        }

        // Kiểm tra xem thiết bị nào đang trong phiếu mượn chưa trả
        $stmtDangMuon = $db->prepare("SELECT DISTINCT ctm.tai_san_id 
                                      FROM chi_tiet_muon ctm 
                                      JOIN tai_san ts ON ctm.tai_san_id = ts.id 
                                      WHERE ts.phong_truc_id = ? AND ctm.thoi_gian_tra IS NULL");
        $stmtDangMuon->bind_param('i', $phongTrucId);
        $stmtDangMuon->execute();
        $rowsMuon = $stmtDangMuon->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtDangMuon->close();

        $setDangMuon = [];
        foreach ($rowsMuon as $rm) {
            $setDangMuon[(int)$rm['tai_san_id']] = true;
        }

        // Tạo mã đợt kiểm kê: KK-YYYYMMDD-XXXXXXXX
        $maDotKiemKe = sinh_ma('KK');

        $sqlInsertDot = "INSERT INTO dot_kiem_ke 
                         (ma_dot_kiem_ke, ten_dot_kiem_ke, phong_truc_id, nhan_vien_lap_id, loai_kiem_ke, ghi_chu) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $stmtDot = $db->prepare($sqlInsertDot);
        $ghiChuVal = $ghiChu !== '' ? $ghiChu : null;
        $stmtDot->bind_param('ssiiss', $maDotKiemKe, $tenDot, $phongTrucId, $nhanVienLapId, $loaiKiemKe, $ghiChuVal);
        $stmtDot->execute();
        $dotId = (int)$db->insert_id;
        $stmtDot->close();

        // Chụp sổ sách vào chi_tiet_kiem_ke
        $sqlInsertCt = "INSERT INTO chi_tiet_kiem_ke 
                        (dot_kiem_ke_id, tai_san_id, tinh_trang_so_sach, trang_thai_so_sach, phong_hoc_so_sach_id) 
                        VALUES (?, ?, ?, ?, ?)";
        $stmtCt = $db->prepare($sqlInsertCt);

        foreach ($dsTaiSan as $ts) {
            $tsId = (int)$ts['id'];
            $trangThaiSoSach = isset($setDangMuon[$tsId]) ? 'DANG_MUON' : $ts['trang_thai'];
            $phongHocSoSachId = $ts['phong_hoc_hien_tai_id'] !== null ? (int)$ts['phong_hoc_hien_tai_id'] : null;

            $stmtCt->bind_param('iissi', $dotId, $tsId, $ts['tinh_trang'], $trangThaiSoSach, $phongHocSoSachId);
            $stmtCt->execute();
        }
        $stmtCt->close();

        $db->commit();

        return [
            'thanh_cong'     => true,
            'loi'            => '',
            'id'             => $dotId,
            'ma_dot_kiem_ke' => $maDotKiemKe,
        ];
    } catch (Throwable $e) {
        $db->rollback();
        return [
            'thanh_cong'     => false,
            'loi'            => $e->getMessage(),
            'id'             => 0,
            'ma_dot_kiem_ke' => '',
        ];
    }
}

/**
 * Ghi nhận kết quả đối chiếu thực tế cho 1 tài sản trong đợt kiểm kê.
 *
 * @param array $duLieu (dot_kiem_ke_id, chi_tiet_id, ket_qua, tinh_trang_thuc_te, phong_hoc_thuc_te_id, ghi_chu)
 * @param int $nhanVienId
 * @param int|null $phongTrucId
 * @return array
 */
function kiem_ke_ghi_nhan(array $duLieu, int $nhanVienId, ?int $phongTrucId = null): array {
    $db = db();

    $dotId = doc_so_nguyen('dot_kiem_ke_id', 0, $duLieu);
    $chiTietId = doc_so_nguyen('chi_tiet_id', 0, $duLieu);
    $ketQua = trim((string)($duLieu['ket_qua'] ?? ''));

    if ($dotId <= 0 || $chiTietId <= 0) {
        return ['thanh_cong' => false, 'loi' => 'Thông tin bản ghi kiểm kê không hợp lệ.'];
    }

    if (!in_array($ketQua, ['TIM_THAY', 'CHUA_TIM_THAY'], true)) {
        return ['thanh_cong' => false, 'loi' => 'Kết quả kiểm tra phải là Có mặt hoặc Chưa tìm thấy.'];
    }

    $ghiChu = trim((string)($duLieu['ghi_chu'] ?? ''));
    $tinhTrangThucTe = null;
    $phongHocThucTeId = null;

    if ($ketQua === 'TIM_THAY') {
        $tinhTrangThucTe = trim((string)($duLieu['tinh_trang_thuc_te'] ?? ''));
        if (!in_array($tinhTrangThucTe, ['TOT', 'HU_HONG'], true)) {
            return ['thanh_cong' => false, 'loi' => 'Vui lòng chọn tình trạng thực tế của thiết bị (Tốt hoặc Hư hỏng).'];
        }

        if ($tinhTrangThucTe === 'HU_HONG' && $ghiChu === '') {
            return ['thanh_cong' => false, 'loi' => 'Vui lòng ghi rõ mô tả hư hỏng hoặc lỗi thực tế phát hiện.'];
        }

        $phongHocThucTeId = doc_so_nguyen('phong_hoc_thuc_te_id', 0, $duLieu);
        if ($phongHocThucTeId <= 0) {
            $phongHocThucTeId = null;
        }
    } else {
        // CHUA_TIM_THAY
        if ($ghiChu === '') {
            return ['thanh_cong' => false, 'loi' => 'Vui lòng ghi chú lý do hoặc vị trí đã tìm kiếm khi chưa tìm thấy thiết bị.'];
        }
    }

    $db->begin_transaction();
    try {
        // Khóa kiểm tra đợt kiểm kê
        $stmtDot = $db->prepare("SELECT id, trang_thai, phong_truc_id FROM dot_kiem_ke WHERE id = ? FOR UPDATE");
        $stmtDot->bind_param('i', $dotId);
        $stmtDot->execute();
        $dot = $stmtDot->get_result()->fetch_assoc();
        $stmtDot->close();

        if (!$dot) {
            throw new RuntimeException("Không tìm thấy đợt kiểm kê.");
        }
        if ($dot['trang_thai'] !== 'DANG_KIEM_KE') {
            throw new RuntimeException("Đợt kiểm kê này đã kết thúc, không thể thay đổi kết quả.");
        }

        // Khóa kiểm tra bản ghi chi tiết
        $stmtCt = $db->prepare("SELECT ct.id, ct.tai_san_id, ct.trang_thai_so_sach, ct.phong_hoc_so_sach_id, ts.phong_truc_id 
                                FROM chi_tiet_kiem_ke ct 
                                JOIN tai_san ts ON ct.tai_san_id = ts.id 
                                WHERE ct.id = ? AND ct.dot_kiem_ke_id = ? FOR UPDATE");
        $stmtCt->bind_param('ii', $chiTietId, $dotId);
        $stmtCt->execute();
        $ct = $stmtCt->get_result()->fetch_assoc();
        $stmtCt->close();

        if (!$ct) {
            throw new RuntimeException("Bản ghi thiết bị không thuộc đợt kiểm kê này.");
        }

        if ($ct['trang_thai_so_sach'] === 'DANG_MUON') {
            throw new RuntimeException("Thiết bị này đang trong trạng thái Đang mượn lúc mở đợt, không cần kiểm thực tế.");
        }

        // Kiểm tra xem thiết bị hiện tại có đang bị mượn không
        $stmtM = $db->prepare("SELECT id FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL LIMIT 1");
        $stmtM->bind_param('i', $ct['tai_san_id']);
        $stmtM->execute();
        $dangM = $stmtM->get_result()->fetch_assoc();
        $stmtM->close();
        if ($dangM) {
            throw new RuntimeException("Thiết bị hiện đang có phiếu mượn chưa trả, không thể kiểm thực tế.");
        }

        // Kiểm tra phòng học thực tế nếu có truyền
        if ($phongHocThucTeId !== null) {
            $stmtPh = $db->prepare("SELECT id FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1 LIMIT 1");
            $stmtPh->bind_param('i', $phongHocThucTeId);
            $stmtPh->execute();
            $phValid = $stmtPh->get_result()->fetch_assoc();
            $stmtPh->close();
            if (!$phValid) {
                throw new RuntimeException("Phòng học thực tế không tồn tại hoặc đã ngừng hoạt động.");
            }
        }

        // Cập nhật kết quả đối chiếu
        $sqlUpdate = "UPDATE chi_tiet_kiem_ke 
                      SET ket_qua = ?, 
                          tinh_trang_thuc_te = ?, 
                          phong_hoc_thuc_te_id = ?, 
                          thoi_gian_kiem_tra = NOW(), 
                          ghi_chu = ? 
                      WHERE id = ?";
        $stmtUp = $db->prepare($sqlUpdate);
        $ghiChuVal = $ghiChu !== '' ? $ghiChu : null;
        $stmtUp->bind_param('ssisi', $ketQua, $tinhTrangThucTe, $phongHocThucTeId, $ghiChuVal, $chiTietId);
        $stmtUp->execute();
        $stmtUp->close();

        $db->commit();

        return ['thanh_cong' => true, 'loi' => ''];
    } catch (Throwable $e) {
        $db->rollback();
        return ['thanh_cong' => false, 'loi' => $e->getMessage()];
    }
}

/**
 * Hoàn tất chốt sổ đợt kiểm kê.
 * Tự động tạo bản ghi su_co_tai_san cho các thiết bị chênh lệch (Mất, Hư hỏng, Sai vị trí).
 * Cập nhật trạng thái thiết bị tương ứng (NGUNG_SU_DUNG hoặc BAO_TRI).
 *
 * @param int $dotKiemKeId
 * @param int $nhanVienId
 * @param int|null $phongTrucId
 * @return array ['thanh_cong' => bool, 'loi' => string, 'so_su_co' => int, 'tong_tai_san' => int]
 */
function kiem_ke_hoan_tat(int $dotKiemKeId, int $nhanVienId, ?int $phongTrucId = null): array {
    $db = db();

    $db->begin_transaction();
    try {
        $stmtDot = $db->prepare("SELECT id, ma_dot_kiem_ke, trang_thai, phong_truc_id FROM dot_kiem_ke WHERE id = ? FOR UPDATE");
        $stmtDot->bind_param('i', $dotKiemKeId);
        $stmtDot->execute();
        $dot = $stmtDot->get_result()->fetch_assoc();
        $stmtDot->close();

        if (!$dot) {
            throw new RuntimeException("Không tìm thấy đợt kiểm kê.");
        }
        if ($dot['trang_thai'] !== 'DANG_KIEM_KE') {
            throw new RuntimeException("Đợt kiểm kê này không ở trạng thái đang kiểm kê.");
        }

        // Lấy danh sách chi tiết có khóa dòng
        $sqlDetails = "SELECT ct.*, ts.phong_truc_id, ts.tinh_trang AS tinh_trang_ts, ts.trang_thai AS trang_thai_ts,
                              ts.phong_hoc_hien_tai_id AS phong_hoc_ts
                       FROM chi_tiet_kiem_ke ct
                       JOIN tai_san ts ON ct.tai_san_id = ts.id
                       WHERE ct.dot_kiem_ke_id = ?
                       ORDER BY ct.tai_san_id ASC FOR UPDATE";
        $stmtDetails = $db->prepare($sqlDetails);
        $stmtDetails->bind_param('i', $dotKiemKeId);
        $stmtDetails->execute();
        $details = $stmtDetails->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtDetails->close();

        $soSuCo = 0;

        foreach ($details as $ct) {
            // Thiết bị đang mượn lúc mở đợt được bỏ qua kiểm tra thực tế
            if ($ct['trang_thai_so_sach'] === 'DANG_MUON') {
                continue;
            }

            // Chặn nếu còn thiết bị chưa kiểm tra
            if ($ct['ket_qua'] === 'CHUA_KIEM_TRA') {
                throw new RuntimeException("Còn thiết bị chưa được đối chiếu thực tế. Hãy ghi nhận Có mặt hoặc Chưa tìm thấy cho tất cả thiết bị trước khi hoàn tất.");
            }

            $taiSanId = (int)$ct['tai_san_id'];
            $chiTietId = (int)$ct['id'];

            // 1. Trường hợp: CHƯA TÌM THẤY (MẤT)
            if ($ct['ket_qua'] === 'CHUA_TIM_THAY') {
                $maSuCo = sinh_ma('SC');
                $moTa = "Chưa tìm thấy thiết bị trong đợt kiểm kê {$dot['ma_dot_kiem_ke']}. " . ($ct['ghi_chu'] ?: '');
                $stmtSc = $db->prepare("INSERT INTO su_co_tai_san 
                    (ma_su_co, tai_san_id, nhan_vien_lap_id, chi_tiet_kiem_ke_id, loai_su_co, mo_ta, trang_thai) 
                    VALUES (?, ?, ?, ?, 'MAT', ?, 'CHUA_XU_LY')");
                $stmtSc->bind_param('siiis', $maSuCo, $taiSanId, $nhanVienId, $chiTietId, $moTa);
                $stmtSc->execute();
                $stmtSc->close();

                // Chuyển tài sản sang NGỪNG SỬ DỤNG
                $db->execute_query("UPDATE tai_san SET trang_thai = 'NGUNG_SU_DUNG' WHERE id = ?", [$taiSanId]);
                $soSuCo++;
                continue;
            }

            // 2. Trường hợp: CÓ MẶT nhưng PHÁT HIỆN HƯ HỎNG MỚI
            if ($ct['tinh_trang_thuc_te'] === 'HU_HONG' && $ct['tinh_trang_so_sach'] !== 'HU_HONG') {
                $maSuCo = sinh_ma('SC');
                $moTa = "Phát hiện hư hỏng qua đợt kiểm kê {$dot['ma_dot_kiem_ke']}. " . ($ct['ghi_chu'] ?: '');
                $stmtSc = $db->prepare("INSERT INTO su_co_tai_san 
                    (ma_su_co, tai_san_id, nhan_vien_lap_id, chi_tiet_kiem_ke_id, loai_su_co, mo_ta, trang_thai) 
                    VALUES (?, ?, ?, ?, 'HU_HONG', ?, 'CHUA_XU_LY')");
                $stmtSc->bind_param('siiis', $maSuCo, $taiSanId, $nhanVienId, $chiTietId, $moTa);
                $stmtSc->execute();
                $stmtSc->close();

                // Cập nhật trạng thái tài sản sang BẢO TRÌ và tình trạng HƯ HỎNG
                $db->execute_query("UPDATE tai_san SET tinh_trang = 'HU_HONG', trang_thai = 'BAO_TRI' WHERE id = ?", [$taiSanId]);
                $soSuCo++;
            }

            // 3. Trường hợp: SAI VỊ TRÍ PHÒNG HỌC
            $saiViTri = ($ct['phong_hoc_thuc_te_id'] !== null && (int)$ct['phong_hoc_thuc_te_id'] !== (int)$ct['phong_hoc_so_sach_id']);
            if ($saiViTri) {
                $maSuCo = sinh_ma('SC');
                $moTa = "Vị trí thực tế khác sổ sách trong đợt kiểm kê {$dot['ma_dot_kiem_ke']}. " . ($ct['ghi_chu'] ?: 'Cần điều chuyển hoặc kiểm tra lại vị trí.');
                $stmtSc = $db->prepare("INSERT INTO su_co_tai_san 
                    (ma_su_co, tai_san_id, nhan_vien_lap_id, chi_tiet_kiem_ke_id, loai_su_co, mo_ta, trang_thai) 
                    VALUES (?, ?, ?, ?, 'SAI_VI_TRI', ?, 'CHUA_XU_LY')");
                $stmtSc->bind_param('siiis', $maSuCo, $taiSanId, $nhanVienId, $chiTietId, $moTa);
                $stmtSc->execute();
                $stmtSc->close();

                // Tạm thời dừng cho mượn để chờ xử lý điều chuyển vị trí
                $db->execute_query("UPDATE tai_san SET trang_thai = 'NGUNG_SU_DUNG' WHERE id = ?", [$taiSanId]);
                $soSuCo++;
            }
        }

        // Cập nhật đợt kiểm kê sang HOÀN TẤT
        $db->execute_query("UPDATE dot_kiem_ke SET trang_thai = 'HOAN_TAT', thoi_gian_ket_thuc = NOW() WHERE id = ?", [$dotKiemKeId]);

        $db->commit();

        return [
            'thanh_cong'   => true,
            'loi'          => '',
            'so_su_co'     => $soSuCo,
            'tong_tai_san' => count($details),
        ];
    } catch (Throwable $e) {
        $db->rollback();
        return [
            'thanh_cong'   => false,
            'loi'          => $e->getMessage(),
            'so_su_co'     => 0,
            'tong_tai_san' => 0,
        ];
    }
}

/**
 * Hủy đợt kiểm kê đang diễn ra.
 *
 * @param int $dotKiemKeId
 * @param string $lyDoHuy
 * @param int $nhanVienHuyId
 * @param int|null $phongTrucId
 * @return array
 */
function kiem_ke_huy(int $dotKiemKeId, string $lyDoHuy, int $nhanVienHuyId, ?int $phongTrucId = null): array {
    $db = db();

    $lyDoHuy = trim($lyDoHuy);
    if ($lyDoHuy === '') {
        return ['thanh_cong' => false, 'loi' => 'Vui lòng nhập lý do hủy đợt kiểm kê.'];
    }
    if (mb_strlen($lyDoHuy, 'UTF-8') > 500) {
        return ['thanh_cong' => false, 'loi' => 'Lý do hủy không được vượt quá 500 ký tự.'];
    }

    $db->begin_transaction();
    try {
        $stmtDot = $db->prepare("SELECT id, trang_thai FROM dot_kiem_ke WHERE id = ? FOR UPDATE");
        $stmtDot->bind_param('i', $dotKiemKeId);
        $stmtDot->execute();
        $dot = $stmtDot->get_result()->fetch_assoc();
        $stmtDot->close();

        if (!$dot) {
            throw new RuntimeException("Không tìm thấy đợt kiểm kê.");
        }
        if ($dot['trang_thai'] !== 'DANG_KIEM_KE') {
            throw new RuntimeException("Chỉ có thể hủy đợt kiểm kê đang thực hiện.");
        }

        $stmtUp = $db->prepare("UPDATE dot_kiem_ke 
                                SET trang_thai = 'DA_HUY', 
                                    thoi_gian_ket_thuc = NOW(), 
                                    nhan_vien_huy_id = ?, 
                                    ly_do_huy = ? 
                                WHERE id = ?");
        $stmtUp->bind_param('isi', $nhanVienHuyId, $lyDoHuy, $dotKiemKeId);
        $stmtUp->execute();
        $stmtUp->close();

        $db->commit();
        return ['thanh_cong' => true, 'loi' => ''];
    } catch (Throwable $e) {
        $db->rollback();
        return ['thanh_cong' => false, 'loi' => $e->getMessage()];
    }
}
