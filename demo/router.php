<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// 1. Roteamento de /api para /api.php
if ($uri === '/api' || $uri === '/api.php') {
    require __DIR__ . '/api.php';
    return;
}

// 2. Roteamento de /500 para /500.php
if ($uri === '/500' || $uri === '/500.php') {
    require __DIR__ . '/500.php';
    return;
}

// 3. Arquivos estáticos físicos (ex: logo.png, css, js)
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// 4. Default: página inicial index.php
require __DIR__ . '/index.php';
