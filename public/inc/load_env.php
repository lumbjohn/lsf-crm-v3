<?php

$dotenv = Dotenv\Dotenv::create(__DIR__ . '/../..');
try {
    $dotenv->load();
} catch (DotEnv\Exception\InvalidPathException $e) {
}
$dotenv->required(['APP_URL', 'DB_HOST', 'DB_USERNAME', 'DB_DATABASE'])->notEmpty();
