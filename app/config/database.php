<?php
$config = require __DIR__ . '/config.php';
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$config['db']['host'],$config['db']['port'],$config['db']['database']);
    $pdo = new PDO($dsn,$config['db']['username'],$config['db']['password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi .env dan pastikan MySQL berjalan.');
}
return $pdo;
