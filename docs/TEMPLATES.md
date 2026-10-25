# Guia de Templates e Customização de Mensagens

O **HeliomarPM ErrorTracker** permite que você desacople o conteúdo das mensagens das classes PHP, utilizando arquivos de templates externos ou closures formatadoras.

---

## 1. Tags Disponíveis para Interpolação

Qualquer template ou formato que utilize `TemplateRenderer` suporta as seguintes tags:

| Tag | Descrição | Exemplo |
| :--- | :--- | :--- |
| `{{timestamp}}` | Data e hora atual da ocorrência | `2026-10-25 14:30:00` |
| `{{severity}}` | Nome da severidade em maiúsculas | `CRITICAL`, `WARNING`, `NOTICE` |
| `{{severity_value}}` | Valor da severidade em minúsculas | `critical`, `warning`, `notice` |
| `{{message}}` | Mensagem do erro ou exceção | `Call to undefined method...` |
| `{{file}}` | Caminho do arquivo onde o erro ocorreu | `/var/www/src/Service.php` |
| `{{line}}` | Número da linha da ocorrência | `42` |
| `{{code}}` | Código numérico ou string da exceção | `500` |
| `{{trace}}` | Stack trace formatado | `#0 /app/index.php...` |
| `{{payload}}` | Dados de request sanitizados em JSON | `{"post":{"user":"admin"...}}` |
| `{{hash}}` | Assinatura única (MD5) do erro | `eba8872610b3e3188d4da1c5c714cdcf` |

---

## 2. Usando um Template de Arquivo Customizado

### No FilelogHandler:
```php
use HeliomarPM\ErrorTracker\Handlers\FilelogHandler;

$handler = new FilelogHandler(
    logDirectory: __DIR__ . '/storage/logs',
    templateOrPath: __DIR__ . '/config/custom_filelog.tpl'
);
```

### No EmailHandler:
```php
use HeliomarPM\ErrorTracker\Handlers\EmailHandler;

$handler = new EmailHandler(
    to: 'dev@empresa.com',
    format: 'html',
    templateOrPath: __DIR__ . '/templates/alerta_customizado.html'
);
```

---

## 3. Usando uma Closure Formatadora Customizada

Se você deseja controle total via código PHP para formatar a mensagem:

```php
use HeliomarPM\ErrorTracker\ErrorContext;
use HeliomarPM\ErrorTracker\Handlers\FilelogHandler;

$handler = new FilelogHandler(
    logDirectory: __DIR__ . '/storage/logs',
    formatter: function (ErrorContext $context): string {
        return json_encode([
            '@timestamp' => date('c'),
            'level' => $context->severity->name,
            'err' => $context->message,
            'location' => "{$context->file}:{$context->line}",
            'fingerprint' => $context->hash,
        ]);
    }
);
```

---

## 🔗 Veja Também

* [Guia de Handlers e Canais](./HANDLERS.md)
* [README Principal](../README.md)
* [Guia de Contribuição](./CONTRIBUTING.md)
