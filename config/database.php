<?php
declare(strict_types=1);

$localConfig = __DIR__ . '/database.local.php';
if (is_file($localConfig)) {
    return require $localConfig;
}

return [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'camera_store_db',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
];
