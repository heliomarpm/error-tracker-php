# Tarefas do Projeto: HeliomarPM ErrorTracker

> **Meta:** Estabilização de código, modernização para PHP 8.2+, suíte de testes, Handlers desacoplados com templates externos, identidade visual e demo para Vercel\
> **Status:** em desenvolvimento | **Progresso:** 24/25 (96%) | **WIP:** 0/2 | **Atualizado em:** 2026-10-27

## Em progresso

(nenhuma)

## Bloqueadas

(nenhuma)

## Backlog

- [ ] **T-025 · P3 · Adicionar favicon na aplicação demo**
  - Origem: manual | Dep.: nenhuma
  - Ref: [index.php](../demo/index.php), [500.php](../demo/500.php), [router.php](../demo/router.php)
  - DoD:
    - [ ] Criar ícone favicon SVG moderno e alinhado à identidade visual do ErrorTracker em `demo/favicon.svg`
    - [ ] Adicionar tag `<link rel="icon" type="image/svg+xml" href="/favicon.svg">` no `<head>` de `demo/index.php` e `demo/500.php`
    - [ ] Validar carregamento do favicon sem erros 404 no servidor embutido

## Concluídas recentemente

- [x] **T-024 · P2 · Reforçar robustez e customização do DataSanitizer e payload de requisição** (2026-10-27)
  - Arquivos: [DataSanitizer.php](../src/DataSanitizer.php), [ErrorTracker.php](../src/ErrorTracker.php), [DataSanitizerTest.php](../tests/Unit/DataSanitizerTest.php), [ErrorTrackerTest.php](../tests/Unit/ErrorTrackerTest.php)
  - Evidência: testes passaram (79 testes, 265 asserções no PHPUnit), PHPStan 0 erros, Pint formatado
  - DoD:
    - [x] Adicionar limite de recursão `$maxDepth = 5` no `DataSanitizer::sanitize()` para evitar estouro de memória em arrays circulares/profundos
    - [x] Otimizar verificação de regex pulando checagem em strings com comprimento excessivo (> 500 caracteres)
    - [x] Permitir configuração de chaves mascaradas adicionais customizáveis pelo usuário
    - [x] Filtrar chaves essenciais de `$_SERVER` em `processError()` para evitar inchar o payload e não vazar variáveis de ambiente de infraestrutura

- [x] **T-023 · P2 · Eliminar duplicidade de resolução de usuário nos Handlers via trait** (2026-10-27)
  - Arquivos: [ResolvesUserTrait.php](../src/Handlers/Traits/ResolvesUserTrait.php), [DatabaseHandler.php](../src/Handlers/DatabaseHandler.php), [FilelogHandler.php](../src/Handlers/FilelogHandler.php), [EmailHandler.php](../src/Handlers/EmailHandler.php), [WebhookHandler.php](../src/Handlers/WebhookHandler.php), [GitHubIssueHandler.php](../src/Handlers/GitHubIssueHandler.php), [WebhookHandlerTest.php](../tests/Unit/WebhookHandlerTest.php)
  - Evidência: testes passaram (73 testes, 242 asserções no PHPUnit), PHPStan 0 erros, Pint formatado
  - DoD:
    - [x] Criar trait `ResolvesUserTrait` com método unificado `resolveUser(ErrorContext $context): ?string`
    - [x] Substituir o bloco duplicado de resolução de usuário nos 5 handlers (`DatabaseHandler`, `FilelogHandler`, `EmailHandler`, `WebhookHandler`, `GitHubIssueHandler`)
    - [x] Garantir 100% de compatibilidade e aprovação de todos os testes unitários

- [x] **T-022 · P2 · Otimizar performance de I/O em TemplateRenderer e StackTraceFormatter** (2026-10-27)
  - Arquivos: [TemplateRenderer.php](../src/Formatters/TemplateRenderer.php), [StackTraceFormatter.php](../src/Formatters/StackTraceFormatter.php), [WebhookHandler.php](../src/Handlers/WebhookHandler.php), [GitHubIssueHandler.php](../src/Handlers/GitHubIssueHandler.php), [TemplateRendererTest.php](../tests/Unit/TemplateRendererTest.php), [StackTraceFormatterTest.php](../tests/Unit/StackTraceFormatterTest.php)
  - Evidência: testes passaram (72 testes, 240 asserções no PHPUnit), PHPStan 0 erros, Pint formatado
  - DoD:
    - [x] Implementar cache estático em memória para conteúdo de arquivos `.tpl` em `TemplateRenderer`
    - [x] Pré-computar e armazenar raízes de diretório (`cachedRoots`) em `StackTraceFormatter::normalizePath()`
    - [x] Configurar `CURLOPT_CONNECTTIMEOUT => 2` em `WebhookHandler` e `GitHubIssueHandler`
    - [x] Validar testes existentes e adicionar teste de cache em `TemplateRendererTest`

