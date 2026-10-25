# Guia de Handlers (Canais de Alerta e Persistência)

O **HeliomarPM ErrorTracker** utiliza a arquitetura baseada no padrão *Strategy / Observer*. Cada canal de notificação implementa a interface `HeliomarPM\ErrorTracker\Contracts\HandlerInterface`.

---

## 1. DatabaseHandler (MySQL / MariaDB / SQLite)

Persiste os incidentes em banco de dados relacional e gerencia contadores de reincidência automaticamente (`incidents = incidents + 1` e `updated_at`).

### Inicialização com MySQL:
```php
use HeliomarPM\ErrorTracker\Handlers\DatabaseHandler;

$pdo = new PDO("mysql:host=localhost;dbname=meu_banco;charset=utf8mb4", "root", "senha");
$handler = new DatabaseHandler($pdo, tableName: 'error_tracker', autoCreate: true);

$tracker->addHandler($handler);
```

### Inicialização com SQLite:
```php
$pdo = new PDO("sqlite:" . __DIR__ . "/storage/errors.sqlite");
$handler = new DatabaseHandler($pdo, tableName: 'error_tracker', autoCreate: true);

$tracker->addHandler($handler);
```

* `autoCreate = true`: Cria a tabela e índices automaticamente na primeira execução se ainda não existirem.
* `notifySeverities`: Array com os níveis de severidade aceitos (ex: `[Severity::CRITICAL, Severity::WARNING]`). Se omitido ou `null`, persiste todos os incidentes.

```php
// Exemplo: persistir apenas avisos e falhas críticas
$handler = new DatabaseHandler(
    pdo: $pdo,
    notifySeverities: [Severity::CRITICAL, Severity::WARNING]
);
```

### Estrutura da Tabela e Índices

O handler armazena dados estruturados para permitir agregações analíticas velozes sem depender de parsing de JSON:

| Coluna | Tipo (MySQL) | Tipo (SQLite) | Descrição |
| :--- | :--- | :--- | :--- |
| `id` | `BIGINT AUTO_INCREMENT` | `INTEGER PRIMARY KEY` | Identificador único sequencial |
| `hash` | `VARCHAR(32) UNIQUE` | `VARCHAR(32) UNIQUE` | Hash MD5 do erro (arquivo + linha + mensagem) |
| `severity` | `VARCHAR(20)` | `VARCHAR(20)` | Nível de severidade (`CRITICAL`, `ERROR`, `WARNING`, `INFO`, `DEBUG`) |
| `error_class` | `VARCHAR(150)` | `VARCHAR(150)` | Nome da classe da exceção (ex: `PDOException`, `RuntimeException`) |
| `previous_class`| `VARCHAR(150) NULL` | `VARCHAR(150) NULL` | Classe da causa raiz encadeada (`$e->getPrevious()`) |
| `message` | `TEXT` | `TEXT` | Mensagem do erro |
| `file` | `VARCHAR(255)` | `VARCHAR(255)` | Caminho relativo limpo do arquivo gerador |
| `line` | `INT` | `INT` | Linha do erro |
| `method` | `VARCHAR(10) NULL` | `VARCHAR(10) NULL` | Método HTTP da requisição (`GET`, `POST`, `CLI`, etc.) |
| `uri` | `VARCHAR(255) NULL` | `VARCHAR(255) NULL` | Rota/URI onde ocorreu o incidente |
| `user_name` | `VARCHAR(100) NULL` | `VARCHAR(100) NULL` | Nome ou identificador do usuário autenticado no contexto |
| `trace` | `LONGTEXT` | `TEXT` | Rastreamento de pilha (Stack Trace) legível |
| `payload` | `JSON` | `TEXT` | Payload sanitizado completo em JSON (GET, POST, SERVER, etc.) |
| `created_at` | `DATETIME` | `DATETIME` | Data e hora do primeiro disparo |
| `updated_at` | `DATETIME` | `DATETIME` | Data e hora da última reincidência |
| `incidents` | `INT DEFAULT 1` | `INT DEFAULT 1` | Contador automático de ocorrências |

**Índices de Alta Performance Criados Automaticamente:**
* `idx_severity`: Filtro por criticidade em painéis e relatórios.
* `idx_error_class`: Agrupamento e contagem por tipo de exceção.
* `idx_updated_at`: Ordenação de incidentes recentes.
* `idx_incidents`: Identificação rápida dos gargalos mais reincidentes.

### Consultas Analíticas Úteis

