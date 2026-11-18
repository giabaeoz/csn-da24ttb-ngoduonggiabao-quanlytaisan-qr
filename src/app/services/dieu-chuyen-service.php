<?php
/**
 * Dịch vụ nghiệp vụ Điều chuyển thiết bị giữa các phòng học / phòng trực (G7).
 * Xử lý kiểm tra điều kiện an toàn, ràng buộc đợt kiểm kê, tính tương thích thiết bị,
 * và thực hiện chuyển vị trí tài sản trong transaction có khóa dòng an toàn.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/tai-san-service.php';
require_once __DIR__ . '/danh-muc-service.php';

/**
 * Kiểm tra xem phòng trực của nhân viên có đang bị khóa bởi đợt kiểm kê hay không.
 *
 * @param int $phongTrucId ID phòng trực cần kiểm tra
 * @return bool True nếu đang kiểm kê (bị khóa)
 */
function dieu_chuyen_kiem_tra_khoa_kiem_ke(int $phongTrucId): bool {
    if ($phongTrucId <= 0) {
        return false;
    }
    $db = db();
    $sql = "SELECT id, ma_dot_kiem_ke, ten_dot_kiem_ke 
            FROM dot_kiem_ke 
            WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE' 
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $phongTrucId);
    $stmt->execute();
    $res = $stmt->get_result();
    $coKhoa = $res->num_rows > 0;
    $stmt->close();
    return $coKhoa;
}

/**
 * Lấy danh sách phiếu điều chuyển có phân trang và bộ lọc tìm kiếm.
 *
 * @param array $boLoc Bộ lọc (tu_khoa, phong_hoc_di_id, phong_hoc_den_id, phong_truc_id, tu_ngay, den_ngay)
 * @param int $trang Trang hiện tại (1-based)
 * @param int $soMoiTrang Số bản ghi trên mỗi trang
 * @return array ['danh_sach' => array, 'tong_so' => int, 'trang' => int, 'so_moi_trang' => int, 'tong_trang' => int]
 */
