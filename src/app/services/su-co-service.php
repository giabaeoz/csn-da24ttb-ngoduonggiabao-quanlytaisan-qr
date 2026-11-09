<?php
/**
 * Dịch vụ nghiệp vụ Quản lý Báo cáo sự cố tài sản & Bảo trì / Sửa chữa (G9).
 * Hỗ trợ ghi nhận sự cố (Hư hỏng, Mất mát, Sai vị trí), quy trình xử lý,
 * cập nhật trạng thái thiết bị và truy xuất lịch sử sự cố.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/tai-san-service.php';
require_once __DIR__ . '/danh-muc-service.php';

/**
 * Lấy danh sách các sự cố tài sản có phân trang và bộ lọc tìm kiếm.
 *
 * @param array $boLoc
 * @param int $trang
 * @param int $soMoiTrang
 * @return array
 */
function su_co_danh_sach(array $boLoc = [], int $trang = 1, int $soMoiTrang = 15): array {
    $db = db();
    $trang = max(1, $trang);
    $soMoiTrang = max(1, min(100, $soMoiTrang));
    $offset = ($trang - 1) * $soMoiTrang;

    $where = ["1=1"];
    $types = '';
    $params = [];

    // Lọc theo từ khóa
    $tuKhoa = trim((string)($boLoc['tu_khoa'] ?? ''));
    if ($tuKhoa !== '') {
        $where[] = "(sc.ma_su_co LIKE ? OR sc.mo_ta LIKE ? OR ts.ma_tai_san LIKE ? OR ts.ten_tai_san LIKE ? OR ts.ma_qr LIKE ? OR nv.ho_ten LIKE ?)";
        $like = '%' . $tuKhoa . '%';
        $types .= 'ssssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // Lọc theo phòng trực quản lý thiết bị
    $phongTrucId = (int)($boLoc['phong_truc_id'] ?? 0);
    if ($phongTrucId > 0) {
        $where[] = "ts.phong_truc_id = ?";
        $types .= 'i';
        $params[] = $phongTrucId;
    }

    // Lọc theo loại sự cố
    $loaiSuCo = trim((string)($boLoc['loai_su_co'] ?? ''));
    if ($loaiSuCo !== '' && in_array($loaiSuCo, ['HU_HONG', 'MAT', 'SAI_VI_TRI'], true)) {
        $where[] = "sc.loai_su_co = ?";
        $types .= 's';
        $params[] = $loaiSuCo;
    }

    // Lọc theo trạng thái xử lý
    $trangThai = trim((string)($boLoc['trang_thai'] ?? ''));
    if ($trangThai !== '' && in_array($trangThai, ['CHUA_XU_LY', 'DANG_XU_LY', 'DA_XU_LY'], true)) {
        $where[] = "sc.trang_thai = ?";
        $types .= 's';
        $params[] = $trangThai;
    }

    // Lọc theo khoảng thời gian phát hiện
    $tuNgay = trim((string)($boLoc['tu_ngay'] ?? ''));
    if ($tuNgay !== '') {
        $where[] = "sc.thoi_gian_phat_hien >= ?";
        $types .= 's';
        $params[] = $tuNgay . ' 00:00:00';
    }

    $denNgay = trim((string)($boLoc['den_ngay'] ?? ''));
    if ($denNgay !== '') {
        $where[] = "sc.thoi_gian_phat_hien <= ?";
        $types .= 's';
        $params[] = $denNgay . ' 23:59:59';
    }

    $whereSql = implode(' AND ', $where);

    // Đếm tổng số bản ghi
    $sqlCount = "SELECT COUNT(*) AS tong 
                 FROM su_co_tai_san sc
                 JOIN tai_san ts ON sc.tai_san_id = ts.id
                 JOIN nhan_vien nv ON sc.nhan_vien_lap_id = nv.id
                 WHERE $whereSql";
    $stmtCount = $db->prepare($sqlCount);
    if ($types !== '') {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $tongSo = (int)($stmtCount->get_result()->fetch_assoc()['tong'] ?? 0);
    $stmtCount->close();

    $tongTrang = (int)ceil($tongSo / $soMoiTrang);

    // Lấy dữ liệu danh sách
    $sqlData = "SELECT sc.*,
                       ts.ma_tai_san, ts.ma_qr, ts.ten_tai_san, ts.tinh_trang AS tinh_trang_tai_san, ts.trang_thai AS trang_thai_tai_san,
                       lts.ten_loai AS ten_loai_tai_san,
                       pt.id AS phong_truc_id, pt.ten_phong_truc, pt.ma_phong_truc,
                       ph.ma_phong AS ma_phong_hien_tai, ph.ten_phong AS ten_phong_hien_tai,
                       nv.ho_ten AS ho_ten_nguoi_lap, nv.ma_nhan_vien AS ma_nhan_vien_lap,
                       pm.ma_phieu_muon,
                       dk.ma_dot_kiem_ke
                FROM su_co_tai_san sc
                JOIN tai_san ts ON sc.tai_san_id = ts.id
                LEFT JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
                LEFT JOIN phong_truc pt ON ts.phong_truc_id = pt.id
                LEFT JOIN phong_hoc ph ON ts.phong_hoc_hien_tai_id = ph.id
                JOIN nhan_vien nv ON sc.nhan_vien_lap_id = nv.id
                LEFT JOIN chi_tiet_muon ctm ON sc.chi_tiet_muon_id = ctm.id
                LEFT JOIN phieu_muon pm ON ctm.phieu_muon_id = pm.id
                LEFT JOIN chi_tiet_kiem_ke ctkk ON sc.chi_tiet_kiem_ke_id = ctkk.id
                LEFT JOIN dot_kiem_ke dk ON ctkk.dot_kiem_ke_id = dk.id
                WHERE $whereSql
                ORDER BY (sc.trang_thai = 'CHUA_XU_LY') DESC, (sc.trang_thai = 'DANG_XU_LY') DESC, sc.thoi_gian_phat_hien DESC, sc.id DESC
                LIMIT ? OFFSET ?";

    $typesData = $types . 'ii';
    $paramsData = array_merge($params, [$soMoiTrang, $offset]);

    $stmtData = $db->prepare($sqlData);
    $stmtData->bind_param($typesData, ...$paramsData);
    $stmtData->execute();
    $danhSach = $stmtData->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtData->close();

    // Thống kê nhanh theo các trạng thái
    $thongKe = su_co_thong_ke_nhanh($phongTrucId > 0 ? $phongTrucId : null);

    return [
        'danh_sach'   => $danhSach,
        'tong_so'     => $tongSo,
        'trang'       => $trang,
        'so_moi_trang'=> $soMoiTrang,
        'tong_trang'  => $tongTrang,
        'thong_ke'    => $thongKe,
    ];
}

/**
 * Thống kê nhanh số lượng sự cố theo trạng thái và phân loại.
 *
 * @param int|null $phongTrucId
 * @return array
 */
function su_co_thong_ke_nhanh(?int $phongTrucId = null): array {
    $db = db();
    $wherePt = "";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $wherePt = " WHERE ts.phong_truc_id = " . (int)$phongTrucId;
    }

    $sql = "SELECT 
        COUNT(*) AS tong_so,
        SUM(CASE WHEN sc.trang_thai = 'CHUA_XU_LY' THEN 1 ELSE 0 END) AS so_chua_xu_ly,
        SUM(CASE WHEN sc.trang_thai = 'DANG_XU_LY' THEN 1 ELSE 0 END) AS so_dang_xu_ly,
        SUM(CASE WHEN sc.trang_thai = 'DA_XU_LY' THEN 1 ELSE 0 END) AS so_da_xu_ly,
        SUM(CASE WHEN sc.loai_su_co = 'HU_HONG' THEN 1 ELSE 0 END) AS so_hu_hong,
        SUM(CASE WHEN sc.loai_su_co = 'MAT' THEN 1 ELSE 0 END) AS so_mat,
        SUM(CASE WHEN sc.loai_su_co = 'SAI_VI_TRI' THEN 1 ELSE 0 END) AS so_sai_vi_tri
    FROM su_co_tai_san sc
    JOIN tai_san ts ON sc.tai_san_id = ts.id
    $wherePt";

    $row = $db->query($sql)->fetch_assoc();

    return [
        'tong_so'      => (int)($row['tong_so'] ?? 0),
        'so_chua_xu_ly'=> (int)($row['so_chua_xu_ly'] ?? 0),
        'so_dang_xu_ly'=> (int)($row['so_dang_xu_ly'] ?? 0),
        'so_da_xu_ly'  => (int)($row['so_da_xu_ly'] ?? 0),
        'so_hu_hong'   => (int)($row['so_hu_hong'] ?? 0),
        'so_mat'       => (int)($row['so_mat'] ?? 0),
        'so_sai_vi_tri'=> (int)($row['so_sai_vi_tri'] ?? 0),
    ];
}

/**
 * Tìm thông tin chi tiết một sự cố theo ID.
 *
 * @param int $id
 * @param int|null $phongTrucId
 * @return array|null
 */
function su_co_tim_theo_id(int $id, ?int $phongTrucId = null): ?array {
    if ($id <= 0) {
        return null;
    }
    $db = db();

    $wherePt = "";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $wherePt = " AND ts.phong_truc_id = " . (int)$phongTrucId;
    }

    $sql = "SELECT sc.*,
                   ts.ma_tai_san, ts.ma_qr, ts.ten_tai_san, ts.hang_san_xuat, ts.model,
                   ts.tinh_trang AS tinh_trang_tai_san, ts.trang_thai AS trang_thai_tai_san,
                   ts.phong_hoc_hien_tai_id,
                   lts.ten_loai AS ten_loai_tai_san,
                   pt.id AS phong_truc_id, pt.ten_phong_truc, pt.ma_phong_truc,
                   ph.ma_phong AS ma_phong_hien_tai, ph.ten_phong AS ten_phong_hien_tai,
                   nv.ho_ten AS ho_ten_nguoi_lap, nv.ma_nhan_vien AS ma_nhan_vien_lap,
                   pm.id AS phieu_muon_id, pm.ma_phieu_muon,
                   dk.id AS dot_kiem_ke_id, dk.ma_dot_kiem_ke, dk.ten_dot_kiem_ke
            FROM su_co_tai_san sc
            JOIN tai_san ts ON sc.tai_san_id = ts.id
            LEFT JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
            LEFT JOIN phong_truc pt ON ts.phong_truc_id = pt.id
            LEFT JOIN phong_hoc ph ON ts.phong_hoc_hien_tai_id = ph.id
            JOIN nhan_vien nv ON sc.nhan_vien_lap_id = nv.id
            LEFT JOIN chi_tiet_muon ctm ON sc.chi_tiet_muon_id = ctm.id
            LEFT JOIN phieu_muon pm ON ctm.phieu_muon_id = pm.id
            LEFT JOIN chi_tiet_kiem_ke ctkk ON sc.chi_tiet_kiem_ke_id = ctkk.id
            LEFT JOIN dot_kiem_ke dk ON ctkk.dot_kiem_ke_id = dk.id
            WHERE sc.id = ? $wherePt
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Ghi nhận báo cáo sự cố tài sản mới (thủ công hoặc phát hiện đột xuất).
 *
 * @param array $duLieu (tai_san_id, loai_su_co, mo_ta)
 * @param int $nhanVienLapId
 * @param int|null $phongTrucId Giới hạn phân quyền theo phòng trực của nhân viên
 * @return array ['thanh_cong' => bool, 'loi' => string, 'id' => int, 'ma_su_co' => string]
 */
function su_co_bao_cao(array $duLieu, int $nhanVienLapId, ?int $phongTrucId = null): array {
    $db = db();

    $taiSanId = doc_so_nguyen('tai_san_id', 0, $duLieu);
    if ($taiSanId <= 0) {
        return ['thanh_cong' => false, 'loi' => 'Vui lòng chọn thiết bị gặp sự cố.', 'id' => 0, 'ma_su_co' => ''];
    }

    $loaiSuCo = trim((string)($duLieu['loai_su_co'] ?? ''));
    if (!in_array($loaiSuCo, ['HU_HONG', 'MAT', 'SAI_VI_TRI'], true)) {
        return ['thanh_cong' => false, 'loi' => 'Loại sự cố không hợp lệ (chỉ chấp nhận Hư hỏng, Mất hoặc Sai vị trí).', 'id' => 0, 'ma_su_co' => ''];
    }

    $moTa = trim((string)($duLieu['mo_ta'] ?? ''));
    if ($moTa === '') {
        return ['thanh_cong' => false, 'loi' => 'Vui lòng nhập mô tả chi tiết sự cố phát hiện.', 'id' => 0, 'ma_su_co' => ''];
    }
    if (mb_strlen($moTa, 'UTF-8') > 2000) {
        return ['thanh_cong' => false, 'loi' => 'Mô tả sự cố không được vượt quá 2000 ký tự.', 'id' => 0, 'ma_su_co' => ''];
    }

    $db->begin_transaction();
    try {
        // Kiểm tra sự tồn tại của tài sản
        $stmtTs = $db->prepare("SELECT id, ma_tai_san, ten_tai_san, phong_truc_id, tinh_trang, trang_thai 
                                FROM tai_san WHERE id = ? FOR UPDATE");
        $stmtTs->bind_param('i', $taiSanId);
        $stmtTs->execute();
        $taiSan = $stmtTs->get_result()->fetch_assoc();
        $stmtTs->close();

        if (!$taiSan) {
            throw new RuntimeException("Thiết bị không tồn tại trong hệ thống.");
        }

        // Kiểm tra quyền quản lý bàn trực
        if ($phongTrucId !== null && $phongTrucId > 0 && (int)$taiSan['phong_truc_id'] !== $phongTrucId) {
            throw new RuntimeException("Thiết bị này không thuộc quyền quản lý của bàn trực bạn phụ trách.");
        }

        // Sinh mã sự cố duy nhất: SC-YYYYMMDD-XXXXXXXX
        $maSuCo = sinh_ma('SC');

        // Thêm bản ghi sự cố mới
        $sqlInsert = "INSERT INTO su_co_tai_san 
                      (ma_su_co, tai_san_id, nhan_vien_lap_id, loai_su_co, mo_ta, trang_thai) 
                      VALUES (?, ?, ?, ?, ?, 'CHUA_XU_LY')";
        $stmtIn = $db->prepare($sqlInsert);
        $stmtIn->bind_param('siiss', $maSuCo, $taiSanId, $nhanVienLapId, $loaiSuCo, $moTa);
        $stmtIn->execute();
        $suCoId = (int)$db->insert_id;
        $stmtIn->close();

        // Cập nhật trạng thái tài sản theo loại sự cố
        if ($loaiSuCo === 'HU_HONG') {
            $db->execute_query("UPDATE tai_san SET tinh_trang = 'HU_HONG', trang_thai = 'BAO_TRI' WHERE id = ?", [$taiSanId]);
        } elseif ($loaiSuCo === 'MAT' || $loaiSuCo === 'SAI_VI_TRI') {
            $db->execute_query("UPDATE tai_san SET trang_thai = 'NGUNG_SU_DUNG' WHERE id = ?", [$taiSanId]);
        }

        $db->commit();

        return [
            'thanh_cong' => true,
            'loi'        => '',
            'id'         => $suCoId,
            'ma_su_co'   => $maSuCo,
        ];
    } catch (Throwable $e) {
        $db->rollback();
        return [
            'thanh_cong' => false,
            'loi'        => $e->getMessage(),
            'id'         => 0,
            'ma_su_co'   => '',
        ];
    }
}

/**
 * Chuyển trạng thái sự cố sang Đang xử lý (DANG_XU_LY).
 * Đảm bảo thỏa mãn CHECK constraint ck_su_co_ket_qua_xu_ly: thoi_gian_xu_ly = NULL, ket_qua_xu_ly = NULL.
 *
 * @param int $suCoId
 * @param int $nhanVienId
 * @param int|null $phongTrucId
 * @return array
 */
function su_co_chuyen_dang_xu_ly(int $suCoId, int $nhanVienId, ?int $phongTrucId = null): array {
    $db = db();

    if ($suCoId <= 0) {
        return ['thanh_cong' => false, 'loi' => 'Mã sự cố không hợp lệ.'];
    }

    $db->begin_transaction();
    try {
        $stmtCheck = $db->prepare("SELECT sc.id, sc.trang_thai, ts.phong_truc_id 
                                   FROM su_co_tai_san sc
                                   JOIN tai_san ts ON sc.tai_san_id = ts.id
                                   WHERE sc.id = ? FOR UPDATE");
        $stmtCheck->bind_param('i', $suCoId);
        $stmtCheck->execute();
        $sc = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        if (!$sc) {
            throw new RuntimeException("Không tìm thấy thông tin sự cố.");
        }

        if ($phongTrucId !== null && $phongTrucId > 0 && (int)$sc['phong_truc_id'] !== $phongTrucId) {
            throw new RuntimeException("Bạn không có quyền xử lý sự cố của bàn trực này.");
        }

        if ($sc['trang_thai'] === 'DA_XU_LY') {
            throw new RuntimeException("Sự cố này đã được xử lý hoàn tất từ trước, không thể chuyển lại trạng thái đang xử lý.");
        }

        // Cập nhật sang DANG_XU_LY, đảm bảo thoi_gian_xu_ly và ket_qua_xu_ly là NULL
        $db->execute_query(
            "UPDATE su_co_tai_san 
             SET trang_thai = 'DANG_XU_LY', thoi_gian_xu_ly = NULL, ket_qua_xu_ly = NULL 
             WHERE id = ?",
            [$suCoId]
        );

        $db->commit();
        return ['thanh_cong' => true, 'loi' => ''];
    } catch (Throwable $e) {
        $db->rollback();
        return ['thanh_cong' => false, 'loi' => $e->getMessage()];
    }
}

/**
 * Hoàn tất xử lý sự cố tài sản (DA_XU_LY).
 * Cập nhật thời gian xử lý = NOW(), kết quả xử lý và khôi phục/cập nhật trạng thái tài sản.
 *
 * @param int $suCoId
 * @param array $duLieu (ket_qua_xu_ly, tinh_trang_tai_san, trang_thai_tai_san, phong_hoc_moi_id)
 * @param int $nhanVienId
 * @param int|null $phongTrucId
 * @return array
 */
function su_co_hoan_tat_xu_ly(int $suCoId, array $duLieu, int $nhanVienId, ?int $phongTrucId = null): array {
    $db = db();

    if ($suCoId <= 0) {
        return ['thanh_cong' => false, 'loi' => 'Mã sự cố không hợp lệ.'];
    }

    $ketQuaXuLy = trim((string)($duLieu['ket_qua_xu_ly'] ?? ''));
    if ($ketQuaXuLy === '') {
        return ['thanh_cong' => false, 'loi' => 'Vui lòng nhập chi tiết kết quả xử lý / sửa chữa / khắc phục sự cố.'];
    }
    if (mb_strlen($ketQuaXuLy, 'UTF-8') > 2000) {
        return ['thanh_cong' => false, 'loi' => 'Kết quả xử lý không được vượt quá 2000 ký tự.'];
    }

    $tinhTrangMoi = trim((string)($duLieu['tinh_trang_tai_san'] ?? 'TOT'));
    if (!in_array($tinhTrangMoi, ['TOT', 'HU_HONG'], true)) {
        $tinhTrangMoi = 'TOT';
    }

    $trangThaiMoi = trim((string)($duLieu['trang_thai_tai_san'] ?? 'SAN_SANG'));
    if (!in_array($trangThaiMoi, ['SAN_SANG', 'BAO_TRI', 'NGUNG_SU_DUNG'], true)) {
        $trangThaiMoi = 'SAN_SANG';
    }

    $phongHocMoiId = doc_so_nguyen('phong_hoc_moi_id', 0, $duLieu);

    $db->begin_transaction();
    try {
        $stmtCheck = $db->prepare("SELECT sc.id, sc.tai_san_id, sc.loai_su_co, sc.trang_thai, sc.thoi_gian_phat_hien, ts.phong_truc_id 
                                   FROM su_co_tai_san sc
                                   JOIN tai_san ts ON sc.tai_san_id = ts.id
                                   WHERE sc.id = ? FOR UPDATE");
        $stmtCheck->bind_param('i', $suCoId);
        $stmtCheck->execute();
        $sc = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        if (!$sc) {
            throw new RuntimeException("Không tìm thấy thông tin sự cố.");
        }

        if ($phongTrucId !== null && $phongTrucId > 0 && (int)$sc['phong_truc_id'] !== $phongTrucId) {
            throw new RuntimeException("Bạn không có quyền xử lý sự cố của bàn trực này.");
        }

        $taiSanId = (int)$sc['tai_san_id'];

        // Cập nhật sự cố sang DA_XU_LY, thoi_gian_xu_ly = GREATEST(NOW(), thoi_gian_phat_hien) để đảm bảo ck_su_co_thoi_gian
        $sqlUpdateSc = "UPDATE su_co_tai_san 
                        SET trang_thai = 'DA_XU_LY', 
                            thoi_gian_xu_ly = GREATEST(NOW(), thoi_gian_phat_hien), 
                            ket_qua_xu_ly = ? 
                        WHERE id = ?";
        $stmtUp = $db->prepare($sqlUpdateSc);
        $stmtUp->bind_param('si', $ketQuaXuLy, $suCoId);
        $stmtUp->execute();
        $stmtUp->close();

        // Cập nhật trạng thái và tình trạng của thiết bị
        if ($phongHocMoiId > 0) {
            $db->execute_query(
                "UPDATE tai_san SET tinh_trang = ?, trang_thai = ?, phong_hoc_hien_tai_id = ? WHERE id = ?",
                [$tinhTrangMoi, $trangThaiMoi, $phongHocMoiId, $taiSanId]
            );
        } else {
            $db->execute_query(
                "UPDATE tai_san SET tinh_trang = ?, trang_thai = ? WHERE id = ?",
                [$tinhTrangMoi, $trangThaiMoi, $taiSanId]
            );
        }

        $db->commit();
        return ['thanh_cong' => true, 'loi' => ''];
    } catch (Throwable $e) {
        $db->rollback();
        return ['thanh_cong' => false, 'loi' => $e->getMessage()];
    }
}

