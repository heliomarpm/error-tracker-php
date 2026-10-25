# Estratégia de Testes: HeliomarPM ErrorTracker

> Atualizado em: 2026-10-25

## Framework e Configuração

O repositório possui arquivo de configuração para o **PHPUnit** na raiz do projeto (`phpunit.xml`).

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
| Executar todos os testes | `vendor/bin/phpunit` | `phpunit.xml` (requer `phpunit/phpunit` instalado) |
| Executar apenas Unitários | `vendor/bin/phpunit --testsuite Unit` | `phpunit.xml` |
| Executar apenas Integração | `vendor/bin/phpunit --testsuite Integration` | `phpunit.xml` |

*Nota: O `composer.json` atual não inclui `phpunit/phpunit` na seção `require-dev` nem scripts de atalho (`"test": "phpunit"`).*

---

## Diagnóstico e Lacuna Crítica de Cobertura

O diretório `tests/` **não existe** no repositório. Toda a validação funcional atual depende da aplicação manual em `demo/index.php`.

Esta ausência impede a garantia de regressão em refatorações e manutenções do pacote.

---

## Plano de Testes Recomendado

### 1. Testes Unitários (`tests/Unit/`)

- **`DataSanitizerTest.php`**:
  - Testar blackout de senhas (`password`, `senha`, `token`, `secret`, `cvv`, `authorization`).
  - Testar ofuscação de e-mails normais e e-mails com nome curto.
  - Testar formatação de CPF (`***.***.123-45`).
  - Testar mascaramento de cartão de crédito e telefones.
  - Testar arrays multidimensionais e preservação de tipos não-string.
- **`FileCacheTest.php`**:
  - Testar operações `set`, `get`, `has`, `delete` e `clear`.
  - Testar expiração de TTL (tempo limite).
  - Testar concorrência básica e integridade de arquivo.
- **`ErrorContextTest.php`**:
  - Validar cálculo determinístico do hash MD5 (`md5(file . line . message)`).
  - Validar inicialização com severidade padrão `CRITICAL`.
- **`ErrorTrackerTest.php`**:
  - Validar adição e disparo ordenado de handlers mockados (`HandlerInterface`).
  - Validar supressão de disparo quando o hash já consta no cache (anti-flood).
  - Validar isolamento de exceções (quando um handler falha, os outros continuam).
  - Validar invocação de `onCritical` e rota de fuga.

### 2. Testes de Integração (`tests/Integration/`)

- **`FilelogHandlerTest.php`**:
  - Gravação real em diretório temporário (`sys_get_temp_dir()`).
  - Verificação de formato de log, append e rotação de nomes `{severity}_{Ymd}.log`.
- **`DatabaseHandlerTest.php`**:
  - Execução de queries com banco SQLite em memória (`sqlite::memory:`) ou MySQL de teste para validar o auto-setup de tabela e integridade dos campos.

## Notas da Equipe
*(Espaço reservado para anotações manuais da equipe. A skill não altera esta seção.)*
