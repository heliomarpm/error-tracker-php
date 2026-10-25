<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\ErrorTracker;
use HeliomarPM\ErrorTracker\Handlers\DatabaseHandler;
use HeliomarPM\ErrorTracker\Handlers\EmailHandler;
use HeliomarPM\ErrorTracker\Handlers\FilelogHandler;
use HeliomarPM\ErrorTracker\Handlers\GitHubIssueHandler;
use HeliomarPM\ErrorTracker\Handlers\WebhookHandler;
use HeliomarPM\ErrorTracker\Severity;
use Psr\SimpleCache\CacheInterface;

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// --------------------------------------------------------------------------
// Cache 100% em memória para o demo (Zero poluição de disco no servidor Vercel)
// --------------------------------------------------------------------------
$memoryCache = new class () implements CacheInterface {
    private array $storage = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->storage[$key])) {
        return $default;
        }
        if ($this->storage[$key]['exp'] !== null && time() > $this->storage[$key]['exp']) {
            unset($this->storage[$key]);
            return $default;
        }
        return $this->storage[$key]['val'];
    }
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        $exp = $ttl !== null ? time() + (is_int($ttl) ? $ttl : 3600) : null;
        $this->storage[$key] = ['val' => $value, 'exp' => $exp];
        return true;
    }
    public function delete(string $key): bool
    {
        unset($this->storage[$key]);
        return true;
    }
    public function clear(): bool
    {
        $this->storage = [];
        return true;
    }
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $res = [];
        foreach ($keys as $k) {
        $res[$k] = $this->get((string)$k, $default);
        }
        return $res;
    }
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $k => $v) {
        $this->set((string)$k, $v, $ttl);
        }
        return true;
    }
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $k) {
        $this->delete((string)$k);
        }
        return true;
    }
};

// --------------------------------------------------------------------------
// Handler em memória para capturar contexto sem persistência física
// --------------------------------------------------------------------------
$capturedContext = null;
$captureHandler = new class (function (ErrorContext $context) use (&$capturedContext) {
    $capturedContext = $context;
}) implements HandlerInterface {
    public function __construct(private $callback) {}
    public function report(ErrorContext $context): void
    {
        ($this->callback)($context);
    }
};

$inputRaw = file_get_contents('php://input');
$input = !empty($inputRaw) ? (json_decode($inputRaw, true) ?: []) : $_POST;
$action = (string)($input['action'] ?? ($_GET['action'] ?? 'info'));

// Instancia handlers para geração dos previews mockados
$emailHtmlHandler = new EmailHandler('suporte@empresa.com', format: 'html');
$emailTextHandler = new EmailHandler('suporte@empresa.com', format: 'text');
$githubHandler = new GitHubIssueHandler('ghp_mock_token_demo', 'heliomarpm/error-tracker-demo');
$slackHandler = new WebhookHandler('https://hooks.slack.com/...', format: 'slack');
$discordHandler = new WebhookHandler('https://discord.com/api/webhooks/...', format: 'discord');
$filelogHandler = new FilelogHandler(__DIR__ . '/storage/logs');
$sqliteMemoryPdo = new PDO('sqlite::memory:');
$databaseHandler = new DatabaseHandler($sqliteMemoryPdo, tableName: 'error_tracker', autoCreate: true);

$buildPreviews = function (?ErrorContext $context) use (
    $emailHtmlHandler,
    $emailTextHandler,
    $githubHandler,
    $slackHandler,
    $discordHandler,
    $filelogHandler,
    $databaseHandler
): array {
    if ($context === null) {
        return [];
    }

    return [
        'email_html' => $emailHtmlHandler->renderBody($context),
        'email_text' => $emailTextHandler->renderBody($context),
        'github_title' => $githubHandler->renderTitle($context),
        'github_markdown' => $githubHandler->renderBody($context),
        'slack_json' => $slackHandler->buildPayload($context),
        'discord_json' => $discordHandler->buildPayload($context),
        'filelog' => [
            'filename' => $filelogHandler->getLogFilename($context),
            'content' => $filelogHandler->renderEntry($context),
        ],
        'database' => [
            'table' => 'error_tracker',
            'query_mysql' => $databaseHandler->getInsertQuery('mysql'),
            'query_sqlite' => $databaseHandler->getInsertQuery('sqlite'),
            'record' => $databaseHandler->buildRecord($context),
        ],
    ];
};

