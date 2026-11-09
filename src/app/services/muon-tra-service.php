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
