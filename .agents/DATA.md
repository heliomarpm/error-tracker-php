# Dados e Integrações: HeliomarPM ErrorTracker

> Atualizado em: 2026-10-25

## Persistência e Banco de Dados

A persistência relacional é fornecida opcionalmente através do `DatabaseHandler`, utilizando **PDO**. O dialeto SQL utilizado é compatível com **MySQL / MariaDB** (deduzido pelos comandos `ENGINE=InnoDB`, `ON DUPLICATE KEY UPDATE` e `NOW()`).

### Tabela: `error_tracker` (padrão)

| Coluna | Tipo | Nulo | Descrição |
| :--- | :--- | :--- | :--- |
| `id` | BIGINT AUTO_INCREMENT | Não | Chave primária |
| `hash` | VARCHAR(32) | Não | Hash MD5 (`file + line + message`) para deduplicação |
| `severity` | VARCHAR(20) | Não | Nível de severidade (`critical`, `warning`, etc.) |
| `message` | TEXT | Não | Mensagem do erro ou exceção |
| `file` | VARCHAR(255) | Não | Caminho do arquivo de origem da falha |
| `line` | INT | Não | Número da linha onde o erro ocorreu |
| `trace` | LONGTEXT | Não | Stack trace completo formatado |
| `payload` | JSON | Não | Dados de requisição sanitizados (`$_POST`, `$_GET`, `$_SERVER`) |
| `created_at` | DATETIME | Não | Data e hora do primeiro incidente |
| `updated_at` | DATETIME | Sim | Data e hora da última ocorrência registrada *(lacuna no DDL autoCreate)* |
| `incidents` | INT | Sim | Contador acumulado de reincidências *(lacuna no DDL autoCreate)* |

Índices definidos: `idx_hash` em `hash` e `idx_severity` em `severity`.

### Tabela referenciada: `error_tracker_users`
Há referência a uma tabela de associação usuário-erro (`error_tracker_users`) no código de `DatabaseHandler.php:57` com colunas `error_hash` e `usuario_id`, porém sua criação automática e injeção do usuário logado ainda não foram concluídas.

---

## Estratégia de Cache e Deduplicação (PSR-16)

Para evitar avalanche de alertas (*flood*), a biblioteca utiliza o padrão **PSR-16 SimpleCache** (`psr/simple-cache: ^3.0`).

- **Padrão de Chave**: `error_tracker_{hash}`
- **TTL Padrão**: 3.600 segundos (1 hora), configurável no construtor de `ErrorTracker`.
- **Mecanismo padrão de arquivo**:
  - `FileCache` grava arquivos individuais com nome `md5($key) . '.cache'`.
  - Conteúdo serializado via `serialize(['value' => ..., 'expires_at' => ...])` com bloqueio exclusivo `LOCK_EX`.
  - Diretório padrão: `sys_get_temp_dir() . '/ErrorTracker_Cache'` ou caminho customizado (ex.: `storage/logs/errors_cache`).

---

## Integrações Externas

### 1. GitHub REST API (v3)
- **Classe**: `HeliomarPM\ErrorTracker\Handlers\GitHubIssueHandler`
- **Endpoint**: `https://api.github.com/repos/{owner}/{repo}/issues`
- **Autenticação**: Bearer Token via Personal Access Token (PAT).
- **Payload**: Título formatado com nome da aplicação e severidade, corpo em Markdown colapsável (`<details>`) e labels (`bug`, `automated-report`, `severity:{nivel}`).
- **Conexão**: cURL síncrono com timeout estrito de 3 segundos.

### 2. E-mail (MTA nativo)
- **Classe**: `HeliomarPM\ErrorTracker\Handlers\EmailHandler`
- **Canal**: Função nativa `mail()` do PHP com supressão de warnings (`@mail`) e envio formatado em HTML UTF-8.
- **Filtro**: Dispara apenas para severidades autorizadas (padrão `[Severity::CRITICAL]`).

### 3. Log em Sistema de Arquivos
- **Classe**: `HeliomarPM\ErrorTracker\Handlers\FilelogHandler`
- **Formato**: Arquivo diário rotacionado `{logDirectory}/{severity}_{Ymd}.log`.
- **Gravação**: Append atômico com `FILE_APPEND | LOCK_EX`.

## Notas da Equipe
*(Espaço reservado para anotações manuais da equipe. A skill não altera esta seção.)*
