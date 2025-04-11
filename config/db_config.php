<?php 
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

return [
    'driver' => 'pdo_mysql',         
    'host' => $_ENV['DB_HOST'],            
    'port' => $_ENV['DB_PORT'],            
    'dbname' => $_ENV['DB_NAME'],         
    'user' => $_ENV['DB_USER'],            
    'password' => $_ENV['DB_PASSWORD'],     
    'serverVersion' => $_ENV['DB_SERVER_VERSION'], 
];