- [x] **T-021 · P1 · Adicionar coluna app_name no DatabaseHandler** (2026-10-27)
  - Arquivos: [DatabaseHandler.php](../src/Handlers/DatabaseHandler.php), [DatabaseHandlerTest.php](../tests/Unit/DatabaseHandlerTest.php)
  - Evidência: testes passaram (70 testes, 232 asserções no PHPUnit), PHPStan 0 erros, Pint formatado
  - DoD:
    - [x] Adicionar campo `app_name VARCHAR(100)` no DDL SQLite e MySQL em `createTableIfNotExists()`
    - [x] Adicionar índice `idx_app_name` e atualizar inserções/registros para vincular `$context->appName`
    - [x] Atualizar `buildRecord()` e queries de inserção/reincidência
    - [x] Atualizar testes unitários em `tests/Unit/DatabaseHandlerTest.php`

- [x] **T-020 · P1 · Blindar unserialize no FileCache e sanear logs e lifecycle do ErrorTracker** (2026-10-27)
  - Arquivos: [FileCache.php](../src/Cache/FileCache.php), [ErrorTracker.php](../src/ErrorTracker.php), [FileCacheTest.php](../tests/Unit/FileCacheTest.php), [ErrorTrackerTest.php](../tests/Unit/ErrorTrackerTest.php)
  - Evidência: testes passaram (68 testes, 226 asserções no PHPUnit), PHPStan 0 erros, Pint formatado
  - DoD:
    - [x] Adicionar `['allowed_classes' => false]` no `unserialize()` em `FileCache::get()` para mitigar risco de deserialização de objetos
    - [x] Mover `error_log` em `ErrorTracker::handleError` para dentro de `if (error_reporting() & $level)` respeitando o operador de supressão `@`
    - [x] Remover chamada de depuração residual `error_log("HeliomarPM ReportAdpter iniciando para erro: ...")` em `ErrorTracker::processError`
    - [x] Adicionar método estático `unregister(): void` em `ErrorTracker` permitindo restaurar os tratadores nativos de erro e exceção do PHP
    - [x] Atualizar testes unitários em `tests/Unit/FileCacheTest.php` e `tests/Unit/ErrorTrackerTest.php`

- [x] **T-019 · P2 · Externalizar templates do GitHubIssueHandler em arquivos .tpl** (2026-10-26)
  - Arquivos: [github_issue_body.tpl](../src/Templates/github_issue_body.tpl), [github_issue_comment.tpl](../src/Templates/github_issue_comment.tpl), [GitHubIssueHandler.php](../src/Handlers/GitHubIssueHandler.php), [TemplateRenderer.php](../src/Formatters/TemplateRenderer.php), [GitHubIssueHandlerTest.php](../tests/Unit/GitHubIssueHandlerTest.php), [TEMPLATES.md](../docs/TEMPLATES.md), [HANDLERS.md](../docs/HANDLERS.md)
  - Evidência: testes passaram (64 testes, 220 asserções no PHPUnit), PHPStan 0 erros, Pint formatado
  - DoD:
    - [x] Criar templates padrão em `src/Templates/github_issue_body.tpl` e `src/Templates/github_issue_comment.tpl`
    - [x] Integrar `TemplateRenderer` no `GitHubIssueHandler` para renderização do corpo e comentário de reincidência
    - [x] Permitir customização via caminho de arquivo ou string no construtor (`$bodyTemplateOrPath`, `$commentTemplateOrPath`)
    - [x] Preservar retrocompatibilidade com `$bodyFormatter` (closure com prioridade sobre o template)
    - [x] Adicionar/atualizar testes unitários cobrindo renderização padrão e templates customizados
    - [x] Atualizar documentação em `docs/TEMPLATES.md` e `docs/HANDLERS.md` com as novas tags e exemplos

- [x] **T-018 · P1 · Redesenhar interface da demo em layout workbench com navegação por abas**
  - Origem: manual | Dep.: nenhuma
  - Ref: [demo/index.php](../demo/index.php), [demo/api.php](../demo/api.php)
  - DoD:
    - [x] Reestruturar layout da demo para 2 colunas: Controles (esquerda) e Workspace por Abas (direita)
    - [x] Ajustar tom de voz para público técnico/desenvolvedor (direto, sem termos publicitários)
    - [x] Adicionar terminal de 1-clique no Hero com comando `composer require heliomarpm/error-tracker`
    - [x] Implementar abas principais: Notificações (Handlers), Playground LGPD, Histórico (com badge) e Como Implementar
    - [x] Criar aba "Como Implementar" com snippets de código copiáveis para todos os casos de uso
    - [x] Manter e integrar todas as rotas e funções de API existentes (simulação, anti-flood, sanitização, prévias)
    - [x] Validar visualmente via subagente de browser no localhost:8000

