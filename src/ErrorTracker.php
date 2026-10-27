<?php

declare(strict_types=1);

namespace HeliomarPM\ErrorTracker;

use ErrorException;
use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;
use Stringable;
use Throwable;

final class ErrorTracker implements LoggerInterface
{
  use LoggerTrait;

  /**
   * Chaves essenciais de requisição preservadas em $_SERVER.
   */
  private const ALLOWED_SERVER_KEYS = [
      'REQUEST_METHOD',
      'REQUEST_URI',
      'QUERY_STRING',
      'SERVER_NAME',
      'SERVER_ADDR',
      'SERVER_PORT',
      'SERVER_PROTOCOL',
      'SERVER_SOFTWARE',
      'REMOTE_ADDR',
      'REMOTE_PORT',
      'SCRIPT_NAME',
      'SCRIPT_FILENAME',
      'PHP_SELF',
      'DOCUMENT_ROOT',
      'HTTPS',
      'CONTENT_TYPE',
      'CONTENT_LENGTH',
      'argv',
      'argc',
  ];

  /** @var HandlerInterface[] */
  private array $handlers = [];
  private DataSanitizer $sanitizer;
  private ?CacheInterface $cache;
  private int $cacheTtl;

  /** @var callable|null */
  private $onCriticalCallback = null;

  private string $appName = 'ErrorTracker';
  private string $environment = 'production';
  private ?string $serverName = null;
  private ?string $userName = null;

  /** @var (callable(): (string|Stringable|null))|null */
  private $userResolver = null;

  /** @var array<string, mixed> */
  private array $extraContext = [];

  /**
   * Inicializa o rastreador.
   *
   * @param CacheInterface|null $cache Instância de cache (PSR-16) para evitar flood de alertas.
   * @param int $cacheTtlSeconds Tempo em segundos que um erro idêntico será silenciado (padrão 1 hora).
   * @param string|null $userName Nome ou identificador estático do usuário padrão.
   * @param (callable(): (string|Stringable|null))|null $userResolver Callback dinâmico invocado em runtime para resolver o usuário logado.
   * @param string $appName Nome da aplicação.
   * @param string $environment Ambiente de execução (ex: production, staging, development, local).
   * @param string|null $serverName Nome do host ou servidor.
   * @param array<string, mixed> $extraContext Metadados adicionais compartilhados com todos os handlers.
   * @param array<string> $maskedKeys Chaves adicionais personalizadas para blackout no sanitizador.
   */
  public function __construct(
      ?CacheInterface $cache = null,
      int $cacheTtlSeconds = 3600,
      ?string $userName = null,
      ?callable $userResolver = null,
      string $appName = 'ErrorTracker',
      string $environment = 'production',
      ?string $serverName = null,
      array $extraContext = [],
      array $maskedKeys = []
  ) {
    $this->sanitizer = new DataSanitizer($maskedKeys);
    $this->cache = $cache;
    $this->cacheTtl = $cacheTtlSeconds;
    $this->userName = $userName;
    $this->userResolver = $userResolver;
    $this->appName = $appName;
    $this->environment = $environment;
    $this->serverName = $serverName;
    $this->extraContext = $extraContext;
  }

  /**
   * Restaura os tratadores nativos de erro e exceção do PHP.
   */
  public static function unregister(): void
  {
    restore_error_handler();
    restore_exception_handler();
  }

