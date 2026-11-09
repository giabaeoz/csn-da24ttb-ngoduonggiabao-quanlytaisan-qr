<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rbac.php';

$nguoiDung = kiem_tra_quyen(VAI_TRO_NHAN_VIEN);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$deskId = (int)($nguoiDung['phong_truc_id'] ?? 0);
$desk = $deskId ? $db->execute_query('SELECT ten_phong_truc, ma_phong_truc, dang_hoat_dong FROM phong_truc WHERE id = ?', [$deskId])->fetch_assoc() : null;
$activeDesk = $desk && (int)$desk['dang_hoat_dong'] === 1;

$stats = ['total' => 0, 'ready' => 0, 'open' => 0, 'late' => 0];
$categories = [];
$weeklyData = [];
$trackedLoans = [];

if ($activeDesk) {
    $dbTotal = (int)$db->execute_query("SELECT COUNT(*) FROM tai_san WHERE phong_truc_id = ? AND trang_thai <> 'NGUNG_SU_DUNG'", [$deskId])->fetch_row()[0];
    $dbReady = (int)$db->execute_query("SELECT COUNT(*) FROM tai_san t WHERE t.phong_truc_id = ? AND t.trang_thai = 'SAN_SANG' AND t.tinh_trang = 'TOT' AND t.phong_hoc_hien_tai_id IS NULL AND NOT EXISTS (SELECT 1 FROM chi_tiet_muon c WHERE c.tai_san_id = t.id AND c.thoi_gian_tra IS NULL)", [$deskId])->fetch_row()[0];
    $dbOpen = (int)$db->execute_query("SELECT COUNT(*) FROM chi_tiet_muon c JOIN phieu_muon p ON p.id = c.phieu_muon_id WHERE p.phong_truc_id = ? AND c.thoi_gian_tra IS NULL", [$deskId])->fetch_row()[0];
    $dbLate = (int)$db->execute_query("SELECT COUNT(*) FROM chi_tiet_muon c JOIN phieu_muon p ON p.id = c.phieu_muon_id WHERE p.phong_truc_id = ? AND c.thoi_gian_tra IS NULL AND p.han_tra < NOW()", [$deskId])->fetch_row()[0];

    $stats['total'] = $dbTotal;
    $stats['ready'] = $dbReady;
    $stats['open'] = $dbOpen;
    $stats['late'] = $dbLate;

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayName = ['Mon' => 'T2', 'Tue' => 'T3', 'Wed' => 'T4', 'Thu' => 'T5', 'Fri' => 'T6', 'Sat' => 'T7', 'Sun' => 'CN'][date('D', strtotime($date))] ?? date('d/m', strtotime($date));
        $bCount = (int)$db->execute_query("SELECT COUNT(*) FROM phieu_muon WHERE phong_truc_id = ? AND DATE(thoi_gian_muon) = ?", [$deskId, $date])->fetch_row()[0];
        $rCount = (int)$db->execute_query("SELECT COUNT(*) FROM chi_tiet_muon c JOIN phieu_muon p ON p.id = c.phieu_muon_id WHERE p.phong_truc_id = ? AND DATE(c.thoi_gian_tra) = ?", [$deskId, $date])->fetch_row()[0];
        $weeklyData[] = ['label' => $dayName, 'borrow' => $bCount, 'return' => $rCount];
    }

    $trackedLoans = $db->execute_query(
        "SELECT p.id, p.ma_phieu_muon, p.han_tra, p.thoi_gian_muon, n.ho_ten, n.ma_so_truong, h.ma_phong,
                COUNT(c.id) AS so_thiet_bi,
                GROUP_CONCAT(t.ten_tai_san SEPARATOR ', ') AS ten_thiet_bi,
                (p.han_tra < NOW()) AS is_overdue
         FROM phieu_muon p
         JOIN nguoi_muon n ON n.id = p.nguoi_muon_id
         JOIN phong_hoc h ON h.id = p.phong_hoc_id
         JOIN chi_tiet_muon c ON c.phieu_muon_id = p.id AND c.thoi_gian_tra IS NULL
         JOIN tai_san t ON t.id = c.tai_san_id
         WHERE p.phong_truc_id = ?
         GROUP BY p.id
         ORDER BY (p.han_tra < NOW()) DESC, p.han_tra ASC
         LIMIT 5",
        [$deskId]
    )->fetch_all(MYSQLI_ASSOC);
}

if (!$weeklyData) {
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $dayName = ['Mon' => 'T2', 'Tue' => 'T3', 'Wed' => 'T4', 'Thu' => 'T5', 'Fri' => 'T6', 'Sat' => 'T7', 'Sun' => 'CN'][date('D', strtotime($date))] ?? date('d/m', strtotime($date));
        $weeklyData[] = ['label' => $dayName, 'borrow' => 0, 'return' => 0];
    }
}

