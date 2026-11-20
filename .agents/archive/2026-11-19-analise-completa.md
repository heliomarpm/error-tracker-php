# 🔬 Análise Completa — `heliomarpm/error-tracker`

> **Objetivo:** Identificar código redundante, code smells, lógica obsoleta e barreiras à adoção fácil da biblioteca por consumidores externos.
>
> **Status:** Arquivada (Concluída em 2026-11-20)
> **Commits relacionados:** `0f18a55`, `d022b66`, `562c051`, `b1925df`, `631b81a`
> **Tarefa no backlog:** [`T-029`](../TASKS.md)

---

## Sumário Executivo

A biblioteca tem uma **arquitetura sólida**: contrato `HandlerInterface` bem definido, sanitização via `DataSanitizer`, tipagem estrita, sistema de templates flexível e integração PSR-3/PSR-16. Porém, há **camadas de redundância acumulada** na resolução de usuário, **aliases excessivos** no template renderer e no método `parameters()`, e algumas decisões de design que **aumentam a superfície de atrito** para quem consome a biblioteca. Abaixo está a análise organizada por eixo.

---

## 1. Redundância Crítica: Resolução de Usuário em 3 Camadas

> [!CAUTION]
> A resolução de usuário está espalhada em **3 locais independentes**, cada um com sua própria lista de chaves e heurísticas. Isso gera duplicação de lógica, inconsistência e comportamento difícil de prever.

### Onde acontece

