<?php
/**
 * Dịch vụ xử lý nghiệp vụ Danh mục (Phòng trực, Phòng học, Loại tài sản).
 * Cung cấp các thao tác truy vấn, thêm, sửa, đổi trạng thái và kiểm tra ràng buộc trước khi xóa.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

// =====================================================================
// 1. NGHIỆP VỤ PHÒNG TRỰC (phong_truc)
// =====================================================================

/**
 * Lấy danh sách tất cả các phòng trực thiết bị.
 *
 * @param bool $chiHoatDong Nếu true chỉ lấy các phòng đang hoạt động (dang_hoat_dong = 1)
 * @return array Danh sách phòng trực kèm số lượng nhân viên và tài sản trực thuộc
 */
function phong_truc_danh_sach(bool $chiHoatDong = false): array {
    $db = db();
    $sql = "SELECT pt.*,
                   (SELECT COUNT(*) FROM nhan_vien nv WHERE nv.phong_truc_id = pt.id) AS so_nhan_vien,
                   (SELECT COUNT(*) FROM tai_san ts WHERE ts.phong_truc_id = pt.id) AS so_tai_san
            FROM phong_truc pt";
    if ($chiHoatDong) {
        $sql .= " WHERE pt.dang_hoat_dong = 1";
    }
    $sql .= " ORDER BY pt.ma_phong_truc ASC";

    $res = $db->query($sql);
    return $res->fetch_all(MYSQLI_ASSOC);
}

/**
 * Tìm phòng trực theo ID.
 */
