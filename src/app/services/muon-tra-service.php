<?php
/**
 * Dịch vụ xử lý nghiệp vụ Mượn - Trả tài sản (Loan & Return Service).
 * Đảm bảo các giao dịch tuân thủ nghiêm ngặt nguyên lý ACID:
 *  - Pessimistic locking (SELECT ... FOR UPDATE theo thứ tự ID tăng dần) để triệt tiêu deadlock.
 *  - Khóa bi quan và chặn mượn trùng ở tầng CSDL (tai_san_chua_tra_id UNIQUE).
 *  - Kiểm tra tính tương thích phòng học & thiết bị cố định trước khi cho mượn.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/tai-san-service.php';
require_once __DIR__ . '/danh-muc-service.php';
require_once __DIR__ . '/nguoi-muon-service.php';

/**
 * Kiểm tra xem phòng trực có đang bị khóa bởi đợt kiểm kê đang diễn ra hay không.
 *
 * @param int $phongTrucId ID phòng trực
 * @return bool True nếu đang kiểm kê (bị khóa mượn trả), False nếu bình thường
 */
function muon_kiem_tra_khoa_kiem_ke(int $phongTrucId): bool {
    $db = db();
    $sql = "SELECT id, ma_dot_kiem_ke, ten_dot_kiem_ke 
            FROM dot_kiem_ke 
            WHERE phong_truc_id = ? AND trang_thai = ? 
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $trangThaiDangKk = KIEM_KE_DANG_KIEM_KE;
    $stmt->bind_param('is', $phongTrucId, $trangThaiDangKk);
    $stmt->execute();
    $coKhoa = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $coKhoa;
}

/**
 * Tạo mới phiếu mượn tài sản (Thực thi Transaction ACID an toàn).
 *
 * Quy trình xử lý:
 *  1. Kiểm tra khóa kiểm kê tại bàn trực.
 *  2. Xác thực hoặc đăng ký nhanh hồ sơ người mượn.
 *  3. Xác thực phòng học đích (phải đang hoạt động).
 *  4. Kiểm tra danh sách thiết bị (1 - 30 thiết bị, không trùng ID trong cùng một phiếu).
 *  5. Khóa bi quan các dòng tài sản: SELECT ... FOR UPDATE ORDER BY id ASC.
 *  6. Kiểm tra điều kiện từng tài sản:
 *     - Thuộc bàn trực quản lý của nhân viên lập phiếu.
 *     - Đang sẵn sàng (trang_thai = SAN_SANG, tinh_trang = TOT, phong_hoc_hien_tai_id IS NULL).
 *     - Không có bản ghi nào chưa trả trong chi_tiet_muon.
 *     - Tương thích với phòng học đích (khớp Hãng/Model thiết bị phòng học nếu chọn THEO_THIET_BI).
 *  7. Thêm bản ghi phieu_muon (trang_thai = DANG_MUON).
 *  8. Thêm các bản ghi chi_tiet_muon (tinh_trang_khi_muon = TOT, thoi_gian_tra = NULL).
 *  9. Cập nhật tai_san (trang_thai = DANG_MUON, phong_hoc_hien_tai_id = phong_hoc_id).
 * 10. Commit transaction.
 *
 * @param array $duLieu Dữ liệu đầu vào:
 *                      - nguoi_muon_id HOẶC thong_tin_nguoi_muon (dang_ky_nhanh)
 *                      - phong_hoc_id
 *                      - danh_sach_tai_san_id (mảng các ID số nguyên)
 *                      - han_tra (chuỗi Y-m-d H:i:s)
 *                      - muc_dich_su_dung
 * @param int|null $nhanVienLapId ID nhân viên đang trực bàn lập phiếu
 * @param int|null $phongTrucId ID bàn trực của nhân viên
 * @return array ['thanh_cong' => bool, 'loi' => string, 'ma_phieu' => string, 'id' => int]
 */
