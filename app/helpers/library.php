<?php
if (!function_exists('notify_user')) {
    function notify_user(PDO $pdo, int $userId, string $title, string $message): void {
        $st=$pdo->prepare('INSERT INTO notifications(user_id,title,message) VALUES(?,?,?)');
        $st->execute([$userId,$title,$message]);
    }
}
if (!function_exists('submission_history')) {
    function submission_history(PDO $pdo, ?int $userId, string $type, int $itemId, string $action, ?string $note, ?int $processorId=null): void {
        $st=$pdo->prepare('INSERT INTO submission_history(user_id,item_type,item_id,action,note,processed_by) VALUES(?,?,?,?,?,?)');
        $st->execute([$userId,$type,$itemId,$action,$note,$processorId]);
    }
}
if (!function_exists('save_upload')) {
    function save_upload(array $file, string $relativeDir, array $allowedExt, int $maxBytes=10485760): ?string {
        if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
        if(($file['error']??0)!==UPLOAD_ERR_OK) throw new RuntimeException('Upload file gagal.');
        $size=(int)($file['size']??0);
        if($size>$maxBytes) throw new RuntimeException('Ukuran file terlalu besar.');
        $tmp=$file['tmp_name']??'';
        if(!is_uploaded_file($tmp)) throw new RuntimeException('File tidak valid.');
        $ext=strtolower(pathinfo($file['name']??'', PATHINFO_EXTENSION));
        if(!in_array($ext,$allowedExt,true)) throw new RuntimeException('Format file tidak diizinkan.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: '';
        $mimeMap=['jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'png'=>['image/png'],'webp'=>['image/webp'],'pdf'=>['application/pdf']];
        if(isset($mimeMap[$ext]) && !in_array($mime,$mimeMap[$ext],true)) throw new RuntimeException('Isi file tidak sesuai dengan format yang dipilih.');
        $dir=dirname(__DIR__,2).'/'.$relativeDir;
        if(!is_dir($dir) && !mkdir($dir,0775,true) && !is_dir($dir)) throw new RuntimeException('Folder upload tidak dapat dibuat.');
        $name=bin2hex(random_bytes(12)).'.'.$ext;
        if(!move_uploaded_file($tmp,$dir.'/'.$name)) throw new RuntimeException('File tidak dapat disimpan.');
        return '/'.$relativeDir.'/'.$name;
    }
}
if (!function_exists('delete_local_upload')) {
    function delete_local_upload(?string $url): void {
        if(!$url || !str_starts_with($url,'/uploads/')) return;
        $full=dirname(__DIR__,2).$url;
        if(is_file($full)) @unlink($full);
    }
}
if (!function_exists('status_label')) {
    function status_label(string $s): string {
        return match($s){'publik'=>'Disetujui','ditolak'=>'Ditolak','perlu_perbaikan'=>'Perlu Perbaikan','menunggu'=>'Menunggu Verifikasi','disetujui'=>'Disetujui','diajukan'=>'Diajukan','privat'=>'Privat',default=>ucfirst($s)};
    }
}
if (!function_exists('status_class')) {
    function status_class(string $s): string {
        return match($s){'publik','disetujui'=>'green','ditolak'=>'red','perlu_perbaikan'=>'yellow','menunggu','diajukan'=>'yellow','privat'=>'badge-dark',default=>'badge-dark'};
    }
}
