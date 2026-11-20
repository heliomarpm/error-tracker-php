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

  /** @var list<string> */
  private array $ignoredCallers = [];

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
   * Factory method para inicialização fluente e de fácil implementação.
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
  public static function create(
      ?CacheInterface $cache = null,
      int $cacheTtlSeconds = 3600,
      ?string $userName = null,
      ?callable $userResolver = null,
      string $appName = 'ErrorTracker',
      string $environment = 'production',
      ?string $serverName = null,
      array $extraContext = [],
      array $maskedKeys = []
  ): self {
    return new self(
        cache: $cache,
        cacheTtlSeconds: $cacheTtlSeconds,
        userName: $userName,
        userResolver: $userResolver,
        appName: $appName,
        environment: $environment,
        serverName: $serverName,
        extraContext: $extraContext,
        maskedKeys: $maskedKeys
    );
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
   * Define ou atualiza os parâmetros globais da aplicação compartilhados com todos os handlers via Named Arguments.
   *
   * Exemplo:
   *   $tracker->parameters(
   *       appName: 'ERP Sistema',
   *       environment: 'production',
   *       userResolver: fn() => $_SESSION['usuario']['nome'] ?? null
   *   );
   *
   * @param string|null $appName Nome da aplicação.
   * @param string|null $environment Ambiente de execução (production, staging, dev, etc.).
   * @param string|null $serverName Nome do host/servidor.
   * @param string|null $userName Nome ou identificador estático do usuário padrão.
   * @param (callable(): (string|Stringable|null))|null $userResolver Callback dinâmico para resolver o usuário ativo.
   * @param array<string, mixed> $extraContext Metadados extras compartilhados.
   * @param array<string> $maskedKeys Chaves adicionais personalizadas para blackout.
   */
  public function parameters(
      ?string $appName = null,
      ?string $environment = null,
      ?string $serverName = null,
      ?string $userName = null,
      ?callable $userResolver = null,
      array $extraContext = [],
      array $maskedKeys = []
  ): self {
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
   * Adiciona caminhos de arquivos, classes ou padrões para ignorar no backtrace ao determinar a origem da mensagem manual.
   */
  public function addIgnoredCaller(string $classOrPath): self
  {
    $normalized = str_replace('\\', '/', $classOrPath);
    if (!in_array($normalized, $this->ignoredCallers, true)) {
      $this->ignoredCallers[] = $normalized;
    }

    return $this;
  }

  /**
   * Retorna os callers configurados para serem ignorados na detecção da origem.
   *
   * @return list<string>
   */
  public function getIgnoredCallers(): array
  {
    return $this->ignoredCallers;
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
   * Assume o controle dos tratadores nativos de erro e exceção do PHP.
   * As configurações e parâmetros devem ser definidos no construtor ou via parameters().
   */
  public function register(): void
  {
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
    $file = 'UNKNOWN_FILE';
    $line = 0;

    if (!empty($extraContext['file']) && is_string($extraContext['file'])) {
      $file = $extraContext['file'];
      $line = isset($extraContext['line']) ? (int)$extraContext['line'] : 0;
    } else {
      // Usa o backtrace para descobrir exatamente qual arquivo e linha da aplicação chamou o método,
      // ignorando frames internos do ErrorTracker, do LoggerTrait do PSR-3 e wrappers configurados.
      $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

      foreach ($trace as $frame) {
        $frameFile = $frame['file'] ?? '';
        $normalized = str_replace('\\', '/', (string)$frameFile);
        if ($frameFile === '') {
          continue;
        }

        if (str_ends_with($normalized, 'src/ErrorTracker.php')
            || str_contains($normalized, 'psr/log')
        ) {
          continue;
        }

        $isIgnored = false;
        foreach ($this->ignoredCallers as $ignored) {
          if ($ignored !== '' && str_contains($normalized, $ignored)) {
            $isIgnored = true;
            break;
          }
        }

        if ($isIgnored) {
          continue;
        }

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
    $resolvedUser = $mergedContext['user']
        ?? $mergedContext['userName']
        ?? $mergedContext['user_name']
        ?? $mergedContext['username']
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
   * Aciona o callback de emergência quando a severidade é CRÍTICA.
   *
   * O callback recebe o ErrorContext e tem total controle sobre a resposta:
   * pode redirecionar, retornar JSON de erro ou chamar exit() explicitamente.
   * A decisão de encerrar o processo fica a cargo do consumidor.
   */
  private function triggerCriticalFailsafe(ErrorContext $context, Severity $severity): void
  {
    if ($severity === Severity::CRITICAL && $this->onCriticalCallback !== null) {
      call_user_func($this->onCriticalCallback, $context);
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