function dieu_chuyen_danh_sach(array $boLoc = [], int $trang = 1, int $soMoiTrang = 15): array {
    $db = db();
    $trang = max(1, $trang);
    $soMoiTrang = max(1, min(100, $soMoiTrang));
    $offset = ($trang - 1) * $soMoiTrang;

    $where = ["1=1"];
    $types = '';
    $params = [];

    // Lọc theo từ khóa (Mã phiếu, tên phòng, người lập)
    $tuKhoa = trim((string)($boLoc['tu_khoa'] ?? ''));
    if ($tuKhoa !== '') {
        $where[] = "(dc.ma_dieu_chuyen LIKE ? OR pdi.ma_phong LIKE ? OR pdi.ten_phong LIKE ? OR pden.ma_phong LIKE ? OR pden.ten_phong LIKE ? OR nv.ho_ten LIKE ?)";
        $like = '%' . $tuKhoa . '%';
        $types .= 'ssssss';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // Lọc theo phòng học đi
    $phongDiId = (int)($boLoc['phong_hoc_di_id'] ?? 0);
    if ($phongDiId > 0) {
        $where[] = "dc.phong_hoc_di_id = ?";
        $types .= 'i';
        $params[] = $phongDiId;
    }

    // Lọc theo phòng học đến
    $phongDenId = (int)($boLoc['phong_hoc_den_id'] ?? 0);
    if ($phongDenId > 0) {
        $where[] = "dc.phong_hoc_den_id = ?";
        $types .= 'i';
        $params[] = $phongDenId;
    }

    // Lọc theo phòng trực (chỉ hiển thị phiếu có chứa tài sản do phòng trực này quản lý)
    $phongTrucId = (int)($boLoc['phong_truc_id'] ?? 0);
    if ($phongTrucId > 0) {
        $where[] = "EXISTS (
            SELECT 1 FROM chi_tiet_dieu_chuyen ctdc_sub 
            JOIN tai_san ts_sub ON ctdc_sub.tai_san_id = ts_sub.id 
            WHERE ctdc_sub.dieu_chuyen_id = dc.id AND ts_sub.phong_truc_id = ?
        )";
        $types .= 'i';
        $params[] = $phongTrucId;
    }

    // Lọc theo ngày
    $tuNgay = trim((string)($boLoc['tu_ngay'] ?? ''));
    if ($tuNgay !== '') {
        $where[] = "dc.thoi_gian_dieu_chuyen >= ?";
        $types .= 's';
        $params[] = $tuNgay . ' 00:00:00';
    }

    $denNgay = trim((string)($boLoc['den_ngay'] ?? ''));
    if ($denNgay !== '') {
        $where[] = "dc.thoi_gian_dieu_chuyen <= ?";
        $types .= 's';
        $params[] = $denNgay . ' 23:59:59';
    }

    $whereSql = implode(' AND ', $where);

    // 1. Đếm tổng số
    $sqlCount = "SELECT COUNT(DISTINCT dc.id) AS tong 
                 FROM dieu_chuyen dc
                 JOIN phong_hoc pdi ON dc.phong_hoc_di_id = pdi.id
                 JOIN phong_hoc pden ON dc.phong_hoc_den_id = pden.id
                 JOIN nhan_vien nv ON dc.nhan_vien_lap_id = nv.id
                 WHERE $whereSql";
    $stmtCount = $db->prepare($sqlCount);
    if ($types !== '') {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $tongSo = (int)($stmtCount->get_result()->fetch_assoc()['tong'] ?? 0);
    $stmtCount->close();

    $tongTrang = (int)ceil($tongSo / $soMoiTrang);

    // 2. Lấy dữ liệu trang
    $sqlData = "SELECT dc.id, dc.ma_dieu_chuyen,
                       dc.thoi_gian_dieu_chuyen, dc.thoi_gian_dieu_chuyen AS thoi_gian_tao,
                       dc.ly_do, dc.ghi_chu, dc.tao_luc,
                       pdi.id AS phong_di_id, pdi.ma_phong AS ma_phong_di, pdi.ten_phong AS ten_phong_di,
                       pden.id AS phong_den_id, pden.ma_phong AS ma_phong_den, pden.ten_phong AS ten_phong_den,
                       nv.id AS nhan_vien_lap_id, nv.ho_ten AS ho_ten_nguoi_lap, nv.ho_ten AS ho_ten_can_bo, nv.ma_nhan_vien AS ma_nhan_vien_lap,
                       (SELECT COUNT(*) FROM chi_tiet_dieu_chuyen WHERE dieu_chuyen_id = dc.id) AS so_luong_tai_san,
                       (SELECT COUNT(*) FROM chi_tiet_dieu_chuyen WHERE dieu_chuyen_id = dc.id) AS tong_so_thiet_bi
                FROM dieu_chuyen dc
                JOIN phong_hoc pdi ON dc.phong_hoc_di_id = pdi.id
                JOIN phong_hoc pden ON dc.phong_hoc_den_id = pden.id
                JOIN nhan_vien nv ON dc.nhan_vien_lap_id = nv.id
                WHERE $whereSql
                ORDER BY dc.thoi_gian_dieu_chuyen DESC, dc.id DESC
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
 * Tìm phiếu điều chuyển chi tiết theo ID.
 *
 * @param int $id ID phiếu điều chuyển
 * @param int|null $phongTrucId Giới hạn phạm vi phòng trực (nếu có)
 * @return array|null Dữ liệu chi tiết phiếu kèm mảng chi tiết thiết bị
 */
function dieu_chuyen_tim_theo_id(int $id, ?int $phongTrucId = null): ?array {
    if ($id <= 0) {
        return null;
    }
    $db = db();

    $wherePhongTruc = "";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $wherePhongTruc = " AND EXISTS (
            SELECT 1 FROM chi_tiet_dieu_chuyen ctdc_chk 
            JOIN tai_san ts_chk ON ctdc_chk.tai_san_id = ts_chk.id 
            WHERE ctdc_chk.dieu_chuyen_id = dc.id AND ts_chk.phong_truc_id = " . (int)$phongTrucId . "
        )";
    }

    $sql = "SELECT dc.id, dc.ma_dieu_chuyen, dc.thoi_gian_dieu_chuyen, dc.ly_do, dc.ghi_chu, dc.tao_luc, dc.cap_nhat_luc,
                   pdi.id AS phong_di_id, pdi.ma_phong AS ma_phong_di, pdi.ten_phong AS ten_phong_di,
                   pden.id AS phong_den_id, pden.ma_phong AS ma_phong_den, pden.ten_phong AS ten_phong_den,
                   nv.id AS nhan_vien_lap_id, nv.ho_ten AS ho_ten_nguoi_lap, nv.ma_nhan_vien AS ma_nhan_vien_lap,
                   pt.ten_phong_truc AS ten_phong_truc_nhan_vien
            FROM dieu_chuyen dc
            JOIN phong_hoc pdi ON dc.phong_hoc_di_id = pdi.id
            JOIN phong_hoc pden ON dc.phong_hoc_den_id = pden.id
            JOIN nhan_vien nv ON dc.nhan_vien_lap_id = nv.id
            LEFT JOIN phong_truc pt ON nv.phong_truc_id = pt.id
            WHERE dc.id = ? $wherePhongTruc
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $phieu = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$phieu) {
        return null;
    }

    // Lấy danh sách thiết bị con trong phiếu
    $sqlCt = "SELECT ctdc.id AS chi_tiet_id, ctdc.ghi_chu AS ghi_chu_chi_tiet,
                     ts.id AS tai_san_id, ts.ma_tai_san, ts.ma_qr, ts.ten_tai_san,
                     ts.hang_san_xuat, ts.model, ts.tinh_trang, ts.trang_thai,
                     ts.pham_vi_tuong_thich, ts.phong_tuong_thich,
                     lts.ten_loai AS ten_loai_tai_san,
                     pt.ten_phong_truc AS ten_phong_truc_quan_ly
              FROM chi_tiet_dieu_chuyen ctdc
              JOIN tai_san ts ON ctdc.tai_san_id = ts.id
              LEFT JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
              LEFT JOIN phong_truc pt ON ts.phong_truc_id = pt.id
              WHERE ctdc.dieu_chuyen_id = ?
              ORDER BY ts.ma_tai_san ASC";
    $stmtCt = $db->prepare($sqlCt);
    $stmtCt->bind_param('i', $id);
    $stmtCt->execute();
    $phieu['chi_tiet'] = $stmtCt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtCt->close();

    return $phieu;
}

