# Tarefas do Projeto: HeliomarPM ErrorTracker

> **Meta:** Estabilização de código, modernização para PHP 8.2+, suíte de testes, Handlers desacoplados com templates externos, identidade visual e demo para Vercel\
> **Status:** concluído | **Progresso:** 14/14 (100%) | **WIP:** 0/2 | **Atualizado em:** 2026-10-25

## Em progresso

(nenhuma)

## Bloqueadas

(nenhuma)

## Backlog

(todas as tarefas concluídas)

## Concluídas recentemente

- [x] **T-001 · P0 · Corrigir variáveis indefinidas e DDL no DatabaseHandler**
  - Origem: discovery | Dep.: nenhuma
  - Ref: [DatabaseHandler.php](../src/Handlers/DatabaseHandler.php#L33-L90)
  - DoD:
    - [x] Remover ou corrigir referências a variáveis indefinidas (`$userId`, `$this->db`, `$sqlUser`, `$hash`) no método `report()`
    - [x] Atualizar DDL em `createTableIfNotExists()` adicionando `updated_at DATETIME`, `incidents INT DEFAULT 1` e `UNIQUE KEY uq_hash (hash)`
    - [x] Validar inserção inicial e reincidência com `ON DUPLICATE KEY UPDATE` (MySQL) e `ON CONFLICT(hash) DO UPDATE` (SQLite) sem erros de PDO

- [x] **T-002 · P1 · Corrigir namespaces dos handlers no README.md**
  - Origem: discovery | Dep.: nenhuma
  - Ref: [README.md](../README.md#L31-L34)
  - DoD:
    - [x] Corrigir referências de `HeliomarPM\ErrorTracker\Adapters` para `HeliomarPM\ErrorTracker\Handlers` no README.md
    - [x] Validar exemplos de código de inicialização e custom handlers

- [x] **T-003 · P2 · Unificar implementações de cache em FileCache**
  - Origem: discovery | Dep.: nenhuma
  - Ref: [FileCache.php](../src/Cache/FileCache.php)
  - DoD:
    - [x] Consolidar a implementação definitiva do PSR-16 em `HeliomarPM\ErrorTracker\Cache\FileCache`
    - [x] Remover `ThrottleGuard.php` para eliminar duplicação de lógica
    - [x] Validar sintaxe e instanciação de `FileCache` com fallback para diretório temporário do sistema

- [x] **T-004 · P2 · Adicionar scripts operacionais de QA no composer.json**
  - Origem: discovery | Dep.: nenhuma
  - Ref: [composer.json](../composer.json)
  - DoD:
    - [x] Configurar seção `scripts` com `test` (phpunit), `format:test` (pint --test), `format:fix` (pint), `lint` (phpstan), `check` e `start`
    - [x] Validar sintaxe com `composer validate`

- [x] **T-005 · P1 · Criar estrutura e testes unitários fundamentais**
  - Origem: discovery | Dep.: T-001, T-003, T-004
  - Ref: [phpunit.xml](../phpunit.xml), [tests/Unit/](../tests/Unit/)
  - DoD:
    - [x] Criar diretório `tests/Unit/`
    - [x] Implementar `DataSanitizerTest.php` cobrindo blackout de chaves críticas e regex (CPF, e-mail, cartão, telefone)
    - [x] Implementar `FileCacheTest.php` cobrindo operações PSR-16 e TTL
    - [x] Implementar `ErrorTrackerTest.php` testando captura, anti-flood e handlers mockados
    - [x] Implementar `DatabaseHandlerTest.php` com SQLite em memória
    - [x] Implementar `TemplateRendererTest.php`
    - [x] Executar suíte PHPUnit com sucesso (22 testes, 77 asserções)

- [x] **T-006 · P2 · Implementar operações em lote (Multiple) da PSR-16 no FileCache**
  - Origem: manual | Dep.: nenhuma
  - Ref: [FileCache.php](../src/Cache/FileCache.php)
  - DoD:
    - [x] Implementar `getMultiple()` iterando sobre as chaves e retornando mapa `key => value` com fallback padrão
    - [x] Implementar `setMultiple()` iterando sobre pares com persistência e retorno booleano
    - [x] Implementar `deleteMultiple()` iterando e removendo as chaves informadas
    - [x] Garantir compatibilidade com arrays e instâncias de `Traversable`

- [x] **T-007 · P2 · Implementar contrato PSR-3 (LoggerInterface) no ErrorTracker**
  - Origem: manual | Dep.: nenhuma
  - Ref: [ErrorTracker.php](../src/ErrorTracker.php), [composer.json](../composer.json)
  - DoD:
    - [x] Adicionar dependência `psr/log: ^3.0` em `composer.json`
    - [x] Implementar `Psr\Log\LoggerInterface` (via `Psr\Log\LoggerTrait`) no `ErrorTracker`
    - [x] Mapear o método `log($level, string|\Stringable $message, array $context = []): void` convertendo o nível para `Severity`
    - [x] Manter compatibilidade com os métodos convenientes já existentes (`info`, `notice`, `warning`, `error`, `critical`, etc.)

- [x] **T-008 · P2 · Criar sistema de templates externos com tags substituíveis para Email e Filelog**
  - Origem: manual | Dep.: nenhuma
  - Ref: [TemplateRenderer.php](../src/Formatters/TemplateRenderer.php), [Templates/](../src/Templates/)
  - DoD:
    - [x] Criar pasta `src/Templates/` com arquivos padrão externos (`email_html.tpl`, `email_text.tpl`, `filelog.tpl`)
    - [x] Implementar mecanismo de substituição de tags (`{{severity}}`, `{{message}}`, `{{file}}`, `{{line}}`, `{{trace}}`, `{{payload}}`, `{{hash}}`, `{{timestamp}}`)
    - [x] Migrar concatenações de strings hardcoded de `EmailHandler` e `FilelogHandler` para os templates externos

- [x] **T-009 · P2 · Adicionar suporte a HTML e Texto Puro configurável no EmailHandler**
  - Origem: manual | Dep.: T-008
  - Ref: [EmailHandler.php](../src/Handlers/EmailHandler.php)
  - DoD:
    - [x] Permitir escolher formato de saída no construtor (`html` ou `text`)
    - [x] Ajustar cabeçalhos MIME (`Content-Type: text/plain` vs `text/html; charset=UTF-8`) conforme formato escolhido
    - [x] Permitir customizar o template do assunto do e-mail, removendo texto fixo ("PDV Hall Hub")
    - [x] Carregar o respectivo template externo padrão de acordo com o formato e expor método `renderBody()`

- [x] **T-010 · P2 · Permitir customização de mensagem por Handler via template ou closure formatadora**
  - Origem: manual | Dep.: T-008
  - Ref: [Handlers/](../src/Handlers/), [docs/TEMPLATES.md](../docs/TEMPLATES.md)
  - DoD:
    - [x] Permitir que `FilelogHandler` e `EmailHandler` recebam caminho de arquivo de template customizado pelo usuário
    - [x] Disponibilizar suporte a closure formatadora `callable(ErrorContext $context): string` para gerar corpo customizado em Handlers
    - [x] Permitir customizar título e corpo de issues no `GitHubIssueHandler` via template/closure
    - [x] Manter fallback seguro para os templates padrões quando nenhuma customização for fornecida

- [x] **T-011 · P1 · Corrigir versão de action e análise estática no pipeline CI**
  - Origem: assessment | Dep.: nenhuma
  - Ref: [.github/workflows/ci.yml](../.github/workflows/ci.yml), [ErrorTracker.php](../src/ErrorTracker.php)
  - DoD:
    - [x] Manter `actions/checkout@v7` conforme instrução do usuário
    - [x] Corrigir verificações de coalescência em superglobais (`$_POST`, `$_GET`, `$_SERVER`) em `ErrorTracker.php`
    - [x] Garantir que o comando `composer lint` passe com zero erros

- [x] **T-012 · P2 · Configurar timeouts e resiliência em Handlers externos síncronos**
  - Origem: assessment | Dep.: nenhuma
  - Ref: [GitHubIssueHandler.php](../src/Handlers/GitHubIssueHandler.php), [EmailHandler.php](../src/Handlers/EmailHandler.php)
  - DoD:
    - [x] Parametrizar timeouts de rede (cURL) no construtor de `GitHubIssueHandler` (`$timeoutSeconds`)
    - [x] Tratar adequadamente falhas e timeouts sem bloquear a resposta principal da aplicação

- [x] **T-013 · P3 · Implementar Handler de Webhook para ChatOps (Slack / Discord)**
  - Origem: assessment | Dep.: nenhuma
  - Ref: [WebhookHandler.php](../src/Handlers/WebhookHandler.php), [WebhookHandlerTest.php](../tests/Unit/WebhookHandlerTest.php)
  - DoD:
    - [x] Criar classe `WebhookHandler` implementando `HandlerInterface`
    - [x] Suportar envio para endpoints de webhook compatíveis com Slack e Discord
    - [x] Formatar payload com embeds ou blocos estruturados (severidade, hash, mensagem, arquivo)

- [x] **T-014 · P3 · Implementar deduplicação de Issues abertas no GitHubIssueHandler**
  - Origem: assessment | Dep.: nenhuma
  - Ref: [GitHubIssueHandler.php](../src/Handlers/GitHubIssueHandler.php)
  - DoD:
    - [x] Consultar a API do GitHub por issues abertas contendo o hash do erro antes de criar nova
    - [x] Se existir issue aberta correspondente, adicionar comentário de reincidência com timestamp
    - [x] Se não existir, criar nova issue normalmente com labels apropriadas

## Canceladas

(nenhuma)