| Local | Arquivo | Linhas | O que faz |
|-------|---------|--------|-----------|
| **Camada 1** | [ErrorTracker.php](../../src/ErrorTracker.php#L548-L569) | 548–569 | Resolve user de `$mergedContext`, `$userResolver` e `$userName` |
| **Camada 2** | [ErrorContext.php](../../src/ErrorContext.php#L126-L154) | 126–154 | Resolve user **de novo** do `$sanitizedRequest['context']`, de `$_SESSION` e via `resolveUserString()` |
| **Camada 3** | `ResolvesUserTrait.php` (removido) | 24–47 | Resolve user **de novo** no handler com `$context->userName`, `$userResolver` e `$userName` locais |

### Problemas concretos

1. **ErrorTracker** resolve o user e passa para `ErrorContext` via `$userName`.
2. **ErrorContext** ignora parcialmente o que já foi resolvido e faz lookup próprio em `$sanitizedRequest['context']` e `$_SESSION` com listas de chaves **diferentes**.
3. **ResolvesUserTrait** nos handlers permite sobrescrever tudo de novo com `$userResolver` e `$userName` locais.

**Resultado:** Se o consumidor configura `userResolver` no `ErrorTracker` mas o handler tem seu próprio `userResolver`, o do handler pode sobrescrever silenciosamente. As 3 camadas verificam chaves como `userName`, `user_name`, `username`, `user`, `usuario`, `usuario_nome` — mas cada uma verifica subconjuntos **ligeiramente diferentes**.

### Recomendação

```
ErrorTracker (resolve UMA vez) → ErrorContext (recebe user já resolvido, sem re-lookup)
                                 → Handlers (usam context->userName, sem re-resolver)
```

- **Eliminar** a resolução de user dentro de `ErrorContext.__construct()` (linhas 126–154) — o `ErrorTracker` já resolve tudo antes de instanciá-lo.
- **Depreciar** `$userName` / `$userResolver` nos handlers. Manter temporariamente para retrocompatibilidade, mas documentar que o caminho recomendado é configurar no `ErrorTracker`.
- **Centralizar** a lista canônica de chaves de user em `ErrorContext::resolveUserString()`.

---

## 2. Método `parameters()` — Excesso de Aliases e Complexidade

> [!WARNING]
> O método `parameters()` em [ErrorTracker.php L138–208](../../src/ErrorTracker.php#L138-L208) aceita **ambos** named arguments **e** array associativo, com aliases em 3 idiomas (`environment`/`env`/`ambiente`, `user_name`/`userName`/`user`/`usuario`).

### Code Smells

| Smell | Detalhe |
|-------|---------|
| **God Method** | 70 linhas com 2 paths (array vs named args) fazendo exatamente a mesma coisa |
| **Multi-idioma desnecessário** | `ambiente`, `usuario`, `servidor` — uma lib publicada em inglês no Packagist deve ter API em inglês |
| **Duplicação** | A branch `is_array()` é uma cópia da branch named-arguments com lógica extra de aliases |
| **Fragilidade** | Adicionar uma nova propriedade exige alterar 2 branches |

### Recomendação

Simplificar o `parameters()` para aceitar **apenas named arguments** (PHP 8.1+ já é o mínimo). Se quiser manter o array para migração, criar método separado `fromArray()` deprecado:

```php
// API limpa — apenas named arguments
public function parameters(
    ?string $appName = null,
    ?string $environment = null,
    ?string $serverName = null,
    ?string $userName = null,
    ?callable $userResolver = null,
    array $extraContext = [],
    array $maskedKeys = []
): self { /* setters diretos */ }
```

---

## 3. Duplicação `register()` ↔ `parameters()`

> [!NOTE]
> O método `register()` em [ErrorTracker.php L373–390](../../src/ErrorTracker.php#L373-L390) aceita exatamente os **mesmos parâmetros** que `parameters()` e os delega para ele. Sua única responsabilidade real é chamar `set_exception_handler()`, `set_error_handler()` e `register_shutdown_function()`.

### Recomendação

`register()` não deveria aceitar parâmetros de configuração. Manter a assinatura limpa:

```php
public function register(): void
{
    error_reporting(E_ALL);
    set_exception_handler([$this, 'handleException']);
    set_error_handler([$this, 'handleError']);
    register_shutdown_function([$this, 'handleFatalError']);
}
```

Configuração deve ser feita antes, via `__construct()` ou `parameters()`.

---

## 4. `ErrorContext` — Acoplamento a Superglobais e God Constructor

> [!WARNING]
> O construtor de [ErrorContext.php](../../src/ErrorContext.php#L47-L158) tem **112 linhas** e acessa diretamente `$_SERVER`, `$_SESSION` e `$GLOBALS`. Isso cria:

### Problemas

1. **Impossível testar unitariamente** sem manipular superglobais
2. **Acoplamento forte** — O DTO "puro" na verdade resolve lógica de negócio
3. **Re-resolução de user** (já discutido no item 1)
4. **Acesso a `$_SESSION`** sem `session_status()` — pode gerar notice
5. **`gethostname()`**, `getenv()` chamados no construtor — efeitos colaterais no DTO

### Responsabilidades que o `ErrorContext` **não deveria ter**

| Responsabilidade | Deveria estar em |
|-----------------|------------------|
| Resolução de `$_SERVER['REQUEST_METHOD']`, etc. | `ErrorTracker::processError()` (já coleta request) |
| Lookup de user em `$_SESSION` | `ErrorTracker` via `userResolver` |
| `memory_get_peak_usage()` | `ErrorTracker` antes de construir o contexto |
| `gethostname()`, `getenv('APP_ENV')` | `ErrorTracker` (configuração global) |

### Recomendação

`ErrorContext` deveria ser um **DTO puro** que recebe todos os dados já resolvidos, sem fazer lookup em superglobais:

```php
public function __construct(
    Throwable $e,
    array $sanitizedRequest,
    Severity $severity,
    string $appName,
    string $environment,
    string $serverName,
    string $requestMethod,
    string $requestUri,
    string $clientIp,
    ?string $userName = null,
    ?string $referer = null,
    ?string $memoryUsage = null,
    bool $isException = true,
) { /* apenas atribuições */ }
```

---

## 5. `ErrorContext::resolveUserString()` — Excesso de Heurísticas

> [!NOTE]
> O método estático [resolveUserString()](../../src/ErrorContext.php#L164-L224) com 60 linhas tenta resolver user de **qualquer tipo**: string, int, Stringable, array com 10 chaves possíveis, objeto com 3 métodos + 10 propriedades.

### Code Smells

- **Feature Envy**: esse método tenta ser o "adaptador universal de user". Para uma biblioteca, basta aceitar `?string` e deixar o consumidor fazer a conversão.
- **Manutenção**: cada framework/projeto pode ter convenções diferentes de user. A lib não deveria assumir todas.
- **Duplicação de chaves**: as mesmas 10 chaves (`userName`, `user_name`, `username`, `nome`, `name`, `login`, `email`, `user`, `usuario`, `id`) aparecem no array e no objeto.

### Recomendação

Para o consumidor, fornecer `userResolver: fn() => (string)Auth::user()->name` é **mais simples** do que a lib adivinhar. Reduzir `resolveUserString()` para aceitar apenas `string|Stringable|null`:

```php
public static function resolveUserString(mixed $user): ?string
{
    if ($user === null) return null;
    if (is_string($user) || $user instanceof Stringable || is_numeric($user)) {
        $s = trim((string)$user);
        return $s !== '' ? $s : null;
    }
    return null;
}
```

---

## 6. TemplateRenderer — Excesso de Aliases nas Tags

> [!NOTE]
> O [TemplateRenderer.php L56–122](../../src/Formatters/TemplateRenderer.php#L56-L122) define **66 tags** onde muitas são aliases puros:

| Valor real | Aliases redundantes |
|-----------|---------------------|
| `$context->appName` | `app_name`, `appName`, `application` |
| `$context->environment` | `environment`, `ambiente`, `env` |
| `$context->requestMethod` | `request_method`, `method` |
| `$context->requestUri` | `request_uri`, `uri` |
| `$context->serverName` | `server_name`, `server` |
| `$context->memoryUsage` | `memory_usage`, `memory` |
| `$context->exceptionClass` | `error_title`, `error_class`, `class` |
| `$context->message` | `message`, `error_message` |
| `$context->line` | `line`, `error_line` |
| `$context->file` | `file`, `error_file` (via `relativeFile`) |
| `$context->trace` | `trace`, `stack_trace` |
| `$context->userName` | `usuario_nome`, `user`, `user_name` |
| `$context->clientIp` | `client_ip`, `ip` |
| `$context->sqlQuery` | `sql`, `sql_query` |

### Recomendação

Manter **uma forma canônica** por tag e documentar. Se um template oficial usa `{{error_file}}`, não precisa de `{{relative_file}}` como alias. Isso reduz a manutenção e simplifica a documentação de templates customizados.

---

## 7. TemplateRenderer — Condicionais Excessivas

> [!NOTE]
> O [renderConditionals()](../../src/Formatters/TemplateRenderer.php#L141-L183) tem um `match` com **~20 condicionais hardcoded** incluindo aliases:

```
EXCEPTION, IS_EXCEPTION
NOT_EXCEPTION, MESSAGE_ONLY, IS_MESSAGE
TRACE, STACK_TRACE, HAS_TRACE
APP_NAME, APPLICATION
ENVIRONMENT, AMBIENTE, ENV
PRODUCTION, PROD, ENV_PROD
STAGING, ENV_STAGING
DEVELOPMENT, DEV, ENV_DEV, LOCAL
SQL, HAS_SQL
USER, USUARIO, USUARIO_NOME
```

### Problema

Adicionar um novo dado contextual exige alterar **código PHP** em vez de simplesmente usar `{{nova_tag}}` e `<!-- IF_NOVA_TAG -->`. O mecanismo de fallback `default => !empty($tags[strtolower($cond)])` já resolve isso — os cases explícitos que apenas fazem `!empty($tags[...])` são redundantes.

### Recomendação

Manter explícitos apenas os que têm lógica especial (ex: `PRODUCTION` que checa `in_array()`). Remover aliases e cases que o fallback `default` já cobre.

---

## 8. Handlers — Duplicação de `resolveExtraTags()`

> [!NOTE]
> O padrão abaixo se repete em **3 handlers** (EmailHandler, FilelogHandler, GitHubIssueHandler):

```php
$userName = $this->resolveUser($context);
if ($userName !== null) {
    $extraTags['usuario_nome'] = $userName;
    $extraTags['user'] = $userName;
    $extraTags['user_name'] = $userName;
}
```

**Locais:**
- [EmailHandler.php L174–180](../../src/Handlers/EmailHandler.php#L174-L180)
- [FilelogHandler.php L75–81](../../src/Handlers/FilelogHandler.php#L75-L81)
- [GitHubIssueHandler.php L129–135](../../src/Handlers/GitHubIssueHandler.php#L129-L135)

### Recomendação

Mover para o `ResolvesUserTrait` como método `resolveExtraTags()`:

```php
protected function resolveUserTags(ErrorContext $context): array
{
    $userName = $this->resolveUser($context);
    if ($userName === null) return [];
    return ['user' => $userName, 'user_name' => $userName, 'usuario_nome' => $userName];
}
```

---

## 9. Handlers — Filtro de Severidade Duplicado

> [!NOTE]
> As mesmas 3 linhas de filtro de severidade se repetem em **todos os 5 handlers**:

```php
if ($this->notifySeverities !== null && !in_array($context->severity, $this->notifySeverities, true)) {
    return;
}
```

**Locais:**
- [DatabaseHandler.php L46–48](../../src/Handlers/DatabaseHandler.php#L46-L48)
- [EmailHandler.php L74–76](../../src/Handlers/EmailHandler.php#L74-L76)
- [FilelogHandler.php L53–55](../../src/Handlers/FilelogHandler.php#L53-L55)
- [GitHubIssueHandler.php L89–91](../../src/Handlers/GitHubIssueHandler.php#L89-L91)
- [WebhookHandler.php L42–44](../../src/Handlers/WebhookHandler.php#L42-L44)

### Recomendação

Duas opções:

**Opção A — Abstract Handler (recomendada):** Criar `AbstractHandler` com método `shouldReport()`:

```php
abstract class AbstractHandler implements HandlerInterface
{
    use ResolvesUserTrait;
    
    public function __construct(
        protected readonly ?array $notifySeverities = null,
        protected readonly ?string $userName = null,
        protected $userResolver = null,
    ) {}
    
    protected function shouldReport(ErrorContext $context): bool
    {
        return $this->notifySeverities === null 
            || in_array($context->severity, $this->notifySeverities, true);
    }
}
```

**Opção B — Manter interface.** Adicionar o filtro no `ErrorTracker::processError()` e passar a severidade como configuração global por handler (menos flexível).

---

## 10. `ErrorTracker` — Setters Individuais Redundantes

> [!NOTE]
> O `ErrorTracker` tem **8 setters e 6 getters individuais** ([L272–341](../../src/ErrorTracker.php#L272-L341)) que fazem exatamente o que `parameters()` já faz. Somando o construtor (que aceita os mesmos parâmetros) e `register()`, existem **4 formas de configurar** as mesmas propriedades.

### API surface atual para setar `appName`:

1. `new ErrorTracker(appName: 'X')`
2. `$tracker->parameters(appName: 'X')`
3. `$tracker->parameters(['app_name' => 'X'])`
4. `$tracker->setAppName('X')`
5. `$tracker->register(appName: 'X')`

### Recomendação

Manter apenas:
1. **Construtor** para configuração inicial
2. **`parameters()`** para reconfiguração fluente (sem a branch array)
3. **Remover** setters/getters individuais que não agregam

Se retrocompatibilidade importa, depreciar com `@deprecated Use parameters() instead.`

---

## 11. `triggerCriticalFailsafe()` — `exit(1)` Hardcoded

> [!WARNING]
> O [triggerCriticalFailsafe()](../../src/ErrorTracker.php#L613-L621) chama `exit(1)` incondicionalmente após o callback `onCritical`. Isso:

1. **Impede testes** — PHPUnit morre junto
2. **Remove controle do consumidor** — O callback `onCritical` já poderia chamar `exit()` se quisesse
3. **Quebra integração** — Frameworks como Laravel/Symfony têm seu próprio lifecycle de shutdown

### Recomendação

Remover o `exit(1)`. O consumidor que quiser parar a execução faz:

```php
$tracker->onCritical(function (ErrorContext $ctx) {
    http_response_code(500);
    include '500.html';
    exit(1);
});
```

---

## 12. `HandlerInterface` — Falta `declare(strict_types=1)`

> [!NOTE]
> [HandlerInterface.php](../../src/Contracts/HandlerInterface.php) é o único arquivo `src/` sem `declare(strict_types=1)`.

```php
// Falta:
declare(strict_types=1);

namespace HeliomarPM\ErrorTracker\Contracts;
```

### Fix
Adicionar `declare(strict_types=1);` na primeira linha.

---

## 13. `EmailHandler` — `SmtpTransport` Recriado a Cada Report

> [!NOTE]
> O [EmailHandler.php L126–133](../../src/Handlers/EmailHandler.php#L126-L133) instancia `new SmtpTransport(...)` **a cada chamada** de `report()`, mesmo com os mesmos parâmetros de configuração.

### Recomendação

Instanciar o `SmtpTransport` no construtor e reutilizá-lo:

```php
private readonly ?SmtpTransport $smtpTransport;

public function __construct(...) {
    $this->smtpTransport = $smtpHost ? new SmtpTransport(...) : null;
}
```

---

## 14. `TemplateRenderer` — Instanciado em 3 Handlers

> [!NOTE]
> `new TemplateRenderer()` é criado no construtor de `EmailHandler`, `FilelogHandler` e `GitHubIssueHandler`. Como é stateless (apenas usa um cache estático), poderia ser compartilhado.

### Recomendação

Se adotar o `AbstractHandler`, injetá-lo lá:

```php
protected readonly TemplateRenderer $renderer;
```

---

## 15. `SmtpTransport` — Propriedades `public readonly` Sensíveis

> [!WARNING]
> [SmtpTransport.php L18–25](../../src/Transports/SmtpTransport.php#L18-L25) expõe `$password` como `public readonly`. Qualquer código com acesso ao objeto pode ler a senha SMTP.

```php
public readonly string $password = "",  // ⚠️ Deveria ser private
```

### Fix
Mudar para `private readonly string $password`.

---

## 16. Barreiras à Adoção Fácil

### 16.1 Setup mínimo é verboso demais

Hoje, o setup mínimo funcional é:

```php
$tracker = new ErrorTracker();
$tracker->addHandler(new FilelogHandler('/tmp/logs'));
$tracker->register(
    appName: 'Meu App',
    environment: 'production',
);
```

**Poderia ser** (com factory method):

```php
ErrorTracker::create(appName: 'Meu App')
    ->addHandler(new FilelogHandler('/tmp/logs'))
    ->register();
```

### 16.2 Muitos parâmetros no construtor do EmailHandler

[EmailHandler](../../src/Handlers/EmailHandler.php#L43-L69) tem **17 parâmetros** no construtor. Considerar um `SmtpConfig` DTO:

```php
$smtp = new SmtpConfig(host: 'smtp.example.com', port: 587, user: 'noreply@...');
$handler = new EmailHandler(to: 'team@...', smtp: $smtp);
```

### 16.3 `FileCache` como cache padrão quando nenhum é fornecido

Se o consumidor não passa cache ao `ErrorTracker`, o anti-flood fica desabilitado silenciosamente. Considerar usar `FileCache` como default:

```php
$this->cache = $cache ?? new FileCache();
```

---

## 17. Checklist de Code Smells

| Smell | Severidade | Localização |
|-------|-----------|-------------|
| Resolução de user triplicada | 🔴 Alta | ErrorTracker, ErrorContext, ResolvesUserTrait |
| `exit(1)` hardcoded | 🔴 Alta | ErrorTracker.php L619 |
| `password` exposto como `public` | 🔴 Alta | SmtpTransport.php L22 |
| `parameters()` God Method (70 LOC) | 🟡 Média | ErrorTracker.php L138–208 |
| Aliases multi-idioma na API | 🟡 Média | parameters(), TemplateRenderer |
| `ErrorContext` God Constructor (112 LOC) | 🟡 Média | ErrorContext.php L47–158 |
| Filtro de severidade copiado 5x | 🟡 Média | Todos os handlers |
| `resolveExtraTags()` copiado 3x | 🟡 Média | Email, Filelog, GitHub handlers |
| `SmtpTransport` recriado a cada report | 🟢 Baixa | EmailHandler.php L126 |
| `TemplateRenderer` instanciado 3x | 🟢 Baixa | Email, Filelog, GitHub handlers |
| 4 caminhos para setar `appName` | 🟢 Baixa | ErrorTracker |
| Falta `strict_types` em `HandlerInterface` | 🟢 Baixa | Contracts/HandlerInterface.php |

---

## 18. Plano de Ação Sugerido

> [!IMPORTANT]
> Recomendação por prioridade de impacto para a experiência do consumidor:

### Fase 1 — Quick wins (sem breaking changes)
1. ✅ Adicionar `declare(strict_types=1)` ao `HandlerInterface`
2. ✅ Mudar `SmtpTransport::$password` para `private`
3. ✅ Remover `exit(1)` do `triggerCriticalFailsafe()` — deixar controle para o `onCritical` callback
4. ✅ Instanciar `SmtpTransport` no construtor do `EmailHandler` (evoluído na Fase 3 para injeção direta de `?SmtpTransport`)
5. ✅ Eliminar `ResolvesUserTrait` e centralizar resolução única de usuário

### Fase 2 — Simplificação de API (minor version bump)
6. ✅ Remover branch array e aliases multi-idioma do `parameters()` + remover getters/setters individuais redundantes
7. ✅ Remover parâmetros de configuração do `register()` (`register(): void`)
8. ✅ Adicionar `ErrorTracker::create()` factory method
9. ✅ Manter cache PSR-16 opcional com `FileCache` documentado no setup rápido (evitando I/O implícito inesperado)

### Fase 3 — Refatoração estrutural (consolidada)
10. ✅ Limpar `ErrorContext` removendo lookup em `$_SESSION`, reflexão de objetos e heurísticas mágicas de SQL
11. ✅ Unificar resolução de user no `ErrorTracker`
12. ✅ Introduzir `AbstractHandler` com `shouldReport()` e `renderer()` compartilhado entre os 5 handlers
13. ✅ Reduzir aliases no `TemplateRenderer` para forma canônica
14. ✅ Remover `resolveUserString()` aceitando string/contexto explícito no `ErrorTracker`
15. ✅ Desacoplar configuração SMTP do `EmailHandler` injetando `?SmtpTransport` diretamente

---

> *Análise e plano de ação concluídos e verificados em 2026-11-20 (PHPUnit: 93 testes/299 asserções, PHPStan: 0 erros, Pint: aprovado).*
