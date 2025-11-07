<?php
$cfg = [
  'host' => '127.0.0.1',
  'port' => 3306, // o 3307 según tu XAMPP
  'db'   => 'inspecciones',
  'user' => 'root',
  'pass' => '',
];

$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};charset=utf8mb4";
$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
