<?php

declare(strict_types=1);

/**
 * Ponto de entrada Serverless para a plataforma Vercel.
 *
 * Roteia as requisições para os arquivos da pasta demo/
 * mantendo compatibilidade total com o runtime vercel-php.
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

// 1. Rota da API (simulações, sanitização e geração de prévias)
if ($uri === '/api' || $uri === '/api.php' || $uri === '/demo/api.php' || str_starts_with($uri, '/api/')) {
    require __DIR__ . '/../demo/api.php';
    exit;
}

// 2. Rota de simulação de erro fatal 500 (tela failsafe)
if ($uri === '/500' || $uri === '/500.php' || $uri === '/demo/500.php') {
    require __DIR__ . '/../demo/500.php';
    exit;
}

// 3. Imagem estática do logotipo
if ($uri === '/logo.png') {
    $logo = __DIR__ . '/../logo.png';
    if (!file_exists($logo)) {
        $logo = __DIR__ . '/../demo/logo.png';
    }
    if (file_exists($logo)) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=86400');
        readfile($logo);
        exit;
    }
}

// 4. Página principal do Workbench
require __DIR__ . '/../demo/index.php';
