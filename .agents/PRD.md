# PRD Retroativo: HeliomarPM ErrorTracker

> Atualizado em: 2026-11-20

## Visão do Produto e Proposta de Valor

O **HeliomarPM ErrorTracker** é uma biblioteca PHP leve e de código aberto criada para capturar, sanitizar, deduplicar e despachar erros, exceções e logs PSR-3 de aplicações web, CLIs e APIs.

A proposta de valor central é oferecer aos desenvolvedores PHP uma camada de observabilidade autônoma, fácil de plugar (`ErrorTracker::create()->addHandler(...)->register()`) e segura, que elimina vazamentos de dados confidenciais (senhas, CPFs, tokens) em logs e telas, evita ataques acidentais de negação de serviço provocados por disparos em massa de alertas (*alert storm / flood*) e permite encaminhar incidentes automaticamente para ferramentas de trabalho cotidianas (GitHub Issues, banco relacional, ChatOps, e-mail SMTP/MTA e arquivos de log).

## Usuários e Personas

- **Engenheiro(a) de Software PHP**: Desenvolve aplicações e APIs (legadas ou modernas) e precisa de uma solução simples de interceptação de erros sem adicionar dependências pesadas ou serviços SaaS externos caros.
- **Equipe de Suporte e Sustentação**: Necessita de stack traces legíveis e estruturados com dados sanitizados para abrir e rastrear chamados técnicos com rapidez.

## Funcionalidades Existentes

| Funcionalidade | Status | Arquivo de Origem |
| :--- | :--- | :--- |
| Captura global de Exceptions, Warnings e Fatal Errors + `create()` | Implementada | `src/ErrorTracker.php` |
| Compatibilidade PSR-3 (`LoggerInterface`) com resolução de origem | Implementada | `src/ErrorTracker.php` |
| Sanitização de dados sensíveis (blackout e máscaras regex) | Implementada | `src/DataSanitizer.php` |
| Anti-flood com deduplicação de alertas via cache PSR-16 | Implementada | `src/ErrorTracker.php`, `src/Cache/FileCache.php` |
| Rota de fuga configurável (`onCritical`) sob controle do consumidor | Implementada | `src/ErrorTracker.php` |
| Motor de templates externos `.tpl` e formatação de stack trace | Implementada | `src/Formatters/`, `src/Templates/` |
| Handler base desacoplado (`AbstractHandler`) | Implementada | `src/Handlers/AbstractHandler.php` |
| Persistência em Banco de Dados MySQL/MariaDB/SQLite via PDO | Implementada | `src/Handlers/DatabaseHandler.php` |
| Abertura e deduplicação automática de Issues no GitHub | Implementada | `src/Handlers/GitHubIssueHandler.php` |
| Notificações ChatOps via Webhooks (Slack, Discord, Genérico) | Implementada | `src/Handlers/WebhookHandler.php` |
| Alertas de E-mail via `SmtpTransport` (SSL/TLS) ou `mail()` + multipart | Implementada | `src/Handlers/EmailHandler.php`, `src/Transports/SmtpTransport.php` |
| Escrita em arquivo de log rotacionado por dia (`LOCK_EX`) | Implementada | `src/Handlers/FilelogHandler.php` |
| Playground interativo web, API sandbox e página 500 | Implementada | `demo/index.php`, `demo/api.php`, `demo/500.php` |

## Fora de Escopo Observado

- Dashboard administrativo web próprio para visualização e triagem de erros em produção.
- APM completo com instrumentação de rede e tracing distribuído (OpenTelemetry).
- Adaptadores de mensageria assíncrona (RabbitMQ, Kafka, SQS).

## Métricas de Sucesso

- Qualidade validada continuamente por `composer test` (PHPUnit), `composer lint` (PHPStan) e `composer format` (Laravel Pint).

## Notas da Equipe
*(Espaço reservado para anotações manuais da equipe. A skill não altera esta seção.)*
