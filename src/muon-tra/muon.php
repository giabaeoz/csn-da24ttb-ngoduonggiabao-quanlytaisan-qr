<?php
/**
 * Bộ điều khiển Lập phiếu Mượn tài sản bằng mã QR (Borrow Asset Controller).
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/muon-tra-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';
require_once dirname(__DIR__) . '/app/services/nguoi-muon-service.php';

// Yêu cầu quyền trực tiếp thực hiện nghiệp vụ mượn - trả (QUAN_TRI hoặc NHAN_VIEN)
yeu_cau_quyen('nghiep_vu_muon_tra');

$nguoiDung = nguoi_dung_hien_tai();

// Xác định phòng trực đang thao tác
$phongTrucId = (int)($nguoiDung['phong_truc_id'] ?? 0);
if ($phongTrucId <= 0 && $nguoiDung['vai_tro'] === VAI_TRO_QUAN_TRI) {
    // Nếu là Quản trị viên chưa gán phòng trực thì cho phép chọn hoặc mặc định bàn 1 (D6)
    $phongTrucId = doc_so_nguyen('phong_truc_id', 1, $_REQUEST);
}

if ($phongTrucId <= 0) {
    dat_thong_bao('loi', 'Tài khoản của bạn chưa được phân công phụ trách phòng trực nào.');
    chuyen_huong('tong-quan.php');
}

$thongBaoLoi = '';

// Kiểm tra xem phòng trực có đang bị khóa bởi đợt kiểm kê không
$dangBiKhoaKiemKe = muon_kiem_tra_khoa_kiem_ke($phongTrucId);

// Xử lý gửi biểu mẫu tạo phiếu mượn
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_kiem_tra()) {
        $thongBaoLoi = 'Phiên làm việc bảo mật đã hết hạn. Vui lòng thử lại.';
    } elseif ($dangBiKhoaKiemKe) {
        $thongBaoLoi = 'Phòng trực hiện đang có đợt kiểm kê tài sản. Không thể lập phiếu mượn lúc này.';
    } else {
        $nguoiMuonId = doc_so_nguyen('nguoi_muon_id', 0, $_POST);
        $phongHocId = doc_so_nguyen('phong_hoc_id', 0, $_POST);
        $phongHocNhap = trim((string)($_POST['phong_hoc_nhap'] ?? ''));
        if ($phongHocNhap !== '') {
            $phongHocId = phong_hoc_tim_hoac_tao_nhanh($phongHocNhap);
        }
        $hanTra = (string)($_POST['han_tra'] ?? '');
        $mucDich = (string)($_POST['muc_dich_su_dung'] ?? '');
        $dsTsRaw = $_POST['tai_san_ids'] ?? [];

        // Tự động nhận diện hoặc tạo mới người mượn trực tiếp từ thông tin khai báo trên trang mượn
        $tuKhoaNmNhap = trim((string)($_POST['tu_khoa_nguoi_muon'] ?? ''));
        $maSoNhap = trim((string)($_POST['moi_ma_so_truong'] ?? ''));
        $hoTenNhap = trim((string)($_POST['moi_ho_ten'] ?? ''));
        $loaiNhap = trim((string)($_POST['moi_loai_nguoi_muon'] ?? 'SINH_VIEN'));
        $lopNhap = trim((string)($_POST['moi_lop_don_vi'] ?? ''));
        $sdtNhap = trim((string)($_POST['moi_so_dien_thoai'] ?? ''));

        if ($maSoNhap === '' && $hoTenNhap === '' && $tuKhoaNmNhap !== '') {
            if (preg_match('/^[A-Za-z0-9_-]+$/', $tuKhoaNmNhap)) {
                $maSoNhap = $tuKhoaNmNhap;
            } else {
                $hoTenNhap = $tuKhoaNmNhap;
            }
        }

        $dangKyNhanh = null;
        if ($maSoNhap !== '' || $hoTenNhap !== '') {
            $resLuuNm = nguoi_muon_luu_khi_muon([
                'ma_so_truong'    => $maSoNhap,
                'ho_ten'          => $hoTenNhap,
                'loai_nguoi_muon' => $loaiNhap,
                'lop_don_vi'      => $lopNhap,
                'so_dien_thoai'   => $sdtNhap,
            ]);
            if ($resLuuNm['thanh_cong'] && $resLuuNm['id'] > 0) {
                $nguoiMuonId = (int)$resLuuNm['id'];
            } else {
                $dangKyNhanh = [
                    'ma_so_truong'    => $maSoNhap,
                    'ho_ten'          => $hoTenNhap,
                    'loai_nguoi_muon' => in_array($loaiNhap, ['SINH_VIEN', 'GIANG_VIEN'], true) ? $loaiNhap : 'SINH_VIEN',
                    'lop_don_vi'      => $lopNhap,
                    'so_dien_thoai'   => $sdtNhap,
                ];
            }
        }

        $ketQua = muon_tao_phieu([
            'nguoi_muon_id'       => $nguoiMuonId,
            'dang_ky_nhanh'       => $dangKyNhanh,
            'phong_hoc_id'        => $phongHocId,
            'danh_sach_tai_san_id'=> is_array($dsTsRaw) ? $dsTsRaw : explode(',', (string)$dsTsRaw),
            'han_tra'             => $hanTra,
            'muc_dich_su_dung'    => $mucDich,
        ], (int)$nguoiDung['id'], $phongTrucId);

        if ($ketQua['thanh_cong']) {
            dat_thong_bao('thanh_cong', "Lập phiếu mượn <strong>{$ketQua['ma_phieu']}</strong> thành công!");
            chuyen_huong('muon-tra/chi-tiet.php?id=' . $ketQua['id']);
        } else {
            $thongBaoLoi = $ketQua['loi'];
        }
    }
}

// Lấy danh sách phòng học đang hoạt động
$danhSachPhongHoc = phong_hoc_danh_sach(true);

// Lấy danh sách phòng trực (cho quản trị viên chọn)
$danhSachPhongTruc = phong_truc_danh_sach(true);
$phongTrucHienTai = phong_truc_tim_theo_id($phongTrucId);

// Người mượn định sẵn (nếu truyền qua URL ?ma_so=...)
$nguoiMuonMacDinh = null;
$maSoSan = trim((string)($_GET['ma_so'] ?? ''));
if ($maSoSan !== '') {
    $nguoiMuonMacDinh = nguoi_muon_tim_theo_ma_so_truong($maSoSan);
}

$tieuDeTrang = 'Lập phiếu Mượn thiết bị bằng mã QR';
require dirname(__DIR__) . '/app/views/muon-tra/muon.php';
