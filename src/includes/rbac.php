<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';

/**
 * Đảm bảo người dùng đã đăng nhập, nếu chưa chuyển hướng về trang đăng nhập
 */
function yeu_cau_dang_nhap(): array
{
    if (!auth_da_dang_nhap()) {
        $uriHienTai = $_SERVER['REQUEST_URI'] ?? '';
        $redirectUrl = app_full_url('login.php');
        if ($uriHienTai !== '' && $uriHienTai !== '/') {
            $redirectUrl .= '?tiep_tuc=' . urlencode($uriHienTai);
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    $ketQua = db_ket_noi()->execute_query(
        'SELECT ma_nhan_vien, ho_ten, ten_dang_nhap, so_dien_thoai, vai_tro, phong_truc_id
         FROM nhan_vien WHERE id = ? AND dang_hoat_dong = 1',
        [$_SESSION['nguoi_dung']['id']]
    );
    if (!$ketQua) {
        http_response_code(503);
        exit('Tạm thời chưa kiểm tra được quyền truy cập.');
    }
    $hienTai = $ketQua->fetch_assoc();
    if (!$hienTai) {
        auth_dang_xuat();
        header('Location: ' . app_url('login.php'));
        exit;
    }
    $_SESSION['nguoi_dung'] = array_replace($_SESSION['nguoi_dung'], $hienTai);
    return $_SESSION['nguoi_dung'];
}

/**
 * Kiểm tra quyền hạn phía máy chủ đối với danh sách vai trò cho phép
 * Truy cập trái quyền sẽ trả về mã HTTP 403 và dừng xử lý
 * 
 * @param string|array<string> $vaiTroChoPhep
 * @return array Thông tin tài khoản người dùng
 */
function kiem_tra_quyen(string|array $vaiTroChoPhep): array
{
    $nguoiDung = yeu_cau_dang_nhap();
    $vaiTroDanhSach = is_array($vaiTroChoPhep) ? $vaiTroChoPhep : [$vaiTroChoPhep];

    $vaiTroHienTai = (string) ($nguoiDung['vai_tro'] ?? '');

    if (!in_array($vaiTroHienTai, $vaiTroDanhSach, true)) {
        http_response_code(403);

        $thongTinLoi = [
            'vai_tro_hien_tai' => $vaiTroHienTai,
            'vai_tro_yeu_cau'  => $vaiTroDanhSach,
        ];

        require __DIR__ . '/../403.php';
        exit;
    }

    return $nguoiDung;
}

/**
 * Các hàm tiện ích kiểm tra nghiệp vụ theo ma trận phân quyền đề tài:
 * - NHAN_VIEN: vận hành mượn–trả, kiểm kê và điều chuyển trong phạm vi phòng trực
 * - QUAN_LY: quản lý danh mục, tài sản và giám sát nghiệp vụ
 * - QUAN_TRI: quản lý tài khoản, tài sản, người mượn, phòng trực và phòng học; không trực tiếp thực hiện mượn–trả, kiểm kê, điều chuyển
 */

function co_the_van_hanh_muon_tra(): bool
{
    $nguoiDung = auth_nguoi_dung_hien_tai();
    return ($nguoiDung['vai_tro'] ?? '') === VAI_TRO_NHAN_VIEN;
}

function co_the_quan_ly_tai_san_danh_muc(): bool
{
    $nguoiDung = auth_nguoi_dung_hien_tai();
    return ($nguoiDung['vai_tro'] ?? '') === VAI_TRO_QUAN_LY;
}

function co_the_quan_tri_tai_khoan(): bool
{
    $nguoiDung = auth_nguoi_dung_hien_tai();
    return ($nguoiDung['vai_tro'] ?? '') === VAI_TRO_QUAN_TRI;
}