  /**
   * Define ou atualiza os parâmetros globais da aplicação compartilhados com todos os handlers.
   * Suporta named arguments (PHP 8) ou um array associativo de parâmetros.
   *
   * Exemplo com Named Arguments:
   *   $tracker->parameters(
   *       appName: 'Salvar ERP',
   *       environment: 'production',
   *       userResolver: fn() => $_SESSION['usuario']['nome'] ?? null
   *   );
   *
   * Exemplo com Array Associativo:
   *   $tracker->parameters([
   *       'app_name' => 'Salvar ERP',
   *       'environment' => 'staging',
   *       'user_name' => 'admin',
   *   ]);
   *
   * @param array<string, mixed>|string|null $appName Nome da aplicação ou array de parâmetros.
   * @param string|null $environment Ambiente de execução (production, staging, dev, etc.).
   * @param string|null $serverName Nome do host/servidor.
   * @param string|null $userName Nome ou identificador estático do usuário.
   * @param (callable(): (string|Stringable|null))|null $userResolver Callback dinâmico para resolver o usuário.
   * @param array<string, mixed> $extraContext Metadados extras compartilhados.
   * @param array<string> $maskedKeys Chaves adicionais personalizadas para blackout.
   */
  public function parameters(
      array|string|null $appName = null,
      ?string $environment = null,
      ?string $serverName = null,
      ?string $userName = null,
      ?callable $userResolver = null,
      array $extraContext = [],
      array $maskedKeys = []
  ): self {
    if (is_array($appName)) {
      $params = $appName;
      if (isset($params['app_name']) || isset($params['appName']) || isset($params['application'])) {
        $this->appName = (string)($params['app_name'] ?? ($params['appName'] ?? $params['application']));
      }
      if (isset($params['environment']) || isset($params['env']) || isset($params['ambiente'])) {
        $this->environment = (string)($params['environment'] ?? ($params['env'] ?? $params['ambiente']));
      }
      if (isset($params['server_name']) || isset($params['serverName']) || isset($params['server']) || isset($params['host'])) {
        $this->serverName = (string)($params['server_name'] ?? ($params['serverName'] ?? ($params['server'] ?? $params['host'])));
      }
      if (isset($params['user_name']) || isset($params['userName']) || isset($params['user']) || isset($params['usuario'])) {
        $this->userName = (string)($params['user_name'] ?? ($params['userName'] ?? ($params['user'] ?? $params['usuario'])));
      }
      if (isset($params['user_resolver']) && is_callable($params['user_resolver'])) {
        $this->userResolver = $params['user_resolver'];
      } elseif (isset($params['userResolver']) && is_callable($params['userResolver'])) {
        $this->userResolver = $params['userResolver'];
      }
      if (isset($params['extra_context']) && is_array($params['extra_context'])) {
        $this->extraContext = array_merge($this->extraContext, $params['extra_context']);
      } elseif (isset($params['extraContext']) && is_array($params['extraContext'])) {
        $this->extraContext = array_merge($this->extraContext, $params['extraContext']);
      } elseif (isset($params['context']) && is_array($params['context'])) {
        $this->extraContext = array_merge($this->extraContext, $params['context']);
      }
      if (isset($params['masked_keys']) && is_array($params['masked_keys'])) {
        /** @var string[] $keys */
        $keys = $params['masked_keys'];
        $this->sanitizer->addMaskedKeys($keys);
      } elseif (isset($params['maskedKeys']) && is_array($params['maskedKeys'])) {
        /** @var string[] $keys */
        $keys = $params['maskedKeys'];
        $this->sanitizer->addMaskedKeys($keys);
      }
      return $this;
    }

    if ($appName !== null) {
      $this->appName = $appName;
    }
    if ($environment !== null) {
      $this->environment = $environment;
    }
    if ($serverName !== null) {
      $this->serverName = $serverName;
    }
    if ($userName !== null) {
      $this->userName = $userName;
    }
    if ($userResolver !== null) {
      $this->userResolver = $userResolver;
    }
    if (!empty($extraContext)) {
      $this->extraContext = array_merge($this->extraContext, $extraContext);
    }
    if (!empty($maskedKeys)) {
      $this->sanitizer->addMaskedKeys($maskedKeys);
    }

    return $this;
  }

  /**
   * Retorna os parâmetros globais configurados.
   *
   * @return array<string, mixed>
   */
  public function getParameters(): array
  {
    return [
        'appName' => $this->appName,
        'environment' => $this->environment,
        'serverName' => $this->serverName,
        'userName' => $this->userName,
        'extraContext' => $this->extraContext,
        'maskedKeys' => $this->sanitizer->getMaskedKeys(),
    ];
  }

