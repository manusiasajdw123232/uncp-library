<?php
function save_profile_photo(array $file, int $userId): ?string {
    if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
    if(($file['error']??0)!==UPLOAD_ERR_OK) throw new RuntimeException('Foto profil gagal diunggah.');
    if(($file['size']??0)>3*1024*1024) throw new RuntimeException('Ukuran foto maksimal 3 MB.');
    $tmp=$file['tmp_name']??'';
    if(!is_uploaded_file($tmp)) throw new RuntimeException('File foto tidak valid.');
    $finfo=new finfo(FILEINFO_MIME_TYPE);
    $mime=$finfo->file($tmp);
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if(!isset($allowed[$mime])) throw new RuntimeException('Format foto harus JPG, PNG, atau WebP.');
    $dir=__DIR__.'/../../uploads/profile';
    if(!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('Folder foto profil tidak dapat dibuat.');
    $name='user-'.$userId.'-'.bin2hex(random_bytes(8)).'.'.$allowed[$mime];
    if(!move_uploaded_file($tmp,$dir.'/'.$name)) throw new RuntimeException('Foto profil gagal disimpan.');
    return '/uploads/profile/'.$name;
}
function profile_photo_path(?string $url): ?string {
    if(!$url || !str_starts_with($url,'/uploads/profile/')) return null;
    $full=__DIR__.'/../..'.$url;
    return is_file($full)?$full:null;
}
function profile_initial(array $u): string {
    $name=trim((string)($u['name']??'U'));
    return strtoupper(function_exists('mb_substr')?mb_substr($name,0,1):substr($name,0,1));
}
