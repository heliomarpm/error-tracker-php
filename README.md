<div id="top" align="center">
<h1>
  <img src="./logo.png" alt="ErrorTracker" width="256" /><br>

  <a href="https://navto.me/heliomarpm" target="_blank"><img src="https://navto.me/assets/navigatetome-brand.png" width="32"/></a>
  [![CI Status](https://github.com/heliomarpm/error-tracker/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/heliomarpm/error-tracker/actions)
  [![CodeFactor][url-codefactor-badge]][url-codefactor]
  [![Packagist Version][url-packagist-badge]][url-packagist]
  [![Packagist Downloads][url-packagist-downloads-badge]][url-packagist]
</h1>

<div class="badges">

  [![Live Demo](https://img.shields.io/badge/Vercel-Live%20Demo-1C1E26?style=for-the-badge&logo=vercel&labelColor=1C1E26&color=00F2FE)](https://error-tracker-demo.vercel.app)
  [![GitHub Sponsors][url-github-sponsors-badge]][url-github-sponsors]
  [![PayPal][url-paypal-badge]][url-paypal]
  [![Ko-fi][url-kofi-badge]][url-kofi]
  [![Liberapay][url-liberapay-badge]][url-liberapay]

</div>
</div>

**ErrorTracker** é uma biblioteca PHP moderna para **rastreamento, diagnóstico e alerta de incidentes**. Captura falhas globais, previne vazamento de dados sensíveis (sanitização inteligente), evita avalanche de alertas (*anti-flood* via cache PSR-16), implementa **PSR-3 LoggerInterface** e despacha relatórios estruturados para múltiplos canais (Banco de Dados MySQL/SQLite, ChatOps Slack/Discord, GitHub Issues, E-mail e Arquivos de Log).

## ✨ Principais Recursos

* **Captura Global no Runtime:** Intercepta `Exceptions`, `Fatal Errors`, `Warnings` e `Notices` automaticamente.
* **Compatibilidade PSR-3:** Implementa `Psr\Log\LoggerInterface`, podendo ser injetado em qualquer framework ou container moderno.
* **Sanitização Inteligente:** Mascara automaticamente dados sensíveis em requisições (`$_POST`, `$_SERVER`), ocultando senhas totalmente e ofuscando e-mails, CPFs e Cartões de Crédito de forma parcial (ex: `hel***@gmail.com`, `***.***.789-10`).
* **Sistema Anti-flood (Deduplicação):** Integração com cache PSR-16 ([FileCache](src/Cache/FileCache.php) nativo ou Redis/Memcached) silenciando alertas idênticos dentro da janela de TTL.
* **Rota de Fuga (Failsafe):** Permite registrar um *callback* para assumir o controle da tela em falhas críticas (ex: redirecionar para tela 500 amigável ou emitir Problem Details JSON).
* **Templates Externos Customizáveis:** Templates desacoplados em `src/Templates/` para E-mail (HTML responsivo ou Texto Puro) e Log em arquivo com tags substituíveis (`{{severity}}`, `{{message}}`, `{{file}}`, `{{line}}`, etc.). Consulte o [Guia de Templates](docs/TEMPLATES.md).
* **Handlers Nativos:** (Consulte o [Guia Completo de Handlers](docs/HANDLERS.md))
  * **DatabaseHandler:** Suporte automático para MySQL, MariaDB e SQLite com contagem de reincidências (`incidents`) e auto-setup DDL.
  * **WebhookHandler:** Notificações estruturadas para canais de ChatOps (**Slack**, **Discord**, **Teams** ou Webhooks HTTP genéricos).
  * **GitHubIssueHandler:** Abertura automática de Issues formatadas em Markdown com stack trace e payload recolhível.
  * **EmailHandler:** Envio via `mail()` nativo em HTML estilizado ou texto puro, com assunto customizável.
  * **FilelogHandler:** Log em disco rotacionado diariamente com proteção `LOCK_EX`.

---

## 🌐 Demonstração ao Vivo (Vercel)

Você pode testar a biblioteca interativamente no navegador:
* 🚀 **Demo Online**: [Live Demo no Vercel](https://error-tracker-demo.vercel.app)
* Simulação de falhas leves (Notices/Warnings) e críticas (Exceptions/Fatal).
* Sanitização de formulários em tempo real (teste de senhas, cartões e CPFs).
* Simulador de estresse anti-flood (50 requisições simultâneas com deduplicação).
* Pré-visualização visual de e-mails renderizados e issues do GitHub sem sujar o servidor.

---

## 📦 Instalação

```bash
composer require heliomarpm/error-tracker
```

## 🚀 Como Usar (Quick Start)

Adicione a inicialização no arquivo de bootstrap do seu sistema (ex: `bootstrap.php` ou `index.php`):

```php
use HeliomarPM\ErrorTracker\ErrorTracker;
use HeliomarPM\ErrorTracker\Cache\FileCache;
use HeliomarPM\ErrorTracker\Handlers\DatabaseHandler;
use HeliomarPM\ErrorTracker\Handlers\WebhookHandler;
use HeliomarPM\ErrorTracker\Handlers\GitHubIssueHandler;
use HeliomarPM\ErrorTracker\Handlers\EmailHandler;
use HeliomarPM\ErrorTracker\Handlers\FilelogHandler;
use HeliomarPM\ErrorTracker\Severity;

// 1. Instancia o Cache de Arquivo (Retenção de 1 hora para deduplicação)
$cache = new FileCache(__DIR__ . '/storage/logs/errors_cache');
$tracker = new ErrorTracker($cache, 3600);

// 2. Registra os Handlers desejados
$pdo = new PDO("mysql:host=localhost;dbname=meu_banco", "root", "senha");
// Ou SQLite: $pdo = new PDO("sqlite:" . __DIR__ . "/storage/errors.sqlite");

$tracker->addHandler(new DatabaseHandler($pdo, 'system_errors', true))
        ->addHandler(new FilelogHandler(__DIR__ . '/storage/logs'))
        ->addHandler(new WebhookHandler('https://hooks.slack.com/services/...', format: 'slack'))
        ->addHandler(new GitHubIssueHandler('ghp_SEU_TOKEN_AQUI', 'usuario/repositorio'))
        ->addHandler(new EmailHandler('suporte@empresa.com', 'noreply@empresa.com', [Severity::CRITICAL]));
        // Dica: Todos os handlers aceitam 'notifySeverities' (ex: [Severity::CRITICAL, Severity::WARNING])

// 3. Define a Rota de Fuga (Failsafe em falhas críticas)
$tracker->onCritical(function ($context) {
    header("Location: /500.php?ref={$context->hash}");
});

// 4. Ativa o escudo global!
$tracker->register();
```

## 🛠️ Uso como Logger PSR-3 e Logs Manuais

Por implementar `Psr\Log\LoggerInterface`, você pode usar métodos padronizados do PHP-FIG ou injetar o `ErrorTracker` em containers de injeção de dependência:

```php
use Psr\Log\LogLevel;

// Métodos padrão da PSR-3
$tracker->info("Processamento de pedido #1042 iniciado.");
$tracker->warning("Estoque do produto X abaixo do limite mínimo.");
$tracker->error("Falha ao comunicar com gateway de pagamento.", ['pedido_id' => 1042]);
$tracker->critical("Banco de dados principal inacessível.");

// Uso com o método genérico log()
$tracker->log(LogLevel::ERROR, "Mensagem customizada", ['extra' => 'dados']);
```

## 🛡️ Criando Handlers Personalizados

Basta implementar a interface [HandlerInterface](src/Contracts/HandlerInterface.php):

```php
use HeliomarPM\ErrorTracker\Contracts\HandlerInterface;
use HeliomarPM\ErrorTracker\ErrorContext;

class TelegramHandler implements HandlerInterface
{
    public function report(ErrorContext $context): void
    {
        $mensagem = "🚨 [{$context->severity->name}] {$context->message} em {$context->file}:{$context->line}";
        // Disparo para a API do Telegram...
    }
}

$tracker->addHandler(new TelegramHandler());
```

---

## 📚 Documentação

Aprofunde-se nos recursos através dos guias especializados:

* 📡 **[Guia Completo de Handlers](docs/HANDLERS.md)** — Configuração detalhada do DatabaseHandler (MySQL/SQLite), WebhookHandler (Slack/Discord/Teams), GitHubIssueHandler, EmailHandler e FilelogHandler.
* 🎨 **[Guia de Templates e Interpolação](docs/TEMPLATES.md)** — Como formatar e customizar mensagens de alerta em HTML e texto puro com tags substituíveis e closures.
* 🤝 **[Guia de Contribuição](docs/CONTRIBUTING.md)** — Padrões de código PSR-12, Conventional Commits e execução da suíte de testes.
* 💬 **[Suporte e Comunidade](docs/SUPPORT.md)** — Diretrizes para abertura de bugs, suporte técnico e solicitação de novos recursos.
* 📜 **[Código de Conduta](docs/CODE_OF_CONDUCT.md)** — Diretrizes de convivência acolhedora e inclusiva da comunidade.

---

## 🤝 Contribuições e suporte

Existem várias formas de contribuir:

- ⭐ Marcar o repositório com uma estrela
- 🐞 Relatar bugs
- 💡 Sugerir funcionalidades ou novos drivers
- 🧾 Melhorar a documentação
- 📢 Compartilhar o projeto

Para propor uma melhoria ou contribuição, veja o [Guia de Contribuição](docs/CONTRIBUTING.md).\
Se precisar de ajuda ou encontrou um problema, consulte o [Suporte](docs/SUPPORT.md) ou abra uma [Issue](https://github.com/heliomarpm/error-tracker/issues). Leia também o [Código de Conduta](docs/CODE_OF_CONDUCT.md).

Obrigado a todos que já contribuíram:

[![Contribuidores](https://contrib.rocks/image?repo=heliomarpm/error-tracker)](https://github.com/heliomarpm/error-tracker/graphs/contributors)

###### Made with [contrib.rocks](https://contrib.rocks/image?repo=heliomarpm/error-tracker)

<!-- 
[![Contribuidores](https://contrib.nn.ci/api?repo=heliomarpm/error-tracker&no_bot=true)](https://github.com/heliomarpm/error-tracker/graphs/contributors)

###### Criado com [contrib.nn](https://contrib.nn.ci/?repo=heliomarpm/error-tracker&no_bot=true) 
-->

## 💵 Apoie o Projeto

Se você gosta do projeto, considere fazer uma doação ao desenvolvedor via GitHub Sponsors, Ko-fi, PayPal ou Liberapay — a escolha é sua. 😉

[![GitHub Sponsors][url-github-sponsors-badge]][url-github-sponsors]
[![PayPal][url-paypal-badge]][url-paypal]
[![Ko-fi][url-kofi-badge]][url-kofi]
[![Liberapay][url-liberapay-badge]][url-liberapay]

## 📝 Licença

Distribuído sob a licença [MIT](https://github.com/heliomarpm/error-tracker/blob/main/LICENSE) © [Heliomar P. Marques](https://github.com/heliomarpm).\
Sinta-se livre para utilizar, customizar e estender estas bibliotecas nos seus ambientes de desenvolvimento. [🔝](#top)

---
<!-- Sponsor badges -->
[url-github-sponsors]: https://github.com/sponsors/heliomarpm
[url-github-sponsors-badge]: https://img.shields.io/badge/GitHub%20-Sponsor-1C1E26?style=for-the-badge&labelColor=1C1E26&color=db61a2
[url-kofi]: https://ko-fi.com/heliomarpm
[url-kofi-badge]: https://img.shields.io/badge/kofi-1C1E26?style=for-the-badge&labelColor=1C1E26&color=ff5f5f
[url-liberapay]: https://liberapay.com/heliomarpm
[url-liberapay-badge]: https://img.shields.io/badge/liberapay-1C1E26?style=for-the-badge&labelColor=1C1E26&color=f6c915
[url-paypal]: https://bit.ly/paypal-sponsor-heliomarpm
[url-paypal-badge]: https://img.shields.io/badge/donate%20on-paypal-1C1E26?style=for-the-badge&labelColor=1C1E26&color=0475fe

<!-- Packagist badges -->
[url-packagist]: https://packagist.org/packages/heliomarpm/error-tracker
[url-packagist-badge]: https://img.shields.io/packagist/v/heliomarpm/error-tracker
[url-packagist-downloads-badge]: https://img.shields.io/packagist/dt/heliomarpm/error-tracker

<!-- other badges -->
[url-codefactor]: https://www.codefactor.io/repository/github/heliomarpm/error-tracker
[url-codefactor-badge]: https://www.codefactor.io/repository/github/heliomarpm/error-tracker/badge
