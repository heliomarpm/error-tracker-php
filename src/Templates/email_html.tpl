<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Alerta de Incidente - ErrorTracker</title>
</head>
<body style="margin: 0; padding: 24px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f1117; color: #e6edf3;">
  <div style="max-width: 680px; margin: 0 auto; background-color: #161b22; border: 1px solid #30363d; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);">
    
    <!-- Cabeçalho -->
    <div style="padding: 24px 32px; background: linear-gradient(135deg, #1f242c 0%, #161b22 100%); border-bottom: 1px solid #30363d;">
      <div style="display: flex; align-items: center; justify-content: space-between;">
        <span style="font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #f85149; background: rgba(248, 81, 73, 0.15); border: 1px solid rgba(248, 81, 73, 0.4); padding: 4px 10px; border-radius: 20px;">
          🚨 {{severity}}
        </span>
        <span style="font-size: 12px; color: #8b949e; font-family: monospace;">{{timestamp}}</span>
      </div>
      <h1 style="margin: 16px 0 0 0; font-size: 20px; font-weight: 600; color: #ffffff; line-height: 1.4;">
        {{message}}
      </h1>
      <p style="margin: 8px 0 0 0; font-size: 13px; color: #8b949e; font-family: monospace;">
        📍 {{file}} (linha: <strong style="color: #58a6ff;">{{line}}</strong>)
      </p>
    </div>

    <!-- Conteúdo -->
    <div style="padding: 32px;">
      
      <!-- Stack Trace -->
      <h3 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #58a6ff;">
        🔍 Stack Trace
      </h3>
      <div style="background-color: #0d1117; border: 1px solid #30363d; border-radius: 8px; padding: 16px; margin-bottom: 24px; overflow-x: auto;">
        <pre style="margin: 0; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 12px; line-height: 1.6; color: #c9d1d9; white-space: pre-wrap; word-break: break-all;">{{trace}}</pre>
      </div>

      <!-- Payload Sanitizado -->
      <h3 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #3fb950;">
        📦 Requisição & Ambiente (Sanitizado)
      </h3>
      <div style="background-color: #0d1117; border: 1px solid #30363d; border-radius: 8px; padding: 16px; margin-bottom: 24px; overflow-x: auto;">
        <pre style="margin: 0; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace; font-size: 12px; line-height: 1.6; color: #7ee787; white-space: pre-wrap;">{{payload}}</pre>
      </div>

      <!-- Rodapé do Card -->
      <div style="padding-top: 16px; border-top: 1px solid #21262d; font-size: 12px; color: #8b949e; display: flex; justify-content: space-between;">
        <span>Hash de Rastreio: <code style="color: #bc8cff; font-family: monospace;">{{hash}}</code></span>
        <span>HeliomarPM ErrorTracker</span>
      </div>

    </div>
  </div>
</body>
</html>
