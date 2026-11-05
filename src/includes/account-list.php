<?php
declare(strict_types=1);

function account_list(mysqli $db, string $keyword, string $role, string $status, mixed $requestedPage): array
{
    $pattern = '%'.strtr($keyword,['!'=>'!!','%'=>'!%','_'=>'!_']).'%';
    $where = " WHERE (n.ma_nhan_vien LIKE ? ESCAPE '!' OR n.ho_ten LIKE ? ESCAPE '!' OR n.ten_dang_nhap LIKE ? ESCAPE '!')
    AND (?='' OR n.vai_tro=?) AND (?='' OR n.dang_hoat_dong=?)";
    $args = [$pattern,$pattern,$pattern,$role,$role,$status,$status];
    $total = (int)$db->execute_query('SELECT COUNT(*) FROM nhan_vien n'.$where,$args)->fetch_row()[0];
    $pages = max(1,(int)ceil($total/20));
    $page = min($pages,max(1,(int)filter_var($requestedPage,FILTER_VALIDATE_INT)));
    $list = $db->execute_query('SELECT n.id,n.ma_nhan_vien,n.ho_ten,n.ten_dang_nhap,n.vai_tro,n.dang_hoat_dong,p.ten_phong_truc
        FROM nhan_vien n LEFT JOIN phong_truc p ON p.id=n.phong_truc_id'.$where.' ORDER BY n.id DESC LIMIT 20 OFFSET ?', [...$args,($page-1)*20]);

    return [$list, $total, $page, $pages];
}