function phong_truc_tim_theo_id(int $id): ?array {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM phong_truc WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Thêm mới hoặc cập nhật thông tin phòng trực.
 *
 * @param array $duLieu Mảng dữ liệu: ma_phong_truc, ten_phong_truc, vi_tri, dang_hoat_dong
 * @param int|null $id ID nếu là cập nhật, null nếu là thêm mới
 * @return array Kết quả ['thanh_cong' => bool, 'loi' => string, 'id' => int]
 */
function phong_truc_luu(array $duLieu, ?int $id = null): array {
    $db = db();

    $ma = strtoupper(trim((string)($duLieu['ma_phong_truc'] ?? '')));
    $ten = trim((string)($duLieu['ten_phong_truc'] ?? ''));
    $viTri = trim((string)($duLieu['vi_tri'] ?? ''));
    $dangHoatDong = isset($duLieu['dang_hoat_dong']) ? (int)$duLieu['dang_hoat_dong'] : 1;
    $dangHoatDong = in_array($dangHoatDong, [0, 1], true) ? $dangHoatDong : 1;

    if ($ma === '') {
        return ['thanh_cong' => false, 'loi' => 'Mã phòng trực không được để trống.', 'id' => 0];
    }
    if ($ten === '') {
        return ['thanh_cong' => false, 'loi' => 'Tên phòng trực không được để trống.', 'id' => 0];
    }

    // Kiểm tra trùng mã phòng trực
    if ($id === null) {
        $checkStmt = $db->prepare("SELECT id FROM phong_truc WHERE ma_phong_truc = ? LIMIT 1");
        $checkStmt->bind_param('s', $ma);
    } else {
        $checkStmt = $db->prepare("SELECT id FROM phong_truc WHERE ma_phong_truc = ? AND id <> ? LIMIT 1");
        $checkStmt->bind_param('si', $ma, $id);
    }
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $checkStmt->close();
        return ['thanh_cong' => false, 'loi' => "Mã phòng trực '$ma' đã tồn tại trong hệ thống.", 'id' => 0];
    }
    $checkStmt->close();

    if ($id === null) {
        $stmt = $db->prepare("INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc, vi_tri, dang_hoat_dong) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssi', $ma, $ten, $viTri, $dangHoatDong);
        $stmt->execute();
        $moiId = (int)$db->insert_id;
        $stmt->close();
        return ['thanh_cong' => true, 'loi' => '', 'id' => $moiId];
    } else {
        $stmt = $db->prepare("UPDATE phong_truc SET ma_phong_truc = ?, ten_phong_truc = ?, vi_tri = ?, dang_hoat_dong = ? WHERE id = ?");
        $stmt->bind_param('sssii', $ma, $ten, $viTri, $dangHoatDong, $id);
        $stmt->execute();
        $stmt->close();
        return ['thanh_cong' => true, 'loi' => '', 'id' => $id];
    }
}

/**
 * Xóa phòng trực nếu chưa có dữ liệu phụ thuộc (nhân viên hoặc tài sản).
 */
function phong_truc_xoa(int $id): array {
    $db = db();

    // 1. Kiểm tra ràng buộc nhân viên
    $checkNv = $db->prepare("SELECT COUNT(*) FROM nhan_vien WHERE phong_truc_id = ?");
    $checkNv->bind_param('i', $id);
    $checkNv->execute();
    $soNv = (int)$checkNv->get_result()->fetch_row()[0];
    $checkNv->close();
    if ($soNv > 0) {
        return ['thanh_cong' => false, 'loi' => "Không thể xóa phòng trực này vì đang có $soNv tài khoản cán bộ trực thuộc. Vui lòng chuyển cán bộ sang phòng khác trước."];
    }

    // 2. Kiểm tra ràng buộc tài sản
    $checkTs = $db->prepare("SELECT COUNT(*) FROM tai_san WHERE phong_truc_id = ?");
    $checkTs->bind_param('i', $id);
    $checkTs->execute();
    $soTs = (int)$checkTs->get_result()->fetch_row()[0];
    $checkTs->close();
    if ($soTs > 0) {
        return ['thanh_cong' => false, 'loi' => "Không thể xóa phòng trực này vì đang quản lý $soTs tài sản. Bạn có thể chọn tắt hoạt động thay vì xóa."];
    }

    $stmt = $db->prepare("DELETE FROM phong_truc WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return ['thanh_cong' => true, 'loi' => ''];
}

/**
 * Bật/tắt trạng thái hoạt động của phòng trực.
 */
function phong_truc_doi_trang_thai(int $id): bool {
    $db = db();
    $stmt = $db->prepare("UPDATE phong_truc SET dang_hoat_dong = 1 - dang_hoat_dong WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}


// =====================================================================
// 2. NGHIỆP VỤ PHÒNG HỌC (phong_hoc)
// =====================================================================

/**
 * Lấy danh sách phòng học, hỗ trợ tìm kiếm và lọc hoạt động.
 *
 * @param bool $chiHoatDong
 * @param string|null $tuKhoa Từ khóa tìm kiếm theo mã hoặc tên phòng
 * @return array
 */
function phong_hoc_danh_sach(bool $chiHoatDong = false, ?string $tuKhoa = null): array {
    $db = db();
    $sql = "SELECT ph.*,
                   (SELECT COUNT(*) FROM tai_san ts WHERE ts.phong_hoc_hien_tai_id = ph.id) AS so_tai_san_hien_tai,
                   (SELECT COUNT(*) FROM tai_san ts WHERE ts.phong_hoc_phu_hop_id = ph.id) AS so_tai_san_gan_nhan
            FROM phong_hoc ph WHERE 1=1";

    $params = [];
    $types = '';

    if ($chiHoatDong) {
        $sql .= " AND ph.dang_hoat_dong = 1";
    }

    if ($tuKhoa !== null && trim($tuKhoa) !== '') {
        $sql .= " AND (ph.ma_phong LIKE ? OR ph.ten_phong LIKE ? OR ph.toa_nha LIKE ?)";
        $like = '%' . trim($tuKhoa) . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }

    $sql .= " ORDER BY ph.toa_nha ASC, ph.tang ASC, ph.ma_phong ASC";

    if (!empty($params)) {
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $rows = $db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    return $rows;
}

/**
 * Lấy danh sách tất cả các phòng học đang hoạt động.
 *
 * @return array
 */
function phong_hoc_danh_sach_tat_ca(): array {
    return phong_hoc_danh_sach(true);
}

/**
 * Tìm phòng học theo ID.
 */
function phong_hoc_tim_theo_id(int $id): ?array {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM phong_hoc WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Thêm mới hoặc cập nhật thông tin phòng học kèm cấu hình thiết bị cố định (hãng/model).
 *
 * @param array $duLieu
 * @param int|null $id
 * @return array
 */
function phong_hoc_luu(array $duLieu, ?int $id = null): array {
    $db = db();

    $ma = strtoupper(trim((string)($duLieu['ma_phong'] ?? '')));
    $ten = trim((string)($duLieu['ten_phong'] ?? ''));
    $toaNha = trim((string)($duLieu['toa_nha'] ?? ''));
    $tang = trim((string)($duLieu['tang'] ?? ''));
    $dangHoatDong = isset($duLieu['dang_hoat_dong']) ? (int)$duLieu['dang_hoat_dong'] : 1;
    $dangHoatDong = in_array($dangHoatDong, [0, 1], true) ? $dangHoatDong : 1;

    // Xử lý danh sách thiết bị cố định dạng JSON
    // Thiết bị cố định được gửi lên dưới dạng mảng các object: [{'ten':..., 'hang':..., 'model':...}]
    $thietBiPhong = null;
    if (isset($duLieu['thiet_bi_phong'])) {
        if (is_array($duLieu['thiet_bi_phong'])) {
            $danhSachTb = [];
            foreach ($duLieu['thiet_bi_phong'] as $tb) {
                if (is_array($tb)) {
                    $tenTb = trim((string)($tb['ten'] ?? ''));
                    $hangTb = trim((string)($tb['hang'] ?? ''));
                    $modelTb = trim((string)($tb['model'] ?? ''));
                    if ($tenTb !== '' || $hangTb !== '') {
                        $danhSachTb[] = [
                            'ten'   => $tenTb,
                            'hang'  => $hangTb,
                            'model' => $modelTb,
                        ];
                    }
                }
            }
            $thietBiPhong = !empty($danhSachTb) ? json_encode($danhSachTb, JSON_UNESCAPED_UNICODE) : null;
        } elseif (is_string($duLieu['thiet_bi_phong']) && trim($duLieu['thiet_bi_phong']) !== '') {
            $jsonParsed = json_decode($duLieu['thiet_bi_phong'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonParsed)) {
                $thietBiPhong = json_encode($jsonParsed, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    if ($ma === '') {
        return ['thanh_cong' => false, 'loi' => 'Mã phòng học không được để trống.', 'id' => 0];
    }
    if ($ten === '') {
        return ['thanh_cong' => false, 'loi' => 'Tên phòng học không được để trống.', 'id' => 0];
    }

    // Kiểm tra trùng mã phòng
    if ($id === null) {
        $checkStmt = $db->prepare("SELECT id FROM phong_hoc WHERE ma_phong = ? LIMIT 1");
        $checkStmt->bind_param('s', $ma);
    } else {
        $checkStmt = $db->prepare("SELECT id FROM phong_hoc WHERE ma_phong = ? AND id <> ? LIMIT 1");
        $checkStmt->bind_param('si', $ma, $id);
    }
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $checkStmt->close();
        return ['thanh_cong' => false, 'loi' => "Mã phòng học '$ma' đã tồn tại trong hệ thống.", 'id' => 0];
    }
    $checkStmt->close();

    if ($id === null) {
        $stmt = $db->prepare("INSERT INTO phong_hoc (ma_phong, ten_phong, toa_nha, tang, thiet_bi_phong, dang_hoat_dong) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sssssi', $ma, $ten, $toaNha, $tang, $thietBiPhong, $dangHoatDong);
        $stmt->execute();
        $moiId = (int)$db->insert_id;
        $stmt->close();
        return ['thanh_cong' => true, 'loi' => '', 'id' => $moiId];
    } else {
        $stmt = $db->prepare("UPDATE phong_hoc SET ma_phong = ?, ten_phong = ?, toa_nha = ?, tang = ?, thiet_bi_phong = ?, dang_hoat_dong = ? WHERE id = ?");
        $stmt->bind_param('sssssii', $ma, $ten, $toaNha, $tang, $thietBiPhong, $dangHoatDong, $id);
        $stmt->execute();
        $stmt->close();
        return ['thanh_cong' => true, 'loi' => '', 'id' => $id];
    }
}

/**
 * Xóa phòng học nếu chưa có tài sản hoặc lịch sử phiếu mượn liên kết.
 */
function phong_hoc_xoa(int $id): array {
    $db = db();

    // 1. Kiểm tra tài sản đang ở tại phòng
    $checkTs = $db->prepare("SELECT COUNT(*) FROM tai_san WHERE phong_hoc_hien_tai_id = ? OR phong_hoc_phu_hop_id = ?");
    $checkTs->bind_param('ii', $id, $id);
    $checkTs->execute();
    $soTs = (int)$checkTs->get_result()->fetch_row()[0];
    $checkTs->close();
    if ($soTs > 0) {
        return ['thanh_cong' => false, 'loi' => "Không thể xóa phòng học này vì đang liên kết với $soTs tài sản. Bạn có thể chọn tắt hoạt động để tạm ẩn."];
    }

    // 2. Kiểm tra lịch sử mượn trả
    $checkPm = $db->prepare("SELECT COUNT(*) FROM phieu_muon WHERE phong_hoc_id = ?");
    $checkPm->bind_param('i', $id);
    $checkPm->execute();
    $soPm = (int)$checkPm->get_result()->fetch_row()[0];
    $checkPm->close();
    if ($soPm > 0) {
        return ['thanh_cong' => false, 'loi' => "Không thể xóa phòng học này vì đã có $soPm giao dịch mượn tài sản trong lịch sử truy vết."];
    }

    $stmt = $db->prepare("DELETE FROM phong_hoc WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return ['thanh_cong' => true, 'loi' => ''];
}

/**
 * Bật/tắt trạng thái hoạt động của phòng học.
 */
function phong_hoc_doi_trang_thai(int $id): bool {
    $db = db();
    $stmt = $db->prepare("UPDATE phong_hoc SET dang_hoat_dong = 1 - dang_hoat_dong WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}


// =====================================================================
// 3. NGHIỆP VỤ LOẠI TÀI SẢN (loai_tai_san)
// =====================================================================

/**
 * Lấy danh sách loại tài sản.
 */
function loai_tai_san_danh_sach(bool $chiHoatDong = false): array {
    $db = db();
    $sql = "SELECT lts.*,
                   (SELECT COUNT(*) FROM tai_san ts WHERE ts.loai_tai_san_id = lts.id) AS so_tai_san
            FROM loai_tai_san lts";
    if ($chiHoatDong) {
        $sql .= " WHERE lts.dang_hoat_dong = 1";
    }
    $sql .= " ORDER BY lts.ma_loai ASC";

    return $db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

/**
 * Tìm loại tài sản theo ID.
 */
function loai_tai_san_tim_theo_id(int $id): ?array {
    $db = db();
    $stmt = $db->prepare("SELECT * FROM loai_tai_san WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Thêm mới hoặc cập nhật loại tài sản.
 */
function loai_tai_san_luu(array $duLieu, ?int $id = null): array {
    $db = db();

    $ma = strtoupper(trim((string)($duLieu['ma_loai'] ?? '')));
    $ten = trim((string)($duLieu['ten_loai'] ?? ''));
    $moTa = trim((string)($duLieu['mo_ta'] ?? ''));
    $dangHoatDong = isset($duLieu['dang_hoat_dong']) ? (int)$duLieu['dang_hoat_dong'] : 1;
    $dangHoatDong = in_array($dangHoatDong, [0, 1], true) ? $dangHoatDong : 1;

    if ($ma === '') {
        return ['thanh_cong' => false, 'loi' => 'Mã loại tài sản không được để trống.', 'id' => 0];
    }
    if ($ten === '') {
        return ['thanh_cong' => false, 'loi' => 'Tên loại tài sản không được để trống.', 'id' => 0];
    }

    // Kiểm tra trùng mã
    if ($id === null) {
        $checkStmt = $db->prepare("SELECT id FROM loai_tai_san WHERE ma_loai = ? LIMIT 1");
        $checkStmt->bind_param('s', $ma);
    } else {
        $checkStmt = $db->prepare("SELECT id FROM loai_tai_san WHERE ma_loai = ? AND id <> ? LIMIT 1");
        $checkStmt->bind_param('si', $ma, $id);
    }
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $checkStmt->close();
        return ['thanh_cong' => false, 'loi' => "Mã loại tài sản '$ma' đã tồn tại trong hệ thống.", 'id' => 0];
    }
    $checkStmt->close();

    if ($id === null) {
        $stmt = $db->prepare("INSERT INTO loai_tai_san (ma_loai, ten_loai, mo_ta, dang_hoat_dong) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('sssi', $ma, $ten, $moTa, $dangHoatDong);
        $stmt->execute();
        $moiId = (int)$db->insert_id;
        $stmt->close();
        return ['thanh_cong' => true, 'loi' => '', 'id' => $moiId];
    } else {
        $stmt = $db->prepare("UPDATE loai_tai_san SET ma_loai = ?, ten_loai = ?, mo_ta = ?, dang_hoat_dong = ? WHERE id = ?");
        $stmt->bind_param('sssii', $ma, $ten, $moTa, $dangHoatDong, $id);
        $stmt->execute();
        $stmt->close();
        return ['thanh_cong' => true, 'loi' => '', 'id' => $id];
    }
}

/**
 * Xóa loại tài sản nếu chưa có tài sản liên kết.
 */
function loai_tai_san_xoa(int $id): array {
    $db = db();

    $check = $db->prepare("SELECT COUNT(*) FROM tai_san WHERE loai_tai_san_id = ?");
    $check->bind_param('i', $id);
    $check->execute();
    $soTs = (int)$check->get_result()->fetch_row()[0];
    $check->close();

    if ($soTs > 0) {
        return ['thanh_cong' => false, 'loi' => "Không thể xóa loại tài sản này vì đang có $soTs thiết bị thuộc nhóm này. Hãy đổi sang Tắt hoạt động."];
    }

    $stmt = $db->prepare("DELETE FROM loai_tai_san WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    return ['thanh_cong' => true, 'loi' => ''];
}

/**
 * Bật/tắt trạng thái hoạt động của loại tài sản.
 */
function loai_tai_san_doi_trang_thai(int $id): bool {
    $db = db();
    $stmt = $db->prepare("UPDATE loai_tai_san SET dang_hoat_dong = 1 - dang_hoat_dong WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}
