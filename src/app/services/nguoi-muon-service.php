<?php
/**
 * Dịch vụ xử lý nghiệp vụ Quản lý Người mượn (Sinh viên / Giảng viên).
 * Hỗ trợ tra cứu, phân trang, thêm mới, sửa, tìm kiếm nhanh và truy vết lịch sử mượn trả.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * Lấy danh sách người mượn có phân trang và bộ lọc tìm kiếm.
 *
 * @param array $boLoc Mảng bộ lọc: 'tu_khoa', 'loai_nguoi_muon'
 * @param int $trang Trang hiện tại (bắt đầu từ 1)
 * @param int $soMoiTrang Số lượng bản ghi trên một trang (mặc định 10)
 * @return array ['danh_sach' => array, 'tong_so' => int, 'trang' => int, 'so_moi_trang' => int, 'tong_so_trang' => int]
 */
function nguoi_muon_danh_sach(array $boLoc = [], int $trang = 1, int $soMoiTrang = 10): array {
    $db = db();
    $trang = max(1, $trang);
    $soMoiTrang = max(1, min(100, $soMoiTrang));
    $viTriBatDau = ($trang - 1) * $soMoiTrang;

    $where = " WHERE 1=1";
    $params = [];
    $types = '';

    // 1. Lọc theo loại người mượn
    if (!empty($boLoc['loai_nguoi_muon']) && in_array($boLoc['loai_nguoi_muon'], ['SINH_VIEN', 'GIANG_VIEN'], true)) {
        $where .= " AND nm.loai_nguoi_muon = ?";
        $params[] = $boLoc['loai_nguoi_muon'];
        $types .= 's';
    }

    // 2. Lọc theo từ khóa tìm kiếm
    if (!empty($boLoc['tu_khoa']) && trim((string)$boLoc['tu_khoa']) !== '') {
        $tuKhoa = '%' . trim((string)$boLoc['tu_khoa']) . '%';
        $where .= " AND (nm.ma_nguoi_muon LIKE ? OR nm.ma_so_truong LIKE ? OR nm.ho_ten LIKE ? OR nm.so_dien_thoai LIKE ? OR nm.lop_don_vi LIKE ?)";
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $types .= 'sssss';
    }

    // 3. Lọc theo phòng học đang sử dụng (nếu có)
    if (!empty($boLoc['phong']) && trim((string)$boLoc['phong']) !== '') {
        $phongKw = '%' . trim((string)$boLoc['phong']) . '%';
        $where .= " AND EXISTS (
            SELECT 1 FROM phieu_muon pm_ph
            JOIN phong_hoc ph_f ON pm_ph.phong_hoc_id = ph_f.id
            WHERE pm_ph.nguoi_muon_id = nm.id
              AND (ph_f.ma_phong LIKE ? OR ph_f.ten_phong LIKE ?)
        )";
        $params[] = $phongKw;
        $params[] = $phongKw;
        $types .= 'ss';
    }

    // 4. Lọc theo phòng trực (nếu tài khoản gắn với phòng trực cụ thể, chỉ hiển thị người mượn có giao dịch tại phòng trực này)
    $phongTrucId = !empty($boLoc['phong_truc_id']) ? (int)$boLoc['phong_truc_id'] : null;
    $ptSubWhere = "";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $where .= " AND EXISTS (
            SELECT 1 FROM phieu_muon pm_pt
            WHERE pm_pt.nguoi_muon_id = nm.id AND pm_pt.phong_truc_id = ?
        )";
        $params[] = $phongTrucId;
        $types .= 'i';
        $ptSubWhere = " AND pm.phong_truc_id = " . $phongTrucId;
    }

    // 5. Lọc theo tab trạng thái mượn (dang_muon / qua_han / tat_ca)
    $tab = trim((string)($boLoc['tab'] ?? ''));
    if ($tab === 'dang_muon') {
        $where .= " AND EXISTS (
            SELECT 1 FROM phieu_muon pm_dm
            JOIN chi_tiet_muon ctm_dm ON pm_dm.id = ctm_dm.phieu_muon_id
            WHERE pm_dm.nguoi_muon_id = nm.id AND ctm_dm.thoi_gian_tra IS NULL" . ($phongTrucId ? " AND pm_dm.phong_truc_id = " . (int)$phongTrucId : "") . "
        )";
    } elseif ($tab === 'qua_han') {
        $where .= " AND EXISTS (
            SELECT 1 FROM phieu_muon pm_qh
            JOIN chi_tiet_muon ctm_qh ON pm_qh.id = ctm_qh.phieu_muon_id
            WHERE pm_qh.nguoi_muon_id = nm.id
              AND ctm_qh.thoi_gian_tra IS NULL
              AND pm_qh.han_tra < NOW()" . ($phongTrucId ? " AND pm_qh.phong_truc_id = " . (int)$phongTrucId : "") . "
        )";
    }

    // Đếm tổng số bản ghi
    $sqlDem = "SELECT COUNT(*) FROM nguoi_muon nm $where";
    if (!empty($params)) {
        $stmtDem = $db->prepare($sqlDem);
        $stmtDem->bind_param($types, ...$params);
        $stmtDem->execute();
        $tongSo = (int)$stmtDem->get_result()->fetch_row()[0];
        $stmtDem->close();
    } else {
        $tongSo = (int)$db->query($sqlDem)->fetch_row()[0];
    }

    $tongSoTrang = (int)ceil($tongSo / $soMoiTrang);

    // Lấy danh sách kèm số thiết bị đang mượn, phòng học gần nhất và hạn trả gần nhất
    $sql = "SELECT nm.*,
                   (SELECT COUNT(*) 
                    FROM phieu_muon pm 
                    JOIN chi_tiet_muon ctm ON pm.id = ctm.phieu_muon_id 
                    WHERE pm.nguoi_muon_id = nm.id AND ctm.thoi_gian_tra IS NULL $ptSubWhere
                   ) AS so_thiet_bi_dang_muon,
                   (SELECT COUNT(*) 
                    FROM phieu_muon pm 
                    JOIN chi_tiet_muon ctm ON pm.id = ctm.phieu_muon_id 
                    WHERE pm.nguoi_muon_id = nm.id AND ctm.thoi_gian_tra IS NULL $ptSubWhere
                   ) AS so_thiet_bi_chua_tra,
                   (SELECT COUNT(*) 
                    FROM phieu_muon pm 
                    WHERE pm.nguoi_muon_id = nm.id $ptSubWhere
                   ) AS tong_so_luot_muon,
                   (SELECT ph_sub.ma_phong
                    FROM phieu_muon pm
                    JOIN phong_hoc ph_sub ON pm.phong_hoc_id = ph_sub.id
                    WHERE pm.nguoi_muon_id = nm.id $ptSubWhere
                    ORDER BY (pm.trang_thai = 'DANG_MUON') DESC, pm.id DESC
                    LIMIT 1
                   ) AS phong_hoc_gan_nhat,
                   (SELECT pm.han_tra
                    FROM phieu_muon pm
                    WHERE pm.nguoi_muon_id = nm.id $ptSubWhere
                    ORDER BY (pm.trang_thai = 'DANG_MUON') DESC, pm.id DESC
                    LIMIT 1
                   ) AS han_tra_gan_nhat,
                   (SELECT pm.thoi_gian_muon
                    FROM phieu_muon pm
                    WHERE pm.nguoi_muon_id = nm.id $ptSubWhere
                    ORDER BY pm.id DESC
                    LIMIT 1
                   ) AS thoi_gian_muon_gan_nhat,
                   (SELECT COUNT(*)
                    FROM phieu_muon pm
                    JOIN chi_tiet_muon ctm ON pm.id = ctm.phieu_muon_id
                    WHERE pm.nguoi_muon_id = nm.id
                      AND ctm.thoi_gian_tra IS NULL
                      AND pm.han_tra < NOW() $ptSubWhere
                   ) AS so_thiet_bi_qua_han
            FROM nguoi_muon nm
            $where
            ORDER BY (so_thiet_bi_dang_muon > 0) DESC,
                     COALESCE(thoi_gian_muon_gan_nhat, nm.tao_luc) DESC,
                     nm.id DESC
            LIMIT ?, ?";

    $paramsLay = $params;
    $typesLay = $types . 'ii';
    $paramsLay[] = $viTriBatDau;
    $paramsLay[] = $soMoiTrang;

    $stmt = $db->prepare($sql);
    $stmt->bind_param($typesLay, ...$paramsLay);
    $stmt->execute();
    $danhSach = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return [
        'danh_sach'     => $danhSach,
        'tong_so'       => $tongSo,
        'trang'         => $trang,
        'so_moi_trang'  => $soMoiTrang,
        'tong_so_trang' => max(1, $tongSoTrang),
    ];
}