  /**
   * Adiciona chaves adicionais para sofrerem blackout total no sanitizador.
   *
   * @param array<string> $keys
   */
  public function addMaskedKeys(array $keys): self
  {
    $this->sanitizer->addMaskedKeys($keys);
    return $this;
  }

  /**
   * Retorna a instância do sanitizador de dados.
   */
  public function getSanitizer(): DataSanitizer
  {
    return $this->sanitizer;
  }

  /**
   * Define o nome ou identificador do usuário ativo.
   */
  public function setUserName(?string $userName): self
  {
    $this->userName = $userName;
    return $this;
  }

  public function getUserName(): ?string
  {
    return $this->userName;
  }

  /**
   * Define um callback dinâmico para resolver o nome do usuário ativo em runtime.
   * Exemplo: fn() => Auth::user()?->name ?? $_SESSION['usuario']['nome'] ?? null
   */
  public function setUserResolver(?callable $resolver): self
  {
    $this->userResolver = $resolver;
    return $this;
  }

  public function setAppName(string $appName): self
  {
    $this->appName = $appName;
    return $this;
  }

  public function getAppName(): string
  {
    return $this->appName;
  }

  public function setEnvironment(string $environment): self
  {
    $this->environment = $environment;
    return $this;
  }

  public function getEnvironment(): string
  {
    return $this->environment;
  }

  public function setServerName(?string $serverName): self
  {
    $this->serverName = $serverName;
    return $this;
  }

  public function getServerName(): ?string
  {
    return $this->serverName;
  }

  /**
   * @param array<string, mixed> $extraContext
   */
  public function setExtraContext(array $extraContext): self
  {
    $this->extraContext = $extraContext;
    return $this;
  }

  /**
   * @return array<string, mixed>
   */
  public function getExtraContext(): array
  {
    return $this->extraContext;
  }

  /**
   * Adiciona um canal de reporte (ex: Banco de Dados, E-mail, GitHub).
   */
  public function addHandler(HandlerInterface $handler): self
  {
    $this->handlers[] = $handler;
    return $this;
  }

  /**
   * Define o comportamento final do sistema caso ocorra um erro CRÍTICO.
   * Ideal para redirecionar o usuário para a tela de erro 500 ou retornar JSON em APIs.
   */
  public function onCritical(callable $callback): self
  {
    $this->onCriticalCallback = $callback;
    return $this;
  }

  /**
   * Assume o controle das funções nativas de tratamento de erro do PHP e opcionalmente define parâmetros globais.
   *
   * @param array<string, mixed>|string|null $appName Nome da aplicação ou array associativo de parâmetros.
   * @param string|null $environment Ambiente de execução.
   * @param string|null $serverName Nome do servidor/host.
   * @param string|null $userName Nome do usuário padrão.
   * @param (callable(): (string|Stringable|null))|null $userResolver Callback dinâmico de usuário.
   * @param array<string, mixed> $extraContext Metadados adicionais compartilhados.
   * @param array<string> $maskedKeys Chaves adicionais personalizadas para blackout.
   */
  public function register(
      array|string|null $appName = null,
      ?string $environment = null,
      ?string $serverName = null,
      ?string $userName = null,
      ?callable $userResolver = null,
      array $extraContext = [],
      array $maskedKeys = []
  ): void {
    if ($appName !== null || $environment !== null || $serverName !== null || $userName !== null || $userResolver !== null || !empty($extraContext) || !empty($maskedKeys)) {
      $this->parameters($appName, $environment, $serverName, $userName, $userResolver, $extraContext, $maskedKeys);
    }

    error_reporting(E_ALL);
    set_exception_handler([$this, "handleException"]);
    set_error_handler([$this, "handleError"]);
    register_shutdown_function([$this, "handleFatalError"]);
  }

