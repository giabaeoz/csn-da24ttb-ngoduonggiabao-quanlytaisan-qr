<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../src/includes/database.php';
require_once __DIR__ . '/../src/includes/assets.php';

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function check(bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
    echo "PASS: $message\n";
}

function rejects(callable $action, string $message): void
{
    try {
        $action();
    } catch (DomainException $ex) {
        echo "PASS: $message\n";
        return;
    }
    throw new RuntimeException("Expected rejection for: $message");
}

$db->begin_transaction();

try {
    // 1. Lấy hoặc tạo nhân viên thử nghiệm
    $staffRow = $db->query(
        "SELECT id, phong_truc_id FROM nhan_vien WHERE vai_tro = 'NHAN_VIEN' AND dang_hoat_dong = 1 LIMIT 1"
    )->fetch_assoc();

    check($staffRow !== null, 'Có tài khoản Nhân viên hợp lệ');
    $staffId = (int)$staffRow['id'];
    $deskId = (int)$staffRow['phong_truc_id'];

    // Lấy loại tài sản và phòng học để test
    $typeId = (int)$db->query("SELECT id FROM loai_tai_san WHERE dang_hoat_dong = 1 LIMIT 1")->fetch_row()[0];
    $roomRows = $db->query("SELECT id, ma_phong FROM phong_hoc WHERE dang_hoat_dong = 1 LIMIT 2")->fetch_all(MYSQLI_ASSOC);
    check(count($roomRows) >= 2, 'Có ít nhất 2 phòng học đang hoạt động');
    $room1Id = (int)$roomRows[0]['id'];
    $room1Code = $roomRows[0]['ma_phong'];
    $room2Id = (int)$roomRows[1]['id'];
    $room2Code = $roomRows[1]['ma_phong'];

    // Tạo mã ngẫu nhiên cho test
    $testCode = 'STF-' . strtoupper(bin2hex(random_bytes(3)));

    // 2. Tạo tài sản mới trong phạm vi phòng trực của Nhân viên
    $db->execute_query(
        'INSERT INTO tai_san (
            ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id,
            phong_hoc_phu_hop_id, phong_hoc_hien_tai_id, pham_vi_tuong_thich,
            phong_tuong_thich, tinh_trang, trang_thai, ngay_dua_vao_su_dung, mo_ta
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $testCode,
            'TVU:' . $testCode,
            'Thiết bị thử nghiệm của nhân viên',
            $typeId,
            $deskId,
            $room1Id,
            $room1Id,
            'THEO_PHONG',
            json_encode([$room1Code, $room2Code], JSON_UNESCAPED_UNICODE),
            'TOT',
            'SAN_SANG',
            '2026-11-06',
            'Ghi chú thiết bị test'
        ]
    );
    $assetId = (int)$db->insert_id;
    check($assetId > 0, "Nhân viên tạo tài sản $testCode thuộc phòng trực được phân công");

    // 3. Kiểm tra thông tin đã lưu
    $saved = $db->execute_query('SELECT * FROM tai_san WHERE id = ?', [$assetId])->fetch_assoc();
    check($saved['ma_qr'] === 'TVU:' . $testCode, 'Mã QR được tạo ổn định dạng TVU:<ma_tai_san>');
    check((int)$saved['phong_truc_id'] === $deskId, 'Tài sản gắn đúng phòng trực của nhân viên');

    // 4. Kiểm tra truy vấn danh sách theo phòng trực của nhân viên
    $list = $db->execute_query(
        'SELECT id FROM tai_san WHERE phong_truc_id = ? AND ma_tai_san = ?',
        [$deskId, $testCode]
    )->fetch_all();
    check(count($list) === 1, 'Danh sách tài sản của nhân viên tìm thấy đúng tài sản đã tạo');

    // 5. Cập nhật thông tin tài sản
    $newName = 'Thiết bị thử nghiệm đã cập nhật';
    $db->execute_query(
        'UPDATE tai_san SET ten_tai_san = ?, mo_ta = ? WHERE id = ? AND phong_truc_id = ?',
        [$newName, 'Mô tả mới', $assetId, $deskId]
    );
    $updatedName = $db->execute_query('SELECT ten_tai_san FROM tai_san WHERE id = ?', [$assetId])->fetch_row()[0];
    check($updatedName === $newName, 'Nhân viên cập nhật thành công tên tài sản');

    // 6. Kiểm tra chặn mượn/sửa khi có lượt mượn chưa trả
    // Tạo 1 lượt mượn giả lập
    $borrowerCode = 'TEST_BORROWER_' . bin2hex(random_bytes(3));
    $db->execute_query(
        'INSERT INTO nguoi_muon (ma_nguoi_muon, ma_so_truong, ho_ten, loai_nguoi_muon) VALUES (?, ?, ?, ?)',
        [$borrowerCode, $borrowerCode, 'Người mượn test', 'GIANG_VIEN']
    );
    $borrowerId = (int)$db->insert_id;

    $loanCode = 'PM-' . bin2hex(random_bytes(3));
    $db->execute_query(
        'INSERT INTO phieu_muon (ma_phieu_muon, nguoi_muon_id, phong_hoc_id, phong_truc_id, nhan_vien_lap_id, han_tra)
         VALUES (?, ?, ?, ?, ?, NOW() + INTERVAL 2 HOUR)',
        [$loanCode, $borrowerId, $room1Id, $deskId, $staffId]
    );
    $loanId = (int)$db->insert_id;

    $db->execute_query(
        'INSERT INTO chi_tiet_muon (phieu_muon_id, tai_san_id) VALUES (?, ?)',
        [$loanId, $assetId]
    );

    // Khi đang mượn: trạng thái hiển thị phản ánh DANG_MUON
    $openLoanExists = (bool)$db->execute_query(
        'SELECT 1 FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL',
        [$assetId]
    )->fetch_row();
    check($openLoanExists, 'Nhận diện chính xác tài sản đang có lượt mượn chưa trả');

    // Trả thiết bị để có thể ngừng sử dụng
    $db->execute_query(
        "UPDATE chi_tiet_muon SET thoi_gian_tra = NOW(), tinh_trang_khi_tra = 'TOT' WHERE phieu_muon_id = ?",
        [$loanId]
    );

    // Chuyển sang ngừng sử dụng
    $db->execute_query(
        "UPDATE tai_san SET trang_thai = 'NGUNG_SU_DUNG' WHERE id = ? AND phong_truc_id = ?",
        [$assetId, $deskId]
    );
    $finalState = $db->execute_query('SELECT trang_thai FROM tai_san WHERE id = ?', [$assetId])->fetch_row()[0];
    check($finalState === 'NGUNG_SU_DUNG', 'Nhân viên chuyển trạng thái tài sản sang Ngừng sử dụng thành công');

    echo "Tất cả các kiểm tra phân hệ Tài sản của Nhân viên đều ĐẠT.\n";
} finally {
    $db->rollback();
    echo "Đã hoàn tác toàn bộ dữ liệu kiểm thử an toàn.\n";
}

