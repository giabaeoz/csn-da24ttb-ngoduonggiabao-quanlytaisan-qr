<?php

/**
 * Layout phần đầu trang dùng chung (Header & Topbar) - Bám sát phác thảo
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

require_once dirname(__DIR__) . '/partials/icon.php';

/**
 * @var string|null $tieuDeTrang
 */

$nguoiDung = nguoi_dung_hien_tai();
$tieuDe = $tieuDeTrang ?? cau_hinh('ung_dung.ten', 'Quản lý tài sản bằng mã QR');
$tepHienTai = basename($_SERVER['PHP_SELF'] ?? '');
$thuMucHienTai = basename(dirname($_SERVER['PHP_SELF'] ?? ''));

// Chữ viết tắt avatar 2 chữ cái chuẩn
$hoTenNguoiDung = (string)($nguoiDung['ho_ten'] ?? 'Nhân viên');
$chuVietTat = viet_tat_hai_chu_cai($hoTenNguoiDung);

$nhanVaiTro = match ($nguoiDung['vai_tro'] ?? '') {
    'QUAN_TRI' => 'Quản trị viên',
    'QUAN_LY'  => 'Cán bộ quản lý',
    default    => 'Nhân viên phòng trực',
};

// Xác định breadcrumb
$tenTrang = match ($thuMucHienTai) {
    'tai-san'     => 'Tài sản',
    'nguoi-muon'  => 'Người mượn',
    'muon-tra'    => 'Mượn - trả',
    'dieu-chuyen' => 'Điều chuyển',
    'kiem-ke'     => 'Kiểm kê',
    'thong-ke'    => 'Tra cứu, thống kê',
    'danh-muc'    => 'Danh mục',
    'tai-khoan'   => 'Tài khoản',
    'su-co'       => 'Sự cố & Bảo trì',
    default       => ($tepHienTai === 'tong-quan.php' ? 'Tổng quan' : $tieuDe),
};

// Kiểm tra thông báo quá hạn cho chuông & lấy thông tin chi tiết cán bộ đang đăng nhập
$coPhieuQuaHan = false;
$soPhieuQuaHan = 0;
$chiTietNguoiDung = $nguoiDung ?? [];
if ($nguoiDung !== null) {
    $dbDau = db();
    $dieuKienPtDau = '';
    if (($nguoiDung['vai_tro'] ?? '') !== 'QUAN_TRI' && !empty($nguoiDung['phong_truc_id'])) {
        $ptIdDau = (int)$nguoiDung['phong_truc_id'];
        $dieuKienPtDau = " AND pm.phong_truc_id = $ptIdDau";
    }
    $resQhDau = $dbDau->query("SELECT COUNT(*) FROM phieu_muon pm WHERE pm.trang_thai = 'DANG_MUON' AND pm.han_tra < NOW() $dieuKienPtDau");
    if ($resQhDau) {
        $soPhieuQuaHan = (int)($resQhDau->fetch_row()[0] ?? 0);
        $coPhieuQuaHan = ($soPhieuQuaHan > 0);
    }

    $nvIdDau = (int)($nguoiDung['id'] ?? 0);
    if ($nvIdDau > 0) {
        $stmtNvDau = $dbDau->prepare("SELECT nv.ma_nhan_vien, nv.ten_dang_nhap, nv.ho_ten, nv.so_dien_thoai, nv.vai_tro, pt.ma_phong_truc, pt.ten_phong_truc FROM nhan_vien nv LEFT JOIN phong_truc pt ON nv.phong_truc_id = pt.id WHERE nv.id = ? LIMIT 1");
        $stmtNvDau->bind_param('i', $nvIdDau);
        $stmtNvDau->execute();
        $rowNvDau = $stmtNvDau->get_result()->fetch_assoc();
        $stmtNvDau->close();
        if ($rowNvDau) {
            $chiTietNguoiDung = array_merge($chiTietNguoiDung, $rowNvDau);
        }
    }
}
$lichSuThongBao = function_exists('lay_lich_su_thong_bao') ? lay_lich_su_thong_bao() : [];
$tongThongBaoChuong = count($lichSuThongBao) + ($coPhieuQuaHan ? 1 : 0);

