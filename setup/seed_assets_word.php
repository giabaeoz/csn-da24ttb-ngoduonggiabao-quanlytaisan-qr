<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../src/includes/database.php';
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$samples = require __DIR__ . '/assets_word_samples.php';
$deskName = 'Phòng trực thiết bị khu 1 (khu B)';
$db->begin_transaction();
try {
    $desk = $db->execute_query('SELECT id FROM phong_truc WHERE ten_phong_truc = ? ORDER BY id LIMIT 1', [$deskName])->fetch_row();
    if (!$desk) {
        $db->execute_query('INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc) VALUES (?, ?)', ['PT_K1B', $deskName]);
        $desk = [$db->insert_id];
    }
    $created = 0;
    $skipped = 0;
    foreach ($samples as [$code, $name, $typeCode, $scope, $rooms]) {
        $existing = $db->execute_query('SELECT ma_tai_san FROM tai_san WHERE ma_tai_san = ? OR ma_qr = ?', [$code, 'TVU:' . $code])->fetch_row();
        if ($existing) {
            if ($existing[0] !== $code) {
                throw new RuntimeException('Mã QR trùng với tài sản khác: ' . $code);
            }
            $skipped++;
            continue;
        }
        $type = $db->execute_query('SELECT id FROM loai_tai_san WHERE ma_loai = ? OR ten_loai = ? ORDER BY id LIMIT 1', [$typeCode, $name])->fetch_row();
        if (!$type) {
            $db->execute_query('INSERT INTO loai_tai_san (ma_loai, ten_loai) VALUES (?, ?)', [$typeCode, $name]);
            $type = [$db->insert_id];
        }
        $roomIds = [];
        foreach ($rooms as $roomCode) {
            $room = $db->execute_query('SELECT id FROM phong_hoc WHERE ma_phong = ?', [$roomCode])->fetch_row();
            if (!$room) {
                $db->execute_query('INSERT INTO phong_hoc (ma_phong, ten_phong) VALUES (?, ?)', [$roomCode, 'Phòng ' . $roomCode]);
                $room = [$db->insert_id];
            }
            $roomIds[] = (int)$room[0];
        }
        // Cột FK cũ chỉ được điền khi có đúng một phòng tương thích.
        $singleRoom = count($roomIds) === 1 ? $roomIds[0] : null;
        $db->execute_query('INSERT INTO tai_san
            (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id,
             phong_hoc_phu_hop_id, pham_vi_tuong_thich, phong_tuong_thich, mo_ta)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            $code, 'TVU:' . $code, $name, $type[0], $desk[0], $singleRoom, $scope,
            json_encode($rooms, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'Nguồn: BẢNG DỮ LIỆU MẪU THIẾT BỊ TẠI ĐẠI HỌC TRÀ VINH.docx. '
            . 'Phòng trực do người dùng xác nhận. Tốt/Sẵn sàng là mặc định dữ liệu demo. '
            . 'Chưa xác định phòng hiện tại và ngày đưa vào sử dụng.',
        ]);
        $created++;
    }
    $db->commit();
    echo "OK: thêm $created tài sản, bỏ qua $skipped mã đã có.\n";
} catch (Throwable $exception) {
    $db->rollback();
    throw $exception;
}