/**
 * Tìm người mượn theo ID.
 */
function nguoi_muon_tim_theo_id(int $id): ?array {
    $db = db();
    $stmt = $db->prepare("SELECT nm.*,
                                 (SELECT COUNT(*) 
                                  FROM phieu_muon pm 
                                  JOIN chi_tiet_muon ctm ON pm.id = ctm.phieu_muon_id 
                                  WHERE pm.nguoi_muon_id = nm.id AND ctm.thoi_gian_tra IS NULL
                                 ) AS so_thiet_bi_dang_muon
                          FROM nguoi_muon nm 
                          WHERE nm.id = ? 
                          LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Tìm người mượn theo mã số trường (MSSV hoặc MSGV).
 */
function nguoi_muon_tim_theo_ma_so_truong(string $maSoTruong): ?array {
    $db = db();
    $maSoTruong = trim($maSoTruong);
    $stmt = $db->prepare("SELECT nm.*,
                                 (SELECT COUNT(*) 
                                  FROM phieu_muon pm 
                                  JOIN chi_tiet_muon ctm ON pm.id = ctm.phieu_muon_id 
                                  WHERE pm.nguoi_muon_id = nm.id AND ctm.thoi_gian_tra IS NULL
                                 ) AS so_thiet_bi_dang_muon
                          FROM nguoi_muon nm 
                          WHERE nm.ma_so_truong = ? 
                          LIMIT 1");
    $stmt->bind_param('s', $maSoTruong);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Tìm kiếm nhanh người mượn theo từ khóa để gợi ý tự động (Dùng cho API AJAX quét mượn).
 * Tìm theo mã số trường (MSSV/MSGV), số điện thoại hoặc họ tên.
 *
 * @param string $tuKhoa Từ khóa tìm kiếm
 * @param int $gioiHan Số lượng kết quả tối đa (mặc định 10)
 * @return array
 */
function nguoi_muon_tim_kiem_nhanh(string $tuKhoa, int $gioiHan = 10): array {
    $db = db();
    $tuKhoa = trim($tuKhoa);
    if ($tuKhoa === '') {
        return [];
    }

    $like = '%' . $tuKhoa . '%';
    $exact = $tuKhoa;

    // Ưu tiên khớp chính xác mã trường hoặc SĐT lên trước
    $sql = "SELECT nm.id, nm.ma_nguoi_muon, nm.ma_so_truong, nm.ho_ten, 
                   nm.loai_nguoi_muon, nm.lop_don_vi, nm.so_dien_thoai,
                   (SELECT COUNT(*) 
                    FROM phieu_muon pm 
                    JOIN chi_tiet_muon ctm ON pm.id = ctm.phieu_muon_id 
                    WHERE pm.nguoi_muon_id = nm.id AND ctm.thoi_gian_tra IS NULL
                   ) AS so_thiet_bi_dang_muon
            FROM nguoi_muon nm
            WHERE nm.ma_so_truong LIKE ? 
               OR nm.so_dien_thoai LIKE ? 
               OR nm.ho_ten LIKE ?
               OR nm.ma_nguoi_muon LIKE ?
            ORDER BY (nm.ma_so_truong = ?) DESC,
                     (nm.so_dien_thoai = ?) DESC,
                     nm.ho_ten ASC
            LIMIT ?";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('ssssssi', $like, $like, $like, $like, $exact, $exact, $gioiHan);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * Thêm mới hoặc cập nhật thông tin hồ sơ người mượn.
 * Tự động kiểm tra không trùng lặp ma_so_truong, ma_nguoi_muon và so_dien_thoai.
 *
 * @param array $duLieu
 * @param int|null $id
 * @return array ['thanh_cong' => bool, 'loi' => string, 'id' => int]
 */
function nguoi_muon_luu(array $duLieu, ?int $id = null): array {
    $db = db();

    $maSoTruong = trim((string)($duLieu['ma_so_truong'] ?? ''));
    $hoTen = trim((string)($duLieu['ho_ten'] ?? ''));
    $loai = (string)($duLieu['loai_nguoi_muon'] ?? 'SINH_VIEN');
    $lopDonVi = trim((string)($duLieu['lop_don_vi'] ?? ''));
    $sdt = trim((string)($duLieu['so_dien_thoai'] ?? ''));
    $maNguoiMuon = trim((string)($duLieu['ma_nguoi_muon'] ?? ''));

    // Kiểm tra dữ liệu bắt buộc
    if ($maSoTruong === '') {
        return ['thanh_cong' => false, 'loi' => 'Mã số trường (MSSV/MSGV) không được để trống.', 'id' => 0];
    }
    if ($hoTen === '') {
        return ['thanh_cong' => false, 'loi' => 'Họ và tên người mượn không được để trống.', 'id' => 0];
    }
    if (!in_array($loai, ['SINH_VIEN', 'GIANG_VIEN'], true)) {
        return ['thanh_cong' => false, 'loi' => 'Loại người mượn không hợp lệ (chỉ chấp nhận Sinh viên hoặc Giảng viên).', 'id' => 0];
    }

    // Tự sinh mã người mượn nếu chưa có
    if ($maNguoiMuon === '') {
        $maNguoiMuon = 'NM-' . $maSoTruong;
    }

    // Kiểm tra trùng mã số trường (MSSV/MSGV)
    if ($id === null) {
        $checkMst = $db->prepare("SELECT id FROM nguoi_muon WHERE ma_so_truong = ? LIMIT 1");
        $checkMst->bind_param('s', $maSoTruong);
    } else {
        $checkMst = $db->prepare("SELECT id FROM nguoi_muon WHERE ma_so_truong = ? AND id <> ? LIMIT 1");
        $checkMst->bind_param('si', $maSoTruong, $id);
    }
    $checkMst->execute();
    if ($checkMst->get_result()->num_rows > 0) {
        $checkMst->close();
        return ['thanh_cong' => false, 'loi' => "Mã số trường '$maSoTruong' đã tồn tại cho một người mượn khác.", 'id' => 0];
    }
    $checkMst->close();

    // Kiểm tra trùng mã người mượn
    if ($id === null) {
        $checkNm = $db->prepare("SELECT id FROM nguoi_muon WHERE ma_nguoi_muon = ? LIMIT 1");
        $checkNm->bind_param('s', $maNguoiMuon);
    } else {
        $checkNm = $db->prepare("SELECT id FROM nguoi_muon WHERE ma_nguoi_muon = ? AND id <> ? LIMIT 1");
        $checkNm->bind_param('si', $maNguoiMuon, $id);
    }
    $checkNm->execute();
    if ($checkNm->get_result()->num_rows > 0) {
        $checkNm->close();
        return ['thanh_cong' => false, 'loi' => "Mã định danh người mượn '$maNguoiMuon' đã tồn tại.", 'id' => 0];
    }
    $checkNm->close();

    // Kiểm tra trùng số điện thoại (nếu có nhập)
    if ($sdt !== '') {
        if ($id === null) {
            $checkSdt = $db->prepare("SELECT id FROM nguoi_muon WHERE so_dien_thoai = ? LIMIT 1");
            $checkSdt->bind_param('s', $sdt);
        } else {
            $checkSdt = $db->prepare("SELECT id FROM nguoi_muon WHERE so_dien_thoai = ? AND id <> ? LIMIT 1");
            $checkSdt->bind_param('si', $sdt, $id);
        }
        $checkSdt->execute();
        if ($checkSdt->get_result()->num_rows > 0) {
            $checkSdt->close();
            return ['thanh_cong' => false, 'loi' => "Số điện thoại '$sdt' đã được sử dụng bởi một người mượn khác.", 'id' => 0];
        }
        $checkSdt->close();
    }

    $sdtDb = $sdt !== '' ? $sdt : null;

    if ($id === null) {
        $stmt = $db->prepare("INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon, lop_don_vi, so_dien_thoai) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssss', $maNguoiMuon, $maSoTruong, $hoTen, $loai, $lopDonVi, $sdtDb);
        $stmt->execute();
        $moiId = (int)$db->insert_id;
        $stmt->close();
        return ['thanh_cong' => true, 'loi' => '', 'id' => $moiId];
    } else {
        $stmt = $db->prepare("UPDATE nguoi_muon SET ma_nguoi_muon = ?, ma_so_truong = ?, ho_ten = ?, loai_nguoi_muon = ?, lop_don_vi = ?, so_dien_thoai = ? WHERE id = ?");
        $stmt->bind_param('ssssssi', $maNguoiMuon, $maSoTruong, $hoTen, $loai, $lopDonVi, $sdtDb, $id);
        $stmt->execute();
        $stmt->close();
        return ['thanh_cong' => true, 'loi' => '', 'id' => $id];
    }
}

/**
 * Lưu hoặc cập nhật hồ sơ người mượn trực tiếp khi thực hiện giao dịch mượn:
 * Đảm bảo mọi thông tin người mượn khai báo tại quầy luôn được lưu vào bảng nguoi_muon.
 *
 * @param array $duLieu ['ma_so_truong', 'ho_ten', 'loai_nguoi_muon', 'lop_don_vi', 'so_dien_thoai']
 * @return array ['thanh_cong' => bool, 'loi' => string, 'id' => int]
 */
function nguoi_muon_luu_khi_muon(array $duLieu): array {
    $db = db();
    $maSoTruong = strtoupper(trim((string)($duLieu['ma_so_truong'] ?? '')));
    $hoTen = trim((string)($duLieu['ho_ten'] ?? ''));
    $loai = in_array($duLieu['loai_nguoi_muon'] ?? '', ['SINH_VIEN', 'GIANG_VIEN'], true)
        ? (string)$duLieu['loai_nguoi_muon']
        : 'SINH_VIEN';
    $lopDonVi = trim((string)($duLieu['lop_don_vi'] ?? ''));
    $sdt = trim((string)($duLieu['so_dien_thoai'] ?? ''));

    // Nếu nhân viên chỉ nhập mã số hoặc chỉ nhập họ tên, bổ sung giá trị hợp lệ để lưu hồ sơ
    if ($maSoTruong === '' && $hoTen === '') {
        return ['thanh_cong' => false, 'loi' => 'Vui lòng nhập Mã số hoặc Họ tên người mượn.', 'id' => 0];
    }
    if ($maSoTruong === '') {
        $maSoTruong = ($sdt !== '') ? ('NM' . preg_replace('/\D+/', '', $sdt)) : ('NM' . date('ymdHis'));
    }
    if ($hoTen === '') {
        $hoTen = 'Người mượn ' . $maSoTruong;
    }

    // 1. Kiểm tra xem mã số trường đã có trong bảng nguoi_muon chưa
    $nmTonTai = nguoi_muon_tim_theo_ma_so_truong($maSoTruong);
    if ($nmTonTai !== null) {
        $nmId = (int)$nmTonTai['id'];
        $hoTenCapNhat = ($hoTen !== '' && $hoTen !== ('Người mượn ' . $maSoTruong)) ? $hoTen : (string)$nmTonTai['ho_ten'];
        $lopCapNhat = $lopDonVi !== '' ? $lopDonVi : (string)($nmTonTai['lop_don_vi'] ?? '');
        $sdtCapNhat = $sdt !== '' ? $sdt : (string)($nmTonTai['so_dien_thoai'] ?? '');
        $sdtDb = $sdtCapNhat !== '' ? $sdtCapNhat : null;

        $stmtUp = $db->prepare("UPDATE nguoi_muon SET ho_ten = ?, loai_nguoi_muon = ?, lop_don_vi = ?, so_dien_thoai = ? WHERE id = ?");
        $stmtUp->bind_param('ssssi', $hoTenCapNhat, $loai, $lopCapNhat, $sdtDb, $nmId);
        $stmtUp->execute();
        $stmtUp->close();

        return ['thanh_cong' => true, 'loi' => '', 'id' => $nmId];
    }

    // 2. Tạo mới hồ sơ người mượn trong bảng nguoi_muon
    $maNguoiMuon = 'NM-' . $maSoTruong;
    $checkMa = $db->prepare("SELECT id FROM nguoi_muon WHERE ma_nguoi_muon = ? LIMIT 1");
    $checkMa->bind_param('s', $maNguoiMuon);
    $checkMa->execute();
    if ($checkMa->get_result()->num_rows > 0) {
        $maNguoiMuon = 'NM-' . $maSoTruong . '-' . rand(10, 99);
    }
    $checkMa->close();

    $sdtDb = $sdt !== '' ? $sdt : null;
    $stmtIns = $db->prepare("INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon, lop_don_vi, so_dien_thoai) VALUES (?, ?, ?, ?, ?, ?)");
    $stmtIns->bind_param('ssssss', $maNguoiMuon, $maSoTruong, $hoTen, $loai, $lopDonVi, $sdtDb);
    $stmtIns->execute();
    $moiId = (int)$db->insert_id;
    $stmtIns->close();

    return ['thanh_cong' => $moiId > 0, 'loi' => '', 'id' => $moiId];
}

/**
 * Xóa hồ sơ người mượn (tự động giải phóng thiết bị đang mượn nếu có và dọn lịch sử phiếu mượn liên kết).
 */
function nguoi_muon_xoa(int $id): array {
    $db = db();
    if ($id <= 0) {
        return ['thanh_cong' => false, 'loi' => 'ID người mượn không hợp lệ.'];
    }

    $db->begin_transaction();
    try {
        // 1. Hoàn trả trạng thái SAN_SANG cho các thiết bị người này đang mượn chưa trả (nếu có)
        $sqlTs = "UPDATE tai_san ts
                  JOIN chi_tiet_muon ctm ON ctm.tai_san_id = ts.id
                  JOIN phieu_muon pm ON ctm.phieu_muon_id = pm.id
                  SET ts.trang_thai = 'SAN_SANG', ts.phong_hoc_hien_tai_id = NULL
                  WHERE pm.nguoi_muon_id = ? AND ctm.thoi_gian_tra IS NULL";
        $stmtTs = $db->prepare($sqlTs);
        $stmtTs->bind_param('i', $id);
        $stmtTs->execute();
        $stmtTs->close();

        // 2. Xóa chi tiết mượn thuộc các phiếu mượn của người này
        $sqlCtm = "DELETE ctm FROM chi_tiet_muon ctm
                   JOIN phieu_muon pm ON ctm.phieu_muon_id = pm.id
                   WHERE pm.nguoi_muon_id = ?";
        $stmtCtm = $db->prepare($sqlCtm);
        $stmtCtm->bind_param('i', $id);
        $stmtCtm->execute();
        $stmtCtm->close();

        // 3. Xóa các phiếu mượn của người này
        $stmtPm = $db->prepare("DELETE FROM phieu_muon WHERE nguoi_muon_id = ?");
        $stmtPm->bind_param('i', $id);
        $stmtPm->execute();
        $stmtPm->close();

        // 4. Xóa hồ sơ người mượn
        $stmt = $db->prepare("DELETE FROM nguoi_muon WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        return ['thanh_cong' => true, 'loi' => ''];
    } catch (Throwable $e) {
        $db->rollback();
        return ['thanh_cong' => false, 'loi' => 'Không thể xóa hồ sơ người mượn: ' . $e->getMessage()];
    }
}

/**
 * Lấy toàn bộ lịch sử các phiếu mượn và danh sách thiết bị của một người mượn.
 *
 * @param int $nguoiMuonId
 * @param int|null $phongTrucId Lọc theo phòng trực (nếu cán bộ được gán phòng trực)
 * @return array Danh sách phiếu mượn kèm chi tiết thiết bị
 */
function nguoi_muon_lich_su_muon(int $nguoiMuonId, ?int $phongTrucId = null): array {
    $db = db();

    $wherePt = "";
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $wherePt = " AND pm.phong_truc_id = " . (int)$phongTrucId;
    }

    // 1. Lấy danh sách phiếu mượn
    $sql = "SELECT pm.*, 
                   pm.ma_phieu_muon AS ma_phieu,
                   pm.ghi_chu AS muc_dich_su_dung,
                   ph.ma_phong, ph.ten_phong,
                   pt.ten_phong_truc,
                   nv.ho_ten AS ten_nhan_vien_lap
            FROM phieu_muon pm
            JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
            JOIN phong_truc pt ON pm.phong_truc_id = pt.id
            JOIN nhan_vien nv ON pm.nhan_vien_lap_id = nv.id
            WHERE pm.nguoi_muon_id = ? $wherePt
            ORDER BY pm.thoi_gian_muon DESC";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $nguoiMuonId);
    $stmt->execute();
    $cacPhieu = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($cacPhieu)) {
        return [];
    }

    // 2. Lấy danh sách thiết bị chi tiết cho các phiếu mượn
    $idPhieus = array_column($cacPhieu, 'id');
    $placeholders = implode(',', array_fill(0, count($idPhieus), '?'));
    $types = str_repeat('i', count($idPhieus));

    $sqlCt = "SELECT ctm.*,
                     ts.ma_tai_san, ts.ten_tai_san, ts.ma_qr,
                     nv_tra.ho_ten AS ten_nhan_vien_nhan
              FROM chi_tiet_muon ctm
              JOIN tai_san ts ON ctm.tai_san_id = ts.id
              LEFT JOIN nhan_vien nv_tra ON ctm.nhan_vien_nhan_id = nv_tra.id
              WHERE ctm.phieu_muon_id IN ($placeholders)
              ORDER BY ctm.id ASC";

    $stmtCt = $db->prepare($sqlCt);
    $stmtCt->bind_param($types, ...$idPhieus);
    $stmtCt->execute();
    $cacChiTiet = $stmtCt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtCt->close();

    // Gom nhóm chi tiết theo phieu_muon_id
    $chiTietTheoPhieu = [];
    foreach ($cacChiTiet as $ct) {
        $pId = (int)$ct['phieu_muon_id'];
        $chiTietTheoPhieu[$pId][] = $ct;
    }

    foreach ($cacPhieu as &$phieu) {
        $pId = (int)$phieu['id'];
        $phieu['chi_tiet'] = $chiTietTheoPhieu[$pId] ?? [];
    }
    unset($phieu);

    return $cacPhieu;
}