  /**
   * Trata exceções não capturadas. Todas são consideradas falhas críticas.
   *
   * @internal Callback registrado no set_exception_handler do PHP. Não deve ser invocado diretamente pelo utilizador.
   */
  public function handleException(Throwable $exception): void
  {
    $this->processError($exception, Severity::CRITICAL);
  }

  /**
   * Trata erros nativos do PHP e traduz o nível para nossa enumeração de Severidade.
   *
   * @internal Callback registrado no set_error_handler do PHP. Não deve ser invocado diretamente pelo utilizador.
   */
  public function handleError(int $level, string $message, string $file = "", int $line = 0): bool
  {
    if (error_reporting() & $level) {
      error_log($level . ": {$message} in {$file}:{$line}");
      $severity = match ($level) {
        E_NOTICE, E_USER_NOTICE => Severity::NOTICE,
        E_DEPRECATED, E_USER_DEPRECATED => Severity::DEPRECATED,
        E_WARNING, E_USER_WARNING => Severity::WARNING,
        default => Severity::CRITICAL,
      };

      $exception = new ErrorException($message, 0, $level, $file, $line);
      $this->processError($exception, $severity);
    }

    // Retornar true evita que o tratador padrão do PHP jogue o erro na tela (vazando dados)
    return true;
  }

