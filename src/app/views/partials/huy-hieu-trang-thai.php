<?php
/**
 * Thành phần Huy hiệu trạng thái dùng chung (Badges).
 * Nhận biến $ma (mã trạng thái) và biến tùy chọn $nhan (chữ hiển thị).
 */

declare(strict_types=1);

if (!defined('APP_KHOI_DONG')) {
    http_response_code(403);
    exit;
}

$ma = $ma ?? '';
$nhan = $nhan ?? null;

$cauHinhHuyHieu = match ($ma) {
    'SAN_SANG' => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Có thể mượn'],
    'TOT'      => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Bình thường'],
    'DA_TRA'   => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Đã trả đủ'],
    'HOAN_TAT' => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Đã hoàn tất'],
    'TIM_THAY', 'CO_MAT' => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Có mặt'],
    'TUONG_THICH' => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Tương thích'],
    'TRONG_HAN'   => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Trong hạn'],
    
    'DANG_MUON'    => ['kieu' => 'huy-hieu-chinh', 'mac_dinh' => 'Đang mượn'],
    'DANG_KIEM_KE' => ['kieu' => 'huy-hieu-chinh', 'mac_dinh' => 'Đang kiểm kê'],
    'DANG_XU_LY'   => ['kieu' => 'huy-hieu-chinh', 'mac_dinh' => 'Đang xử lý'],
    'TRA_MOT_PHAN' => ['kieu' => 'huy-hieu-chinh', 'mac_dinh' => 'Trả một phần'],

    'BAO_TRI'          => ['kieu' => 'huy-hieu-canh-bao', 'mac_dinh' => 'Bảo trì'],
    'SAI_VI_TRI'       => ['kieu' => 'huy-hieu-canh-bao', 'mac_dinh' => 'Sai vị trí'],
    'DEN_HAN_HOM_NAY'  => ['kieu' => 'huy-hieu-canh-bao', 'mac_dinh' => 'Đến hạn hôm nay'],

    'HU_HONG'          => ['kieu' => 'huy-hieu-nguy-hiem', 'mac_dinh' => 'Hỏng'],
    'MAT'              => ['kieu' => 'huy-hieu-nguy-hiem', 'mac_dinh' => 'Mất'],
    'QUA_HAN'          => ['kieu' => 'huy-hieu-nguy-hiem', 'mac_dinh' => 'Quá hạn'],
    'CHUA_TIM_THAY'    => ['kieu' => 'huy-hieu-nguy-hiem', 'mac_dinh' => 'Chưa tìm thấy'],
    'KHONG_TUONG_THICH'=> ['kieu' => 'huy-hieu-nguy-hiem', 'mac_dinh' => 'Không tương thích'],

    default            => ['kieu' => 'huy-hieu-xam', 'mac_dinh' => $ma],
};

$chuHienThi = $nhan ?? $cauHinhHuyHieu['mac_dinh'];
?>
<span class="huy-hieu <?= $cauHinhHuyHieu['kieu'] ?>"><?= e($chuHienThi) ?></span>