try {
    if ($action === 'test_flood') {
        // Simulação Anti-Flood: 50 disparos simultâneos
        $tracker = new ErrorTracker($memoryCache, cacheTtlSeconds: 60);
        $dispatchCount = 0;
        $counterHandler = new class (function () use (&$dispatchCount) {
            $dispatchCount++;
        }) implements HandlerInterface {
            public function __construct(private $cb) {}
            public function report(ErrorContext $context): void
            {
            ($this->cb)();
            }
        };

        $tracker->addHandler($counterHandler);
        $tracker->addHandler($captureHandler);

        $fakeException = new RuntimeException('Falha de conexão com gateway de pagamentos (Ataque DDoS)');
        for ($i = 0; $i < 50; $i++) {
            $tracker->handleException($fakeException);
        }

        echo json_encode([
            'status' => 'success',
            'action' => 'test_flood',
            'total_triggered' => 50,
            'dispatched_count' => $dispatchCount,
            'suppressed_count' => 50 - $dispatchCount,
            'efficiency_percent' => '98%',
            'message' => '50 erros disparados em loop. O cache anti-flood autorizou apenas 1 despacho e silenciou 49 duplicatas.',
            'context' => [
                'hash' => $capturedContext?->hash,
                'message' => $capturedContext?->message,
                'severity' => $capturedContext?->severity->name,
                'file' => $capturedContext?->file,
                'line' => $capturedContext?->line,
            ],
            'previews' => $buildPreviews($capturedContext),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit(0);
    }

    // Ações de simulação de erro e sanitização
    $tracker = new ErrorTracker($memoryCache, cacheTtlSeconds: 0);
    $tracker->addHandler($captureHandler);

    if ($action === 'sanitization') {
        // Simula submissão de formulário com dados altamente confidenciais
        $_POST = [
            'username' => (string)($input['username'] ?? 'heliomar_dev'),
            'password' => (string)($input['password'] ?? 'minhaSenhaSuperSecreta123'),
            'token' => (string)($input['token'] ?? 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...'),
            'cpf' => (string)($input['cpf'] ?? '123.456.789-10'),
            'credit_card' => (string)($input['credit_card'] ?? '4532 1122 3344 9876'),
            'email' => (string)($input['email'] ?? 'suporte.tech@empresa.com.br'),
        ];

        $tracker->error('Erro no processamento do checkout com dados do cliente');
    } elseif ($action === 'notice') {
        $tracker->notice('Tentativa de acesso com cupom expirado: ' . ($input['coupon'] ?? 'PROMO2026'));
    } elseif ($action === 'warning') {
        $tracker->warning('Uso de endpoint depreciado v1/pedidos detectado.');
    } elseif ($action === 'exception') {
        $rootCause = new PDOException('SQLSTATE[HY000] [2002] Connection timed out ao conectar com cluster MySQL (porta 3306)', 2002);
        $fakeException = new RuntimeException(
            (string)($input['error_message'] ?? 'Falha ao processar pagamento com cartão: Timeout no gateway de transações'),
            500,
            $rootCause
        );
        $tracker->handleException($fakeException);
    } else {
        $tracker->info('Consulta de catálogo executada.');
    }

    if ($capturedContext === null) {
        $capturedContext = new ErrorContext(
            new ErrorException('Evento de demonstração', 0, E_USER_NOTICE, __FILE__, __LINE__),
            [],
            Severity::INFO
        );
    }

    echo json_encode([
        'status' => 'success',
        'action' => $action,
        'context' => [
            'hash' => $capturedContext->hash,
            'message' => $capturedContext->message,
            'severity' => $capturedContext->severity->name,
            'severity_value' => $capturedContext->severity->value,
            'file' => $capturedContext->file,
            'line' => $capturedContext->line,
            'code' => $capturedContext->code,
            'trace' => $capturedContext->trace,
            'sanitized_payload' => $capturedContext->requestData,
            'sanitized_post' => $capturedContext->requestData['post'] ?? [],
            'timestamp' => date('Y-m-d H:i:s'),
        ],
        'previews' => $buildPreviews($capturedContext),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