function muon_tao_phieu(array $duLieu, ?int $nhanVienLapId = null, ?int $phongTrucId = null): array {
    $db = db();

    $nhanVienLapId = $nhanVienLapId ?? (int)($duLieu['nhan_vien_lap_id'] ?? (tai_khoan_hien_tai()['id'] ?? 0));
    $phongTrucId = $phongTrucId ?? (int)($duLieu['phong_truc_id'] ?? (phong_truc_hien_tai()['id'] ?? 0));

    // 1. Kiểm tra khóa kiểm kê tại phòng trực
    if (muon_kiem_tra_khoa_kiem_ke($phongTrucId)) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Bàn trực hiện đang có một đợt kiểm kê tài sản đang diễn ra. Mọi hoạt động mượn tài sản tạm thời bị khóa cho đến khi chốt hoặc hủy đợt kiểm kê.',
            'ma_phieu'   => '',
            'id'         => 0,
        ];
    }

    // 2. Xác thực hoặc đăng ký nhanh người mượn
    $nguoiMuonId = (int)($duLieu['nguoi_muon_id'] ?? 0);
    if ($nguoiMuonId <= 0 && !empty($duLieu['dang_ky_nhanh']) && is_array($duLieu['dang_ky_nhanh'])) {
        // Đăng ký nhanh người mượn mới
        $resDk = nguoi_muon_luu($duLieu['dang_ky_nhanh']);
        if (!$resDk['thanh_cong']) {
            return [
                'thanh_cong' => false,
                'loi'        => 'Không thể đăng ký người mượn mới: ' . $resDk['loi'],
                'ma_phieu'   => '',
                'id'         => 0,
            ];
        }
        $nguoiMuonId = $resDk['id'];
    }

    if ($nguoiMuonId <= 0) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Vui lòng chọn hoặc khai báo thông tin người mượn (Sinh viên / Giảng viên).',
            'ma_phieu'   => '',
            'id'         => 0,
        ];
    }

    $nguoiMuon = nguoi_muon_tim_theo_id($nguoiMuonId);
    if ($nguoiMuon === null) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Hồ sơ người mượn không tồn tại trong hệ thống.',
            'ma_phieu'   => '',
            'id'         => 0,
        ];
    }

    // 3. Xác thực phòng học đích
    $phongHocId = (int)($duLieu['phong_hoc_id'] ?? 0);
    if ($phongHocId <= 0) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Vui lòng chọn phòng học sử dụng thiết bị.',
            'ma_phieu'   => '',
            'id'         => 0,
        ];
    }

    $phongHoc = phong_hoc_tim_theo_id($phongHocId);
    if ($phongHoc === null || (int)$phongHoc['dang_hoat_dong'] !== 1) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Phòng học đã chọn không tồn tại hoặc hiện đang tạm khóa.',
            'ma_phieu'   => '',
            'id'         => 0,
        ];
    }

    // 4. Kiểm tra danh sách thiết bị
    $dsIdRaw = $duLieu['danh_sach_tai_san_id'] ?? [];
    if (!is_array($dsIdRaw) || empty($dsIdRaw)) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Vui lòng quét hoặc chọn ít nhất một thiết bị cần mượn.',
            'ma_phieu'   => '',
            'id'         => 0,
        ];
    }

    // Ép kiểu số nguyên và loại bỏ trùng lặp trong mảng đầu vào
    $dsIdTs = array_values(array_unique(array_filter(array_map('intval', $dsIdRaw), fn($val) => $val > 0)));

    if (empty($dsIdTs)) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Danh sách thiết bị không hợp lệ.',
            'ma_phieu'   => '',
            'id'         => 0,
        ];
    }

    if (count($dsIdTs) > GIOI_HAN_THIET_BI_MUON_TOI_DA) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Số lượng thiết bị trong một phiếu mượn không được vượt quá ' . GIOI_HAN_THIET_BI_MUON_TOI_DA . ' thiết bị.',
            'ma_phieu'   => '',
            'id'         => 0,
        ];
    }

    // 5. Kiểm tra hạn trả
    $hanTra = trim((string)($duLieu['han_tra'] ?? ''));
    if ($hanTra === '') {
        // Mặc định hạn trả là 17:30 cùng ngày hoặc 4 giờ sau
        $hanTra = date('Y-m-d 17:30:00');
        if (strtotime($hanTra) <= time()) {
            $hanTra = date('Y-m-d H:i:s', time() + 14400); // +4 giờ
        }
    }

    $tsHanTra = strtotime($hanTra);
    if ($tsHanTra === false || $tsHanTra <= time()) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Hạn trả dự kiến phải là một thời điểm trong tương lai so với thời điểm hiện tại.',
            'ma_phieu'   => '',
            'id'         => 0,
        ];
    }

    $mucDich = trim((string)($duLieu['muc_dich_su_dung'] ?? 'Phục vụ giảng dạy / học tập'));

    // Sắp xếp ID tài sản tăng dần để triệt tiêu deadlock khi lock row
    sort($dsIdTs, SORT_NUMERIC);

    // BẮT ĐẦU TRANSACTION
    $db->begin_transaction();

    try {
        // 6. Khóa bi quan (Pessimistic Locking) các dòng tài sản theo thứ tự ID tăng dần
        $placeholders = implode(',', array_fill(0, count($dsIdTs), '?'));
        $types = str_repeat('i', count($dsIdTs));

        $sqlLock = "SELECT ts.*, lts.ten_loai, pt.ten_phong_truc 
                    FROM tai_san ts
                    JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
                    JOIN phong_truc pt ON ts.phong_truc_id = pt.id
                    WHERE ts.id IN ($placeholders)
                    ORDER BY ts.id ASC
                    FOR UPDATE";

        $stmtLock = $db->prepare($sqlLock);
        $stmtLock->bind_param($types, ...$dsIdTs);
        $stmtLock->execute();
        $taiSanLocked = $stmtLock->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtLock->close();

        // Kiểm tra đủ số lượng tài sản tìm thấy
        if (count($taiSanLocked) !== count($dsIdTs)) {
            $db->rollback();
            return [
                'thanh_cong' => false,
                'loi'        => 'Một hoặc nhiều thiết bị trong danh sách yêu cầu không tồn tại trong hệ thống.',
                'ma_phieu'   => '',
                'id'         => 0,
            ];
        }

        // 7. Kiểm tra điều kiện nghiêm ngặt của từng tài sản
        foreach ($taiSanLocked as $ts) {
            $tenTsHienThi = "[{$ts['ma_tai_san']}] {$ts['ten_tai_san']}";

            // 7.1. Kiểm tra quyền sở hữu phòng trực
            if ((int)$ts['phong_truc_id'] !== $phongTrucId) {
                $db->rollback();
                return [
                    'thanh_cong' => false,
                    'loi'        => "Thiết bị $tenTsHienThi không thuộc quản lý của bàn trực {$ts['ten_phong_truc']}. Cán bộ chỉ được cho mượn thiết bị thuộc bàn trực của mình.",
                    'ma_phieu'   => '',
                    'id'         => 0,
                ];
            }

            // 7.2. Kiểm tra trạng thái vận hành
            if ($ts['trang_thai'] !== TRANG_THAI_SAN_SANG) {
                $db->rollback();
                $tenTt = TEN_TRANG_THAI[$ts['trang_thai']] ?? $ts['trang_thai'];
                return [
                    'thanh_cong' => false,
                    'loi'        => "Thiết bị $tenTsHienThi hiện không sẵn sàng cho mượn (Trạng thái hiện tại: $tenTt).",
                    'ma_phieu'   => '',
                    'id'         => 0,
                ];
            }

            // 7.3. Kiểm tra tình trạng vật lý
            if ($ts['tinh_trang'] !== TINH_TRANG_TOT) {
                $db->rollback();
                $tenTtVatLy = TEN_TINH_TRANG[$ts['tinh_trang']] ?? $ts['tinh_trang'];
                return [
                    'thanh_cong' => false,
                    'loi'        => "Thiết bị $tenTsHienThi đang trong tình trạng vật lý: $tenTtVatLy. Không được phép xuất mượn thiết bị hư hỏng.",
                    'ma_phieu'   => '',
                    'id'         => 0,
                ];
            }

            // 7.4. Kiểm tra có giao dịch mượn dở dang chưa trả không
            $stmtKtra = $db->prepare("SELECT id FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL LIMIT 1");
            $stmtKtra->bind_param('i', $ts['id']);
            $stmtKtra->execute();
            $coMuonDoDang = $stmtKtra->get_result()->num_rows > 0;
            $stmtKtra->close();

            if ($coMuonDoDang) {
                $db->rollback();
                return [
                    'thanh_cong' => false,
                    'loi'        => "Thiết bị $tenTsHienThi hiện đang nằm trong một phiếu mượn chưa hoàn tất trả. Hệ thống ngăn chặn mượn trùng tuyệt đối.",
                    'ma_phieu'   => '',
                    'id'         => 0,
                ];
            }

            // 7.5. KIỂM TRA ĐỘ TƯƠNG THÍCH VỚI PHÒNG HỌC ĐÍCH
            $kqTuongThich = tai_san_kiem_tra_tuong_thich($ts, $phongHoc);
            if (!$kqTuongThich['tuong_thich']) {
                $db->rollback();
                return [
                    'thanh_cong' => false,
                    'loi'        => "Thiết bị $tenTsHienThi không tương thích với phòng học {$phongHoc['ma_phong']}: {$kqTuongThich['ly_do']}",
                    'ma_phieu'   => '',
                    'id'         => 0,
                ];
            }
        }

        // 8. Sinh mã phiếu mượn tự động: PM-YYYYMMDD-XXXXXXXX
        $maPhieu = sinh_ma('PM');
        $ghiChu = (string)($duLieu['ghi_chu'] ?? $duLieu['muc_dich_su_dung'] ?? '');

        // 9. Thêm bản ghi vào bảng phieu_muon
        $sqlPm = "INSERT INTO phieu_muon (
                    ma_phieu_muon, nguoi_muon_id, phong_hoc_id, phong_truc_id, nhan_vien_lap_id,
                    thoi_gian_muon, han_tra, ghi_chu, trang_thai
                  ) VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?)";

        $trangThaiPhieu = PHIEU_MUON_DANG_MUON;
        $stmtPm = $db->prepare($sqlPm);
        $stmtPm->bind_param(
            'siiiisss',
            $maPhieu, $nguoiMuonId, $phongHocId, $phongTrucId, $nhanVienLapId,
            $hanTra, $ghiChu, $trangThaiPhieu
        );
        $stmtPm->execute();
        $phieuMuonId = (int)$db->insert_id;
        $stmtPm->close();

        // 10. Thêm các dòng vào chi_tiet_muon và cập nhật tai_san
        $sqlCtm = "INSERT INTO chi_tiet_muon (phieu_muon_id, tai_san_id, tinh_trang_khi_muon, thoi_gian_tra) VALUES (?, ?, 'TOT', NULL)";
        $stmtCtm = $db->prepare($sqlCtm);

        $sqlUpTs = "UPDATE tai_san SET trang_thai = ?, phong_hoc_hien_tai_id = ? WHERE id = ?";
        $stmtUpTs = $db->prepare($sqlUpTs);
        $trangThaiDangMuon = TRANG_THAI_DANG_MUON;

        foreach ($dsIdTs as $tsId) {
            // Thêm chi tiết mượn
            $stmtCtm->bind_param('ii', $phieuMuonId, $tsId);
            $stmtCtm->execute();

            // Cập nhật tài sản sang ĐANG_MUON và gắn phòng học hiện tại
            $stmtUpTs->bind_param('sii', $trangThaiDangMuon, $phongHocId, $tsId);
            $stmtUpTs->execute();
        }

        $stmtCtm->close();
        $stmtUpTs->close();

        // 11. Cam kết Transaction (Commit)
        $db->commit();

        return [
            'thanh_cong' => true,
            'loi'        => '',
            'ma_phieu'   => $maPhieu,
            'id'         => $phieuMuonId,
        ];

    } catch (Throwable $e) {
        $db->rollback();
        error_log('[LOAN ERROR] Lỗi tạo phiếu mượn: ' . $e->getMessage());

        // Kiểm tra xem có phải lỗi trùng khóa UNIQUE trên cột tai_san_chua_tra_id không
        if (str_contains($e->getMessage(), 'uq_chi_tiet_muon_chua_tra') || str_contains($e->getMessage(), 'Duplicate entry')) {
            return [
                'thanh_cong' => false,
                'loi'        => 'Giao dịch bị từ chối do có thiết bị đang được mượn trong một giao dịch khác (Ràng buộc toàn vẹn cơ sở dữ liệu).',
                'ma_phieu'   => '',
                'id'         => 0,
            ];
        }

        return [
            'thanh_cong' => false,
            'loi'        => 'Lỗi hệ thống khi tạo phiếu mượn: ' . $e->getMessage(),
            'ma_phieu'   => '',
            'id'         => 0,
        ];
    }
}

