<?php
/**
 * Dịch vụ xử lý nghiệp vụ Quản lý Tài khoản Cán bộ (nhan_vien).
 * Cung cấp các chức năng: lấy danh sách có lọc và phân trang, thống kê nhanh,
 * thêm mới, chỉnh sửa thông tin, đặt lại mật khẩu, đổi trạng thái hoạt động,
 * và xóa an toàn khi chưa phát sinh dữ liệu giao dịch liên kết.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * Lấy danh sách tài khoản cán bộ có bộ lọc và phân trang.
 *
 * @param array $boLoc Mảng bộ lọc: 'tu_khoa', 'vai_tro', 'phong_truc_id', 'dang_hoat_dong'
 * @param int $trang Trang hiện tại (bắt đầu từ 1)
 * @param int $soMoiTrang Số bản ghi trên mỗi trang (mặc định 15)
 * @return array ['danh_sach' => array, 'tong_so' => int, 'trang' => int, 'so_moi_trang' => int, 'tong_so_trang' => int]
 */
function tai_khoan_danh_sach(array $boLoc = [], int $trang = 1, int $soMoiTrang = 15): array {
    $db = db();
    $trang = max(1, $trang);
    $soMoiTrang = max(1, min(100, $soMoiTrang));
    $viTriBatDau = ($trang - 1) * $soMoiTrang;

    $where = " WHERE 1=1";
    $params = [];
    $types = '';

    // 1. Lọc theo từ khóa (mã nhân viên, tên đăng nhập, họ tên, số điện thoại)
    if (!empty($boLoc['tu_khoa']) && trim((string)$boLoc['tu_khoa']) !== '') {
        $like = '%' . trim((string)$boLoc['tu_khoa']) . '%';
        $where .= " AND (nv.ma_nhan_vien LIKE ? OR nv.ten_dang_nhap LIKE ? OR nv.ho_ten LIKE ? OR nv.so_dien_thoai LIKE ?)";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'ssss';
    }

    // 2. Lọc theo vai trò
    if (!empty($boLoc['vai_tro']) && in_array($boLoc['vai_tro'], [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN], true)) {
        $where .= " AND nv.vai_tro = ?";
        $params[] = $boLoc['vai_tro'];
        $types .= 's';
    }

    // 3. Lọc theo phòng trực phân công
    if (!empty($boLoc['phong_truc_id']) && (int)$boLoc['phong_truc_id'] > 0) {
        $where .= " AND nv.phong_truc_id = ?";
        $params[] = (int)$boLoc['phong_truc_id'];
        $types .= 'i';
    }

    // 4. Lọc theo trạng thái hoạt động
    if (isset($boLoc['dang_hoat_dong']) && $boLoc['dang_hoat_dong'] !== '' && $boLoc['dang_hoat_dong'] !== null) {
        $trangThaiHd = (int)$boLoc['dang_hoat_dong'];
        if (in_array($trangThaiHd, [0, 1], true)) {
            $where .= " AND nv.dang_hoat_dong = ?";
            $params[] = $trangThaiHd;
            $types .= 'i';
        }
    }

    // Đếm tổng số bản ghi
    $sqlDem = "SELECT COUNT(*) FROM nhan_vien nv $where";
    if (!empty($params)) {
        $stmtDem = $db->prepare($sqlDem);
        $stmtDem->bind_param($types, ...$params);
        $stmtDem->execute();
        $tongSo = (int)$stmtDem->get_result()->fetch_row()[0];
        $stmtDem->close();
    } else {
        $tongSo = (int)$db->query($sqlDem)->fetch_row()[0];
    }

    $tongSoTrang = max(1, (int)ceil($tongSo / $soMoiTrang));
    if ($trang > $tongSoTrang) {
        $trang = $tongSoTrang;
        $viTriBatDau = ($trang - 1) * $soMoiTrang;
    }

    // Truy vấn danh sách kèm thông tin phòng trực và tổng số giao dịch đã thực hiện
    $sql = "SELECT nv.id, nv.ma_nhan_vien, nv.ho_ten, nv.so_dien_thoai,
                   nv.ten_dang_nhap, nv.vai_tro, nv.phong_truc_id,
                   nv.dang_hoat_dong, nv.tao_luc, nv.cap_nhat_luc,
                   pt.ma_phong_truc, pt.ten_phong_truc,
                   (
                       (SELECT COUNT(*) FROM phieu_muon pm WHERE pm.nhan_vien_lap_id = nv.id) +
                       (SELECT COUNT(*) FROM chi_tiet_muon ctm WHERE ctm.nhan_vien_nhan_id = nv.id) +
                       (SELECT COUNT(*) FROM dieu_chuyen dc WHERE dc.nhan_vien_lap_id = nv.id) +
                       (SELECT COUNT(*) FROM dot_kiem_ke dkk WHERE dkk.nhan_vien_lap_id = nv.id OR dkk.nhan_vien_huy_id = nv.id) +
                       (SELECT COUNT(*) FROM su_co_tai_san sc WHERE sc.nhan_vien_lap_id = nv.id)
                   ) AS tong_giao_dich
            FROM nhan_vien nv
            LEFT JOIN phong_truc pt ON nv.phong_truc_id = pt.id
            $where
            ORDER BY FIELD(nv.vai_tro, 'QUAN_TRI', 'QUAN_LY', 'NHAN_VIEN') ASC, nv.id ASC
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
        'tong_so_trang' => $tongSoTrang,
    ];
}

