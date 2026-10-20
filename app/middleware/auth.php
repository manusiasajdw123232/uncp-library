<?php
function require_login(): array {
    $u=auth_user();
    if(!$u) redirect('/login.php');
    return $u;
}
function require_roles(array $roles): array {
    $u=require_login();
    $role=(string)($u['role_name']??'');
    $ok=in_array($role,$roles,true);
    if(in_array('admin',$roles,true) && !empty($u['is_admin'])) $ok=true;
    if(!$ok){ http_response_code(403); exit('403 - Akses ditolak.'); }
    return $u;
}
function require_admin(): array {
    $u=require_login();
    if(($u['role_name']??'')!=='admin' && empty($u['is_admin'])){ http_response_code(403); exit('403 - Khusus admin.'); }
    return $u;
}
