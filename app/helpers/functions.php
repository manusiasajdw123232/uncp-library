<?php
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): never { header('Location: '.$url); exit; }
function is_post(): bool { return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'; }
function csrf_token(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(24)); return $_SESSION['csrf']; }
function csrf_input(): string { return '<input type="hidden" name="_csrf" value="'.e(csrf_token()).'">'; }
function csrf_check(): void { if(!hash_equals($_SESSION['csrf'] ?? '', $_POST['_csrf'] ?? '')) { http_response_code(419); exit('Sesi formulir kedaluwarsa. Silakan ulangi.'); } }
function auth_user(): ?array { return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null; }
function flash(string $type,string $message): void { $_SESSION['flash']=['type'=>$type,'message'=>$message]; }
function get_flash(): ?array { $f=$_SESSION['flash']??null; unset($_SESSION['flash']); return $f; }
function log_activity(PDO $pdo, ?int $userId, string $role, string $action, string $description=''): void {
    $st=$pdo->prepare('INSERT INTO activity_logs(user_id,role_name,action,description,ip_address,user_agent) VALUES(?,?,?,?,?,?)');
    $st->execute([$userId,$role,$action,$description,$_SERVER['REMOTE_ADDR']??null,substr($_SERVER['HTTP_USER_AGENT']??'',0,500)]);
}
function dashboard_role(array $u): string {
    $role=(string)($u['role_name']??'');
    if($role==='admin' || !empty($u['is_admin'])) return 'admin';
    if($role==='penjaga') return 'penjaga';
    return 'pengguna';
}
function role_home(array $u): string {
    $role=(string)($u['role_name']??'');
    if($role==='admin' || !empty($u['is_admin'])) return 'admin/dashboard/index.php';
    if($role==='penjaga') return 'penjaga/dashboard/index.php';
    return 'pengguna/dashboard/index.php';
}
function book_status(int $total,int $available,string $status): string { if(in_array($status,['rusak','hilang'],true)) return ucfirst($status); return $available>0?'Tersedia':'Habis'; }
function money($n): string { return 'Rp '.number_format((float)$n,0,',','.'); }
function safe_int($value, int $fallback=0): int {
    if($value===null || $value==='') return $fallback;
    if(is_int($value)) return $value;
    if(is_float($value) || is_numeric($value)) return (int)$value;
    return $fallback;
}