/**
 * Lấy danh sách phiếu mượn có phân trang và bộ lọc tìm kiếm.
 *
 * @param array $boLoc Các tiêu chí: 'tu_khoa', 'phong_truc_id', 'trang_thai', 'ngay_muon', 'nguoi_muon_id'
 * @param int $trang Trang hiện tại (1-based)
 * @param int $soMoiTrang Số lượng bản ghi trên một trang (mặc định 15)
 * @return array
 */
function muon_danh_sach(array $boLoc = [], int $trang = 1, int $soMoiTrang = 15): array {
    $db = db();
    $trang = max(1, $trang);
    $soMoiTrang = max(1, min(100, $soMoiTrang));
    $viTriBatDau = ($trang - 1) * $soMoiTrang;

    $where = " WHERE 1=1";
    $params = [];
    $types = '';

    // 1. Lọc theo phòng trực
    if (!empty($boLoc['phong_truc_id'])) {
        $where .= " AND pm.phong_truc_id = ?";
        $params[] = (int)$boLoc['phong_truc_id'];
        $types .= 'i';
    }

    // 2. Lọc theo trạng thái phiếu (DANG_MUON / DA_TRA)
    if (!empty($boLoc['trang_thai'])) {
        $where .= " AND pm.trang_thai = ?";
        $params[] = (string)$boLoc['trang_thai'];
        $types .= 's';
    }

    // 3. Lọc theo ngày mượn (Y-m-d)
    if (!empty($boLoc['ngay_muon'])) {
        $where .= " AND DATE(pm.thoi_gian_muon) = ?";
        $params[] = (string)$boLoc['ngay_muon'];
        $types .= 's';
    }

    // 4. Lọc theo người mượn cụ thể
    if (!empty($boLoc['nguoi_muon_id'])) {
        $where .= " AND pm.nguoi_muon_id = ?";
        $params[] = (int)$boLoc['nguoi_muon_id'];
        $types .= 'i';
    }

    // 5. Lọc theo từ khóa (Mã phiếu, MSSV, Họ tên người mượn, Mã phòng học)
    if (!empty($boLoc['tu_khoa']) && trim((string)$boLoc['tu_khoa']) !== '') {
        $tuKhoa = '%' . trim((string)$boLoc['tu_khoa']) . '%';
        $where .= " AND (pm.ma_phieu_muon LIKE ? OR nm.ma_so_truong LIKE ? OR nm.ho_ten LIKE ? OR ph.ma_phong LIKE ?)";
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $types .= 'ssss';
    }

    // Đếm tổng số phiếu mượn
    $sqlDem = "SELECT COUNT(*) 
               FROM phieu_muon pm
               JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
               JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
               $where";

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

    // Lấy danh sách phiếu kèm số thiết bị và số thiết bị chưa trả
    $sql = "SELECT pm.*,
                   pm.ma_phieu_muon AS ma_phieu,
                   nm.ho_ten AS ten_nguoi_muon, nm.ma_so_truong, nm.so_dien_thoai, nm.loai_nguoi_muon,
                   ph.ma_phong, ph.ten_phong,
                   pt.ten_phong_truc, pt.ma_phong_truc,
                   nv.ho_ten AS ten_nhan_vien_lap,
                   (SELECT COUNT(*) FROM chi_tiet_muon ctm WHERE ctm.phieu_muon_id = pm.id) AS tong_so_thiet_bi,
                   (SELECT COUNT(*) FROM chi_tiet_muon ctm WHERE ctm.phieu_muon_id = pm.id AND ctm.thoi_gian_tra IS NULL) AS so_chua_tra
            FROM phieu_muon pm
            JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
            JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
            JOIN phong_truc pt ON pm.phong_truc_id = pt.id
            JOIN nhan_vien nv ON pm.nhan_vien_lap_id = nv.id
            $where
            ORDER BY pm.id DESC
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
 * Tìm phiếu mượn theo ID kèm danh sách chi tiết toàn bộ thiết bị.
 *
 * @param int $id ID phiếu mượn
 * @return array|null
 */
function muon_tim_theo_id(int $id): ?array {
    $db = db();
    $sql = "SELECT pm.*,
                   pm.ma_phieu_muon AS ma_phieu,
                   nm.ho_ten AS ten_nguoi_muon, nm.ma_so_truong, nm.so_dien_thoai, nm.lop_don_vi, nm.loai_nguoi_muon,
                   ph.ma_phong, ph.ten_phong, ph.toa_nha, ph.tang,
                   pt.ten_phong_truc, pt.ma_phong_truc,
                   nv.ho_ten AS ten_nhan_vien_lap
            FROM phieu_muon pm
            JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
            JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
            JOIN phong_truc pt ON pm.phong_truc_id = pt.id
            JOIN nhan_vien nv ON pm.nhan_vien_lap_id = nv.id
            WHERE pm.id = ?
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $phieu = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$phieu) {
        return null;
    }

    // Lấy danh sách thiết bị trong phiếu mượn
    $sqlCtm = "SELECT ctm.*,
                      ctm.tinh_trang_khi_muon AS tinh_trang_muon,
                      ctm.tinh_trang_khi_tra AS tinh_trang_tra,
                      ctm.ghi_chu AS ghi_chu_tra,
                      ts.ma_tai_san, ts.ten_tai_san, ts.ma_qr, ts.hang_san_xuat, ts.model,
                      lts.ten_loai,
                      nv_tra.ho_ten AS ten_nhan_vien_nhan
               FROM chi_tiet_muon ctm
               JOIN tai_san ts ON ctm.tai_san_id = ts.id
               JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
               LEFT JOIN nhan_vien nv_tra ON ctm.nhan_vien_nhan_id = nv_tra.id
               WHERE ctm.phieu_muon_id = ?
               ORDER BY ctm.id ASC";

    $stmtCtm = $db->prepare($sqlCtm);
    $stmtCtm->bind_param('i', $id);
    $stmtCtm->execute();
    $phieu['chi_tiet'] = $stmtCtm->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtCtm->close();

    return $phieu;
}