// Danh sách chức năng hệ thống theo phân quyền để tìm kiếm nhanh trên Topbar
$danhSachChucNangTimKiem = [];
if ($nguoiDung !== null) {
    $tatCaChucNang = [
        ['quyen' => ['xem_tong_quan'], 'ten' => 'Bảng điều khiển Tổng quan', 'nhom' => 'Tổng quan & Thống kê', 'icon' => 'home', 'url' => url('tong-quan.php'), 'tu_khoa' => 'tong quan dashboard trang chu'],
        ['quyen' => ['xem_thong_ke'], 'ten' => 'Tra cứu, thống kê báo cáo', 'nhom' => 'Tổng quan & Thống kê', 'icon' => 'bar-chart-3', 'url' => url('thong-ke/index.php'), 'tu_khoa' => 'tra cuu thong ke bao cao excel bieu do'],
        ['quyen' => ['xem_danh_sach_tai_san'], 'ten' => 'Danh sách tài sản & thiết bị', 'nhom' => 'Quản lý hồ sơ', 'icon' => 'package', 'url' => url('tai-san/index.php'), 'tu_khoa' => 'tai san thiet bi may chieu remote micro qr'],
        ['quyen' => ['quan_ly_tai_san'], 'ten' => 'Thêm thiết bị mới', 'nhom' => 'Quản lý hồ sơ', 'icon' => 'plus', 'url' => url('tai-san/them.php'), 'tu_khoa' => 'them tai san moi khai bao thiet bi'],
        ['quyen' => ['quan_ly_nguoi_muon', 'nghiep_vu_muon_tra'], 'ten' => 'Quản lý hồ sơ người mượn', 'nhom' => 'Quản lý hồ sơ', 'icon' => 'users', 'url' => url('nguoi-muon/index.php'), 'tu_khoa' => 'nguoi muon sinh vien giang vien mssv'],
        ['quyen' => ['quan_ly_nguoi_muon'], 'ten' => 'Thêm người mượn mới', 'nhom' => 'Quản lý hồ sơ', 'icon' => 'plus', 'url' => url('nguoi-muon/them.php'), 'tu_khoa' => 'them nguoi muon dang ky sinh vien'],
        ['quyen' => ['nghiep_vu_muon_tra'], 'ten' => 'Lập phiếu mượn thiết bị (QR)', 'nhom' => 'Nghiệp vụ', 'icon' => 'repeat', 'url' => url('muon-tra/muon.php'), 'tu_khoa' => 'muon thiet bi lap phieu muon quet qr'],
        ['quyen' => ['nghiep_vu_muon_tra'], 'ten' => 'Nhận trả thiết bị (QR)', 'nhom' => 'Nghiệp vụ', 'icon' => 'corner-down-left', 'url' => url('muon-tra/tra.php'), 'tu_khoa' => 'tra thiet bi nhan tra thu hoi quet qr'],
        ['quyen' => ['xem_lich_su_muon_tra', 'nghiep_vu_muon_tra'], 'ten' => 'Lịch sử phiếu mượn - trả', 'nhom' => 'Nghiệp vụ', 'icon' => 'list', 'url' => url('muon-tra/index.php'), 'tu_khoa' => 'lich su muon tra danh sach phieu muon qua han'],
        ['quyen' => ['xem_dieu_chuyen', 'tao_dieu_chuyen'], 'ten' => 'Điều chuyển tài sản', 'nhom' => 'Nghiệp vụ', 'icon' => 'arrow-left-right', 'url' => url('dieu-chuyen/index.php'), 'tu_khoa' => 'dieu chuyen ban giao phong hoc'],
        ['quyen' => ['tao_dieu_chuyen'], 'ten' => 'Lập phiếu điều chuyển mới', 'nhom' => 'Nghiệp vụ', 'icon' => 'plus', 'url' => url('dieu-chuyen/them.php'), 'tu_khoa' => 'lap phieu dieu chuyen moi'],
        ['quyen' => ['xem_kiem_ke', 'thuc_hien_kiem_ke'], 'ten' => 'Kiểm kê tài sản phòng trực', 'nhom' => 'Nghiệp vụ', 'icon' => 'clipboard-check', 'url' => url('kiem-ke/index.php'), 'tu_khoa' => 'kiem ke tai san doi chieu so sach'],
        ['quyen' => ['mo_dot_kiem_ke'], 'ten' => 'Mở đợt kiểm kê mới', 'nhom' => 'Nghiệp vụ', 'icon' => 'plus', 'url' => url('kiem-ke/tao.php'), 'tu_khoa' => 'mo dot kiem ke tao kiem ke cuoi buoi'],
        ['quyen' => ['xem_su_co', 'ghi_nhan_su_co'], 'ten' => 'Quản lý sự cố & Bảo trì', 'nhom' => 'Nghiệp vụ', 'icon' => 'wrench', 'url' => url('su-co/index.php'), 'tu_khoa' => 'su co bao tri hu hong mat sai vi tri'],
        ['quyen' => ['ghi_nhan_su_co'], 'ten' => 'Báo cáo sự cố thiết bị mới', 'nhom' => 'Nghiệp vụ', 'icon' => 'alert-triangle', 'url' => url('su-co/bao-cao.php'), 'tu_khoa' => 'bao cao su co ghi nhan hu hong'],
        ['quyen' => ['quan_ly_danh_muc'], 'ten' => 'Danh mục Phòng trực', 'nhom' => 'Quản trị hệ thống', 'icon' => 'folder-tree', 'url' => url('danh-muc/phong-truc.php'), 'tu_khoa' => 'danh muc phong truc ban truc'],
        ['quyen' => ['quan_ly_danh_muc'], 'ten' => 'Danh mục Phòng học', 'nhom' => 'Quản trị hệ thống', 'icon' => 'home', 'url' => url('danh-muc/phong-hoc.php'), 'tu_khoa' => 'danh muc phong hoc giang duong'],
        ['quyen' => ['quan_ly_danh_muc'], 'ten' => 'Danh mục Loại tài sản', 'nhom' => 'Quản trị hệ thống', 'icon' => 'package', 'url' => url('danh-muc/loai-tai-san.php'), 'tu_khoa' => 'danh muc loai tai san chung loai'],
        ['quyen' => ['quan_ly_tai_khoan'], 'ten' => 'Quản lý tài khoản cán bộ', 'nhom' => 'Quản trị hệ thống', 'icon' => 'user-cog', 'url' => url('tai-khoan/index.php'), 'tu_khoa' => 'tai khoan can bo nhan vien phan quyen'],
        ['quyen' => ['quan_ly_tai_khoan'], 'ten' => 'Thêm tài khoản cán bộ mới', 'nhom' => 'Quản trị hệ thống', 'icon' => 'plus', 'url' => url('tai-khoan/them.php'), 'tu_khoa' => 'them tai khoan tao nhan vien moi'],
    ];
    foreach ($tatCaChucNang as $cn) {
        foreach ($cn['quyen'] as $q) {
            if (co_quyen($q)) {
                $danhSachChucNangTimKiem[] = $cn;
                break;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tieuDe) ?> - <?= e(cau_hinh('ung_dung.ten', 'TÀI SẢN QR')) ?></title>
    <link rel="icon" type="image/jpeg" href="<?= url('assets/images/logo-he-thong.jpg') ?>">
    <link rel="stylesheet" href="<?= url('assets/vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/app.css?v=' . @filemtime(dirname(__DIR__, 3) . '/assets/css/app.css')) ?>">
</head>

<body>

    <div class="khung-ung-dung">
        <?php require __DIR__ . '/menu.php'; ?>

        <div class="vung-noi-dung">
            <header class="topbar-app">
                <nav class="breadcrumb-app d-flex align-items-center gap-2" aria-label="Đường dẫn">
                    <a href="<?= url('tong-quan.php') ?>" class="d-inline-flex align-items-center text-decoration-none text-muted" aria-label="Trang chủ">
                        <?= icon('home', 'bieu-tuong-sm me-1') ?>
                        <span>Trang chủ</span>
                    </a>
                    <span class="text-muted">/</span>
                    <strong class="text-dark fw-bold"><?= e($tenTrang) ?></strong>
                </nav>

                <?php if ($nguoiDung !== null): ?>
                    <div class="d-flex align-items-center gap-3">
                        <!-- Thanh tìm kiếm nhanh chức năng hệ thống (nằm bên trái biểu tượng thông báo) -->
                        <div class="hop-tim-kiem-chuc-nang position-relative d-none d-md-block" id="hopTimKiemChucNang">
                            <div class="o-tim-kiem o-tim-kiem-topbar">
                                <?= icon('search', 'bieu-tuong-sm text-primary') ?>
                                <input type="text"
                                    id="inputTimChucNang"
                                    class="o-nhap o-nhap-tim-chuc-nang"
                                    placeholder="Tìm chức năng..."
                                    autocomplete="off"
                                    aria-label="Tìm kiếm chức năng hệ thống">
                            </div>
                            <div class="bang-goi-y-chuc-nang shadow rounded-3 d-none" id="bangGoiYChucNang">
                                <div class="px-3 py-2 border-bottom bg-light rounded-top d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark chu-nho-11 text-uppercase d-flex align-items-center gap-1">
                                        <?= icon('search', 'bieu-tuong-sm text-primary') ?>
                                        <span>Chức năng hệ thống</span>
                                    </span>
                                    <span class="text-muted chu-nho-11">Chọn để mở nhanh</span>
                                </div>
                                <div class="danh-sach-goi-y-chuc-nang" id="danhSachGoiYChucNang">
                                    <?php foreach ($danhSachChucNangTimKiem as $cnItem): ?>
                                        <a href="<?= e($cnItem['url']) ?>"
                                            data-khong-modal="1"
                                            data-search="<?= e(mb_strtolower($cnItem['ten'] . ' ' . $cnItem['nhom'] . ' ' . $cnItem['tu_khoa'], 'UTF-8')) ?>"
                                            class="muc-goi-y-chuc-nang text-decoration-none d-flex align-items-center gap-3 px-3 py-2">
                                            <div class="hop-icon-dau flex-shrink-0">
                                                <?= icon($cnItem['icon'], 'bieu-tuong-sm') ?>
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <div class="fw-semibold text-dark small text-truncate"><?= e($cnItem['ten']) ?></div>
                                                <div class="text-muted chu-nho-11 text-truncate"><?= e($cnItem['nhom']) ?></div>
                                            </div>
                                            <?= icon('arrow-right', 'bieu-tuong-sm text-muted opacity-50 flex-shrink-0') ?>
                                        </a>
                                    <?php endforeach; ?>
                                    <div class="text-center py-3 text-muted small d-none" id="khongTimThayChucNang">
                                        Không tìm thấy chức năng phù hợp.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Nút thông báo nhỏ quản lý tất cả thông báo gần đây -->
                        <div class="dropdown">
                            <button class="nut-chuong-tb position-relative text-dark d-inline-flex align-items-center justify-content-center border-0 bg-transparent"
                                type="button"
                                id="nutChuongThongBao"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                                aria-label="Thông báo gần đây"
                                title="Thông báo gần đây">
                                <?= icon('bell', 'bieu-tuong') ?>
                                <span class="cham-do-thong-bao <?= $tongThongBaoChuong > 0 ? '' : 'd-none' ?>" id="chamDoThongBao"></span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 mt-2 hop-dropdown-thong-bao" aria-labelledby="nutChuongThongBao">
                                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light rounded-top">
                                    <div class="d-flex align-items-center gap-2 fw-bold text-dark small">
                                        <?= icon('bell', 'bieu-tuong-sm text-primary') ?>
                                        <span>Thông báo gần đây</span>
                                    </div>
                                    <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 chu-nho-11 text-muted" onclick="xoaLichSuThongBao()">
                                        <?= icon('trash-2', 'bieu-tuong-sm') ?> Xóa tất cả
                                    </button>
                                </div>
                                <div class="danh-sach-tb-gan-day" id="danhSachThongBaoGanDay">
                                    <?php if ($coPhieuQuaHan): ?>
                                        <a href="<?= url('muon-tra/index.php?trang_thai=QUA_HAN') ?>" class="muc-tb-gan-day muc-tb-do text-decoration-none">
                                            <div class="icon-tb-tron bg-danger text-white">
                                                <?= icon('alert-triangle', 'bieu-tuong-sm') ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold text-danger small">Cảnh báo quá hạn</div>
                                                <div class="text-dark small">Hiện có <?= $soPhieuQuaHan ?> phiếu mượn quá hạn cần thu hồi.</div>
                                            </div>
                                        </a>
                                    <?php endif; ?>
                                    <?php if (empty($lichSuThongBao) && !$coPhieuQuaHan): ?>
                                        <div class="text-center py-4 text-muted small" id="tbTrong">
                                            <?= icon('bell', 'bieu-tuong mb-1 text-secondary opacity-50') ?>
                                            <div>Chưa có thông báo nào gần đây.</div>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($lichSuThongBao as $tbItem):
                                            $laTcItem = (($tbItem['loai'] ?? '') === 'thanh_cong');
                                        ?>
                                            <div class="muc-tb-gan-day <?= $laTcItem ? 'muc-tb-xanh' : 'muc-tb-do' ?>">
                                                <div class="icon-tb-tron <?= $laTcItem ? 'bg-success' : 'bg-danger' ?> text-white">
                                                    <?= icon($laTcItem ? 'check-circle-2' : 'alert-circle', 'bieu-tuong-sm') ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="text-dark small fw-medium"><?= e(strip_tags((string)($tbItem['noi_dung'] ?? ''))) ?></div>
                                                    <div class="text-muted chu-nho-11 mt-1"><?= e((string)($tbItem['thoi_gian'] ?? '')) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Nút tài khoản người dùng hiển thị chi tiết thông tin cán bộ & nút Đăng xuất -->
                        <div class="dropdown">
                            <button class="btn p-0 border-0 d-flex align-items-center gap-2 text-start bg-transparent shadow-none" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Mở thông tin người dùng">
                                <div class="avatar-tron"><?= e($chuVietTat) ?></div>
                                <div class="user-info d-none d-sm-block">
                                    <div class="user-label"><?= e($nhanVaiTro) ?></div>
                                    <div class="user-role"><?= e($hoTenNguoiDung) ?></div>
                                </div>
                                <?= icon('chevron-down', 'bieu-tuong-sm text-muted ms-1') ?>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow border-0 p-0 mt-2 hop-dropdown-nguoi-dung" aria-labelledby="userMenuDropdown">
                                <div class="p-3 border-bottom bg-light rounded-top d-flex align-items-center gap-3">
                                    <div class="avatar-tron avatar-tron-lg"><?= e($chuVietTat) ?></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= e($hoTenNguoiDung) ?></div>
                                        <span class="huy-hieu-tt huy-hieu-tt-xanh mt-1"><?= e($nhanVaiTro) ?></span>
                                    </div>
                                </div>
                                <div class="p-3 d-flex flex-column gap-2 small">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span class="text-muted d-inline-flex align-items-center gap-2">
                                            <?= icon('user-cog', 'bieu-tuong-sm text-primary') ?>
                                            <span>Mã cán bộ:</span>
                                        </span>
                                        <span class="fw-semibold text-dark font-monospace"><?= e((string)($chiTietNguoiDung['ma_nhan_vien'] ?? '---')) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span class="text-muted d-inline-flex align-items-center gap-2">
                                            <?= icon('lock', 'bieu-tuong-sm text-primary') ?>
                                            <span>Tên đăng nhập:</span>
                                        </span>
                                        <span class="fw-semibold text-dark"><?= e((string)($chiTietNguoiDung['ten_dang_nhap'] ?? '---')) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span class="text-muted d-inline-flex align-items-center gap-2">
                                            <?= icon('home', 'bieu-tuong-sm text-primary') ?>
                                            <span>Phòng trực:</span>
                                        </span>
                                        <span class="fw-semibold text-dark text-end"><?= e((string)($chiTietNguoiDung['ten_phong_truc'] ?? 'Toàn hệ thống')) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <span class="text-muted d-inline-flex align-items-center gap-2">
                                            <?= icon('users', 'bieu-tuong-sm text-primary') ?>
                                            <span>Số điện thoại:</span>
                                        </span>
                                        <span class="fw-semibold text-dark"><?= e((string)($chiTietNguoiDung['so_dien_thoai'] ?: 'Chưa cập nhật')) ?></span>
                                    </div>
                                </div>
                                <div class="p-2 border-top bg-light rounded-bottom">
                                    <form action="<?= url('logout.php') ?>" method="POST" class="m-0">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-nguy-hiem btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
                                            <?= icon('log-out', 'bieu-tuong-sm') ?>
                                            <span>Đăng xuất khỏi hệ thống</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </header>

            <main class="noi-dung-chinh">
                <?php require dirname(__DIR__) . '/partials/thong-bao.php'; ?>