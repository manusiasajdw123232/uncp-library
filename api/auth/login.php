<?php
require __DIR__.'/../../app/core/bootstrap.php';
header('Content-Type: application/json');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'POST required']);exit;}
$d=json_decode(file_get_contents('php://input'),true) ?: $_POST;
$id=trim($d['identifier']??'');
$pw=$d['password']??'';
if($id===''||$pw===''){http_response_code(422);echo json_encode(['error'=>$id===''?'Tuliskan username, NIM, atau email.':'Masukkan password.']);exit;}
try{
  $st=$pdo->prepare("SELECT u.*,r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE (u.nim=? OR u.username=? OR u.email=?) AND u.status='aktif' LIMIT 1");
  $st->execute([$id,$id,$id]);
  $u=$st->fetch();
  if(!$u||!password_verify($pw,$u['password_hash'])){http_response_code(401);echo json_encode(['error'=>'Username, NIM, email, atau password tidak sesuai.']);exit;}
  session_regenerate_id(true);
  $pdo->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([(int)$u['id']]);
  unset($u['password_hash']);
  $_SESSION['user']=$u;
  log_activity($pdo,(int)$u['id'],$u['role_name'],'Login API','Masuk ke sistem melalui API');
  echo json_encode(['success'=>true,'redirect'=>'/'.role_home($u),'user'=>$u]);
}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['error'=>'Login tidak dapat diproses. Silakan coba lagi.']);
}
