<?php
/**
 * Database configuration loader
 * Returns a config array used by src/Database.php
 * Environment variables must already be loaded before requiring this file.
 */

return [
    'host'     => $_ENV['DB_HOST']  ?? 'localhost',
    'port'     => $_ENV['DB_PORT']  ?? '3306',
    'dbname'   => $_ENV['DB_NAME']  ?? 'repair_system',
    'username' => $_ENV['DB_USER']  ?? 'root',
    'password' => $_ENV['DB_PASS']  ?? '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ],
];
