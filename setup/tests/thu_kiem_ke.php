<?php
/**
 * Bộ kiểm thử tự động BÀI THỬ 9: QUY TRÌNH KIỂM KÊ TÀI SẢN THEO PHÒNG TRỰC (G8).
 * 
 * Ràng buộc & Nghiệp vụ kiểm tra:
 * 1. Chụp sổ sách toàn bộ tài sản đang hoạt động của bàn trực vào chi_tiet_kiem_ke.
 * 2. Tài sản đang có phiếu mượn chưa trả được tự động ghi nhận là DANG_MUON.
 * 3. Khóa bàn trực khỏi lập phiếu mượn mới và điều chuyển khi đợt kiểm kê đang DANG_KIEM_KE.
 * 4. Chặn mở đợt kiểm kê thứ 2 trên cùng bàn trực khi đang có đợt kiểm kê chưa chốt.
 * 5. Chặn ghi nhận đối chiếu thực tế cho tài sản đang mượn.
 * 6. Đối chiếu có mặt: Tốt, đúng vị trí -> cập nhật thành công.
 * 7. Đối chiếu có mặt: Hư hỏng -> bắt buộc ghi chú lý do/mô tả hư hỏng.
 * 8. Đối chiếu có mặt: Sai vị trí phòng học -> cập nhật thành công.
 * 9. Đối chiếu chưa tìm thấy (Mất) -> bắt buộc ghi chú lý do.
 * 10. Chặn hoàn tất chốt sổ khi vẫn còn thiết bị CHUA_KIEM_TRA.
 * 11. Hoàn tất chốt sổ thành công:
 *     - Tự động sinh sự cố su_co_tai_san cho Mất (MAT), Hư hỏng (HU_HONG), Sai vị trí (SAI_VI_TRI).
 *     - Cập nhật trạng thái thiết bị tương ứng (NGUNG_SU_DUNG cho mất/sai vị trí, BAO_TRI cho hư hỏng).
 *     - Đợt kiểm kê sang HOAN_TAT, thoi_gian_ket_thuc được gán NOW().
 *     - Mở khóa bàn trực bình thường.
 * 12. Hủy đợt kiểm kê (DA_HUY) yêu cầu lý do và mở khóa bàn trực.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/kiem-ke-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/muon-tra-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/dieu-chuyen-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/tai-san-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/danh-muc-service.php';

$db = db();
$maTest = strtoupper(bin2hex(random_bytes(3)));

echo "============================================================\n";
echo "  BÀI THỬ 9: QUY TRÌNH KIỂM KÊ TÀI SẢN THEO PHÒNG TRỰC (G8)\n";
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
$phongTrucId = 0;
$nhanVienId = 0;
$phongHoc1Id = 0;
$phongHoc2Id = 0;
$loaiTsId = 0;
$nguoiMuonId = 0;
$phieuMuonId = 0;
$dotKiemKe1Id = 0;
$dotKiemKe2Id = 0;
$tsIds = [];

try {
    // -------------------------------------------------------------
    // BƯỚC 0: THIẾT LẬP DỮ LIỆU KIỂM THỬ BAN ĐẦU
    // -------------------------------------------------------------
    // 1. Tạo bàn trực
    $maPt = 'PT-KK-' . $maTest;
    $db->execute_query(
        "INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc, vi_tri, dang_hoat_dong) VALUES (?, ?, 'Tầng 1 Nhà Test', 1)",
        [$maPt, 'Bàn trực Test KK ' . $maTest]
    );
    $phongTrucId = (int)$db->insert_id;

    // 2. Tạo nhân viên trực
    $maNv = 'NV-KK-' . $maTest;
    $db->execute_query(
        "INSERT INTO nhan_vien (phong_truc_id, ma_nhan_vien, ten_dang_nhap, mat_khau_hash, ho_ten, so_dien_thoai, vai_tro, dang_hoat_dong) 
         VALUES (?, ?, ?, 'hash', 'Cán bộ test KK', '0901234567', 'NHAN_VIEN', 1)",
        [$phongTrucId, $maNv, 'nvkk_' . strtolower($maTest)]
    );
    $nhanVienId = (int)$db->insert_id;

    // 3. Tạo 2 phòng học
    $maPh1 = 'PH1-' . $maTest;
    $db->execute_query(
        "INSERT INTO phong_hoc (ma_phong, ten_phong, toa_nha, tang, dang_hoat_dong) 
         VALUES (?, 'Phòng A1 Test', 'Tòa A', '1', 1)",
        [$maPh1]
    );
    $phongHoc1Id = (int)$db->insert_id;

    $maPh2 = 'PH2-' . $maTest;
    $db->execute_query(
        "INSERT INTO phong_hoc (ma_phong, ten_phong, toa_nha, tang, dang_hoat_dong) 
         VALUES (?, 'Phòng A2 Test', 'Tòa A', '2', 1)",
        [$maPh2]
    );
    $phongHoc2Id = (int)$db->insert_id;

    // 4. Tạo loại tài sản
    $maLoai = 'LT-' . $maTest;
    $db->execute_query(
        "INSERT INTO loai_tai_san (ma_loai, ten_loai, mo_ta) VALUES (?, 'Loại tài sản Test KK', 'Mô tả test')",
        [$maLoai]
    );
    $loaiTsId = (int)$db->insert_id;

    // 5. Tạo 5 tài sản:
    // TS 0: Tốt, Sẵn sàng (Sau này đối chiếu: TỐT, ĐÚNG PHÒNG)
    // TS 1: Tốt, Sẵn sàng (Sau này đối chiếu: HƯ HỎNG MỚI)
    // TS 2: Tốt, Sẵn sàng (Sau này đối chiếu: TỐT, SAI PHÒNG HỌC)
    // TS 3: Tốt, Sẵn sàng (Sau này đối chiếu: CHƯA TÌM THẤY - MẤT)
    // TS 4: Sẽ được mượn -> ĐANG MƯỢN (Không cần kiểm tra)
    for ($i = 0; $i < 5; $i++) {
        $maTs = sprintf('TS%d-%s', $i + 1, $maTest);
        $maQr = sprintf('QR-%s-%02d', $maTest, $i + 1);
        $db->execute_query(
            "INSERT INTO tai_san (loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, ma_tai_san, ma_qr, ten_tai_san, tinh_trang, trang_thai) 
             VALUES (?, ?, ?, ?, ?, ?, 'TOT', 'SAN_SANG')",
            [$loaiTsId, $phongTrucId, $phongHoc1Id, $maTs, $maQr, 'Thiết bị test ' . ($i + 1)]
        );
        $tsIds[] = (int)$db->insert_id;
    }

    // 6. Tạo người mượn và phiếu mượn cho TS 4 (chưa trả)
    $maNm = 'NM-KK-' . $maTest;
    $db->execute_query(
        "INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon, lop_don_vi, so_dien_thoai) 
         VALUES (?, ?, 'Người mượn test KK', 'GIANG_VIEN', 'Khoa CNTT', '0901234567')",
        [$maNm, 'CB-' . $maTest]
    );
    $nguoiMuonId = (int)$db->insert_id;

    $maPm = 'PM-KK-' . $maTest;
    $db->execute_query(
        "INSERT INTO phieu_muon (ma_phieu_muon, nguoi_muon_id, phong_truc_id, nhan_vien_lap_id, phong_hoc_id, han_tra, trang_thai, ghi_chu) 
         VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 4 HOUR), 'DANG_MUON', 'Mục đích test kiểm kê')",
        [$maPm, $nguoiMuonId, $phongTrucId, $nhanVienId, $phongHoc1Id]
    );
    $phieuMuonId = (int)$db->insert_id;

    $db->execute_query(
        "INSERT INTO chi_tiet_muon (phieu_muon_id, tai_san_id, tinh_trang_khi_muon) VALUES (?, ?, 'TOT')",
        [$phieuMuonId, $tsIds[4]]
    );

    // Cập nhật tài sản 4 sang ĐANG_MUON
    $db->execute_query("UPDATE tai_san SET trang_thai = 'DANG_MUON' WHERE id = ?", [$tsIds[4]]);

    echo "  [OK] Đã khởi tạo môi trường dữ liệu test xong.\n\n";

    // -------------------------------------------------------------
    // TEST 1: KIỂM TRA RÀNG BUỘC KHI MỞ ĐỢT KIỂM KÊ
    // -------------------------------------------------------------
    $kqLoiTen = kiem_ke_mo_dot([
        'ten_dot_kiem_ke' => '',
        'loai_kiem_ke'    => LOAI_KIEM_KE_CUOI_BUOI,
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqLoiTen['thanh_cong'], "Từ chối mở đợt kiểm kê khi tên đợt rỗng.");

    $kqLoiLoai = kiem_ke_mo_dot([
        'ten_dot_kiem_ke' => 'Đợt test hợp lệ',
        'loai_kiem_ke'    => 'KHONG_HOP_LE',
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqLoiLoai['thanh_cong'], "Từ chối mở đợt kiểm kê khi loại kiểm kê không hợp lệ.");

    // -------------------------------------------------------------
    // TEST 2: MỞ ĐỢT KIỂM KÊ HỢP LỆ VÀ SNAPSHOT SỔ SÁCH
    // -------------------------------------------------------------
    $kqMo = kiem_ke_mo_dot([
        'ten_dot_kiem_ke' => 'Đợt kiểm kê cuối ca ' . $maTest,
        'loai_kiem_ke'    => LOAI_KIEM_KE_CUOI_BUOI,
        'ghi_chu'         => 'Ghi chú mở đợt kiểm kê',
    ], $nhanVienId, $phongTrucId);

    kt_assert($kqMo['thanh_cong'], "Mở đợt kiểm kê hợp lệ thành công (Mã: {$kqMo['ma_dot_kiem_ke']}).");
    $dotKiemKe1Id = (int)$kqMo['id'];

    // Kiểm tra snapshot chi_tiet_kiem_ke
    $chiTietRows = kiem_ke_lay_chi_tiet_dot($dotKiemKe1Id);
    kt_assert(count($chiTietRows) === 5, "Snapshot ghi nhận đủ 5 tài sản thuộc bàn trực vào chi_tiet_kiem_ke (Thực tế: " . count($chiTietRows) . ").");

    // Kiểm tra tài sản 4 có trang_thai_so_sach = 'DANG_MUON'
    $ts4Ct = null;
    $ts0Ct = null;
    $ts1Ct = null;
    $ts2Ct = null;
    $ts3Ct = null;
    foreach ($chiTietRows as $r) {
        if ((int)$r['tai_san_id'] === $tsIds[4]) $ts4Ct = $r;
        if ((int)$r['tai_san_id'] === $tsIds[0]) $ts0Ct = $r;
        if ((int)$r['tai_san_id'] === $tsIds[1]) $ts1Ct = $r;
        if ((int)$r['tai_san_id'] === $tsIds[2]) $ts2Ct = $r;
        if ((int)$r['tai_san_id'] === $tsIds[3]) $ts3Ct = $r;
    }

    kt_assert($ts4Ct !== null && $ts4Ct['trang_thai_so_sach'] === 'DANG_MUON', "Tài sản đang mượn được tự động gán trang_thai_so_sach = 'DANG_MUON'.");
    kt_assert($ts0Ct !== null && $ts0Ct['ket_qua'] === 'CHUA_KIEM_TRA', "Tài sản chưa kiểm tra có ket_qua = 'CHUA_KIEM_TRA'.");

    // -------------------------------------------------------------
    // TEST 3: CHẶN MỞ ĐỢT THỨ HAI TRÊN CÙNG BÀN TRỰC
    // -------------------------------------------------------------
    $kqMo2 = kiem_ke_mo_dot([
        'ten_dot_kiem_ke' => 'Đợt kiểm kê thứ hai ' . $maTest,
        'loai_kiem_ke'    => LOAI_KIEM_KE_CUOI_BUOI,
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqMo2['thanh_cong'], "Chặn mở đợt kiểm kê thứ 2 khi đợt trước đang diễn ra.");

    // -------------------------------------------------------------
    // TEST 4: KHÓA BÀN TRỰC KHỎI MƯỢN MỚI VÀ ĐIỀU CHUYỂN
    // -------------------------------------------------------------
    $khoaMuon = muon_kiem_tra_khoa_kiem_ke($phongTrucId);
    kt_assert($khoaMuon === true, "muon_kiem_tra_khoa_kiem_ke() xác nhận bàn trực đang bị khóa mượn.");

    $khoaDieuChuyen = dieu_chuyen_kiem_tra_khoa_kiem_ke($phongTrucId);
    kt_assert($khoaDieuChuyen === true, "dieu_chuyen_kiem_tra_khoa_kiem_ke() xác nhận bàn trực đang bị khóa điều chuyển.");

    // Thử tạo phiếu điều chuyển khi bàn trực đang kiểm kê -> phải bị từ chối
    $kqDc = dieu_chuyen_tao([
        'phong_hoc_di_id'       => $phongHoc1Id,
        'phong_hoc_den_id'      => $phongHoc2Id,
        'ly_do'                 => 'Điều chuyển thử trong khi kiểm kê',
        'xac_nhan_tuong_thich'  => '1',
        'danh_sach_tai_san_ids' => [$tsIds[0]],
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqDc['thanh_cong'], "dieu_chuyen_tao() bị chặn khi bàn trực đang kiểm kê.");

    // -------------------------------------------------------------
    // TEST 5: TRA CỨU ĐỢT ĐANG MỞ VÀ THỐNG KÊ
    // -------------------------------------------------------------
    $dotDangMo = kiem_ke_tim_dot_dang_mo($phongTrucId);
    kt_assert($dotDangMo !== null && (int)$dotDangMo['id'] === $dotKiemKe1Id, "kiem_ke_tim_dot_dang_mo() tìm thấy đúng đợt đang mở.");
    kt_assert($dotDangMo['thong_ke']['tong_so_sach'] === 5, "Thống kê tổng sổ sách: 5.");
    kt_assert($dotDangMo['thong_ke']['so_dang_muon'] === 1, "Thống kê số đang mượn: 1.");
    kt_assert($dotDangMo['thong_ke']['so_con_lai_can_kiem'] === 4, "Thống kê số còn lại cần kiểm: 4.");

    // -------------------------------------------------------------
    // TEST 6: CHẶN GHI NHẬN ĐỐI CHIẾU CHO TÀI SẢN ĐANG MƯỢN
    // -------------------------------------------------------------
    $kqGhiMuon = kiem_ke_ghi_nhan([
        'dot_kiem_ke_id'     => $dotKiemKe1Id,
        'chi_tiet_id'        => (int)$ts4Ct['id'],
        'ket_qua'            => 'TIM_THAY',
        'tinh_trang_thuc_te' => 'TOT',
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqGhiMuon['thanh_cong'], "Từ chối ghi nhận đối chiếu thực tế cho tài sản đang có phiếu mượn.");

    // -------------------------------------------------------------
    // TEST 7: GHI NHẬN TS 0: TÌM THẤY - TỐT - ĐÚNG VỊ TRÍ
    // -------------------------------------------------------------
    $kqGhi0 = kiem_ke_ghi_nhan([
        'dot_kiem_ke_id'       => $dotKiemKe1Id,
        'chi_tiet_id'          => (int)$ts0Ct['id'],
        'ket_qua'              => 'TIM_THAY',
        'tinh_trang_thuc_te'   => 'TOT',
        'phong_hoc_thuc_te_id' => $phongHoc1Id,
        'ghi_chu'              => 'Bình thường',
    ], $nhanVienId, $phongTrucId);
    kt_assert($kqGhi0['thanh_cong'], "Ghi nhận TS 0 (Có mặt - Tốt - đúng vị trí) thành công.");

    // -------------------------------------------------------------
    // TEST 8: GHI NHẬN TS 1: TÌM THẤY - HƯ HỎNG (YÊU CẦU GHI CHÚ)
    // -------------------------------------------------------------
    $kqGhi1KhongGhiChu = kiem_ke_ghi_nhan([
        'dot_kiem_ke_id'       => $dotKiemKe1Id,
        'chi_tiet_id'          => (int)$ts1Ct['id'],
        'ket_qua'              => 'TIM_THAY',
        'tinh_trang_thuc_te'   => 'HU_HONG',
        'phong_hoc_thuc_te_id' => $phongHoc1Id,
        'ghi_chu'              => '',
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqGhi1KhongGhiChu['thanh_cong'], "Bắt buộc nhập ghi chú mô tả hư hỏng khi thiết bị bị hỏng.");

    $kqGhi1CoGhiChu = kiem_ke_ghi_nhan([
        'dot_kiem_ke_id'       => $dotKiemKe1Id,
        'chi_tiet_id'          => (int)$ts1Ct['id'],
        'ket_qua'              => 'TIM_THAY',
        'tinh_trang_thuc_te'   => 'HU_HONG',
        'phong_hoc_thuc_te_id' => $phongHoc1Id,
        'ghi_chu'              => 'Bị bể vỏ ngoài và hỏng cổng HDMI',
    ], $nhanVienId, $phongTrucId);
    kt_assert($kqGhi1CoGhiChu['thanh_cong'], "Ghi nhận TS 1 (Có mặt - Hư hỏng kèm ghi chú) thành công.");

    // -------------------------------------------------------------
    // TEST 9: GHI NHẬN TS 2: TÌM THẤY - SAI PHÒNG HỌC
    // -------------------------------------------------------------
    $kqGhi2 = kiem_ke_ghi_nhan([
        'dot_kiem_ke_id'       => $dotKiemKe1Id,
        'chi_tiet_id'          => (int)$ts2Ct['id'],
        'ket_qua'              => 'TIM_THAY',
        'tinh_trang_thuc_te'   => 'TOT',
        'phong_hoc_thuc_te_id' => $phongHoc2Id, // Khác phòng sổ sách (PH1)
        'ghi_chu'              => 'Tìm thấy tại phòng A2 Test thay vì phòng A1',
    ], $nhanVienId, $phongTrucId);
    kt_assert($kqGhi2['thanh_cong'], "Ghi nhận TS 2 (Có mặt - Sai vị trí phòng học) thành công.");

    // -------------------------------------------------------------
    // TEST 10: CHẶN CHỐT SỔ KHI CÒN THIẾT BỊ CHƯA KIỂM (TS 3 chưa kiểm)
    // -------------------------------------------------------------
    $kqChotSom = kiem_ke_hoan_tat($dotKiemKe1Id, $nhanVienId, $phongTrucId);
    kt_assert(!$kqChotSom['thanh_cong'], "Chặn chốt sổ hoàn tất khi vẫn còn thiết bị chưa đối chiếu (TS 3).");

    // -------------------------------------------------------------
    // TEST 11: GHI NHẬN TS 3: CHƯA TÌM THẤY (MẤT - YÊU CẦU GHI CHÚ)
    // -------------------------------------------------------------
    $kqGhi3KhongGhiChu = kiem_ke_ghi_nhan([
        'dot_kiem_ke_id' => $dotKiemKe1Id,
        'chi_tiet_id'    => (int)$ts3Ct['id'],
        'ket_qua'        => 'CHUA_TIM_THAY',
        'ghi_chu'        => '',
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqGhi3KhongGhiChu['thanh_cong'], "Bắt buộc nhập ghi chú khi thiết bị Chưa tìm thấy.");

    $kqGhi3CoGhiChu = kiem_ke_ghi_nhan([
        'dot_kiem_ke_id' => $dotKiemKe1Id,
        'chi_tiet_id'    => (int)$ts3Ct['id'],
        'ket_qua'        => 'CHUA_TIM_THAY',
        'ghi_chu'        => 'Đã rà soát toàn bộ phòng A1 và kho trực nhưng không thấy',
    ], $nhanVienId, $phongTrucId);
    kt_assert($kqGhi3CoGhiChu['thanh_cong'], "Ghi nhận TS 3 (Chưa tìm thấy kèm ghi chú) thành công.");

    // -------------------------------------------------------------
    // TEST 12: HOÀN TẤT CHỐT SỔ ĐỢT KIỂM KÊ
    // -------------------------------------------------------------
    $kqChot = kiem_ke_hoan_tat($dotKiemKe1Id, $nhanVienId, $phongTrucId);
    kt_assert($kqChot['thanh_cong'], "Chốt sổ đợt kiểm kê thành công (Số sự cố sinh ra: {$kqChot['so_su_co']}).");
    kt_assert($kqChot['so_su_co'] === 3, "Hệ thống tự động phát hiện và sinh đủ 3 sự cố (Mất, Hư hỏng, Sai vị trí).");

    // Kiểm tra trạng thái đợt kiểm kê trong CSDL
    $dotSauChot = kiem_ke_tim_theo_id($dotKiemKe1Id);
    kt_assert($dotSauChot['trang_thai'] === 'HOAN_TAT', "Trạng thái đợt kiểm kê được cập nhật sang HOAN_TAT.");
    kt_assert(!empty($dotSauChot['thoi_gian_ket_thuc']), "thoi_gian_ket_thuc được gán thời gian kết thúc.");

    // Kiểm tra các sự cố được sinh ra trong su_co_tai_san
    $scMat = $db->query("SELECT * FROM su_co_tai_san WHERE tai_san_id = {$tsIds[3]} AND loai_su_co = 'MAT'")->fetch_assoc();
    kt_assert($scMat !== null, "Đã tự động tạo phiếu sự cố MẤT cho tài sản 3.");

    $scHong = $db->query("SELECT * FROM su_co_tai_san WHERE tai_san_id = {$tsIds[1]} AND loai_su_co = 'HU_HONG'")->fetch_assoc();
    kt_assert($scHong !== null, "Đã tự động tạo phiếu sự cố HƯ HỎNG cho tài sản 1.");

    $scSaiVt = $db->query("SELECT * FROM su_co_tai_san WHERE tai_san_id = {$tsIds[2]} AND loai_su_co = 'SAI_VI_TRI'")->fetch_assoc();
    kt_assert($scSaiVt !== null, "Đã tự động tạo phiếu sự cố SAI VỊ TRÍ cho tài sản 2.");

    // Kiểm tra trạng thái các tài sản sau chốt sổ
    $ts3Db = $db->query("SELECT trang_thai FROM tai_san WHERE id = {$tsIds[3]}")->fetch_assoc();
    kt_assert($ts3Db['trang_thai'] === 'NGUNG_SU_DUNG', "Tài sản 3 (Mất) được cập nhật trạng thái sang NGUNG_SU_DUNG.");

    $ts1Db = $db->query("SELECT tinh_trang, trang_thai FROM tai_san WHERE id = {$tsIds[1]}")->fetch_assoc();
    kt_assert($ts1Db['tinh_trang'] === 'HU_HONG' && $ts1Db['trang_thai'] === 'BAO_TRI', "Tài sản 1 (Hỏng) cập nhật tinh_trang = HU_HONG và trang_thai = BAO_TRI.");

    $ts2Db = $db->query("SELECT trang_thai FROM tai_san WHERE id = {$tsIds[2]}")->fetch_assoc();
    kt_assert($ts2Db['trang_thai'] === 'NGUNG_SU_DUNG', "Tài sản 2 (Sai vị trí) tạm khóa sang NGUNG_SU_DUNG để xử lý điều chuyển.");

    // -------------------------------------------------------------
    // TEST 13: MỞ KHÓA BÀN TRỰC SAU KHI CHỐT SỔ
    // -------------------------------------------------------------
    $khoaSauChot = muon_kiem_tra_khoa_kiem_ke($phongTrucId);
    kt_assert($khoaSauChot === false, "Bàn trực được mở khóa mượn trả sau khi chốt sổ kiểm kê.");

    $khoaDcSauChot = dieu_chuyen_kiem_tra_khoa_kiem_ke($phongTrucId);
    kt_assert($khoaDcSauChot === false, "Bàn trực được mở khóa điều chuyển sau khi chốt sổ kiểm kê.");

    // -------------------------------------------------------------
    // TEST 14: KIỂM TRA HỦY ĐỢT KIỂM KÊ (kiem_ke_huy)
    // -------------------------------------------------------------
    $kqMoDot2 = kiem_ke_mo_dot([
        'ten_dot_kiem_ke' => 'Đợt kiểm kê sẽ hủy ' . $maTest,
        'loai_kiem_ke'    => LOAI_KIEM_KE_DOT_XUAT,
    ], $nhanVienId, $phongTrucId);
    kt_assert($kqMoDot2['thanh_cong'], "Mở thành công đợt kiểm kê thứ hai để thử nghiệm hủy đợt.");
    $dotKiemKe2Id = (int)$kqMoDot2['id'];

    // Hủy không có lý do -> thất bại
    $kqHuyKhongLyDo = kiem_ke_huy($dotKiemKe2Id, '', $nhanVienId, $phongTrucId);
    kt_assert(!$kqHuyKhongLyDo['thanh_cong'], "Bắt buộc nhập lý do khi hủy đợt kiểm kê.");

    // Hủy có lý do hợp lệ -> thành công
    $kqHuyCoLyDo = kiem_ke_huy($dotKiemKe2Id, 'Hủy đợt do thay đổi lịch công tác đột xuất', $nhanVienId, $phongTrucId);
    kt_assert($kqHuyCoLyDo['thanh_cong'], "Hủy đợt kiểm kê có lý do thành công.");

    $dot2Db = kiem_ke_tim_theo_id($dotKiemKe2Id);
    kt_assert($dot2Db['trang_thai'] === 'DA_HUY', "Đợt kiểm kê cập nhật trạng thái DA_HUY.");
    kt_assert((int)$dot2Db['nhan_vien_huy_id'] === $nhanVienId, "Ghi nhận đúng cán bộ hủy đợt.");
    kt_assert(!empty($dot2Db['thoi_gian_ket_thuc']), "thoi_gian_ket_thuc được gán thời điểm hủy.");
    kt_assert($dot2Db['ly_do_huy'] === 'Hủy đợt do thay đổi lịch công tác đột xuất', "Lý do hủy được lưu chính xác.");

    // Bàn trực lại được mở khóa sau khi hủy đợt
    kt_assert(muon_kiem_tra_khoa_kiem_ke($phongTrucId) === false, "Bàn trực được mở khóa sau khi hủy đợt kiểm kê.");

    // -------------------------------------------------------------
    // TEST 15: KIỂM TRA API TRA CỨU MÃ QR KIỂM KÊ (src/api/kiem-ke.php)
    // -------------------------------------------------------------
    // Tra cứu mã QR của TS 0 trong đợt 1
    $_GET = [
        'hanh_dong'      => 'tra_cuu',
        'dot_kiem_ke_id' => (string)$dotKiemKe1Id,
        'ma_qr'          => 'QR-' . $maTest . '-01',
    ];
    ob_start();
    // Gọi trực tiếp hàm tra cứu qua service
    $taiSanTim = tai_san_tim_theo_qr('QR-' . $maTest . '-01');
    kt_assert($taiSanTim !== null && (int)$taiSanTim['id'] === $tsIds[0], "API / Service tra cứu mã QR tìm chính xác tài sản trong đợt.");

} catch (Throwable $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . " tại " . $e->getFile() . ":" . $e->getLine() . "\n";
    $tongBai++;
} finally {
    // -------------------------------------------------------------
    // DỌN DẸP DỮ LIỆU KIỂM THỬ (CLEANUP TEARDOWN)
    // -------------------------------------------------------------
    echo "\n  [CLEANUP] Đang dọn dẹp dữ liệu kiểm thử test...\n";
    if ($dotKiemKe1Id > 0) {
        $db->execute_query("DELETE FROM su_co_tai_san WHERE chi_tiet_kiem_ke_id IN (SELECT id FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?)", [$dotKiemKe1Id]);
        $db->execute_query("DELETE FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?", [$dotKiemKe1Id]);
        $db->execute_query("DELETE FROM dot_kiem_ke WHERE id = ?", [$dotKiemKe1Id]);
    }
    if ($dotKiemKe2Id > 0) {
        $db->execute_query("DELETE FROM chi_tiet_kiem_ke WHERE dot_kiem_ke_id = ?", [$dotKiemKe2Id]);
        $db->execute_query("DELETE FROM dot_kiem_ke WHERE id = ?", [$dotKiemKe2Id]);
    }
    if ($phieuMuonId > 0) {
        $db->execute_query("DELETE FROM chi_tiet_muon WHERE phieu_muon_id = ?", [$phieuMuonId]);
        $db->execute_query("DELETE FROM phieu_muon WHERE id = ?", [$phieuMuonId]);
    }
    if (!empty($tsIds)) {
        $inTs = implode(',', $tsIds);
        $db->execute_query("DELETE FROM su_co_tai_san WHERE tai_san_id IN ($inTs)");
        $db->execute_query("DELETE FROM tai_san WHERE id IN ($inTs)");
    }
    if ($nguoiMuonId > 0) {
        $db->execute_query("DELETE FROM nguoi_muon WHERE id = ?", [$nguoiMuonId]);
    }
    if ($loaiTsId > 0) {
        $db->execute_query("DELETE FROM loai_tai_san WHERE id = ?", [$loaiTsId]);
    }
    if ($phongHoc1Id > 0) {
        $db->execute_query("DELETE FROM phong_hoc WHERE id = ?", [$phongHoc1Id]);
    }
    if ($phongHoc2Id > 0) {
        $db->execute_query("DELETE FROM phong_hoc WHERE id = ?", [$phongHoc2Id]);
    }
    if ($nhanVienId > 0) {
        $db->execute_query("DELETE FROM nhan_vien WHERE id = ?", [$nhanVienId]);
    }
    if ($phongTrucId > 0) {
        $db->execute_query("DELETE FROM phong_truc WHERE id = ?", [$phongTrucId]);
    }
    echo "  [OK] Đã dọn dẹp sạch sẽ toàn bộ dữ liệu test.\n";
}

echo "\n------------------------------------------------------------\n";
echo "  KẾT QUẢ [BÀI THỬ 9: KIỂM KÊ TÀI SẢN (G8)]: $soDat / $tongBai bài đạt.\n";
echo "============================================================\n\n";

if ($soDat !== $tongBai) {
    exit(1);
}
exit(0);
