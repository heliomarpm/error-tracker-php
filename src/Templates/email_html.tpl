<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><!-- IF_EXCEPTION -->🚨 [{{severity}}] Alerta de Incidente - ErrorTracker<!-- /IF_EXCEPTION --><!-- IF_MESSAGE_ONLY -->ℹ️ [{{severity}}] Registro de Evento - ErrorTracker<!-- /IF_MESSAGE_ONLY --></title>
</head>
<body style="margin: 0; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0d1117; color: #e6edf3;">
  <div style="max-width: 720px; margin: 0 auto; background-color: #161b22; border: 1px solid #30363d; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);">
    
    <!-- Cabeçalho -->
    <div style="padding: 24px 32px; background: linear-gradient(135deg, #1f242c 0%, #161b22 100%); border-bottom: 1px solid #30363d;">
      <table style="width: 100%; border-collapse: collapse;">
        <tr>
          <td style="vertical-align: middle;">
            <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #f85149; background: rgba(248, 81, 73, 0.15); border: 1px solid rgba(248, 81, 73, 0.4); padding: 4px 12px; border-radius: 20px;">
              <!-- IF_EXCEPTION -->🚨 {{severity}}<!-- /IF_EXCEPTION --><!-- IF_MESSAGE_ONLY -->ℹ️ {{severity}}<!-- /IF_MESSAGE_ONLY -->
            </span>
          </td>
          <td style="text-align: right; vertical-align: middle; font-size: 12px; color: #8b949e; font-family: 'SFMono-Regular', Consolas, monospace;">
            {{timestamp}}
          </td>
        </tr>
      </table>

      <div style="margin-top: 16px;">
<!-- IF_EXCEPTION -->
        <span style="display: inline-block; font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; font-weight: 600; color: #a5b4fc; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.35); padding: 3px 10px; border-radius: 6px; margin-bottom: 8px;">
          {{error_title}}
        </span>
<!-- /IF_EXCEPTION -->
        <h1 style="margin: 4px 0 0 0; font-size: 20px; font-weight: 600; color: #ffffff; line-height: 1.4;">
          {{error_message}}
        </h1>
      </div>
    </div>

    <!-- Metadados e Informações Operacionais -->
    <div style="padding: 28px 32px;">

<!-- IF_ORIGIN -->
      <div style="background-color: #0d1117; border: 1px solid #30363d; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #8b949e; display: block; margin-bottom: 4px;">
          📍 Origem da Falha
        </span>
        <code style="font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; color: #58a6ff; word-break: break-all;">
          {{error_file}}:<strong style="color: #ffffff;">{{error_line}}</strong>
        </code>
      </div>
<!-- /IF_ORIGIN -->

<!-- IF_PREVIOUS -->
      <div style="background-color: rgba(210, 153, 34, 0.1); border: 1px solid rgba(210, 153, 34, 0.35); border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
        <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #d29922; display: block; margin-bottom: 4px;">
          ⚡ Causa Raiz (Exceção Anterior)
        </span>
        <div style="font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; color: #f0f6fc;">
          <strong style="color: #e3b341;">{{previous_class}}:</strong> {{previous_message}}
        </div>
      </div>
<!-- /IF_PREVIOUS -->

      <!-- Grade Operacional -->
      <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px; background-color: #0d1117; border: 1px solid #30363d; border-radius: 8px;">
        <tr>
          <td style="padding: 12px 16px; border-bottom: 1px solid #21262d; width: 50%;">
            <span style="font-size: 11px; text-transform: uppercase; color: #8b949e; display: block; font-weight: 600;">Aplicação / Ambiente</span>
            <span style="font-size: 13px; color: #f0f6fc; font-weight: 500;">{{app_name}} <span style="font-size: 11px; color: #a5b4fc; background: rgba(99, 102, 241, 0.2); padding: 2px 6px; border-radius: 4px;">{{environment}}</span></span>
          </td>
          <td style="padding: 12px 16px; border-bottom: 1px solid #21262d; width: 50%;">
            <span style="font-size: 11px; text-transform: uppercase; color: #8b949e; display: block; font-weight: 600;">Usuário</span>
            <span style="font-size: 13px; color: #f0f6fc; font-weight: 500;">{{usuario_nome}}</span>
          </td>
        </tr>
        <tr>
          <td style="padding: 12px 16px; border-bottom: 1px solid #21262d; width: 50%;">
            <span style="font-size: 11px; text-transform: uppercase; color: #8b949e; display: block; font-weight: 600;">Rota / Método</span>
            <code style="font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; color: #38bdf8;">{{request_method}} {{request_uri}}</code>
          </td>
          <td style="padding: 12px 16px; border-bottom: 1px solid #21262d; width: 50%;">
            <span style="font-size: 11px; text-transform: uppercase; color: #8b949e; display: block; font-weight: 600;">Pico de Memória</span>
            <span style="font-size: 13px; color: #4ade80; font-weight: 600;">{{memory_usage}}</span>
          </td>
        </tr>
        <tr>
          <td colspan="2" style="padding: 12px 16px; width: 100%;">
            <span style="font-size: 11px; text-transform: uppercase; color: #8b949e; display: block; font-weight: 600;">PHP / Servidor</span>
            <span style="font-size: 13px; color: #f0f6fc;">PHP <strong style="color: #ffffff;">{{php_version}}</strong> em {{server_name}}</span>
          </td>
        </tr>
      </table>

<!-- IF_TRACE -->
      <!-- Stack Trace -->
      <h3 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #58a6ff;">
        🔍 Rastreamento da Pilha (Stack Trace)
      </h3>
      <div style="background-color: #0d1117; border: 1px solid #30363d; border-radius: 8px; padding: 16px; margin-bottom: 24px; overflow-x: auto;">
        <pre style="margin: 0; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 12px; line-height: 1.6; color: #c9d1d9; white-space: pre-wrap; word-break: break-all;">{{stack_trace}}</pre>
      </div>
<!-- /IF_TRACE -->

      <!-- Payload Sanitizado -->
      <h3 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #3fb950;">
        📦 Payload e Ambiente (Sanitizado)
      </h3>
      <div style="background-color: #0d1117; border: 1px solid #30363d; border-radius: 8px; padding: 16px; margin-bottom: 24px; overflow-x: auto;">
        <pre style="margin: 0; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 12px; line-height: 1.6; color: #7ee787; white-space: pre-wrap;">{{payload}}</pre>
      </div>

      <!-- Rodapé do Card -->
      <div style="padding-top: 16px; border-top: 1px solid #21262d; font-size: 12px; color: #8b949e; display: flex; justify-content: space-between; align-items: center;">
        <span>Hash: <code style="color: #bc8cff; font-family: 'SFMono-Regular', Consolas, monospace;">{{hash}}</code></span>
        <span>Este é um e-mail automático gerado pelo sistema de monitoramento do {{server_name}}.</span>
      </div>

    </div>
  </div>
</body>
</html>
