<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Tests\Unit;

use HeliomarPM\ErrorTracker\Cache\FileCache;
use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\ErrorTracker;
use HeliomarPM\ErrorTracker\Severity;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use RuntimeException;

final class ErrorTrackerTest extends TestCase
{
    private string $cacheDir;
    private FileCache $cache;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/error_tracker_et_test_' . uniqid();
        $this->cache = new FileCache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        if (is_dir($this->cacheDir)) {
            @rmdir($this->cacheDir);
        }
    }

    public function testDispatchesErrorToRegisteredHandlers(): void
    {
        $tracker = new ErrorTracker($this->cache);

        $reportedContext = null;
        $handlerMock = new class (function (ErrorContext $c) use (&$reportedContext) {
            $reportedContext = $c;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handlerMock);
        $tracker->log(LogLevel::ERROR, 'Falha de teste manual');

        $this->assertNotNull($reportedContext);
        $this->assertSame('Falha de teste manual', $reportedContext->message);
        $this->assertSame(Severity::CRITICAL, $reportedContext->severity);
    }

    public function testSuppressesDuplicateErrorsWithinCacheTtl(): void
    {
        $tracker = new ErrorTracker($this->cache, 3600);

        $callCount = 0;
        $handler = new class (function () use (&$callCount) {
            $callCount++;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);

        $exception = new RuntimeException('Erro duplicado teste');

        // Primeira chamada
        $tracker->handleException($exception);
        $this->assertSame(1, $callCount);

        // Segunda chamada (idêntica)
        $tracker->handleException($exception);
        $this->assertSame(1, $callCount, 'O segundo alerta deveria ser suprimido pelo cache anti-flood.');
    }

    public function testLogsWithDifferentSeverityLevels(): void
    {
        $tracker = new ErrorTracker($this->cache);

        $severities = [];
        $handler = new class (function (ErrorContext $c) use (&$severities) {
            $severities[] = $c->severity;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);

        $tracker->info('Info log');
        $tracker->warning('Warning log');
        $tracker->notice('Notice log');

        $this->assertContains(Severity::INFO, $severities);
        $this->assertContains(Severity::WARNING, $severities);
        $this->assertContains(Severity::NOTICE, $severities);
    }

    public function testInfoLogResolvesCallerOriginAndIsNotMarkedAsException(): void
    {
        $tracker = new ErrorTracker($this->cache);

        /** @var ErrorContext|null $reportedContext */
        $reportedContext = null;
        $handler = new class (function (ErrorContext $c) use (&$reportedContext) {
            $reportedContext = $c;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);

        // Chamada direta do método info com contexto de usuário
        $line = __LINE__ + 1;
        $tracker->info('Tentativa de login com credencial inválida', ['username' => 'ana_tech']);

        $this->assertNotNull($reportedContext);
        $this->assertSame('Tentativa de login com credencial inválida', $reportedContext->message);
        $this->assertSame(Severity::INFO, $reportedContext->severity);
        $this->assertFalse($reportedContext->isException);
        $this->assertSame('INFO', $reportedContext->exceptionClass);
        $this->assertSame('ana_tech', $reportedContext->userName);
        $this->assertSame(__FILE__, $reportedContext->file);
        $this->assertSame($line, $reportedContext->line);
        $this->assertSame('', $reportedContext->trace);
    }

    public function testTracksUserConfiguredViaSetUserName(): void
    {
        $tracker = new ErrorTracker($this->cache);
        $tracker->setUserName('admin_global');

        $reportedContext = null;
        $handler = new class (function (ErrorContext $c) use (&$reportedContext) {
            $reportedContext = $c;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);
        $tracker->handleException(new RuntimeException('Erro com user estático'));

        $this->assertNotNull($reportedContext);
        $this->assertSame('admin_global', $reportedContext->userName);
    }

    public function testTracksUserConfiguredViaSetUserResolver(): void
    {
        $tracker = new ErrorTracker($this->cache);
        $currentUser = 'joao_silva';
        $tracker->setUserResolver(function () use (&$currentUser) {
            return $currentUser;
        });

        $reportedContext = null;
        $handler = new class (function (ErrorContext $c) use (&$reportedContext) {
            $reportedContext = $c;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);
        $tracker->handleException(new RuntimeException('Erro com user dinâmico'));

        $this->assertNotNull($reportedContext);
        $this->assertSame('joao_silva', $reportedContext->userName);

        // Altera o usuário da aplicação e testa nova resolução
        $currentUser = 'maria_dev';
        $tracker->log(LogLevel::ERROR, 'Outro erro com novo usuário');
        $this->assertSame('maria_dev', $reportedContext->userName);
    }

    public function testTracksUserConfiguredViaConstructor(): void
    {
        $tracker = new ErrorTracker($this->cache, 3600, userName: 'user_from_constructor');

        $reportedContext = null;
        $handler = new class (function (ErrorContext $c) use (&$reportedContext) {
            $reportedContext = $c;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);
        $tracker->handleException(new RuntimeException('Erro construtor'));

        $this->assertNotNull($reportedContext);
        $this->assertSame('user_from_constructor', $reportedContext->userName);
    }

    public function testUserResolverExceptionDoesNotBreakTracking(): void
    {
        $tracker = new ErrorTracker($this->cache);
        $tracker->setUserResolver(function () {
            throw new RuntimeException('Falha no serviço de autenticação');
        });

        $reportedContext = null;
        $handler = new class (function (ErrorContext $c) use (&$reportedContext) {
            $reportedContext = $c;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);
        $tracker->handleException(new RuntimeException('Erro com resolver defeituoso'));

        $this->assertNotNull($reportedContext);
        $this->assertNull($reportedContext->userName);
    }

    public function testExtraContextUserOverridesGlobalTrackerUser(): void
    {
        $tracker = new ErrorTracker($this->cache);
        $tracker->setUserName('user_padrao');

        $reportedContext = null;
        $handler = new class (function (ErrorContext $c) use (&$reportedContext) {
            $reportedContext = $c;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);
        $tracker->info('Log com sobrescrita', ['username' => 'usuario_especifico']);

        $this->assertNotNull($reportedContext);
        $this->assertSame('usuario_especifico', $reportedContext->userName);
    }

    public function testConfiguresParametersWithNamedArguments(): void
    {
        $tracker = new ErrorTracker($this->cache);
        $tracker->parameters(
            appName: 'Salvar ERP',
            environment: 'staging',
            serverName: 'cluster-node-02',
            userName: 'tech_lead',
            extraContext: ['tenant_id' => 'empresa_123']
        );

        $params = $tracker->getParameters();
        $this->assertSame('Salvar ERP', $params['appName']);
        $this->assertSame('staging', $params['environment']);
        $this->assertSame('cluster-node-02', $params['serverName']);
        $this->assertSame('tech_lead', $params['userName']);
        $this->assertSame(['tenant_id' => 'empresa_123'], $params['extraContext']);
    }

    public function testConfiguresParametersWithAssociativeArray(): void
    {
        $tracker = new ErrorTracker($this->cache);
        $tracker->parameters([
            'app_name' => 'API Gateway',
            'environment' => 'production',
            'server_name' => 'aws-ec2-01',
            'user_name' => 'devops_user',
            'extra_context' => ['version' => '2.1.0'],
        ]);

        $params = $tracker->getParameters();
        $this->assertSame('API Gateway', $params['appName']);
        $this->assertSame('production', $params['environment']);
        $this->assertSame('aws-ec2-01', $params['serverName']);
        $this->assertSame('devops_user', $params['userName']);
        $this->assertSame(['version' => '2.1.0'], $params['extraContext']);
    }

    public function testParametersPropagateToErrorContext(): void
    {
        $tracker = new ErrorTracker($this->cache);
        $tracker->parameters(
            appName: 'Meu Sistema',
            environment: 'development',
            serverName: 'dev-box',
            userName: 'marcos',
            extraContext: ['commit' => 'abcdef']
        );

        $reportedContext = null;
        $handler = new class (function (ErrorContext $c) use (&$reportedContext) {
            $reportedContext = $c;
        }) implements HandlerInterface {
            public function __construct(private $callback) {}
            public function report(ErrorContext $context): void
            {
                ($this->callback)($context);
            }
        };

        $tracker->addHandler($handler);
        $tracker->handleException(new RuntimeException('Teste de parâmetros'));

        $this->assertNotNull($reportedContext);
        $this->assertSame('Meu Sistema', $reportedContext->appName);
        $this->assertSame('development', $reportedContext->environment);
        $this->assertSame('dev-box', $reportedContext->serverName);
        $this->assertSame('marcos', $reportedContext->userName);
        $this->assertSame('abcdef', $reportedContext->requestData['context']['commit'] ?? null);
    }

    public function testRegisterAcceptsParametersAndSetsUpHandlers(): void
    {
        $tracker = new ErrorTracker($this->cache);
        $tracker->register(
            appName: 'App No Register',
            environment: 'testing'
        );

        $this->assertSame('App No Register', $tracker->getAppName());
        $this->assertSame('testing', $tracker->getEnvironment());

        restore_error_handler();
        restore_exception_handler();
    }
}
