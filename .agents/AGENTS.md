# Diretrizes para Assistentes de IA

> Atualizado em: 2026-10-25

## Regras obrigatórias

1. **Tipagem estrita e PHP 8.1+**: use `declare(strict_types=1);`, propriedades `readonly`, enumerações tipadas (`Severity`) e construtores promovidos. (Origem: código existente em `src/`)
2. **Padrão PSR-4 e Estrutura de Namespaces**: respeite o namespace raiz `HeliomarPM\ErrorTracker\`. Novos canais de envio pertencem a `HeliomarPM\ErrorTracker\Handlers` e devem implementar `HeliomarPM\ErrorTracker\Contracts\HandlerInterface`. (Origem: `composer.json`, `src/Contracts/HandlerInterface.php`)
3. **Princípio Open/Closed**: novos canais de alerta devem ser criados como classes desacopladas que implementam `HandlerInterface`, sem acoplar regras de negócio dentro de `ErrorTracker.php`. (Origem: `src/ErrorTracker.php`)
4. **Sanitização compulsória de dados**: todo payload recebido de requisições (`post`, `get`, `server`) deve passar obrigatoriamente pelo `DataSanitizer` antes da geração do contexto e do despacho para handlers. (Origem: `src/ErrorTracker.php:164`)
5. **Commits no padrão Conventional Commits**: siga o formato `<tipo>(escopo opcional): <gitmoji> <descrição no imperativo>` em português (pt-br), com no máximo 72 caracteres no título e sem pontuação final. (Origem: regras globais do projeto)

## Restrições (o que não fazer)

- Não execute comandos destrutivos no banco (`DROP`, `TRUNCATE`, `DELETE` sem `WHERE`).
- Não faça commit de tokens sensíveis (ex: Personal Access Token do GitHub em testes ou demos).
- Não confunda o namespace `Handlers` com `Adapters` (no código a pasta e namespace são `Handlers/`).
- Não adicione dependências pesadas de produção ao `composer.json` além do contrato PSR-16.
- Não permita que exceções internas de um `Handler` quebrem a aplicação principal (absorver e registrar em `error_log`).

## Skills aplicáveis

Stack detectada: PHP 8.1+, PDO (MySQL/MariaDB), PSR-16. Use `-tech-php`, `-sql`, `-testing` e `-git`.

## Antes de concluir uma tarefa

1. Validar sintaxe PHP em arquivos alterados (`php -l <arquivo>`).
2. Garantir integridade de namespaces e contratos (`HandlerInterface`).
3. Executar a suíte de testes (`vendor/bin/phpunit`) assim que implementada.

## Notas da Equipe
*(Espaço reservado para anotações manuais da equipe. A skill não altera esta seção.)*
