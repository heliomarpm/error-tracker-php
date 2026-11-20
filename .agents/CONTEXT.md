# Contexto do Projeto: HeliomarPM ErrorTracker

> Atualizado em: 2026-11-20

## Propósito
Biblioteca PHP orientada a objetos para rastreamento de erros e exceções, compatível com PSR-3 (`LoggerInterface`), sanitização de dados sensíveis em requisições, deduplicação anti-flood via cache PSR-16 e despacho para múltiplos canais (`DatabaseHandler`, `EmailHandler`, `FilelogHandler`, `GitHubIssueHandler` e `WebhookHandler`).

## Stack

| Componente | Tecnologia | Versão | Origem |
| :--- | :--- | :--- | :--- |
| Runtime | PHP | `^8.1` (enums, `readonly`, `match`, named args) | `composer.json` / `src/` |
| Gerenciador | Composer | v2 | `composer.json` |
| Logger Contract | PSR-3 (`psr/log`) | `^3.0` | `composer.json` |
| Cache Contract | PSR-16 (`psr/simple-cache`) | `^3.0` | `composer.json` |
| Banco | PDO (MySQL / MariaDB / SQLite) | Nativo PHP | `src/Handlers/DatabaseHandler.php` |
| Testes | PHPUnit | `^11.0 \|\| ^12.0 \|\| ^13.0` | `composer.json` / `phpunit.xml` |
| Análise Estática | PHPStan | `*` (configurado em `phpstan.neon`) | `composer.json` / `phpstan.neon` |
| Estilo de Código | Laravel Pint | `^1.20 \|\| ^1.32` | `composer.json` / `pint.json` |

## Comandos

| Ação | Comando | Origem |
| :--- | :--- | :--- |
| Instalar dependências | `composer install` | `composer.json` |
| Executar testes | `composer test` | `composer.json` (`phpunit.xml`) |
| Análise estática | `composer lint` | `composer.json` (`phpstan analyse`) |
| Verificar estilo | `composer format` | `composer.json` (`pint --test`) |
| Validar estilo + lint | `composer check` | `composer.json` (`@format` + `@lint`) |
| Servidor de demonstração | `composer start` | `composer.json` (`php -S localhost:8001 -t ./demo demo/router.php`) |

## Topologia de pastas

```text
src/
├── Cache/          # Implementação de cache PSR-16 em arquivo (FileCache)
├── Contracts/      # Contratos e interfaces públicas (HandlerInterface)
├── Formatters/     # StackTraceFormatter e TemplateRenderer
├── Handlers/       # AbstractHandler + Database, Email, Filelog, GitHubIssue e Webhook
├── Templates/      # Templates .tpl externos (e-mail HTML/texto, filelog, GitHub)
├── Transports/     # Cliente SMTP nativo via sockets (SmtpTransport)
├── DataSanitizer.php # Sanitização e ofuscação de dados sensíveis
├── ErrorContext.php  # DTO imutável contendo payload, stack trace, SQL e hash MD5
├── ErrorTracker.php  # Orquestrador central, LoggerInterface (PSR-3) e hooks globais
└── Severity.php      # Enum de severidades (critical, warning, notice, deprecated, info)
tests/Unit/         # 12 suítes de testes unitários PHPUnit
demo/               # Workbench interativo, API sandbox, router e página 500
docs/               # Documentação pública (Handlers, Templates, Contribuição, Suporte)
```

## Índice da documentação

| Documento | Conteúdo |
| :--- | :--- |
| [AGENTS.md](./AGENTS.md) | Regras e restrições obrigatórias para assistentes de IA |
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Estilo arquitetural, fluxo do erro, handlers e pontos de atenção |
| [DATA.md](./DATA.md) | Persistência PDO (MySQL/SQLite), esquema da tabela, cache e integrações |
| [PRD.md](./PRD.md) | Produto retroativo, funcionalidades, personas e escopo |
| [GUIDELINE.md](./GUIDELINE.md) | Padrões de código PHP, sanitização, git e convenções |
| [TESTING.md](./TESTING.md) | Configuração PHPUnit, suítes de teste e validação de qualidade |
| [TASKS.md](./TASKS.md) | Backlog vivo de tarefas e histórico de entregas |
| [2026-11-19-analise-completa.md](./archive/2026-11-19-analise-completa.md) | Análise técnica arquivada da refatoração de simplificação (`T-029`) |

## Notas da Equipe
*(Espaço reservado para anotações manuais da equipe. A skill não altera esta seção.)*
