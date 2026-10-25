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

$handler = new GitHubIssueHandler(
    token: 'github_pat_...',
    repository: 'heliomarpm/meu-projeto',
    application: 'MeuApp Produção',
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

```php
use HeliomarPM\ErrorTracker\Handlers\FilelogHandler;

$handler = new FilelogHandler(__DIR__ . '/storage/logs');
$tracker->addHandler($handler);
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
