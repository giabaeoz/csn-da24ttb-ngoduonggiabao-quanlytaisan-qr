<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/rbac.php';
require_once __DIR__.'/../includes/assets.php';
require_once __DIR__.'/../includes/manager-service.php';
$nguoiDung = kiem_tra_quyen(VAI_TRO_QUAN_TRI);
$db = db_ket_noi();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$post=$_SERVER['REQUEST_METHOD']==='POST';
$error='';
$source=$post?$_POST:$_GET;
try {
    $id=manager_id($source['id']??null,true);
} catch (DomainException $ex) { http_response_code(400); exit(e($ex->getMessage())); }
$action=$post && is_string($_POST['action']??null)?$_POST['action']:'';
if (!$post && isset($_GET['edit']) && !$id) { http_response_code(400); exit('Chưa chọn tài sản cần sửa.'); }
if ($post) {
    if (!csrf_kiem_tra(is_string($_POST['csrf_token']??null)?$_POST['csrf_token']:null)) {
        http_response_code(403); exit('Phiên biểu mẫu đã hết hạn.');
    }
    try {
        $db->begin_transaction();
        manager_check_actor($db,(int)$nguoiDung['id'],[VAI_TRO_QUAN_TRI]);
        $saved=manager_asset_save($db,$action,$id,$_POST);
        $db->commit();
        set_flash('success',match($action) {
            'stop'=>'Tài sản đã ngừng sử dụng.',
            'delete'=>'Đã xóa tài sản chưa có lịch sử nghiệp vụ.',
            default=>'Đã lưu tài sản và mã QR.',
        });
        header('Location: tai-san.php'.($action==='delete'?'':'?id='.$saved.'#asset-profile'),true,303);
        exit;
    } catch (DomainException | mysqli_sql_exception $ex) {
        $db->rollback();
        $error=$ex instanceof DomainException?$ex->getMessage():match((int)$ex->getCode()) {
            1062=>'Mã tài sản hoặc mã QR đã tồn tại.',
            1451=>'Tài sản còn được dùng trong nghiệp vụ.',
            default=>'Chưa lưu được tài sản. Vui lòng kiểm tra lại dữ liệu.',
        };
        http_response_code(422);
    }
}
try {
    $filters=[
        'q'=>asset_text($_GET,'q'), 'type'=>asset_filter_id($_GET,'type'),
        'desk'=>asset_filter_id($_GET,'desk'), 'location'=>asset_filter_id($_GET,'location'),
        'state'=>asset_text($_GET,'state',30),'condition'=>asset_text($_GET,'condition',30),
        'scope'=>asset_text($_GET,'scope',30),'compatible'=>asset_text($_GET,'compatible',30),
    ];
    foreach (['state'=>ASSET_STATES,'condition'=>ASSET_CONDITIONS,'scope'=>['THEO_PHONG'=>1,'LINH_HOAT'=>1]] as $key=>$allowed) {
        if ($filters[$key]!=='' && !isset($allowed[$filters[$key]])) { throw new InvalidArgumentException('Bộ lọc không hợp lệ.'); }
    }
    if (!$id && ($_GET['scan'] ?? '') === '1' && $filters['q'] !== '') {
        $exact = $db->execute_query('SELECT id FROM tai_san WHERE ma_qr = ? OR ma_tai_san = ? LIMIT 1', [$filters['q'], $filters['q']])->fetch_row();
        if ($exact) $id = (int)$exact[0];
    }
    [$rows,$total,$page,$pages]=asset_list($db,$filters,(int)filter_var($_GET['page']??1,FILTER_VALIDATE_INT));
    $stats=$db->query("SELECT COUNT(*) AS total,
        COALESCE(SUM(trang_thai_hien_thi='SAN_SANG' AND tinh_trang='TOT'),0) AS ready,
        COALESCE(SUM(trang_thai_hien_thi='DANG_MUON'),0) AS borrowed
        FROM (".ASSET_SELECT.") a")->fetch_assoc();
    $types=$db->query('SELECT id,ten_loai,dang_hoat_dong FROM loai_tai_san ORDER BY ten_loai')->fetch_all(MYSQLI_ASSOC);
    $desks=$db->query('SELECT id,ten_phong_truc,dang_hoat_dong FROM phong_truc ORDER BY ten_phong_truc')->fetch_all(MYSQLI_ASSOC);
    $rooms=$db->query('SELECT id,ma_phong,dang_hoat_dong FROM phong_hoc ORDER BY ma_phong')->fetch_all(MYSQLI_ASSOC);
    $asset=$id?$db->execute_query(ASSET_SELECT.' WHERE t.id=?',[$id])->fetch_assoc():null;
    if ($id && !$asset) { http_response_code(404); exit('Không tìm thấy tài sản.'); }
    $history=$asset?asset_history($db,$id):[];
} catch (InvalidArgumentException $ex) { http_response_code(400); exit(e($ex->getMessage())); }
catch (mysqli_sql_exception $ex) { error_log('[MANAGER ASSETS] '.$ex->getMessage()); http_response_code(503); exit('Chưa tải được dữ liệu tài sản.'); }
$showForm=isset($_GET['new']) || isset($_GET['edit']) || ($post && in_array($action,['create','edit'],true));
$formData=$post && in_array($action,['create','edit'],true)?$_POST:[];
if (!$formData && $asset) {
    $formData=$asset;
    $formData['rooms']=[];
    $codes=$asset['pham_vi_tuong_thich']==='THEO_PHONG'?json_decode($asset['phong_tuong_thich'],true):[];
    if (is_array($codes)) {
        foreach ($rooms as $room) { if (in_array($room['ma_phong'],$codes,true)) { $formData['rooms'][]=(string)$room['id']; } }
    }
}
$link=static fn(array $changes): string => '?'.http_build_query(array_replace($filters,['page'=>$page],$changes));
$canEditAsset=true;
$canViewBorrower=false;
$canEditTypes=false;
$canEditRooms=true;
$title='Tài sản';
require __DIR__.'/admin-head.php';
?>
<?php if ($error): ?><p class="manager-error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php require __DIR__.'/../shared/views/assets-list.php'; ?>
<?php if ($asset): require __DIR__.'/../shared/views/asset-detail.php'; endif; ?>
<?php if ($showForm): require __DIR__.'/../quan-ly/views/asset-form.php'; endif; ?>
<script src="../vendor/qrcode.js"></script><script src="../vendor/qrcode_UTF8.js"></script><script src="../shared/assets.js"></script>
<script src="../vendor/jsQR.js"></script><script src="../shared/qr-scanner.js"></script><script src="../shared/asset-qr-search.js"></script>
<script>
const scope = document.querySelector('[name="pham_vi_tuong_thich"]');
const roomChecks = document.querySelectorAll('#compatible-rooms input');
if (scope) {
    const sync = () => {
        const flexible = scope.value === 'LINH_HOAT';
        roomChecks.forEach((check) => { if (flexible) check.checked = false; check.disabled = flexible; });
    };
    scope.addEventListener('change', sync);
    sync();
}
</script>
<?php require __DIR__.'/../shared/views/end.php';