/**
 * Tra cứu phiếu mượn đang hoạt động (chưa trả đủ) theo từ khóa bất kỳ:
 *  - Mã phiếu mượn (PM-...)
 *  - Mã QR của tem thiết bị đang mượn (QR-...)
 *  - Mã tài sản của thiết bị đang mượn (MC-..., MT-...)
 *  - Mã số trường của người mượn (MSSV / MSGV)
 *
 * @param string $tuKhoa Từ khóa tra cứu
 * @param int|null $phongTrucId Giới hạn trong phạm vi bàn trực (nếu có)
 * @return array|null Trả về thông tin phiếu mượn kèm chi tiết thiết bị, hoặc null nếu không tìm thấy
 */
function tra_tim_phieu_theo_tu_khoa(string $tuKhoa, ?int $phongTrucId = null): ?array {
    $db = db();
    $tuKhoa = trim($tuKhoa);
    if ($tuKhoa === '') {
        return null;
    }

    $phieuId = 0;

    // 1. Tìm theo mã phiếu mượn chính xác
    $sql1 = "SELECT id FROM phieu_muon WHERE ma_phieu_muon = ? AND trang_thai = ?";
    $params1 = [$tuKhoa, PHIEU_MUON_DANG_MUON];
    $types1 = 'ss';
    if ($phongTrucId !== null && $phongTrucId > 0) {
        $sql1 .= " AND phong_truc_id = ?";
        $params1[] = $phongTrucId;
        $types1 .= 'i';
    }
    $sql1 .= " LIMIT 1";

    $stmt1 = $db->prepare($sql1);
    $stmt1->bind_param($types1, ...$params1);
    $stmt1->execute();
    $res1 = $stmt1->get_result()->fetch_assoc();
    $stmt1->close();

    if ($res1) {
        $phieuId = (int)$res1['id'];
    }

    // 2. Tìm theo mã QR của thiết bị đang được mượn
    if ($phieuId === 0) {
        $sql2 = "SELECT pm.id 
                 FROM phieu_muon pm
                 JOIN chi_tiet_muon ctm ON pm.id = ctm.phieu_muon_id
                 JOIN tai_san ts ON ctm.tai_san_id = ts.id
                 WHERE ts.ma_qr = ? AND ctm.thoi_gian_tra IS NULL AND pm.trang_thai = ?";
        $params2 = [$tuKhoa, PHIEU_MUON_DANG_MUON];
        $types2 = 'ss';
        if ($phongTrucId !== null && $phongTrucId > 0) {
            $sql2 .= " AND pm.phong_truc_id = ?";
            $params2[] = $phongTrucId;
            $types2 .= 'i';
        }
        $sql2 .= " ORDER BY pm.id DESC LIMIT 1";

        $stmt2 = $db->prepare($sql2);
        $stmt2->bind_param($types2, ...$params2);
        $stmt2->execute();
        $res2 = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        if ($res2) {
            $phieuId = (int)$res2['id'];
        }
    }

    // 3. Tìm theo mã tài sản (Mã thiết bị) đang được mượn
    if ($phieuId === 0) {
        $sql3 = "SELECT pm.id 
                 FROM phieu_muon pm
                 JOIN chi_tiet_muon ctm ON pm.id = ctm.phieu_muon_id
                 JOIN tai_san ts ON ctm.tai_san_id = ts.id
                 WHERE ts.ma_tai_san = ? AND ctm.thoi_gian_tra IS NULL AND pm.trang_thai = ?";
        $params3 = [$tuKhoa, PHIEU_MUON_DANG_MUON];
        $types3 = 'ss';
        if ($phongTrucId !== null && $phongTrucId > 0) {
            $sql3 .= " AND pm.phong_truc_id = ?";
            $params3[] = $phongTrucId;
            $types3 .= 'i';
        }
        $sql3 .= " ORDER BY pm.id DESC LIMIT 1";

        $stmt3 = $db->prepare($sql3);
        $stmt3->bind_param($types3, ...$params3);
        $stmt3->execute();
        $res3 = $stmt3->get_result()->fetch_assoc();
        $stmt3->close();

        if ($res3) {
            $phieuId = (int)$res3['id'];
        }
    }

    // 4. Tìm theo mã số trường (MSSV / MSGV) của người mượn
    if ($phieuId === 0) {
        $sql4 = "SELECT pm.id 
                 FROM phieu_muon pm
                 JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
                 WHERE nm.ma_so_truong = ? AND pm.trang_thai = ?";
        $params4 = [$tuKhoa, PHIEU_MUON_DANG_MUON];
        $types4 = 'ss';
        if ($phongTrucId !== null && $phongTrucId > 0) {
            $sql4 .= " AND pm.phong_truc_id = ?";
            $params4[] = $phongTrucId;
            $types4 .= 'i';
        }
        $sql4 .= " ORDER BY pm.id DESC LIMIT 1";

        $stmt4 = $db->prepare($sql4);
        $stmt4->bind_param($types4, ...$params4);
        $stmt4->execute();
        $res4 = $stmt4->get_result()->fetch_assoc();
        $stmt4->close();

        if ($res4) {
            $phieuId = (int)$res4['id'];
        }
    }

    if ($phieuId > 0) {
        return muon_tim_theo_id($phieuId);
    }

    return null;
}

