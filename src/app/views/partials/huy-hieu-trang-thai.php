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

/**
 * @var string $ma
 * @var string|null $nhan
 */

$ma = $ma ?? '';
$nhan = $nhan ?? null;

$cauHinhHuyHieu = match ($ma) {
    'SAN_SANG' => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Có thể mượn', 'icon' => 'check-circle-2'],
    'TOT'      => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Bình thường', 'icon' => 'check-circle-2'],
    'DA_TRA'   => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Đã trả đủ', 'icon' => 'check-circle-2'],
    'HOAN_TAT' => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Đã hoàn tất', 'icon' => 'check-circle-2'],
    'TIM_THAY', 'CO_MAT' => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Có mặt', 'icon' => 'check-circle-2'],
    'TUONG_THICH' => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Tương thích', 'icon' => 'check-circle-2'],
    'TRONG_HAN'   => ['kieu' => 'huy-hieu-thanh-cong', 'mac_dinh' => 'Trong hạn', 'icon' => ''],
    
    'DANG_MUON'    => ['kieu' => 'huy-hieu-chinh', 'mac_dinh' => 'Đang mượn', 'icon' => 'repeat'],
    'DANG_KIEM_KE' => ['kieu' => 'huy-hieu-chinh', 'mac_dinh' => 'Đang kiểm kê', 'icon' => 'clock'],
    'DANG_XU_LY'   => ['kieu' => 'huy-hieu-chinh', 'mac_dinh' => 'Đang xử lý', 'icon' => 'clock'],
    'TRA_MOT_PHAN' => ['kieu' => 'huy-hieu-chinh', 'mac_dinh' => 'Trả một phần', 'icon' => 'clock'],

    'BAO_TRI'          => ['kieu' => 'huy-hieu-canh-bao', 'mac_dinh' => 'Bảo trì', 'icon' => 'wrench'],
    'SAI_VI_TRI'       => ['kieu' => 'huy-hieu-canh-bao', 'mac_dinh' => 'Sai vị trí', 'icon' => 'alert-triangle'],
    'DEN_HAN_HOM_NAY'  => ['kieu' => 'huy-hieu-canh-bao', 'mac_dinh' => 'Đến hạn hôm nay', 'icon' => 'clock'],

    'HU_HONG'          => ['kieu' => 'huy-hieu-nguy-hiem', 'mac_dinh' => 'Hỏng', 'icon' => 'alert-triangle'],
    'MAT'              => ['kieu' => 'huy-hieu-nguy-hiem', 'mac_dinh' => 'Mất', 'icon' => 'alert-triangle'],
    'QUA_HAN'          => ['kieu' => 'huy-hieu-nguy-hiem', 'mac_dinh' => 'Quá hạn', 'icon' => 'alert-triangle'],
    'CHUA_TIM_THAY'    => ['kieu' => 'huy-hieu-nguy-hiem', 'mac_dinh' => 'Chưa tìm thấy', 'icon' => 'alert-triangle'],
    'KHONG_TUONG_THICH'=> ['kieu' => 'huy-hieu-nguy-hiem', 'mac_dinh' => 'Không tương thích', 'icon' => 'alert-triangle'],

    default            => ['kieu' => 'huy-hieu-xam', 'mac_dinh' => $ma, 'icon' => ''],
};

$chuHienThi = $nhan ?? $cauHinhHuyHieu['mac_dinh'];
$tenIcon = $cauHinhHuyHieu['icon'] ?? '';
?>
<span class="huy-hieu <?= $cauHinhHuyHieu['kieu'] ?>"><?php if ($tenIcon !== '' && function_exists('icon')): ?><?= icon($tenIcon, 'bieu-tuong-xs') ?><?php endif; ?><span><?= e($chuHienThi) ?></span></span>