- [x] **T-017 · P2 · Documentar exemplos e configurações do GitHubIssueHandler no README.md**
  - Origem: manual | Dep.: nenhuma
  - Ref: [README.md](../README.md), [GitHubIssueHandler.php](../src/Handlers/GitHubIssueHandler.php), [HANDLERS.md](../docs/HANDLERS.md)
  - DoD:
    - [x] Adicionar seção dedicada de configuração e boas práticas do GitHubIssueHandler no README.md
    - [x] Detalhar geração e permissões de token do GitHub (PAT Fine-grained vs Classic `repo`)
    - [x] Apresentar exemplos práticos: setup básico, filtragem de severidades, deduplicação com reincidência e formatador customizado de corpo via closure
    - [x] Exemplificar logs manuais PSR-3 com contexto enriquecido (username, IP, metadata)
    - [x] Validar links e formatação Markdown no README.md

- [x] **T-016 · P1 · Adaptar templates e rastreamento para logs informativos PSR-3**
  - Origem: manual | Dep.: T-007, T-008
  - Ref: [ErrorTracker.php](../src/ErrorTracker.php), [ErrorContext.php](../src/ErrorContext.php), [TemplateRenderer.php](../src/Formatters/TemplateRenderer.php), [Templates/](../src/Templates/)
  - DoD:
    - [x] Localizar linha e arquivo real de chamada em `captureMessage()` pulando frames internos de `ErrorTracker.php` e `LoggerTrait.php`
    - [x] Adicionar propriedade `isException` em `ErrorContext`, identificando logs manuais e omitindo classes genéricas de exceção (`ErrorException`)
    - [x] Adicionar suporte a `username` na resolução de usuário do contexto
    - [x] Suportar tags e blocos condicionais `<!-- IF_EXCEPTION -->`, `<!-- IF_MESSAGE_ONLY -->` e `<!-- IF_TRACE -->` em `TemplateRenderer`
    - [x] Adaptar templates padrões (`filelog.tpl`, `email_text.tpl`, `email_html.tpl`) para não exibirem stack trace e cabeçalhos de incidente em logs informativos
    - [x] Ajustar formatação de mensagens em `WebhookHandler` (Slack / Discord) para diferenciar eventos e incidentes
    - [x] Criar testes unitários validando log informativo vs exceção (39 testes, 163 asserções aprovados)
    - [x] Documentar novas tags e blocos condicionais em `docs/TEMPLATES.md`

- [x] **T-015 · P1 · Padronizar filtro de severidade em todos os Handlers**
  - Origem: manual | Dep.: nenhuma
  - Ref: [DatabaseHandler.php](../src/Handlers/DatabaseHandler.php), [FilelogHandler.php](../src/Handlers/FilelogHandler.php), [GitHubIssueHandler.php](../src/Handlers/GitHubIssueHandler.php), [EmailHandler.php](../src/Handlers/EmailHandler.php), [WebhookHandler.php](../src/Handlers/WebhookHandler.php), [HANDLERS.md](../docs/HANDLERS.md)
  - DoD:
    - [x] Adicionar parâmetro `$notifySeverities` tipado (`?array<Severity>`) nos construtores de `FilelogHandler`, `DatabaseHandler` e `GitHubIssueHandler`
    - [x] Em `FilelogHandler` e `DatabaseHandler`, assumir `null` como padrão (processa todas as severidades), aplicando filtro caso uma lista seja informada
    - [x] Em `GitHubIssueHandler`, definir filtro padrão (`[Severity::CRITICAL, Severity::WARNING]`) com suporte a customização pelo usuário
    - [x] Harmonizar `EmailHandler` e `WebhookHandler` para suportarem `null` (notificar todas as severidades quando desejado)
    - [x] Interromper a execução do método `report()` no início caso a severidade do evento não esteja na lista permitida
    - [x] Criar/atualizar testes unitários em `tests/Unit/` validando o descarte e o registro conforme os níveis configurados (37 testes, 144 asserções aprovados)
    - [x] Atualizar a documentação em `docs/HANDLERS.md` e `README.md` com exemplos práticos de filtragem

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