$title = 'Tổng quan';
$staffPageCss = 'dashboard.css';
require __DIR__ . '/staff-head.php';
?>
<div class="dashboard-page">
    <div class="dashboard-top-bar">
        <div class="dashboard-title-area">
            <h1>Tổng quan</h1>
        </div>
        <div class="dashboard-welcome-area">
            <div class="welcome-text">
                Xin chào “<?= e($nguoiDung['ho_ten']) ?>”
                <small>Hôm nay ngày <?= date('d/m/Y') ?></small>
            </div>
        </div>
    </div>

    <div class="dashboard-stat-grid">
        <div class="dashboard-stat-box">
            <div class="stat-num"><?= sprintf('%02d', $stats['total']) ?></div>
            <div class="stat-lbl">Tổng tài sản</div>
        </div>
        <div class="dashboard-stat-box">
            <div class="stat-num"><?= sprintf('%02d', $stats['ready']) ?></div>
            <div class="stat-lbl">Tài sản có thể mượn</div>
        </div>
        <div class="dashboard-stat-box">
            <div class="stat-num"><?= sprintf('%02d', $stats['open']) ?></div>
            <div class="stat-lbl">Tài sản đang mượn</div>
        </div>
        <div class="dashboard-stat-box">
            <div class="stat-num"><?= sprintf('%02d', $stats['late']) ?></div>
            <div class="stat-lbl">Lượt mượn quá hạn</div>
        </div>
    </div>

    <div class="quick-actions-row">
        <span class="quick-lbl">Thao tác nhanh</span>
        <a href="muon-tra.php?tab=muon" class="btn-quick">Lập phiếu mượn</a>
        <a href="muon-tra.php?tab=tra" class="btn-quick">Nhận trả tài sản</a>
        <a href="tai-san.php#scanner" class="btn-quick">Quét mã QR</a>
    </div>

    <div class="sketch-card">
        <div class="sketch-card-header">
            <div>
                <h2 class="sketch-card-title">Xu hướng mượn – trả trong tuần</h2>
                <p class="sketch-card-sub">Đơn vị: lượt giao dịch</p>
            </div>
            <div class="line-chart-legend">
                <span><svg width="22" height="12"><line x1="0" y1="6" x2="22" y2="6" stroke="#222222" stroke-width="2.5"/></svg> Mượn</span>
                <span><svg width="22" height="12"><line x1="0" y1="6" x2="22" y2="6" stroke="#888888" stroke-width="2" stroke-dasharray="4,3"/></svg> Trả</span>
            </div>
        </div>
        <div style="height: 180px; position: relative;">
            <svg width="100%" height="160" viewBox="0 0 700 160" preserveAspectRatio="none">
                <line x1="30" y1="20" x2="680" y2="20" stroke="#eeeeee" stroke-width="1"/>
                <text x="15" y="24" font-size="11" fill="#888" text-anchor="middle">30</text>

                <line x1="30" y1="60" x2="680" y2="60" stroke="#eeeeee" stroke-width="1"/>
                <text x="15" y="64" font-size="11" fill="#888" text-anchor="middle">20</text>

                <line x1="30" y1="100" x2="680" y2="100" stroke="#eeeeee" stroke-width="1"/>
                <text x="15" y="104" font-size="11" fill="#888" text-anchor="middle">10</text>

                <line x1="30" y1="140" x2="680" y2="140" stroke="#dddddd" stroke-width="1"/>
                <text x="15" y="144" font-size="11" fill="#888" text-anchor="middle">0</text>

                <?php
                $ptsBorrow = [];
                $ptsReturn = [];
                $countW = count($weeklyData);
                $step = 620 / max(1, $countW - 1);
                foreach ($weeklyData as $idx => $w):
                    $x = 45 + $idx * $step;
                    $yB = 140 - ($w['borrow'] / 35 * 120);
                    $yR = 140 - ($w['return'] / 35 * 120);
                    $ptsBorrow[] = "$x,$yB";
                    $ptsReturn[] = "$x,$yR";
                ?>
                    <text x="<?= $x ?>" y="156" font-size="12" fill="#555" text-anchor="middle"><?= e($w['label']) ?></text>
                <?php endforeach; ?>

                <polyline fill="none" stroke="#888888" stroke-width="2" stroke-dasharray="5,4" points="<?= implode(' ', $ptsReturn) ?>"/>
                <polyline fill="none" stroke="#222222" stroke-width="2.5" points="<?= implode(' ', $ptsBorrow) ?>"/>

                <?php foreach ($weeklyData as $idx => $w):
                    $x = 45 + $idx * $step;
                    $yB = 140 - ($w['borrow'] / 35 * 120);
                    $yR = 140 - ($w['return'] / 35 * 120);
                ?>
                    <circle cx="<?= $x ?>" cy="<?= $yR ?>" r="4" fill="#ffffff" stroke="#888888" stroke-width="2"/>
                    <circle cx="<?= $x ?>" cy="<?= $yB ?>" r="4.5" fill="#ffffff" stroke="#222222" stroke-width="2.5"/>
                <?php endforeach; ?>
            </svg>
        </div>
    </div>

    <div class="charts-two-col">
        <div class="sketch-card">
            <div class="sketch-card-header">
                <div>
                    <h2 class="sketch-card-title">Lượt mượn theo nhóm tài sản</h2>
                    <p class="sketch-card-sub">Đơn vị: lượt</p>
                </div>
            </div>
            <div class="vertical-bars-wrap">
                <div class="vertical-bar-item">
                    <span class="bar-val">46</span>
                    <div class="bar-pillar" style="height: 120px;"></div>
                </div>
                <div class="vertical-bar-item">
                    <span class="bar-val">39</span>
                    <div class="bar-pillar" style="height: 102px;"></div>
                </div>
                <div class="vertical-bar-item">
                    <span class="bar-val">21</span>
                    <div class="bar-pillar" style="height: 55px;"></div>
                </div>
                <div class="vertical-bar-item">
                    <span class="bar-val">32</span>
                    <div class="bar-pillar" style="height: 84px;"></div>
                </div>
            </div>
            <div class="vertical-bars-labels">
                <span>Remote</span>
                <span>Micro</span>
                <span>Máy chiếu</span>
                <span>Phụ kiện</span>
            </div>
            <div class="bars-footer-total">Tổng: 138 lượt mượn</div>
        </div>

        <div class="sketch-card">
            <div class="sketch-card-header">
                <div>
                    <h2 class="sketch-card-title">Tình trạng tài sản</h2>
                </div>
            </div>
            <div class="donut-chart-wrap">
                <div class="donut-svg-container">
                    <svg viewBox="0 0 36 36" class="w-100 h-100">
                        <path stroke="#eaeaea" stroke-width="5.5" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path stroke="#555555" stroke-width="5.5" stroke-dasharray="70.8, 100" stroke-dashoffset="25" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path stroke="#888888" stroke-width="5.5" stroke-dasharray="23.3, 100" stroke-dashoffset="-45.8" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                        <path stroke="#cccccc" stroke-width="5.5" stroke-dasharray="5.8, 100" stroke-dashoffset="-69.1" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    </svg>
                    <div class="donut-center-text">
                        <span class="cnt">120</span>
                        <span class="lbl">tài sản</span>
                    </div>
                </div>
                <div class="donut-legend-rows">
                    <div class="donut-legend-row">
                        <div class="left"><span class="swatch" style="background:#555555;"></span> Có thể mượn</div>
                        <div class="right">85 · 70,8%</div>
                    </div>
                    <div class="donut-legend-row">
                        <div class="left"><span class="swatch" style="background:#888888;"></span> Đang mượn</div>
                        <div class="right">28 · 23,3%</div>
                    </div>
                    <div class="donut-legend-row">
                        <div class="left"><span class="swatch" style="background:#cccccc;"></span> Trạng thái khác</div>
                        <div class="right">7 · 5,8%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="sketch-table-card">
        <div class="card-head">
            <h2>Lượt mượn cần theo dõi</h2>
            <a href="muon-tra.php?tab=tra" class="btn-sketch">Xem tất cả</a>
        </div>
        <div class="sketch-table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã phiếu</th>
                        <th>Người mượn</th>
                        <th>Tài sản</th>
                        <th>Hạn trả</th>
                        <th>Trạng thái</th>
                        <th style="text-align: right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($trackedLoans): ?>
                        <?php foreach ($trackedLoans as $loan): ?>
                            <tr>
                                <td><strong><?= e($loan['ma_phieu_muon']) ?></strong></td>
                                <td><?= e($loan['ho_ten']) ?></td>
                                <td><?= e($loan['ten_thiet_bi'] ?? ($loan['so_thiet_bi'] . ' thiết bị')) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime((string)$loan['han_tra'])) ?></td>
                                <td>
                                    <?php if ((int)$loan['is_overdue'] === 1): ?>
                                        <span class="status-sketch-pill" style="color:#b91c1c;background:#fee2e2;border-color:#fca5a5;">Quá hạn</span>
                                    <?php else: ?>
                                        <span class="status-sketch-pill">Đang mượn</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="muon-tra.php?tab=tra&id=<?= (int)$loan['id'] ?>" class="btn-table-action">Nhận trả</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Không có lượt mượn nào cần theo dõi tại phòng trực này.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require __DIR__ . '/staff-foot.php'; ?>
