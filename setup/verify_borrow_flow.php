<?php
declare(strict_types=1);

require __DIR__ . '/../src/includes/database.php';
require __DIR__ . '/../src/includes/borrow-service.php';

if (PHP_SAPI !== 'cli') exit('Chỉ chạy CLI.');

$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$key = strtoupper(bin2hex(random_bytes(4)));
$deskId = 0; $staffId = 0; $borrowerId = 0; $loanId = 0; $directLoanId = 0; $assets = [];

function check_flow(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
    echo "PASS $message\n";
}

try {
    $room = $db->query('SELECT id, ma_phong FROM phong_hoc WHERE dang_hoat_dong = 1 ORDER BY id LIMIT 1')->fetch_assoc();
    $type = $db->query('SELECT id FROM loai_tai_san WHERE dang_hoat_dong = 1 ORDER BY id LIMIT 1')->fetch_assoc();
    check_flow((bool)($room && $type), 'Có danh mục nền');

    // Tạo phòng trực & nhân viên
    $db->execute_query('INSERT INTO phong_truc (ma_phong_truc, ten_phong_truc) VALUES (?, ?)', ['PT' . $key, 'Phòng trực ' . $key]);
    $deskId = (int)$db->insert_id;
    $db->execute_query("INSERT INTO nhan_vien (ma_nhan_vien, ho_ten, ten_dang_nhap, mat_khau_hash, vai_tro, phong_truc_id) VALUES (?, 'NV Test Flow', ?, 'hash', 'NHAN_VIEN', ?)",
        ['NV' . $key, 'user_' . strtolower($key), $deskId]);
    $staffId = (int)$db->insert_id;

    // 1. Đăng ký nhanh người mượn mới
    $newB = loan_borrower_quick_create($db, $staffId, [
        'loai_nguoi_muon' => 'SINH_VIEN',
        'ma_so_truong' => 'SV' . $key,
        'ho_ten' => 'Sinh viên ' . $key,
        'so_dien_thoai' => '0988' . random_int(100000, 999999),
        'lop_don_vi' => 'CNTT-K' . $key,
    ]);
    $borrowerId = (int)$newB['id'];
    check_flow($borrowerId > 0, 'Đăng ký nhanh người mượn mới thành công');

    // Chặn trùng mã số
    try {
        loan_borrower_quick_create($db, $staffId, [
            'loai_nguoi_muon' => 'SINH_VIEN',
            'ma_so_truong' => 'SV' . $key,
            'ho_ten' => 'Trùng mã',
            'so_dien_thoai' => '0911222333',
        ]);
        throw new RuntimeException('Chưa chặn trùng mã số người mượn');
    } catch (DomainException) {
        check_flow(true, 'Chặn tạo trùng mã số người mượn');
    }

    // 2. Tìm kiếm người mượn
    $searchRes = loan_borrower_search($db, 'SV' . $key, $deskId);
    check_flow(count($searchRes) === 1 && $searchRes[0]['ma_so_truong'] === 'SV' . $key, 'Tìm kiếm người mượn theo mã số');

    // 3. Tạo tài sản để mượn
    for ($i = 1; $i <= 2; $i++) {
        $c = "EQ-$key-$i";
        $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, pham_vi_tuong_thich, phong_tuong_thich) VALUES (?, ?, ?, ?, ?, 'LINH_HOAT', '[]')",
            [$c, "TVU:$c", "Thiết bị $key $i", (int)$type['id'], $deskId]);
        $assets[] = (int)$db->insert_id;
    }

    // 4. Tra cứu QR qua loan_lookup
    $roomLookup = loan_lookup($db, $deskId, room_qr_payload($room['ma_phong']), null);
    check_flow($roomLookup['type'] === 'room' && $roomLookup['ma_phong'] === $room['ma_phong'], 'Quét nhận diện đúng QR phòng');

    $assetLookup = loan_lookup($db, $deskId, "TVU:EQ-$key-1", (int)$room['id']);
    check_flow($assetLookup['type'] === 'asset' && $assetLookup['ok'] === true, 'Quét nhận diện đúng tài sản và kiểm tra tính tương thích/sẵn sàng');

    // 5. Tạo phiếu mượn
    $loanId = loan_create($db, $staffId, [
        'nguoi_muon_id' => $borrowerId,
        'phong_hoc_id' => (int)$room['id'],
        'han_tra' => date('Y-m-d\TH:i', time() + 7200),
        'tai_san_id' => $assets,
        'ghi_chu' => 'Giao dịch thử nghiệm',
    ]);
    check_flow($loanId > 0, 'Tạo phiếu mượn thành công');

    // 5b. Tạo thêm 1 tài sản và kiểm tra tạo phiếu mượn nhập thông tin trực tiếp (không truyền nguoi_muon_id)
    $db->execute_query("INSERT INTO tai_san (ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id, pham_vi_tuong_thich, phong_tuong_thich) VALUES (?, ?, 'Thiết bị phụ', ?, ?, 'LINH_HOAT', '[]')",
        ["EQ-$key-3", "TVU:EQ-$key-3", (int)$type['id'], $deskId]);
    $extraAsset = (int)$db->insert_id;
    $assets[] = $extraAsset;

    $directLoanId = loan_create($db, $staffId, [
        'nguoi_muon_id' => '',
        'ma_so_truong' => 'DIRECT-' . $key,
        'ho_ten' => 'Người mượn trực tiếp ' . $key,
        'so_dien_thoai' => '0912345678',
        'lop_don_vi' => 'Lớp trực tiếp',
        'phong_hoc_id' => (int)$room['id'],
        'han_tra' => date('Y-m-d\TH:i', time() + 7200),
        'tai_san_id' => [$extraAsset],
    ]);
    check_flow($directLoanId > 0, 'Nhập trực tiếp mã số & họ tên người mượn không cần chọn trước');
    $createdDirectBorrower = $db->execute_query('SELECT id FROM nguoi_muon WHERE ma_so_truong = ?', ['DIRECT-' . $key])->fetch_row();
    check_flow((bool)$createdDirectBorrower, 'Tự động tạo hồ sơ người mượn khi nhập trực tiếp');

    // 6. Kiểm tra overview người mượn
    $overview = loan_borrower_overview($db, $borrowerId, $deskId);
    check_flow(count($overview['open']) === 2, 'Hiển thị đúng 2 tài sản đang mượn của người mượn');

    // 7. Nhận trả phần 1 (1 thiết bị bình thường)
    $det1 = (int)$db->execute_query('SELECT id FROM chi_tiet_muon WHERE phieu_muon_id = ? AND tai_san_id = ?', [$loanId, $assets[0]])->fetch_row()[0];
    $returnedAt1 = null;
    $rem = loan_return($db, $staffId, [
        'phieu_muon_id' => $loanId,
        'chi_tiet_id' => [$det1],
        'tinh_trang' => [$det1 => 'TOT'],
        'ghi_chu' => [],
    ], $returnedAt1);
    check_flow($rem === 1 && !empty($returnedAt1), 'Nhận trả 1 thiết bị, phiếu còn 1 thiết bị');

    // Kiểm tra nhân viên tiếp nhận và thời gian trả
    $ct1 = $db->execute_query('SELECT nhan_vien_nhan_id, tinh_trang_khi_tra FROM chi_tiet_muon WHERE id = ?', [$det1])->fetch_assoc();
    check_flow((int)$ct1['nhan_vien_nhan_id'] === $staffId && $ct1['tinh_trang_khi_tra'] === 'TOT', 'Lưu đúng nhân viên tiếp nhận và tình trạng trả');

    // 8. Nhận trả phần 2 (1 thiết bị hư hỏng)
    $det2 = (int)$db->execute_query('SELECT id FROM chi_tiet_muon WHERE phieu_muon_id = ? AND tai_san_id = ?', [$loanId, $assets[1]])->fetch_row()[0];
    $returnedAt2 = null;
    $rem2 = loan_return($db, $staffId, [
        'phieu_muon_id' => $loanId,
        'chi_tiet_id' => [$det2],
        'tinh_trang' => [$det2 => 'HU_HONG'],
        'ghi_chu' => [$det2 => 'Bị rơi vỡ nút bấm'],
    ], $returnedAt2);
    check_flow($rem2 === 0, 'Nhận trả thiết bị cuối, hoàn tất đóng phiếu');

    // Trạng thái tài sản 2 phải sang BAO_TRI
    $as2 = $db->execute_query('SELECT trang_thai, tinh_trang FROM tai_san WHERE id = ?', [$assets[1]])->fetch_assoc();
    check_flow($as2['trang_thai'] === 'BAO_TRI' && $as2['tinh_trang'] === 'HU_HONG', 'Tài sản hư hỏng tự chuyển sang trạng thái BAO_TRI');

} finally {
    if ($directLoanId) {
        $db->execute_query('DELETE FROM chi_tiet_muon WHERE phieu_muon_id = ?', [$directLoanId]);
        $db->execute_query('DELETE FROM phieu_muon WHERE id = ?', [$directLoanId]);
        $db->execute_query('DELETE FROM nguoi_muon WHERE ma_so_truong = ?', ['DIRECT-' . $key]);
    }
    if ($loanId) {
        $db->execute_query('DELETE FROM su_co_tai_san WHERE chi_tiet_muon_id IN (SELECT id FROM chi_tiet_muon WHERE phieu_muon_id = ?)', [$loanId]);
        $db->execute_query('DELETE FROM chi_tiet_muon WHERE phieu_muon_id = ?', [$loanId]);
        $db->execute_query('DELETE FROM phieu_muon WHERE id = ?', [$loanId]);
    }
    foreach ($assets as $a) $db->execute_query('DELETE FROM tai_san WHERE id = ?', [$a]);
    if ($borrowerId) $db->execute_query('DELETE FROM nguoi_muon WHERE id = ?', [$borrowerId]);
    if ($staffId) $db->execute_query('DELETE FROM nhan_vien WHERE id = ?', [$staffId]);
    if ($deskId) $db->execute_query('DELETE FROM phong_truc WHERE id = ?', [$deskId]);
    echo "Đã dọn dẹp dữ liệu kiểm thử.\n";
}