/**
 * Thống kê nhanh số lượng tài khoản theo trạng thái và vai trò.
 *
 * @return array ['tong_so' => int, 'dang_hoat_dong' => int, 'tam_khoa' => int, 'quan_tri' => int, 'quan_ly' => int, 'nhan_vien' => int]
 */
function tai_khoan_thong_ke_nhanh(): array {
    $db = db();
    $sql = "SELECT
                COUNT(*) AS tong_so,
                SUM(CASE WHEN dang_hoat_dong = 1 THEN 1 ELSE 0 END) AS dang_hoat_dong,
                SUM(CASE WHEN dang_hoat_dong = 0 THEN 1 ELSE 0 END) AS tam_khoa,
                SUM(CASE WHEN vai_tro = 'QUAN_TRI' THEN 1 ELSE 0 END) AS quan_tri,
                SUM(CASE WHEN vai_tro = 'QUAN_LY' THEN 1 ELSE 0 END) AS quan_ly,
                SUM(CASE WHEN vai_tro = 'NHAN_VIEN' THEN 1 ELSE 0 END) AS nhan_vien
            FROM nhan_vien";
    $row = $db->query($sql)->fetch_assoc();

    return [
        'tong_so'        => (int)($row['tong_so'] ?? 0),
        'dang_hoat_dong' => (int)($row['dang_hoat_dong'] ?? 0),
        'tam_khoa'       => (int)($row['tam_khoa'] ?? 0),
        'quan_tri'       => (int)($row['quan_tri'] ?? 0),
        'quan_ly'        => (int)($row['quan_ly'] ?? 0),
        'nhan_vien'      => (int)($row['nhan_vien'] ?? 0),
    ];
}

/**
 * Tìm thông tin chi tiết một tài khoản cán bộ theo ID (không trả về mat_khau_hash).
 */