/**
 * Lấy danh sách các phiếu mượn đang chờ trả tại bàn trực (dùng hiển thị nhanh trong màn hình trả).
 * Ưu tiên các phiếu đã quá hạn trả lên trước để nhân viên dễ nhận diện và xử lý.
 *
 * @param int|null $phongTrucId ID bàn trực
 * @param int $gioiHan Số lượng hiển thị tối đa
 * @return array
 */
function tra_danh_sach_cho_xu_ly(?int $phongTrucId = null, int $gioiHan = 15): array {
    $db = db();
    $phongTrucId = $phongTrucId ?? (int)(phong_truc_hien_tai()['id'] ?? 0);

    $sql = "SELECT pm.*,
                   pm.ma_phieu_muon AS ma_phieu,
                   nm.ho_ten AS ten_nguoi_muon, nm.ma_so_truong, nm.so_dien_thoai,
                   ph.ma_phong, ph.ten_phong,
                   pt.ten_phong_truc, pt.ma_phong_truc,
                   (SELECT COUNT(*) FROM chi_tiet_muon ctm WHERE ctm.phieu_muon_id = pm.id) AS tong_so_thiet_bi,
                   (SELECT COUNT(*) FROM chi_tiet_muon ctm WHERE ctm.phieu_muon_id = pm.id AND ctm.thoi_gian_tra IS NULL) AS so_chua_tra,
                   (pm.han_tra < NOW()) AS la_qua_han
            FROM phieu_muon pm
            JOIN nguoi_muon nm ON pm.nguoi_muon_id = nm.id
            JOIN phong_hoc ph ON pm.phong_hoc_id = ph.id
            JOIN phong_truc pt ON pm.phong_truc_id = pt.id
            WHERE pm.trang_thai = ?";

    $params = [PHIEU_MUON_DANG_MUON];
    $types = 's';

    if ($phongTrucId > 0) {
        $sql .= " AND pm.phong_truc_id = ?";
        $params[] = $phongTrucId;
        $types .= 'i';
    }

    $sql .= " ORDER BY (pm.han_tra < NOW()) DESC, pm.id DESC LIMIT ?";
    $params[] = max(1, min(100, $gioiHan));
    $types .= 'i';

    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $rows;
}

