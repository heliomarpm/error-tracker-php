# Estratégia de Testes: HeliomarPM ErrorTracker

> Atualizado em: 2026-11-20

## Framework e Configuração

O repositório utiliza **PHPUnit** configurado em `phpunit.xml`, complementado por **PHPStan** (`phpstan.neon`) e **Laravel Pint** (`pint.json`).

```xml
<phpunit bootstrap="vendor/autoload.php" colors="true" executionOrder="random" failOnRisky="true" failOnWarning="true">
  <testsuites>
    <testsuite name="Unit">
      <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Integration">
      <directory>tests/Integration</directory>
    </testsuite>
  </testsuites>
</phpunit>
```

## Como Executar

| Ação | Comando | Origem |
| :--- | :--- | :--- |
| Executar todos os testes | `composer test` | `composer.json` / `phpunit.xml` |
| Executar apenas Unitários | `vendor/bin/phpunit --testsuite Unit` | `phpunit.xml` |
| Executar com cobertura | `composer test:coverage` | `composer.json` |
| Análise estática (PHPStan) | `composer lint` | `composer.json` / `phpstan.neon` |
| Verificar estilo (Pint) | `composer format` | `composer.json` / `pint.json` |
| Validar estilo + lint | `composer check` | `composer.json` |

---

## Cobertura Atual (`tests/Unit/`)

A suíte possui **12 arquivos de teste unitário** totalizando **93 testes e 299 asserções** (última execução completa validada em 2026-11-20 com 0 falhas e 0 erros):

- `DatabaseHandlerTest.php`: DDL SQLite em memória, upsert com incremento de `incidents`, filtro de severidade, `app_name` e `user_name`.
- `DataSanitizerTest.php`: blackout de chaves sensíveis, máscara de e-mails/CPF/cartões/telefones e limite de profundidade (`DEFAULT_MAX_DEPTH`).
- `EmailHandlerTest.php`: renderização HTML/texto, formatter customizado, filtro de severidade e injeção de `SmtpTransport` sem exceções.
- `ErrorContextTest.php`: hash determinístico, resolução de usuário direto/contexto, SQL params, IP e Referer.
- `ErrorTrackerTest.php`: disparo para handlers, deduplicação anti-flood, PSR-3 (`LoggerInterface`), backtrace com `ignoredCallers`, `create()`, `parameters()`, `register()`/`unregister()`, filtro de `$_SERVER` e `onCritical`.
- `FileCacheTest.php`: `set`, `get`, `has`, `delete`, `clear`, operações múltiplas e expiração por TTL.
- `FilelogHandlerTest.php`: gravação em disco com rotação diária, formatter customizado, renderização de usuário e tolerância a diretórios inválidos.
- `GitHubIssueHandlerTest.php`: renderização de título, corpo e comentário de reincidência com tags de usuário e `isException: false`.
- `SmtpTransportTest.php`: inicialização padrão/customizada, validação de destinatários vazios e falha graciosa em porta fechada.
- `StackTraceFormatterTest.php`: normalização de caminhos e formatação compacta/detalhada.
- `TemplateRendererTest.php`: interpolação de tags, condicionais (`IF_EXCEPTION`, `IF_PREVIOUS`, `IF_SQL`, `IF_USER`, etc.), tags extras e cache estático de templates.
- `WebhookHandlerTest.php`: payloads Slack, Discord e genérico, filtro de severidade e `payloadFormatter` customizado.

## Notas da Equipe
*(Espaço reservado para anotações manuais da equipe. A skill não altera esta seção.)*
