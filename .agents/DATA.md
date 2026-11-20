# Dados e Integrações: HeliomarPM ErrorTracker

> Atualizado em: 2026-11-20

## Persistência e Banco de Dados

A persistência relacional é fornecida através do `DatabaseHandler`, utilizando **PDO** com suporte nativo a **MySQL / MariaDB** (`ON DUPLICATE KEY UPDATE`, `NOW()`, `ENGINE=InnoDB`, `utf8mb4`) e **SQLite** (`ON CONFLICT(hash) DO UPDATE`, `datetime('now')`). Quando `autoCreate: true`, a tabela e os índices são criados automaticamente na primeira execução (`CREATE TABLE IF NOT EXISTS`).

### Tabela: `error_tracker` (padrão)

| Coluna | Tipo (MySQL / MariaDB) | Tipo (SQLite) | Nulo | Descrição |
| :--- | :--- | :--- | :--- | :--- |
| `id` | `BIGINT AUTO_INCREMENT` | `INTEGER PRIMARY KEY AUTOINCREMENT` | Não | Chave primária |
| `app_name` | `VARCHAR(100)` | `VARCHAR(100)` | Sim | Nome da aplicação ou microsserviço |
| `hash` | `VARCHAR(32)` | `VARCHAR(32)` | Não | Hash MD5 (`file . line . message`) único para upsert |
| `severity` | `VARCHAR(20)` | `VARCHAR(20)` | Não | Nível de severidade (`critical`, `warning`, etc.) |
| `error_class` | `VARCHAR(150)` | `VARCHAR(150)` | Não | Classe da exceção ou nome da severidade em log manual |
| `previous_class` | `VARCHAR(150)` | `VARCHAR(150)` | Sim | Classe da causa raiz encadeada (`$e->getPrevious()`) |
| `message` | `TEXT` | `TEXT` | Não | Mensagem do erro ou exceção |
| `file` | `VARCHAR(255)` | `VARCHAR(255)` | Não | Caminho relativo de origem da falha |
| `line` | `INT` | `INT` | Não | Número da linha onde o erro ocorreu |
| `method` | `VARCHAR(10)` | `VARCHAR(10)` | Sim | Método HTTP (`GET`, `POST`, `CLI`, etc.) |
| `uri` | `VARCHAR(255)` | `VARCHAR(255)` | Sim | URI da requisição ou comando CLI |
| `user_name` | `VARCHAR(100)` | `VARCHAR(100)` | Sim | Usuário resolvido no contexto do incidente |
| `trace` | `LONGTEXT` | `TEXT` | Não | Stack trace formatado |
| `payload` | `JSON` | `TEXT` | Não | Dados de requisição e contexto sanitizados em JSON |
| `created_at` | `DATETIME` | `DATETIME` | Não | Data e hora da primeira ocorrência |
| `updated_at` | `DATETIME` | `DATETIME` | Não | Data e hora da última reincidência |
| `incidents` | `INT DEFAULT 1` | `INT DEFAULT 1` | Sim | Contador acumulado de ocorrências |

**Constraints e índices criados automaticamente:**
- `uq_hash`: `UNIQUE (hash)`
- `idx_app_name` em `app_name`
- `idx_severity` em `severity`
- `idx_error_class` em `error_class`
- `idx_updated_at` em `updated_at`
- `idx_incidents` em `incidents`

---

## Estratégia de Cache e Deduplicação (PSR-16)

Para evitar avalanche de alertas (*flood*), a biblioteca utiliza o padrão **PSR-16 SimpleCache** (`psr/simple-cache: ^3.0`).

- **Padrão de Chave**: `error_tracker_{hash}`
- **TTL Padrão**: `3600` segundos (1 hora), configurável no construtor/factory de `ErrorTracker`.
- **Mecanismo padrão de arquivo (`FileCache`)**:
  - Grava arquivos individuais com nome `md5($key) . '.cache'`.
  - Conteúdo serializado via `serialize(['value' => ..., 'expires_at' => ...])` com bloqueio exclusivo `LOCK_EX` e desserialização segura (`allowed_classes => false`).
  - Diretório padrão: `sys_get_temp_dir() . '/ErrorTracker_Cache'` ou caminho customizado.

---

## Integrações Externas

### 1. GitHub REST API (v3)
- **Classe**: `HeliomarPM\ErrorTracker\Handlers\GitHubIssueHandler`
- **Endpoints**: `POST /repos/{owner}/{repo}/issues`, `GET /search/issues` (deduplicação de issue aberta por hash) e `POST /repos/{owner}/{repo}/issues/{number}/comments` (comentário de reincidência).
- **Autenticação**: Bearer Token via Personal Access Token (PAT).
- **Payload**: Título formatado, corpo e comentários renderizados via `TemplateRenderer` (`github_issue_body.tpl` e `github_issue_comment.tpl`) ou `bodyFormatter`, com labels (`bug`, `automated-report`, `severity:{nivel}`).
- **Conexão**: cURL síncrono com timeout configurável (padrão `3s`).

### 2. E-mail (SMTP Autenticado e MTA Nativo)
- **Classes**: `HeliomarPM\ErrorTracker\Handlers\EmailHandler` e `HeliomarPM\ErrorTracker\Transports\SmtpTransport`
- **Canais**: Injeção opcional de `SmtpTransport` nativo via `stream_socket_client` (SSL porta 465 / STARTTLS porta 587 / `AUTH LOGIN` / dot-stuffing RFC 5321), com fallback automático para `mail()` nativo e envelope sender (`-f`).
- **Formatos**: HTML (`email_html.tpl`), texto puro (`email_text.tpl`) ou `multipart/alternative` (texto + HTML) quando `multipart: true`, com higienização de cabeçalho contra CRLF injection no assunto (`mb_encode_mimeheader`).
- **Filtro**: Padrão `[Severity::CRITICAL]`.

### 3. Webhooks ChatOps (Slack, Discord e Genérico)
- **Classe**: `HeliomarPM\ErrorTracker\Handlers\WebhookHandler`
- **Formatos**: `'slack'` (attachments coloridos), `'discord'` (embeds com código e fields) e `'generic'` (JSON estruturado completo), ou `payloadFormatter` customizado.
- **Conexão**: cURL HTTP POST `application/json` com timeout configurável (padrão `3s`).

### 4. Log em Sistema de Arquivos
- **Classe**: `HeliomarPM\ErrorTracker\Handlers\FilelogHandler`
- **Formato**: Arquivo diário rotacionado `{logDirectory}/{SEVERITY}_{Ymd}.log`, formatado via `filelog.tpl` ou `formatter` customizado.
- **Gravação**: Append atômico com `FILE_APPEND | LOCK_EX`.

## Notas da Equipe
*(Espaço reservado para anotações manuais da equipe. A skill não altera esta seção.)*
