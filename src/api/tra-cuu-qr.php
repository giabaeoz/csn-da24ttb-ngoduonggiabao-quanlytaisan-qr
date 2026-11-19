<?php
/**
 * API JSON Tra cứu đa năng qua Mã QR (QR Universal Lookup API).
 * Nhận diện tự động mã QR thiết bị, mã QR phiếu mượn hoặc mã phòng học.
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/core/khoi-dong.php';
require_once dirname(__DIR__) . '/app/services/tai-san-service.php';
require_once dirname(__DIR__) . '/app/services/muon-tra-service.php';
require_once dirname(__DIR__) . '/app/services/dieu-chuyen-service.php';
require_once dirname(__DIR__) . '/app/services/danh-muc-service.php';

yeu_cau_quyen('xem_danh_sach_tai_san');

$maQr = trim((string)($_GET['ma_qr'] ?? $_GET['ma'] ?? $_GET['code'] ?? $_GET['tu_khoa'] ?? $_GET['q'] ?? ''));
$hanhDong = strtoupper(trim((string)($_GET['hanh_dong'] ?? '')));
if ($maQr === '') {
    phan_hoi_json(['thanh_cong' => false, 'loi' => 'Vui lòng nhập hoặc quét mã QR thiết bị.', 'thong_diep' => 'Vui lòng nhập hoặc quét mã QR thiết bị.'], 400);
}

// 1. Kiểm tra xem có phải mã tài sản / tem QR thiết bị không
$taiSan = tai_san_tim_theo_qr($maQr) ?? tai_san_tim_theo_ma($maQr);
if ($taiSan !== null) {
    $nguoiDungQr = nguoi_dung_hien_tai();
    if ($nguoiDungQr && $nguoiDungQr['vai_tro'] !== VAI_TRO_QUAN_TRI && !empty($nguoiDungQr['phong_truc_id'])) {
        if ((int)$taiSan['phong_truc_id'] !== (int)$nguoiDungQr['phong_truc_id']) {
            $msg = "Thiết bị [{$taiSan['ma_tai_san']}] thuộc {$taiSan['ten_phong_truc']}, không thuộc thẩm quyền quản lý của phòng trực bạn.";
            phan_hoi_json([
                'thanh_cong' => false,
                'loi'        => $msg,
                'thong_diep' => $msg,
                'du_lieu'    => $taiSan,
            ], 403);
        }
    }

    $taiSan['hinh_anh_url'] = tai_san_lay_url_anh($taiSan['hinh_anh'] ?? null);
    $coTheMuon = ($taiSan['trang_thai'] === TRANG_THAI_SAN_SANG && $taiSan['tinh_trang'] === TINH_TRANG_TOT);
    if ($hanhDong === 'MUON' && !$coTheMuon) {
        $msg = "Thiết bị [{$taiSan['ma_tai_san']}] hiện không ở trạng thái Sẵn sàng (Đang mượn hoặc Bảo trì).";
        phan_hoi_json([
            'thanh_cong' => false,
            'loi'        => $msg,
            'thong_diep' => $msg,
            'du_lieu'    => $taiSan,
        ], 422);
    }

    if ($hanhDong === 'DIEU_CHUYEN' && (!in_array($taiSan['trang_thai'], [TRANG_THAI_SAN_SANG, TRANG_THAI_DANG_MUON], true) || $taiSan['tinh_trang'] !== TINH_TRANG_TOT)) {
        $msg = "Thiết bị [{$taiSan['ma_tai_san']}] hiện đang bảo trì hoặc hư hỏng, không thể điều chuyển.";
        phan_hoi_json([
            'thanh_cong' => false,
            'loi'        => $msg,
            'thong_diep' => $msg,
            'du_lieu'    => $taiSan,
        ], 422);
    }

    $phongHocDichId = doc_so_nguyen('phong_hoc_id', 0, $_GET);
    $maPhongDich = trim((string)($_GET['ma_phong'] ?? ''));
    $phongHoc = null;

    if ($phongHocDichId > 0) {
        $phongHoc = phong_hoc_tim_theo_id($phongHocDichId);
    }

    if ($phongHoc === null && $maPhongDich !== '') {
        $maTach = strtoupper(trim(preg_split('/[\(\-]/', $maPhongDich)[0] ?? $maPhongDich));
        $maUpper = strtoupper($maPhongDich);
        $dbPh = db();
        $stmtTimPh = $dbPh->prepare("SELECT * FROM phong_hoc WHERE UPPER(ma_phong) = ? OR UPPER(ten_phong) = ? LIMIT 1");
        $stmtTimPh->bind_param('ss', $maTach, $maUpper);
        $stmtTimPh->execute();
        $phongHoc = $stmtTimPh->get_result()->fetch_assoc() ?: null;
        $stmtTimPh->close();

        if ($phongHoc === null) {
            $phongHoc = [
                'id'             => 0,
                'ma_phong'       => $maTach !== '' ? $maTach : $maUpper,
                'ten_phong'      => $maPhongDich,
                'thiet_bi_phong' => [],
            ];
        }
    }

    $tuongThich = null;
    if ($phongHoc !== null) {
        $tuongThich = tai_san_kiem_tra_tuong_thich($taiSan, $phongHoc);
    }

    phan_hoi_json([
        'thanh_cong'           => true,
        'loai'                 => 'TAI_SAN',
        'du_lieu'              => $taiSan,
        'co_the_muon'          => $coTheMuon,
        'kiem_tra_tuong_thich' => $tuongThich,
    ]);
}

// 2. Kiểm tra xem có phải mã phiếu mượn (PM-...)
$phongTrucId = doc_so_nguyen('phong_truc_id', 0, $_GET);
$phieu = tra_tim_phieu_theo_tu_khoa($maQr, $phongTrucId > 0 ? $phongTrucId : null);
if ($phieu !== null) {
    phan_hoi_json([
        'thanh_cong' => true,
        'loai'       => 'PHIEU_MUON',
        'du_lieu'    => $phieu,
    ]);
}

// 3. Kiểm tra xem có phải mã phiếu điều chuyển (DC-...)
if (str_starts_with($maQr, 'DC-')) {
    $phieuDc = dieu_chuyen_tim_theo_ma($maQr);
    if ($phieuDc !== null) {
        phan_hoi_json([
            'thanh_cong' => true,
            'loai'       => 'DIEU_CHUYEN',
            'du_lieu'    => $phieuDc,
        ]);
    }
}

// 4. Kiểm tra xem có phải mã phòng học (phong_hoc)
$db = db();
$stmtPh = $db->prepare("SELECT * FROM phong_hoc WHERE ma_phong = ? LIMIT 1");
$stmtPh->bind_param('s', $maQr);
$stmtPh->execute();
$phongHoc = $stmtPh->get_result()->fetch_assoc();
$stmtPh->close();

if ($phongHoc !== null) {
    phan_hoi_json([
        'thanh_cong' => true,
        'loai'       => 'PHONG_HOC',
        'du_lieu'    => $phongHoc,
    ]);
}

$msgKhongThay = "Không tìm thấy thiết bị khớp với mã '$maQr'.";
phan_hoi_json([
    'thanh_cong' => false,
    'loi'        => $msgKhongThay,
    'thong_diep' => $msgKhongThay,
], 404);

