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

// 3. Fallback para requisições de /favicon.ico apontando para favicon.svg
if ($uri === '/favicon.ico' && !file_exists(__DIR__ . '/favicon.ico') && file_exists(__DIR__ . '/favicon.svg')) {
    header('Content-Type: image/svg+xml');
    readfile(__DIR__ . '/favicon.svg');
    return;
}

// 4. Arquivos estáticos físicos dentro de demo/ (com blindagem contra Path Traversal)
$realDemoDir = realpath(__DIR__) ?: __DIR__;
$filePath = __DIR__ . $uri;
$realFile = realpath($filePath);

if ($uri !== '/' && $realFile !== false && !is_dir($realFile)) {
    // Garante que o arquivo solicitado está estritamente contido no diretório da demo
    if (str_starts_with($realFile, $realDemoDir . DIRECTORY_SEPARATOR)) {
        return false;
    }

    // Bloqueia tentativas de navegação fora da pasta demo (Path Traversal)
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo '403 Forbidden: Acesso restrito ao diretório da demonstração.';
    return;
}

// 5. Default: página inicial index.php
require __DIR__ . '/index.php';
