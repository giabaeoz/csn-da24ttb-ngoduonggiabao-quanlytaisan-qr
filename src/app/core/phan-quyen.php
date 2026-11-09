<?php
/**
 * Quản lý phân quyền dựa trên vai trò (Role-Based Access Control - RBAC).
 * Định nghĩa bảng phân quyền tập trung cho 3 vai trò:
 *  - QUAN_TRI (Quản trị viên)
 *  - QUAN_LY  (Cán bộ Quản lý)
 *  - NHAN_VIEN (Nhân viên trực phòng)
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

/**
 * Ma trận phân quyền các chức năng hệ thống theo vai trò.
 */
const BANG_QUYEN = [
    // 1. Quản trị hệ thống & tài khoản
    'quan_ly_tai_khoan'   => [VAI_TRO_QUAN_TRI],
    'cau_hinh_he_thong'   => [VAI_TRO_QUAN_TRI],

    // 2. Danh mục cơ sở dữ liệu (Phòng trực, Phòng học, Loại tài sản)
    'quan_ly_danh_muc'    => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY],

    // 3. Quản lý tài sản / thiết bị (Thêm, sửa, ngưng sử dụng, in QR)
    'quan_ly_tai_san'     => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY],
    'xem_danh_sach_tai_san' => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN],

    // 4. Quản lý hồ sơ người mượn (Sinh viên / Giảng viên)
    'quan_ly_nguoi_muon'  => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN],

    // 5. Nghiệp vụ Mượn - Trả tài sản bằng mã QR
    'nghiep_vu_muon_tra'  => [VAI_TRO_QUAN_TRI, VAI_TRO_NHAN_VIEN],
    'xem_lich_su_muon_tra' => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN],

    // 6. Nghiệp vụ Điều chuyển tài sản
    'tao_dieu_chuyen'     => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN],
    'xem_dieu_chuyen'     => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN],

    // 7. Nghiệp vụ Kiểm kê tài sản
    'mo_dot_kiem_ke'      => [VAI_TRO_QUAN_TRI, VAI_TRO_NHAN_VIEN],
    'thuc_hien_kiem_ke'   => [VAI_TRO_QUAN_TRI, VAI_TRO_NHAN_VIEN],
    'chot_kiem_ke'        => [VAI_TRO_QUAN_TRI, VAI_TRO_NHAN_VIEN],
    'xem_kiem_ke'         => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN],

    // 8. Báo cáo sự cố tài sản
    'ghi_nhan_su_co'      => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN],
    'xu_ly_su_co'         => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY],

    // 9. Báo cáo thống kê & Biểu đồ Chart.js
    'xem_thong_ke'        => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY],

    // 10. Bảng điều khiển Tổng quan (Dashboard)
    'xem_tong_quan'       => [VAI_TRO_QUAN_TRI, VAI_TRO_QUAN_LY, VAI_TRO_NHAN_VIEN],
];

/**
 * Kiểm tra xem người dùng hiện tại (hoặc người dùng chỉ định) có quyền thực thi chức năng hay không.
 *
 * @param string $quyen Mã quyền cần kiểm tra (trong BANG_QUYEN)
 * @param array|null $nguoiDung Mảng thông tin cán bộ (mặc định lấy nguoi_dung_hien_tai())
 * @return bool True nếu có quyền, ngược lại False
 */
function co_quyen(string $quyen, ?array $nguoiDung = null): bool {
    if ($nguoiDung === null) {
        $nguoiDung = nguoi_dung_hien_tai();
    }

    if ($nguoiDung === null || empty($nguoiDung['vai_tro'])) {
        return false;
    }

    $vaiTro = (string)$nguoiDung['vai_tro'];

    // Quản trị viên luôn có toàn bộ quyền
    if ($vaiTro === VAI_TRO_QUAN_TRI) {
        return true;
    }

    $cacVaiTroDuocPhep = BANG_QUYEN[$quyen] ?? [];
    return in_array($vaiTro, $cacVaiTroDuocPhep, true);
}

/**
 * Chặn truy cập nếu người dùng không đủ quyền hạn.
 * Nếu không có quyền, hiển thị trang lỗi 403 Forbidden.
 *
 * @param string $quyen Mã quyền cần kiểm tra
 */
function yeu_cau_quyen(string $quyen): void {
    yeu_cau_dang_nhap();
    if (!co_quyen($quyen)) {
        phan_hoi_loi_403('Bạn không có quyền thực hiện chức năng này (yêu cầu quyền: ' . htmlspecialchars($quyen, ENT_QUOTES, 'UTF-8') . ').');
    }
}