/**
 * Tìm phiếu điều chuyển theo Mã phiếu (phục vụ quét mã QR phiếu điều chuyển).
 *
 * @param string $maDieuChuyen Mã phiếu điều chuyển (DC-YYYYMMDD-XXXXXXXX)
 * @return array|null Dữ liệu phiếu
 */
function dieu_chuyen_tim_theo_ma(string $maDieuChuyen): ?array {
    $maDieuChuyen = trim($maDieuChuyen);
    if ($maDieuChuyen === '') {
        return null;
    }
    $db = db();
    $stmt = $db->prepare("SELECT id FROM dieu_chuyen WHERE ma_dieu_chuyen = ? LIMIT 1");
    $stmt->bind_param('s', $maDieuChuyen);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        return null;
    }
    return dieu_chuyen_tim_theo_id((int)$row['id']);
}

/**
 * Lấy danh sách các tài sản hợp lệ đang nằm tại một phòng học nguồn để sẵn sàng điều chuyển.
 *
 * @param int $phongHocDiId ID phòng học nguồn
 * @param int $phongTrucId ID phòng trực của cán bộ thao tác
 * @return array Danh sách tài sản
 */
function dieu_chuyen_lay_tai_san_hop_le(int $phongHocDiId, int $phongTrucId): array {
    if ($phongHocDiId <= 0 || $phongTrucId <= 0) {
        return [];
    }
    $db = db();
    $sql = "SELECT ts.id, ts.ma_tai_san, ts.ma_qr, ts.ten_tai_san, ts.hang_san_xuat, ts.model,
                   ts.tinh_trang, ts.trang_thai, ts.pham_vi_tuong_thich, ts.phong_tuong_thich,
                   ts.phong_hoc_hien_tai_id, ts.phong_hoc_phu_hop_id,
                   lts.ten_loai AS ten_loai_tai_san
            FROM tai_san ts
            LEFT JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
            WHERE (ts.phong_hoc_hien_tai_id = ? OR (ts.phong_hoc_hien_tai_id IS NULL AND ts.phong_hoc_phu_hop_id = ?))
              AND ts.phong_truc_id = ?
              AND ts.trang_thai IN ('SAN_SANG', 'DANG_MUON')
              AND ts.tinh_trang = 'TOT'
            ORDER BY ts.ma_tai_san ASC";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('iii', $phongHocDiId, $phongHocDiId, $phongTrucId);
    $stmt->execute();
    $danhSach = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $danhSach;
}

