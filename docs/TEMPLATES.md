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
| `{{error_title}}` | Nome da classe da exceção lançada | `RuntimeException`, `PDOException` |
| `{{message}}` / `{{error_message}}` | Mensagem do erro ou exceção | `Call to undefined method...` |
| `{{file}}` / `{{error_file}}` | Caminho do arquivo onde o erro ocorreu | `/var/www/src/Service.php` |
| `{{line}}` / `{{error_line}}` | Número da linha da ocorrência | `42` |
| `{{code}}` | Código numérico ou string da exceção | `500` |
| `{{previous_class}}` | Classe da exceção anterior / causa raiz | `PDOException` |
| `{{previous_message}}` | Mensagem da exceção anterior | `SQLSTATE[HY000] [2002] Connection refused` |
| `{{request_method}}` | Método HTTP da requisição ou `CLI` | `POST`, `GET`, `CLI` |
| `{{request_uri}}` | Rota/URI acessada ou comando executado | `/api/v1/checkout` |
| `{{server_name}}` | Hostname do servidor ou nome do host | `production-web-01` |
| `{{php_version}}` | Versão do runtime PHP | `8.4.11` |
| `{{memory_usage}}` | Pico de memória alocada no script | `18.45 MB` |
| `{{usuario_nome}}` | Nome do usuário autenticado no contexto | `Carlos Dev` ou `Anônimo` |
| `{{trace}}` / `{{stack_trace}}` | Stack trace formatado | `#0 /app/index.php...` |
| `{{payload}}` | Dados de request sanitizados em JSON | `{"post":{"user":"admin"...}}` |
| `{{hash}}` | Assinatura única (MD5) do erro | `eba8872610b3e3188d4da1c5c714cdcf` |

---

## 2. Blocos Condicionais (`<!-- IF_COND -->...<!-- /IF_COND -->`)

Você pode ocultar linhas e seções inteiras de forma inteligente quando um dado não estiver disponível, evitando linhas vazias no e-mail:

```text
<!-- IF_ORIGIN -->
Origem:       {{error_file}}:{{error_line}}
<!-- /IF_ORIGIN -->
<!-- IF_PREVIOUS -->
Causa Raiz:   {{previous_class}}: {{previous_message}}
<!-- /IF_PREVIOUS -->
<!-- IF_USER -->
Usuário:      {{usuario_nome}}
<!-- /IF_USER -->
```

* **`<!-- IF_ORIGIN -->`**: Exibido apenas se o arquivo e linha de origem estiverem disponíveis.
* **`<!-- IF_PREVIOUS -->`**: Exibido apenas se houver uma exceção encadeada (`$e->getPrevious()`). Se o erro não tiver causa anterior, todo o bloco é removido sem deixar linhas em branco.
* **`<!-- IF_USER -->`**: Exibido apenas quando houver usuário autenticado no contexto ou na sessão.
* Qualquer outra tag (ex.: `<!-- IF_PAYLOAD -->`) também é suportada.

---

## 3. Usando um Template de Arquivo Customizado

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

## 4. Usando uma Closure Formatadora Customizada

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