function tai_khoan_tim_theo_id(int $id): ?array {
    $db = db();
    $sql = "SELECT nv.id, nv.ma_nhan_vien, nv.ho_ten, nv.so_dien_thoai,
                   nv.ten_dang_nhap, nv.vai_tro, nv.phong_truc_id,
                   nv.dang_hoat_dong, nv.tao_luc, nv.cap_nhat_luc,
                   pt.ma_phong_truc, pt.ten_phong_truc
            FROM nhan_vien nv
            LEFT JOIN phong_truc pt ON nv.phong_truc_id = pt.id
            WHERE nv.id = ?
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Thêm mới hoặc cập nhật thông tin tài khoản cán bộ.
 *
 * @param array $duLieu Mảng dữ liệu từ biểu mẫu
 * @param int|null $id ID tài khoản nếu chỉnh sửa, null nếu tạo mới
 * @param int $nguoiThucHienId ID của quản trị viên đang thao tác (để bảo vệ không tự hạ quyền/khóa chính mình)
 * @return array ['thanh_cong' => bool, 'loi' => string, 'id' => int]
 */
function tai_khoan_luu(array $duLieu, ?int $id = null, int $nguoiThucHienId = 0): array {
    $db = db();

    $maNhanVien = strtoupper(trim((string)($duLieu['ma_nhan_vien'] ?? '')));
    $tenDangNhap = trim((string)($duLieu['ten_dang_nhap'] ?? ''));
    $hoTen = trim((string)($duLieu['ho_ten'] ?? ''));
    $soDienThoai = trim((string)($duLieu['so_dien_thoai'] ?? ''));
    $vaiTro = strtoupper(trim((string)($duLieu['vai_tro'] ?? VAI_TRO_NHAN_VIEN)));
    $phongTrucId = !empty($duLieu['phong_truc_id']) && (int)$duLieu['phong_truc_id'] > 0
        ? (int)$duLieu['phong_truc_id']
        : null;
    $dangHoatDong = isset($duLieu['dang_hoat_dong']) ? (int)$duLieu['dang_hoat_dong'] : 1;
    $dangHoatDong = in_array($dangHoatDong, [0, 1], true) ? $dangHoatDong : 1;

    $matKhau = (string)($duLieu['mat_khau'] ?? '');
    $matKhauXacNhan = (string)($duLieu['xac_nhan_mat_khau'] ?? $matKhau);

    // 1. Kiểm tra các trường bắt buộc
    if ($maNhanVien === '') {
        return ['thanh_cong' => false, 'loi' => 'Mã nhân viên không được để trống.', 'id' => 0];
    }
    if ($tenDangNhap === '') {
        return ['thanh_cong' => false, 'loi' => 'Tên đăng nhập không được để trống.', 'id' => 0];
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $tenDangNhap)) {
        return ['thanh_cong' => false, 'loi' => 'Tên đăng nhập chỉ được chứa chữ cái không dấu, chữ số, dấu chấm, gạch dưới hoặc gạch ngang (từ 3 đến 50 ký tự).', 'id' => 0];
    }
    if ($hoTen === '') {
        return ['thanh_cong' => false, 'loi' => 'Họ và tên cán bộ không được để trống.', 'id' => 0];
    }
    if (!in_array($vaiTro, [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN], true)) {
        return ['thanh_cong' => false, 'loi' => 'Vai trò cán bộ không hợp lệ.', 'id' => 0];
    }

    // 2. Ràng buộc ck_nhan_vien_phong_truc: Nhân viên trực bắt buộc phải có phòng trực phân công
    if ($vaiTro === VAI_TRO_NHAN_VIEN && $phongTrucId === null) {
        return ['thanh_cong' => false, 'loi' => 'Tài khoản Nhân viên trực bắt buộc phải được phân công vào một phòng trực thiết bị.', 'id' => 0];
    }

    // Kiểm tra sự tồn tại của phòng trực nếu có chọn
    if ($phongTrucId !== null) {
        $stmtPt = $db->prepare("SELECT id FROM phong_truc WHERE id = ? LIMIT 1");
        $stmtPt->bind_param('i', $phongTrucId);
        $stmtPt->execute();
        if ($stmtPt->get_result()->num_rows === 0) {
            $stmtPt->close();
            return ['thanh_cong' => false, 'loi' => 'Phòng trực đã chọn không tồn tại trong hệ thống.', 'id' => 0];
        }
        $stmtPt->close();
    }

    // 3. Kiểm tra bảo vệ khi chỉnh sửa tài khoản hiện có
    if ($id !== null) {
        $hienTai = tai_khoan_tim_theo_id($id);
        if ($hienTai === null) {
            return ['thanh_cong' => false, 'loi' => 'Không tìm thấy tài khoản cần cập nhật.', 'id' => 0];
        }

        // Không cho phép tự khóa hoặc tự hạ vai trò của chính mình
        if ($nguoiThucHienId > 0 && $id === $nguoiThucHienId) {
            if ($dangHoatDong === 0) {
                return ['thanh_cong' => false, 'loi' => 'Bạn không thể tự khóa tài khoản đang đăng nhập của chính mình.', 'id' => 0];
            }
            if ($vaiTro !== VAI_TRO_QUAN_TRI && $hienTai['vai_tro'] === VAI_TRO_QUAN_TRI) {
                return ['thanh_cong' => false, 'loi' => 'Bạn không thể tự hạ quyền Quản trị viên của chính mình.', 'id' => 0];
            }
        }

        // Đảm bảo hệ thống luôn còn ít nhất 1 Quản trị viên đang hoạt động
        if ($hienTai['vai_tro'] === VAI_TRO_QUAN_TRI && (int)$hienTai['dang_hoat_dong'] === 1) {
            if ($vaiTro !== VAI_TRO_QUAN_TRI || $dangHoatDong === 0) {
                $stmtAdmin = $db->prepare("SELECT COUNT(*) FROM nhan_vien WHERE vai_tro = 'QUAN_TRI' AND dang_hoat_dong = 1 AND id <> ?");
                $stmtAdmin->bind_param('i', $id);
                $stmtAdmin->execute();
                $soAdminConLai = (int)$stmtAdmin->get_result()->fetch_row()[0];
                $stmtAdmin->close();

                if ($soAdminConLai === 0) {
                    return ['thanh_cong' => false, 'loi' => 'Hệ thống phải có ít nhất một tài khoản Quản trị viên đang hoạt động.', 'id' => 0];
                }
            }
        }
    }

    // 4. Kiểm tra mật khẩu (bắt buộc khi thêm mới, tùy chọn khi chỉnh sửa)
    if ($id === null && $matKhau === '') {
        return ['thanh_cong' => false, 'loi' => 'Vui lòng nhập mật khẩu khởi tạo cho tài khoản mới.', 'id' => 0];
    }
    if ($matKhau !== '') {
        if (strlen($matKhau) < 6) {
            return ['thanh_cong' => false, 'loi' => 'Mật khẩu phải có độ dài tối thiểu 6 ký tự.', 'id' => 0];
        }
        if ($matKhau !== $matKhauXacNhan) {
            return ['thanh_cong' => false, 'loi' => 'Mật khẩu xác nhận không khớp.', 'id' => 0];
        }
    }

    // 5. Kiểm tra trùng mã nhân viên
    if ($id === null) {
        $checkMa = $db->prepare("SELECT id FROM nhan_vien WHERE ma_nhan_vien = ? LIMIT 1");
        $checkMa->bind_param('s', $maNhanVien);
    } else {
        $checkMa = $db->prepare("SELECT id FROM nhan_vien WHERE ma_nhan_vien = ? AND id <> ? LIMIT 1");
        $checkMa->bind_param('si', $maNhanVien, $id);
    }
    $checkMa->execute();
    if ($checkMa->get_result()->num_rows > 0) {
        $checkMa->close();
        return ['thanh_cong' => false, 'loi' => "Mã nhân viên '$maNhanVien' đã tồn tại trong hệ thống.", 'id' => 0];
    }
    $checkMa->close();

    // 6. Kiểm tra trùng tên đăng nhập
    if ($id === null) {
        $checkUser = $db->prepare("SELECT id FROM nhan_vien WHERE ten_dang_nhap = ? LIMIT 1");
        $checkUser->bind_param('s', $tenDangNhap);
    } else {
        $checkUser = $db->prepare("SELECT id FROM nhan_vien WHERE ten_dang_nhap = ? AND id <> ? LIMIT 1");
        $checkUser->bind_param('si', $tenDangNhap, $id);
    }
    $checkUser->execute();
    if ($checkUser->get_result()->num_rows > 0) {
        $checkUser->close();
        return ['thanh_cong' => false, 'loi' => "Tên đăng nhập '$tenDangNhap' đã được sử dụng.", 'id' => 0];
    }
    $checkUser->close();

    $sdtLuu = $soDienThoai !== '' ? $soDienThoai : null;

    // 7. Thực thi lưu vào CSDL
    if ($id === null) {
        $matKhauHash = password_hash($matKhau, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            "INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, so_dien_thoai, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id, dang_hoat_dong)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('ssssssii', $maNhanVien, $hoTen, $sdtLuu, $tenDangNhap, $matKhauHash, $vaiTro, $phongTrucId, $dangHoatDong);
        $stmt->execute();
        $moiId = (int)$db->insert_id;
        $stmt->close();

        return ['thanh_cong' => true, 'loi' => '', 'id' => $moiId];
    } else {
        if ($matKhau !== '') {
            $matKhauHash = password_hash($matKhau, PASSWORD_DEFAULT);
            $stmt = $db->prepare(
                "UPDATE nhan_vien
                 SET ma_nhan_vien = ?, ho_ten = ?, so_dien_thoai = ?, ten_dang_nhap = ?,
                     mat_khau_hash = ?, vai_tro = ?, phong_truc_id = ?, dang_hoat_dong = ?
                 WHERE id = ?"
            );
            $stmt->bind_param('ssssssiii', $maNhanVien, $hoTen, $sdtLuu, $tenDangNhap, $matKhauHash, $vaiTro, $phongTrucId, $dangHoatDong, $id);
        } else {
            $stmt = $db->prepare(
                "UPDATE nhan_vien
                 SET ma_nhan_vien = ?, ho_ten = ?, so_dien_thoai = ?, ten_dang_nhap = ?,
                     vai_tro = ?, phong_truc_id = ?, dang_hoat_dong = ?
                 WHERE id = ?"
            );
            $stmt->bind_param('sssssiii', $maNhanVien, $hoTen, $sdtLuu, $tenDangNhap, $vaiTro, $phongTrucId, $dangHoatDong, $id);
        }
        $stmt->execute();
        $stmt->close();

        return ['thanh_cong' => true, 'loi' => '', 'id' => $id];
    }
}

/**
 * Đặt lại mật khẩu cho một tài khoản cán bộ.
 */
function tai_khoan_dat_lai_mat_khau(int $id, string $matKhauMoi): array {
    $db = db();
    if (strlen($matKhauMoi) < 6) {
        return ['thanh_cong' => false, 'loi' => 'Mật khẩu mới phải có ít nhất 6 ký tự.'];
    }

    $tk = tai_khoan_tim_theo_id($id);
    if ($tk === null) {
        return ['thanh_cong' => false, 'loi' => 'Không tìm thấy tài khoản cán bộ.'];
    }

    $hash = password_hash($matKhauMoi, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE nhan_vien SET mat_khau_hash = ? WHERE id = ?");
    $stmt->bind_param('si', $hash, $id);
    $stmt->execute();
    $stmt->close();

    return ['thanh_cong' => true, 'loi' => ''];
}

/**
 * Bật / Tắt trạng thái hoạt động của tài khoản cán bộ.
 *
 * @param int $id ID tài khoản cần đổi trạng thái
 * @param int $nguoiThucHienId ID của người đang thao tác
 * @return array ['thanh_cong' => bool, 'loi' => string]
 */
function tai_khoan_doi_trang_thai(int $id, int $nguoiThucHienId = 0): array {
    $db = db();
    $tk = tai_khoan_tim_theo_id($id);
    if ($tk === null) {
        return ['thanh_cong' => false, 'loi' => 'Không tìm thấy tài khoản cán bộ.'];
    }

    if ($nguoiThucHienId > 0 && $id === $nguoiThucHienId) {
        return ['thanh_cong' => false, 'loi' => 'Bạn không thể tự khóa tài khoản đang đăng nhập của chính mình.'];
    }

    // Nếu đang hoạt động và là Quản trị viên cuối cùng thì chặn khóa
    if ((int)$tk['dang_hoat_dong'] === 1 && $tk['vai_tro'] === VAI_TRO_QUAN_TRI) {
        $stmtAdmin = $db->prepare("SELECT COUNT(*) FROM nhan_vien WHERE vai_tro = 'QUAN_TRI' AND dang_hoat_dong = 1 AND id <> ?");
        $stmtAdmin->bind_param('i', $id);
        $stmtAdmin->execute();
        $soAdminConLai = (int)$stmtAdmin->get_result()->fetch_row()[0];
        $stmtAdmin->close();

        if ($soAdminConLai === 0) {
            return ['thanh_cong' => false, 'loi' => 'Không thể khóa tài khoản Quản trị viên hoạt động duy nhất của hệ thống.'];
        }
    }

    $stmt = $db->prepare("UPDATE nhan_vien SET dang_hoat_dong = 1 - dang_hoat_dong WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();

    return ['thanh_cong' => true, 'loi' => ''];
}

/**
 * Xóa tài khoản cán bộ nếu chưa từng phát sinh bất kỳ giao dịch nào trong lịch sử.
 *
 * @param int $id ID tài khoản cần xóa
 * @param int $nguoiThucHienId ID người đang thao tác
 * @return array ['thanh_cong' => bool, 'loi' => string]
 */
function tai_khoan_xoa(int $id, int $nguoiThucHienId = 0): array {
    $db = db();
    $tk = tai_khoan_tim_theo_id($id);
    if ($tk === null) {
        return ['thanh_cong' => false, 'loi' => 'Không tìm thấy tài khoản cán bộ cần xóa.'];
    }

    if ($nguoiThucHienId > 0 && $id === $nguoiThucHienId) {
        return ['thanh_cong' => false, 'loi' => 'Bạn không thể tự xóa tài khoản đang đăng nhập của chính mình.'];
    }

    if ($tk['vai_tro'] === VAI_TRO_QUAN_TRI) {
        $stmtAdmin = $db->prepare("SELECT COUNT(*) FROM nhan_vien WHERE vai_tro = 'QUAN_TRI' AND dang_hoat_dong = 1 AND id <> ?");
        $stmtAdmin->bind_param('i', $id);
        $stmtAdmin->execute();
        $soAdminConLai = (int)$stmtAdmin->get_result()->fetch_row()[0];
        $stmtAdmin->close();

        if ($soAdminConLai === 0) {
            return ['thanh_cong' => false, 'loi' => 'Không thể xóa tài khoản Quản trị viên duy nhất của hệ thống.'];
        }
    }

    // Kiểm tra các bảng giao dịch có tham chiếu khóa ngoại tới nhan_vien
    $sqlKt = "SELECT
                (SELECT COUNT(*) FROM phieu_muon WHERE nhan_vien_lap_id = ?) +
                (SELECT COUNT(*) FROM chi_tiet_muon WHERE nhan_vien_nhan_id = ?) +
                (SELECT COUNT(*) FROM dieu_chuyen WHERE nhan_vien_lap_id = ?) +
                (SELECT COUNT(*) FROM dot_kiem_ke WHERE nhan_vien_lap_id = ? OR nhan_vien_huy_id = ?) +
                (SELECT COUNT(*) FROM su_co_tai_san WHERE nhan_vien_lap_id = ?) AS tong_tham_chieu";
    $stmtKt = $db->prepare($sqlKt);
    $stmtKt->bind_param('iiiiii', $id, $id, $id, $id, $id, $id);
    $stmtKt->execute();
    $soThamChieu = (int)$stmtKt->get_result()->fetch_row()[0];
    $stmtKt->close();

    if ($soThamChieu > 0) {
        return [
            'thanh_cong' => false,
            'loi'        => "Không thể xóa tài khoản này vì đã có $soThamChieu bản ghi giao dịch (mượn trả, điều chuyển, kiểm kê hoặc sự cố) liên kết trong lịch sử. Vui lòng chuyển sang trạng thái Khóa tài khoản.",
        ];
    }

    try {
        $stmt = $db->prepare("DELETE FROM nhan_vien WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        return ['thanh_cong' => true, 'loi' => ''];
    } catch (mysqli_sql_exception $e) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Không thể xóa tài khoản do có dữ liệu nghiệp vụ liên kết. Vui lòng sử dụng chức năng Khóa tài khoản.',
        ];
    }
}