  /**
   * Captura erros que quebram a compilação ou estouram limites (Memory Limit, Syntax Error).
   *
   * @internal Callback registrado no register_shutdown_function do PHP. Não deve ser invocado diretamente pelo utilizador.
   */
  public function handleFatalError(): void
  {
    $error = error_get_last();
    if ($error !== null && in_array($error["type"], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
      $exception = new ErrorException($error["message"], 0, $error["type"], $error["file"], $error["line"]);
      $this->processError($exception, Severity::CRITICAL);
    }
  }

  /**
   * Implementação obrigatória da PSR-3 (LoggerInterface).
   *
   * @param mixed $level Nível do log (ex: LogLevel::ERROR, LogLevel::INFO)
   * @param string|Stringable $message Mensagem a registrar
   * @param array<string, mixed> $context Metadados adicionais
   */
  public function log(mixed $level, string|Stringable $message, array $context = []): void
  {
    $severity = match (strtolower((string)$level)) {
      LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR => Severity::CRITICAL,
      LogLevel::WARNING => Severity::WARNING,
      LogLevel::NOTICE => Severity::NOTICE,
      LogLevel::INFO, LogLevel::DEBUG => Severity::INFO,
      default => Severity::NOTICE,
    };

    if (isset($context['exception']) && $context['exception'] instanceof Throwable) {
      $this->processError($context['exception'], $severity, $context);
      return;
    }

    $this->captureMessage((string)$message, $severity, $context);
  }

  /**
   * Método interno para transformar uma mensagem manual em um contexto válido.
   *
   * @param array<string, mixed> $extraContext
   */
  private function captureMessage(string $message, Severity $severity, array $extraContext = []): void
  {
    // Usa o backtrace para descobrir exatamente qual arquivo e linha da aplicação chamou o método,
    // ignorando frames internos do ErrorTracker e do LoggerTrait do PSR-3.
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $file = 'UNKNOWN_FILE';
    $line = 0;

    foreach ($trace as $frame) {
      $frameFile = $frame['file'] ?? '';
      $normalized = str_replace('\\', '/', (string)$frameFile);
      if ($frameFile !== ''
          && !str_ends_with($normalized, 'src/ErrorTracker.php')
          && !str_contains($normalized, 'psr/log')
      ) {
        $file = (string)$frameFile;
        $line = (int)($frame['line'] ?? 0);
        break;
      }
    }

    // Criamos uma exceção "sintética" para transportar mensagem, arquivo e linha
    $exception = new ErrorException($message, 0, E_USER_NOTICE, $file, $line);

    // Dispara para o fluxo principal de Handlers marcando como evento manual (isException: false)
    $this->processError($exception, $severity, $extraContext, isException: false);
  }

  /**
   * Núcleo de processamento: sanitiza, constrói o contexto, checa deduplicação e despacha.
   *
   * @param array<string, mixed> $extraContext
   */
  private function processError(
      Throwable $exception,
      Severity $severity,
      array $extraContext = [],
      bool $isException = true
  ): void {
    // 1. Coleta e sanitiza os dados (mascarando senhas, CPFs, cartões)
    $rawRequest = [
        'post' => $_POST,
        'get' => $_GET,
        'server' => $this->filterServerData($_SERVER),
    ];

    $mergedContext = array_merge($this->extraContext, $extraContext);

    if (!empty($mergedContext)) {
      $rawRequest['context'] = $mergedContext;
    }

    $requestData = $this->sanitizer->sanitize($rawRequest);

    // 2. Constrói o DTO que agrupa todas as informações (incluindo o Hash MD5)
    $resolvedUser = $mergedContext['userName']
        ?? $mergedContext['user_name']
        ?? $mergedContext['username']
        ?? $mergedContext['user']
        ?? $mergedContext['usuario']
        ?? $mergedContext['usuario_nome']
        ?? null;

    if ($resolvedUser === null && $this->userResolver !== null) {
      try {
        $resolved = ($this->userResolver)();
        if ($resolved !== null && $resolved !== '') {
          $resolvedUser = (string)$resolved;
        }
      } catch (Throwable) {
        // Absorve falhas no resolver customizado para manter a resiliência do rastreamento
      }
    }

    if ($resolvedUser === null && $this->userName !== null) {
      $resolvedUser = $this->userName;
    }

    $context = new ErrorContext(
        $exception,
        $requestData,
        $severity,
        userName: $resolvedUser !== null ? (string)$resolvedUser : null,
        isException: $isException,
        appName: $this->appName,
        environment: $this->environment,
        serverName: $this->serverName
    );

    // 3. Verificação de Deduplicação via Cache
    if ($this->cache !== null) {
      $cacheKey = "error_tracker_{$context->hash}";

      if ($this->cache->has($cacheKey)) {
        // Se já reportou recentemente, silencia o envio, mas ainda aciona a rota de fuga se for crítico.
        $this->triggerCriticalFailsafe($context, $severity);
        return;
      }

      // Grava no cache indicando que já lidamos com este erro
      $this->cache->set($cacheKey, true, $this->cacheTtl);
    }

    // 4. Dispara o erro para os canais (Handlers)
    foreach ($this->handlers as $handler) {
      try {
        $handler->report($context);
      } catch (Throwable $e) {
        // Absorve falhas dos próprios adaptadores para não gerar um loop infinito
        error_log("HeliomarPM ErrorTracker falhou ao executar o handler " . get_class($handler) . ": {$e->getMessage()}");
      }
    }

    // 5. Aciona o escudo final
    $this->triggerCriticalFailsafe($context, $severity);
  }

  /**
   * Aciona o callback final e mata a aplicação se a integridade foi comprometida.
   */
  private function triggerCriticalFailsafe(ErrorContext $context, Severity $severity): void
  {
    if ($severity === Severity::CRITICAL && $this->onCriticalCallback !== null) {
      call_user_func($this->onCriticalCallback, $context);

      // Força a parada do servidor para evitar estragos em banco de dados ou views corrompidas
      exit(1);
    }
  }

  /**
   * Filtra chaves de $_SERVER mantendo apenas cabeçalhos HTTP e variáveis essenciais de requisição,
   * descartando variáveis de ambiente do sistema operacional e potenciais segredos de infraestrutura.
   *
   * @param array<string, mixed> $server
   * @return array<string, mixed>
   */
  private function filterServerData(array $server): array
  {
    $filtered = [];
    foreach ($server as $key => $value) {
      $keyStr = (string)$key;
      if (str_starts_with($keyStr, 'HTTP_') || in_array($keyStr, self::ALLOWED_SERVER_KEYS, true)) {
        $filtered[$key] = $value;
      }
    }
    return $filtered;
  }
}
