# Diretrizes de Engenharia: HeliomarPM ErrorTracker

> Atualizado em: 2026-10-25

## Padrões de Código e Tipagem

| Padrão | Regra | Justificativa e Origem |
| :--- | :--- | :--- |
| Versão PHP | PHP 8.1+ com recursos modernos | Uso de `enum`, `readonly`, `match` e construtores promovidos em `src/` |
| Tipagem Estrita | Incluir `declare(strict_types=1);` em novos arquivos | Prevenir coerções silenciosas de tipos em bibliotecas |
| Estilo de Indentação | 2 espaços, codificação UTF-8, finais de linha LF | Configuração explícita em `.editorconfig` |
| Nomenclatura | PascalCase para classes/interfaces/enums; camelCase para métodos/variáveis; UPPER_CASE para cases de enum | Convenção PSR-12 e consistência com `src/Severity.php` e classes existentes |
| Namespaces | `HeliomarPM\ErrorTracker\...` | Configuração PSR-4 do `composer.json` |

## Organização e Estrutura

- **Contratos e Interfaces**: Devem residir exclusivamente em `src/Contracts/` (ex: `HandlerInterface.php`).
- **Implementações de Envio**: Novos canais devem residir em `src/Handlers/` e implementar `HandlerInterface`.
- **Implementações de Cache**: Devem residir em `src/Cache/` e implementar `Psr\SimpleCache\CacheInterface`.
- **DTOs**: Devem ser imutáveis com propriedades `public readonly` (ex: `ErrorContext.php`).

## Tratamento de Erros e Resiliência

1. **Isolamento de Falhas nos Handlers**: Nenhum `HandlerInterface` deve permitir que exceções internas vazem para o orquestrador `ErrorTracker`. Todas as chamadas em lote em `ErrorTracker::processError` são envolvidas em bloco `try/catch (Throwable)` e enviadas ao `error_log` do PHP.
2. **Prevenção de Loops Infinitos de Erro**: Em caso de falha de conexão (ex.: banco fora do ar, falha de cURL no GitHub ou no envio de e-mail), use sempre `error_log` nativo em vez de disparar uma nova exceção ou chamar o próprio tracker.
3. **Supressão Controlada**: Operações nativas arriscadas (como `mail()`) devem usar supressão com verificação de retorno booleano e fallback em log.

## Segurança e Sanitização

- Todas as requisições (`$_POST`, `$_GET`, `$_SERVER`) devem ser submetidas ao `DataSanitizer::sanitize` antes da persistência ou do envio para APIs externas.
- **Blackout Total (`********`)**: Aplicado a qualquer chave que contenha termos como `password`, `senha`, `token`, `authorization`, `secret`, `cvv`.
- **Máscaras Parciais**: E-mails preservam até 3 caracteres iniciais; CPFs mantêm dígitos finais; cartões mantêm últimos 4 dígitos.

## Padrão de Commits Git

- Formato: `<tipo>(escopo opcional): <gitmoji> <descrição no imperativo>`
- Idioma: Português (pt-br).
- Limite do título: Máximo de 72 caracteres, sem ponto final ao final.
- Exemplos:
  - `feat(handlers): :sparkles: adicionar adaptador para Discord Webhooks`
  - `fix(database): :bug: corrigir variaveis indefinidas no DatabaseHandler`
  - `docs(context): :memo: atualizar CONTEXT.md com novos endpoints`

## Política de Dependências

A biblioteca tem foco em ser ultraleve. Não adicione dependências pesadas de produção no `composer.json`. Para novos canais que dependam de serviços externos (ex: AWS SNS, Telegram, Discord), prefira usar contratos ou recursos nativos (cURL/HTTP nativo) ou permitir injeção de dependência cliente pelo desenvolvedor.

## Notas da Equipe
*(Espaço reservado para anotações manuais da equipe. A skill não altera esta seção.)*
