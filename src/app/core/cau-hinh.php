<?php
/**
 * Quản lý hằng số hệ thống và nạp tệp cấu hình ứng dụng.
 * Tệp này định nghĩa tập trung tất cả hằng số nghiệp vụ để toàn bộ
 * hệ thống tuân thủ "Single Source of Truth", không dùng chuỗi ký tự tự do.
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

// =====================================================================
// 1. VAI TRÒ CÁN BỘ HỆ THỐNG (nhan_vien.vai_tro)
// =====================================================================
const VAI_TRO_QUAN_TRI  = 'QUAN_TRI';  // Quản trị viên hệ thống (toàn quyền)
const VAI_TRO_QUAN_LY   = 'QUAN_LY';   // Cán bộ Quản lý (danh mục, thiết bị, báo cáo, điều chuyển, sự cố)
const VAI_TRO_NHAN_VIEN = 'NHAN_VIEN'; // Nhân viên trực phòng mượn - trả, kiểm kê

const TEN_VAI_TRO = [
    VAI_TRO_QUAN_TRI  => 'Quản trị viên',
    VAI_TRO_QUAN_LY   => 'Cán bộ Quản lý',
    VAI_TRO_NHAN_VIEN => 'Nhân viên trực',
];

// =====================================================================
// 2. TÌNH TRẠNG VẬT LÝ CỦA TÀI SẢN (tai_san.tinh_trang)
// =====================================================================
const TINH_TRANG_TOT      = 'TOT';      // Thiết bị nguyên vẹn, sử dụng bình thường
const TINH_TRANG_HU_HONG  = 'HU_HONG';  // Thiết bị bị hư hỏng, cần sửa chữa
const TINH_TRANG_MAT      = 'MAT';      // Thiết bị bị thất lạc hoặc mất

const TEN_TINH_TRANG = [
    TINH_TRANG_TOT     => 'Tốt',
    TINH_TRANG_HU_HONG => 'Hư hỏng',
    TINH_TRANG_MAT     => 'Mất',
];

// =====================================================================
// 3. TRẠNG THÁI VẬN HÀNH CỦA TÀI SẢN (tai_san.trang_thai)
// =====================================================================
const TRANG_THAI_SAN_SANG      = 'SAN_SANG';       // Sẵn sàng cho mượn tại phòng trực
const TRANG_THAI_DANG_MUON     = 'DANG_MUON';      // Đang được mượn phục vụ tại phòng học
const TRANG_THAI_BAO_TRI       = 'BAO_TRI';        // Đang chờ hoặc đang sửa chữa
const TRANG_THAI_NGUNG_SU_DUNG = 'NGUNG_SU_DUNG';  // Đã thanh lý hoặc ngưng lưu hành

const TEN_TRANG_THAI = [
    TRANG_THAI_SAN_SANG      => 'Sẵn sàng',
    TRANG_THAI_DANG_MUON     => 'Đang mượn',
    TRANG_THAI_BAO_TRI       => 'Bảo trì',
    TRANG_THAI_NGUNG_SU_DUNG => 'Ngừng sử dụng',
];

// =====================================================================
// 4. PHẠM VI TƯƠNG THÍCH THIẾT BỊ VỚI PHÒNG HỌC (tai_san.pham_vi_tuong_thich)
// =====================================================================
const TUONG_THICH_THEO_THIET_BI = 'THEO_THIET_BI'; // Khớp theo Hãng sản xuất & Model thiết bị phòng học
const TUONG_THICH_THEO_PHONG    = 'THEO_PHONG';    // Cố định theo danh sách mã phòng học chỉ định
const TUONG_THICH_LINH_HOAT     = 'LINH_HOAT';     // Dùng chung cho mọi phòng học (cáp, ổ cắm, loa kéo...)

const TEN_PHAM_VI_TUONG_THICH = [
    TUONG_THICH_THEO_THIET_BI => 'Theo Hãng/Model thiết bị phòng',
    TUONG_THICH_THEO_PHONG    => 'Theo phòng chỉ định',
    TUONG_THICH_LINH_HOAT     => 'Linh hoạt (mọi phòng)',
];

// =====================================================================
// 5. TRẠNG THÁI PHIẾU MƯỢN (phieu_muon.trang_thai)
// =====================================================================
const PHIEU_MUON_DANG_MUON = 'DANG_MUON'; // Đang còn thiết bị chưa trả
const PHIEU_MUON_DA_TRA    = 'DA_TRA';    // Đã hoàn tất trả toàn bộ thiết bị

// =====================================================================
// 6. ĐỢT KIỂM KÊ (dot_kiem_ke.trang_thai & loai_kiem_ke)
// =====================================================================
const KIEM_KE_DANG_KIEM_KE = 'DANG_KIEM_KE';
const KIEM_KE_HOAN_TAT     = 'HOAN_TAT';
const KIEM_KE_DA_HUY       = 'DA_HUY';

const LOAI_KIEM_KE_CUOI_BUOI = 'CUOI_BUOI';
const LOAI_KIEM_KE_DINH_KY   = 'DINH_KY';
const LOAI_KIEM_KE_DOT_XUAT  = 'DOT_XUAT';

// =====================================================================
// 7. SỰ CỐ TÀI SẢN (su_co_tai_san.trang_thai & loai_su_co)
// =====================================================================
const SU_CO_CHUA_XU_LY = 'CHUA_XU_LY';
const SU_CO_DANG_XU_LY = 'DANG_XU_LY';
const SU_CO_DA_XU_LY   = 'DA_XU_LY';

const LOAI_SU_CO_HU_HONG    = 'HU_HONG';
const LOAI_SU_CO_MAT        = 'MAT';
const LOAI_SU_CO_SAI_VI_TRI = 'SAI_VI_TRI';

// =====================================================================
// 8. GIỚI HẠN NGHIỆP VỤ HỆ THỐNG
// =====================================================================
const GIOI_HAN_THIET_BI_MUON_TOI_DA = 30; // Tối đa 30 thiết bị trong 1 phiếu mượn

/**
 * Đọc giá trị cấu hình từ tệp cấu hình cục bộ hoặc tệp mẫu.
 * Hỗ trợ truy xuất phân cấp bằng dấu chấm, ví dụ: cau_hinh('csdl.host')
 *
 * @param string $khoa Khóa cấu hình (ví dụ: 'csdl.host', 'ung_dung.ten')
 * @param mixed $macDinh Giá trị mặc định trả về nếu không tìm thấy khóa
 * @return mixed Giá trị cấu hình
 */
function cau_hinh(string $khoa, mixed $macDinh = null): mixed {
    static $duLieuCauHinh = null;

    if ($duLieuCauHinh === null) {
        $duLieuCauHinh = [];
        $tepCucBo = dirname(__DIR__) . '/config/config.local.php';
        $tepMau = dirname(__DIR__) . '/config/config.example.php';

        if (is_file($tepCucBo)) {
            $duLieuCauHinh = require $tepCucBo;
        } elseif (is_file($tepMau)) {
            $duLieuCauHinh = require $tepMau;
        }
    }

    $cacDoan = explode('.', $khoa);
    $giaTri = $duLieuCauHinh;
    foreach ($cacDoan as $doan) {
        if (!is_array($giaTri) || !array_key_exists($doan, $giaTri)) {
            return $macDinh;
        }
        $giaTri = $giaTri[$doan];
    }

    return $giaTri;
}