```sql
-- Top 10 erros mais reincidentes
SELECT error_class, message, file, line, incidents, updated_at
FROM error_tracker
ORDER BY incidents DESC
LIMIT 10;

-- Incidentes críticos nas últimas 24 horas
SELECT id, error_class, message, uri, incidents, updated_at
FROM error_tracker
WHERE severity = 'CRITICAL' AND updated_at >= NOW() - INTERVAL 1 DAY
ORDER BY updated_at DESC;

-- Erros agrupados por rota/endpoint da aplicação
SELECT method, uri, COUNT(*) as unique_errors, SUM(incidents) as total_occurrences
FROM error_tracker
WHERE uri IS NOT NULL
GROUP BY method, uri
ORDER BY total_occurrences DESC;
```

---

## 2. WebhookHandler (Slack / Discord / Teams / Genérico)

Envia notificações estruturadas com cards/embeds diretamente para canais de ChatOps.

### Exemplo para Slack:
```php
use HeliomarPM\ErrorTracker\Handlers\WebhookHandler;
use HeliomarPM\ErrorTracker\Severity;

$handler = new WebhookHandler(
    webhookUrl: 'https://hooks.slack.com/services/...',
    notifySeverities: [Severity::CRITICAL, Severity::WARNING],
    format: 'slack',
    timeoutSeconds: 3
);

$tracker->addHandler($handler);
```

### Exemplo para Discord:
```php
$handler = new WebhookHandler(
    webhookUrl: 'https://discord.com/api/webhooks/...',
    format: 'discord'
);

$tracker->addHandler($handler);
```

---

## 3. GitHubIssueHandler

Abre issues automaticamente no repositório GitHub da equipe com títulos formatados, tags de severidade e detalhes recolhíveis em Markdown.

```php
use HeliomarPM\ErrorTracker\Handlers\GitHubIssueHandler;
use HeliomarPM\ErrorTracker\Severity;

$handler = new GitHubIssueHandler(
    token: 'github_pat_...',
    repository: 'heliomarpm/meu-projeto',
    application: 'MeuApp Produção',
    notifySeverities: [Severity::CRITICAL, Severity::WARNING], // padrão: [CRITICAL, WARNING]
    timeoutSeconds: 3
);

$tracker->addHandler($handler);
```

---

## 4. EmailHandler

Dispara e-mails de alerta formatados em **HTML responsivo** ou **Texto Puro** usando a função nativa `mail()`.

```php
use HeliomarPM\ErrorTracker\Handlers\EmailHandler;
use HeliomarPM\ErrorTracker\Severity;

$handler = new EmailHandler(
    to: 'devops@empresa.com',
    from: 'alertas@empresa.com',
    notifySeverities: [Severity::CRITICAL],
    format: 'html', // ou 'text'
    subjectTemplate: '🚨 [{{severity}}] Falha no Sistema: {{message}}'
);

$tracker->addHandler($handler);
```

---

## 5. FilelogHandler

Grava logs estruturados em disco com rotação diária automática por severidade e bloqueio exclusivo concorrente (`LOCK_EX`).
Por padrão, utiliza o template `filelog.tpl`, gerando cabeçalhos de diagnóstico limpos (tipo de erro, causa raiz encadeada, usuário, memória, hash e stack trace simplificado).

* `notifySeverities`: Níveis gravados em disco (ex: `[Severity::CRITICAL, Severity::WARNING]`). Se omitido ou `null`, grava todas as severidades.

```php
use HeliomarPM\ErrorTracker\Handlers\FilelogHandler;
use HeliomarPM\ErrorTracker\Severity;

// Gravar apenas falhas críticas e avisos
$handler = new FilelogHandler(
    logDirectory: __DIR__ . '/storage/logs',
    notifySeverities: [Severity::CRITICAL, Severity::WARNING]
);
$tracker->addHandler($handler);
```

Você também pode fornecer um template customizado:
```php
$handler = new FilelogHandler(
    logDirectory: __DIR__ . '/storage/logs',
    templateOrPath: __DIR__ . '/templates/custom_log.tpl'
);
```

---

## 6. Criando um Handler Customizado

Implemente `HandlerInterface`:

```php
namespace App\ErrorHandlers;

use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;

class TelegramHandler implements HandlerInterface
{
    public function report(ErrorContext $context): void
    {
        $payload = [
            'chat_id' => '-100123456789',
            'text' => "🚨 [{$context->severity->name}] {$context->message}\nArquivo: {$context->file}:{$context->line}",
        ];
        // Envio HTTP...
    }
}
```

---

## 🔗 Veja Também

* [Guia de Templates e Customização](./TEMPLATES.md)
* [README Principal](../README.md)
* [Guia de Contribuição](./CONTRIBUTING.md)
