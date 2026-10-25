# Contexto do Projeto: HeliomarPM ErrorTracker

> Atualizado em: 2026-10-25

## Propósito
Biblioteca PHP orientada a objetos para rastreamento de erros e exceções, sanitização de dados sensíveis em requisições, deduplicação anti-flood via cache PSR-16 e despacho para múltiplos canais (handlers: Banco de Dados, E-mail, Arquivo e GitHub Issues).

## Stack

| Componente | Tecnologia | Versão | Origem |
| :--- | :--- | :--- | :--- |
| Runtime | PHP | ^8.1 (inferido: enums, readonly, match) | `composer.json` / código em `src/` |
| Gerenciador | Composer | v2 | `composer.json` |
| Cache Contract | PSR-16 (`psr/simple-cache`) | `^3.0` | `composer.json` |
| Banco | PDO (MySQL / MariaDB inferido) | n/d | `src/Handlers/DatabaseHandler.php` |
| Testes | PHPUnit | n/d | `phpunit.xml` |

## Comandos

| Ação | Comando | Origem |
| :--- | :--- | :--- |
| Instalar dependências | `composer install` | `composer.json` |
| Executar testes | `vendor/bin/phpunit` | `phpunit.xml` (não verificado em scripts) |
| Servidor de demonstração | `php -S localhost:8000 -t demo` | inferido via `demo/index.php` |
| Análise estática | não verificado | n/d |
| Deploy | não verificado | n/d |

## Topologia de pastas

```text
src/
├── Cache/          # Implementação de cache PSR-16 em arquivo (FileCache)
├── Contracts/      # Contratos e interfaces públicas (HandlerInterface)
├── Handlers/       # Adaptadores de despacho (Database, Email, Filelog, GitHub)
├── DataSanitizer.php # Sanitização e ofuscação de dados sensíveis
├── ErrorContext.php  # DTO contendo payload, stack trace e hash MD5
├── ErrorTracker.php  # Orquestrador central e interceptador global de erros
└── Severity.php      # Enum de severidades (critical, warning, notice, etc.)
demo/               # Aplicação web de exemplo para teste manual e página 500
storage/            # Diretório padrão para logs e cache de deduplicação
```

## Índice da documentação

| Documento | Conteúdo |
| :--- | :--- |
| [AGENTS.md](./AGENTS.md) | Regras e restrições obrigatórias para assistentes de IA |
| [ARCHITECTURE.md](./ARCHITECTURE.md) | Estilo arquitetural, fluxo do erro, handlers e riscos |
| [DATA.md](./DATA.md) | Persistência PDO, esquema da tabela, cache e integrações |
| [PRD.md](./PRD.md) | Produto retroativo, funcionalidades, personas e lacunas |
| [GUIDELINE.md](./GUIDELINE.md) | Padrões de código PHP, sanitização, git e convenções |
| [TESTING.md](./TESTING.md) | Configuração PHPUnit, lacuna de testes e plano recomendado |
| [TASKS.md](./TASKS.md) | Backlog vivo de tarefas e débitos técnicos |

## Notas da Equipe
*(Espaço reservado para anotações manuais da equipe. A skill não altera esta seção.)*
