<?php

// Carregue configurações
require_once __DIR__ . '/vendor/autoload.php';

// Carregue o .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Exiba variáveis para teste
echo "<pre>";
echo "Teste de variáveis de ambiente:\n";
echo "DB_HOST: " . $_ENV['DB_HOST'] . "\n";
echo "DB_NAME: " . $_ENV['DB_NAME'] . "\n";
echo "APP_ENV: " . $_ENV['APP_ENV'] . "\n";
echo "</pre>";
?>