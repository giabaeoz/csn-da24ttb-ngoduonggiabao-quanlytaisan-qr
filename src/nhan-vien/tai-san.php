<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/assets.php';

$nguoiDung = kiem_tra_quyen(VAI_TRO_NHAN_VIEN);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$deskId = (int)($nguoiDung['phong_truc_id'] ?? 0);
$desk = $deskId ? $db->execute_query(
    'SELECT id, ten_phong_truc, ma_phong_truc, dang_hoat_dong FROM phong_truc WHERE id = ?',
    [$deskId]
)->fetch_assoc() : null;
$canOperate = $desk && (int)$desk['dang_hoat_dong'] === 1;

$error = '';
$isPost = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';

if ($isPost) {
    if (!csrf_kiem_tra(is_string($_POST['csrf_token'] ?? null) ? $_POST['csrf_token'] : null)) {
        http_response_code(403);
        exit('Biểu mẫu đã hết hạn hoặc không hợp lệ. Vui lòng tải lại trang.');
    }

    if (!$canOperate) {
        http_response_code(403);
        exit('Tài khoản chưa được gán phòng trực hoạt động để quản lý tài sản.');
    }

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $id = $id ? (int)$id : null;

    try {
        $db->begin_transaction();

        $inInventory = (bool)$db->execute_query(
            "SELECT id FROM dot_kiem_ke WHERE phong_truc_id = ? AND trang_thai = 'DANG_KIEM_KE' LIMIT 1",
            [$deskId]
        )->fetch_row();

        if ($inInventory) {
            throw new DomainException('Phòng trực đang trong đợt kiểm kê; tạm thời không thể thêm hoặc sửa thông tin tài sản.');
        }

        if ($action === 'create') {
            $code = trim((string)($_POST['ma_tai_san'] ?? ''));
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,29}$/', $code)) {
                throw new DomainException('Mã tài sản chỉ gồm chữ Latin, số, dấu chấm, gạch ngang và gạch dưới (tối đa 30 ký tự).');
            }

            $dup = $db->execute_query('SELECT 1 FROM tai_san WHERE ma_tai_san = ? OR ma_qr = ? LIMIT 1', [$code, 'TVU:' . $code])->fetch_row();
            if ($dup) {
                throw new DomainException("Mã tài sản \"$code\" hoặc mã QR đã tồn tại trong hệ thống.");
            }

            $name = trim((string)($_POST['ten_tai_san'] ?? ''));
            if ($name === '' || mb_strlen($name, 'UTF-8') > 150) {
                throw new DomainException('Tên tài sản không được để trống và tối đa 150 ký tự.');
            }

            if (($_POST['loai_tai_san_id'] ?? '') === '__NEW__' || !empty($_POST['ten_loai_moi'])) {
                $newTypeName = trim((string)($_POST['ten_loai_moi'] ?? ''));
                if ($newTypeName === '' || mb_strlen($newTypeName, 'UTF-8') > 100) {
                    throw new DomainException('Tên phân loại mới không được để trống và tối đa 100 ký tự.');
                }
                $newTypeCode = trim((string)($_POST['ma_loai_moi'] ?? ''));
                if ($newTypeCode === '') {
                    $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', preg_replace('/[áàảãạăắặằẳẵâấầẩẫậéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵđ]/iu', 'x', $newTypeName)));
                    if (strlen($base) < 2) {
                        $base = 'PL';
                    }
                    $newTypeCode = substr($base, 0, 10) . '_' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
                } elseif (!preg_match('/^[A-Za-z0-9_-]{1,30}$/', $newTypeCode)) {
                    throw new DomainException('Mã phân loại mới chỉ gồm chữ cái, số, gạch ngang, gạch dưới (tối đa 30 ký tự).');
                }
                $exist = $db->execute_query('SELECT id FROM loai_tai_san WHERE ma_loai = ?', [$newTypeCode])->fetch_assoc();
                if ($exist) {
                    $typeId = (int)$exist['id'];
                } else {
                    $db->execute_query(
                        'INSERT INTO loai_tai_san (ma_loai, ten_loai, mo_ta, dang_hoat_dong, tao_luc, cap_nhat_luc) VALUES (?, ?, ?, 1, NOW(), NOW())',
                        [$newTypeCode, $newTypeName, 'Thêm nhanh từ biểu mẫu tài sản']
                    );
                    $typeId = (int)$db->insert_id;
                }
            } else {
                $typeId = filter_var($_POST['loai_tai_san_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if (!$typeId || !$db->execute_query('SELECT 1 FROM loai_tai_san WHERE id = ? AND dang_hoat_dong = 1', [(int)$typeId])->fetch_row()) {
                    throw new DomainException('Loại tài sản không hợp lệ hoặc đã ngừng hoạt động.');
                }
            }

            $scope = is_string($_POST['pham_vi_tuong_thich'] ?? null) && in_array($_POST['pham_vi_tuong_thich'], ['THEO_PHONG', 'LINH_HOAT'], true)
                ? $_POST['pham_vi_tuong_thich']
                : 'LINH_HOAT';

            $currentRoomId = filter_var($_POST['phong_hoc_id'] ?? ($_POST['phong_hoc_hien_tai_id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $currentRoomId = $currentRoomId ? (int)$currentRoomId : null;
            $singleRoomId = $currentRoomId;
            $roomCodes = [];

            if ($currentRoomId) {
                $curRow = $db->execute_query('SELECT ma_phong FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1', [$currentRoomId])->fetch_row();
                if (!$curRow) {
                    throw new DomainException('Phòng học được chọn không tồn tại hoặc đã ngừng hoạt động.');
                }
                $roomCodes[] = $curRow[0];
            }

            $condition = (string)($_POST['tinh_trang'] ?? 'TOT');
            if (!isset(ASSET_CONDITIONS[$condition])) {
                throw new DomainException('Tình trạng tài sản không hợp lệ.');
            }

            $state = (string)($_POST['trang_thai'] ?? 'SAN_SANG');
            if (!in_array($state, ['SAN_SANG', 'BAO_TRI', 'NGUNG_SU_DUNG'], true)) {
                throw new DomainException('Trạng thái tài sản không hợp lệ.');
            }
            if ($state === 'SAN_SANG' && $condition !== 'TOT') {
                throw new DomainException('Chỉ tài sản có tình trạng Tốt mới được ở trạng thái Sẵn sàng.');
            }
            if ($condition === 'MAT' && $state !== 'NGUNG_SU_DUNG') {
                throw new DomainException('Tài sản mất phải ở trạng thái Ngừng sử dụng.');
            }

            $date = trim((string)($_POST['ngay_dua_vao_su_dung'] ?? ''));
            if ($date !== '') {
                $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                    throw new DomainException('Ngày đưa vào sử dụng không đúng định dạng YYYY-MM-DD.');
                }
            }

            $note = trim((string)($_POST['mo_ta'] ?? ''));
            if (mb_strlen($note, 'UTF-8') > 5000) {
                throw new DomainException('Mô tả ghi chú không vượt quá 5000 ký tự.');
            }

            $db->execute_query(
                'INSERT INTO tai_san (
                    ma_tai_san, ma_qr, ten_tai_san, loai_tai_san_id, phong_truc_id,
                    phong_hoc_phu_hop_id, phong_hoc_hien_tai_id, pham_vi_tuong_thich,
                    phong_tuong_thich, tinh_trang, trang_thai, ngay_dua_vao_su_dung, mo_ta
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, \'\'), NULLIF(?, \'\'))',
                [
                    $code,
                    'TVU:' . $code,
                    $name,
                    $typeId,
                    $deskId,
                    $singleRoomId,
                    $currentRoomId,
                    $scope,
                    json_encode($roomCodes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    $condition,
                    $state,
                    $date,
                    $note
                ]
            );

            $newId = (int)$db->insert_id;
            $db->commit();
            set_flash('success', "Đã thêm tài sản mới \"$code\" thành công.");
            header('Location: tai-san.php', true, 303);
            exit;

        } elseif ($action === 'edit') {
            if (!$id) {
                throw new DomainException('Chưa chọn tài sản cần chỉnh sửa.');
            }

            $old = $db->execute_query(
                'SELECT * FROM tai_san WHERE id = ? AND phong_truc_id = ? FOR UPDATE',
                [$id, $deskId]
            )->fetch_assoc();

            if (!$old) {
                throw new DomainException('Không tìm thấy tài sản trong phạm vi phòng trực của bạn.');
            }

            $openLoan = (bool)$db->execute_query(
                'SELECT 1 FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL LIMIT 1',
                [$id]
            )->fetch_row();

            $name = trim((string)($_POST['ten_tai_san'] ?? ''));
            if ($name === '' || mb_strlen($name, 'UTF-8') > 150) {
                throw new DomainException('Tên tài sản không được để trống và tối đa 150 ký tự.');
            }

            $note = trim((string)($_POST['mo_ta'] ?? ''));
            if (mb_strlen($note, 'UTF-8') > 5000) {
                throw new DomainException('Mô tả ghi chú không vượt quá 5000 ký tự.');
            }

            if ($openLoan) {
                $db->execute_query(
                    'UPDATE tai_san SET ten_tai_san = ?, mo_ta = NULLIF(?, \'\') WHERE id = ?',
                    [$name, $note, $id]
                );
            } else {
                if (($_POST['loai_tai_san_id'] ?? '') === '__NEW__' || !empty($_POST['ten_loai_moi'])) {
                    $newTypeName = trim((string)($_POST['ten_loai_moi'] ?? ''));
                    if ($newTypeName === '' || mb_strlen($newTypeName, 'UTF-8') > 100) {
                        throw new DomainException('Tên phân loại mới không được để trống và tối đa 100 ký tự.');
                    }
                    $newTypeCode = trim((string)($_POST['ma_loai_moi'] ?? ''));
                    if ($newTypeCode === '') {
                        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', preg_replace('/[áàảãạăắặằẳẵâấầẩẫậéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵđ]/iu', 'x', $newTypeName)));
                        if (strlen($base) < 2) {
                            $base = 'PL';
                        }
                        $newTypeCode = substr($base, 0, 10) . '_' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
                    } elseif (!preg_match('/^[A-Za-z0-9_-]{1,30}$/', $newTypeCode)) {
                        throw new DomainException('Mã phân loại mới chỉ gồm chữ cái, số, gạch ngang, gạch dưới (tối đa 30 ký tự).');
                    }
                    $exist = $db->execute_query('SELECT id FROM loai_tai_san WHERE ma_loai = ?', [$newTypeCode])->fetch_assoc();
                    if ($exist) {
                        $typeId = (int)$exist['id'];
                    } else {
                        $db->execute_query(
                            'INSERT INTO loai_tai_san (ma_loai, ten_loai, mo_ta, dang_hoat_dong, tao_luc, cap_nhat_luc) VALUES (?, ?, ?, 1, NOW(), NOW())',
                            [$newTypeCode, $newTypeName, 'Thêm nhanh từ biểu mẫu tài sản']
                        );
                        $typeId = (int)$db->insert_id;
                    }
                } else {
                    $typeId = filter_var($_POST['loai_tai_san_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if (!$typeId || !$db->execute_query('SELECT 1 FROM loai_tai_san WHERE id = ? AND dang_hoat_dong = 1', [(int)$typeId])->fetch_row()) {
                        throw new DomainException('Loại tài sản không hợp lệ hoặc đã ngừng hoạt động.');
                    }
                }

                $scope = is_string($_POST['pham_vi_tuong_thich'] ?? null) && in_array($_POST['pham_vi_tuong_thich'], ['THEO_PHONG', 'LINH_HOAT'], true)
                    ? $_POST['pham_vi_tuong_thich']
                    : 'LINH_HOAT';

                $currentRoomId = filter_var($_POST['phong_hoc_id'] ?? ($_POST['phong_hoc_hien_tai_id'] ?? null), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $currentRoomId = $currentRoomId ? (int)$currentRoomId : null;
                $singleRoomId = $currentRoomId;
                $roomCodes = [];

                if ($currentRoomId) {
                    $curRow = $db->execute_query('SELECT ma_phong FROM phong_hoc WHERE id = ? AND dang_hoat_dong = 1', [$currentRoomId])->fetch_row();
                    if (!$curRow) {
                        throw new DomainException('Phòng học được chọn không tồn tại hoặc đã ngừng hoạt động.');
                    }
                    $roomCodes[] = $curRow[0];
                }

                $condition = (string)($_POST['tinh_trang'] ?? 'TOT');
                if (!isset(ASSET_CONDITIONS[$condition])) {
                    throw new DomainException('Tình trạng tài sản không hợp lệ.');
                }

                $state = (string)($_POST['trang_thai'] ?? 'SAN_SANG');
                if (!in_array($state, ['SAN_SANG', 'BAO_TRI', 'NGUNG_SU_DUNG'], true)) {
                    throw new DomainException('Trạng thái tài sản không hợp lệ.');
                }
                if ($state === 'SAN_SANG' && $condition !== 'TOT') {
                    throw new DomainException('Chỉ tài sản có tình trạng Tốt mới được ở trạng thái Sẵn sàng.');
                }
                if ($condition === 'MAT' && $state !== 'NGUNG_SU_DUNG') {
                    throw new DomainException('Tài sản mất phải ở trạng thái Ngừng sử dụng.');
                }

                $date = trim((string)($_POST['ngay_dua_vao_su_dung'] ?? ''));
                if ($date !== '') {
                    $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
                    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                        throw new DomainException('Ngày đưa vào sử dụng không đúng định dạng YYYY-MM-DD.');
                    }
                }

                $db->execute_query(
                    'UPDATE tai_san
                     SET ten_tai_san = ?, loai_tai_san_id = ?, phong_hoc_phu_hop_id = ?,
                         phong_hoc_hien_tai_id = ?, pham_vi_tuong_thich = ?, phong_tuong_thich = ?,
                         tinh_trang = ?, trang_thai = ?, ngay_dua_vao_su_dung = NULLIF(?, \'\'),
                         mo_ta = NULLIF(?, \'\')
                     WHERE id = ? AND phong_truc_id = ?',
                    [
                        $name,
                        $typeId,
                        $singleRoomId,
                        $currentRoomId,
                        $scope,
                        json_encode($roomCodes, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        $condition,
                        $state,
                        $date,
                        $note,
                        $id,
                        $deskId
                    ]
                );
            }

            $db->commit();
            set_flash('success', 'Đã lưu thay đổi thông tin tài sản.');
            header('Location: tai-san.php', true, 303);
            exit;

        } elseif ($action === 'stop') {
            if (!$id) {
                throw new DomainException('Chưa chọn tài sản cần ngừng sử dụng.');
            }

            $old = $db->execute_query(
                'SELECT id FROM tai_san WHERE id = ? AND phong_truc_id = ? FOR UPDATE',
                [$id, $deskId]
            )->fetch_assoc();

            if (!$old) {
                throw new DomainException('Không tìm thấy tài sản trong phạm vi phòng trực của bạn.');
            }

            $openLoan = (bool)$db->execute_query(
                'SELECT 1 FROM chi_tiet_muon WHERE tai_san_id = ? AND thoi_gian_tra IS NULL LIMIT 1',
                [$id]
            )->fetch_row();

            if ($openLoan) {
                throw new DomainException('Tài sản đang có lượt mượn chưa trả, không thể ngừng sử dụng.');
            }

            $db->execute_query("UPDATE tai_san SET trang_thai = 'NGUNG_SU_DUNG' WHERE id = ?", [$id]);
            $db->commit();
            set_flash('success', 'Tài sản đã được chuyển sang trạng thái ngừng sử dụng.');
            header('Location: tai-san.php?tab=stopped', true, 303);
            exit;
        } else {
            throw new DomainException('Thao tác không được hỗ trợ.');
        }

    } catch (DomainException $ex) {
        $db->rollback();
        $error = $ex->getMessage();
        http_response_code(422);
    } catch (mysqli_sql_exception $ex) {
        $db->rollback();
        error_log('[STAFF_ASSET_SAVE] ' . $ex->getMessage());
        $error = (int)$ex->getCode() === 1062
            ? 'Mã tài sản hoặc mã QR đã tồn tại.'
            : 'Chưa thể lưu tài sản do lỗi hệ thống. Vui lòng thử lại.';
        http_response_code(422);
    }
}


$tab = is_string($_GET['tab'] ?? null) && in_array($_GET['tab'], ['all', 'ready', 'borrowed', 'maintenance', 'stopped'], true)
    ? $_GET['tab']
    : 'all';

$filters = [
    'q' => is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 100) : '',
    'type' => filter_var($_GET['type'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null,
    'status' => is_string($_GET['status'] ?? null) ? $_GET['status'] : '',
    'location' => is_string($_GET['location'] ?? null) ? $_GET['location'] : '',
];

$requestedPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$page = $requestedPage ? (int)$requestedPage : 1;

$stats = ['total' => 0, 'ready' => 0, 'borrowed' => 0, 'other' => 0];
$rows = [];
$total = 0;
$pages = 1;
$types = [];
$rooms = [];
$selectedAsset = null;
$assetHistory = null;
$assetToEdit = null;
$isNew = isset($_GET['new']) && $canOperate;
$isEdit = isset($_GET['edit']) && $canOperate;

if ($canOperate) {
    $types = $db->execute_query('SELECT id, ten_loai, dang_hoat_dong FROM loai_tai_san ORDER BY ten_loai')->fetch_all(MYSQLI_ASSOC);
    $rooms = $db->execute_query('SELECT id, ma_phong, ten_phong, dang_hoat_dong FROM phong_hoc ORDER BY ma_phong')->fetch_all(MYSQLI_ASSOC);

    $stats['total'] = (int)$db->execute_query(
        "SELECT COUNT(*) FROM tai_san WHERE phong_truc_id = ?",
        [$deskId]
    )->fetch_row()[0];

    $stats['ready'] = (int)$db->execute_query(
        "SELECT COUNT(*) FROM tai_san t
         WHERE t.phong_truc_id = ? AND t.trang_thai = 'SAN_SANG' AND t.tinh_trang = 'TOT'
           AND NOT EXISTS (SELECT 1 FROM chi_tiet_muon c WHERE c.tai_san_id = t.id AND c.thoi_gian_tra IS NULL)",
        [$deskId]
    )->fetch_row()[0];

    $stats['borrowed'] = (int)$db->execute_query(
        'SELECT COUNT(DISTINCT c.tai_san_id) FROM chi_tiet_muon c
         JOIN tai_san t ON t.id = c.tai_san_id
         WHERE t.phong_truc_id = ? AND c.thoi_gian_tra IS NULL',
        [$deskId]
    )->fetch_row()[0];

    $stats['other'] = max(0, $stats['total'] - $stats['ready'] - $stats['borrowed']);

    $where = ['t.phong_truc_id = ?'];
    $params = [$deskId];

    match ($filters['status']) {
        'CO_THE_MUON' => (function() use (&$where) {
            $where[] = "t.trang_thai = 'SAN_SANG' AND t.tinh_trang = 'TOT'
                        AND NOT EXISTS (SELECT 1 FROM chi_tiet_muon c WHERE c.tai_san_id = t.id AND c.thoi_gian_tra IS NULL)";
        })(),
        'DANG_MUON' => (function() use (&$where) {
            $where[] = "EXISTS (SELECT 1 FROM chi_tiet_muon c WHERE c.tai_san_id = t.id AND c.thoi_gian_tra IS NULL)";
        })(),
        'BAO_TRI' => (function() use (&$where) {
            $where[] = "t.trang_thai = 'BAO_TRI'";
        })(),
        'HONG' => (function() use (&$where) {
            $where[] = "(t.tinh_trang = 'HU_HONG' OR t.tinh_trang = 'MAT')";
        })(),
        'NGUNG_SU_DUNG' => (function() use (&$where) {
            $where[] = "t.trang_thai = 'NGUNG_SU_DUNG'";
        })(),
        default => null,
    };

    if ($filters['q'] !== '') {
        $pattern = '%' . strtr($filters['q'], ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
        $where[] = "(t.ma_tai_san LIKE ? ESCAPE '!' OR t.ten_tai_san LIKE ? ESCAPE '!' OR t.ma_qr LIKE ? ESCAPE '!')";
        array_push($params, $pattern, $pattern, $pattern);
    }

    if ($filters['type'] !== null) {
        $where[] = "t.loai_tai_san_id = ?";
        $params[] = $filters['type'];
    }

    if ($filters['location'] === 'PHONG_TRUC') {
        $where[] = "t.phong_hoc_hien_tai_id IS NULL";
    } elseif (ctype_digit($filters['location'])) {
        $where[] = "t.phong_hoc_hien_tai_id = ?";
        $params[] = (int)$filters['location'];
    }

    $whereSql = ' WHERE ' . implode(' AND ', $where);

    $total = (int)$db->execute_query(
        "SELECT COUNT(*) FROM tai_san t $whereSql",
        $params
    )->fetch_row()[0];

    $pages = max(1, (int)ceil($total / 20));
    $page = min(max(1, $page), $pages);
    $offset = ($page - 1) * 20;

    $sqlList = "SELECT t.*, l.ten_loai, p.ten_phong_truc,
                       h.ma_phong AS phong_hien_tai, f.ma_phong AS phong_cu,
                       CASE WHEN EXISTS (
                           SELECT 1 FROM chi_tiet_muon c WHERE c.tai_san_id = t.id AND c.thoi_gian_tra IS NULL
                       ) THEN 'DANG_MUON' ELSE t.trang_thai END AS trang_thai_hien_thi
                FROM tai_san t
                JOIN loai_tai_san l ON l.id = t.loai_tai_san_id
                JOIN phong_truc p ON p.id = t.phong_truc_id
                LEFT JOIN phong_hoc h ON h.id = t.phong_hoc_hien_tai_id
                LEFT JOIN phong_hoc f ON f.id = t.phong_hoc_phu_hop_id
                $whereSql
                ORDER BY t.id DESC
                LIMIT 20 OFFSET ?";

    $rows = $db->execute_query($sqlList, [...$params, $offset])->fetch_all(MYSQLI_ASSOC);

    $selectedId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($selectedId) {
        $assetRow = $db->execute_query(
            "SELECT t.*, l.ten_loai, p.ten_phong_truc,
                    h.ma_phong AS phong_hien_tai, f.ma_phong AS phong_cu,
                    CASE WHEN EXISTS (
                        SELECT 1 FROM chi_tiet_muon c WHERE c.tai_san_id = t.id AND c.thoi_gian_tra IS NULL
                    ) THEN 'DANG_MUON' ELSE t.trang_thai END AS trang_thai_hien_thi
             FROM tai_san t
             JOIN loai_tai_san l ON l.id = t.loai_tai_san_id
             JOIN phong_truc p ON p.id = t.phong_truc_id
             LEFT JOIN phong_hoc h ON h.id = t.phong_hoc_hien_tai_id
             LEFT JOIN phong_hoc f ON f.id = t.phong_hoc_phu_hop_id
             WHERE t.id = ? AND t.phong_truc_id = ?",
            [(int)$selectedId, $deskId]
        )->fetch_assoc();

        if ($assetRow) {
            $selectedAsset = $assetRow;
            if ($isEdit) {
                $assetToEdit = $assetRow;
            } else {
                $assetHistory = asset_history($db, (int)$selectedId);
            }
        }
    }
}

$formData = $_POST;
if (!$isPost && $assetToEdit) {
    $formData = [
        'ma_tai_san' => $assetToEdit['ma_tai_san'],
        'ten_tai_san' => $assetToEdit['ten_tai_san'],
        'loai_tai_san_id' => $assetToEdit['loai_tai_san_id'],
        'pham_vi_tuong_thich' => $assetToEdit['pham_vi_tuong_thich'] ?? 'THEO_PHONG',
        'phong_hoc_hien_tai_id' => $assetToEdit['phong_hoc_hien_tai_id'],
        'tinh_trang' => $assetToEdit['tinh_trang'],
        'trang_thai' => $assetToEdit['trang_thai'],
        'ngay_dua_vao_su_dung' => $assetToEdit['ngay_dua_vao_su_dung'] ?? '',
        'mo_ta' => $assetToEdit['mo_ta'] ?? '',
        'rooms' => json_decode((string)($assetToEdit['phong_tuong_thich'] ?? '[]'), true),
    ];
}

$activeFilters = [
    'q' => $filters['q'],
    'type' => $filters['type'],
    'status' => $filters['status'],
    'location' => $filters['location'],
];
$link = static fn(array $changes): string => 'tai-san.php?' . http_build_query(array_filter(
    array_replace($activeFilters, ['page' => $page], $changes),
    static fn($v): bool => $v !== null && $v !== ''
));

$title = 'Tài sản';
$staffPageCss = 'assets.css';
require __DIR__ . '/staff-head.php';
?>

<?php if (!$canOperate): ?>
    <div class="alert alert-warning my-3">
        Tài khoản cần được phân công một phòng trực đang hoạt động để xem và quản lý tài sản. Vui lòng liên hệ Quản trị viên.
    </div>
<?php else: ?>
    <?php if ($error !== ''): ?>
        <div class="alert alert-danger mb-3" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php require __DIR__ . '/views/asset-list.php'; ?>

    <?php if ($selectedAsset && !$isEdit && !$isNew): ?>
        <?php if (isset($_GET['qr'])): ?>
            <?php require __DIR__ . '/views/asset-qr-modal.php'; ?>
        <?php else: ?>
            <?php require __DIR__ . '/views/asset-detail-modal.php'; ?>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($isNew || ($isEdit && $assetToEdit)): ?>
        <?php require __DIR__ . '/views/asset-form-modal.php'; ?>
    <?php endif; ?>
<?php endif; ?>

<script src="../vendor/qrcode.js"></script>
<script src="../vendor/jsQR.js"></script>
<script src="../shared/qr-scanner.js"></script>
<script src="asset-ui.js"></script>

<?php require __DIR__ . '/staff-foot.php'; ?>

