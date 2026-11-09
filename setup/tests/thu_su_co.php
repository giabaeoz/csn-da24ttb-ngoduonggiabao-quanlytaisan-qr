<?php
/**
 * Bộ kiểm thử tự động BÀI THỬ 10: QUẢN LÝ BÁO CÁO SỰ CỐ & BẢO TRÌ SỬA CHỮA (G9).
 * 
 * Kiểm tra các ràng buộc & nghiệp vụ:
 * 1. Báo cáo sự cố mới (Hư hỏng, Mất, Sai vị trí):
 *    - Sinh mã duy nhất SC-YYYYMMDD-XXXXXXXX.
 *    - Tự động cập nhật trạng thái tài sản (HU_HONG -> BAO_TRI, MAT/SAI_VI_TRI -> NGUNG_SU_DUNG).
 *    - Ràng buộc mô tả không được rỗng (ck_su_co_mo_ta).
 * 2. Tiếp nhận xử lý sự cố (DANG_XU_LY):
 *    - Giữ thoi_gian_xu_ly = NULL, ket_qua_xu_ly = NULL (ck_su_co_ket_qua_xu_ly).
 * 3. Hoàn tất xử lý sự cố (DA_XU_LY):
 *    - Bắt buộc có kết quả xử lý (ket_qua_xu_ly).
 *    - thoi_gian_xu_ly >= thoi_gian_phat_hien (ck_su_co_thoi_gian).
 *    - Khôi phục trạng thái và vị trí tài sản sau sửa chữa/bảo hành.
 * 4. Chặn thao tác không hợp lệ trên sự cố đã hoàn tất.
 * 5. Tra cứu, thống kê và bộ lọc danh sách sự cố.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/src/app/core/khoi-dong.php';
require_once dirname(__DIR__, 2) . '/src/app/services/su-co-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/tai-san-service.php';
require_once dirname(__DIR__, 2) . '/src/app/services/danh-muc-service.php';

$db = db();
$maTest = strtoupper(bin2hex(random_bytes(3)));

echo "============================================================\n";
echo "  BÀI THỬ 10: QUẢN LÝ BÁO CÁO SỰ CỐ & BẢO TRÌ (G9)\n";
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
$tsIds = [];
$suCoIds = [];

try {
    // -------------------------------------------------------------
    // BƯỚC 0: THIẾT LẬP DỮ LIỆU KIỂM THỬ BAN ĐẦU
    // -------------------------------------------------------------
    // 1. Tạo bàn trực
    $maPt = 'PT-SC-' . $maTest;
    $db->execute_query(
        "INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc, vi_tri, dang_hoat_dong) VALUES (?, ?, 'Tòa SC Nhà Test', 1)",
        [$maPt, 'Bàn trực Test SC ' . $maTest]
    );
    $phongTrucId = (int)$db->insert_id;

    // 2. Tạo nhân viên trực
    $maNv = 'NV-SC-' . $maTest;
    $db->execute_query(
        "INSERT INTO nhan_vien (phong_truc_id, ma_nhan_vien, ten_dang_nhap, mat_khau_hash, ho_ten, so_dien_thoai, vai_tro, dang_hoat_dong) 
         VALUES (?, ?, ?, 'hash', 'Cán bộ test SC', '0909999888', 'NHAN_VIEN', 1)",
        [$phongTrucId, $maNv, 'nvsc_' . strtolower($maTest)]
    );
    $nhanVienId = (int)$db->insert_id;

    // 3. Tạo 2 phòng học
    $maPh1 = 'PH1-SC-' . $maTest;
    $db->execute_query(
        "INSERT INTO phong_hoc (ma_phong, ten_phong, toa_nha, tang, dang_hoat_dong) 
         VALUES (?, 'Phòng Học SC 1', 'Tòa SC', '1', 1)",
        [$maPh1]
    );
    $phongHoc1Id = (int)$db->insert_id;

    $maPh2 = 'PH2-SC-' . $maTest;
    $db->execute_query(
        "INSERT INTO phong_hoc (ma_phong, ten_phong, toa_nha, tang, dang_hoat_dong) 
         VALUES (?, 'Phòng Học SC 2', 'Tòa SC', '2', 1)",
        [$maPh2]
    );
    $phongHoc2Id = (int)$db->insert_id;

    // 4. Tạo loại tài sản
    $maLoai = 'LT-SC-' . $maTest;
    $db->execute_query(
        "INSERT INTO loai_tai_san (ma_loai, ten_loai, mo_ta) VALUES (?, 'Loại tài sản Test SC', 'Mô tả test SC')",
        [$maLoai]
    );
    $loaiTsId = (int)$db->insert_id;

    // 5. Tạo 3 tài sản
    for ($i = 0; $i < 3; $i++) {
        $maTs = sprintf('TS-SC%d-%s', $i + 1, $maTest);
        $maQr = sprintf('QR-SC-%s-%02d', $maTest, $i + 1);
        $db->execute_query(
            "INSERT INTO tai_san (loai_tai_san_id, phong_truc_id, phong_hoc_hien_tai_id, ma_tai_san, ma_qr, ten_tai_san, tinh_trang, trang_thai) 
             VALUES (?, ?, ?, ?, ?, ?, 'TOT', 'SAN_SANG')",
            [$loaiTsId, $phongTrucId, $phongHoc1Id, $maTs, $maQr, 'Thiết bị test SC ' . ($i + 1)]
        );
        $tsIds[] = (int)$db->insert_id;
    }

    echo "  [OK] Đã khởi tạo môi trường dữ liệu test xong.\n\n";

    // -------------------------------------------------------------
    // TEST 1: RÀNG BUỘC KHI BÁO CÁO SỰ CỐ
    // -------------------------------------------------------------
    $kqLoiTs = su_co_bao_cao([
        'tai_san_id' => 0,
        'loai_su_co' => 'HU_HONG',
        'mo_ta'      => 'Mô tả hợp lệ',
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqLoiTs['thanh_cong'], "Từ chối báo cáo sự cố khi mã thiết bị rỗng / không hợp lệ.");

    $kqLoiLoai = su_co_bao_cao([
        'tai_san_id' => $tsIds[0],
        'loai_su_co' => 'KHONG_HOP_LE',
        'mo_ta'      => 'Mô tả hợp lệ',
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqLoiLoai['thanh_cong'], "Từ chối báo cáo sự cố khi loại sự cố không thuộc enum cho phép.");

    $kqLoiMoTa = su_co_bao_cao([
        'tai_san_id' => $tsIds[0],
        'loai_su_co' => 'HU_HONG',
        'mo_ta'      => '',
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqLoiMoTa['thanh_cong'], "Từ chối báo cáo sự cố khi mô tả rỗng (ck_su_co_mo_ta).");

    // -------------------------------------------------------------
    // TEST 2: BÁO CÁO SỰ CỐ HƯ HỎNG (HU_HONG)
    // -------------------------------------------------------------
    $kqSc1 = su_co_bao_cao([
        'tai_san_id' => $tsIds[0],
        'loai_su_co' => 'HU_HONG',
        'mo_ta'      => 'Cổng HDMI bị gãy chân tiếp xúc, không xuất được tín hiệu hình ảnh.',
    ], $nhanVienId, $phongTrucId);

    kt_assert($kqSc1['thanh_cong'], "Ghi nhận sự cố Hư hỏng thành công (Mã: {$kqSc1['ma_su_co']}).");
    $suCo1Id = (int)$kqSc1['id'];
    $suCoIds[] = $suCo1Id;

    // Kiểm tra CSDL bảng su_co_tai_san
    $sc1Db = su_co_tim_theo_id($suCo1Id);
    kt_assert($sc1Db['trang_thai'] === 'CHUA_XU_LY', "Sự cố mới có trạng thái ban đầu là CHUA_XU_LY.");
    kt_assert($sc1Db['thoi_gian_xu_ly'] === null, "thoi_gian_xu_ly là NULL khi chưa xử lý.");
    kt_assert($sc1Db['ket_qua_xu_ly'] === null, "ket_qua_xu_ly là NULL khi chưa xử lý.");

    // Kiểm tra trạng thái thiết bị 0: chuyển sang BAO_TRI và HU_HONG
    $ts0Db = $db->query("SELECT tinh_trang, trang_thai FROM tai_san WHERE id = {$tsIds[0]}")->fetch_assoc();
    kt_assert($ts0Db['tinh_trang'] === 'HU_HONG', "Tài sản tự động cập nhật tinh_trang = HU_HONG.");
    kt_assert($ts0Db['trang_thai'] === 'BAO_TRI', "Tài sản tự động cập nhật trang_thai = BAO_TRI.");

    // -------------------------------------------------------------
    // TEST 3: BÁO CÁO SỰ CỐ MẤT MÁT (MAT)
    // -------------------------------------------------------------
    $kqSc2 = su_co_bao_cao([
        'tai_san_id' => $tsIds[1],
        'loai_su_co' => 'MAT',
        'mo_ta'      => 'Không tìm thấy thiết bị tại phòng học sau ca học chiều.',
    ], $nhanVienId, $phongTrucId);

    kt_assert($kqSc2['thanh_cong'], "Ghi nhận sự cố Mất mát thành công (Mã: {$kqSc2['ma_su_co']}).");
    $suCo2Id = (int)$kqSc2['id'];
    $suCoIds[] = $suCo2Id;

    // Kiểm tra trạng thái thiết bị 1: chuyển sang NGUNG_SU_DUNG
    $ts1Db = $db->query("SELECT trang_thai FROM tai_san WHERE id = {$tsIds[1]}")->fetch_assoc();
    kt_assert($ts1Db['trang_thai'] === 'NGUNG_SU_DUNG', "Tài sản mất mát tự động chuyển trang_thai = NGUNG_SU_DUNG.");

    // -------------------------------------------------------------
    // TEST 4: BÁO CÁO SỰ CỐ SAI VỊ TRÍ (SAI_VI_TRI)
    // -------------------------------------------------------------
    $kqSc3 = su_co_bao_cao([
        'tai_san_id' => $tsIds[2],
        'loai_su_co' => 'SAI_VI_TRI',
        'mo_ta'      => 'Phát hiện thiết bị bị đưa sang phòng khác mà chưa lập phiếu điều chuyển.',
    ], $nhanVienId, $phongTrucId);

    kt_assert($kqSc3['thanh_cong'], "Ghi nhận sự cố Sai vị trí thành công (Mã: {$kqSc3['ma_su_co']}).");
    $suCo3Id = (int)$kqSc3['id'];
    $suCoIds[] = $suCo3Id;

    $ts2Db = $db->query("SELECT trang_thai FROM tai_san WHERE id = {$tsIds[2]}")->fetch_assoc();
    kt_assert($ts2Db['trang_thai'] === 'NGUNG_SU_DUNG', "Tài sản sai vị trí tạm khóa trang_thai = NGUNG_SU_DUNG.");

    // -------------------------------------------------------------
    // TEST 5: THỐNG KÊ NHANH SỰ CỐ (su_co_thong_ke_nhanh)
    // -------------------------------------------------------------
    $thongKe = su_co_thong_ke_nhanh($phongTrucId);
    kt_assert($thongKe['tong_so'] === 3, "Thống kê tổng số sự cố của bàn trực: 3 (Thực tế: {$thongKe['tong_so']}).");
    kt_assert($thongKe['so_chua_xu_ly'] === 3, "Thống kê số chưa xử lý: 3.");
    kt_assert($thongKe['so_hu_hong'] === 1, "Thống kê số sự cố hư hỏng: 1.");
    kt_assert($thongKe['so_mat'] === 1, "Thống kê số sự cố mất mát: 1.");
    kt_assert($thongKe['so_sai_vi_tri'] === 1, "Thống kê số sự cố sai vị trí: 1.");

    // -------------------------------------------------------------
    // TEST 6: TIẾP NHẬN XỬ LÝ SỰ CỐ (DANG_XU_LY)
    // -------------------------------------------------------------
    $kqTiepNhan = su_co_chuyen_dang_xu_ly($suCo1Id, $nhanVienId, $phongTrucId);
    kt_assert($kqTiepNhan['thanh_cong'], "Tiếp nhận xử lý sự cố 1 thành công.");

    $sc1SauTiepNhan = su_co_tim_theo_id($suCo1Id);
    kt_assert($sc1SauTiepNhan['trang_thai'] === 'DANG_XU_LY', "Trạng thái sự cố chuyển sang DANG_XU_LY.");
    kt_assert($sc1SauTiepNhan['thoi_gian_xu_ly'] === null, "thoi_gian_xu_ly vẫn là NULL khi DANG_XU_LY.");
    kt_assert($sc1SauTiepNhan['ket_qua_xu_ly'] === null, "ket_qua_xu_ly vẫn là NULL khi DANG_XU_LY.");

    // -------------------------------------------------------------
    // TEST 7: HOÀN TẤT XỬ LÝ SỰ CỐ (DA_XU_LY)
    // -------------------------------------------------------------
    // Hoàn tất không có kết quả -> phải bị từ chối
    $kqHoanTatRong = su_co_hoan_tat_xu_ly($suCo1Id, [
        'ket_qua_xu_ly' => '',
    ], $nhanVienId, $phongTrucId);
    kt_assert(!$kqHoanTatRong['thanh_cong'], "Từ chối hoàn tất xử lý khi kết quả xử lý rỗng.");

    // Hoàn tất hợp lệ: Đã thay thế chân cắm HDMI, khôi phục SAN_SANG & TOT
    $kqHoanTat = su_co_hoan_tat_xu_ly($suCo1Id, [
        'ket_qua_xu_ly'        => 'Đã thay mới chân cắm HDMI và kiểm tra xuất hình ảnh đạt chuẩn full HD.',
        'tinh_trang_tai_san'   => 'TOT',
        'trang_thai_tai_san'   => 'SAN_SANG',
    ], $nhanVienId, $phongTrucId);
    kt_assert($kqHoanTat['thanh_cong'], "Hoàn tất xử lý sự cố 1 thành công.");

    $sc1SauHoanTat = su_co_tim_theo_id($suCo1Id);
    kt_assert($sc1SauHoanTat['trang_thai'] === 'DA_XU_LY', "Trạng thái sự cố chuyển sang DA_XU_LY.");
    kt_assert(!empty($sc1SauHoanTat['thoi_gian_xu_ly']), "thoi_gian_xu_ly được gán thời gian hoàn tất.");
    kt_assert(!empty($sc1SauHoanTat['ket_qua_xu_ly']), "ket_qua_xu_ly được lưu đầy đủ.");

    // Kiểm tra trạng thái thiết bị 0 được khôi phục: TOT và SAN_SANG
    $ts0KhoiPhuc = $db->query("SELECT tinh_trang, trang_thai FROM tai_san WHERE id = {$tsIds[0]}")->fetch_assoc();
    kt_assert($ts0KhoiPhuc['tinh_trang'] === 'TOT', "Thiết bị được khôi phục tinh_trang = TOT.");
    kt_assert($ts0KhoiPhuc['trang_thai'] === 'SAN_SANG', "Thiết bị được khôi phục trang_thai = SAN_SANG (sẵn sàng sử dụng).");

    // -------------------------------------------------------------
    // TEST 8: HOÀN TẤT XỬ LÝ KÈM CẬP NHẬT VỊ TRÍ PHÒNG HỌC MỚI (TS 2)
    // -------------------------------------------------------------
    $kqHoanTatVt = su_co_hoan_tat_xu_ly($suCo3Id, [
        'ket_qua_xu_ly'      => 'Đã di chuyển thiết bị về đúng phòng học PH2 theo nhu cầu giảng dạy.',
        'tinh_trang_tai_san' => 'TOT',
        'trang_thai_tai_san' => 'SAN_SANG',
        'phong_hoc_moi_id'   => $phongHoc2Id,
    ], $nhanVienId, $phongTrucId);
    kt_assert($kqHoanTatVt['thanh_cong'], "Hoàn tất xử lý sự cố Sai vị trí kèm cập nhật vị trí phòng học mới thành công.");

    $ts2SauVt = $db->query("SELECT phong_hoc_hien_tai_id, trang_thai FROM tai_san WHERE id = {$tsIds[2]}")->fetch_assoc();
    kt_assert((int)$ts2SauVt['phong_hoc_hien_tai_id'] === $phongHoc2Id, "Vị trí phòng học của thiết bị đã được cập nhật sang Phòng 2.");
    kt_assert($ts2SauVt['trang_thai'] === 'SAN_SANG', "Thiết bị được mở khóa sang SAN_SANG.");

    // -------------------------------------------------------------
    // TEST 9: CHẶN CHUYỂN DANG_XU_LY KHI ĐÃ XỬ LÝ XONG (DA_XU_LY)
    // -------------------------------------------------------------
    $kqChuyenLoi = su_co_chuyen_dang_xu_ly($suCo1Id, $nhanVienId, $phongTrucId);
    kt_assert(!$kqChuyenLoi['thanh_cong'], "Chặn chuyển trạng thái sang Đang xử lý khi sự cố đã xử lý hoàn tất.");

    // -------------------------------------------------------------
    // TEST 10: TRA CỨU & BỘ LỌC DANH SÁCH SỰ CỐ
    // -------------------------------------------------------------
    $dsLoc = su_co_danh_sach(['trang_thai' => 'DA_XU_LY', 'phong_truc_id' => $phongTrucId]);
    kt_assert($dsLoc['tong_so'] === 2, "Bộ lọc trạng thái DA_XU_LY tìm thấy chính xác 2 sự cố.");

    $dsLocChua = su_co_danh_sach(['trang_thai' => 'CHUA_XU_LY', 'phong_truc_id' => $phongTrucId]);
    kt_assert($dsLocChua['tong_so'] === 1, "Bộ lọc trạng thái CHUA_XU_LY tìm thấy chính xác 1 sự cố (sự cố mất mát).");

} catch (Throwable $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . " tại " . $e->getFile() . ":" . $e->getLine() . "\n";
    $tongBai++;
} finally {
    // -------------------------------------------------------------
    // DỌN DẸP DỮ LIỆU KIỂM THỬ (CLEANUP TEARDOWN)
    // -------------------------------------------------------------
    echo "\n  [CLEANUP] Đang dọn dẹp dữ liệu kiểm thử test...\n";
    if (!empty($suCoIds)) {
        $inSc = implode(',', $suCoIds);
        $db->execute_query("DELETE FROM su_co_tai_san WHERE id IN ($inSc)");
    }
    if (!empty($tsIds)) {
        $inTs = implode(',', $tsIds);
        $db->execute_query("DELETE FROM su_co_tai_san WHERE tai_san_id IN ($inTs)");
        $db->execute_query("DELETE FROM tai_san WHERE id IN ($inTs)");
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
echo "  KẾT QUẢ [BÀI THỬ 10: QUẢN LÝ SỰ CỐ & BẢO TRÌ (G9)]: $soDat / $tongBai bài đạt.\n";
echo "============================================================\n\n";

if ($soDat !== $tongBai) {
    exit(1);
}
exit(0);
