<?php

// Carregue o autoloader do Composer
require_once __DIR__ . '/vendor/autoload.php';

// Carregue as variáveis de ambiente do arquivo .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Definir variáveis necessárias
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS']);

// Retornar uma configuração estruturada (opcional)
return [
    'database' => [
        'host' => $_ENV['DB_HOST'],
        'dbname' => $_ENV['DB_NAME'],
        'username' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASS'],
    ],
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'debug' => isset($_ENV['APP_DEBUG']) ? (bool)$_ENV['APP_DEBUG'] : false,
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    ],
    'api' => [
        'key' => $_ENV['API_KEY'] ?? null,
    ],
];

?>