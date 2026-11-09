<?php
/**
 * Dịch vụ xử lý nghiệp vụ Quản lý Tài sản / Thiết bị (Asset Service).
 * Hỗ trợ tra cứu, phân trang, thêm mới, sửa, sinh mã QR, kiểm tra tương thích phòng học
 * và kiểm soát an toàn trạng thái vận hành.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * Lấy danh sách tài sản có phân trang và bộ lọc đa tiêu chí.
 *
 * @param array $boLoc Các tiêu chí: 'tu_khoa', 'phong_truc_id', 'loai_tai_san_id', 'tinh_trang', 'trang_thai'
 * @param int $trang Trang hiện tại (1-based)
 * @param int $soMoiTrang Số lượng bản ghi trên một trang (mặc định 15)
 * @return array
 */
function tai_san_danh_sach(array $boLoc = [], int $trang = 1, int $soMoiTrang = 15): array {
    $db = db();
    $trang = max(1, $trang);
    $soMoiTrang = max(1, min(100, $soMoiTrang));
    $viTriBatDau = ($trang - 1) * $soMoiTrang;

    $where = " WHERE 1=1";
    $params = [];
    $types = '';

    // 1. Lọc theo phòng trực quản lý
    if (!empty($boLoc['phong_truc_id'])) {
        $where .= " AND ts.phong_truc_id = ?";
        $params[] = (int)$boLoc['phong_truc_id'];
        $types .= 'i';
    }

    // 2. Lọc theo chủng loại
    if (!empty($boLoc['loai_tai_san_id'])) {
        $where .= " AND ts.loai_tai_san_id = ?";
        $params[] = (int)$boLoc['loai_tai_san_id'];
        $types .= 'i';
    }

    // 3. Lọc theo tình trạng vật lý (TOT, HU_HONG, MAT)
    if (!empty($boLoc['tinh_trang'])) {
        $where .= " AND ts.tinh_trang = ?";
        $params[] = (string)$boLoc['tinh_trang'];
        $types .= 's';
    }

    // 4. Lọc theo trạng thái vận hành (SAN_SANG, DANG_MUON, BAO_TRI, NGUNG_SU_DUNG)
    if (!empty($boLoc['trang_thai'])) {
        $where .= " AND ts.trang_thai = ?";
        $params[] = (string)$boLoc['trang_thai'];
        $types .= 's';
    }

    // 5. Lọc theo từ khóa tìm kiếm (mã tài sản, mã QR, tên tài sản, hãng, model)
    if (!empty($boLoc['tu_khoa']) && trim((string)$boLoc['tu_khoa']) !== '') {
        $tuKhoa = '%' . trim((string)$boLoc['tu_khoa']) . '%';
        $where .= " AND (ts.ma_tai_san LIKE ? OR ts.ma_qr LIKE ? OR ts.ten_tai_san LIKE ? OR ts.hang_san_xuat LIKE ? OR ts.model LIKE ?)";
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $params[] = $tuKhoa;
        $types .= 'sssss';
    }

    // Đếm tổng số bản ghi
    $sqlDem = "SELECT COUNT(*) FROM tai_san ts $where";
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

    // Lấy dữ liệu chi tiết kèm thông tin liên kết
    $sql = "SELECT ts.*,
                   lts.ten_loai, lts.ma_loai,
                   pt.ten_phong_truc, pt.ma_phong_truc,
                   ph_hientai.ma_phong AS ma_phong_hien_tai, ph_hientai.ten_phong AS ten_phong_hien_tai,
                   ph_phuhop.ma_phong AS ma_phong_phu_hop, ph_phuhop.ten_phong AS ten_phong_phu_hop
            FROM tai_san ts
            JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
            JOIN phong_truc pt ON ts.phong_truc_id = pt.id
            LEFT JOIN phong_hoc ph_hientai ON ts.phong_hoc_hien_tai_id = ph_hientai.id
            LEFT JOIN phong_hoc ph_phuhop ON ts.phong_hoc_phu_hop_id = ph_phuhop.id
            $where
            ORDER BY ts.id DESC
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
 * Tìm tài sản theo ID.
 */
function tai_san_tim_theo_id(int $id): ?array {
    $db = db();
    $sql = "SELECT ts.*,
                   lts.ten_loai, lts.ma_loai,
                   pt.ten_phong_truc, pt.ma_phong_truc,
                   ph_hientai.ma_phong AS ma_phong_hien_tai, ph_hientai.ten_phong AS ten_phong_hien_tai,
                   ph_phuhop.ma_phong AS ma_phong_phu_hop, ph_phuhop.ten_phong AS ten_phong_phu_hop
            FROM tai_san ts
            JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
            JOIN phong_truc pt ON ts.phong_truc_id = pt.id
            LEFT JOIN phong_hoc ph_hientai ON ts.phong_hoc_hien_tai_id = ph_hientai.id
            LEFT JOIN phong_hoc ph_phuhop ON ts.phong_hoc_phu_hop_id = ph_phuhop.id
            WHERE ts.id = ?
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Tìm tài sản theo mã tài sản (ma_tai_san).
 */
function tai_san_tim_theo_ma(string $maTaiSan): ?array {
    $db = db();
    $maTaiSan = trim($maTaiSan);
    $sql = "SELECT ts.*,
                   lts.ten_loai, lts.ma_loai,
                   pt.ten_phong_truc, pt.ma_phong_truc,
                   ph_hientai.ma_phong AS ma_phong_hien_tai, ph_hientai.ten_phong AS ten_phong_hien_tai,
                   ph_phuhop.ma_phong AS ma_phong_phu_hop, ph_phuhop.ten_phong AS ten_phong_phu_hop
            FROM tai_san ts
            JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
            JOIN phong_truc pt ON ts.phong_truc_id = pt.id
            LEFT JOIN phong_hoc ph_hientai ON ts.phong_hoc_hien_tai_id = ph_hientai.id
            LEFT JOIN phong_hoc ph_phuhop ON ts.phong_hoc_phu_hop_id = ph_phuhop.id
            WHERE ts.ma_tai_san = ?
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $maTaiSan);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Tìm tài sản theo mã QR (chuỗi mã vạch quét từ camera).
 */
function tai_san_tim_theo_qr(string $maQr): ?array {
    $db = db();
    $maQr = trim($maQr);
    $sql = "SELECT ts.*,
                   lts.ten_loai, lts.ma_loai,
                   pt.ten_phong_truc, pt.ma_phong_truc,
                   ph_hientai.ma_phong AS ma_phong_hien_tai, ph_hientai.ten_phong AS ten_phong_hien_tai,
                   ph_phuhop.ma_phong AS ma_phong_phu_hop, ph_phuhop.ten_phong AS ten_phong_phu_hop
            FROM tai_san ts
            JOIN loai_tai_san lts ON ts.loai_tai_san_id = lts.id
            JOIN phong_truc pt ON ts.phong_truc_id = pt.id
            LEFT JOIN phong_hoc ph_hientai ON ts.phong_hoc_hien_tai_id = ph_hientai.id
            LEFT JOIN phong_hoc ph_phuhop ON ts.phong_hoc_phu_hop_id = ph_phuhop.id
            WHERE ts.ma_qr = ?
            LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $maQr);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

/**
 * Sinh mã QR chuẩn duy nhất cho tài sản: QR-[PHONG_TRUC]-[MA_TAI_SAN]
 * Ví dụ: QR-D6-MC0001, QR-B2-RM0002
 *
 * @param string $maTaiSan Mã tài sản
 * @param string|null $maPhongTruc Mã phòng trực quản lý
 * @return string Mã QR
 */
function tai_san_sinh_ma_qr(string $maTaiSan, ?string $maPhongTruc = null): string {
    $maTsRong = preg_replace('/[^A-Z0-9]/i', '', $maTaiSan);
    if (!empty($maPhongTruc)) {
        $maPtRong = preg_replace('/[^A-Z0-9]/i', '', $maPhongTruc);
        return strtoupper("QR-{$maPtRong}-{$maTsRong}");
    }
    return strtoupper("QR-{$maTsRong}");
}

/**
 * Kiểm tra tính tương thích giữa tài sản đang quét và phòng học đích.
 *
 * Quy tắc:
 *  1. LINH_HOAT: Luôn tương thích với mọi phòng học.
 *  2. THEO_PHONG: Mã phòng học đích phải nằm trong mảng JSON phong_tuong_thich.
 *  3. THEO_THIET_BI: Duyệt danh sách thiết bị cố định (thiet_bi_phong) của phòng học đích:
 *     - Phải có ít nhất 1 thiết bị cố định cùng HÃNG SẢN XUẤT (không phân biệt hoa/thường).
 *     - Nếu tài sản có khai báo model cụ thể, thiết bị phòng cũng phải khớp model đó.
 *
 * Lưu ý: Nhãn phòng gợi ý (phong_hoc_phu_hop_id) KHÔNG dùng để khóa cứng phòng,
 * mà chỉ hỗ trợ nhân viên nhận diện và tìm thiết bị nhanh trên giá kệ tủ!
 *
 * @param array $taiSan Mảng thông tin tài sản (gồm pham_vi_tuong_thich, hang_san_xuat, model, phong_tuong_thich...)
 * @param array $phongHoc Mảng thông tin phòng học (gồm ma_phong, thiet_bi_phong...)
 * @return array ['tuong_thich' => bool, 'ly_do' => string]
 */
function tai_san_kiem_tra_tuong_thich(array $taiSan, array $phongHoc): array {
    $phamVi = $taiSan['pham_vi_tuong_thich'] ?? TUONG_THICH_LINH_HOAT;

    // 1. Phạm vi linh hoạt: Luôn tương thích
    if ($phamVi === TUONG_THICH_LINH_HOAT) {
        return [
            'tuong_thich' => true,
            'ly_do'       => 'Thiết bị dùng linh hoạt cho mọi phòng học.',
        ];
    }

    // 2. Phạm vi theo phòng chỉ định
    if ($phamVi === TUONG_THICH_THEO_PHONG) {
        $dsPhong = [];
        if (!empty($taiSan['phong_tuong_thich'])) {
            $dsPhong = is_string($taiSan['phong_tuong_thich'])
                ? (json_decode($taiSan['phong_tuong_thich'], true) ?: [])
                : $taiSan['phong_tuong_thich'];
        }

        $maPhongDich = (string)($phongHoc['ma_phong'] ?? '');
        if (in_array($maPhongDich, $dsPhong, true)) {
            return [
                'tuong_thich' => true,
                'ly_do'       => "Phòng $maPhongDich nằm trong danh sách phòng chỉ định của thiết bị.",
            ];
        }

        return [
            'tuong_thich' => false,
            'ly_do'       => "Thiết bị này chỉ dùng cố định cho danh sách phòng: " . implode(', ', $dsPhong),
        ];
    }

    // 3. Phạm vi theo Hãng & Model thiết bị phòng học
    if ($phamVi === TUONG_THICH_THEO_THIET_BI) {
        $hangTs = trim((string)($taiSan['hang_san_xuat'] ?? ''));
        $modelTs = trim((string)($taiSan['model'] ?? ''));

        if ($hangTs === '') {
            // Không có hãng cụ thể thì cho phép linh hoạt
            return [
                'tuong_thich' => true,
                'ly_do'       => 'Tài sản không giới hạn hãng sản xuất cụ thể.',
            ];
        }

        // Đọc danh sách thiết bị cố định của phòng học
        $dsTbPhong = [];
        if (!empty($phongHoc['thiet_bi_phong'])) {
            $dsTbPhong = is_string($phongHoc['thiet_bi_phong'])
                ? (json_decode($phongHoc['thiet_bi_phong'], true) ?: [])
                : $phongHoc['thiet_bi_phong'];
        }

        if (empty($dsTbPhong) || !is_array($dsTbPhong)) {
            return [
                'tuong_thich' => false,
                'ly_do'       => "Phòng học " . ($phongHoc['ma_phong'] ?? '') . " chưa được cấu hình thiết bị cố định nào.",
            ];
        }

        foreach ($dsTbPhong as $tb) {
            $hangTb = trim((string)($tb['hang'] ?? ''));
            $modelTb = trim((string)($tb['model'] ?? ''));

            // So khớp Hãng sản xuất (không phân biệt hoa thường)
            if (strcasecmp($hangTb, $hangTs) === 0) {
                // Nếu tài sản có khai báo Model cụ thể, kiểm tra khớp Model
                if ($modelTs !== '' && $modelTb !== '') {
                    if (strcasecmp($modelTb, $modelTs) === 0) {
                        return [
                            'tuong_thich' => true,
                            'ly_do'       => "Khớp hoàn hảo với {$tb['ten']} ({$hangTb} {$modelTb}) của phòng học.",
                        ];
                    }
                } else {
                    // Cùng hãng sản xuất (remote đa năng cùng hãng hoặc micro cùng hệ)
                    return [
                        'tuong_thich' => true,
                        'ly_do'       => "Tương thích cùng hãng {$hangTb} với {$tb['ten']} của phòng học.",
                    ];
                }
            }
        }

        return [
            'tuong_thich' => false,
            'ly_do'       => "Thiết bị này (Hãng: $hangTs" . ($modelTs !== '' ? ", Model: $modelTs" : "") . ") không tương thích với bất kỳ thiết bị cố định nào tại phòng " . ($phongHoc['ma_phong'] ?? '') . ".",
        ];
    }

    return [
        'tuong_thich' => true,
        'ly_do'       => 'Mặc định cho phép.',
    ];
}

/**
 * Thêm mới hoặc cập nhật thông tin tài sản / thiết bị.
 *
 * @param array $duLieu
 * @param int|null $id
 * @return array ['thanh_cong' => bool, 'loi' => string, 'id' => int]
 */
function tai_san_luu(array $duLieu, ?int $id = null): array {
    $db = db();

    $maTaiSan = strtoupper(trim((string)($duLieu['ma_tai_san'] ?? '')));
    $tenTaiSan = trim((string)($duLieu['ten_tai_san'] ?? ''));
    $loaiId = (int)($duLieu['loai_tai_san_id'] ?? 0);
    $phongTrucId = (int)($duLieu['phong_truc_id'] ?? 0);
    $hang = trim((string)($duLieu['hang_san_xuat'] ?? ''));
    $model = trim((string)($duLieu['model'] ?? ''));
    $phongPhuHopId = !empty($duLieu['phong_hoc_phu_hop_id']) ? (int)$duLieu['phong_hoc_phu_hop_id'] : null;
    $moTa = trim((string)($duLieu['mo_ta'] ?? ''));
    $ngayDuaVao = !empty($duLieu['ngay_dua_vao_su_dung']) ? (string)$duLieu['ngay_dua_vao_su_dung'] : null;

    $phamVi = (string)($duLieu['pham_vi_tuong_thich'] ?? TUONG_THICH_LINH_HOAT);
    if (!in_array($phamVi, [TUONG_THICH_THEO_THIET_BI, TUONG_THICH_THEO_PHONG, TUONG_THICH_LINH_HOAT], true)) {
        $phamVi = TUONG_THICH_LINH_HOAT;
    }

    // Xử lý danh sách phòng tương thích nếu chọn THEO_PHONG
    $phongTuongThich = null;
    if ($phamVi === TUONG_THICH_THEO_PHONG) {
        if (!empty($duLieu['phong_tuong_thich'])) {
            if (is_array($duLieu['phong_tuong_thich'])) {
                $cacPhong = array_values(array_filter(array_map('trim', $duLieu['phong_tuong_thich'])));
                $phongTuongThich = !empty($cacPhong) ? json_encode($cacPhong, JSON_UNESCAPED_UNICODE) : null;
            } elseif (is_string($duLieu['phong_tuong_thich'])) {
                $cacPhong = array_values(array_filter(array_map('trim', explode(',', $duLieu['phong_tuong_thich']))));
                $phongTuongThich = !empty($cacPhong) ? json_encode($cacPhong, JSON_UNESCAPED_UNICODE) : null;
            }
        }
    }

    // Kiểm tra dữ liệu bắt buộc
    if ($maTaiSan === '') {
        return ['thanh_cong' => false, 'loi' => 'Mã tài sản không được để trống.', 'id' => 0];
    }
    if ($tenTaiSan === '') {
        return ['thanh_cong' => false, 'loi' => 'Tên tài sản không được để trống.', 'id' => 0];
    }
    if ($loaiId <= 0) {
        return ['thanh_cong' => false, 'loi' => 'Vui lòng chọn loại tài sản.', 'id' => 0];
    }
    if ($phongTrucId <= 0) {
        return ['thanh_cong' => false, 'loi' => 'Vui lòng chọn phòng trực quản lý.', 'id' => 0];
    }

    // Kiểm tra trùng mã tài sản
    if ($id === null) {
        $checkMa = $db->prepare("SELECT id FROM tai_san WHERE ma_tai_san = ? LIMIT 1");
        $checkMa->bind_param('s', $maTaiSan);
    } else {
        $checkMa = $db->prepare("SELECT id FROM tai_san WHERE ma_tai_san = ? AND id <> ? LIMIT 1");
        $checkMa->bind_param('si', $maTaiSan, $id);
    }
    $checkMa->execute();
    if ($checkMa->get_result()->num_rows > 0) {
        $checkMa->close();
        return ['thanh_cong' => false, 'loi' => "Mã tài sản '$maTaiSan' đã tồn tại trong hệ thống.", 'id' => 0];
    }
    $checkMa->close();

    // Lấy mã phòng trực để sinh mã QR
    $stmtPt = $db->prepare("SELECT ma_phong_truc FROM phong_truc WHERE id = ? LIMIT 1");
    $stmtPt->bind_param('i', $phongTrucId);
    $stmtPt->execute();
    $ptRow = $stmtPt->get_result()->fetch_assoc();
    $stmtPt->close();
    $maPt = $ptRow['ma_phong_truc'] ?? '';

    // Xử lý mã QR
    $maQr = trim((string)($duLieu['ma_qr'] ?? ''));
    if ($maQr === '') {
        $maQr = tai_san_sinh_ma_qr($maTaiSan, $maPt);
    }

    // Kiểm tra trùng mã QR
    if ($id === null) {
        $checkQr = $db->prepare("SELECT id FROM tai_san WHERE ma_qr = ? LIMIT 1");
        $checkQr->bind_param('s', $maQr);
    } else {
        $checkQr = $db->prepare("SELECT id FROM tai_san WHERE ma_qr = ? AND id <> ? LIMIT 1");
        $checkQr->bind_param('si', $maQr, $id);
    }
    $checkQr->execute();
    if ($checkQr->get_result()->num_rows > 0) {
        $checkQr->close();
        return ['thanh_cong' => false, 'loi' => "Mã QR '$maQr' đã được sử dụng cho tài sản khác.", 'id' => 0];
    }
    $checkQr->close();

    if ($id === null) {
        // Thêm mới tài sản: mặc định tình trạng TOT, trạng thái SAN_SANG, vị trí phòng học là NULL (ở tại phòng trực)
        $tinhTrang = (string)($duLieu['tinh_trang'] ?? 'TOT');
        if (!in_array($tinhTrang, ['TOT', 'HU_HONG', 'MAT'], true)) {
            $tinhTrang = 'TOT';
        }
        $trangThai = (string)($duLieu['trang_thai'] ?? 'SAN_SANG');
        if (!in_array($trangThai, ['SAN_SANG', 'DANG_MUON', 'BAO_TRI', 'NGUNG_SU_DUNG'], true)) {
            $trangThai = 'SAN_SANG';
        }
        if ($trangThai === TRANG_THAI_SAN_SANG && $tinhTrang !== TINH_TRANG_TOT) {
            $trangThai = TRANG_THAI_BAO_TRI;
        }
        $phongHocHienTaiId = null;

        $sql = "INSERT INTO tai_san (
                    ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id,
                    hang_san_xuat, model, phong_hoc_phu_hop_id, phong_hoc_hien_tai_id,
                    tinh_trang, trang_thai, ngay_dua_vao_su_dung, mo_ta,
                    pham_vi_tuong_thich, phong_tuong_thich
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'sssiissiissssss',
            $maTaiSan, $maQr, $tenTaiSan, $loaiId, $phongTrucId,
            $hang, $model, $phongPhuHopId, $phongHocHienTaiId,
            $tinhTrang, $trangThai, $ngayDuaVao, $moTa,
            $phamVi, $phongTuongThich
        );
        $stmt->execute();
        $moiId = (int)$db->insert_id;
        $stmt->close();

        return ['thanh_cong' => true, 'loi' => '', 'id' => $moiId];
    } else {
        // Cập nhật thông tin tài sản (không ghi đè vị trí hiện tại hoặc trạng thái mượn đang diễn ra)
        $taiSanHienTai = tai_san_tim_theo_id($id);
        if ($taiSanHienTai === null) {
            return ['thanh_cong' => false, 'loi' => 'Không tìm thấy tài sản.', 'id' => 0];
        }

        // Tình trạng vật lý có thể cập nhật qua form sửa nếu không đang mượn
        $tinhTrang = (string)($duLieu['tinh_trang'] ?? $taiSanHienTai['tinh_trang']);
        if (!in_array($tinhTrang, ['TOT', 'HU_HONG', 'MAT'], true)) {
            $tinhTrang = $taiSanHienTai['tinh_trang'];
        }

        $trangThai = (string)($duLieu['trang_thai'] ?? $taiSanHienTai['trang_thai']);
        if (!in_array($trangThai, ['SAN_SANG', 'DANG_MUON', 'BAO_TRI', 'NGUNG_SU_DUNG'], true)) {
            $trangThai = $taiSanHienTai['trang_thai'];
        }

        // Ràng buộc toàn vẹn: Đang sẵn sàng thì tình trạng PHẢI LÀ TOT
        if ($trangThai === TRANG_THAI_SAN_SANG && $tinhTrang !== TINH_TRANG_TOT) {
            $trangThai = TRANG_THAI_BAO_TRI;
        }

        $sql = "UPDATE tai_san SET 
                    ma_tai_san = ?, ma_qr = ?, ten_tai_san = ?, loai_tai_san_id = ?, phong_truc_id = ?,
                    hang_san_xuat = ?, model = ?, phong_hoc_phu_hop_id = ?, tinh_trang = ?, trang_thai = ?,
                    ngay_dua_vao_su_dung = ?, mo_ta = ?, pham_vi_tuong_thich = ?, phong_tuong_thich = ?
                WHERE id = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param(
            'sssiississssssi',
            $maTaiSan, $maQr, $tenTaiSan, $loaiId, $phongTrucId,
            $hang, $model, $phongPhuHopId, $tinhTrang, $trangThai,
            $ngayDuaVao, $moTa, $phamVi, $phongTuongThich,
            $id
        );
        $stmt->execute();
        $stmt->close();

        return ['thanh_cong' => true, 'loi' => '', 'id' => $id];
    }
}

/**
 * Xóa hoặc chuyển trạng thái tài sản sang NGỪNG SỬ DỤNG.
 * - Nếu tài sản đã từng có giao dịch trong chi_tiet_muon, chi_tiet_dieu_chuyen, chi_tiet_kiem_ke hoặc su_co_tai_san:
 *   Không xóa vật lý mà chuyển trang_thai = 'NGUNG_SU_DUNG'.
 * - Nếu chưa từng phát sinh dữ liệu liên kết: cho phép xóa cứng khỏi CSDL.
 *
 * @param int $id
 * @return array ['thanh_cong' => bool, 'loi' => string, 'da_xoa_cung' => bool]
 */
function tai_san_xoa_hoac_ngung_su_dung(int $id): array {
    $db = db();

    $taiSan = tai_san_tim_theo_id($id);
    if ($taiSan === null) {
        return ['thanh_cong' => false, 'loi' => 'Không tìm thấy tài sản.', 'da_xoa_cung' => false];
    }

    if ($taiSan['trang_thai'] === TRANG_THAI_DANG_MUON) {
        return ['thanh_cong' => false, 'loi' => 'Thiết bị đang được mượn tại phòng học. Vui lòng nhận trả thiết bị trước khi xóa hoặc ngừng sử dụng.', 'da_xoa_cung' => false];
    }

    // 1. Kiểm tra lịch sử trong chi_tiet_muon
    $checkMuon = $db->prepare("SELECT COUNT(*) FROM chi_tiet_muon WHERE tai_san_id = ?");
    $checkMuon->bind_param('i', $id);
    $checkMuon->execute();
    $soMuon = (int)$checkMuon->get_result()->fetch_row()[0];
    $checkMuon->close();

    // 2. Kiểm tra điều chuyển
    $checkDc = $db->prepare("SELECT COUNT(*) FROM chi_tiet_dieu_chuyen WHERE tai_san_id = ?");
    $checkDc->bind_param('i', $id);
    $checkDc->execute();
    $soDc = (int)$checkDc->get_result()->fetch_row()[0];
    $checkDc->close();

    // 3. Kiểm tra kiểm kê
    $checkKk = $db->prepare("SELECT COUNT(*) FROM chi_tiet_kiem_ke WHERE tai_san_id = ?");
    $checkKk->bind_param('i', $id);
    $checkKk->execute();
    $soKk = (int)$checkKk->get_result()->fetch_row()[0];
    $checkKk->close();

    // 4. Kiểm tra sự cố
    $checkSc = $db->prepare("SELECT COUNT(*) FROM su_co_tai_san WHERE tai_san_id = ?");
    $checkSc->bind_param('i', $id);
    $checkSc->execute();
    $soSc = (int)$checkSc->get_result()->fetch_row()[0];
    $checkSc->close();

    $coLichSu = ($soMuon + $soDc + $soKk + $soSc) > 0;

    if ($coLichSu) {
        // Chuyển sang ngừng sử dụng
        $stmtUpdate = $db->prepare("UPDATE tai_san SET trang_thai = 'NGUNG_SU_DUNG' WHERE id = ?");
        $stmtUpdate->bind_param('i', $id);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        return [
            'thanh_cong'   => true,
            'loi'          => '',
            'da_xoa_cung'  => false,
            'thong_diep'   => 'Thiết bị đã phát sinh lịch sử truy vết giao dịch. Đã chuyển trạng thái sang NGỪNG SỬ DỤNG để bảo toàn dữ liệu.',
        ];
    } else {
        // Xóa cứng an toàn
        $stmtDel = $db->prepare("DELETE FROM tai_san WHERE id = ?");
        $stmtDel->bind_param('i', $id);
        $stmtDel->execute();
        $stmtDel->close();

        return [
            'thanh_cong'  => true,
            'loi'         => '',
            'da_xoa_cung' => true,
            'thong_diep'  => 'Đã xóa hoàn toàn thiết bị khỏi hệ thống.',
        ];
    }
}

/**
 * Thống kê nhanh số lượng tài sản theo trạng thái (Dùng cho Dashboard và Bảng điều khiển).
 *
 * @param int|null $phongTrucId Lọc theo phòng trực (nếu là nhân viên)
 * @return array
 */
function tai_san_thong_ke_nhanh(?int $phongTrucId = null): array {
    $db = db();
    $where = " WHERE 1=1";
    $params = [];
    $types = '';

    if ($phongTrucId !== null && $phongTrucId > 0) {
        $where .= " AND phong_truc_id = ?";
        $params[] = $phongTrucId;
        $types .= 'i';
    }

    $sql = "SELECT 
                COUNT(*) AS tong_so,
                SUM(CASE WHEN trang_thai = 'SAN_SANG' THEN 1 ELSE 0 END) AS san_sang,
                SUM(CASE WHEN trang_thai = 'DANG_MUON' THEN 1 ELSE 0 END) AS dang_muon,
                SUM(CASE WHEN trang_thai = 'BAO_TRI' OR tinh_trang = 'HU_HONG' THEN 1 ELSE 0 END) AS bao_tri_hu_hong,
                SUM(CASE WHEN trang_thai = 'NGUNG_SU_DUNG' THEN 1 ELSE 0 END) AS ngung_su_dung
            FROM tai_san $where";

    if (!empty($params)) {
        $stmt = $db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } else {
        $res = $db->query($sql)->fetch_assoc();
    }

    return [
        'tong_so'           => (int)($res['tong_so'] ?? 0),
        'san_sang'          => (int)($res['san_sang'] ?? 0),
        'dang_muon'         => (int)($res['dang_muon'] ?? 0),
        'bao_tri_hu_hong'   => (int)($res['bao_tri_hu_hong'] ?? 0),
        'ngung_su_dung'     => (int)($res['ngung_su_dung'] ?? 0),
    ];
}

