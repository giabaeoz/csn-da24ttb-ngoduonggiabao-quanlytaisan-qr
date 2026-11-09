<?php
/**
 * Bộ kiểm thử tự động BÀI THỬ 8: QUY TRÌNH ĐIỀU CHUYỂN THIẾT BỊ GIỮA CÁC PHÒNG HỌC (G7).
 * Kiểm tra các ràng buộc:
 * 1. Khác phòng nguồn và đích.
 * 2. Xác nhận kiểm tra tương thích.
 * 3. Bắt buộc lý do điều chuyển.
 * 4. Chặn trùng thiết bị, giới hạn 1-30 thiết bị.
 * 5. Chặn khi phòng trực đang diễn ra kiểm kê.
 * 6. Chặn thiết bị không thuộc phòng trực nhân viên.
 * 7. Chặn thiết bị không ở đúng phòng học nguồn.
 * 8. Chặn thiết bị không ở trạng thái Sẵn sàng / Tốt.
 * 9. Chặn thiết bị đang có phiếu mượn chưa trả.
 * 10. Chặn thiết bị không tương thích hãng/model/phòng với phòng đích.
 * 11. Tạo phiếu thành công trong transaction an toàn, cập nhật phòng học hiện tại, giữ nguyên phòng trực.
 * 12. Tra cứu chi tiết phiếu và quét mã QR phiếu điều chuyển.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/dieu-chuyen-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/muon-tra-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/tai-san-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/danh-muc-service.php';

$db = db();
$maTest = strtoupper(bin2hex(random_bytes(3)));

echo "============================================================\n";
echo "  BÀI THỬ 8: QUY TRÌNH ĐIỀU CHUYỂN THIẾT BỊ GIỮA CÁC PHÒNG HỌC (G7)\n";
echo "============================================================\n\n";

$soDat = 0;
$tongBai = 0;

function kt_assert(bool $dieuKien, string $thongBao): void {
    global $soDat, $tongBai;
    $tongBai++;
    if ($dieuKien) {
        $soDat++;
        echo "  [PASS] $thongBao\n";
    } else {
        echo "  [FAIL] $thongBao\n";
    }
}

// Biến lưu ID dữ liệu kiểm thử
$phongTruc1Id = 0;
$phongTruc2Id = 0;
$nhanVien1Id = 0;
$nhanVien2Id = 0;
$phongHoc1Id = 0;
$phongHoc2Id = 0;
$phongHoc3Id = 0;
$nguoiMuonId = 0;
$phieuMuonId = 0;
$dotKiemKeId = 0;
$dieuChuyenId = 0;
$tsIds = [];

try {
    // 1. Tạo 2 bàn trực thử nghiệm
    $db->execute_query("INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc, dang_hoat_dong) VALUES (?, ?, 1)",
        ['PT_DC1_' . $maTest, 'Bàn trực Điều chuyển 1 Test ' . $maTest]);
    $phongTruc1Id = (int)$db->insert_id;

    $db->execute_query("INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc, dang_hoat_dong) VALUES (?, ?, 1)",
        ['PT_DC2_' . $maTest, 'Bàn trực Điều chuyển 2 Test ' . $maTest]);
    $phongTruc2Id = (int)$db->insert_id;

    // 2. Tạo 2 nhân viên thuộc 2 bàn trực
    $hash = password_hash('matkhau123', PASSWORD_DEFAULT);
    $db->execute_query("INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id, dang_hoat_dong)
        VALUES (?, 'NV Điều Chuyển 1', ?, ?, 'NHAN_VIEN', ?, 1)",
        ['NV_DC1_' . $maTest, 'nvdc1_' . strtolower($maTest), $hash, $phongTruc1Id]);
    $nhanVien1Id = (int)$db->insert_id;

    $db->execute_query("INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id, dang_hoat_dong)
        VALUES (?, 'NV Điều Chuyển 2', ?, ?, 'NHAN_VIEN', ?, 1)",
        ['NV_DC2_' . $maTest, 'nvdc2_' . strtolower($maTest), $hash, $phongTruc2Id]);
    $nhanVien2Id = (int)$db->insert_id;

    // 3. Tạo 3 phòng học thử nghiệm (Phòng 1: nguồn, Phòng 2: đích có máy lạnh Daikin & âm thanh Sony, Phòng 3: phòng khác)
    $cauHinhPhong2 = json_encode([
        ['ten' => 'Máy lạnh', 'hang' => 'Daikin', 'model' => 'FTKQ25SAVMV'],
        ['ten' => 'Dàn âm thanh', 'hang' => 'Sony', 'model' => 'MDR-ZX110'],
    ], JSON_UNESCAPED_UNICODE);

    $db->execute_query("INSERT INTO phong_hoc (ma_phong, ten_phong, dang_hoat_dong) VALUES (?, 'Phòng Nguồn G7', 1)",
        ['PH_NGUON_' . $maTest]);
    $phongHoc1Id = (int)$db->insert_id;

    $db->execute_query("INSERT INTO phong_hoc (ma_phong, ten_phong, thiet_bi_phong, dang_hoat_dong) VALUES (?, 'Phòng Đích G7', ?, 1)",
        ['PH_DICH_' . $maTest, $cauHinhPhong2]);
    $phongHoc2Id = (int)$db->insert_id;

    $db->execute_query("INSERT INTO phong_hoc (ma_phong, ten_phong, dang_hoat_dong) VALUES (?, 'Phòng Khác G7', 1)",
        ['PH_KHAC_' . $maTest]);
    $phongHoc3Id = (int)$db->insert_id;

    $loaiTsRow = $db->query("SELECT id FROM loai_tai_san WHERE dang_hoat_dong = 1 LIMIT 1")->fetch_assoc();
    $loaiTsId = (int)($loaiTsRow['id'] ?? 1);

    // 4. Tạo các tài sản thử nghiệm với các kịch bản khác nhau
    // TS 1: Hợp lệ (Linh hoạt, ở phòng nguồn, bàn trực 1, Tốt, Sẵn sàng)
    $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, pham_vi_tuong_thich, tinh_trang, trang_thai)
        VALUES (?, ?, 'TS Linh Hoạt Test', ?, ?, ?, 'LINH_HOAT', 'TOT', 'SAN_SANG')",
        ['TS_LH_' . $maTest, 'QR_LH_' . $maTest, $loaiTsId, $phongTruc1Id, $phongHoc1Id]);
    $tsIds['LINH_HOAT'] = (int)$db->insert_id;

    // TS 2: Hợp lệ theo Hãng/Model tương thích với phòng đích (Daikin)
    $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, hang_san_xuat, model, pham_vi_tuong_thich, tinh_trang, trang_thai)
        VALUES (?, ?, 'Remote Daikin Test', ?, ?, ?, 'Daikin', 'FTKQ25SAVMV', 'THEO_THIET_BI', 'TOT', 'SAN_SANG')",
        ['TS_DK_' . $maTest, 'QR_DK_' . $maTest, $loaiTsId, $phongTruc1Id, $phongHoc1Id]);
    $tsIds['DAIKIN_OK'] = (int)$db->insert_id;

    // TS 3: Không tương thích (Hãng Panasonic, model không khớp cấu hình phòng đích)
    $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, hang_san_xuat, model, pham_vi_tuong_thich, tinh_trang, trang_thai)
        VALUES (?, ?, 'Remote Panasonic Test', ?, ?, ?, 'Panasonic', 'CS-N9WKH-8', 'THEO_THIET_BI', 'TOT', 'SAN_SANG')",
        ['TS_PANA_' . $maTest, 'QR_PANA_' . $maTest, $loaiTsId, $phongTruc1Id, $phongHoc1Id]);
    $tsIds['PANA_INCOMP'] = (int)$db->insert_id;

    // TS 4: Không ở phòng nguồn (Đang ở phòng 3)
    $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, pham_vi_tuong_thich, tinh_trang, trang_thai)
        VALUES (?, ?, 'TS Sai Phòng Nguồn Test', ?, ?, ?, 'LINH_HOAT', 'TOT', 'SAN_SANG')",
        ['TS_SAIPHONG_' . $maTest, 'QR_SAIPHONG_' . $maTest, $loaiTsId, $phongTruc1Id, $phongHoc3Id]);
    $tsIds['SAI_PHONG'] = (int)$db->insert_id;

    // TS 5: Thuộc bàn trực khác (Bàn trực 2)
    $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, pham_vi_tuong_thich, tinh_trang, trang_thai)
        VALUES (?, ?, 'TS Bàn Trực Khác Test', ?, ?, ?, 'LINH_HOAT', 'TOT', 'SAN_SANG')",
        ['TS_KHACBAN_' . $maTest, 'QR_KHACBAN_' . $maTest, $loaiTsId, $phongTruc2Id, $phongHoc1Id]);
    $tsIds['KHAC_BAN'] = (int)$db->insert_id;

    // TS 6: Tình trạng Hư hỏng / Trạng thái Bảo trì
    $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, pham_vi_tuong_thich, tinh_trang, trang_thai)
        VALUES (?, ?, 'TS Hư Hỏng Test', ?, ?, ?, 'LINH_HOAT', 'HU_HONG', 'BAO_TRI')",
        ['TS_HONG_' . $maTest, 'QR_HONG_' . $maTest, $loaiTsId, $phongTruc1Id, $phongHoc1Id]);
    $tsIds['HU_HONG'] = (int)$db->insert_id;

    // TS 7: Đang mượn chưa trả
    $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, pham_vi_tuong_thich, tinh_trang, trang_thai)
        VALUES (?, ?, 'TS Đang Mượn Test', ?, ?, ?, 'LINH_HOAT', 'TOT', 'DANG_MUON')",
        ['TS_DANGMUON_' . $maTest, 'QR_DANGMUON_' . $maTest, $loaiTsId, $phongTruc1Id, $phongHoc1Id]);
    $tsIds['DANG_MUON'] = (int)$db->insert_id;

    // Tạo người mượn và phiếu mượn cho TS 7
    $db->execute_query("INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon)
        VALUES (?, ?, 'Người Mượn Test G7', 'SINH_VIEN')",
        ['NM_G7_' . $maTest, 'MS_G7_' . $maTest]);
    $nguoiMuonId = (int)$db->insert_id;

    $db->execute_query("INSERT INTO phieu_muon (ma_phieu_muon, nguoi_muon_id, phong_hoc_id, phong_truc_id, nhan_vien_lap_id, han_tra, trang_thai)
        VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 1 DAY), 'DANG_MUON')",
        ['PM_DC_' . $maTest, $nguoiMuonId, $phongHoc1Id, $phongTruc1Id, $nhanVien1Id]);
    $phieuMuonId = (int)$db->insert_id;

    $db->execute_query("INSERT INTO chi_tiet_muon (phieu_muon_id, tai_san_id, tinh_trang_khi_muon) VALUES (?, ?, 'TOT')",
        [$phieuMuonId, $tsIds['DANG_MUON']]);

    kt_assert(true, "Khởi tạo thành công môi trường và 7 thiết bị kiểm thử điều chuyển");

    // ============================================================
    // BẮT ĐẦU KIỂM THỬ CÁC QUY TẮC RÀNG BUỘC NGHIỆP VỤ
    // ============================================================

    // TEST 1: Chặn khi phòng đi trùng phòng đến
    $kq1 = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc1Id,
        'ly_do'                => 'Thử nghiệm phòng trùng nhau',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [$tsIds['LINH_HOAT']],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq1['thanh_cong'] && str_contains($kq1['loi'], 'khác nhau'),
        "Chặn thành công khi phòng đi và phòng đến trùng nhau ({$kq1['loi']})");

    // TEST 2: Chặn khi chưa tích xác nhận tương thích
    $kq2 = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => 'Thử nghiệm chưa xác nhận tương thích',
        'xac_nhan_tuong_thich' => '0',
        'tai_san_ids'          => [$tsIds['LINH_HOAT']],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq2['thanh_cong'] && str_contains($kq2['loi'], 'xác nhận'),
        "Bắt buộc tích cam kết kiểm tra tính tương thích trước khi chuyển");

    // TEST 3: Chặn khi lý do điều chuyển rỗng
    $kq3 = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => '   ',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [$tsIds['LINH_HOAT']],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq3['thanh_cong'] && str_contains($kq3['loi'], 'lý do'),
        "Bắt buộc nhập lý do điều chuyển thiết bị");

    // TEST 4: Chặn khi danh sách tài sản rỗng hoặc trùng lặp
    $kq4a = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => 'Thử nghiệm rỗng tài sản',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq4a['thanh_cong'], "Chặn khi danh sách thiết bị rỗng");

    $kq4b = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => 'Thử nghiệm trùng thiết bị',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [$tsIds['LINH_HOAT'], $tsIds['LINH_HOAT']],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq4b['thanh_cong'] && str_contains($kq4b['loi'], 'trùng lặp'),
        "Chặn khi danh sách thiết bị bị chọn trùng nhau");

    // TEST 5: Chặn tài sản không thuộc phòng trực nhân viên
    $kq5 = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => 'Chuyển tài sản bàn trực khác',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [$tsIds['KHAC_BAN']],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq5['thanh_cong'] && str_contains($kq5['loi'], 'thẩm quyền'),
        "Chặn điều chuyển thiết bị thuộc quyền quản lý của bàn trực khác ({$kq5['loi']})");

    // TEST 6: Chặn tài sản không ở đúng phòng học nguồn đã chọn
    $kq6 = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => 'Chuyển tài sản đang ở phòng khác',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [$tsIds['SAI_PHONG']],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq6['thanh_cong'] && str_contains($kq6['loi'], 'không ở phòng học nguồn'),
        "Chặn thiết bị không ở phòng học nguồn đã chọn");

    // TEST 7: Chặn tài sản không ở trạng thái Sẵn sàng / Hư hỏng
    $kq7 = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => 'Chuyển tài sản hư hỏng',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [$tsIds['HU_HONG']],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq7['thanh_cong'] && (str_contains($kq7['loi'], 'sẵn sàng') || str_contains($kq7['loi'], 'tình trạng tốt')),
        "Chặn thiết bị không ở trạng thái Tốt hoặc đang bảo trì");

    // TEST 8: Chặn tài sản đang có phiếu mượn chưa trả
    $kq8 = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => 'Chuyển tài sản đang mượn',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [$tsIds['DANG_MUON']],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq8['thanh_cong'] && (str_contains($kq8['loi'], 'mượn') || str_contains($kq8['loi'], 'sẵn sàng')),
        "Chặn điều chuyển thiết bị đang được mượn chưa hoàn trả");

    // TEST 9: Chặn tài sản không tương thích cấu hình phòng đích (Panasonic vs Daikin)
    $kq9 = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => 'Chuyển thiết bị không tương thích',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [$tsIds['PANA_INCOMP']],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq9['thanh_cong'] && str_contains($kq9['loi'], 'không tương thích'),
        "Chặn thiết bị không tương thích hãng/model phòng đích ({$kq9['loi']})");

    // TEST 10: Chặn khi bàn trực đang diễn ra kiểm kê
    $db->execute_query("INSERT INTO dot_kiem_ke (ma_dot_kiem_ke, ten_dot_kiem_ke, phong_truc_id, nhan_vien_lap_id, loai_kiem_ke, trang_thai)
        VALUES (?, 'Đợt kiểm kê khóa điều chuyển', ?, ?, 'DINH_KY', 'DANG_KIEM_KE')",
        ['KK_LOCK_' . $maTest, $phongTruc1Id, $nhanVien1Id]);
    $dotKiemKeId = (int)$db->insert_id;

    $kq10 = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => 'Chuyển khi kiểm kê',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [$tsIds['LINH_HOAT']],
    ], $nhanVien1Id, $phongTruc1Id);
    kt_assert(!$kq10['thanh_cong'] && str_contains($kq10['loi'], 'kiểm kê'),
        "Khóa nghiệp vụ điều chuyển khi bàn trực đang có đợt kiểm kê ({$kq10['loi']})");

    // Mở khóa bằng cách chốt đợt kiểm kê
    $db->execute_query("UPDATE dot_kiem_ke SET trang_thai = 'HOAN_TAT', thoi_gian_ket_thuc = NOW() WHERE id = ?", [$dotKiemKeId]);

    // TEST 11: Lập phiếu điều chuyển HỢP LỆ với 2 thiết bị (Linh hoạt + Daikin tương thích)
    $kq11 = dieu_chuyen_tao([
        'phong_hoc_di_id'      => $phongHoc1Id,
        'phong_hoc_den_id'     => $phongHoc2Id,
        'ly_do'                => 'Bổ sung thiết bị cho phòng máy học kỳ mới',
        'ghi_chu'              => 'Ghi chú bàn giao điều chuyển',
        'xac_nhan_tuong_thich' => '1',
        'tai_san_ids'          => [$tsIds['LINH_HOAT'], $tsIds['DAIKIN_OK']],
    ], $nhanVien1Id, $phongTruc1Id);
    if (!$kq11['thanh_cong']) {
        echo "[ERROR TEST 11] " . $kq11['loi'] . "\n";
    }
    kt_assert($kq11['thanh_cong'] && $kq11['id'] > 0 && str_starts_with($kq11['ma_dieu_chuyen'], 'DC-'),
        "Lập phiếu điều chuyển hợp lệ thành công (Mã phiếu: {$kq11['ma_dieu_chuyen']})");
    $dieuChuyenId = (int)$kq11['id'];

    // TEST 12: Kiểm tra dữ liệu được ghi trong CSDL
    $demCt = (int)$db->execute_query("SELECT COUNT(*) FROM chi_tiet_dieu_chuyen WHERE dieu_chuyen_id = ?", [$dieuChuyenId])->fetch_row()[0];
    kt_assert($demCt === 2, "Bảng chi_tiet_dieu_chuyen ghi nhận đúng 2 thiết bị");

    // TEST 13: Vị trí hiện tại của thiết bị được cập nhật sang phòng đích
    $demViTriMoi = (int)$db->execute_query("SELECT COUNT(*) FROM tai_san WHERE id IN (?, ?) AND phong_hoc_hien_tai_id = ?",
        [$tsIds['LINH_HOAT'], $tsIds['DAIKIN_OK'], $phongHoc2Id])->fetch_row()[0];
    kt_assert($demViTriMoi === 2, "Vị trí hiện tại (phong_hoc_hien_tai_id) của 2 thiết bị đã chuyển sang Phòng đích");

    // TEST 14: Phòng trực sở hữu (phong_truc_id) vẫn được bảo toàn nguyên vẹn
    $demPhongTruc = (int)$db->execute_query("SELECT COUNT(*) FROM tai_san WHERE id IN (?, ?) AND phong_truc_id = ?",
        [$tsIds['LINH_HOAT'], $tsIds['DAIKIN_OK'], $phongTruc1Id])->fetch_row()[0];
    kt_assert($demPhongTruc === 2, "Phòng trực quản lý (phong_truc_id) giữ nguyên không bị thay đổi");

    // TEST 15: Tra cứu chi tiết phiếu điều chuyển theo ID
    $phieuCt = dieu_chuyen_tim_theo_id($dieuChuyenId);
    kt_assert($phieuCt !== null && count($phieuCt['chi_tiet']) === 2 && $phieuCt['ma_phong_den'] === 'PH_DICH_' . $maTest,
        "Truy vấn chi tiết phiếu điều chuyển kèm danh sách thiết bị con thành công");

    // TEST 16: Tra cứu phiếu điều chuyển bằng mã QR phiếu
    $phieuQr = dieu_chuyen_tim_theo_ma($kq11['ma_dieu_chuyen']);
    kt_assert($phieuQr !== null && (int)$phieuQr['id'] === $dieuChuyenId,
        "Tra cứu phiếu điều chuyển bằng quét mã QR phiếu ({$kq11['ma_dieu_chuyen']}) thành công");

    // TEST 17: Danh sách lịch sử điều chuyển có bộ lọc
    $dsLoc = dieu_chuyen_danh_sach(['phong_hoc_di_id' => $phongHoc1Id]);
    kt_assert($dsLoc['tong_so'] >= 1, "Phiếu điều chuyển xuất hiện chính xác trong danh sách lịch sử lọc");

} finally {
    // Dọn dẹp dữ liệu kiểm thử
    if ($dieuChuyenId > 0) {
        $db->execute_query("DELETE FROM chi_tiet_dieu_chuyen WHERE dieu_chuyen_id = ?", [$dieuChuyenId]);
        $db->execute_query("DELETE FROM dieu_chuyen WHERE id = ?", [$dieuChuyenId]);
    }
    if ($dotKiemKeId > 0) {
        $db->execute_query("DELETE FROM dot_kiem_ke WHERE id = ?", [$dotKiemKeId]);
    }
    if ($phieuMuonId > 0) {
        $db->execute_query("DELETE FROM chi_tiet_muon WHERE phieu_muon_id = ?", [$phieuMuonId]);
        $db->execute_query("DELETE FROM phieu_muon WHERE id = ?", [$phieuMuonId]);
    }
    if (!empty($tsIds)) {
        foreach ($tsIds as $id) {
            $db->execute_query("DELETE FROM tai_san WHERE id = ?", [$id]);
        }
    }
    if ($nguoiMuonId > 0) {
        $db->execute_query("DELETE FROM nguoi_muon WHERE id = ?", [$nguoiMuonId]);
    }
    if ($nhanVien1Id > 0) {
        $db->execute_query("DELETE FROM nhan_vien WHERE id = ?", [$nhanVien1Id]);
    }
    if ($nhanVien2Id > 0) {
        $db->execute_query("DELETE FROM nhan_vien WHERE id = ?", [$nhanVien2Id]);
    }
    if ($phongHoc1Id > 0) {
        $db->execute_query("DELETE FROM phong_hoc WHERE id = ?", [$phongHoc1Id]);
    }
    if ($phongHoc2Id > 0) {
        $db->execute_query("DELETE FROM phong_hoc WHERE id = ?", [$phongHoc2Id]);
    }
    if ($phongHoc3Id > 0) {
        $db->execute_query("DELETE FROM phong_hoc WHERE id = ?", [$phongHoc3Id]);
    }
    if ($phongTruc1Id > 0) {
        $db->execute_query("DELETE FROM phong_truc WHERE id = ?", [$phongTruc1Id]);
    }
    if ($phongTruc2Id > 0) {
        $db->execute_query("DELETE FROM phong_truc WHERE id = ?", [$phongTruc2Id]);
    }
}

echo "\n------------------------------------------------------------\n";
echo "  KẾT QUẢ [ĐIỀU CHUYỂN THIẾT BỊ GIỮA CÁC PHÒNG HỌC (G7)]: $soDat / $tongBai bài đạt.\n";
echo "============================================================\n";

if ($soDat !== $tongBai) {
    exit(1);
}
