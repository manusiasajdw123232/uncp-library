<?php
if (!function_exists('env_value')) {
function env_value(string $key, $default = null) {
    static $env = null;
    if ($env === null) {
        $env = [];
        $path = dirname(__DIR__, 2) . '/.env';
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$k,$v] = explode('=', $line, 2); $v = trim($v);
                $env[trim($k)] = trim($v, "\"'");
            }
        }
    }
    return $env[$key] ?? $default;
}
}

return [
  'app_name'=>env_value('APP_NAME','UNCP Library Repository'),
  'app_url'=>env_value('APP_URL','http://localhost:8000'),
  'db'=>[
    'host'=>env_value('DB_HOST','127.0.0.1'), 'port'=>env_value('DB_PORT',3306),
    'database'=>env_value('DB_DATABASE','uncp_library'), 'username'=>env_value('DB_USERNAME','root'),
    'password'=>env_value('DB_PASSWORD','')
  ]
];
