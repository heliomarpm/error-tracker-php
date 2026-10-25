<?php

namespace HeliomarPM\ErrorTracker;

use ErrorException;
use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Psr\SimpleCache\CacheInterface;
use Stringable;
use Throwable;

class ErrorTracker implements LoggerInterface
{
  use LoggerTrait;

  /** @var HandlerInterface[] */
  private array $handlers = [];
  private DataSanitizer $sanitizer;
  private ?CacheInterface $cache;
  private int $cacheTtl;

  /** @var callable|null */
  private $onCriticalCallback = null;

  /**
   * Inicializa o rastreador.
   *
   * @param CacheInterface|null $cache Instância de cache (PSR-16) para evitar flood de alertas.
   * @param int $cacheTtlSeconds Tempo em segundos que um erro idêntico será silenciado (padrão 1 hora).
   */
  public function __construct(?CacheInterface $cache = null, int $cacheTtlSeconds = 3600)
  {
    $this->sanitizer = new DataSanitizer();
    $this->cache = $cache;
    $this->cacheTtl = $cacheTtlSeconds;
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
   * Assume o controle das funções nativas de tratamento de erro do PHP.
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
   */
  public function handleException(Throwable $exception): void
  {
    $this->processError($exception, Severity::CRITICAL);
  }

  /**
   * Trata erros nativos do PHP e traduz o nível para nossa enumeração de Severidade.
   */
  public function handleError(int $level, string $message, string $file = "", int $line = 0): bool
  {
    error_log($level . ": {$message} in {$file}:{$line}");
    if (error_reporting() & $level) {
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
    // Usa o backtrace para descobrir exatamente qual arquivo e linha chamou o método
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = $trace[1] ?? ($trace[0] ?? []);
    $file = (string)($caller['file'] ?? 'UNKNOWN_FILE');
    $line = (int)($caller['line'] ?? 0);

    // Criamos uma exceção "falsa" apenas para gerar o stack trace completo e estruturado
    $exception = new ErrorException($message, 0, E_USER_NOTICE, $file, $line);

    // Dispara para o fluxo principal de Handlers
    $this->processError($exception, $severity, $extraContext);
  }

  /**
   * Núcleo de processamento: sanitiza, constrói o contexto, checa deduplicação e despacha.
   *
   * @param array<string, mixed> $extraContext
   */
  private function processError(Throwable $exception, Severity $severity, array $extraContext = []): void
  {
    // 1. Coleta e sanitiza os dados (mascarando senhas, CPFs, cartões)
    $rawRequest = [
        'post' => $_POST,
        'get' => $_GET,
        'server' => $_SERVER,
    ];

    if (!empty($extraContext)) {
      $rawRequest['context'] = $extraContext;
    }

    $requestData = $this->sanitizer->sanitize($rawRequest);

    // 2. Constrói o DTO que agrupa todas as informações (incluindo o Hash MD5)
    $context = new ErrorContext($exception, $requestData, $severity);

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

    // 4. Dispara o erro para os canais (Adapters)
    error_log("HeliomarPM ReportAdpter iniciando para erro: {$context->hash}");
    foreach ($this->handlers as $handler) {
      try {
        $handler->report($context);
      } catch (Throwable $e) {
        // Absorve falhas dos próprios adaptadores para não gerar um loop infinito
        error_log("HeliomarPM ErrorTracker falhou ao executar o adaptador " . get_class($handler) . ": {$e->getMessage()}");
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
}