/**
 * Thực hiện nghiệp vụ lập phiếu điều chuyển thiết bị giữa hai phòng học.
 *
 * @param array $duLieu Dữ liệu gửi lên:
 *                      - phong_hoc_di_id (int)
 *                      - phong_hoc_den_id (int)
 *                      - ly_do (string)
 *                      - ghi_chu (string|null)
 *                      - xac_nhan_tuong_thich (bool|string)
 *                      - tai_san_ids (array<int>)
 * @param int $nhanVienLapId ID nhân viên lập phiếu
 * @param int|null $phongTrucId ID phòng trực của nhân viên (nếu null sẽ tự tra cứu)
 * @return array ['thanh_cong' => bool, 'loi' => string, 'id' => int, 'ma_dieu_chuyen' => string]
 */
function dieu_chuyen_tao(array $duLieu, int $nhanVienLapId, ?int $phongTrucId = null): array {
    $db = db();

    // 1. Kiểm tra tham số cơ bản
    $phongDiId = doc_so_nguyen('phong_hoc_di_id', 0, $duLieu);
    $phongDenId = doc_so_nguyen('phong_hoc_den_id', 0, $duLieu);

    if ($phongDiId <= 0 || $phongDenId <= 0) {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Vui lòng chọn đầy đủ Phòng học nguồn (đi) và Phòng học đích (đến).',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }

    if ($phongDiId === $phongDenId) {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Phòng học nguồn và phòng học đích phải khác nhau.',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }

    $xacNhanTuongThich = (string)($duLieu['xac_nhan_tuong_thich'] ?? '');
    if ($xacNhanTuongThich !== '1' && $xacNhanTuongThich !== 'true') {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Bạn cần xác nhận đã kiểm tra tính tương thích của thiết bị với phòng học đích trước khi điều chuyển.',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }

    $lyDo = trim((string)($duLieu['ly_do'] ?? ''));
    if ($lyDo === '') {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Vui lòng nhập lý do điều chuyển thiết bị.',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }
    if (mb_strlen($lyDo, 'UTF-8') > 2000) {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Lý do điều chuyển không được vượt quá 2000 ký tự.',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }

    $ghiChu = trim((string)($duLieu['ghi_chu'] ?? ''));
    if (mb_strlen($ghiChu, 'UTF-8') > 2000) {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Ghi chú không được vượt quá 2000 ký tự.',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }

    $rawAssetIds = $duLieu['tai_san_ids'] ?? $duLieu['tai_san_id'] ?? [];
    if (!is_array($rawAssetIds) || empty($rawAssetIds)) {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Vui lòng chọn ít nhất 1 thiết bị cần điều chuyển.',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }

    if (count($rawAssetIds) > 30) {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Mỗi phiếu điều chuyển tối đa 30 thiết bị.',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }

    $assetIds = [];
    foreach ($rawAssetIds as $aid) {
        $cleanId = (int)$aid;
        if ($cleanId > 0) {
            $assetIds[] = $cleanId;
        }
    }

    if (empty($assetIds)) {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Danh sách thiết bị chọn điều chuyển không hợp lệ.',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }

    // Loại bỏ trùng lặp và kiểm tra
    if (count(array_unique($assetIds)) !== count($assetIds)) {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Danh sách chứa thiết bị trùng lặp. Mỗi thiết bị chỉ được chọn 1 lần.',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }

    // Sắp xếp ID tăng dần để khóa dòng theo thứ tự nhất quán chống deadlock
    sort($assetIds, SORT_NUMERIC);

    // 2. Xác định phòng trực của nhân viên nếu chưa truyền vào
    if ($phongTrucId === null || $phongTrucId <= 0) {
        $stmtNv = $db->prepare("SELECT phong_truc_id, vai_tro FROM nhan_vien WHERE id = ? LIMIT 1");
        $stmtNv->bind_param('i', $nhanVienLapId);
        $stmtNv->execute();
        $nvRow = $stmtNv->get_result()->fetch_assoc();
        $stmtNv->close();

        if (!$nvRow) {
            return [
                'thanh_cong'    => false,
                'loi'           => 'Không tìm thấy thông tin tài khoản nhân viên lập phiếu.',
                'id'            => 0,
                'ma_dieu_chuyen'=> '',
            ];
        }
        $phongTrucId = (int)($nvRow['phong_truc_id'] ?? 0);
    }

    // Kiểm tra phòng trực có đang diễn ra kiểm kê không
    if ($phongTrucId > 0 && dieu_chuyen_kiem_tra_khoa_kiem_ke($phongTrucId)) {
        return [
            'thanh_cong'    => false,
            'loi'           => 'Phòng trực hiện đang có đợt kiểm kê tài sản đang diễn ra. Hoạt động điều chuyển tạm thời bị khóa cho đến khi chốt đợt kiểm kê.',
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }

    // 3. Bắt đầu TRANSACTION
    $db->begin_transaction();

    try {
        // Khóa kiểm tra phòng đi và phòng đến
        $stmtRoomDi = $db->prepare("SELECT id, ma_phong, ten_phong, dang_hoat_dong FROM phong_hoc WHERE id = ? FOR SHARE");
        $stmtRoomDi->bind_param('i', $phongDiId);
        $stmtRoomDi->execute();
        $roomDi = $stmtRoomDi->get_result()->fetch_assoc();
        $stmtRoomDi->close();

        $stmtRoomDen = $db->prepare("SELECT id, ma_phong, ten_phong, dang_hoat_dong, thiet_bi_phong FROM phong_hoc WHERE id = ? FOR SHARE");
        $stmtRoomDen->bind_param('i', $phongDenId);
        $stmtRoomDen->execute();
        $roomDen = $stmtRoomDen->get_result()->fetch_assoc();
        $stmtRoomDen->close();

        if (!$roomDi || (int)$roomDi['dang_hoat_dong'] !== 1) {
            throw new RuntimeException("Phòng học nguồn [ID: $phongDiId] không tồn tại hoặc đã ngừng hoạt động.");
        }
        if (!$roomDen || (int)$roomDen['dang_hoat_dong'] !== 1) {
            throw new RuntimeException("Phòng học đích [ID: $phongDenId] không tồn tại hoặc đã ngừng hoạt động.");
        }

        // Kiểm tra từng tài sản với khóa dòng FOR UPDATE
        foreach ($assetIds as $aid) {
            $stmtTs = $db->prepare("SELECT ts.id, ts.ma_tai_san, ts.ma_qr, ts.ten_tai_san, ts.phong_truc_id, 
                                           ts.phong_hoc_hien_tai_id, ts.phong_hoc_phu_hop_id,
                                           ts.pham_vi_tuong_thich, ts.phong_tuong_thich, 
                                           ts.hang_san_xuat, ts.model, ts.tinh_trang, ts.trang_thai,
                                           ph_ht.ma_phong AS ma_phong_hien_tai,
                                           ph_ph.ma_phong AS ma_phong_phu_hop
                                    FROM tai_san ts
                                    LEFT JOIN phong_hoc ph_ht ON ts.phong_hoc_hien_tai_id = ph_ht.id
                                    LEFT JOIN phong_hoc ph_ph ON ts.phong_hoc_phu_hop_id = ph_ph.id
                                    WHERE ts.id = ? FOR UPDATE");
            $stmtTs->bind_param('i', $aid);
            $stmtTs->execute();
            $ts = $stmtTs->get_result()->fetch_assoc();
            $stmtTs->close();

            if (!$ts) {
                throw new RuntimeException("Không tìm thấy thiết bị có ID: $aid trong hệ thống.");
            }

            // Kiểm tra quyền sở hữu phòng trực
            if ($phongTrucId > 0 && (int)$ts['phong_truc_id'] !== $phongTrucId) {
                throw new RuntimeException("Thiết bị [{$ts['ma_tai_san']}] {$ts['ten_tai_san']} không thuộc thẩm quyền quản lý của phòng trực bạn.");
            }

            // Kiểm tra vị trí phòng nguồn:
            // Chấp nhận nếu thiết bị đang ở phòng đi (phong_hoc_hien_tai_id == $phongDiId),
            // hoặc có nhãn phòng kệ gắn với phòng đi (phong_hoc_phu_hop_id == $phongDiId khi chưa gán vị trí hiện tại),
            // hoặc nằm trong danh sách phòng tương thích, hoặc chưa gắn cố định phòng nào.
            $phongHienTaiId = (int)($ts['phong_hoc_hien_tai_id'] ?? 0);
            $phongPhuHopId  = (int)($ts['phong_hoc_phu_hop_id'] ?? 0);

            $dsPhongTuongThich = [];
            if (!empty($ts['phong_tuong_thich'])) {
                $decoded = is_string($ts['phong_tuong_thich']) ? json_decode($ts['phong_tuong_thich'], true) : $ts['phong_tuong_thich'];
                if (is_array($decoded)) {
                    $dsPhongTuongThich = array_map('strtoupper', array_map('trim', $decoded));
                }
            }
            $khopPhongTuongThich = in_array(strtoupper((string)$roomDi['ma_phong']), $dsPhongTuongThich, true);

            if ($phongHienTaiId > 0) {
                if ($phongHienTaiId !== $phongDiId && $phongPhuHopId !== $phongDiId && !$khopPhongTuongThich) {
                    $tenPhongDangO = $ts['ma_phong_hien_tai'] ?: ('ID #' . $phongHienTaiId);
                    throw new RuntimeException("Thiết bị [{$ts['ma_tai_san']}] hiện đang ở Phòng {$tenPhongDangO}, không phải phòng học nguồn đã chọn (Phòng {$roomDi['ma_phong']}).");
                }
            } elseif ($phongPhuHopId > 0) {
                if ($phongPhuHopId !== $phongDiId && !$khopPhongTuongThich) {
                    $tenPhongKe = $ts['ma_phong_phu_hop'] ?: ('ID #' . $phongPhuHopId);
                    throw new RuntimeException("Thiết bị [{$ts['ma_tai_san']}] thuộc kệ Phòng {$tenPhongKe}, không phải phòng học nguồn đã chọn (Phòng {$roomDi['ma_phong']}).");
                }
            }

            // Kiểm tra trạng thái và tình trạng (cho phép cả thiết bị SAN_SANG lẫn thiết bị DANG_MUON tại phòng nguồn)
            if (!in_array($ts['trang_thai'], ['SAN_SANG', 'DANG_MUON'], true) || $ts['tinh_trang'] !== 'TOT') {
                $tt = $ts['trang_thai'] === 'BAO_TRI' ? 'Bảo trì/Sự cố' : $ts['trang_thai'];
                throw new RuntimeException("Thiết bị [{$ts['ma_tai_san']}] không ở tình trạng tốt hoặc đang bảo trì (Hiện tại: $tt).");
            }

            // Kiểm tra tính tương thích với phòng học đích
            $kqTuongThich = tai_san_kiem_tra_tuong_thich($ts, $roomDen);
            if (!$kqTuongThich['tuong_thich']) {
                throw new RuntimeException("Thiết bị [{$ts['ma_tai_san']}] {$ts['ten_tai_san']} không tương thích với phòng đích {$roomDen['ma_phong']}: {$kqTuongThich['ly_do']}");
            }
        }

        // Sinh mã phiếu điều chuyển: DC-YYYYMMDD-XXXXXXXX
        $maDieuChuyen = sinh_ma('DC');

        // Ghi bản ghi phiếu điều chuyển
        $sqlInsertDc = "INSERT INTO dieu_chuyen 
                        (ma_dieu_chuyen, phong_hoc_di_id, phong_hoc_den_id, nhan_vien_lap_id, ly_do, ghi_chu) 
                        VALUES (?, ?, ?, ?, ?, ?)";
        $stmtInsertDc = $db->prepare($sqlInsertDc);
        $ghiChuVal = $ghiChu !== '' ? $ghiChu : null;
        $stmtInsertDc->bind_param('siiiss', $maDieuChuyen, $phongDiId, $phongDenId, $nhanVienLapId, $lyDo, $ghiChuVal);
        $stmtInsertDc->execute();
        $dieuChuyenId = (int)$db->insert_id;
        $stmtInsertDc->close();

        // Ghi chi tiết điều chuyển và cập nhật vị trí thiết bị sang phòng đích
        $sqlInsertCt = "INSERT INTO chi_tiet_dieu_chuyen (dieu_chuyen_id, tai_san_id, ghi_chu) VALUES (?, ?, ?)";
        $stmtInsertCt = $db->prepare($sqlInsertCt);

        $sqlUpdateTs = "UPDATE tai_san 
                        SET phong_hoc_hien_tai_id = ?,
                            phong_hoc_phu_hop_id = CASE 
                                WHEN phong_hoc_phu_hop_id = ? OR phong_hoc_phu_hop_id IS NULL THEN ? 
                                ELSE phong_hoc_phu_hop_id 
                            END
                        WHERE id = ?";
        $stmtUpdateTs = $db->prepare($sqlUpdateTs);

        foreach ($assetIds as $aid) {
            $ghiChuCt = null;
            $stmtInsertCt->bind_param('iis', $dieuChuyenId, $aid, $ghiChuCt);
            $stmtInsertCt->execute();

            $stmtUpdateTs->bind_param('iiii', $phongDenId, $phongDiId, $phongDenId, $aid);
            $stmtUpdateTs->execute();
        }

        $stmtInsertCt->close();
        $stmtUpdateTs->close();

        // Commit transaction thành công
        $db->commit();

        return [
            'thanh_cong'    => true,
            'loi'           => '',
            'id'            => $dieuChuyenId,
            'ma_dieu_chuyen'=> $maDieuChuyen,
        ];
    } catch (Throwable $e) {
        $db->rollback();
        return [
            'thanh_cong'    => false,
            'loi'           => $e->getMessage(),
            'id'            => 0,
            'ma_dieu_chuyen'=> '',
        ];
    }
}