/**
 * Thực hiện tiếp nhận trả tài sản (Hỗ trợ trả từng phần hoặc trả đủ toàn bộ phiếu).
 * Thực thi Transaction ACID an toàn, kiểm tra khóa kiểm kê và tự động ghi nhận sự cố nếu thiết bị bị hư hỏng.
 *
 * @param array $duLieu Dữ liệu gửi lên:
 *                      - phieu_muon_id (int)
 *                      - chi_tiet_id (mảng ID các chi_tiet_muon được chọn trả)
 *                      - tinh_trang (mảng trạng thái khi trả: [chi_tiet_id => 'TOT' | 'HU_HONG'])
 *                      - ghi_chu (mảng ghi chú phản ánh: [chi_tiet_id => string])
 * @param int|null $nhanVienNhanId ID nhân viên tiếp nhận trả (mặc định lấy nhân viên đăng nhập)
 * @param int|null $phongTrucId ID bàn trực tiếp nhận (mặc định lấy bàn trực hiện tại)
 * @return array ['thanh_cong' => bool, 'loi' => string, 'so_da_tra' => int, 'con_lai' => int, 'hoan_tat' => bool, 'ma_phieu' => string]
 */
function tra_thuc_hien(array $duLieu, ?int $nhanVienNhanId = null, ?int $phongTrucId = null): array {
    $db = db();

    $nhanVienNhanId = $nhanVienNhanId ?? (int)($duLieu['nhan_vien_nhan_id'] ?? (tai_khoan_hien_tai()['id'] ?? 0));
    $phongTrucId = $phongTrucId ?? (int)($duLieu['phong_truc_id'] ?? (phong_truc_hien_tai()['id'] ?? 0));

    // 1. Kiểm tra khóa kiểm kê tại bàn trực
    if ($phongTrucId > 0 && muon_kiem_tra_khoa_kiem_ke($phongTrucId)) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Bàn trực hiện đang có đợt kiểm kê tài sản đang diễn ra. Hoạt động nhận trả tạm thời bị khóa.',
            'so_da_tra'  => 0,
            'con_lai'    => 0,
            'hoan_tat'   => false,
            'ma_phieu'   => '',
        ];
    }

    // 2. Xác thực phiếu mượn
    $phieuMuonId = (int)($duLieu['phieu_muon_id'] ?? 0);
    if ($phieuMuonId <= 0) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Không tìm thấy mã định danh phiếu mượn cần trả.',
            'so_da_tra'  => 0,
            'con_lai'    => 0,
            'hoan_tat'   => false,
            'ma_phieu'   => '',
        ];
    }

    // 3. Kiểm tra danh sách thiết bị được tích chọn trả
    $dsChiTietChon = $duLieu['chi_tiet_id'] ?? [];
    if (!is_array($dsChiTietChon) || empty($dsChiTietChon)) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Vui lòng tích chọn ít nhất một thiết bị cần tiếp nhận trả.',
            'so_da_tra'  => 0,
            'con_lai'    => 0,
            'hoan_tat'   => false,
            'ma_phieu'   => '',
        ];
    }

    $dsIdCtm = array_values(array_unique(array_filter(array_map('intval', $dsChiTietChon), fn($id) => $id > 0)));
    if (empty($dsIdCtm)) {
        return [
            'thanh_cong' => false,
            'loi'        => 'Danh sách thiết bị chọn trả không hợp lệ.',
            'so_da_tra'  => 0,
            'con_lai'    => 0,
            'hoan_tat'   => false,
            'ma_phieu'   => '',
        ];
    }

    // Sắp xếp thứ tự ID tăng dần để ngăn ngừa Deadlock khi nhiều bàn trực nhận trả cùng lúc
    sort($dsIdCtm, SORT_NUMERIC);

    $bangTinhTrang = (array)($duLieu['tinh_trang'] ?? []);
    $bangGhiChu = (array)($duLieu['ghi_chu'] ?? []);

    // 4. Bắt đầu Transaction ACID
    $db->begin_transaction();

    try {
        // Khóa dòng phiếu mượn để kiểm tra
        $stmtPm = $db->prepare("SELECT id, ma_phieu_muon, phong_truc_id, trang_thai FROM phieu_muon WHERE id = ? FOR UPDATE");
        $stmtPm->bind_param('i', $phieuMuonId);
        $stmtPm->execute();
        $phieu = $stmtPm->get_result()->fetch_assoc();
        $stmtPm->close();

        if (!$phieu) {
            throw new RuntimeException('Phiếu mượn không tồn tại trong hệ thống.');
        }

        if ($phongTrucId > 0 && (int)$phieu['phong_truc_id'] !== $phongTrucId) {
            throw new RuntimeException('Phiếu mượn này thuộc bàn trực khác, bạn không có thẩm quyền tiếp nhận trả tại bàn trực này.');
        }

        if ($phieu['trang_thai'] !== PHIEU_MUON_DANG_MUON) {
            throw new RuntimeException("Phiếu mượn [{$phieu['ma_phieu_muon']}] đã được xác nhận hoàn tất trả đủ từ trước.");
        }

        $now = date('Y-m-d H:i:s');
        $soLuongDaTra = 0;

        // Chuẩn bị các câu lệnh cập nhật
        $stmtKiemTraCtm = $db->prepare("SELECT ctm.id, ctm.tai_san_id, ctm.thoi_gian_tra, ts.ma_tai_san, ts.ten_tai_san, ts.phong_truc_id
                                        FROM chi_tiet_muon ctm
                                        JOIN tai_san ts ON ctm.tai_san_id = ts.id
                                        WHERE ctm.id = ? AND ctm.phieu_muon_id = ?
                                        FOR UPDATE");

        $stmtCapNhatCtm = $db->prepare("UPDATE chi_tiet_muon 
                                        SET thoi_gian_tra = ?, tinh_trang_khi_tra = ?, nhan_vien_nhan_id = ?, ghi_chu = ? 
                                        WHERE id = ?");

        $stmtCapNhatTs = $db->prepare("UPDATE tai_san 
                                       SET tinh_trang = ?, trang_thai = ?, phong_hoc_hien_tai_id = NULL 
                                       WHERE id = ?");

        $stmtTaoSuCo = $db->prepare("INSERT INTO su_co_tai_san (
                                        ma_su_co, tai_san_id, nhan_vien_lap_id, chi_tiet_muon_id, loai_su_co, mo_ta, trang_thai
                                     ) VALUES (?, ?, ?, ?, 'HU_HONG', ?, 'CHUA_XU_LY')");

        foreach ($dsIdCtm as $ctmId) {
            // Kiểm tra và khóa dòng chi tiết
            $stmtKiemTraCtm->bind_param('ii', $ctmId, $phieuMuonId);
            $stmtKiemTraCtm->execute();
            $detail = $stmtKiemTraCtm->get_result()->fetch_assoc();

            if (!$detail) {
                throw new RuntimeException("Thiết bị chi tiết #$ctmId không thuộc phiếu mượn này.");
            }

            if ($detail['thoi_gian_tra'] !== null) {
                throw new RuntimeException("Thiết bị [{$detail['ma_tai_san']}] đã được hoàn trả từ trước, không thể nhận trả lần 2.");
            }

            if ($phongTrucId > 0 && (int)$detail['phong_truc_id'] !== $phongTrucId) {
                throw new RuntimeException("Thiết bị [{$detail['ma_tai_san']}] đã được điều chuyển sang phòng trực khác.");
            }

            // Tình trạng thiết bị khi nhận lại
            $tinhTrangKhiTra = (string)($bangTinhTrang[$ctmId] ?? 'TOT');
            if (!in_array($tinhTrangKhiTra, [TINH_TRANG_TOT, TINH_TRANG_HU_HONG], true)) {
                $tinhTrangKhiTra = TINH_TRANG_TOT;
            }

            $ghiChuTra = trim((string)($bangGhiChu[$ctmId] ?? ''));

            // QUY TẮC BẮT BUỘC: Nếu thiết bị bị hư hỏng, bắt buộc phải có ghi chú mô tả hư hại
            if ($tinhTrangKhiTra === TINH_TRANG_HU_HONG && $ghiChuTra === '') {
                throw new RuntimeException("Thiết bị [{$detail['ma_tai_san']}] {$detail['ten_tai_san']} được đánh dấu Hư hỏng. Vui lòng ghi rõ mô tả hư hỏng, sự cố hoặc linh kiện bị mất/hỏng.");
            }

            $ghiChuLuu = ($ghiChuTra !== '') ? $ghiChuTra : null;

            // 1. Cập nhật chi_tiet_muon
            $stmtCapNhatCtm->bind_param('ssisi', $now, $tinhTrangKhiTra, $nhanVienNhanId, $ghiChuLuu, $ctmId);
            $stmtCapNhatCtm->execute();

            // 2. Cập nhật tai_san:
            // Nếu TOT -> tinh_trang = TOT, trang_thai = SAN_SANG, vị trí phòng học = NULL (trả về phòng trực)
            // Nếu HU_HONG -> tinh_trang = HU_HONG, trang_thai = BAO_TRI, vị trí phòng học = NULL
            $taiSanId = (int)$detail['tai_san_id'];
            $trangThaiMoi = ($tinhTrangKhiTra === TINH_TRANG_TOT) ? TRANG_THAI_SAN_SANG : TRANG_THAI_BAO_TRI;

            $stmtCapNhatTs->bind_param('ssi', $tinhTrangKhiTra, $trangThaiMoi, $taiSanId);
            $stmtCapNhatTs->execute();

            // 3. Nếu hư hỏng, tự động sinh phiếu sự cố (su_co_tai_san)
            if ($tinhTrangKhiTra === TINH_TRANG_HU_HONG) {
                $maSuCo = sinh_ma('SC');
                $stmtTaoSuCo->bind_param('siiis', $maSuCo, $taiSanId, $nhanVienNhanId, $ctmId, $ghiChuTra);
                $stmtTaoSuCo->execute();
            }

            $soLuongDaTra++;
        }

        $stmtKiemTraCtm->close();
        $stmtCapNhatCtm->close();
        $stmtCapNhatTs->close();
        $stmtTaoSuCo->close();

        // 5. Kiểm tra xem phiếu mượn còn thiết bị nào chưa trả không
        $stmtDem = $db->prepare("SELECT COUNT(*) FROM chi_tiet_muon WHERE phieu_muon_id = ? AND thoi_gian_tra IS NULL");
        $stmtDem->bind_param('i', $phieuMuonId);
        $stmtDem->execute();
        $soConLai = (int)$stmtDem->get_result()->fetch_row()[0];
        $stmtDem->close();

        // Nếu tất cả thiết bị đã được trả -> cập nhật trạng thái phiếu sang DA_TRA
        $hoanTat = ($soConLai === 0);
        if ($hoanTat) {
            $stmtDongPhieu = $db->prepare("UPDATE phieu_muon SET trang_thai = ? WHERE id = ?");
            $ttDaTra = PHIEU_MUON_DA_TRA;
            $stmtDongPhieu->bind_param('si', $ttDaTra, $phieuMuonId);
            $stmtDongPhieu->execute();
            $stmtDongPhieu->close();
        }

        // Commit transaction
        $db->commit();

        return [
            'thanh_cong' => true,
            'loi'        => '',
            'so_da_tra'  => $soLuongDaTra,
            'con_lai'    => $soConLai,
            'hoan_tat'   => $hoanTat,
            'ma_phieu'   => $phieu['ma_phieu_muon'],
        ];

    } catch (Throwable $e) {
        $db->rollback();
        error_log('[RETURN ERROR] Lỗi trả thiết bị: ' . $e->getMessage());

        return [
            'thanh_cong' => false,
            'loi'        => $e->getMessage(),
            'so_da_tra'  => 0,
            'con_lai'    => 0,
            'hoan_tat'   => false,
            'ma_phieu'   => '',
        ];
    }
}
