# PRD Retroativo: HeliomarPM ErrorTracker

> Atualizado em: 2026-10-25

## Visão do Produto e Proposta de Valor

O **HeliomarPM ErrorTracker** é uma biblioteca PHP leve e de código aberto criada para capturar, sanitizar, deduplicar e despachar erros e exceções de aplicações web e APIs.

A proposta de valor central é oferecer aos desenvolvedores PHP uma camada de observabilidade autônoma e segura, que elimina vazamentos de dados confidenciais (senhas, CPFs, tokens) em logs e telas, evita ataques acidentais de negação de serviço provocados por disparos em massa de alertas (*alert storm / flood*) e permite encaminhar incidentes automaticamente para ferramentas de trabalho cotidianas (GitHub Issues, banco relacional, e-mail e arquivos). *(inferido)*

## Usuários e Personas

- **Engenheiro(a) de Software PHP**: Desenvolve aplicações e APIs (legadas ou modernas) e precisa de uma solução simples de interceptação de erros sem adicionar dependências pesadas ou serviços SaaS externos caros. *(inferido)*
- **Equipe de Suporte e Sustentação**: Necessita de stack traces legíveis e estruturados com dados sanitizados para abrir e rastrear chamados técnicos com rapidez. *(inferido)*

## Funcionalidades Existentes

| Funcionalidade | Status | Arquivo de Origem |
| :--- | :--- | :--- |
| Captura global de Exceptions, Warnings e Fatal Errors | Implementada | `src/ErrorTracker.php:58-106` |
| Sanitização de dados sensíveis (blackout de senhas/tokens e máscaras regex de CPF/e-mail/cartão) | Implementada | `src/DataSanitizer.php` |
| Anti-flood com deduplicação de alertas via cache PSR-16 | Implementada | `src/ErrorTracker.php:174-185`, `src/Cache/FileCache.php` |
| Rota de fuga configurável (`onCritical`) para tela 500 / JSON de falha | Implementada | `src/ErrorTracker.php:49-53, 205-213` |
| Métodos para logs manuais (`info`, `notice`, `warning`, `error`) | Implementada | `src/ErrorTracker.php:111-156` |
| Handler para registro em Banco de Dados via PDO | Parcial *(defeito em variáveis e DDL)* | `src/Handlers/DatabaseHandler.php` |
| Handler para abertura automática de Issues no GitHub | Implementada | `src/Handlers/GitHubIssueHandler.php` |
| Handler para disparo de alertas por E-mail em formato HTML | Implementada | `src/Handlers/EmailHandler.php` |
| Handler para escrita em arquivo de log rotacionado por dia | Implementada | `src/Handlers/FilelogHandler.php` |
| Interface Web de demonstração interativa e rota de erro 500 | Implementada | `demo/index.php`, `demo/500.php` |

## Fora de Escopo Observado

- Dashboard administrativo web próprio para visualização e triagem de erros.
- APM completo com instrumentação de rede e tracing distribuído (OpenTelemetry).
- Adaptadores de mensageria assíncrona (RabbitMQ, Kafka, SQS).

## Métricas de Sucesso

- `[A CONFIRMAR]` (salvo se documentadas pela equipe em versões futuras).

## Perguntas Abertas e Lacunas

1. `[A CONFIRMAR]` Deseja-se manter a associação de usuários em `DatabaseHandler` (`error_tracker_users`) ou remover as linhas quebradas e focar exclusivamente no log da tabela principal?
2. `[A CONFIRMAR]` O `composer.json` mantém suporte e keywords herdadas de outro projeto (`linq-php`). Devem ser ajustadas para o `error-tracker`?

## Notas da Equipe
*(Espaço reservado para anotações manuais da equipe. A skill não altera esta seção.)*
